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
	private $_search = null;


	private $_type = null;
	private $_mc = null;
	private $_table = array('schema' => null, 'data'=>null );
	private $_conf = array('db'=>null, 'mc'=>false);

	function __construct( $table, $db, $search, $type, $mc) {
		$this->_table = $table;
		$this->_db = $db;
		$this->_search = $search;
		
		$this->_type = $type;
		$this->_mc = $mc;
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
		$sheet =  $this->_db->getSchemaByName( $name, $allow_null );
		return $this->_formatSheet( $sheet );
	}


	/**
	 * 根据ID读取一个数据表 (Sheet)
	 * @param  [type]  $id         [description]
	 * @param  boolean $allow_null 如果为true, 如果Sheet不存在，返回null。 默认为 false 抛出异常
	 * @return [type]              [description]
	 */
	public function getSheetByID( $id, $allow_null=false ) {
		$sheet = $this->_db->getSchema( $id, $allow_null );
		return $this->_formatSheet( $sheet );
	}

	/**
	 * 读取一个字段
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function getField( $schema_id, $name ) {
		$type =  $this->_db->getField( $schema_id, $name );
		return $this->_typeObj($type);
	}

	/**
	 * 增加一个新字段 
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function addField( $schema_id, $name, Type $type ) {

		return $this->_db->addField( $schema_id, $name, $type->bindField($schema_id, $name)->toArray() );
	}

	/**
	 * 修改一个新字段 
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function alterField( $schema_id, $name, Type $type ) {
		return $this->_db->alterField( $schema_id, $name, $type->bindField($schema_id, $name)->toArray() );
	}


	/**
	 * 替换一个新字段 （如不存在则创建 ）
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function replaceField( $schema_id, $name, Type $type ) {
		return $this->_db->replaceField( $schema_id, $name, $type->bindField($schema_id, $name)->toArray() );
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


	private function _formatSheet( & $data ) {
		$data['name'] = $data['_spt_name'];
		$data['create_at'] = $data['_spt_create_at'];
		$data['update_at'] = $data['_spt_update_at'];
		$data['is_deleted'] =$data['_spt_is_deleted'];

		foreach ($data['_spt_schema_json'] as $field=>$type ) {
			$data['columns'][$field] = $this->_typeObj( $type );
		}

		return $data;
	}


	private function _typeObj( $type_schema ) {
		if ( !is_array($type_schema['data']) ||
			 !is_array($type_schema['option']) ||
			 !isset($type_schema['type']) 
			) {

			print_r( $type_schema );
			throw new Exception(" _typeObj 返回结果错误");
		}
		return $this->_type->load($type_schema['type'], $type_schema['data'], $type_schema['option']);
	}



	function get( $id ) {

	}
}