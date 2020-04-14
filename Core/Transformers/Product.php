<?php

namespace EasySales\Integrari\Core\Transformers;

use EasySales\Integrari\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductCategoryList;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class Product
{
    /**
     * @var \Magento\Catalog\Model\Product
     */
    private $product;
    /**
     * @var StockItemRepository
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
    public $data;

    public function __construct(
        StockItemRepository $stockItemRepository,
        ProductCategoryList $productCategoryList,
        Data $helperData
    ) {
        $this->stockRepository = $stockItemRepository;
        $this->productCategoryList = $productCategoryList;

        $this->helperData = $helperData;

        $this->eanAttribute = $this->helperData->getGeneralConfig('ean_attribute');
        $this->brandAttribute = $this->helperData->getGeneralConfig('brand_attribute');
        $this->warehouseLocationAttribute = $this->helperData->getGeneralConfig('warehouse_location_attribute');
    }

    public function setProduct(\Magento\Catalog\Api\Data\ProductInterface $product)
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

    public function toArray()
    {
        return $this->data;
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
        try {
            $stock = $this->stockRepository->get($product->getId());
            $stock = $stock->getQty();
        } catch (NoSuchEntityException $exception) {
            $stock = 0;
        }

        return $stock;
    }
}
