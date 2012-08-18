<?php
use \pUnit\Assert as Assert;

/**
 * A tests class for ApcCacheBackend.
 *
 **/
class ApcCacheBackendTests {
	
	private function GetCache() {
		
		return new \EggCup\Backend\ApcCacheBackend('prefix');

	}
	
	/**
	 * Checks that getting a valid cache item
	 * works well
	 *
	 * @return void
	 **/
	public function Cache_And_Get_By_Key(){ 
		// Arrange
		$cache = $this->GetCache();
		$date = new DateTime();
		$date = $date->add(new DateInterval("P1Y"));
		$cacheItem = new \EggCup\CacheItem("key", $date, array(), "value");
		// Act
		$cache->Cache($cacheItem);
		// Assert
		Assert::IsTrue($cache->TryGetByKey("key", $value));
		Assert::AreEqual($value, "value");
	}
	
	/**
	 * Checks that getting a
	 * cached item that expired returns false
	 *
	 * @return void
	 **/
	public function Cache_And_Get_Expired() {
		// Arrange
		$cache = $this->GetCache();
		$date = new DateTime();
		$date = $date->sub(new DateInterval("P1Y"));
		$cacheItem = new \EggCup\CacheItem("key", $date, array(), "value");
		// Act
		$cache->Cache($cacheItem);
		// Assert
		Assert::IsFalse($cache->TryGetByKey("key", $value));
	}
	
	/**
	 * Checks that caching with tags is not supported
	 *
	 * @return void
	 **/
	public function Tagging_Not_Supported_In_Cache() {
		// Arrange
		$cache = $this->GetCache();
		$date = new DateTime();
		$date = $date->add(new DateInterval("P1Y"));
		$cacheItem = new \EggCup\CacheItem("key", $date, array("MyTag"), "value");
		
		// Act && Assert
		Assert::Throws(function () use($cache, $cacheItem) {$cache->Cache($cacheItem);}, '\EggCup\Exceptions\NotSupportedException');
		
	}
	
	/**
	 * Checks that caching with tags is not supported
	 *
	 * @return void
	 **/
	public function Removing_Tag_Is_Not_Supported() {
		// Arrange
		$cache = $this->GetCache();
		
		// Act && Assert
		Assert::Throws(function () use($cache) {$cache->DeleteByTag('tag');}, '\EggCup\Exceptions\NotSupportedException');
		
	}
	
	
}