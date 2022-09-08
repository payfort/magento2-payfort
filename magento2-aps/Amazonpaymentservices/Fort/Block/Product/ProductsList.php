<?php
namespace Amazonpaymentservices\Fort\Block\Product;

class ProductsList extends \Magento\CatalogWidget\Block\Product\ProductsList
{

    protected function _construct()
    {
        $this->setTemplate('Amazonpaymentservices_Fort::product/widget/content/grid.phtml');
    }
}
