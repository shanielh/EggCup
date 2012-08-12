<?php
namespace EggCup\Backend;
use \EggCup\ICacheItem as ICacheItem;

/**
 * undocumented class
 *
 * @package default
 * @author Shani Elharrar
 **/
class InMemoryCacheBackend implements ICacheBackend 
{
	private $_keyHash = array();
	
	private $_tagsHash = array();
	
	/**
	 * Caches the item with its settings
	 * in the backend
	 *
	 * @return void
	 **/
	public function Cache(ICacheItem $item)
	{
		$this->_keyHash[$item->GetKey()] = &$item;
		
		foreach ($item->GetTags() as $tag) {
			if (!isset($this->_tagsHash[$tag])) {
				$this->_tagsHash[$tag] = array();
			}
			
			$this->_tagsHash[$tag][] = &$item;
		}
	}
	
	/**
	 * Removes all the cache items associated
	 * with the given tag name
	 *
	 * @return void
	 **/
	public function DeleteByTag($tagName) {
		
		if (isset($this->_tagsHash[$tagName])) {
			foreach ($this->_tagsHash[$tagName] as $item) {
				unset($this->_keyHash[$item->GetKey()]);
			}
			
			unset($this->_tagsHash[$tagName]);
		}
		
	}
	
	/**
	 * Tries to get the value of an item 
	 * by it's key
	 *
	 * @return bool
	 **/
	public function TryGetByKey($key, &$value)
	{	
		if (!isset($this->_keyHash[$key])) {
			return false;
		}
		$cacheItem = $this->_keyHash[$key];
				
		// Fallback by expiration date
		if ($cacheItem->GetExpiryDate() <= new \DateTime()) {
			return false;
		}

		// Get value
		$value = $cacheItem->GetValue();
		return true;
	}
	
}  