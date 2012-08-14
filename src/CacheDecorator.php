<?php
namespace EggCup;

/**
 * A Cache decorator for all object
 *
 * @package default
 **/
class CacheDecorator 
{
	
	private $_obj;
	
	private $_backend;
	
	private $_commentParser;
	
	private static $_defaultOptions = array('cache-tags' => '', 
											'cache-ttl' => 'PT5M');
	
	/**
	 * Creates an instance of CacheDecorator
	 * with the given parameters
	 *
	 * @return CacheDecorator
	 **/
	public function __construct($obj, \EggCup\Backend\ICacheBackend $backend) {
		$this->_obj = $obj;
		$this->_backend = $backend;
		$this->_commentParser = new CommentParser($obj);
	}
	
	/**
	 * Calls the given method with the given arguments
	 *
	 * @return object
	 **/
	private function Call($name, $args) {
		return call_user_func_array(array($this->_obj, $name), $args);
	}
	
	/**
	 * Explode the given string and trims the inner
	 * strings.
	 *
	 * @return array
	 **/
	private static function TrimExplode($delimiter, $string) {
		$retVal = array();
		
		foreach (explode($delimiter, $string) as $val) {
			$val = trim($val);
			if ($val != '') {
				$retVal[] = $val;
			}
		}
		
		return $retVal;
	}
	
	/**
	 * Calls the given object with caching enabled
	 *
	 * @return object
	 **/
	public function __call($name, $args) {
		
		$key = serialize(array($name, $args));
		$options = $this->_commentParser->GetTags($name);
		
		// Invalidations
		if (isset($options['cache-invalidate'])) {
			foreach (self::TrimExplode(',', $options['cache-invalidate']) as $tag) {
				$this->_backend->DeleteByTag($tag);
			}
		}
				
		// Should not be cached
		if (!isset($options['cache'])) {
			return $this->Call($name, $args);
		}
		
		// Should be cached
		if ($this->_backend->TryGetByKey($key, $value)) {
			return $value;
		} 
	
		// If not cached
		$expiryDate = new \DateTime();
		
		$options = $options + self::$_defaultOptions;		
		$interval = new \DateInterval($options['cache-ttl']);
		
		$expiryDate = $expiryDate->add($interval);
		$tags = self::TrimExplode(',', $options['cache-tags']);
		$value = $this->Call($name, $args);
		
		$cacheItem = new CacheItem($key, $expiryDate, $tags, $value);
		
		$this->_backend->Cache($cacheItem);
		
		return $value;
	}
	
	/**
	 * Simple getter implementation
	 *
	 * @return object
	 **/
	public function __get($name) {
		return $this->_obj->{$name};
	}
	
	/**
	 * Simple setter implementation
	 *
	 * @return void
	 **/
	public function __set($name, $value) {
		$this->_obj->{$name} = $value;
	}
	
	
} 
?>