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
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @throws Exception
     */
    public function __construct(
        Data $helperData,
        RequestInterface $request,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($request, $helperData);
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
}
