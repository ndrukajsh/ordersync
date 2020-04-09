<?php 

namespace Seizera\MageSync\Action\Sync;

use Seizera\MageSync\Api\Communication;
use \Magento\Catalog\Model\ProductFactory;
use \Seizera\MageSync\Model\ProductSyncLogFactory;
use \Seizera\MageSync\Model\ResourceModel\CategorySyncLog\CollectionFactory as CatCollectionFactory;
use \Seizera\MageSync\Helper\Data;
use \Magento\Catalog\Api\CategoryLinkManagementInterface;

class Product extends Communication
{
    protected $_productModel;
    protected $_productSyncLogFactory;
	protected $_catCollectionFactory;
	protected $_categoryRepositoryInterface;

    protected $_stockRegistry;
    protected $_productRepository;

	public function __construct(
		Data $data,
        ProductFactory $productModel,
        ProductSyncLogFactory $productSyncLogFactory,
		CatCollectionFactory $catCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagementInterface
	){
        $this->_productModel = $productModel;
        $this->_productSyncLogFactory = $productSyncLogFactory;
	    $this->_catCollectionFactory = $catCollectionFactory;
        $this->_categoryLinkManagementInterface = $categoryLinkManagementInterface;
	    parent::__construct($data);
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
        if (is_array($product['media_gallery_entries']) && count($product['media_gallery_entries'])) {
            $i = 0;
            foreach ($product['media_gallery_entries'] as $image) {
                // Adding Image to product
                $imagePath = $this->getUrl() . 'pub/media/catalog/product' . $image['file']; // path of the image
                // check if image exists in server
                if ($this->checkRemoteFileExists($imagePath)) {
                    $path = $this->downloadProductImage($imagePath);
                    $imgRoles = [];
                    if ($i == 0) {
                        $imgRoles = ['image', 'small_image', 'thumbnail'];
                    }
                    $productObject->addImageToMediaGallery($path, $imgRoles, false, false);
                    $productObject->save();
                    unlink($path);
                }
                $i++;
            }
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