<?php

namespace EasySales\Integrari\Observer;

use EasySales\Integrari\Core\EasySales;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendCategoryAfterSave implements ObserverInterface
{
    private $easySales;
    /**
     * @var \EasySales\Integrari\Core\Transformers\Category
     */
    private $categoryTransformer;

    public function __construct(EasySales $easySales, \EasySales\Integrari\Core\Transformers\Category $categoryTransformer)
    {
        $this->easySales = $easySales;
        $this->categoryTransformer = $categoryTransformer;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Category $category */
        $category = $observer->getEvent()->getData('category');

        $transformed = $this->categoryTransformer->transform($category);

        $this->easySales->execute("sendCategory", ['category' => $transformed->toArray()]);
    }
}
