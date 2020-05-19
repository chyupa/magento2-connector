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
use Magento\Eav\Model\Entity\Attribute\OptionLabel;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
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
     * @var CategoryLinkManagementInterface
     */
    private $categoryManagement;

    /**
     * @var ImageProcessor
     */
    private $imageProcessor;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteria;

    /**
     * @var Product
     */
    private $productService;
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;
    /**
     * @var ProductAction
     */
    private $productAction;
    /**
     * @var GroupFactory
     */
    private $groupFactory;
    /**
     * @var AttributeFactory
     */
    private $attributeFactory;
    /**
     * @var AttributeManagementInterface
     */
    private $attributeManagement;
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $sourceItemsBySku;
    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;
    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var mixed
     */
    private $defaultStockSource;
    /**
     * @var AttributeOptionLabelInterface
     */
    private $attributeOptionLabel;
    /**
     * @var Option
     */
    private $option;
    /**
     * @var AttributeOptionManagementInterface
     */
    private $attributeOptionManagement;

    /**
     * CategoryManagement constructor.
     *
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param ProductAction $productAction
     * @param CategoryLinkManagementInterface $categoryManagement
     * @param AttributeRepositoryInterface $attributeRepository
     * @param AttributeManagementInterface $attributeManagement
     * @param GroupFactory $groupFactory
     * @param AttributeFactory $attributeFactory
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     * @param FrontendLabelFactory $attributeOptionLabel
     * @param \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $option
     * @param GetSourceItemsBySkuInterface $sourceItemsBySku
     * @param ImageProcessor $imageProcessor
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
        ProductAction $productAction,
        CategoryLinkManagementInterface $categoryManagement,
        AttributeRepositoryInterface $attributeRepository,
        AttributeManagementInterface $attributeManagement,
        GroupFactory $groupFactory,
        AttributeFactory $attributeFactory,
        AttributeOptionManagementInterface $attributeOptionManagement,
        FrontendLabelFactory $attributeOptionLabel,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $option,
        GetSourceItemsBySkuInterface $sourceItemsBySku,
        ImageProcessor $imageProcessor,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceRepositoryInterface $sourceRepository,
        Product $productService,
        Data $helperData
    ) {
        parent::__construct($request, $helperData);

        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->categoryManagement = $categoryManagement;
        $this->imageProcessor = $imageProcessor;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->productService = $productService;
        $this->attributeRepository = $attributeRepository;
        $this->productAction = $productAction;
        $this->groupFactory = $groupFactory;
        $this->attributeFactory = $attributeFactory;
        $this->attributeManagement = $attributeManagement;
        $this->sourceItemsBySku = $sourceItemsBySku;
        $this->sourceRepository = $sourceRepository;
        $this->helperData = $helperData;

        $this->defaultStockSource = $this->helperData->getGeneralConfig('stock_source');
        $this->attributeOptionLabel = $attributeOptionLabel;
        $this->option = $option;
        $this->attributeOptionManagement = $attributeOptionManagement;
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
            ->setCurrentPage($page);

        $list = $this->productRepository
            ->getList($this->searchCriteria->create());

        $products = [];

        foreach ($list->getItems() as $product) {
            $products[] = $this->productService->setProduct($product)->toArray();
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
     */
    public function saveProduct(string $productId = null)
    {
        $data = $this->request->getBodyParams();
        try {
            /** @var \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product */
            $product = $this->getNewOrExistingProduct($productId);

            $product->setName($data['name']);
            $product->setDescription($data['description']);

            if (!empty($data['categories'])) {
                $categoryIds = array_map(function ($category) {
                    return $category['category_website_id'];
                }, $data['categories']);

                $this->categoryManagement->assignProductToCategories($product->getSku(), $categoryIds);
            }

            if (!empty($data['images'])) {
                $this->updateProductImages($product, $data['images']);
            }

            if (!empty($data['characteristics'])) {
                $this->updateProductCharacteristics($product, $data['characteristics']);
            }

            $product->setData('easysales_should_send', false);
            $this->productRepository->save($product);

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
                    $this->imageProcessor->updateImage($product, $galleryImage->getFile(), [
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
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    private function updateProductCharacteristics(&$product, $characteristics)
    {
        $processed = [];
        $characteristicsData = [];
        $attributeGroupId = null;
        foreach ($characteristics as $characteristic) {
            if (in_array($characteristic['characteristic_website_id'], $processed)) continue;
            $value = $characteristic['value'];
            $attribute = $this->attributeRepository->get(
                \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE,
                $characteristic['characteristic_website_id']
            );

            // guess that all attributes belong to the custom attributes group
            if (is_null($attributeGroupId)) {
                $attributeGroupId = $attribute->getData('attribute_group_id');
            }

            if ($attribute->getFrontendInput() === "boolean") {
                $value = false;
                if (in_array(strtolower($characteristic['value']), ['yes', 'da', 'true', '1'])) {
                    $value = true;
                }
            } else if ($attribute->getFrontendInput() === "select") {
                $value = $attribute->getSource()->getOptionId($value);
            } else if ($attribute->getFrontendInput() === "multiselect") {
                $characteristicOptions = array_filter($characteristics, function ($characteristicOption) use ($characteristic) {
                    return $characteristicOption['characteristic_website_id'] === $characteristic['characteristic_website_id'];
                });

                $value = $attribute->getSource()->getOptionId($value);

                if (count($characteristicOptions) > 1) {
                    $processed[] = $characteristic['characteristic_website_id'];
                    $characteristicsValues = [];
                    foreach ($characteristicOptions as $characteristicOption) {
                        $option = $attribute->getSource()->getOptionId($characteristicOption['value']);
                        if (!$option) {
                            $option = $this->addOption($attribute, $characteristicOption['value']);
                        }

                        $characteristicsValues[] = $option;
                    }

                    $value = implode(",", $characteristicsValues);
                }
            }

            if (!$product->getData($attribute->getAttributeCode()) && $attributeGroupId) {
                $this->attributeManagement->assign(
                    \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE,
                    $product->getAttributeSetId(),
                    $attributeGroupId,
                    $attribute->getAttributeCode(),
                    null
                );

                // retrieve the product again just so we can add the attribute value to the newly assigned attribute
                $product = $this->getNewOrExistingProduct($product->getId());
            }

            $characteristicsData[$attribute->getAttributeCode()] = $value;

        }
        $this->productAction->updateAttributes([$product->getId()], $characteristicsData, 0);
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
        return $productId ? $this->productRepository->getById($productId, true) : $this->productFactory->create();
    }

    /**
     * Add new option to Attribute if it is mutliselect or select
     * Might still fail if there is another label with default value the $label and store value something else
     *
     * @param $attribute
     * @param $label
     * @return string|null
     */
    private function addOption($attribute, $label)
    {
        /** @var \Magento\Eav\Model\Entity\Attribute\OptionLabel $optionLabel */
        $optionLabel = $this->attributeOptionLabel->create();
        $optionLabel->setStoreId(0);
        $optionLabel->setLabel($label);

        $option = $this->option->create();
        $option->setLabel($label);
        $option->setStoreLabels([$optionLabel]);
        $option->setSortOrder(0);
        $option->setIsDefault(false);

        try {
            $newOptionId = $this->attributeOptionManagement->add(
                \Magento\Catalog\Model\Product::ENTITY,
                $attribute,
                $option
            );
        } catch (\Exception $exception) {
            $newOptionId = null;
        }

        return $newOptionId;
    }
}
