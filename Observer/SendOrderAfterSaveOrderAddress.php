<?php

namespace EasySales\Integrari\Observer;

use EasySales\Integrari\Core\EasySales;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendOrderAfterSaveOrderAddress implements ObserverInterface
{
    private $easySales;
    /**
     * @var \EasySales\Integrari\Core\Transformers\Order
     */
    private $orderTransformer;

    public function __construct(EasySales $easySales, \EasySales\Integrari\Core\Transformers\Order $orderTransformer)
    {
        $this->easySales = $easySales;
        $this->orderTransformer = $orderTransformer;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Category $category */
        $address = $observer->getEvent()->getData('address');

        $transformed = $this->orderTransformer->transform($address->getOrder());

        $this->easySales->execute("sendOrder", ['order' => $transformed->toArray()]);
    }
}
