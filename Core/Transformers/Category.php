<?php

namespace EasySales\Integrari\Core\Transformers;

class Category
{
    public function transform(\Magento\Catalog\Model\Category $category)
    {
        $this->data = [
            'category_website_id' => $category->getId(),
            'name' => $category->getName(),
        ];

        return $this;
    }

    public function toArray()
    {
        return $this->data;
    }
}
