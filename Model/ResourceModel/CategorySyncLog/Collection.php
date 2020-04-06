<?php
namespace Seizera\MageSync\Model\ResourceModel\CategorySyncLog;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
	protected $_idFieldName = 'entity_id';
	protected $_eventPrefix = 'category_sync_log_collection';
	protected $_eventObject = 'category_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct(){
		$this->_init(
			'Seizera\MageSync\Model\CategorySyncLog', 
			'Seizera\MageSync\Model\ResourceModel\CategorySyncLog'
		);
	}

}