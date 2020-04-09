<?php
namespace Seizera\MageSync\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ImportCategories extends Command
{
	protected function configure(){
		$options = [];
		$this->setName('categories:import')
			->setDescription('Import Product Categories from Odoo ERP')
			->setDefinition($options);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		$output->writeln("Loading Categories...");
		$start = microtime(true);
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$mainCategories = $objectManager->create('Seizera\MageSync\Model\Sync\Categories');
		$catCollectionFactory = $objectManager->create('\Seizera\MageSync\Model\ResourceModel\CategorySyncLog\CollectionFactory');
		$categorySyncHandler = $objectManager->create('Seizera\MageSync\Action\Sync\Category');
		
		// // categories from main instance
		$categories = $mainCategories->getCategories();
		// saved category Id from last sync
		$collection = $catCollectionFactory
		                    ->create()
		                    ->addFieldToSelect('mage_category_id')
		                    ->addFieldToSelect('mage_client_category_id')
		                    ->getData();
		$collection = $this->getIdArray($collection, 'category');

		foreach ($categories as $category) {
		    if (in_array($category['id'], $collection)) {
		        $catId = array_search($category['id'], $collection);
		        // update category
		        $categorySyncHandler->updateClientCat($catId, $category);
		        try {
		        	$output->writeln('Updated Category "' . $category['name']);
		        } catch (\Exception $e) {
		        	$output->writeln('Failed to remove category "' . $category['name']);
		        	$output->writeln($e->getMessage() . "\n");
		        }
		    }else{
		        // create category
		        $categorySyncHandler->createCategories($category);
		        try {
		        	$output->writeln('Added Category "' . $category['name']);
		        } catch (\Exception $e) {
		        	$output->writeln('Failed to remove category "' . $category['name']);
		        	$output->writeln($e->getMessage() . "\n");
		        }
		    }
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