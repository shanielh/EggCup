<?php
use \pUnit\Assert as Assert;

class MyCachedClass {
	
	public $_numTimesCalled = 0;
	
	/**
	 * Some function with cached value
	 *
	 * @cache
	 **/
	public function MyFunction() {
		$this->_numTimesCalled++;
		return $this->_numTimesCalled;
	}
	
}

/**
 * A tests class for CacheDecorator.
 *
 **/
class CacheDecoratorTests {
	
	/**
	 * Checks simple caching.
	 *
	 * @todo replace with unit tests (instead of integration test)
	 * @return void
	 **/
	public function Should_Call_Only_Once() {
		// Arrange
		$obj = new MyCachedClass();
		$cache = new EggCup\CacheDecorator($obj, new \EggCup\Backend\InMemoryCacheBackend());
		// Act
		$val = $cache->MyFunction();
		$val = $cache->MyFunction();
		// Assert
		Assert::AreEqual(1, $cache->_numTimesCalled);
		Assert::AreEqual(1, $val);
	}
	
}
