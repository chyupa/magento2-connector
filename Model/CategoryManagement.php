<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\CategoryManagementInterface;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;
use Magento\Framework\Event\ConfigInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Registry;
use Magento\Framework\ObjectManagerInterface;

class CategoryManagement implements CategoryManagementInterface
{
    /**
     * @var MagentoCategoryManagementInterface
     */
    private $_categoryListRepo;

    private $_categoryRepo;

    private $_searchCriteria;

    private $_request;
    private $_eventManager;
    private $registry;

    /**
     * CategoryManagement constructor.
     * @param RequestInterface $request
     * @param CategoryListInterface $categoryManagement
     * @param CategoryFactory $categoryRepo
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ConfigInterface $_eventManager
     * @param Registry $registry
     */
    public function __construct(
        RequestInterface $request,
        CategoryListInterface $categoryList,
        CategoryFactory $categoryRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ConfigInterface $_eventManager,
        ObjectManagerInterface $objectManager,
    Registry $registry
    ) {
        $this->_request = $request;
        $this->_categoryListRepo = $categoryList;
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
            'pages' => ceil($list->getTotalCount() / $limit ),
            'curPage' => $page,
            'categories' => $categories,
        ]];
    }

    /**
     * @inheritDoc
     */
    public function saveCategory($categoryId = null)
    {

        $data = $this->_request->getBodyParams();
        $category = $this->_categoryRepo->create();

        if ($categoryId) {
            $category->load($categoryId);
        }
        $category->setName($data['name']);
        $category->setStoreId(Store::DEFAULT_STORE_ID);
        $category->setUrlKey($category->formatUrlKey($data['name']));
        $category->setData('easysales_should_send', false);

        $category->save();

        return [[
            "success" => true,
            "category" => $category->getId(),
        ]];
    }
}

