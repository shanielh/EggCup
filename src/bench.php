<?php

//define( 'DEBUG', True );

// uncomment for Predis support
// require 'predis/lib/Predis/Autoloader.php';
// Predis\Autoloader::register();

require_once( "Cacheable.php" );
require_once( "Redis.php" );

class MyExistingClass extends \Eggcup\Cacheable {

	public $date = 1;

	/**
	* cache-me
	* cache-expiry: 7
	* cache-invalidate-on: date
	*/
	public function getSomeDataFromDB( $arg1, $arg2 ) {
		// take a long time to generate some data using $args1 and $args2 as determining vars.
		return $this->date;
	}

	/**
	* cache-flush: date
	*/
	public function setData( $arg ) {
		// take a long time to generate some data using $args1 and $args2 as determining vars.
		$this->date = $arg;
	}
}

// replace $cachedclass = new MyExistingClass();
// ...with:
$cachedclass = new \Eggcup\Redis( new MyExistingClass(), array( array( "host" => "red1.db", "port" => "6379" ) ) );

function bench() {
	global $cachedclass;
	
	print "benching \n";

	$start = microtime( true );
	for( $i = 0; $i < 10000; $i ++ ) {
		$cachedclass->getSomeDataFromDB( $i, 2 );
		if( $i % 100 == 0 ) {
			$cachedclass->setData( 1 );
		}
	}
	$dur = microtime( true ) - $start;
	print 10000/$dur;
}

bench();

// EOF
