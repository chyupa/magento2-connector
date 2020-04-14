<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\CategoryManagementInterface;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;
use Magento\Store\Model\Store;

class CategoryManagement implements CategoryManagementInterface
{
    private $_categoryListRepo;

    private $_categoryFactory;
    private $_categoryRepo;

    private $_searchCriteria;

    private $_request;
    private $_eventConfig;

    /**
     * CategoryManagement constructor.
     * @param RequestInterface $request
     * @param CategoryListInterface $categoryList
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepo
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ConfigInterface $_eventManager
     */
    public function __construct(
        RequestInterface $request,
        CategoryListInterface $categoryList,
        CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ConfigInterface $_eventManager
    ) {
        $this->_request = $request;
        $this->_categoryListRepo = $categoryList;
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryRepo = $categoryRepo;
        $this->_searchCriteria = $searchCriteriaBuilder;
        $this->_eventConfig = $_eventManager;
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
            'pages' => ceil($list->getTotalCount() / $limit),
            'curPage' => $page,
            'categories' => $categories,
        ]];
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function saveCategory(string $categoryId = null)
    {
        $data = $this->_request->getBodyParams();

        $category = $this->getNewOrExistingCategory($categoryId);

        $category->setName($data['name']);
        $category->setStoreId(Store::DEFAULT_STORE_ID);
        $category->setUrlKey($category->formatUrlKey($data['name']));
        $category->setData('easysales_should_send', false);

        $this->_categoryRepo->save($category);

        return [[
            "success" => true,
            "category" => $category->getId(),
        ]];
    }

    /**
     * Get category by id or create a new category object
     *
     * @param $categoryId
     * @return \Magento\Catalog\Api\Data\CategoryInterface|\Magento\Catalog\Model\Category
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getNewOrExistingCategory($categoryId)
    {
        return $categoryId ? $this->_categoryRepo->get($categoryId) : $this->_categoryFactory->create();
    }
}
