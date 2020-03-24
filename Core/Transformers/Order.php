<?php

namespace EasySales\Integrari\Core\Transformers;

use EasySales\Integrari\Helper\Data;

class Order
{
    /**
     * @var Data
     */
    private $helperData;
    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    public function __construct(Data $helperData, \Magento\Framework\Stdlib\DateTime $dateTime)
    {
        $this->helperData = $helperData;
        $this->dateTime = $dateTime;
    }

    public function transform(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        try {
            $code = $order->getPayment()->getMethodInstance()->getCode();
        } catch (\Exception $exception) {
            $code = 'unknown';
        }

        $paymentMethods = [
            'checkmo' => 1,
            'free' => 1,
            'purchaseorder' => 1,
            'banktransfer' => 3,
            'cashondelivery' => 2,
            'authorizenet' => 1,
            'paypal_express' => 1,
            'paypal_direct' => 1,
            'unknown'=> 4,
            'zitec_dpd_cashondelivery' => 2
        ];

        $statuses = [
            'pending' => 1,
            'canceled' => 0,
            'closed' => 3,
            'complete' => 3,
            'suspected_fraud' => 4,
            'payment_review' => 2,
            'on_hold' => 4,
            'paypal_canceled_reversal' => 0,
            'paypal_reversed' => 0,
            'pending_payment' => 1,
            'pending_paypal' => 1,
            'processing' => 4
        ];

        $this->data = [
            'order_id' => $order->getIncrementId(),
            'invoice_series' => $this->helperData->getConfigValue('invoice_series', null),
            'order_date' => $this->dateTime->formatDate($order->getCreatedAt()),
            'order_total' => (float) $order->getTotalDue(),
            'status' => isset($statuses[$order->getStatus()]) ? $statuses[$order->getStatus()] : 1,
            'payment_mode' => isset($paymentMethods[$code]) ? $paymentMethods[$code] : 1,
            'shipment_tax' => (float) $order->getBaseShippingInclTax(),
            'observations' => $order->getCustomerNote(),
            'total_vouchers' => (float) $order->getBaseDiscountAmount(),
            'customer' => $this->getCustomer($order, $order->getBillingAddress()->getData()),
            'billing_address' => $this->getAddress($order->getBillingAddress()->getData()),
            'shipping_address' => $this->getAddress($order->getShippingAddress()->getData()),
            'order_products' => $this->getOrderProducts($order),
        ];

        return $this;
    }

    /**
     * @param $order
     * @param $address
     * @return array
     */
    public function getCustomer($order, $address)
    {
        $name = $order['customer_firstname'] . ' ' . $order['customer_lastname'];

        if (!$order['customer_firstname'] || !$order['customer_lastname']) {
            $name = $address['firstname'] . ' ' . $address['lastname'];
        }

        return [
            'name' => $name,
            'company_name' => $address['company'],
            'phone' => $address['telephone'],
            'email' => $order['customer_email'],
            'fax' => $address['fax'],
            'identification_number' => null,
            'legal_entity' => $address['company'] ? 1 : 0,
            'bank' => NULL,
            'iban' => NULL,
            'vat_id' => $address['vat_id'],
            'registration_number' => NULL,
            'vat_payer' => NULL
        ];
    }


    /**
     * @param $address
     * @return array
     */
    public function getAddress($address)
    {
        return [
            'name' => $address['firstname'] . ' ' . $address['lastname'],
            'phone' => $address['telephone'],
            'country' => $address['country_id'],
            'county' => $this->helperData->replaceSpecialChars($address['region']),
            'city' => $address['city'],
            'street' => $address['street'],
            'postal_code' => $address['postcode']
        ];
    }

    /**
     * @param $order
     * @return array
     */
    public function getOrderProducts($order)
    {
        $products = [];

        $productsData = $order->getAllVisibleItems();


        foreach ($productsData as $product) {
            if ($product->getParentItem()) {
                continue;
            }

            $id = null;
            $name = null;

            if (count($product->getChildrenItems())) {
                $id = $product->getChildrenItems()[0]->getProductId();
                $name = $product->getChildrenItems()[0]->getName();
            }

            $products[] = [
                'product_website_id' => $id ? $id : $product->getProductId(),
                'sku' => $product->getSku(),
                'name' => $name ? $name : $product->getName(),
                'quantity' => (float) $product->getQtyOrdered(),
                'price' => (float) $product->getPrice(),
                'total' => (float) $product->getPrice() * $product->getQtyOrdered(),
                'tax' => (float) $product->getTaxPercent()
            ];
        }

        return $products;
    }

    public function toArray()
    {
        return $this->data;
    }
}
