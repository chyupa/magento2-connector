<?php

namespace EasySales\Integrari\Api;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\RequestInterface;

interface CategoryManagementInterface
{
    /**
     * GET for Categories API
     * @return mixed
     */
    public function getCategories();

    /**
     * @param string $categoryId
     * @return mixed\
     */
    public function saveCategory(string $categoryId);
}
