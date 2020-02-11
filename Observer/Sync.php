<?php

namespace Seizera\OrderSync\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

/**
 * Class to get the newly placed order and push it to the main platform
 */
class Sync implements ObserverInterface
{
	protected $_helperData;
	protected $_logger;

	public function __construct(
		\Seizera\OrderSync\Helper\Data $helper, 
		\Psr\Log\LoggerInterface $logger
	){
		$this->_helperData = $helper;
		$this->_logger = $logger;
	}

    public function execute(Observer $observer){
    	$mainUrl = $this->_helperData->getGeneralConfig('main_url');
    	$mainUsername = $this->_helperData->getGeneralConfig('main_username');
    	$mainPassword = $this->_helperData->getGeneralConfig('main_password');
		$order = $observer->getEvent()->getOrder();
		// ok deri ktu
		// $this->_logger->debug('CCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCC' . $order->getCustomerId());
		$orderId = $order->getId();
    }
}