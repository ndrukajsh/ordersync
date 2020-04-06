<?php

namespace Seizera\MageSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use \Seizera\MageSync\Helper\Data;
use \Seizera\MageSync\Helper\CustomerHelper;
use \Seizera\MageSync\Api\Communication;
use \Seizera\MageSync\Model\ResourceModel\ProductSyncLog\CollectionFactory as ProductSyncLogFactory;

class SyncHelper extends AbstractHelper
{
	protected $_client;
	protected $_token;
	protected $_helperData;
	protected $_customerHelper;
	protected $_communication;
	protected $_pLogCollectionFactory;

	public function __construct(
		Data $helper, 
		CustomerHelper $customerHelper,
		Communication $communication,
		ProductSyncLogFactory $pLogCollectionFactory
	){
		$this->_helperData = $helper;
		$this->_pLogCollectionFactory = $pLogCollectionFactory;
		$this->_communication = $communication;
		$this->_customerHelper = $customerHelper;
	}

	public function createMainOrder($order){
    	$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/custom.log');
    	$logger = new \Zend\Log\Logger();
    	$logger->addWriter($writer);
    	$record = $order->getData();
	    // create magento 2 order
	    $apiUrl = $this->_helperData->getGeneralConfig('main_url') . 'rest/V1/orders/create';

	    // find the id of the web owner in order to be able to push it to magento
	    $mageCustomer = $this->_customerHelper->getMainInstanceCurrentCustomerId();

	    $orderData = [
	        "base_currency_code"      => $record["base_currency_code"],
	        "base_discount_amount"    => $record["base_discount_amount"],
	        "base_grand_total"        => $record["base_grand_total"],
	        "base_shipping_amount"    => $record["base_shipping_amount"],
	        "base_subtotal"           => $record["base_subtotal"],
	        "base_tax_amount"         => $record["base_tax_amount"],
	        "customer_email"          => $mageCustomer['email'],
	        "customer_firstname"      => $mageCustomer["firstname"],
	        "customer_lastname"       => $mageCustomer["lastname"],
	        // "customer_group_id"       => 4,
	        "customer_id"             => $mageCustomer['id'],
	        "customer_is_guest"       => 0,
	        // "customer_note_notify"    => $record["customer_note_notify"],
	        "discount_amount"         => $record["discount_amount"],
	        "grand_total"             => $record["grand_total"],
	        "is_virtual"              => $record["is_virtual"],
	        "order_currency_code"     => $record["order_currency_code"],
	        "shipping_amount"         => $record["shipping_amount"],
	        "shipping_description"    => $record["shipping_description"],
	        "state"                   => $record["state"],
	        "status"                  => $record["status"],
	        "store_currency_code"     => $record["store_currency_code"],
	        "store_id"                => $record["store_id"],
	        // "store_name"              => $record["store_name"],
	        "subtotal"                => $record["subtotal"],
	        "subtotal_incl_tax"       => $record["subtotal_incl_tax"],
	        "tax_amount"              => $record["tax_amount"],
	        "total_due"               => $record["total_due"],
	        // "total_item_count"        => $record["total_item_count"],
	        "total_qty_ordered"       => $record["total_qty_ordered"],
	        // "updated_at"              => $record["updated_at"],
	        "weight"                  => $record["weight"],
	    ];

	    $items = $this->addItemsArray($order->getAllItems());
	    $orderData['items'] = $items;

	    $billingAddress = $this->getDefaultAddress($order->getBillingAddress(), $mageCustomer['id']);
	    $billingAddress['address_type'] = 'billing';
	    $orderData['billing_address'] = $billingAddress;

	    $orderData['payment']['method'] = 'cashondelivery';

	    $shippingAddress = $this->getDefaultAddress($order->getShippingAddress(), $mageCustomer['id']);
	    $shippingAddress['address_type'] = 'shipping';
	    $shippingArray['shipping']['address'] = $shippingAddress;
	    $shippingArray['shipping']['method'] = 'flatrate_flatrate';
	    $orderData['extension_attributes']['shipping_assignments'][] = $shippingArray;
	    $newOrderJson = json_encode(['entity' => $orderData]);

	    $ch = curl_init($apiUrl);
	    $curlOptions = array(
	        CURLOPT_CUSTOMREQUEST  => "PUT",
	        CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_POSTFIELDS => $newOrderJson,
	        CURLOPT_HTTPHEADER => array(
	            "Content-type: application/json", 
	            "Authorization: bearer " . $this->_communication->getMageAdminToken()
	        )
	    );
	     
	    curl_setopt_array($ch, $curlOptions);
	    $response = curl_exec($ch);
	    // $decodedResponse = (array) json_decode($response);
	    $logger->info($response);
	}

