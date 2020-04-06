<?php

namespace Seizera\MageSync\Model;

use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\DataObject\IdentityInterface;

class CategorySyncLog extends AbstractModel implements IdentityInterface
{
	const CACHE_TAG = 'category_sync_log';

	protected $_cacheTag = 'category_sync_log';

	protected $_eventPrefix = 'category_sync_log';

	protected function _construct(){
		$this->_init('Seizera\MageSync\Model\ResourceModel\CategorySyncLog');
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

	public function getOdooCategoryId(){ 
		return $this->getData('odoo_category_id');
	}

	public function getMageCategoryId(){ 
		return $this->getData('mage_category_id');
	}

	public function getSyncType(){ 
		return $this->getData('sync_type');
	}

	// setters

	public function setSyncTime($syncTime){
		return $this->setData('sync_time', $syncTime);
	}
	public function setOdooCategoryId($odooCategoryId){
		return $this->setData('odoo_category_id', $odooCategoryId);
	}
	public function setMageCategoryId($mageCategoryId){
		return $this->setData('mage_category_id', $mageCategoryId);
	}
	public function setSyncType($syncType){
		return $this->setData('sync_type', $syncType);
	}
}