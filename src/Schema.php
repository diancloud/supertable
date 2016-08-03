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
use Tuanduimao\Supertable\Items;
use Tuanduimao\Supertable\Item;


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
	public function createSheet( $name, $data = array(), $allow_exists=false ) {
		
		// 创建空数据表结构
		$schema_id = $this->_stor->createSchema( $name, $data );

		// 创建新的索引类型
		try {
			$newSchema = $this->_stor->getSchema( $schema_id );
			if ( $this->_search->createType( $name, $newSchema['_spt_schema_version'], $allow_exists ) === false ) {
				$this->_stor->deleteSchema( $schema_id, true );
				throw new Exception("Search Error: " . $this->_search->error() );	
			}
		} catch(Exception $e ) {
			$this->_stor->deleteSchema( $schema_id, true );
			// throw new Exception($e->getMessage());	
			throw $e;
			
		}

		return $schema_id;
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
	 * 根据ID更新一张数据表(Sheet) 扩展信息
	 * @param  [type] $id   [description]
	 * @param  array  $data [description]
	 * @return [type]       [description]
	 */
	public function updateSheet( $id, $data = array() ) {
		$this->_stor->updateSchemaData($id, $data );
		return $id;
	}


	/**
	 * 根据$options设置的查询条件，检索符合条件数据表(Sheet)
	 * @param  [type]  $options [description]
	 * @param  [type]  $page    [description]
	 * @param  integer $perpage [description]
	 * @param  integer $maxrows [description]
	 * @return [type]           [description]
	 */
	public function querySheet( $options, $page=null,  $perpage=20, $maxrows=0 ) {
		 $items = $this->_stor->querySchema( $options, $page, $perpage, $maxrows );
		 $items->each( function( $item, $schema ) {
		 	$data = $item->toArray();
		 	$schema->_formatSheet($data);
		 	return new Item( $data );
		 }, $this );

		 return $items;
	}


	/**
	 * 删除Sheet
	 * @param  [type]  $id        [description]
	 * @param  boolean $mark_only [description]
	 * @return [type]             [description]
	 */
	public function deleteSheet( $id, $mark_only=true  ) {

		// 删除索引
		try {
			$schema = $this->_stor->getSchema( $id );
			if ( $this->_search->deleteType( $schema['_spt_name'] ) === false ) {
				throw new Exception("Search Error: " . $this->_search->error() );	
			}
		} catch(Exception $e ) {
			//throw new Exception($e->getMessage());	
			throw $e;
		}

		// 删除记录
		return $this->_stor->dropSchema( $id, $mark_only );
	}


	/**
	 * 删除Sheet 索引
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function deleteSheetIndex( $id ){

		try {
			$schema = $this->_stor->getSchema( $id );
		} catch(Exception $e ) {
			//throw new Exception($e->getMessage());	
			throw $e;
		}
		
		return $this->_search->deleteType( $schema['_spt_name'] );
	}


	/**
	 * 创建Sheet 索引
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function createSheetIndex( $id ) {
		try {
			$schema = $this->_stor->getSchema( $id );
		} catch(Exception $e ) {
			//throw new Exception($e->getMessage());	
			throw $e;
		}

		return $this->_search->createType( $schema['_spt_name'], $schema['_spt_schema_version'] );
	}


	/**
	 * 重建Sheet 索引
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function rebuildSheetIndex( $id ) {

		if( $this->deleteSheetIndex($id) !== false) {
			return $this->createSheetIndex( $id );
		}

		return false;
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
		try {
			$newSchema = $this->_stor->getSchema( $schema_id );
			if ( $this->_search->updateType( $newSchema['_spt_name'], $newSchema['_spt_schema_json'] ) === false ) {
				$this->_stor->rollbackSchema( $schema_id );
				throw new Exception("Search Error: " . $this->_search->error() );	
			}
		} catch(Exception $e ) {
			$this->_stor->rollbackSchema( $schema_id );
			// throw new Exception($e->getMessage());	
			throw $e;
			
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
		
		try {
			$newSchema = $this->_stor->getSchema( $schema_id );
			if ( $this->_search->updateType( $newSchema['_spt_name'], $newSchema['_spt_schema_json'] ) === false ) {
				$this->_stor->rollbackSchema( $schema_id );
				throw new Exception("Search Error: " . $this->_search->error() );	
			}
		} catch(Exception $e ) {
			$this->_stor->rollbackSchema( $schema_id );
			// throw new Exception($e->getMessage());	
			throw $e;
			
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
		try {
			if ( $this->_search->updateType( $newSchema['_spt_name'], $newSchema['_spt_schema_json'] ) === false ) {
				$this->_stor->rollbackSchema( $schema_id );
				throw new Exception("Search Error: " . $this->_search->error() );	
			}
		} catch(Exception $e ) {			
			$this->_stor->rollbackSchema( $schema_id );
			throw $e;
			// throw new Exception($e->getMessage());	
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
		$data['_create_at'] = $data['_spt_create_at'];
		$data['_update_at'] = $data['_spt_update_at'];
		$data['_is_deleted'] =$data['_spt_is_deleted'];

		foreach ($data['_spt_schema_json'] as $field=>$type ) {
			$type = $this->_typeObj( $type )->bindField($data['_id'],$field);
			$data['columns'][$field] = $type;
		}

		return $data;
	}

	private function _typeObj( $type_schema ) {

		if ( !is_array($type_schema['data']) ||
			 !is_array($type_schema['option']) ||
			 !isset($type_schema['type']) 
			) {

			throw new Exception(" _typeObj 返回结果错误");
		}

		$type_schema['option'] = isset($type_schema['option']) ? $type_schema['option'] : [];
		$type_schema['data'] = isset($type_schema['data']) ? $type_schema['data'] : [];

		return $this->_type->load($type_schema['type'], $type_schema['data'], $type_schema['option']);
	}


}