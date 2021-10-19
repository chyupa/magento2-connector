<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\CategoryManagementInterface;
use EasySales\Integrari\Core\Auth\CheckWebsiteToken;
use EasySales\Integrari\Helper\Data;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;
use Magento\Store\Model\Store;

class CategoryManagement extends CheckWebsiteToken implements CategoryManagementInterface
{
    /**
     * @var CategoryListInterface
     */
    private $categoryListRepo;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteria;

    /**
     * CategoryManagement constructor.
     * @param Data $helperData
     * @param RequestInterface $request
     * @param CategoryListInterface $categoryList
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @throws \Exception
     */
    public function __construct(
        Data $helperData,
        RequestInterface $request,
        CategoryListInterface $categoryList,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($request, $helperData);

        $this->categoryListRepo = $categoryList;
        $this->searchCriteria = $searchCriteriaBuilder;
    }

    /**
     * GET api for categories
     *
     * @inheritDoc
     */
    public function getCategories()
    {
        $page = $this->request->getQueryValue('page', 1);
        $limit = $this->request->getQueryValue('limit', self::PER_PAGE);
        $this->searchCriteria->setPageSize($limit)->setCurrentPage($page);
        $list = $this->categoryListRepo->getList($this->searchCriteria->create());

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
            'pages' => ceil($list->getTotalCount() / $limit),
            'curPage' => $page,
            'categories' => $categories,
        ]];
    }
}
