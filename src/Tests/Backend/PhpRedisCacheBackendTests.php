<?php
use \pUnit\Assert as Assert;

/**
 * A tests class for PhpRedisCacheBackend.
 *
 **/
class PhpRedisCacheBackendTests {
	
	private function GetCache() {
		
		$redis = new Redis();
		$redis->pconnect('127.0.0.1');
		return new \EggCup\Backend\PhpRedisCacheBackend($redis);

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
	 * Checks that caching an item
	 * and unvalidating it's tag 
	 * removes the item.
	 *
	 * @return void
	 **/
	public function Cache_And_Remove_Tags() {
		// Arrange
		$cache = $this->GetCache();
		$date = new DateTime();
		$date = $date->add(new DateInterval("P1Y"));
		$cacheItem = new \EggCup\CacheItem("key", $date, array("MyTag"), "value");
		$cache->Cache($cacheItem);
		// Act
		$cache->DeleteByTag("MyTag");
		// Assert
		Assert::IsFalse($cache->TryGetByKey("key", $value));	
	}
	
}