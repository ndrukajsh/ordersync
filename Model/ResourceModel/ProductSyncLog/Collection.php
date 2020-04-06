<?php
namespace Seizera\MageSync\Model\ResourceModel\ProductSyncLog;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
	protected $_idFieldName = 'entity_id';
	protected $_eventPrefix = 'product_sync_log_collection';
	protected $_eventObject = 'product_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct(){
		$this->_init(
			'Seizera\MageSync\Model\ProductSyncLog', 
			'Seizera\MageSync\Model\ResourceModel\ProductSyncLog'
		);
	}

}