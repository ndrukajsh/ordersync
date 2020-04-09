<?php
namespace Seizera\MageSync\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ImportProducts extends Command
{
	protected function configure(){
		$options = [];
		$this->setName('products:import')
			->setDescription('Import Produts from Main Magento Instance')
			->setDefinition($options);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		$start = microtime(true);
		$output->writeln('Loading Products from Main Instance...');
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$mainProducts = $objectManager->create('Seizera\MageSync\Model\Sync\Products');
		$productsCollectionFactory = $objectManager->create('\Seizera\MageSync\Model\ResourceModel\ProductSyncLog\CollectionFactory');
		$productSyncHandler = $objectManager->create('Seizera\MageSync\Action\Sync\Product');

		$objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);
		$appState = $objectManager->get('Magento\Framework\App\State');
		$appState->setAreaCode('frontend');
		
		$productLoadTime = microtime(true);
		// // products from main instance
		$products = $mainProducts->getProducts();
		$loadTimeElapsed = microtime(true) - $productLoadTime;
		$output->writeln('Products took ' . gmdate("H:i:s", $loadTimeElapsed) . ' (Hour:minute:second) to load');
		$output->writeln('Initiating product sync with Client Magento...');
		// saved product Id from last sync
		$collection = $productsCollectionFactory
		                    ->create()
		                    ->addFieldToSelect('mage_product_id')
		                    ->addFieldToSelect('mage_client_product_id')
		                    ->getData();
		$collection = $this->getIdArray($collection, 'product');

		$i = 1;
		foreach ($products as $product) {
		    if (in_array($product['id'], $collection)) {
		        $productId = array_search($product['id'], $collection);
		        // update product
		        $productSyncHandler->updateClientProduct($productId, $product);
		        try {
		        	$output->writeln($i . '. Updated Product ' . $product['name']);
		        } catch (\Exception $e) {
		        	$output->writeln('Warning ' . $product['name']);
		        	$output->writeln($e->getMessage() . "\n");
		        }
		    }else{   	
		        // create product
		        try {
		        	$initWriteTime = microtime(true);
			        $productSyncHandler->createProduct($product);
			        $writeTime = microtime(true) - $initWriteTime;
		        	$output->writeln($i . '.Added Product ' . $product['name'] . ' || Time: ' . gmdate("H:i:s", $writeTime));
		        } catch (\Exception $e) {
		        	$output->writeln('Warning: ' . $product['name']);
		        	$output->writeln($e->getMessage() . "\n");
		        }
		    }
		    $i++;
		}
		$time_elapsed_secs = microtime(true) - $start;
		$output->writeln('Execution time: ' . gmdate("H:i:s", $time_elapsed_secs) . ' (Hour:minute:second)');
		return $this;
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