<?php

namespace EasySales\Integrari\Model\Config\Source;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Ean
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
    }
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->productAttributeRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        
        $characteristics = [
            'value' => null,
            'label' => 'Please choose'
        ];

        foreach ($attributes as $attribute) {
            if ($attribute->getFrontendLabel() && $attribute->getIsUserDefined() === '1') {
                $characteristics[]= [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $attribute->getFrontendLabel()
                ];
            }
        }

        return $characteristics;
    }
}
