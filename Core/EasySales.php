<?php

namespace EasySales\Integrari\Core;

use EasySales\Integrari\Helper\Data;
use Magento\Framework\HTTP\ClientInterface;

class EasySales
{
    public function __construct(ClientInterface $client, Data $helperData)
    {
        $this->client = $client;
        $this->websiteToken = $helperData->getGeneralConfig('website_token');

        $this->routes = [
            'sendCategory' => 'test',
            'sendProduct' => 'test',
            'sendOrder' => 'test',
            'sendCharacteristic' => 'test',
        ];
    }

    public function execute($method, $params)
    {
        $params['website_token'] = $this->websiteToken;
        $this->client->post($this->route($method), $params);
    }

    private function route($method)
    {
        $route = $this->routes[$method];
        return sprintf("%s/%s", "http://dealwise.local/api", $route);
    }
}
