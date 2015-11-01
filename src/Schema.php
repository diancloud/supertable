<?php
/**
 * SuperTable 基类
 *
 * CLASS 
 *
 * 	   Schema 
 *
 * USEAGE: 
 *
 *     不要直接使用
 * 
 */

namespace Tuanduimao\Supertable;
use \Exception as Exception;
use Tuanduimao\Supertable\Type;


class Schema {

	private $_db = null;
	private $_table = array('schema' => null, 'data'=>null );
	private $_conf = array('db'=>null, 'mc'=>false);

	function __construct( $table, $db_conf, $mc_conf=false) {
		$this->_table = $table;
		$this->_conf['db'] = $db_conf;
		$this->_conf['mc'] = $mc_conf;
		$this->db_init();
		$this->_checkTable();
	}

	/**
	 * 创建一个新的数据表(Sheet)
	 * @return [type] [description]
	 */
	public function createSheet( $name, $data = array() ) {
		return $this->_db->createSchema( $name, $data = array() );
	}


	/**
	 *  根据Name读取一个数据表 (Sheet)
	 * @param  [type]  $name       [description]
	 * @param  boolean $allow_null 如果为true, 如果Sheet不存在，返回null。 默认为 false 抛出异常
	 * @return [type]              [description]
	 */
	public function getSheetByName( $name, $allow_null=false ) {
		return $this->_db->getSchemaByName( $name, $allow_null );
	}

	


	/**
	 * 根据ID读取一个数据表 (Sheet)
	 * @param  [type]  $id         [description]
	 * @param  boolean $allow_null 如果为true, 如果Sheet不存在，返回null。 默认为 false 抛出异常
	 * @return [type]              [description]
	 */
	public function getSheetByID( $id, $allow_null=false ) {
		return $this->_db->getSchema( $id, $allow_null );
	}


	
	/**
	 * 增加一个新字段 
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function addField( $schema_id, $name, Type $type ) {
		return $this->_db->addField( $schema_id, $name, $type->toArray() );
	}

	/**
	 * 修改一个新字段 
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function alterField( $schema_id, $name, Type $type ) {
		return $this->_db->alterField( $schema_id, $name, $type->toArray() );
	}

	/**
	 * 替换一个新字段 （如不存在则创建 ）
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function replaceField( $schema_id, $name, Type $type ) {
		return $this->_db->replaceField( $schema_id, $name, $type->toArray() );
	}

	
	/**
	 * 删除一个新字段
	 * @param  [type]  $schema_id        [description]
	 * @param  [type]  $name             [description]
	 * @param  boolean $allow_not_exists 不存在时是否抛出异常，默认 false, 如果不存在，抛出异常 
	 * @return [type]                    [description]
	 */
	public function dropField( $schema_id, $name, $allow_not_exists=false ) {
		return $this->_db->dropField( $schema_id, $name, $allow_not_exists );
	}




	//=========
	

	/**
	 * 检查数据表结构
	 * @param  [type] $table [description]
	 * @return [type]        [description]
	 */
	private function _checkTable( $table=null ) {
		$table = ($table == null) ? $this->_table : $table;
		$this->_db->checktable();
	}



	/**
	 * 连接数据库，并创建数据库对象
	 * @return [type] [description]
	 */
	private function db_init() {
		$table = $this->_table;
		$engine = $this->_conf['db']['engine'];
		$class_name = "\\Tuanduimao\\Supertable\\Database\\{$engine}";
		if ( !class_exists($class_name) ) {
			throw new Exception("$class_name not exists!");
		}
		$this->_db = new $class_name( $table, $this->_conf['db']['options'] );
		return $this;
	}

	function get( $id ) {

	}
}