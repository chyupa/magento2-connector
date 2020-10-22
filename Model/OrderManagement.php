<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\OrderManagementInterface;
use EasySales\Integrari\Core\Auth\CheckWebsiteToken;
use EasySales\Integrari\Core\Transformers\Order;
use EasySales\Integrari\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderManagement extends CheckWebsiteToken implements OrderManagementInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteria;

    /**
     * @var Order
     */
    private $_orderService;

    /**
     * CategoryManagement constructor.
     * @param Data $helperData
     * @param Request $request
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Order $orderService
     * @throws \Exception
     */
    public function __construct(
        Data $helperData,
        Request $request,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Order $orderService
    ) {
        parent::__construct($request, $helperData);

        $this->orderRepository = $orderRepository;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->_orderService = $orderService;
    }

    /**
     * @return array|mixed
     */
    public function getOrders()
    {
        $page = $this->request->getQueryValue('page', 1);
        $limit = $this->request->getQueryValue('limit', self::PER_PAGE);
        $this->searchCriteria->setPageSize(100)->setCurrentPage($page);

        $list = $this->orderRepository->getList($this->searchCriteria->create());
        $orders = [];

        foreach ($list->getItems() as $order) {
            $orders[] = $this->_orderService->transform($order)->toArray();
        }

        return [[
            'perPage' => $limit,
            'pages' => ceil($list->getTotalCount() / $limit),
            'curPage' => $page,
            'orders' => $orders,
        ]];
    }

    /**
     * @param string $orderId
     * @return array[]
     */
    public function updateOrder(string $orderId)
    {
        $searchCriteria = $this->searchCriteria
            ->addFilter('increment_id', $orderId, 'eq')->create();
        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();

        $order = end($orderList);
        $data = $this->request->getBodyParams();
        switch ($data['status']) {
            case 'Completed':
                $orderStatus = \Magento\Sales\Model\Order::STATE_COMPLETE;
                break;
            case 'Canceled':
                $orderStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                break;
            default:
                $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                break;
        }
        $order->setState($orderStatus)
            ->setStatus($orderStatus);
        try {
            $this->orderRepository->save($order);
        } catch (\Exception $exception) {
            return [[
                "success" => false,
                "message" => $exception->getMessage(),
            ]];
        }

        return [[
            "success" => true,
            "order" => $order->getIncrementId(),
        ]];
    }
}
