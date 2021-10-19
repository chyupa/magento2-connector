<?php

namespace EasySales\Integrari\Api;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\RequestInterface;

interface CategoryManagementInterface
{

    const PER_PAGE = 500;

    /**
     * GET for Categories API
     * @return mixed
     */
    public function getCategories();
}
