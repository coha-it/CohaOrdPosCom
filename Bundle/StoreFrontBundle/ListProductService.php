<?php
namespace CohaOrdPosCom\Bundle\StoreFrontBundle;

use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct;

class ListProductService implements ListProductServiceInterface {

    /** @var ListProductServiceInterface */
    private $service;

    public function __construct(ListProductServiceInterface $service) {
        $this->service = $service;
    }

    /**
     * To get detailed information about the selection conditions, structure and content of the returned object,
     * please refer to the linked classes.
     *
     * @see \Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface::get()
     *
     * @param array $numbers
     * @param Struct\ProductContextInterface $context
     *
     * @return Struct\ListProduct[] indexed by the product order number
     */
    public function getList(array $numbers, Struct\ProductContextInterface $context)
    {
        $products = $this->service->getList($numbers, $context);

        foreach ($products as $number => $product) {
            if(
                Shopware()->Config()->getByNamespace('CohaOrdPosCom', 'commentRequired')
                && Shopware()->Config()->getByNamespace('CohaOrdPosCom', 'showPositionCommentOnDetail')
                && (
                    !Shopware()->Config()->getByNamespace('CohaOrdPosCom', 'showOnlyOnSpecificProducts')
                    || $product->getAttribute('core')->get('coha_ord_pos_com_active')
                )
            ) {
                // disallow buy in listing!
                $product->setAllowBuyInListing(false);
                $products[$number] = $product;
            }
        }

        return $products;
    }

    /**
     * Returns a full \Shopware\Bundle\StoreFrontBundle\Struct\ListProduct object.
     * A list product contains all required data to display products in small views like listings, sliders or emotions.
     *
     * @param string $number
     * @param Struct\ProductContextInterface $context
     *
     * @return Struct\ListProduct
     */
    public function get($number, Struct\ProductContextInterface $context)
    {
        $products = $this->getList([$number], $context);

        return array_shift($products);
    }
}