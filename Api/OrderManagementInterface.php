<?php

namespace EasySales\Integrari\Api;

interface OrderManagementInterface
{
    const PER_PAGE = 500;

    /**
     * @return mixed
     */
    public function getOrders();
}
