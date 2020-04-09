<?php

namespace EasySales\Integrari\Api;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\RequestInterface;

interface AttributeManagementInterface
{
    const PER_PAGE = 500;

    /**
     * @return mixed
     */
    public function getAttributes();

    /**
     * @return Magento\Framework\Controller\Result\Json
     */
    public function saveAttribute(string $attributeId = null);
}
