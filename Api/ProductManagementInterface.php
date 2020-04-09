<?php

namespace EasySales\Integrari\Api;

interface ProductManagementInterface
{
    const PER_PAGE = 500;

    /**
     * @return mixed
     */
    public function getProducts();
}
