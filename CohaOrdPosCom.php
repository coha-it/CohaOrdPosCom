<?php
namespace CohaOrdPosCom;

use Doctrine\Common\Collections\ArrayCollection;
use CohaOrdPosCom\Bundle\StoreFrontBundle\ListProductService;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Bundle\AttributeBundle\Service\DataLoader;
use Shopware\Bundle\AttributeBundle\Service\DataPersister;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ProductService;
use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Configurator\Group;
use Shopware\Bundle\StoreFrontBundle\Struct\Product;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Theme\LessDefinition;
use Shopware\Models\Attribute\OrderBasket;

class CohaOrdPosCom extends Plugin {

    private $insertBasketID = null;
    private $insertArticleID = null;
    private $insertArticleNumber = null;

	public static function getSubscribedEvents()
	{
		return [
			'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onPostDispatchCheckout',

            // basket
            'Shopware_Modules_Basket_AddArticle_Start' => 'addArticle',

            // service
            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.list_product_service' => 'decorateShopwareStorefrontListProductService'
		];
	}

    /**
     * @param EventArgs $args
     * @return bool|null
     */
    public function addArticle(\Enlight_Event_EventArgs $args)
    {
        $orderNumber = $args->get('id');
        $quantity = $args->get('quantity');

        /** @var Request $request */
        $request = $this->container->get('front')->Request();
        $cohaOrdPosCom = $request->get('coha_ord_pos_com', null);

        // if no comment is provided, the article has not to be an own order position.
        if($cohaOrdPosCom === null) {
            return null;
        }

        $id = $this->insertInToBasket($orderNumber, $quantity, $cohaOrdPosCom);

        // non false value stops original method from being executed.
        return $id;
    }

    /**
     * @param $ordernumber
     * @param $quantity
     * @param $comment
     *
     * @return mixed
     */
    private function insertInToBasket($ordernumber, $quantity, $comment)
    {
        /** @var ContextService $contextService */
        $contextService = $this->container->get('shopware_storefront.context_service');

        /** @var Product $article */
        $article = $this->getOriginalArticle($ordernumber);

        if($article->isCloseouts()) {
            $inStock = $article->getStock();
            $inBasketQb = $queryBuilder = $this->container->get('dbal_connection')->createQueryBuilder();
            $inBasketQb->select('SUM(sob.quantity)')
                ->from('s_order_basket', 'sob')
                ->where('sob.sessionID = :sessionId')
                ->setParameter('sessionId', Shopware()->Session()->offsetGet('sessionId'))
                ->andWhere('sob.ordernumber = :ordernumber')
                ->setParameter('ordernumber', $ordernumber)
                ->groupBy('sob.ordernumber')
            ;
            $inBasket = (int) $inBasketQb->execute()->fetchColumn();

            if($quantity > ($inStock-$inBasket)) {
                return false;
            }
        }

        $queryBuilder = $this->container->get('dbal_connection')->createQueryBuilder();
        $queryBuilder->insert('s_order_basket')
                     ->values([
                         'sessionID' => ':sessionID',
                         'userID' => ':userID',
                         'articlename' => ':articlename',
                         'articleID' => ':articleID',
                         'ordernumber' => ':ordernumber',
                         'shippingfree' => ':shippingfree',
                         'quantity' => ':quantity',
                         'price' => ':price',
                         'netprice' => ':netprice',
                         'tax_rate' => ':tax_rate',
                         'datum' => ':datum',
                         'modus' => ':modus',
                         'esdarticle' => ':esdarticle',
                         'partnerID' => ':partnerID',
                         'lastviewport' => ':lastviewport',
                         'useragent' => ':useragent',
                         'config' => ':config',
                         'currencyFactor' => ':currencyFactor'
                     ]);

        $queryBuilder->setParameter('sessionID', $this->container->get('Session')->get('sessionId'));
        $queryBuilder->setParameter('userID', $this->container->get('Session')->get('sUserId') || 0);

        if(Shopware()->Config()->getByNamespace('CohaOrdPosCom', 'positionCommentInBasketName')) {
            $queryBuilder->setParameter('articlename', $article->getName() . ' [' . $comment . '] ' . $this->getAdditionaltext($article));
        } else {
            $queryBuilder->setParameter('articlename', $article->getName() . ' ' . $this->getAdditionaltext($article));
        }

        $queryBuilder->setParameter('articleID', $article->getId());
        $queryBuilder->setParameter('ordernumber', $ordernumber);
        $queryBuilder->setParameter('shippingfree', $article->isShippingFree());
        $queryBuilder->setParameter('quantity', $quantity);
        $queryBuilder->setParameter('price', $article->getVariantPrice()->getCalculatedPrice());

        if($contextService->getContext()->getCurrentCustomerGroup()->displayGrossPrices()) {
            $queryBuilder->setParameter('netprice', $article->getVariantPrice()->getCalculatedPrice()/$article->getTax()->getTax());
        } else {
            $queryBuilder->setParameter('netprice', $article->getVariantPrice()->getCalculatedPrice());
        }

        $queryBuilder->setParameter('tax_rate', $article->getTax());
        $queryBuilder->setParameter('datum', date("Y-m-d H:i:s"));
        // modus 0 is a normal article
        $queryBuilder->setParameter('modus', 0);
        $queryBuilder->setParameter('esdarticle', 0);
        $queryBuilder->setParameter('partnerID', '');
        $queryBuilder->setParameter('lastviewport', '');
        $queryBuilder->setParameter('useragent', '');
        $queryBuilder->setParameter('config', '');
        $queryBuilder->setParameter('currencyFactor', 1);

        $queryBuilder->execute();

        $lastInsertId = $this->container->get('dbal_connection')->lastInsertId();

        $this->setCommentInTitleFlag($lastInsertId, $comment);
        $this->insertBasketID = $lastInsertId;
        $this->insertArticleID = $article->getId();
        $this->insertArticleNumber = $ordernumber;

        return $lastInsertId;
    }

