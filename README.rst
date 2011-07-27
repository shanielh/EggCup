EggCup
======

Caching 'decorators' for PHP classes.

Used to wrap any PHP object with a memcache based caching layer.
Uses docstrings on 'hosted' objects methods to determine caching
behaviour

Requires one of the two PHP memcached modules (called 'Memcached' and
'Memcache' - note no 'd' in second.  It's bloody confusing, I know).
Memcached (with 'd') is recommended.

Example::

    $cachedinterface = new Eggcup( new MyExistingInterface(), array( array( "host" => "192.168.4.142", "port" => "11216" ) ) );
