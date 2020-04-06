<?php

namespace Seizera\MageSync\Cron;

use \Psr\Log\LoggerInterface;
use \Seizera\MageSync\Model\ResourceModel\CategorySyncLog\CollectionFactory as CatCollectionFactory;
use \Seizera\MageSync\Model\ResourceModel\ProductSyncLog\CollectionFactory as ProductsCollectionFactory;
use \Seizera\MageSync\Action\Sync\SyncHandler;
use \Seizera\MageSync\Model\Sync\Categories as MainCategories;
use \Seizera\MageSync\Model\Sync\Products as MainProducts;
use \Seizera\MageSync\Helper\Data;

class Sync {

	protected $_logger;
	protected $_mainCategories;
	protected $_syncHandler;
	protected $_collectionFactory;
	protected $_mainProducts;
	protected $_productsCollectionFactory;
	protected $_dataHelper;

	public function __construct(
		LoggerInterface $logger,
		SyncHandler $syncHandler,
		MainCategories $mainCategories,
		MainProducts $mainProducts,
		ProductsCollectionFactory $productsCollectionFactory,
		CatCollectionFactory $catCollectionFactory,
		Data $dataHelper
	) {
		$this->_logger = $logger;
		$this->_dataHelper = $dataHelper;
		$this->_syncHandler = $syncHandler;
		$this->_mainCategories = $mainCategories;
		$this->_mainProducts = $mainProducts;
		$this->_productsCollectionFactory = $productsCollectionFactory;
		$this->_catCollectionFactory = $catCollectionFactory;
	}

	public function execute() {
		$isEnabled = (bool) $this->_dataHelper->getCronConfig('is_sync_active');
		if ($isEnabled) {
			$this->_logger->info('================== Init Sync ================== ');
			// categories from main instance
			$categories = $this->_mainCategories->getCategories();
			// saved category Id from last sync
			$collection = $this->_catCollectionFactory
			                    ->create()
			                    ->addFieldToSelect('mage_category_id')
			                    ->addFieldToSelect('mage_client_category_id')
			                    ->getData();
			$collection = $this->getIdArray($collection, 'category');

			foreach ($categories as $category) {
			    if (in_array($category['id'], $collection)) {
			        $catId = array_search($category['id'], $collection);
			        // update category
			        $this->_syncHandler->updateClientCat($catId, $category);
			    }else{
			        // create category
			        $this->_syncHandler->createCategories($category);
			    }
			}
			// Products
			// products from main instance
			$products = $this->_mainProducts->getProducts();
			// saved Product Id from last sync
			$collection = $this->_productsCollectionFactory
			                    ->create()
			                    ->addFieldToSelect('mage_product_id')
			                    ->addFieldToSelect('mage_client_product_id')
			                    ->getData();
			$collection = $this->getIdArray($collection, 'product');

			foreach ($products as $product) {
			    if (in_array($product['id'], $collection)) {
			        $pId = array_search($product['id'], $collection);
			        // update product
			        $this->_syncHandler->updateClientProduct($pId, $product);
			    }else{
			        // create product
			        $this->_syncHandler->createProduct($product);
			    }
			}
			$this->_logger->info('================== End Sync ================== ');
			
		}
	}

    protected function getIdArray($data, $type){
        $ids = [];
        $mainIndex = 'mage_' . $type . '_id';
        $clientIndex = 'mage_client_' . $type .'_id';
        foreach ($data as $d) {
            $key = (int) $d[$clientIndex];
            $ids[$key] = (int) $d[$mainIndex];
        }
        return $ids;
    }
}