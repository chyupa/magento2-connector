<?php

namespace EasySales\Integrari\Observer;

use EasySales\Integrari\Core\EasySales;
use EasySales\Integrari\Core\Transformers\Order;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendOrderAfterSave implements ObserverInterface
{
    private $easySales;
    /**
     * @var Order
     */
    private $order;

    public function __construct(EasySales $easySales, Order $order)
    {
        $this->easySales = $easySales;
        $this->order = $order;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $category */
        $order = $observer->getEvent()->getData('order');
        if ($order->getData('easysales_should_send') === false) {
            return;
        }

        $transformed = $this->order->transform($order);

        if (!empty($transformed->toArray()['order_date'])) {
            $this->easySales->execute("sendOrder", ['order' => $transformed->toArray()]);
        }
    }
}
