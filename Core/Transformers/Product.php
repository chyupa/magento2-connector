<?php

namespace EasySales\Integrari\Core\Transformers;

use EasySales\Integrari\Core\EasySales;
use EasySales\Integrari\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductCategoryList;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\Tax\Model\TaxCalculation;

class Product extends BaseTransformer
{
    /**
     * @var \Magento\Catalog\Model\Product
     */
    private $product;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    private $parentProduct;
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $stockRepository = null;
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
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;
    /**
     * @var TaxCalculation
     */
    private $taxCalculation;

    private $weightUnit;
    private $dimensionsUnit;
    private $lengthAttribute;
    private $widthAttribute;
    private $heightAttribute;

    private $ignoredAttributeCodes = [
        'description',
        'media_gallery',
        'image',
        'image_label',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout',
        'custom_layout_update',
        'custom_layout_update_file',
        'meta_description',
        'meta_keyword',
        'meta_title',
    ];

    /**
     * Product constructor.
     * @param GetSourceItemsBySkuInterface $stockItemRepository
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCategoryList $productCategoryList
     * @param Configurable $configurableType
     * @param Data $helperData
     * @param TaxCalculation $taxCalculation
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        ProductRepositoryInterface $productRepository,
        ProductCategoryList $productCategoryList,
        Configurable $configurableType,
        Data $helperData,
        TaxCalculation $taxCalculation,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled('Magento_Inventory') && $moduleManager->isEnabled('Magento_InventoryApi')) {
            $this->stockRepository = $objectManager->create('Magento\InventoryApi\Api\GetSourceItemsBySkuInterface');
        }
        $this->productCategoryList = $productCategoryList;
        $this->helperData = $helperData;
        $this->configurableType = $configurableType;
        $this->taxCalculation = $taxCalculation;

        $this->eanAttribute = $this->helperData->getGeneralConfig('ean_attribute');
        $this->brandAttribute = $this->helperData->getGeneralConfig('brand_attribute');
        $this->warehouseLocationAttribute = $this->helperData->getGeneralConfig('warehouse_location_attribute');
        $this->defaultStockSource = $this->helperData->getGeneralConfig('stock_source');

        $this->weightUnit = $this->helperData->getGeneralConfig('weight_unit', 'dimensions');
        $this->dimensionsUnit = $this->helperData->getGeneralConfig('dimension_unit', 'dimensions');

        $this->lengthAttribute = $this->helperData->getGeneralConfig('length_attribute', 'dimensions');
        $this->widthAttribute = $this->helperData->getGeneralConfig('width_attribute', 'dimensions');
        $this->heightAttribute = $this->helperData->getGeneralConfig('height_attribute', 'dimensions');

        $this->productRepository = $productRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @param ProductInterface $product
     * @return $this
     */
    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
        $parentIds = $this->configurableType->getParentIdsByChild($this->product->getId());
        if (count($parentIds)) {
            $parentId = $parentIds[0];
            $this->parentProduct = $this->productRepository->getById($parentId);
        }
        $warehouseLocation = $this->getData($this->warehouseLocationAttribute);
        $brand = $this->getData($this->brandAttribute);
        $ean = $this->getData($this->eanAttribute);
        $stock = $this->getStock($this->product);
        $images = $this->getImages();

        $characteristics = $this->getCharacteristics();

        $taxRate = $this->taxCalculation->getCalculatedRate($this->product->getData('tax_class_id'));
        $salePrice = round($this->product->getFinalPrice() * (1 + $taxRate / 100), EasySales::DECIMAL_PRECISION);
        $fullPrice = round($this->product->getPrice() * (1 + $taxRate / 100), EasySales::DECIMAL_PRECISION);

        $this->data = [
            "product_website_id" => $this->product->getId(),
            "sku" => $this->product->getSku(),
            "name" => $this->product->getName(),
            "sale_price" => $salePrice,
            "full_price" => $fullPrice,
            "tax_rate" => $taxRate,
            "description" => $this->product->getDescription(),
            "stock" => $stock,
            "weight" => $this->convertWeight($this->product->getWeight()),
            "length" => $this->convertDimensions($this->product->getData($this->lengthAttribute)),
            "width" => $this->convertDimensions($this->product->getData($this->widthAttribute)),
            "height" => $this->convertDimensions($this->product->getData($this->heightAttribute)),
            "url" => $this->product->getProductUrl(),
            "warehouse_location" => $warehouseLocation,
            "categories" => $this->productCategoryList->getCategoryIds($this->product->getId()),
            "images" => $images,
            "characteristics" => $characteristics,
            "brand" => $brand,
            "ean" => $ean,
            "type" => $this->parentProduct ? "complex" : "simple",
            "parent_id" => $this->parentProduct ? $this->parentProduct->getId() : null,
            "parent_url" => $this->parentProduct ? $this->parentProduct->getProductUrl() : null,
            "parent_name" => $this->parentProduct ? $this->parentProduct->getName() : null,
        ];

