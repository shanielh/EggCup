<?php
	//define( 'DEBUG', True );
	require 'predis/lib/Predis/Autoloader.php';
	Predis\Autoloader::register();

	require_once( "Eggcup_Redis.inc.php" );

    class MyExistingClass {

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

    $cachedclass = new Eggcup( new MyExistingClass(), array( array( "host" => "127.0.0.1", "port" => "6379" ) ) );
    //$cachedclass = new MyExistingClass();


function test() {
	global $cachedclass;

	$cachedclass->setData( 1 );
	
    // this return value will be cached for 60s
    // first call will use DB
    print $cachedclass->getSomeDataFromDB( 1, 2 );
	print "\n";

	sleep( 2 );

	$cachedclass->date = 2;

    // second call will use memcache
    print $cachedclass->getSomeDataFromDB( 1, 2 );
	print "\n";

	sleep( 2 );

    // different args so will use DB again (cache key auto constructed from args)
    print $cachedclass->getSomeDataFromDB( 3, 4 );
	print "\n";

    sleep( 10 );

    // will us DB again
    print $cachedclass->getSomeDataFromDB( 1, 2 );
	print "\n";
}

function bench() {
	global $cachedclass;
	
	print "benching \n";

	$start = microtime( true );
	for( $i = 0; $i < 100000; $i ++ ) {
		$cachedclass->getSomeDataFromDB( $i, 2 );
		if( $i % 100 == 0 ) {
			$cachedclass->setData( 1 );
		}
	}
	$dur = microtime( true ) - $start;
	print 100000/$dur;
}

bench();

// EOF
