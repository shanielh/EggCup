<?php
namespace EggCup\Backend;
use \EggCup\ICacheItem as ICacheItem;

require_once '../../ext/predis/autoload.php';
\Predis\Autoloader::register();

/**
 * Redis cache backend, for long running scalable php applications or
 * short running php applications (Web sites, etc')
 *
 * @package default
 * @author Shani Elharrar
 **/
class PredisCacheBackend implements ICacheBackend
{
	
	private $_client;
	
	public function __construct(\Predis\Client $client) {
		
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
				
		$replies = $this->_client->pipeline(function($pipe) use ($item) {
			
			$ttl = $item->GetExpiryDate()->getTimestamp() - time();
			$key = PredisCacheBackend::FormatKey($item->GetKey());
			
			$pipe->set($key, serialize($item->GetValue()));
			$pipe->expire($key, $ttl);

			foreach ($item->GetTags() as $tag) {
				$pipe->sadd(PredisCacheBackend::FormatTag($tag), $key);
			}
			
		});
		
	}
	
	/**
	 * Removes all the cache items associated
	 * with the given tag name
	 *
	 * @return void
	 **/
	public function DeleteByTag($tagName) {
		
		$tagFormatted = PredisCacheBackend::FormatTag($tagName);
		$members = $this->_client->smembers($tagFormatted);
		
		$replies = $this->_client->pipeline(function ($pipe) use ($members, $tagFormatted) {
		
			$pipe->del($tagFormatted);
		
			foreach ($members as $member) {
				$pipe->del($member);
			}
		
		});
		
	}
	
	/**
	 * Tries to get the value of an item 
	 * by it's key
	 *
	 * @return bool
	 **/
	public function TryGetByKey($key, &$value) {
		
		$value = $this->_client->get(self::FormatKey($key));
		
		if ($value === null) {
			return false;
		}
		
		$value = unserialize($value);
		return true;		
	}
} 