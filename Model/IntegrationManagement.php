<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\IntegrationManagementInterface;
use EasySales\Integrari\Core\Auth\CheckWebsiteToken;
use EasySales\Integrari\Helper\Data;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;

class IntegrationManagement extends CheckWebsiteToken implements IntegrationManagementInterface
{

    public function __construct(
        Data $helperData,
        RequestInterface $request
    ) {
        parent::__construct($request, $helperData);
    }

    public function testConnection()
    {
        return [[
            "success" => true,
            "message" => "Integration successful",
        ]];
    }
}
