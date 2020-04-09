<?php

namespace EasySales\Integrari\Observer;

use EasySales\Integrari\Core\EasySales;
use EasySales\Integrari\Core\Transformers\Characteristic;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Entity\Attribute;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendCharacteristicAfterSave implements ObserverInterface
{
    private $easySales;
    /**
     * @var Characteristic
     */
    private $characteristicTransformer;

    public function __construct(EasySales $easySales, Characteristic $characteristicTransformer)
    {
        $this->easySales = $easySales;
        $this->characteristicTransformer = $characteristicTransformer;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Attribute $category */
        $characteristic = $observer->getEvent()->getData('attribute');
        if ($characteristic->getData('easysales_should_send') === false) {
            return;
        }

        $transformed = $this->characteristicTransformer->transform($characteristic);

        $this->easySales->execute("sendCharacteristic", ['characteristic' => $transformed->toArray()]);
    }
}
