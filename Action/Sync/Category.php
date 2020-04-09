<?php 

namespace Seizera\MageSync\Action\Sync;

use Seizera\MageSync\Api\Communication;
use \Magento\Catalog\Model\CategoryFactory;
use \Seizera\MageSync\Model\CategorySyncLogFactory;
use \Seizera\MageSync\Model\ResourceModel\CategorySyncLog\CollectionFactory as CatCollectionFactory;
use \Magento\Catalog\Api\CategoryRepositoryInterface;
use \Seizera\MageSync\Helper\Data;

class Category extends Communication
{
	protected $_categoryModel;
	protected $_catSyncLogFactory;
	protected $_catCollectionFactory;
	protected $_categoryRepositoryInterface;


	public function __construct(
		Data $data, 
		CategoryFactory $categoryModel,
		CategorySyncLogFactory $catSyncLogFactory,
		CatCollectionFactory $catCollectionFactory,
		CategoryRepositoryInterface $categoryRepositoryInterface
	){
	    $this->_categoryModel = $categoryModel;
	    $this->_catSyncLogFactory = $catSyncLogFactory;
	    $this->_catCollectionFactory = $catCollectionFactory;
	    $this->_categoryRepositoryInterface = $categoryRepositoryInterface;
	    parent::__construct($data);
	}
    public function createCategories($category){
		$parentId = $this->getParentCategoryId($category['parent_id']);
		$categoryObject = $this->_categoryModel->create();
		$categoryObject->setName($category['name']);
		$categoryObject->setIsActive($category['is_active']);
		$categoryObject->setParentId($parentId);
		$categoryObject->setPosition($category['position']);
		$categoryObject->setLevel($category['level']);
		$categoryObject->setIncludeInMenu(1);
		$this->_categoryRepositoryInterface->save($categoryObject);
		$newlyAddedCatId = (int) $categoryObject->getId();

		// insert the exact time when the sync happens
		$catLog = $this->_catSyncLogFactory->create();
		$catLog->setSyncTime(date('Y-m-d H:i:s'));
		$catLog->setMageCategoryId($category['id']);
		$catLog->setClientMageCategoryId($newlyAddedCatId);
		$catLog->setSyncType('insert');
		$catLog->save();
    }

    public function updateClientCat($catId, $category){
    	$parentId = $this->getParentCategoryId($category['parent_id']);
        $categoryObject = $this->_categoryModel->create()->load($catId);
        $categoryObject->setName($category['name']);
        $categoryObject->setIsActive($category['is_active']);
        $categoryObject->setParentId($parentId);
        $categoryObject->setPosition($category['position']);
        $categoryObject->setLevel($category['level']);
        $categoryObject->setIncludeInMenu(1);
        $categoryObject->setStoreId(0);
        $categoryObject->save();

        $collection = $this->_catSyncLogFactory
                            ->create()
                            ->getCollection()
                            ->addFieldToFilter('mage_client_category_id', ['eq' => $catId])
                            ->getData();
        $logId = (int) $collection[0]['entity_id'];
        // insert the exact time when the sync happens
        $catLog = $this->_catSyncLogFactory->create()->load($logId);
        $catLog->setSyncTime(date('Y-m-d H:i:s'));
        $catLog->setSyncType('update');
        $catLog->save();
    }

    public function getParentCategoryId($mainParentId){
    	if ($mainParentId > 2) {
    		// here the parent id is taken from category_sync_log table
    	    $collection = $this->_catCollectionFactory->create()
    							->addFieldToFilter('mage_category_id', ['eq' => $mainParentId])->getData();
    		return (int) $collection[0]['mage_client_category_id'];
    	}
    	// return default category id
    	return 2;
    }
}