<?php
class InMemoryCacheBackendTests {
	
	public function Put_And_Get_Should_Work(){ 
		
		$cache = new \EggCup\Backend\InMemoryCacheBackend();
		$cache->Cache();
		
	}
	
}