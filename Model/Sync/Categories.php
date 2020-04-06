<?php

namespace Seizera\MageSync\Model\Sync;
use Seizera\MageSync\Api\Communication;

/**
 * Class to get categories from main instance
 */
class Categories
{
    protected $_baseCommunication;
    protected $mergedArray = [];

    public function __construct(Communication $communication){
    	$this->_baseCommunication = $communication;
    }

	public function getCategories($categoryId = null){
		$mainMageUrl = $this->_baseCommunication->getUrl() . 'rest/V1/categories/';
		if (!is_null($categoryId)) {
			$mainMageUrl .= $categoryId;
		}
		$ch = curl_init($mainMageUrl);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Content-Type: application/json", 
				"Authorization: Bearer ".$this->_baseCommunication->getMageAdminToken()
			)
		);

		$result = curl_exec($ch);
		$result = json_decode($result, 1);
		$res = $this->buildArray($result);
		array_shift($res);
		return $res;
	}

	protected function buildArray(array $array){
		$flatten = $this->flatten($array);
		$full = [];
		$aaa = [];
		$keys = [
			"id",
			"parent_id",
			"name",
			"is_active",
			"position",
			"level",
			"product_count",
		];
		$k = 0;
		for ($i = 0; $i < count($flatten); $i++) {
			$full[$keys[$k]] = $flatten[$i];
			$k++;
			if ($k % 7 == 0) {
				unset($full['product_count']);
				$aaa[] = $full;
				$full = [];
				$k = 0;
			}
		}
		return $aaa;
	}

	public function flatten(array $array, ?int $depth = null, bool $assoc = false): array{
		$return = [];
		$addElement = function ($key, $value) use (&$return, $assoc) {
			if (!$assoc || array_key_exists($key, $return)) {
				$return[] = $value;
			} else {
				$return[$key] = $value;
			}
		};
		foreach ($array as $key => $value) {
			if (is_array($value) && (is_null($depth) || $depth >= 1)) {
				foreach ($this->flatten($value, is_null($depth) ? $depth : $depth - 1, $assoc) as $k => $v) {
					$addElement($k, $v);
				}
			} else {
				$addElement($key, $value);
			}
		}
		return $return;
	}
}