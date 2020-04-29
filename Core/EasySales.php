<?php

namespace EasySales\Integrari\Core;

use EasySales\Integrari\Helper\Data;
use Magento\Framework\HTTP\ClientInterface;

class EasySales
{
    const MICROSERVICE_URL = "https://magento2-microservice.easysales.ro/api";
//    const MICROSERVICE_URL = "http://microservice-magento.local/api";

    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var mixed
     */
    private $websiteToken;
    /**
     * @var array
     */
    private $routes;

    /**
     * EasySales constructor.
     * @param ClientInterface $client
     * @param Data $helperData
     */
    public function __construct(ClientInterface $client, Data $helperData)
    {
        $this->client = $client;
        $this->websiteToken = $helperData->getGeneralConfig('website_token');

        $this->routes = [
            'sendCategory' => '/v1/website/categories/save', // done
            'sendProduct' => '/v1/website/products/save', // done
            'sendOrder' => '/v1/website/orders/save',
            'sendCharacteristic' => '/v1/website/characteristics/save', // done
        ];
    }

    /**
     * @param $method
     * @param $params
     */
    public function execute($method, $params)
    {
        $params['website_token'] = $this->websiteToken;
        $this->client->post($this->route($method), $params);
    }

    /**
     * @param $method
     * @return string
     */
    private function route($method)
    {
        $route = $this->routes[$method];
        return sprintf("%s%s", self::MICROSERVICE_URL, $route);
    }
}
