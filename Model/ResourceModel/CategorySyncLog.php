<?php

namespace Seizera\MageSync\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Magento\Framework\Model\ResourceModel\Db\Context;


class CategorySyncLog extends AbstractDb
{
	
	public function __construct(Context $context){
		parent::__construct($context);
	}
	
	protected function _construct(){
		$this->_init('category_sync_log', 'entity_id');
	}
	
}