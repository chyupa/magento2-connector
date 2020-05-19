<?php

namespace EasySales\Integrari\Core\Transformers;

use EasySales\Integrari\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductCategoryList;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;

class Product extends BaseTransformer
{
    /**
     * @var \Magento\Catalog\Model\Product
     */
    private $product;
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $stockRepository;
    /**
     * @var ProductCategoryList
     */
    private $productCategoryList;
    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var mixed
     */
    private $eanAttribute;

    /**
     * @var mixed
     */
    private $brandAttribute;

    /**
     * @var mixed
     */
    private $warehouseLocationAttribute;

    /**
     * @var mixed
     */
    private $defaultStockSource;

    private $configurableType;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Product constructor.
     * @param GetSourceItemsBySkuInterface $stockItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCategoryList $productCategoryList
     * @param Configurable $configurableType
     * @param Data $helperData
     */
    public function __construct(
        GetSourceItemsBySkuInterface $stockItemRepository,
        ProductRepositoryInterface $productRepository,
        ProductCategoryList $productCategoryList,
        Configurable $configurableType,
        Data $helperData
    ) {
        $this->stockRepository = $stockItemRepository;
        $this->productCategoryList = $productCategoryList;
        $this->helperData = $helperData;
        $this->configurableType = $configurableType;

        $this->eanAttribute = $this->helperData->getGeneralConfig('ean_attribute');
        $this->brandAttribute = $this->helperData->getGeneralConfig('brand_attribute');
        $this->warehouseLocationAttribute = $this->helperData->getGeneralConfig('warehouse_location_attribute');
        $this->defaultStockSource = $this->helperData->getGeneralConfig('stock_source');
        $this->productRepository = $productRepository;
    }

    /**
     * @param ProductInterface $product
     * @return $this
     */
    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
        $characteristics = $this->getCharacteristics($this->product);

        $stock = $this->getStock($this->product);

        $images = $this->getImages($this->product);

        $parentProduct = null;
        $parentIds = $this->configurableType->getParentIdsByChild($product->getId());
        if (count($parentIds)) {
            $parentId = $parentIds[0];
            $parentProduct = $this->productRepository->getById($parentId);
        }

        $this->data = [
            "product_website_id" => $this->product->getId(),
            "sku" => $this->product->getSku(),
            "name" => $this->product->getName(),
            "sale_price" => $this->product->getFinalPrice(),
            "full_price" => $this->product->getPrice(),
            "description" => $this->product->getDescription() ?? "&nbsp;",
            "stock" => $stock,
            "weight" => $this->product->getWeight(),
            "url" => $this->product->getProductUrl(),
            "warehouse_location" => $this->warehouseLocationAttribute ? $this->product->getData($this->warehouseLocationAttribute) : null,
            "categories" => $this->productCategoryList->getCategoryIds($this->product->getId()),
            "images" => $images,
            "characteristics" => $characteristics,
            "brand" => $this->brandAttribute ? $this->product->getData($this->brandAttribute) : null,
            "ean" => $this->eanAttribute ? $this->product->getData($this->eanAttribute) : null,
            "type" => $parentProduct ? "complex" : "simple",
            "parent_id" => $parentProduct ? $parentProduct->getId() : null,
            "parent_url" => $parentProduct ? $parentProduct->getProductUrl() : null,
            "parent_name" => $parentProduct ? $parentProduct->getName() : null,
        ];

        return $this;
    }

    /**
     * Return an array of id => value of product characteristics
     *
     * @param ProductInterface $product
     * @return array
     */
    protected function getCharacteristics(ProductInterface $product)
    {
        $characteristics = [];
        foreach ($product->getAttributes() as $productAttribute) {
            $attributeId = $productAttribute->getData('attribute_id');
            $productValue = $product->getData($productAttribute->getData('attribute_code'));
            $isUserDefined = $productAttribute->getIsUserDefined();
            $hasFrontendLabel = $productAttribute->getFrontendLabel();
            if (!$attributeId || !$productValue || is_array($productValue) || !$isUserDefined || !$hasFrontendLabel) {
                continue;
            }
            $value = $product->getData($productAttribute->getData('attribute_code'));
            if ($productAttribute->getFrontendInput() === "multiselect" || $productAttribute->getFrontendInput() === "select") {
                $value = $productAttribute->getSource()->getOptionText($value);
            }
            $characteristics[] = [
                "id" => $productAttribute->getData('attribute_id'),
                "value" => $value,
            ];
        }
        return $characteristics;
    }

    /**
     * Return an array of image urls from product
     *
     * @param ProductInterface $product
     * @return array
     */
    protected function getImages(ProductInterface $product)
    {
        $images = [];
        foreach ($product->getMediaGalleryImages() as $attributeMediaGalleryEntry) {
            $images[] = $attributeMediaGalleryEntry->getData('url');
        }

        return $images;
    }

    /**
     * Return stock quantity for product
     *
     * @param ProductInterface $product
     * @return float|int|\Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    protected function getStock(ProductInterface $product)
    {
        $quantity = 0;
        $stocks = $this->stockRepository->execute($product->getSku());
        $stockSourceItem = null;
        foreach ($stocks as $stock) {
            if ($stock->getSourceCode() === $this->defaultStockSource && $stock->getStatus()) {
                $quantity = $stock->getQuantity();
                break;
            }
        }
        return $quantity;
    }
}
