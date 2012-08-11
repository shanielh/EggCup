<?php
namespace EggCup;

/**
 * Represents a cache item
 * to be sended to cache backends.
 *
 * @package EggCup
 **/
interface ICacheItem {
	
	/**
	 * Gets the key of the item
	 *
	 * @return string
	 **/
	public function GetKey();	
	
	/**
	 * Gets the tags of the item
	 *
	 * @return array
	 **/
	public function GetTags();
	
	/**
	 * Gets the expiry date of the item
	 *
	 * @return DateTime
	 **/
	public function GetExpiryDate();

	/**
	 * Gets the value of the item
	 *
	 * @return object
	 **/
	public function GetValue();
			
}
