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
		$_keyHash[$item->GetKey()] = &$item;
		
		foreach ($item->GetTags() as $tag) {
			if (!isset($_tagsHash[$tag])) {
				$_tagsHash[$tag] = array();
			}
			
			$_tagsHash[$tag][] = &$item;
		}
	}
	
	/**
	 * Removes all the cache items associated
	 * with the given tag name
	 *
	 * @return void
	 **/
	public function DeleteByTag($tagName) {
		
		if (isset($_tagsHash[$tagName])) {
			foreach ($_tagsHash[$tagName] as $item) {
				unset($_keyHash[$item->GetKey()]);
			}
			
			unset($_tagsHash[$tagName]);
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
		if (!isset($_keyHash[$key])) {
			return false;
		}
		
		$value = $_keyHash[$key];
		return true;
	}
	
}  