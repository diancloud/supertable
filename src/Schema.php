<?php
/**
 * Superbucket 基类
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

	private $_stor = null;
	private $_search = null;


	private $_type = null;
	private $_mc = null;
	private $_bucket = array('schema' => null, 'data'=>null );

	function __construct( $bucket, $stor, $search, $type, $mc) {
		$this->_bucket = $bucket;
		$this->_stor = $stor;
		$this->_search = $search;

		$this->_type = $type;
		$this->_mc = $mc;
		$this->_checkbucket();
	}


	/**
	 * 创建一个空数据表(Sheet)
	 * 
	 * === $id: $name ==========
	 * null | null | null | ...
	 * =========================
	 * null | null | null | ...
	 * -------------------------
	 * null | null | null | ...
	 * -------------------------
	 * 
	 * @return Int Sheet ID 
	 */
	public function createSheet( $name, $data = array() ) {
		
		// 创建空数据表结构
		$id = $this->_stor->createSchema( $name, $data = array() );

		// 创建新的索引类型
		if ( $this->_search->createType( $name ) === false ) {
			$this->_stor->deleteSchema( $name );
			throw new Exception("Search Error: " . $this->_search->error() );	
		}

		return $id;
	}



	/**
	 *  根据Name读取一个数据表 (Sheet)
	 * @param  [type]  $name       [description]
	 * @param  boolean $allow_null 如果为true, 如果Sheet不存在，返回null。 默认为 false 抛出异常
	 * @return [type]              [description]
	 */
	public function getSheetByName( $name, $allow_null=false ) {
		$sheet =  $this->_stor->getSchemaByName( $name, $allow_null );
		return $this->_formatSheet( $sheet );
	}


	/**
	 * 根据ID读取一个数据表 (Sheet)
	 * @param  [type]  $id         [description]
	 * @param  boolean $allow_null 如果为true, 如果Sheet不存在，返回null。 默认为 false 抛出异常
	 * @return [type]              [description]
	 */
	public function getSheetByID( $id, $allow_null=false ) {
		$sheet = $this->_stor->getSchema( $id, $allow_null );
		return $this->_formatSheet( $sheet );
	}

	/**
	 * 读取一个字段
	 * 
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function getField( $schema_id, $name ) {
		$type =  $this->_stor->getField( $schema_id, $name );
		return $this->_typeObj($type);
	}

	/**
	 * 增加一个新字段 
	 * 
	 * === ID:$schema_id  ==============
	 * + $name(Type) | null | null | ...
	 * =================================
	 *   null        | null | null | ...
	 * ---------------------------------
	 *   null        | null | null | ...
	 * ---------------------------------
	 * 
	 * @param [type] $name [description]
	 * @param  Int sheet_id
	 */
	public function addField( $schema_id, $name, Type $type ) {

		$schema_id = $this->_stor->addField( $schema_id, $name, $type->bindField($schema_id, $name)->toArray() );

		// 更新索引
		$newSchema = $this->_stor->getSchema( $schema_id );
		if ( $this->_search->updateType( $newSchema['_spt_name'], $newSchema['_spt_schema_json'] ) === false ) {
			$this->_stor->rollbackField( $schema_id, $name );
			throw new Exception("Search Error: " . $this->_search->error() );	
		}

		return $schema_id;
	}


	/**
	 * 修改一个新字段 
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function alterField( $schema_id, $name, Type $type ) {

		$schema_id = $this->_stor->alterField( $schema_id, $name, $type->bindField($schema_id, $name)->toArray() );

		// 更新索引
		$newSchema = $this->_stor->getSchema( $schema_id );
		if ( $this->_search->updateType( $newSchema['_spt_name'], $newSchema['_spt_schema_json'] ) === false ) {
			$this->_stor->rollbackField( $schema_id, $name );
			throw new Exception("Search Error: " . $this->_search->error() );	
		}

		return $schema_id;
	}


	/**
	 * 替换一个新字段 （如不存在则创建 ）
	 * @param [type] $name [description]
	 * @param Type   $type [description]
	 */
	public function putField( $schema_id, $name, Type $type ) {
		$schema_id = $this->_stor->putField( $schema_id, $name, $type->bindField($schema_id, $name)->toArray() );

		
		// 数据没有变更，直接返回
		if ( is_array($schema_id) ) {
			return $schema_id;
		}

		// 更新索引
		$newSchema = $this->_stor->getSchema( $schema_id );
		if ( $this->_search->updateType( $newSchema['_spt_name'], $newSchema['_spt_schema_json'] ) === false ) {
			$this->_stor->rollbackField( $schema_id, $name );
			throw new Exception("Search Error: " . $this->_search->error() );	
		}

		return $schema_id;
	}

	
	/**
	 * 删除一个新字段
	 * @param  [type]  $schema_id        [description]
	 * @param  [type]  $name             [description]
	 * @param  boolean $allow_not_exists 不存在时是否抛出异常，默认 false, 如果不存在，抛出异常 
	 * @return [type]                    [description]
	 */
	public function dropField( $schema_id, $name, $allow_not_exists=false ) {
		$schema_id = $this->_stor->dropField( $schema_id, $name, $allow_not_exists );
		
		// 数据没有变更，直接返回
		if ( is_array($schema_id) ) {
			return $schema_id;
		}

		// 更新索引
		$newSchema = $this->_stor->getSchema( $schema_id );
		if ( $this->_search->updateType( $newSchema['_spt_name'], $newSchema['_spt_schema_json'] ) === false ) {
			$this->_stor->rollbackField( $schema_id, $name );
			throw new Exception("Search Error: " . $this->_search->error() );	
		}

		return $schema_id;
	}


	//=========

	/**
	 * 检查数据表结构
	 * @param  [type] $bucket [description]
	 * @return [type]        [description]
	 */
	private function _checkbucket( $bucket=null ) {
		$bucket = ($bucket == null) ? $this->_bucket : $bucket;
		$this->_stor->checkbucket();
	}


	private function _formatSheet( & $data ) {
		if( !is_array($data) ) {
			return $data;
		}

		$data['name'] = $data['_spt_name'];
		$data['version'] = $data['_spt_schema_version'];
		$data['revision'] = $data['_spt_schema_revision'];
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


}