        return $this;
    }

    /**
     * Get characteristics from the product and from it's parent if it exists
     *
     * @return array
     */
    public function getCharacteristics()
    {
        $characteristics = $this->getProductCharacteristics($this->product);
        if ($this->parentProduct) {
            $parentCharacteristics = $this->getProductCharacteristics($this->parentProduct);
            foreach ($parentCharacteristics as $key => $parentChar) {
                $exists = false;
                foreach ($characteristics as $char) {
                    if ($parentChar['id'] === $char['id']) {
                        $exists = true;
                        break;
                    }
                }

                if ($exists) {
                    unset($parentCharacteristics[$key]);
                }

            }
            $characteristics = array_merge($parentCharacteristics, $characteristics);
        }

        return $characteristics;
    }

    /**
     * Return an array of id => value of product characteristics
     *
     * @param ProductInterface $product
     * @return array
     */
    protected function getProductCharacteristics(ProductInterface $product)
    {
        $characteristics = [];
        foreach ($product->getAttributes() as $productAttribute) {
            // ignore certain attribute code
            if (in_array($productAttribute->getData('attribute_code'), $this->ignoredAttributeCodes)) continue;

            // ignore attribute code which are not the below frontend input
            if (!in_array($productAttribute->getFrontendInput(), [
                'text',
                'select',
                'textarea',
                'multiselect',
                'boolean',
            ])) continue;

            $attributeId = $productAttribute->getData('attribute_id');
            $productValue = $product->getData($productAttribute->getData('attribute_code'));
            $hasFrontendLabel = $productAttribute->getFrontendLabel();
            if (!$attributeId || !$productValue || is_array($productValue) || !$hasFrontendLabel) {
                continue;
            }
            $value = $product->getData($productAttribute->getData('attribute_code'));
            if ($productAttribute->getFrontendInput() === "multiselect" || $productAttribute->getFrontendInput() === "select") {
                $value = $productAttribute->getSource()->getOptionText($value);
                if (is_array($value)) {
                    foreach ($value as $val) {
                        $characteristics[] = [
                            "id" => $productAttribute->getData('attribute_id'),
                            "value" => $val,
                        ];
                    }
                    continue;
                }
            }
            $characteristics[] = [
                "id" => $productAttribute->getData('attribute_id'),
                "value" => $value,
            ];
        }
        return $characteristics;
    }

    /**
     * Return an array of image urls from product and form it's parent product if it exists
     *
     * @return array
     */
    protected function getImages()
    {
        $images = $this->getProductImages($this->product);
        if (!count($images) && $this->parentProduct) {
            $parentImages = $this->getProductImages($this->parentProduct);

            $images = array_unique(array_merge($images, $parentImages));
        }

        return $images;
    }

    /**
     * Return an array of images for product
     *
     * @param ProductInterface $product
     * @return array
     */
    protected function getProductImages(ProductInterface $product)
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

        if (!$this->stockRepository) {
            return $quantity;
        }
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

    protected function getData($attributeCode)
    {
        try {
            $productModuleSettingAttribute = $this->attributeRepository
                ->get('catalog_product', $attributeCode);

            $productModuleSetting = $this->product->getData($attributeCode);

            if (!$productModuleSetting && $this->parentProduct) {
                $productModuleSetting = $this->parentProduct->getData($attributeCode);
            }

            if (!$productModuleSetting) {
                $productModuleSetting = null;
            } else {
                if ($productModuleSettingAttribute->getFrontendInput() === "select" || $productModuleSettingAttribute->getFrontendInput() === "multiselect") {
                    $productModuleSetting = $productModuleSettingAttribute->getSource()->getOptionText($productModuleSetting);
                }
            }

            $this->ignoredAttributeCodes[] = $attributeCode;

            return $productModuleSetting;

        } catch (\Exception $exception) {
            return null;
        }
    }

    private function convertWeight($weight)
    {
        $weight  = (float) $weight;
        $to_unit = 'kg';
        $from_unit = strtolower($this->weightUnit);
        if ($from_unit !== $to_unit) {
            switch (strtolower($this->weightUnit)) {
                case 'g':
                    $weight *= 0.001;
                    break;
                case 'lb':
                    $weight *= 0.453592;
                    break;
                case 'oz':
                    $weight *= 0.0283495;
                    break;
            }
            // Output desired unit.
            switch ($to_unit) {
                case 'g':
                    $weight *= 1000;
                    break;
                case 'lb':
                    $weight *= 2.20462;
                    break;
                case 'oz':
                    $weight *= 35.274;
                    break;
            }
        }

        return $weight < 0 ? 0 : $weight;
    }

    private function convertDimensions($dimension)
    {
        if (!is_numeric($dimension)) {
            return 0;
        }

        $dimension  = (float) $dimension;
        $to_unit = 'cm';
        $from_unit = strtolower($this->dimensionsUnit);
        // Unify all units to cm first.
        if ( $from_unit !== $to_unit ) {
            switch ( $from_unit ) {
                case 'in':
                    $dimension *= 2.54;
                    break;
                case 'm':
                    $dimension *= 100;
                    break;
                case 'mm':
                    $dimension *= 0.1;
                    break;
                case 'yd':
                    $dimension *= 91.44;
                    break;
            }

            // Output desired unit.
            switch ( $to_unit ) {
                case 'in':
                    $dimension *= 0.3937;
                    break;
                case 'm':
                    $dimension *= 0.01;
                    break;
                case 'mm':
                    $dimension *= 10;
                    break;
                case 'yd':
                    $dimension *= 0.010936133;
                    break;
            }
        }

        return $dimension < 0 ? 0 : $dimension;
    }
}
