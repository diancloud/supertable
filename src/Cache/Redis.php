<?php
/**
 * 内存缓存
 *
 * CLASS 
 *
 * 	   Redis 
 *
 * USEAGE: 
 * 	   $rd = new Tuanduimao\Supertable\Cache\Redis([
 * 	   		'host'=>'127.0.0.1',
 * 	   		'port'=>'6379',
 * 	   		'prefix'=>'tdm_',
 * 	   		'db'=>3
 * 	   ]);
 * 	   
 *     $rd->get($key);
 *     $rd->set($key, $value, $expire_time);
 *     $rd->del($key, $timeout );
 */


Namespace Tuanduimao\Supertable\Cache;
use \Redis as MRedis;

class Redis {

	private $redis = null;
	private $prefix = null;

	function __construct( $conf ) {

		$this->redis = new MRedis;
		$conf = (is_array($conf))? $conf : [];
			$this->prefix = (isset($conf['prefix']))? $conf['prefix'] : 'spt_';
			$host = (isset($conf['host']))? $conf['host'] : null;
			$port = (isset($conf['port']))? $conf['port'] : null;
			$timeout = (isset($conf['timeout']))? $conf['timeout'] : 1.0;
			$retry = (isset($conf['retry']))? $conf['retry'] : 500;
			$socket = (isset($conf['socket']))? $conf['socket'] : null;
			$passwd = (isset($conf['passwd']))? $conf['passwd'] : null;
			$db = (isset($conf['db']))? $conf['db'] : 0;

		$ret = true;
		if ( !empty($socket) ) {
			$ret = $this->redis->connect($socket);
		} else {
			$ret = $this->redis->connect($host, $port, $timeout, NULL, $retry);
		}

		if ( !empty($passwd) ) {
			$ret = $this->redis->auth($passwd);
		}

		if ( !$ret ) {
			$this->redis = null;
			$this->prefix = null;
		} else {
			$this->redis->select($db);
		}

	}


	/**
	 * 检查服务器是否正常
	 * @return [type] [description]
	 */
	public function ping(){

		if ( empty($this->redis) ) return false;
		return $this->redis->ping();
	}


	/**
	 * 从内存中读取数据
	 * @param  [type] $key 键值
	 * @return [mix] 成功返回数值， 失败返回false
	 */
	public function get( $key ) {
		if ( empty($this->redis) ) return false;
		return $this->redis->get("{$this->prefix}{$key}");
	}


	/**
	 * 添加一个值，如果已经存在，则覆写
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function set( $key, $value, $expires=0, $flag=false ){
		if ( empty($this->redis) ) return false;

		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$key}", $expires, $value );
		return $this->redis->set("{$this->prefix}{$key}", $value);
	}


	/**
	 * 删除一个键值 
	 * @param  [type]  $key     键值
	 * @param  integer $timeout 设置的秒数以后过期 默认为0
	 * @return [bool]   成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function del( $key, $timeout=0 ) {
		if ( empty($this->redis) ) return false;

		if ( $timeout > 0 ) {
			return $this->redis->setTimeout("{$this->prefix}{$key}", $timeout);
		} 

		$ret = $this->redis->delete("{$this->prefix}{$key}");
		return ( $ret === 1 )? true  : false;
	}
}