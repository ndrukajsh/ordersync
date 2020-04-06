<?php

namespace Seizera\MageSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

use \Seizera\MageSync\Helper\Data;
use \Seizera\MageSync\Api\Communication;

class CustomerHelper extends AbstractHelper
{
	protected $_communication;
    protected $_dataHelper;

    public function __construct(Data $dataHelper, Communication $communication){
    	$this->_communication = $communication;
        $this->_dataHelper = $dataHelper;
    }

    public function getMainInstanceCurrentCustomerId(){
        $userEmail = $this->_dataHelper->getGeneralConfig('user_email');
    	$apiUrl = $this->_communication->getUrl() . 'rest/V1/customers/search?searchCriteria[filter_groups][0][filters][0][field]=email&searchCriteria[filter_groups][0][filters][0][value]='.$userEmail.'&searchCriteria[filter_groups][0][filters][0][condition_type]=eq';

        $total = $this->_communication->makeGetRequest($apiUrl);

        return $total['items'][0];
    }
}
