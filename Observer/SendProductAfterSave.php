<?php

namespace EasySales\Integrari\Observer;

use EasySales\Integrari\Core\EasySales;
use EasySales\Integrari\Core\Transformers\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendProductAfterSave implements ObserverInterface
{
    private $easySales;
    /**
     * @var Product
     */
    private $product;

    public function __construct(EasySales $easySales, Product $product)
    {
        $this->easySales = $easySales;
        $this->product = $product;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $category */
        $product = $observer->getEvent()->getData('product');
        $this->product->setProduct($product);

        $this->easySales->execute("sendProduct", ['product' => $this->product->toArray()]);
    }
}
