<?php

namespace EasySales\Integrari\Model;

use EasySales\Integrari\Api\IntegrationManagementInterface;
use EasySales\Integrari\Core\Auth\CheckWebsiteToken;
use EasySales\Integrari\Core\EasySales;
use EasySales\Integrari\Helper\Data;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;

class IntegrationManagement extends CheckWebsiteToken implements IntegrationManagementInterface
{
    protected $moduleManager;

    public function __construct(
        Data $helperData,
        RequestInterface $request,
        ModuleList $manager
    ) {
        $this->moduleManager = $manager;
        parent::__construct($request, $helperData);
    }

    public function testConnection()
    {
        return [[
            "success" => true,
            "message" => "Integration successful",
            "version" => $this->getModuleVersion(),
        ]];
    }

    protected function getModuleVersion()
    {
        $moduleData = $this->moduleManager->getOne(EasySales::MODULE_NAME);
        if (isset($moduleData['setup_version'])) {
            return $moduleData['setup_version'];
        }

        return '0.9.0'; // default in case we can't get the one from module.xml
    }
}
