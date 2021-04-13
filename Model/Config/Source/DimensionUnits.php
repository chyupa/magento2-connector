<?php

namespace EasySales\Integrari\Model\Config\Source;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class DimensionUnits
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
                'value' => 'mm',
                'label' => 'Millimeters',
            ],
            [
                'value' => 'cm',
                'label' => 'Centimeters',
            ],
            [
                'value' => 'm',
                'label' => 'Meters',
            ],
            [
                'value' => 'in',
                'label' => 'Inches',
            ],
        ];

        return $units;
    }
}
