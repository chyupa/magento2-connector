<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Core\Transformers\Order;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Webapi\Request;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderManagement implements \EasySales\Integrari\Api\OrderManagementInterface
{
    const PER_PAGE = 10;

    private $_orderRepository;

    private $_searchCriteria;

    private $_request;
    /**
     * @var Order
     */
    private $_orderService;

    /**
     * CategoryManagement constructor.
     * @param Request $request
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Order $orderService
     */
    public function __construct(
        Request $request,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Order $orderService
    ) {
        $this->_request = $request;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria = $searchCriteriaBuilder;
        $this->_orderService = $orderService;
    }

    /**
     * @return array|mixed
     */
    public function getOrders()
    {
        $page = $this->_request->getQueryValue('page', 1);
        $limit = $this->_request->getQueryValue('limit', self::PER_PAGE);
        $this->_searchCriteria->setPageSize(100)->setCurrentPage($page);

        $list = $this->_orderRepository->getList($this->_searchCriteria->create());
        $orders = [];

        foreach ($list->getItems() as $order) {
            $orders[] = $this->_orderService->transform($order)->toArray();
        }
//die();
        return [[
            'perPage' => $limit,
            'pages' => ceil($list->getTotalCount() / $limit ),
            'curPage' => $page,
            'orders' => $orders,
        ]];
    }
}
