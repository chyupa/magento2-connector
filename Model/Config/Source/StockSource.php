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
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->sourceRepository = $sourceRepository;
    }
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $sources = $this->sourceRepository->getList();

        $sourcesOptions = [
            'value' => null,
            'label' => 'Please choose'
        ];

        foreach ($sources->getItems() as $source) {
            $sourcesOptions[]= [
                'value' => $source->getSourceCode(),
                'label' => $source->getName(),
            ];
        }

        return $sourcesOptions;
    }
}
