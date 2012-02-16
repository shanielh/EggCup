EggCup
======

Caching 'decorators' for PHP classes.

Used to wrap any PHP object with a memcache or redis based caching
layer.  Uses docstrings on 'hosted' objects methods to determine caching
behaviour

The Redis client supports efficient tag based cache invalidation.
Memcache attempts to emulate this using string based lists, which is
slow and not very atomic.  Only use invalidation if you really need it -
in many cases just letting keys expire will suit needs (we ran Eurogamer
for years with this model).

Memcache support requires one of the two PHP memcached modules (called 'Memcached' and
'Memcache' - note no 'd' in second.  It's bloody confusing, I know).
Memcached (with 'd') is recommended.

Redis mode (Eggcup_Redis.inc.php) supports both Predis (native PHP
version) and phpredis (C module), the latter being about 3 times faster.

Example::

    class MyExistingClass {

        /**
        * cache-me
        * cache-expiry: 60
        */
        public function getSomeDataFromDB( $arg1, $arg2 ) {
            // take a long time to generate some data using $args1 and $args2 as determining vars.
            return $someData;
        }
    }

    $cachedclass = new Eggcup( new MyExistingClass(), array( array( "host" => "192.168.4.142", "port" => "11216" ) ) );

    // this return value will be cached for 60s
    // first call will use DB
    $cachedclass->getSomeDataFromDB( 1, 2 );

    // second call will use memcache
    $cachedclass->getSomeDataFromDB( 1, 2 );

    // different args so will use DB again (cache key auto constructed from args)
    $cachedclass->getSomeDataFromDB( 3, 4 );

    sleep( 61 );

    // will us DB again
    $cachedclass->getSomeDataFromDB( 1, 2 );

Cache-invalidation is also possible by tagging methods::

    /**
    * cache-me
    * cache-expiry: 60
    * cache-invalidate-on: tag1, tag2
    */
    public function getSomeDataFromDB( $arg1, $arg2 ) {
        // take a long time to generate some data using $args1 and $args2 as determining vars.
        return $someData;
    }

Typically tags relate to SQL table names.

Then simply tag methods that write data which invalidates cache::

    /**
    * cache-flush: tag1
    */
    public function writeToSomeTable1( $data ) {
        //writes to some table
    }
    /**
    * cache-flush: tag2
    */
    public function writeToSomeTable2( $data ) {
        //writes to some table
    }

Calling either of these method will delete all cache keys written by
methods tagged with either tag1 or tag2.

Known Issues
------------

Currently, key tagging for invalidation is very primitive due to
memcache limitation, and may not be that robust.

Plans are afoot to reimplement using Redis instead of memcache, which
supports lists and transactions natively, making tagging much easier.


