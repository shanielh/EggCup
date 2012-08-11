<?php
namespace EggCup\Backend;
use \EggCup\ICacheItem as ICacheItem;

/**
 * Represents a backend of
 * a cache
 *
 * @package EggCup
 **/
interface ICacheBackend
{
	
	/**
	 * Caches the item with its settings
	 * in the backend
	 *
	 * @return void
	 **/
	public function Cache(ICacheItem $item);
	
	/**
	 * Removes all the cache items associated
	 * with the given tag name
	 *
	 * @return void
	 **/
	public function DeleteByTag($tagName);
	
	/**
	 * Tries to get the value of an item 
	 * by it's key
	 *
	 * @return bool
	 **/
	public function TryGetByKey($key, &$value);
	
} 