<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\AttributeManagementInterface;
use EasySales\Integrari\Core\Auth\CheckWebsiteToken;
use EasySales\Integrari\Helper\Data;
use Exception;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;

class AttributeManagement extends CheckWebsiteToken implements AttributeManagementInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeListRepo;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteria;

    /**
     * CategoryManagement constructor.
     *
     * @param Data $helperData
     * @param RequestInterface $request
     * @param AttributeFactory $attributeFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ObjectManagerInterface $objectManager
     * @throws Exception
     */
    public function __construct(
        Data $helperData,
        RequestInterface $request,
        AttributeFactory $attributeFactory,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($request, $helperData);
        $this->objectManager = $objectManager;
        $this->attributeFactory = $attributeFactory;
        $this->attributeListRepo = $attributeRepository;
        $this->searchCriteria = $searchCriteriaBuilder;
    }

    /**
     * GET api for attributes
     *
     * @inheritDoc
     */
    public function getAttributes()
    {
        $page = $this->request->getQueryValue('page', 1);
        $limit = $this->request->getQueryValue('limit', self::PER_PAGE);
        $this->searchCriteria->setPageSize($limit)->setCurrentPage($page);
        $searchCriteria = $this->searchCriteria
            ->addFilter('frontend_label', null, 'notnull')
            ->addFilter('frontend_input', [
                'text',
                'select',
                'textarea',
                'multiselect',
                'boolean',
            ], 'in')
            ->create();
        $list = $this->attributeListRepo->getList("catalog_product", $searchCriteria);

        $attributes = [];
        foreach ($list->getItems() as $attribute) {
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
    public function saveAttribute(string $attributeId = null)
    {
        $data = $this->request->getBodyParams();

        try {
            $model = $this->getNewOrExistingAttribute($attributeId);

            $attributeData = $this->generateAttributeData($data['name'], 'text');
            $model->addData($attributeData);
            $model->setEntityTypeId($this->generateEntityTypeId());
            $model->setIsUserDefined(1);
            $model->setData('easysales_should_send', false);

            $this->attributeListRepo->save($model);

            $response = [
                "success"   => true,
                "attribute" => $model->getId(),
            ];

            return [$response];
        } catch (Exception $exception) {
            // does not arrive here though :(
            return [[
                "success" => false,
                "message" => $exception->getMessage(),
            ]];
        }
    }

    /**
     * @param $name
     * @param $type
     * @return array
     * @throws \Zend_Validate_Exception
     */
    private function generateAttributeData($name, $type)
    {
        $attributeData = [
            'frontend_label'                       => [
                0 => $name,
            ],
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
            'source_model'                         => null,
            'backend_model'                        => null,
            'backend_type'                         => 'varchar',
            'is_filterable'                        => 0,
            'is_filterable_in_search'              => 0,
            'default_value'                        => '',
        ];

        return $attributeData;
    }

    /**
     * @return mixed
     */
    private function generateEntityTypeId()
    {
        return $this->objectManager->create(
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
     * @throws \Zend_Validate_Exception
     */
    private function generateCode($label)
    {
        $code = substr(
            preg_replace(
                '/[^a-z_0-9]/',
                '_',
                $this->objectManager->create(\Magento\Catalog\Model\Product\Url::class)->formatUrlKey($label)
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

    /**
     * @param null $attributeId
     * @return \Magento\Eav\Api\Data\AttributeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getNewOrExistingAttribute($attributeId = null)
    {
        return $attributeId ? $this->attributeListRepo->get("catalog_product", $attributeId) : $this->attributeFactory->create();
    }
}
