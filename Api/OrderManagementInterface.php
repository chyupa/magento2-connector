<?php

namespace EasySales\Integrari\Api;

interface OrderManagementInterface
{
    const PER_PAGE = 500;

    /**
     * @return mixed
     */
    public function getOrders();

    /**
     * @param string $orderId
     * @return mixed
     */
    public function updateOrder(string $orderId);

    /**
     * @param string $orderId
     * @return mixed
     */
    public function getOrder(string $orderId);
}
