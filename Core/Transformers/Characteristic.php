<?php

namespace EasySales\Integrari\Core\Transformers;

class Characteristic
{
    public function transform(\Magento\Catalog\Api\Data\ProductAttributeInterface $attribute)
    {
        $this->data = [
            'characteristic_website_id' => $attribute->getId(),
            'name' => $attribute->getName(),
        ];

        return $this;
    }

    public function toArray()
    {
        return $this->data;
    }
}