	protected function addItemsArray($items){
	    $dataItems = [];

	    foreach ($items as $item) {
	    	// initialize with 0 for scope purpose
	    	$mageProductId = 0;
	    	$productId = (int) $item->getProductId();
		    // get the main instance product Id from product_sync_log table
		    $collection = $this->_pLogCollectionFactory->create()
		    					->addFieldToSelect('*')
		                        ->addFieldToFilter('mage_client_product_id', ['eq' => $productId])->getData();
		    if (is_array($collection)) {
		        if (count($collection)) {
		            $mageProductId = $collection[0]['mage_product_id'];
		        }
		    }

	    	$dataItems[] = [
	    	    "base_discount_amount"    => $item->getBaseDiscountAmount(),
	    	    "base_original_price"     => $item->getBaseOriginalPrice(),
	    	    "base_price"              => $item->getBasePrice(),
	    	    "base_price_incl_tax"     => $item->getBasePriceInclTax(),
	    	    // "base_row_invoiced"       => $item->getBaseRowInvoiced(),
	    	    "base_row_total"          => $item->getBaseRowTotal(),
	    	    "base_tax_amount"         => $item->getBaseTaxAmount(),
	    	    // "base_tax_invoiced"       => $item->getBaseTaxInvoiced(),
	    	    "discount_amount"         => $item->getDiscountAmount(),
	    	    "discount_percent"        => $item->getDiscountPercent(),
	    	    "free_shipping"           => 0,
	    	    "is_virtual"              => 0,
	    	    "name"                    => $item->getName(),
	    	    "original_price"          => $item->getOriginalPrice(),
	    	    "price"                   => $item->getPrice(),
	    	    "price_incl_tax"          => $item->getPriceInclTax(),
	    	    "product_id"              => (int) $mageProductId,
	    	    "product_type"            => $item->getProductType(),
	    	    "qty_ordered"             => $item->getQtyOrdered(),
	    	    "row_total"               => $item->getRowTotal(),
	    	    "row_total_incl_tax"      => $item->getRowTotalInclTax(),
	    	    "sku"                     => $item->getSku(),
	    	];
	    }

	    return $dataItems;
	}

	protected function getDefaultAddress($addressObj, $customerId){
		$addressInfo = $addressObj->toArray();
	    $defaultAddress = [];
	    $defaultAddress["customer_id"]  =  $customerId;
	    $defaultAddress["region_id"]    =  $addressInfo['region_id'];
	    $defaultAddress["country_id"]   =  $addressInfo['country_id'];
        $defaultAddress["street"] 		=  [$addressInfo['street']];
	    $defaultAddress["company"]      =  $addressInfo['company'];
	    $defaultAddress["email"]      	=  'testAA@gmail.com';
	    $defaultAddress["telephone"]    =  $addressInfo['telephone'];
	    $defaultAddress["postcode"]     =  $addressInfo['postcode'];
	    $defaultAddress["city"]         =  $addressInfo['city'];
	    $defaultAddress["firstname"]    =  $addressInfo['firstname'];
	    $defaultAddress["lastname"]     =  $addressInfo['lastname'];
	    return $defaultAddress;
	}
}