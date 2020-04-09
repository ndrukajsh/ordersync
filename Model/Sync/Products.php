<?php

namespace Seizera\MageSync\Model\Sync;

use Seizera\MageSync\Api\Communication;
use Seizera\MageSync\Model\ResourceModel\ProductSyncLog\CollectionFactory;

/**
 * Class to get Products from main instance
 */
class Products
{
    protected $_baseCommunication;
    protected $_productSyncLogFactory;

    public function __construct(
    	Communication $communication, 
    	CollectionFactory $productSyncLogFactory
    ){
    	$this->_baseCommunication = $communication;
    	$this->_productSyncLogFactory = $productSyncLogFactory;
    }

	public function getProducts($sku = null){
	    // $apiUrl = $this->_baseCommunication->getUrl() . 'rest/V1/products?searchCriteria';
	    // model method called here
	    // die(var_dump('expression'));
	    $lastSync = $this->getLastSync();
	    $lastSync = $this->_baseCommunication->convertToCurrentTimezone($lastSync);
	    $lastSync = str_replace(' ', '%20', $lastSync);

	    $apiUrl = $this->_baseCommunication->getUrl() . 'rest/V1/products?searchCriteria[filter_groups][0][filters][0][field]=updated_at&searchCriteria[filter_groups][0][filters][0][value]='.$lastSync.'&searchCriteria[filter_groups][0][filters][0][condition_type]=gteq&searchCriteria[pageSize]=10';
	    if (!is_null($sku)) {
	        $apiUrl = $this->_baseCommunication->getUrl() . "rest/V1/products/$sku";
	    }
	    $total = $this->_baseCommunication->makeGetRequest($apiUrl);
	    $result = [];
	    $i = 0;

	    foreach ($total['items'] as $r) {
	        $sku = $r['sku'];
	        $stockApiUrl = $this->_baseCommunication->getUrl() . "rest/V1/stockItems/$sku";
	        $qtyData = $this->_baseCommunication->makeGetRequest($stockApiUrl);
	        $result[] = $r;
	        $result[$i]['qty'] = $qtyData['qty'];
	        $i++;
	    }
	    return $result;
	}

	protected function getLastSync(){
		$date = $this->_productSyncLogFactory
						->create()
						->addFieldToSelect('sync_time')
						->setOrder('sync_time', 'DESC')
						->setPageSize(1)
						->getData();
		if (is_array($date)) {
			if (count($date)) {
				return $date[0]['sync_time'];
			}	
		}
		// return inital date from mktime so that there is no earlier sync done at this moment
		return date('Y-m-d H:i:s', strtotime('1970-01-01 00:00:00'));
	}
}