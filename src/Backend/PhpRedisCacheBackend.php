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
class PhpRedisCacheBackend implements ICacheBackend
{
	
	private $_client;
	
	public function __construct(\Redis $client) {
		
		$this->_client = $client;
		
	}
	
	public static function FormatKey($key) {
		return '__KEY_' . $key;
	}
	
	public static function FormatTag($tag) {
		return '__TAG_' . $tag;
	}
	
	/**
	 * Caches the item with its settings
	 * in the backend
	 *
	 * @return void
	 **/
	public function Cache(ICacheItem $item) {
		
		$client = $this->_client;
				
		$multi = $this->_client->multi();
		
		$expireAt = $item->GetExpiryDate()->getTimestamp();
		$key = self::FormatKey($item->GetKey());
			
		$multi->set($key, serialize($item->GetValue()));
		$multi->expireAt($key, $expireAt);

		foreach ($item->GetTags() as $tag) {
			$multi->sAdd(self::FormatTag($tag), $key);
		}
		
		$multi->exec();
	}
	
	/**
	 * Removes all the cache items associated
	 * with the given tag name
	 *
	 * @return void
	 **/
	public function DeleteByTag($tagName) {
		
		$tagFormatted = self::FormatTag($tagName);
		$members = $this->_client->sMembers($tagFormatted);

		$multi = $this->_client->multi();
		$multi->del($tagFormatted);
		
		foreach ($members as $member) {
			$multi->del($member);
		}
		
		$multi->exec();
	}
	
	/**
	 * Tries to get the value of an item 
	 * by it's key
	 *
	 * @return bool
	 **/
	public function TryGetByKey($key, &$value) {
		
		$value = $this->_client->get(self::FormatKey($key));
		
		if ($value === false) {
			return false;
		}
		
		$value = unserialize($value);
		return true;		
	}
} 