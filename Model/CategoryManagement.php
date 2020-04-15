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
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepo;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteria;

    /**
     * @var ConfigInterface
     */
    private $eventConfig;

    /**
     * CategoryManagement constructor.
     * @param Data $helperData
     * @param RequestInterface $request
     * @param CategoryListInterface $categoryList
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepo
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ConfigInterface $eventManager
     * @throws \Exception
     */
    public function __construct(
        Data $helperData,
        RequestInterface $request,
        CategoryListInterface $categoryList,
        CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ConfigInterface $eventManager
    ) {
        parent::__construct($request, $helperData);

        $this->categoryListRepo = $categoryList;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepo = $categoryRepo;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->eventConfig = $eventManager;
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

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function saveCategory(string $categoryId = null)
    {
        $data = $this->request->getBodyParams();

        $category = $this->getNewOrExistingCategory($categoryId);

        $category->setName($data['name']);
        $category->setStoreId(Store::DEFAULT_STORE_ID);
        $category->setUrlKey($category->formatUrlKey($data['name']));
        $category->setData('easysales_should_send', false);

        $this->categoryRepo->save($category);

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
        return $categoryId ? $this->categoryRepo->get($categoryId) : $this->categoryFactory->create();
    }
}
