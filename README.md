# EggCup

Invisible caching 'decorators' for PHP classes.

Used to wrap any PHP object with a caching layer. Uses docstrings on 'hosted' objects methods to determine caching behaviour.

Currently only in-memory caching support is enabled, But soon to have redis support. 

Redis backend will support both Predis (native PHP
version) and phpredis (C module), the latter being about 3 times faster.

## Tags
	
* *@cache* - Enables cache on this function
* *@cache-tags* - Registers the cache item to invalidate on the given tags (Eg. @cache-tags Tag1,Tag2)
* *@cache-ttl* - Specifies the TTL for this cache item (In [interval spec](http://www.php.net/manual/en/dateinterval.format.php "See interval specifications on PHP site"), Defaults to PT5M)
* *@cache-invalidate* - Specifies to invalidate the given tags (Eg. @cache-invalidate Tag1,Tag2)

## Example

    class MyExistingClass {

        /**
        * @cache
        * @cache-ttl PT1M
        */
        public function getSomeDataFromDB( $arg1, $arg2 ) {
            // take a long time to generate some data using $args1 and $args2 as determining vars.
            return $someData;
        }
    }

    $cachedclass = new \EggCup\CacheDecorator( new MyExistingClass(), new \EggCup\Backend\InMemoryCacheBackend() );

    // this return value will be cached for 60s
    // first call will use DB
    $cachedclass->getSomeDataFromDB( 1, 2 );

    // second call will use cache
    $cachedclass->getSomeDataFromDB( 1, 2 );

    // different args so will use DB again (cache key auto constructed from args)
    $cachedclass->getSomeDataFromDB( 3, 4 );

    sleep( 61 );

    // will us DB again
    $cachedclass->getSomeDataFromDB( 1, 2 );

## Known Issues

Currently, only support for in memory caching supported. Support for redis is planned, And maybe for memcache too. 