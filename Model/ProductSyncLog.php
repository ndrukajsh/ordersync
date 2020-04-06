<?php

namespace Seizera\MageSync\Model;

use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\DataObject\IdentityInterface;

class ProductSyncLog extends AbstractModel implements IdentityInterface
{
	const CACHE_TAG = 'product_sync_log';

	protected $_cacheTag = 'product_sync_log';

	protected $_eventPrefix = 'product_sync_log';

	protected function _construct(){
		$this->_init('Seizera\MageSync\Model\ResourceModel\ProductSyncLog');
	}

	public function getIdentities(){
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues(){
		$values = [];
		return $values;
	}

	// getters

	public function getId(){ 
		return $this->getData('entity_id');
	}

	public function getSyncTime(){ 
		return $this->getData('sync_time');
	}

	public function getMageProductId(){ 
		return $this->getData('mage_product_id');
	}

	public function getClientMageProductId(){ 
		return $this->getData('mage_client_product_id');
	}

	public function getSyncType(){ 
		return $this->getData('sync_type');
	}

	// setters

	public function setSyncTime($syncTime){
		return $this->setData('sync_time', $syncTime);
	}
	public function setMageProductId($mageProductId){
		return $this->setData('mage_product_id', $mageProductId);
	}
	public function setClientMageProductId($clientMageProductId){
		return $this->setData('mage_client_product_id', $clientMageProductId);
	}
	public function setSyncType($syncType){
		return $this->setData('sync_type', $syncType);
	}
}