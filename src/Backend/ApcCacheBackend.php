<?php
namespace EggCup\Backend;
use \EggCup\ICacheItem as ICacheItem;

/**
 * Redis cache backend, for long running scalable php applications or
 * short running php applications (Web sites, etc')
 *
 * @package default
 * @author Shani Elharrar
 **/
class ApcCacheBackend implements ICacheBackend
{
		
	private $_prefix;
		
	public function __construct($prefix) {
		
		$this->_prefix = $prefix;
		
	}
		
	/**
	 * Caches the item with its settings
	 * in the backend
	 *
	 * @return void
	 **/
	public function Cache(ICacheItem $item) {
		
		if (count($item->GetTags()) > 0) {
			throw new \EggCup\Exceptions\NotSupportedException("Tagging is not supported via ApcCacheBackend");
		}
			
		$expireAt = $item->GetExpiryDate()->getTimestamp();
		$ttl = $expireAt - time();
		$key = $this->_prefix . $item->GetKey();
		
		$value = serialize(array($item->GetValue(), $expireAt));
		
		apc_store($key, $value, $ttl);
	}
	
	/**
	 * Removes all the cache items associated
	 * with the given tag name
	 *
	 * @return void
	 **/
	public function DeleteByTag($tagName) {
		
		throw new \EggCup\Exceptions\NotSupportedException("Tagging is not supported via ApcCacheBackend");
	}
	
	/**
	 * Tries to get the value of an item 
	 * by it's key
	 *
	 * @return bool
	 **/
	public function TryGetByKey($key, &$value) {
		
		$key = $this->_prefix. $key;
		$innerValue = apc_fetch($key, $success);

		if ($success === false) {
			return false;
		}
		
		$innerValue = unserialize($innerValue);
		
		// If expired
		if (time() >= $innerValue[1]) {
			return false;
		}
		
		$value = $innerValue[0];
		return true;		
	}
} 