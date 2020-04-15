<?php

namespace EasySales\Integrari\Core\Transformers;

use Magento\Catalog\Model\Category as MagentoCategory;

class Category extends BaseTransformer
{
    public function transform(MagentoCategory $category)
    {
        $this->data = [
            'category_website_id' => $category->getId(),
            'name' => $category->getName(),
        ];

        return $this;
    }
}
