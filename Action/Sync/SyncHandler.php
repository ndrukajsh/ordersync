<?php 

namespace Seizera\MageSync\Action\Sync;

use Seizera\MageSync\Api\Communication;
use \Magento\Catalog\Model\CategoryFactory;
use \Magento\Catalog\Model\ProductFactory;
use \Seizera\MageSync\Model\CategorySyncLogFactory;
use \Seizera\MageSync\Model\ProductSyncLogFactory;
use \Seizera\MageSync\Model\ResourceModel\CategorySyncLog\CollectionFactory as CatCollectionFactory;
use \Seizera\MageSync\Helper\Data;
use \Psr\Log\LoggerInterface;

use \Magento\Catalog\Api\CategoryRepositoryInterface;
use \Magento\Catalog\Api\CategoryLinkManagementInterface;


class SyncHandler extends Communication
{
	protected $_categoryModel;
	protected $_catSyncLogFactory;
    protected $_productSyncLogFactory;
	protected $_catCollectionFactory;
	protected $_logger;
	protected $_categoryRepositoryInterface;
    protected $_productModel;
    protected $_stockRegistry;
    protected $_productRepository;

	public function __construct(
		Data $data, 
		CategoryFactory $categoryModel,
        ProductFactory $productModel,
		LoggerInterface $logger,
		CategorySyncLogFactory $catSyncLogFactory,
        ProductSyncLogFactory $productSyncLogFactory,
		CatCollectionFactory $catCollectionFactory,
		CategoryRepositoryInterface $categoryRepositoryInterface,
        CategoryLinkManagementInterface $categoryLinkManagementInterface,

        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
	){
	    $this->_categoryModel = $categoryModel;
        $this->_productModel = $productModel;
	    $this->_logger = $logger;
	    $this->_catSyncLogFactory = $catSyncLogFactory;
        $this->_productSyncLogFactory = $productSyncLogFactory;
	    $this->_catCollectionFactory = $catCollectionFactory;
	    $this->_categoryRepositoryInterface = $categoryRepositoryInterface;
        $this->_categoryLinkManagementInterface = $categoryLinkManagementInterface;

        $this->_stockRegistry = $stockRegistry;
        $this->_productRepository = $productRepository;
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
                            // ->addFieldToSelect('entity_id')
                            ->addFieldToFilter('mage_client_category_id', ['eq' => $catId])
                            ->getData();
        $logId = (int) $collection[0]['entity_id'];
        // insert the exact time when the sync happens
        $catLog = $this->_catSyncLogFactory->create()->load($logId);
        $catLog->setSyncTime(date('Y-m-d H:i:s'));
        // $catLog->setMageCategoryId($category['id']);
        // $catLog->setClientMageCategoryId($newlyAddedCatId);
        $catLog->setSyncType('update');
        $catLog->save();
    }

    public function createProduct($product){
        $productObject = $this->_productModel->create();
        $this->saveProductData($productObject, $product);
        // insert the exact time when the sync happens
        $productLog = $this->_productSyncLogFactory->create();
        $productLog->setSyncTime(date('Y-m-d H:i:s'));
        $productLog->setMageProductId((int) $product['id']);
        $productLog->setClientMageProductId((int) $productObject->getId());
        $productLog->setSyncType('insert');
        $productLog->save();
    }

    public function updateClientProduct($pId, $product){
    	$productObject = $this->_productModel->create()->load($pId);
        $this->saveProductData($productObject, $product);
        // insert the exact time when the sync happens
        $productLog = $this->_productSyncLogFactory->create();
        $productLog->setSyncTime(date('Y-m-d H:i:s'));
        $productLog->setSyncType('update');
        $productLog->save();
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

    public function getCategoryId($mainCatId) {
        // here the parent id is taken from category_sync_log table
        $collection = $this->_catCollectionFactory->create()
                            ->addFieldToFilter('mage_category_id', ['eq' => $mainCatId])->getData();
        if (is_array($collection)) {
            if (count($collection)) {
                return (int) $collection[0]['mage_client_category_id'];
            }
            return 2;
        }
        return 2;
    }

    public function getProductAttribute($product, $attrCode){
        if (is_array($product['custom_attributes'])) {
            foreach ($product['custom_attributes'] as $attr) {
                if ($attr['attribute_code'] == $attrCode) {
                    return $attr['value'];
                }
            }
        }
        return '';
    }

    protected function saveProductData($productObject, $product){
        $productObject->setStoreId(0);
        $productObject->setName($product['name']);
        $productObject->setTypeId('simple');
        $productObject->setAttributeSetId(4);
        $productObject->setSku($product['sku']);
        $productObject->setWebsiteIds(array(1));
        $productObject->setVisibility(4);
        $productObject->setDescription(
            $this->getProductAttribute($product, 'description')
        );
        $productObject->setWeight($product['weight']);
        $productObject->setPrice($product['price']);

        // $this->_productRepository->save($product);
        $productObject->setQuantityAndStockStatus(
            [
                'use_config_manage_stock' => 0, //'Use config settings' checkbox
                'manage_stock' => 1, //manage stock
                'min_sale_qty' => 1, //Minimum Qty Allowed in Shopping Cart
                'max_sale_qty' => 1000, //Maximum Qty Allowed in Shopping Cart
                'is_in_stock' => 1, //Stock Availability
                'qty' => $product['qty'] //qty
            ]
        );
        $productObject->save();
        $i = 0;
        foreach ($product['media_gallery_entries'] as $image) {
            // Adding Image to product
            $imagePath = $this->getUrl() . 'pub/media/catalog/product' . $image['file']; // path of the image
            $path = $this->downloadProductImage($imagePath);
            $imgRoles = [];
            if ($i == 0) {
                $imgRoles = ['image', 'small_image', 'thumbnail'];
            }
            $productObject->addImageToMediaGallery($path, $imgRoles, false, false);
            $productObject->save();
            unlink($path);
            $i++;
        }
        // assign product to category
        $categoryIds = $this->getProductAttribute($product, 'category_ids');
        $prodCatIds = [];
        foreach ($categoryIds as $catId) {
            $prodCatIds[] = (int) $this->getCategoryId($catId); 
        }
        $this->_categoryLinkManagementInterface->assignProductToCategories(
            $product['sku'],
            $prodCatIds
        );
    }
}