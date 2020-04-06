<?php

namespace Seizera\MageSync\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Seizera\MageSync\Helper\SyncHelper;

/**
 * Class to get the newly placed order and push it to the main platform
 */
class Sync implements ObserverInterface
{
	protected $_syncHelper;

	public function __construct(SyncHelper $helper){
		$this->_syncHelper = $helper;
	}

    public function execute(Observer $observer){
		$order = $observer->getEvent()->getOrder();
    	$orderData = $order->getData();
    	$this->_syncHelper->createMainOrder($order);
    }
}