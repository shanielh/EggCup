<?php
use \pUnit\Assert as Assert;
use \Mockery as Mockery;

class MyCachedClass {
	
	public $_numTimesCalled = 0;
	
	/**
	 * Some function with cached value
	 *
	 * @cache
	 **/
	public function MyFunction() {
		$this->_numTimesCalled++;
		return 5;
	}
	
	/**
	 * Some function with cached value and tags
	 *
	 * @cache
	 * @cache-tags Tag1,Tag2
	 **/
	public function MyFunction2() {
		return 5;
	}
	
	/**
	 * Some function with cached value and tags
	 *
	 * @cache
	 * @cache-ttl P5Y
	 **/
	public function WithTTL() {
		return 5;
	}
	
	/**
	 * Some function that invalidates the tags :
	 * Tag1,Tag2
	 *
	 * @cache-invalidate Tag1,Tag2
	 **/
	 public function InvalidateTags() {
		
	}
	
}

/**
 * A tests class for CacheDecorator.
 *
 **/
class CacheDecoratorTests {
	
	/**
	 * Gets a mock that returns on TryGetByKey with the given key false,
	 * And then validates the \EggCup\ICacheItem given in 'Cache' method
	 * using the validation callback given.
	 *
	 * @return \EggCup\Backend\ICacheBackend
	 **/
	private function GetMock($key, $validation) {
		$backendMock = Mockery::mock('\EggCup\Backend\ICacheBackend')
			->shouldReceive('TryGetByKey')
			->with($key, null)
			->andReturn(false)
			->once()->mock()
			->shouldReceive('Cache')
			->with($validation)
			->once()->mock();
		
		$obj = new MyCachedClass();
		return $cache = new EggCup\CacheDecorator($obj, $backendMock);
	}
	
	
	/**
	 * Checks simple caching.
	 *
	 * @todo replace with unit tests (instead of integration test)
	 * @return void
	 **/
	public function Should_Return_The_Given_Value_From_The_Cache() {
		// Arrange
		$backendMock = Mockery::mock('\EggCup\Backend\ICacheBackend')
						->shouldReceive('TryGetByKey')
						->with(serialize(array('MyFunction', array())), null)
						->andReturn(true)
						->once()
						->mock();
						
		$obj = new MyCachedClass();
		$cache = new EggCup\CacheDecorator($obj, $backendMock);
		// Act
		$val = $cache->MyFunction();
		// Assert
		Assert::AreEqual(null, $val);
		Assert::AreEqual(0, $obj->_numTimesCalled);
	}
		
	/**
	 * Checks that calling 'MyFunction'
	 * calls Cache() on the backend
	 * with the wanted key.
	 *
	 * @return void
	 **/
	public function Should_Call_Cache_With_Right_Key() {
		// Arrange
		$key = serialize(array('MyFunction', array()));
		$cache = $this->GetMock($key,
			Mockery::on(function ($cacheItem) use($key) {
				return $cacheItem->GetKey() == $key;
			}));
		
		// Act
		$val = $cache->MyFunction();
		
		// Assert (In mock)							
	}
	
	/**
	 * Checks that calling 'MyFunction'
	 * calls Cache() on the backend
	 * with the right value returned from the target object.
	 *
	 * @return void
	 **/
	public function Should_Call_Cache_With_Right_Value() {
		// Arrange
		$cache = $this->GetMock(serialize(array('MyFunction', array())),
			Mockery::on(function ($cacheItem) {
				return $cacheItem->GetValue() == 5;
			}));
		
		// Act
		$val = $cache->MyFunction();
		
		// Assert (In mock)					
	}
	
	/**
	 * Checks that calling 'MyFunction'
	 * calls Cache() on the backend
	 * with the right tags.
	 *
	 * @return void
	 **/
	public function Should_Call_Cache_With_No_Tags() {
		// Arrange
		$cache = $this->GetMock(serialize(array('MyFunction', array())),
			Mockery::on(function ($cacheItem) {
				return count($cacheItem->GetTags()) == 0;
			}));
		
		// Act
		$val = $cache->MyFunction();
		
		// Assert (In mock)					
	}
	
	/**
	 * Checks that calling 'MyFunction2'
	 * calls Cache() on the backend
	 * with the right tags.
	 *
	 * @return void
	 **/
	public function Should_Call_Cache_With_Right_Tags() {
		// Arrange
		$cache = $this->GetMock(serialize(array('MyFunction2', array())),
			Mockery::on(function ($cacheItem) {
				return $cacheItem->GetTags() == array('Tag1','Tag2');
			}));
		
		// Act
		$val = $cache->MyFunction2();
		
		// Assert (In mock)					
	}
	
	/**
	 * Checks that calling 'WithTTL'
	 * calls Cache() on the backend
	 * with the right TTL.
	 *
	 * @return void
	 **/
	public function Should_Call_Cache_With_Right_ExpiryDate() {
		// Arrange
		$expected = new \DateTime();
		$expected = $expected->add(new \DateInterval('P5Y'));
		
		$cache = $this->GetMock(serialize(array('WithTTL', array())),
			Mockery::on(function ($cacheItem) use($expected) {
				return $cacheItem->GetExpiryDate() == $expected;
			}));
		
		// Act
		$val = $cache->WithTTL();
		
		// Assert (In mock)
	}
	
	
	/**
	 * Checks that calling 'InvalidateTags'
	 * calls DeleteByTags() on the backend
	 * with the right tags.
	 *
	 * @return void
	 **/
	public function Should_Invalidate_Tags() {
		// Arrange
		$backendMock = Mockery::mock('\EggCup\Backend\ICacheBackend')
						->shouldReceive('DeleteByTag')
						->with(Mockery::anyOf('Tag1', 'Tag2'))
						->twice()
						->mock();
						
		$obj = new MyCachedClass();
		$cache = new EggCup\CacheDecorator($obj, $backendMock);
		// Act
		$val = $cache->InvalidateTags();
		// Assert (Mock)
	}
	
}
