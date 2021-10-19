<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\ProductManagementInterface;
use EasySales\Integrari\Core\Auth\CheckWebsiteToken;
use EasySales\Integrari\Core\Transformers\Product;
use EasySales\Integrari\Helper\Data;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Gallery\Processor as ImageProcessor;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Action as ProductAction;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Model\Entity\Attribute\FrontendLabelFactory;
use Magento\Eav\Model\Entity\Attribute\GroupFactory;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class ProductManagement extends CheckWebsiteToken implements ProductManagementInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteria;

    /**
     * @var Product
     */
    private $productService;
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $sourceItemsBySku;
    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var mixed
     */
    private $defaultStockSource;

    /**
     * CategoryManagement constructor.
     *
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param GetSourceItemsBySkuInterface $sourceItemsBySku
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceRepositoryInterface $sourceRepository
     * @param Product $productService
     * @param Data $helperData
     * @throws \Exception
     */
    public function __construct(
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        GetSourceItemsBySkuInterface $sourceItemsBySku,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Product $productService,
        Data $helperData
    )
    {
        parent::__construct($request, $helperData);

        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->productService = $productService;
        $this->sourceItemsBySku = $sourceItemsBySku;
        $this->helperData = $helperData;

        $this->defaultStockSource = $this->helperData->getGeneralConfig('stock_source');
    }

    /**
     * @return mixed
     */
    public function getProducts()
    {
        $page = $this->request->getQueryValue('page', 1);
        $limit = $this->request->getQueryValue('limit', self::PER_PAGE);
        $this->searchCriteria
            ->addFilter('type_id', 'configurable', 'neq')
            ->setPageSize($limit)
            ->addFilter('store_id', $this->helperData->getGeneralConfig('store_id'))
            ->setCurrentPage($page);

        $list = $this->productRepository
            ->getList($this->searchCriteria->create());

        $products = [];

        foreach ($list->getItems() as $product) {
            $products[] = $this->productService->setProduct($product)->toArray();
        }

        return [[
            'perPage'  => $limit,
            'pages'    => ceil($list->getTotalCount() / $limit),
            'curPage'  => $page,
            'products' => $products,
        ]];
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        $data = $this->request->getBodyParams();
        try {
            if (empty($data['product_id']) && empty($data['sku'])) {
                throw new \Exception("Missing product identifier");
            }

            if (!empty($data['product_id'])) {
                $product = $this->productRepository->getById($data['product_id'], true, 0, true);
            } else {
                $product = $this->productRepository->get($data['sku']);

            }

            return [[
                "product" => $this->productService->setProduct($product)->toArray(),
            ]];

        } catch (\Exception $exception) {
            return [[
                "success" => false,
                "message" => $exception->getMessage(),
            ]];
        }
    }

    /**
     * @param string|null $productId
     * @return mixed|void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveProduct(string $productId = null)
    {
        $data = $this->request->getBodyParams();
        try {
            /** @var \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product */
            $product = $this->getNewOrExistingProduct($productId);

            // update stock after product save otherwise the new product quantity won't be reflected
            $stocks = $this->sourceItemsBySku->execute($product->getSku());

            $stockSourceItem = null;
            foreach ($stocks as $stock) {
                if ($stock->getSourceCode() === $this->defaultStockSource) {
                    $stockSourceItem = $stock;
                    break;
                }
            }

            // only update stock if there is a change
            if ($stockSourceItem && $stockSourceItem->getQuantity() !== $data['stock']) {
                $stockSourceItem->setQuantity($data['stock']);
                $stockSourceItem->save();
            }

        } catch (\Exception $exception) {
            return [[
                "success" => false,
                "message" => $exception->getMessage(),
            ]];
        }

        return [[
            "success" => true,
            "product" => $product->getId(),
            "stock" => $data['stock'],
        ]];
    }

    /**
     * Get product by id or create a new product object
     *
     * @param $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getNewOrExistingProduct($productId)
    {
        return $productId ? $this->productRepository->getById($productId, true, 0, true) : $this->productFactory->create();
    }
}
