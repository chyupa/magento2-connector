<?php

namespace EasySales\Integrari\Model\Config\Source;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class WeightUnits
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $units = [
            [
                'value' => null,
                'label' => 'Please choose',
            ],
            [
                'value' => 'kg',
                'label' => 'Kilograms',
            ],
            [
                'value' => 'g',
                'label' => 'Grams',
            ],
            [
                'value' => 'lb',
                'label' => 'Pounds',
            ],
            [
                'value' => 'oz',
                'label' => 'Ounces',
            ],
        ];

        return $units;
    }
}
