<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\CategoryManagementInterface;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;

class CategoryManagement implements CategoryManagementInterface
{
    const PER_PAGE = 10;
    /**
     * @var MagentoCategoryManagementInterface
     */
    private $_categoryListRepo;

    private $_categoryRepo;

    private $_searchCriteria;

    private $_request;
    private $_eventManager;

    /**
     * CategoryManagement constructor.
     * @param RequestInterface $request
     * @param CategoryListInterface $categoryManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        RequestInterface $request,
        CategoryListInterface $categoryList,
        CategoryRepositoryInterface $categoryRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->_request = $request;
        $this->_categoryListRepo = $categoryList;
        $this->_categoryRepo = $categoryRepo;
        $this->_searchCriteria = $searchCriteriaBuilder;
        $this->_eventManager = $eventManager;
    }

    /**
     * GET api for categories
     *
     * @inheritDoc
     */
    public function getCategories()
    {
        $page = $this->_request->getQueryValue('page', 1);
        $limit = $this->_request->getQueryValue('limit', self::PER_PAGE);
        $this->_searchCriteria->setPageSize($limit)->setCurrentPage($page);
        $list = $this->_categoryListRepo->getList($this->_searchCriteria->create());

        $categories = [];
        foreach ($list->getItems() as $category) {
            $categories[] = [
                "category_website_id" => $category->getId(),
                "parent_id" => $category->getParentId(),
                "name" => $category->getName(),
            ];
        }

        return [[
            'perPage' => $limit,
            'pages' => ceil($list->getTotalCount() / $limit ),
            'curPage' => $page,
            'categories' => $categories,
        ]];
    }

    /**
     * @inheritDoc
     */
    public function saveCategory($categoryId)
    {
        $data = $this->_request->getBodyParams();
        $category = $this->_categoryRepo->get($categoryId);
        $category->setName(9996665);
        $category->setIsActive(false);
////        $category->getObserver
//
//        $a = $this->_categoryRepo->save($category);
//        $this->_eventManager->
//        var_dump($a);die();

        return [
            "aaa" => "true",
        ];
    }
}

