<?php

namespace EasySales\Integrari\Api;

interface ProductManagementInterface
{
    const PER_PAGE = 500;

    /**
     * @return mixed
     */
    public function getProducts();

    /**
     * @param string|null $productId
     * @return Magento\Framework\Controller\Result\Json
     */
    public function saveProduct(string $productId = null);
}
