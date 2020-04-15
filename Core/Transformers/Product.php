<?php

namespace EasySales\Integrari\Core\Transformers;

use EasySales\Integrari\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductCategoryList;
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
     * @var array
     */
    private $data;

    /**
     * @var mixed
     */
    private $defaultStockSource;

    /**
     * Product constructor.
     * @param GetSourceItemsBySkuInterface $stockItemRepository
     * @param ProductCategoryList $productCategoryList
     * @param Data $helperData
     */
    public function __construct(
        GetSourceItemsBySkuInterface $stockItemRepository,
        ProductCategoryList $productCategoryList,
        Data $helperData
    ) {
        $this->stockRepository = $stockItemRepository;
        $this->productCategoryList = $productCategoryList;
        $this->helperData = $helperData;

        $this->eanAttribute = $this->helperData->getGeneralConfig('ean_attribute');
        $this->brandAttribute = $this->helperData->getGeneralConfig('brand_attribute');
        $this->warehouseLocationAttribute = $this->helperData->getGeneralConfig('warehouse_location_attribute');
        $this->defaultStockSource = $this->helperData->getGeneralConfig('stock_source');
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

        $this->data = [
            "product_website_id" => $this->product->getId(),
            "sku" => $this->product->getSku(),
            "name" => $this->product->getName(),
            "sale_price" => $this->product->getFinalPrice(),
            "full_price" => $this->product->getPrice(),
            "description" => $this->product->getDescription(),
            "stock" => $stock,
            "weight" => $this->product->getWeight(),
            "type" => $this->product->getTypeId() === "simple" ? "simple" : "complex",
            "url" => $this->product->getProductUrl(),
            "warehouse_location" => $this->warehouseLocationAttribute ? $this->product->getData($this->warehouseLocationAttribute) : null,
            "categories" => $this->productCategoryList->getCategoryIds($this->product->getId()),
            "images" => $images,
            "characteristics" => $characteristics,
            "brand" => $this->brandAttribute ? $this->product->getData($this->brandAttribute) : null,
            "ean" => $this->eanAttribute ? $this->product->getData($this->eanAttribute) : null,
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
            $characteristics[] = [
                "id" => $productAttribute->getData('attribute_id'),
                "value" => $product->getData($productAttribute->getData('attribute_code')),
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
