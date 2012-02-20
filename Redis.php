<?php

	namespace Eggcup;

	class ConnectionError extends \RuntimeException {
	}


	/**
	 * Markies Magic Eggcup - Redis Edition
	 *
	 * EuroGamer'sGreatCachingUltraPackage
	 * Used to wrap any PHP object with a redis based caching layer.
	 * Uses docstrings on 'hosted' objects methods to determine caching behaviour
	 * 
	 * Example:
	 *   $cachedinterface = new Eggcup\Redis( new MyExistingInterface(), array( array( "host" => "192.168.4.142", "port" => "6379" ) ) );
	 */
	class Redis {

		/**
		 * The object we're caching
		 */
		public $obj;

		/**
		 * Reflection API instance for obj
		 */
		public $refl;

		/**
		 * The redis instance in effect
		 */
		public $red;

		/**
		 * Are we connected?
		 */
		public $connected;

		/**
		 * Instance specific cache prefix used to segregate keys that might
		 * otherwise clash
		 */
		public $cache_prefix = "";

		/**
		 * Which backend we're using
		 * Can be 'predis' or 'phpredis'
		 */
		public $backend = false;

		public static function debug( $msg ) {
			if( defined( "DEBUG" ) ) {
				error_log( "Eggcup: " . $msg );
			}
		}
		/**
		 * Constructor.
		 *
		 * Pass some sort of data source interface (model!) that you wish to cache the output of
		 * and smartly invalidate cache keys during selected writes
		 *
		 * @obj		object	The object you want to cache the results of
		 * @cache_config	array	List of redis configs
		 */
		public function __construct( $obj, $cache_config ) {
			$this->obj = $obj;
			if( $obj instanceof Cacheable ) {
				$this->obj->_cup = $this;
			}
			$this->refl = new \ReflectionClass( $obj );

			if( class_exists( 'Predis\\Client' ) ) {
				$this->backend = 'predis';
				foreach( $cache_config as $cc ) {
					// can only use one
					// TODO: sharding?
					if( $this->refl->hasMethod( "__cachePrefix" ) ) {
						$this->cache_prefix = $this->obj->__cachePrefix();
						$this->red = new Predis\Client( $cc, array( 'prefix' => $this->obj->__cachePrefix() ) );
					} else {
						$this->red = new Predis\Client( $cc );
					}
					break;
				}
			} else if( class_exists( '\\Redis' ) ) {
				$this->backend = 'phpredis';
				$this->red = new \Redis();
				foreach( $cache_config as $cc ) {
					if( $this->red->connect( $cc[ "host" ], (int)$cc[ "port" ] ) ) {
						// can only use one
						$this->connected = true;
						break;
					}
				}
				if( $this->refl->hasMethod( "__cachePrefix" ) ) {
					self::debug( "  Setting cache prefix" );
					$this->cache_prefix = $this->obj->__cachePrefix();
					$redis->setOption( Redis::OPT_PREFIX, $this->obj->__cachePrefix() ); // interesting
				}
			} else {
				throw new \Exception( 'No redis client extensions found!  I need Predis or PhpRedis.' );
			}
			if( !$this->connected ) {
				throw new ConnectionError(); 
			}
			
		}

		/**
		 * Tag a redis key for group flushing.
		 *
		 * maps tokens set in cache-write to object keys and stores them
		 * in a special range of redis keys
		 * e.g. __tags(table1) =  ^|^call1(fg828248r)^|^call2(j23hwdfh24)
		 * uses 'append' for some kind of atomicity
		 * TODO verify append is actually atomic and not faked by php 
		 *
		 * @key		string	The redis key to tag
		 * @tag		string	The tag to use for group flushing
		 */
		public function tagKey( $key, $tag ) {
			self::debug( "Tagging method $key with $tag" );
			$this->red->sAdd( "__tags(" . $tag . ")", $key );
			//self::debug( "__tags(" . $tag . ")" . "=" . $this->red->get( "__tags(" . $tag . ")" ) );
		}

		/**
		 * Flush a group of redis keys
		 *
		 * Will cache invalidate on writes all those keys associated with
		 * the specified tag.
		 *
		 * @tag		string	Tag for group to delete (e.g. 'table1')
		 */
		public function invalidateTag( $tag ) {
			self::debug( "  Invalidating $tag" );
			$keylist = $this->red->smembers( "__tags(" . $tag . ")" );
			$this->red->del( "__tags(" . $tag . ")" );
			// keys can appear more than once in a group list - no atomic way to do write
			// heavy sets in redis, though see this: http://dustin.github.com/2011/02/17/redis-set.html
			// for an interesting discussion
			$keys_deleted = array();
			if( $keylist != false ) {
				//$keys = explode( "^|^", $keylist );
				if( 'predis' == $this->backend ) {
					$pipe = $this->red->pipeline();
				} else {
					$pipe = $this->red->multi(\Redis::PIPELINE);
				}
				foreach( $keylist as $key ) {
					if( $key != "" && ! isset( $keys_deleted[ $key ] ) ) {
						self::debug( "  Deleting $key" );
						$pipe->del( $key, 0 );
						$keys_deleted[ $key ] = 1;
					}
				}
				if( 'predis' == $this->backend ) {
					$pipe->execute();
				} else {
					$pipe->exec();
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
						self::debug( "  Method invalidates on " . $m[1] );

					} else if( preg_match( "/cache\-expiry\s*:\s*(.*)\s*$/", $line, $m ) ) {
						$cache_args[ "expiry" ] = (int) ( $m[1] * ( 1 + ( rand(0, 50) - 25 ) / 100 ) );
						self::debug( "  Method expiry " . $m[1] );

					} else if( preg_match( "/cache\-flush\s*:\s*(.*)\s*$/", $line, $m ) ) {
						$cache_args[ "writes" ] = array_map( "trim", explode( ",", $m[1] ) );
						self::debug( "  Method writes " . $m[1] );

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
			self::debug( "Getting $name" );
			return $this->obj->$name;
		}

		/**
		 * Magic method to support member variable updates on hosted object
		 *
		 * Do not call explicitly.
		 */
		public function __set( $name, $val ) {
			self::debug( "Setting $name" );
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
				self::debug( "Method $name has a docstring" );
				$cache_args = $this->makeCacheArgs( $com );
				if( isset( $cache_args[ "writes" ] ) ) {
					foreach( $cache_args[ "writes" ] as $tag ) {
						$this->invalidateTag( $tag );
					}
					self::debug( "  Returning from write method $name" );
					return call_user_func_array( array( $this->obj, $name), $args );
				}

				if( $cache_args[ "cacheme" ] && ! defined( "CACHE_BYPASS" ) ) {
					$key = $this->cache_prefix . $name . "(" . md5( serialize( $args ) ) . ")";
					self::debug( "  Method $name has signature $key" );
					$ret = $this->red->get( $key );
					self::debug( "    - got " . var_export($ret, true) );
					if( 'predis' == $this->backend && NULL === $ret || 'phpredis' == $this->backend && false === $ret ) {
						self::debug( "  --- Cache miss for $key" );
						$ret = call_user_func_array( array( $this->obj, $name), $args );
						$this->red->setex( $key, $cache_args[ "expiry" ], $ret );
						foreach( $cache_args[ "reads" ] as $tag ) {
							$this->tagKey( $key, $tag );
						}
					} else {
						self::debug( "  *** Cache HIT for $key!" );
					}
					return $ret;

				}
				self::debug( "Method $name has no caching info in docstring" );
				return call_user_func_array( array( $this->obj, $name), $args );

			} else {
				self::debug( "Method $name was handled normally" );
				return call_user_func_array( array( $this->obj, $name), $args );

			}
		}
	}


?>
