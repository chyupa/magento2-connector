<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\AttributeManagementInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Framework\Controller\ResultFactory;

class AttributeManagement implements AttributeManagementInterface
{
    private $_request;
    private $_objectManager;
    private $attributeFactory;
    private $resultFactory;
    private $_attributeListRepo;
    private $_searchCriteria;

    /**
     * CategoryManagement constructor.
     * @param RequestInterface $request
     * @param AttributeFactory $attributeFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ObjectManagerInterface $objectManager
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        RequestInterface $request,
        AttributeFactory $attributeFactory,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ObjectManagerInterface $objectManager,
        ResultFactory $resultFactory
    )
    {
        $this->_request = $request;
        $this->_objectManager = $objectManager;
        $this->attributeFactory = $attributeFactory;
        $this->resultFactory = $resultFactory;
        $this->_attributeListRepo = $attributeRepository;
        $this->_searchCriteria = $searchCriteriaBuilder;
    }

    /**
     * GET api for attributes
     *
     * @inheritDoc
     */
    public function getAttributes()
    {
        $page = $this->_request->getQueryValue('page', 1);
        $limit = $this->_request->getQueryValue('limit', self::PER_PAGE);
        $this->_searchCriteria->setPageSize($limit)->setCurrentPage($page);
        $list = $this->_attributeListRepo->getList("catalog_product", $this->_searchCriteria->create());

        $attributes = [];
        foreach ($list->getItems() as $attribute) {
            if (!$attribute->getDefaultFrontendLabel()) {
                continue;
            }
            $attributes[] = [
                "characteristic_website_id" => $attribute->getId(),
                "name"                      => $attribute->getDefaultFrontendLabel(),
            ];
        }

        return [[
            'perPage'         => $limit,
            'pages'           => ceil($list->getTotalCount() / $limit),
            'curPage'         => $page,
            'characteristics' => $attributes,
        ]];
    }

    /**
     * @inheritDoc
     */
    public function saveAttribute($attributeId = null)
    {
        $data = $this->_request->getBodyParams();
        $model = $this->attributeFactory->create();

        try {
            if ($attributeId) {
                $model->load($attributeId);
            }
            $attributeData = $this->generateAttributeData($data['name'], 'text');
            $model->addData($attributeData);
            $model->setEntityTypeId($this->generateEntityTypeId());
            $model->setIsUserDefined(1);
            $model->save();

            $response = [
                "success"   => true,
                "attribute" => $model->getId(),
            ];

            return [$response];
        } catch (Exception $exception) {
            // does not arrive here thought :(
            return [
                "success" => false,
                "message" => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param $name
     * @param $type
     * @return array
     */
    private function generateAttributeData($name, $type)
    {
        $attributeData = [
            'frontend_label'                       =>
                array(
                    0 => $name,
                ),
            'frontend_input'                       => $type,
            'is_required'                          => '0',
            'update_product_preview_image'         => '0',
            'use_product_image_for_swatch'         => '0',
            'visual_swatch_validation'             => '',
            'visual_swatch_validation_unique'      => '',
            'text_swatch_validation'               => '',
            'text_swatch_validation_unique'        => '',
            'dropdown_attribute_validation'        => '',
            'dropdown_attribute_validation_unique' => '',
            'attribute_code'                       => $this->generateCode($name),
            'is_global'                            => '0',
            'default_value_text'                   => '',
            'default_value_yesno'                  => '0',
            'default_value_date'                   => '',
            'default_value_textarea'               => '',
            'is_unique'                            => '0',
            'frontend_class'                       => '',
            'is_used_in_grid'                      => '1',
            'is_visible_in_grid'                   => '1',
            'is_filterable_in_grid'                => '1',
            'is_searchable'                        => '0',
            'is_comparable'                        => '0',
            'is_used_for_promo_rules'              => '0',
            'is_html_allowed_on_front'             => '1',
            'is_visible_on_front'                  => '0',
            'used_in_product_listing'              => '0',
            'used_for_sort_by'                     => '0',
            'source_model'                         => NULL,
            'backend_model'                        => NULL,
            'backend_type'                         => 'varchar',
            'is_filterable'                        => 0,
            'is_filterable_in_search'              => 0,
            'default_value'                        => '',
        ];

        return $attributeData;
    }

    private function generateEntityTypeId()
    {
        return $this->_objectManager->create(
            \Magento\Eav\Model\Entity::class
        )->setType(
            \Magento\Catalog\Model\Product::ENTITY
        )->getTypeId();
    }


    /**
     * Generate code from label
     *
     * @param string $label
     * @return string
     */
    protected function generateCode($label)
    {
        $code = substr(
            preg_replace(
                '/[^a-z_0-9]/',
                '_',
                $this->_objectManager->create(\Magento\Catalog\Model\Product\Url::class)->formatUrlKey($label)
            ),
            0,
            30
        );
        $validatorAttrCode = new \Zend_Validate_Regex(['pattern' => '/^[a-z][a-z_0-9]{0,29}[a-z0-9]$/']);
        if (!$validatorAttrCode->isValid($code)) {
            $code = 'attr_' . ($code ?: substr(md5(time()), 0, 8));
        }
        return $code;
    }
}

