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

        if ($product->getTypeId() === "configurable") {
            return;
        }

        if ($product->getData('easysales_should_send') === false) {
            return;
        }
        $this->product->setProduct($product);

        $transformed = $this->product->toArray();

        $shouldSendToEs = true;
        // check if all characteristics have value
        // for some reason, when a new product is created not all characteristics have values
        // in order to prevent sending wrong data, we don't send it at all
        foreach ($transformed['characteristics'] as $characteristic) {
            if (!isset($characteristic['value'])) {
                $shouldSendToEs = false;
                break;
            }
        }

        if ($shouldSendToEs) {
            $this->easySales->execute("sendProduct", ['product' => $transformed]);
        }
    }
}
