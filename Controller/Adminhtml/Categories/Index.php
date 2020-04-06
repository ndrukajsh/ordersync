<?php
namespace Seizera\MageSync\Controller\Adminhtml\Categories;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use \Seizera\MageSync\Model\ResourceModel\CategorySyncLog\CollectionFactory as CatCollectionFactory;
use \Seizera\MageSync\Model\ResourceModel\ProductSyncLog\CollectionFactory as ProductsCollectionFactory;
use \Seizera\MageSync\Action\Sync\SyncHandler;

use Seizera\MageSync\Model\Sync\Categories as MainCategories;
use Seizera\MageSync\Model\Sync\Products as MainProducts;

/**
 * Class Index
 */
class Index extends Action implements HttpGetActionInterface
{
    const MENU_ID = 'Seizera_MageSync::categories';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    protected $_mainCategories;
    protected $_syncHandler;
    protected $_collectionFactory;
    protected $_mainProducts;
    protected $_productsCollectionFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        MainCategories $mainCategories,
        CatCollectionFactory $catCollectionFactory,
        ProductsCollectionFactory $productsCollectionFactory,
        MainProducts $mainProducts,
        SyncHandler $syncHandler
    ) {
        parent::__construct($context);
        $this->_mainProducts = $mainProducts;
        $this->_productsCollectionFactory = $productsCollectionFactory;
        $this->_syncHandler = $syncHandler;
        $this->_catCollectionFactory = $catCollectionFactory;
        $this->_mainCategories = $mainCategories;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Load the page defined in view/adminhtml/layout/seizerasync_products_index.xml
     *
     * @return Page
     */
    public function execute(){

        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // // $ch = $objectManager->create('Seizera\MageSync\Helper\Data');
        // $ch = $objectManager->create('Seizera\MageSync\Cron\Sync');

        // // $cid = $ch->getCronConfig('is_sync_active');
        // $cid = $ch->execute();
        // die(var_dump($cid));
        

        // // categories from main instance
        // $categories = $this->_mainCategories->getCategories();
        // // saved category Id from last sync
        // $collection = $this->_catCollectionFactory
        //                     ->create()
        //                     ->addFieldToSelect('mage_category_id')
        //                     ->addFieldToSelect('mage_client_category_id')
        //                     ->getData();
        // $collection = $this->getIdArray($collection, 'category');

        // foreach ($categories as $category) {
        //     if (in_array($category['id'], $collection)) {
        //         $catId = array_search($category['id'], $collection);
        //         // update category
        //         $this->_syncHandler->updateClientCat($catId, $category);
        //     }else{
        //         // create category
        //         $this->_syncHandler->createCategories($category);
        //     }
        // }
        // Products
        // products from main instance
        $products = $this->_mainProducts->getProducts();
        die(var_dump($products));
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

        die(var_dump('Done in this page'));
        $resultPage->setActiveMenu(static::MENU_ID);
        $resultPage->getConfig()->getTitle()->prepend(__('Categories Synchronisation'));

        return $resultPage;
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