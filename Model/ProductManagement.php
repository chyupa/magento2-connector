<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\ProductManagementInterface;
use EasySales\Integrari\Core\Transformers\Product;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Gallery\Processor as ImageProcessor;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;

class ProductManagement implements ProductManagementInterface
{
    private $_productRepository;
    private $_productFactory;
    private $_categoryManagement;
    private $_imageProcessor;

    private $_searchCriteria;

    private $_request;
    /**
     * @var Product
     */
    private $_productService;
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * CategoryManagement constructor.
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param CategoryLinkManagementInterface $categoryManagement
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ImageProcessor $imageProcessor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Product $productService
     */
    public function __construct(
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        CategoryLinkManagementInterface $categoryManagement,
        AttributeRepositoryInterface $attributeRepository,
        ImageProcessor $imageProcessor,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Product $productService
    ) {
        $this->_request = $request;
        $this->_productRepository = $productRepository;
        $this->_productFactory = $productFactory;
        $this->_categoryManagement = $categoryManagement;
        $this->_imageProcessor = $imageProcessor;
        $this->_searchCriteria = $searchCriteriaBuilder;
        $this->_productService = $productService;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @return mixed
     */
    public function getProducts()
    {
        $page = $this->_request->getQueryValue('page', 1);
        $limit = $this->_request->getQueryValue('limit', self::PER_PAGE);
        $this->_searchCriteria
            ->setPageSize($limit)
            ->setCurrentPage($page);

        $list = $this->_productRepository
            ->getList($this->_searchCriteria->create());

        $products = [];

        foreach ($list->getItems() as $product) {
            $products[] = $this->_productService->setProduct($product)->toArray();
        }

        return [[
            'perPage' => $limit,
            'pages' => ceil($list->getTotalCount() / $limit),
            'curPage' => $page,
            'products' => $products,
        ]];
    }

    /**
     * @param string|null $productId
     * @return mixed|void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function saveProduct(string $productId = null)
    {
        $data = $this->_request->getBodyParams();
        try {
            /** @var \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product */
            $product = $this->getNewOrExistingProduct($productId);

            $product->setName($data['name']);
            $product->setDescription($data['description']);

            if (isset($data['categories'])) {
                $categoryIds = array_map(function ($category) {
                    return $category['category_website_id'];
                }, $data['categories']);

                $this->_categoryManagement->assignProductToCategories($product->getSku(), $categoryIds);
            }

            $this->updateProductImages($product, $data['images']);

            if (isset($data['characteristics'])) {
                $this->updateProductCharacteristics($product, $data['characteristics']);
            }

            $this->_productRepository->save($product);
        } catch (\Exception $exception) {
            return [[
                "success" => false,
                "message" => $exception->getMessage(),
            ]];
        }

        return [[
            "success" => true,
            "category" => $product->getId(),
        ]];
    }

    /**
     * Update product images
     * For now it only changes the image order and nothing else
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param $images
     */
    private function updateProductImages(&$product, $images)
    {
        $sort_order = 1;

        foreach ($images as $image) {
            foreach ($product->getMediaGalleryImages() as $galleryImage) {
                if ($galleryImage->getUrl() === $image) {
                    $this->_imageProcessor->updateImage($product, $galleryImage->getFile(), [
                        'position' => $sort_order,
                    ]);
                }
            }

            $sort_order++;
        }
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param $characteristics
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateProductCharacteristics(&$product, $characteristics)
    {
        foreach ($characteristics as $characteristic) {
            $value = $characteristic['value'];
            $attribute = $this->attributeRepository->get(
                \Magento\Catalog\Api\Data\ProductAttributeInterface::ATTRIBUTE_ID,
                $characteristic['characteristic_website_id']
            );

            if ($attribute->getFrontendInput() === "boolean") {
                $value = false;
                if (in_array(strtolower($characteristic['value']), ['yes', 'da', 'true'])) {
                    $value = true;
                }
            }

            if ($attribute->getFrontendInput() === "multiselect") {
                $explodedValue = explode(",", $characteristic['value']);

                $value = array_map([$attribute, 'getOptionId'], $explodedValue);
            }

            $product->setCustomAttribute($attribute->getAttributeCode(), $value);
            $product->setData($attribute->getAttributeCode(), $value);
        }
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
        return $productId ? $this->_productRepository->getById($productId) : $this->_productFactory->create();
    }
}
