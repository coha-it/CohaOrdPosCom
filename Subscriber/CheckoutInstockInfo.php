<?php
namespace CohaOrdPosCom\Subscriber;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Hook_HookArgs;
use PDO;
use Shopware\Components\DependencyInjection\Container;
use Zend_Date;

class CheckoutInstockInfo implements SubscriberInterface {
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Checkout::getInstockInfo::after' => 'afterCheckoutInstockInfo',
        ];
    }

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    public function afterCheckoutInstockInfo(Enlight_Hook_HookArgs $args) {
        $ordernumber = $args->getArgs()[0];
        $quantity = (int) $args->getArgs()[1];
        $return = $args->getReturn();

        $qb = $this->connection->createQueryBuilder();
        $qb->select([
            'sad.instock',
            'sa.laststock as articleLaststock',
            'sad.laststock as variantLaststock',
            'saa.coha_ord_pos_com_active',
        ])
            ->from('s_articles_details', 'sad')
            ->innerJoin('sad', 's_articles', 'sa', 'sa.id = sad.articleID')
            ->leftJoin('sad', 's_articles_attributes', 'saa', 'saa.articledetailsID = sad.id')
            ->where('sad.ordernumber = :ordernumber')
            ->setParameter('ordernumber', $ordernumber)
        ;

        $article = $qb->execute()->fetch(PDO::FETCH_ASSOC);

        if(Shopware()->Config()->getByNamespace('CohaOrdPosCom', 'showPositionCommentOnDetail')
           && (
               Shopware()->Config()->getByNamespace('CohaOrdPosCom', 'showOnlyOnSpecificProducts') == false
               || $article['coha_ord_pos_com_active'] == 1
           )
            && (
                $article['articleLaststock'] == 1
                || $article['variantLaststock'] == 1
           )
        ) {
            // Comment is added on adding article to basket.

            $inBasketQb = $queryBuilder = $this->connection->createQueryBuilder();
            $inBasketQb->select('SUM(sob.quantity)')
                       ->from('s_order_basket', 'sob')
                       ->where('sob.sessionID = :sessionId')
                       ->setParameter('sessionId', $this->container->get('session')->get('sessionId'))
                       ->andWhere('sob.ordernumber = :ordernumber')
                       ->setParameter('ordernumber', $ordernumber)
                       ->groupBy('sob.ordernumber')
            ;
            $inBasket = (int) $inBasketQb->execute()->fetchColumn();

            if($quantity > ($article['instock']-$inBasket)) {
                return Shopware()->Snippets()->getNamespace('frontend/plugins/coha_ord_pos_com/index')->get('productWithCommentNotAddedToBasketMsg');
            }
        }

        return $return;
    }
}