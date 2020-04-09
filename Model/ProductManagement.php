<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Core\Transformers\Product;
use EasySales\Integrari\Api\ProductManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Webapi\Request;

class ProductManagement implements ProductManagementInterface
{
    private $_productRepository;

    private $_searchCriteria;

    private $_request;
    /**
     * @var Product
     */
    private $_productService;

    /**
     * CategoryManagement constructor.
     * @param Request $request
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Product $productService
     */
    public function __construct(
        Request $request,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Product $productService
    ) {
        $this->_request = $request;
        $this->_productRepository = $productRepository;
        $this->_searchCriteria = $searchCriteriaBuilder;
        $this->_productService = $productService;
    }


    /**
     * @return mixed
     */
    public function getProducts()
    {
        $page = $this->_request->getQueryValue('page', 1);
        $limit = $this->_request->getQueryValue('limit', self::PER_PAGE);
        $this->_searchCriteria->setPageSize($limit)->setCurrentPage($page);

        $list = $this->_productRepository->getList($this->_searchCriteria->create());
        $products = [];

        foreach ($list->getItems() as $product) {
            $products[] = $this->_productService->setProduct($product)->toArray();
        }

        return [[
            'perPage' => $limit,
            'pages' => ceil($list->getTotalCount() / $limit ),
            'curPage' => $page,
            'products' => $products,
        ]];
    }
}
