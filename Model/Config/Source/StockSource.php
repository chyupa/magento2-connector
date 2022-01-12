<?php

namespace EasySales\Integrari\Model\Config\Source;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class StockSource
{
    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * StockSource constructor.
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
//        SourceRepositoryInterface $sourceRepository
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled('Magento_InventoryApi')) {
            $this->sourceRepository = $objectManager->create('Magento\InventoryApi\Api\SourceRepositoryInterface');
        } else {
            $this->sourceRepository = null;
        }
    }
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $sourcesOptions = [
            'value' => null,
            'label' => 'Please choose'
        ];
        if ($this->sourceRepository) {
            $sources = $this->sourceRepository->getList();

            foreach ($sources->getItems() as $source) {
                $sourcesOptions[]= [
                    'value' => $source->getSourceCode(),
                    'label' => $source->getName(),
                ];
            }
        }

        return $sourcesOptions;
    }
}
