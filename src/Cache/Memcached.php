<?php
/**
 * 内存缓存 Helper
 *
 * CLASS 
 *
 * 	   Memcached 
 *
 * USEAGE: 
 *
 *     Model('Helper::Mem')->get($key);
 *     Model('Helper::Mem')->set($key, $value, $expire_time);
 *     Model('Helper::Mem')->del($key, $timeout );
 */

Namespace Tuanduimao\Supertable\Cache;
use \Memcache as Memcache;

class Memcached {
	
	private $mmc;
	private $isConnect = false;
	private $conf;

	function __construct( $conf ) {
		$this->conf = $conf;
		$this->mmc = new Memcache;
		foreach ($this->conf as $host) {
			$this->isConnect = $this->mmc->addServer($host['host'], $host['port'] );
		}
	}


	/**
	 * 从MC中读取数据
	 * @param  [type] $key 键值
	 * @return [mix] 成功返回数值， 失败返回false
	 */
	public function get( $key ) {

		if ( !$this->isConnect ) return false;
		return $this->mmc->get( $key );
	}

	/**
	 * 添加一个值，如果已经存在，则覆写
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function set( $key, $value, $expires=0, $flag=false ){

		if ( !$this->isConnect ) return false;
		return $this->mmc->set($key, $value, $flag, $expires);
	}

	/**
	 * 删除一个键值 
	 * @param  [type]  $key     键值
	 * @param  integer $timeout 设置的秒数以后过期 默认为0
	 * @return [bool]   成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function del( $key, $timeout=0 ) {
		if ( !$this->isConnect ) return false;
		return $this->mmc->delete($key,$timeout);
	}


	/**
	 * 删除一组键值 
	 * @param  [type] $keys [description]
	 * @return [type]       [description]
	 */
	public function delete( $keys  ){
		if ( !$this->isConnect ) return false;
		return $this->mmc->delete($keys, 0);
	}
}