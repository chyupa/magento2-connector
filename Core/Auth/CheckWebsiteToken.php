<?php

namespace EasySales\Integrari\Core\Auth;

use EasySales\Integrari\Helper\Data;
use Exception;
use Magento\Framework\Webapi\Rest\Request as RequestInterface;

class CheckWebsiteToken
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * CheckWebsiteToken constructor.
     * @param RequestInterface $request
     * @param Data $helperData
     * @throws Exception
     */
    public function __construct(RequestInterface $request, Data $helperData)
    {
        $this->request = $request;
        $websiteToken = $helperData->getGeneralConfig('website_token');
        $requestHeader = $this->request->getHeader('es-token', false);

        if (!$requestHeader) {
            throw new Exception("Invalid header received");
        } elseif ($requestHeader !== $websiteToken) {
            throw new Exception("Website Token mismatch");
        }
    }
}
