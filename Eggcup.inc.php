<?php

	/**
	 * Markies Magic Eggcup
	 *
	 * EuroGamer'sGreatCachingUltraPackage
	 * Used to wrap any PHP object with a memcache based caching layer.
	 * Uses docstrings on 'hosted' objects methods to determine caching behaviour
	 * 
	 * Example:
	 *   $cachedinterface = new Eggcup( new MyExistingInterface(), array( array( "host" => "192.168.4.142", "port" => "11216" ) ) );
	 */
	class Eggcup {

		/**
		 * The object we're caching
		 */
		public $obj;

		/**
		 * Reflection API instance for obj
		 */
		public $refl;

		/**
		 * The memcache instance in effect
		 */
		public $mc;

		/**
		 * Instance specific cache prefix used to segregate keys that might
		 * otherwise clash
		 */
		public $cache_prefix = "";

		public static function debug( $msg ) {
			if( defined( "DEBUG" ) ) {
				error_log( $msg );
			}
		}
		/**
		 * Constructor.
		 *
		 * Pass some sort of data source interface (model!) that you wish to cache the output of
		 * and smartly invalidate cache keys during selected writes
		 *
		 * @obj		object	The object you want to cache the results of
		 * @cache_config	array	List of memcache configs
		 */
		public function __construct( $obj, $cache_config ) {
			$this->obj = $obj;
			$this->refl = new ReflectionClass( $obj );
			if( class_exists( "Memcached" ) ) {
				$this->mc = new Memcached();
				$this->mc->setOption( Memcached::OPT_COMPRESSION, false );
			} else {
				$this->mc = new Memcache();
				$this->mc->setCompressThreshold( 10000000000 );
			}
			foreach( $cache_config as $cc ) {
				$this->mc->addServer( $cc[ "host" ], $cc[ "port" ] );
			}
			// Use this if you have to switch to memcache native implementation (no 'd')
			if( $this->refl->hasMethod( "__cachePrefix" ) ) {
				Eggcup::debug( "Setting cache prefix" );
				$this->cache_prefix = $this->obj->cachePrefix();
			}
			
		}

		/**
		 * Tag a memcache key for group flushing.
		 *
		 * maps tokens set in cache-write to object keys and stores them
		 * in a special range of memcache keys
		 * e.g. __tags(table1) =  ^|^call1(fg828248r)^|^call2(j23hwdfh24)
		 * uses 'append' for some kind of atomicity
		 * TODO verify append is actually atomic and not faked by php 
		 *
		 * @key		string	The memcache key to tag
		 * @tag		string	The tag to use for group flushing
		 */
		public function tagKey( $key, $tag ) {
			Eggcup::debug( "Tagging method $key with $tag" );
			if( ! $this->mc->add( "__tags(" . $tag . ")", "^|^" . $key ) ) {
				$this->mc->append( "__tags(" . $tag . ")", "^|^" . $key );
			}
			Eggcup::debug( "__tags(" . $tag . ")" . "=" . $this->mc->get( "__tags(" . $tag . ")" ) );
		}

		/**
		 * Flush a group of memcache keys
		 *
		 * Will cache invalidate on writes all those keys associated with
		 * the specified tag.
		 *
		 * @tag		string	Tag for group to delete (e.g. 'table1')
		 */
		public function invalidateTag( $tag ) {
			Eggcup::debug( " Invalidating $tag" );
			$keylist = $this->mc->get( "__tags(" . $tag . ")" );
			$this->mc->delete( "__tags(" . $tag . ")", 0 );
			// keys can appear more than once in a group list - no atomic way to do write
			// heavy sets in memcached, though see this: http://dustin.github.com/2011/02/17/memcached-set.html
			// for an interesting discussion
			$keys_deleted = array();
			if( $keylist != false ) {
				$keys = explode( "^|^", $keylist );
				foreach( $keys as $key ) {
					if( $key != "" && ! isset( $keys_deleted[ $key ] ) ) {
						Eggcup::debug( "  Deleting $key" );
						$this->mc->delete( $key, 0 );
						$keys_deleted[ $key ] = 1;
					}
				}
			}
		}

		/**
		 * Use cache objects docstrings to configure cache behaviour
		 *
		 * Here's the format:
		 * cache-me									- whether to cache OUTPUT or not!
		 * cache-invalidate-on: table1,table2		- optional) which tags to label this call with for smart flushing
		 *											  USE ONLY WHEN CRITICAL - this can lead to an accumulation of data
		 *											  if the tags are rarely invalidated by keys regularly expire
		 * cache-expiry: 2							- time to live for key
		 * cache-flush: table1						- this call should flush all keys with these tags
		 */
		public function makeCacheArgs( $com ) {
			$cache_args = array(
				"cachme" => false,
				"reads" => array(),
				//"writes" => array(),
				"expiry" => 5
			);

			if( $com != "" ) {

				foreach( explode( "\n", $com ) as $line ) {
					$m = array();
					if( preg_match( "/cache\-invalidate-on\s*:\s*(.*)\s*$/", $line, $m ) ) {
						$cache_args[ "reads" ] = array_map( "trim", explode( ",", $m[1] ) );
						Eggcup::debug( "  Method invalidates on " . $m[1] );

					} else if( preg_match( "/cache\-expiry\s*:\s*(.*)\s*$/", $line, $m ) ) {
						$cache_args[ "expiry" ] = (int) $m[1];
						Eggcup::debug( "  Method expiry " . $m[1] );

					} else if( preg_match( "/cache\-flush\s*:\s*(.*)\s*$/", $line, $m ) ) {
						$cache_args[ "writes" ] = array_map( "trim", explode( ",", $m[1] ) );
						Eggcup::debug( "  Method writes " . $m[1] );

					} else if( preg_match( "/cache\-me/", $line ) ) {
						$cache_args[ "cacheme" ] = true;

					} else if( preg_match( "/cache\-/", $line ) ) {
						error_log( "[CACHE] warning - invalid instruction: '$line'" );

					}
				}
			}
			return $cache_args;
		}

		/**
		 * Magic method to support member variable retrieval on hosted object
		 *
		 * Do not call explicitly.
		 */
		public function __get( $name ) {
			Eggcup::debug( "Getting $name" );
			return $this->obj->$name;
		}

		/**
		 * Magic method to support member variable updates on hosted object
		 *
		 * Do not call explicitly.
		 */
		public function __set( $name, $val ) {
			Eggcup::debug( "Setting $name" );
			$this->obj->$name = $val;
		}

		/**
		 * Magic method to handle calls on hosted object.
		 *
		 * Transparently decides whether to cache result of call on hosted objects - do not call explicitly.
		 */
		public function __call( $name, $args ) {
			$method = $this->refl->getMethod( $name );
			$com = trim( $method->getDocComment() );

			if( $com ) {
				Eggcup::debug( "Method $name has a docstring" );
				$cache_args = $this->makeCacheArgs( $com );
				if( isset( $cache_args[ "writes" ] ) ) {
					foreach( $cache_args[ "writes" ] as $tag ) {
						$this->invalidateTag( $tag );
					}
					Eggcup::debug( " Returning from write method $name" );
					return call_user_func_array( array( $this->obj, $name), $args );

				}

				if( $cache_args[ "cacheme" ] ) {
					$key = $this->cache_prefix . $name . "(" . md5( serialize( $args ) ) . ")";
					Eggcup::debug( "Method $name has signature $key" );
					$ret = $this->mc->get( $key );
					if( false === $ret ) {
						Eggcup::debug( "--- Cache miss for $key" );
						$ret = call_user_func_array( array( $this->obj, $name), $args );
						$this->mc->set( $key, $ret, $cache_args[ "expiry" ] );
						foreach( $cache_args[ "reads" ] as $tag ) {
							$this->tagKey( $key, $tag );
						}
					} else {
						Eggcup::debug( "*** Cache HIT for $key!" );
					}
					return $ret;

				}
				Eggcup::debug( "Method $name has no caching info in docstring" );
				return call_user_func_array( array( $this->obj, $name), $args );

			} else {
				Eggcup::debug( "Method $name was handled normally" );
				return call_user_func_array( array( $this->obj, $name), $args );

			}
		}
	}

?>