    private function getAdditionaltext(Product $product) {
        $additionaltext = '';

        /** @var Group $value */
        foreach ($product->getConfiguration() as $value) {
            $additionaltext .= $value->getName() . ': ';

            foreach ($value->getOptions() as $option) {
                $additionaltext .= $option->getName() . ' ';
            }
        }

        return $additionaltext;
    }

    private function setCommentInTitleFlag($basketPositionId, $comment) {
        /** @var DataLoader $dataLoaderService */
        $dataLoaderService = $this->container->get('shopware_attribute.data_loader');
        /** @var DataPersister $dataPersisterService */
        $dataPersisterService = $this->container->get('shopware_attribute.data_persister');

        $attributes = $dataLoaderService->load('s_order_basket_attributes', $basketPositionId);

        if($attributes === false) {
            $attributes = [];
        }

        $attributes['coha_ord_pos_com'] = $comment;

        $dataPersisterService->persist(
            $attributes,
            's_order_basket_attributes',
            $basketPositionId
        );
    }

    /**
     * @param $ordernumber
     *
     * @return Product
     */
    private function getOriginalArticle($ordernumber) {
        /** @var ProductService $productService */
        $productService = $this->container->get('shopware_storefront.product_service');

        /** @var ContextService $contextService */
        $contextService = $this->container->get('shopware_storefront.context_service');

        return $productService->get($ordernumber, $contextService->getContext());
    }

	public function install(InstallContext $context)
	{
		$this->createAttributes();
		$context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);

