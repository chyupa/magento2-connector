<?php

namespace EasySales\Integrari\Core\Transformers;

use Magento\Catalog\Api\Data\ProductAttributeInterface;

class Characteristic extends BaseTransformer
{
    /**
     * @param ProductAttributeInterface $attribute
     * @return $this
     */
    public function transform(ProductAttributeInterface $attribute)
    {
        $this->data = [
            'characteristic_website_id' => $attribute->getId(),
            'name' => $attribute->getName(),
        ];

        return $this;
    }
}
