<?php

namespace Seizera\MageSync\Api;
use \Seizera\MageSync\Helper\Data;


class Communication
{
	protected $_url;
	protected $_username;
	protected $_password;
	protected $_helperData;

    public function __construct(Data $helper){
    	$this->_helperData = $helper;
    	$this->_url = $this->_helperData->getGeneralConfig('main_url');
    	$this->_username = $this->_helperData->getGeneralConfig('main_username');
    	$this->_password = $this->_helperData->getGeneralConfig('main_password');
    }

    /**
     * @return type
     */
    public function getUrl() {
        return $this->_url;
    }

    public function getToken($credentials){
    	$token_url= $this->_url . "rest/V1/integration/admin/token";
    	$ch = curl_init();
    	$data = array("username" => $credentials['username'], "password" => $credentials['password']);
    	$data_string = json_encode($data);
    	$ch = curl_init($token_url);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		'Content-Type: application/json',
    		'Content-Length: ' . strlen($data_string))
		);
    	$token = curl_exec($ch);
    	return json_decode($token);
    }

    public function getMageAdminToken(){
    	$credentials = [
    		'url'		=>	$this->_url,
    		'username'	=>	$this->_username,
    		'password'	=>	$this->_password,
    	];
    	return $this->getToken($credentials);
    }


	public function makeGetRequest($apiUrl){
		$ch = curl_init($apiUrl);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json", 
                "Authorization: Bearer ".$this->getMageAdminToken()
            )
        );

		$result = curl_exec($ch);
		return json_decode($result, 1);
	}

	public function convertToCurrentTimezone($time){
		$currentTimeZone = date_default_timezone_get();
		$date = new \DateTime($time, new \DateTimeZone($currentTimeZone));
		$date->setTimezone(new \DateTimeZone('Etc/UTC'));
		return $date->format('Y-m-d H:i:s');
	}

    public function downloadProductImage($url){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
        $mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath() . 'import/tmp/';
        $imgName = explode('/', $url);

        @$rawImage = file_get_contents($url);
        if($rawImage){
            file_put_contents($mediaPath . end($imgName), $rawImage);
            return $mediaPath . end($imgName);
        }
        return false;
    }

    function checkRemoteFileExists($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if( $httpCode == 200 ){return true;}
        return false;
    }
}