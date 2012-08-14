<?php
namespace EggCup;

/**
 * Represents a cache item
 * to be sended to cache backends.
 *
 * @package EggCup
 **/
class CacheItem implements ICacheItem {
	
	private $_key;
	
	private $_expirationDate;
	
	private $_tags;
	
	private $_value;
	
	/**
	* Creates an instance of CacheItem
	*/
	public function __construct($key, \DateTime $expirationDate, array $tags, $value) 
	{
		$this->_key = $key;
		$this->_expirationDate = $expirationDate;
		$this->_tags = $tags;
		$this->_value = $value;
	}
	
	/**
	 * Gets the key of the item
	 *
	 * @return string
	 **/
	public function GetKey() {
		return $this->_key;
	}	
	
	/**
	 * Gets the tags of the item
	 *
	 * @return array
	 **/
	public function GetTags() {
		return $this->_tags;
	}
	
	/**
	 * Gets the expiry date of the item
	 *
	 * @return DateTime
	 **/
	public function GetExpiryDate() {
		return $this->_expirationDate;
	}

	/**
	 * Gets the value of the item
	 *
	 * @return object
	 **/
	public function GetValue() {
		return $this->_value;
	}
			
}