		return true;
	}

	public function update(UpdateContext $context)
	{
		$this->createAttributes();
		$context->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);

		return true;
	}

	public function uninstall(UninstallContext $context)
	{
		if(!$context->keepUserData()) {
			$this->deleteAttributes();
		}
		$context->scheduleClearCache(UninstallContext::CACHE_LIST_ALL);
		return true;
	}

	public function activate(ActivateContext $context)
	{
		$context->scheduleClearCache(ActivateContext::CACHE_LIST_FRONTEND);
		return true;
	}

	public function deactivate(DeactivateContext $context)
	{
		$context->scheduleClearCache(DeactivateContext::CACHE_LIST_FRONTEND);
		return true;
	}

	/**
	 * create additional attributes
	 */
	private function createAttributes()
	{
		/** @var CrudService $service */
		$service = Shopware()->Container()->get('shopware_attribute.crud_service');
		$service->update('s_order_details_attributes', 'coha_ord_pos_com', 'text', [
			'label' => 'Bestellpositionskommentar:',
			'displayInBackend' => true,
			'position' => 0,
			'custom' => true,
		]);
		$service->update('s_order_basket_attributes', 'coha_ord_pos_com', 'text', [
			'label' => 'Bestellpositionskommentar:',
			'displayInBackend' => true,
			'position' => 0,
			'custom' => true,
		]);

		// Article-Attributes
        $service->update('s_articles_attributes', 'coha_ord_pos_com_active', 'boolean', [
            'label' => '[Bestellpositionskommentar] anzeigen:',
            'displayInBackend' => true,
            'position' => 0,
            'custom' => true,
        ]);

        $service->update('s_articles_attributes', 'coha_ord_pos_com_by_qty', 'boolean', [
            'label' => '[Bestellpositionskommentar] nach Menge erweitern:',
            'displayInBackend' => true,
            'position' => 0,
            'custom' => true,
        ]);

        $service->update('s_articles_attributes', 'coha_ord_pos_com_text', 'string', [
            'label' => '[Bestellpositionskommentar] Individueller Text:',
            'displayInBackend' => true,
            'position' => 0,
            'custom' => true,
        ]);

        $service->update('s_articles_attributes', 'coha_ord_pos_com_placeholder', 'string', [
            'label' => '[Bestellpositionskommentar] Individueller Platzhalter:',
            'displayInBackend' => true,
            'position' => 0,
            'custom' => true,
        ]);

        $this->regenerateModels();
	}

	private function deleteAttributes() {
		/** @var CrudService $service */
		$service = Shopware()->Container()->get('shopware_attribute.crud_service');

		$service->delete('s_order_details_attributes', 'coha_ord_pos_com');
		$service->delete('s_order_basket_attributes', 'coha_ord_pos_com');
		$service->delete('s_articles_attributes', 'coha_ord_pos_com_active');
        $service->delete('s_articles_attributes', 'coha_ord_pos_com_by_qty');
		$service->delete('s_articles_attributes', 'coha_ord_pos_com_text');
        $service->delete('s_articles_attributes', 'coha_ord_pos_com_placeholder');

		$this->regenerateModels();
	}

    private function regenerateModels() {
        $metaDataCache = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();
        Shopware()->Models()->generateAttributeModels(
            [
                's_order_details_attributes',
                's_order_basket_attributes',
                's_articles_attributes',
            ]
        );
    }

	public function onPostDispatchCheckout(\Enlight_Event_EventArgs $args) {
		if (!Shopware()->Config()->getByNamespace('CohaOrdPosCom', 'show')) {
			// abort if shop is not activated
			return;
		}

		/**@var $subject \Shopware_Controllers_Frontend_Checkout*/
		$subject  = $args->getSubject();
		$request  = $subject->Request();
		$action   = $request->getActionName();
		$view     = $subject->View();

		// Save to basket before basket is saved to order.
		if($action === 'ajaxCart') {
		    return;
		}

		if($action === 'confirm' || $action === 'cart') {
			$view->assign('cohaOrdPosComs', $this->getBasketAttributes());
		}

		if($action === 'ajax_add_article') {

		    if($this->insertBasketID !== null && $this->insertArticleNumber !== null && $this->insertArticleID) {
                $view->sArticleName = Shopware()->Modules()->Articles()->sGetArticleNameByOrderNumber($this->insertArticleNumber);
                if (!empty($this->insertArticleID)) {
                    $basket = $subject->getBasket();
                    foreach ($basket['content'] as $item) {
                        if ($item['id']==$this->insertBasketID) {
                            $view->sArticle = $item;
                            break;
                        }
                    }
                }

                if (Shopware()->Config()->get('similarViewedShow', true)) {
                    $view->sCrossSimilarShown = $subject->getSimilarShown($this->insertArticleID);
                }

                if (Shopware()->Config()->get('alsoBoughtShow', true)) {
                    $view->sCrossBoughtToo = $subject->getBoughtToo($this->insertArticleID);
                }
            }
        }
	}

	/**
	 * Save attributes to basket in database.
	 *
	 * @param $basketID
	 * @param $comment
	 *
	 * @return \Zend_Db_Statement_Pdo
	 */
	function updateBasketPositionComment($basketID, $comment) {
	    // .. in attribute
		$sql = "UPDATE
			s_order_basket sob
		INNER JOIN s_order_basket_attributes soba ON (
			soba.basketID = sob.id
		)
		SET
			soba.coha_ord_pos_com = ?
		WHERE
			sob.id = ?
			AND sessionID = ?";

		$result = Shopware()->Db()->query(
			$sql,
			array(
				$comment,
				$basketID,
				Shopware()->Session()->get("sessionId")
			)
		);

		// todo: .. in title

		return $result;
	}

	public function insertBasketPositionComment($ordernumber, $comment) {

		$comment = trim($comment);

		if($comment === "") {
			return;
		}

		$sql = "UPDATE
			s_order_basket sob
		INNER JOIN s_order_basket_attributes soba ON (
			soba.basketID = sob.id
		)
		SET
			soba.coha_ord_pos_com = ?
		WHERE
			sob.ordernumber = ?
			AND sessionID = ?";

		$result = Shopware()->Db()->query(
			$sql,
			array(
				$comment,
				$ordernumber,
				Shopware()->Session()->get("sessionId")
			)
		);

		return $result;
	}

	/**
	 * Get basket attributes from database.
	 */
	private function getBasketAttributes() {

		$sql = "SELECT
			sob.id,
			sob.ordernumber,
			soba.coha_ord_pos_com as attribute
		FROM
			s_order_basket sob
		INNER JOIN s_order_basket_attributes soba ON (
			soba.basketID = sob.id
		)
		WHERE
			sessionID = ?";

		$result = Shopware()->Db()->fetchAll(
			$sql,
			array(
				Shopware()->Session()->get("sessionId")
			)
		);

		$structuredResult = array();
		foreach ($result as $key => $item) {
			$structuredResult[$item['id']] = $item['attribute'];
		}

		return $structuredResult;
	}

    public function decorateShopwareStorefrontListProductService() {
        /** @var ListProductServiceInterface $coreService */
        $coreService = $this->container->get('shopware_storefront.list_product_service');
        $decoratedService = new ListProductService($coreService);

        $this->container->set('shopware_storefront.list_product_service', $decoratedService);
    }
}