<?php
/**
 * Storage 接口: MySQL
 *
 * CLASS 
 *
 * 	   Tuanduimao\Supertable\Storage\Mysql 
 *
 * USEAGE: 
 *
 *     不要直接使用
 * 
 */

Namespace Tuanduimao\Supertable\Storage;
use \Tuanduimao\Supertable\Items as Items;
use \Tuanduimao\Supertable\Item as Item;
use \Mysqli as Mysqli;
use \Exception as Exception;

class Mysql {
	
	private $dbs = array();
	private $_schemaBackup = array();

	private $_opts  = array();
	private $_table =  array('schema' => null, 'data'=>null );
	private $_data_table = array();
	private $_schema_table = array();
	private $_data_struct = array(
		'_spt_id' => array(
			'type' => 'BIGINT',
			'auto' => 'AUTO_INCREMENT',
			'index' => array('PRIMARY KEY'),
			'length' => 20,
			'orhas' => 'PRIMARY KEY',
		),

		'_spt_schema_id' => array(
			'type' => 'BIGINT',
			'index' => array('KEY'),
			'length' => 20,
		),

		'_spt_data_json' => array(
			'type' => 'TEXT',
			'allow_null' => true,
		),
		'_spt_create_at' => array(
			'type'  => 'DATETIME',
			'index' => 'KEY',
			'allow_null' => true,
		),
		'_spt_update_at' => array(
			'type'  => 'DATETIME',
			'index' => 'KEY',
			'allow_null' => true,
		),

		'_spt_is_deleted' => array(
			'type'  => 'INT',
			'index' => 'KEY',
			'default' => '0',
			'length' => 1,
		)

	);

	private $_schema_struct = array(
		'_spt_id' => array(
			'type' => 'BIGINT',
			'auto' => 'AUTO_INCREMENT',
			'index' => array('PRIMARY KEY'),
			'length' => 20,
			'orhas' => 'PRIMARY KEY',
		),

		'_spt_name' => array(
			'type' => 'VARCHAR',
			'index' => array('UNIQUE KEY'),
			'length' => 150,
		),

		'_spt_schema_version' => array(
			'type' => 'INT',
			'length' => 10,
			'default' => 1,
			'allow_null' => false,
		),

		'_spt_schema_revision' => array(
			'type' => 'INT',
			'length' => 10,
			'default' => 1,
			'allow_null' => false,
		),

		'_spt_schema_json' => array(
			'type' => 'TEXT',
			'allow_null' => true,
		),
		'_spt_create_at' => array(
			'type'  => 'DATETIME',
			'index' => 'KEY',
			'allow_null' => true,
		),
		'_spt_update_at' => array(
			'type'  => 'DATETIME',
			'index' => 'KEY',
			'allow_null' => true,
		),
		
		'_spt_is_deleted' => array(
			'type'  => 'INT',
			'index' => 'KEY',
			'default' => '0',
			'length' => 1,
		)
	);


	function __construct(  $table, $options ) {
		$this->_table = $table;
		$this->options($options);
	}


	/**
	 * API: 检查数据表结构，是否符合要求
	 *      1） 如不存在则创建
	 *      2） 读取数据表结构，并缓存。
	 *      
	 * @param  [type] $table [description]
	 * @return 返回数据对象
	 * 
	 */
	function checkbucket() {
		 
		 $table = $this->_table;

		// 判断数据表是否存在，如不存在则创建
		if ( $this->_table_exists( $table['data'] ) === false ) {
			$this->_table_create(  $table['data'], $this->_data_struct );
		}

		// 判断结构表是否存在， 如不存在则创建
		if ( $this->_table_exists( $table['schema'] ) === false ) {
			$this->_table_create(  $table['schema'], $this->_schema_struct );
		}

		// 检查表结构
		$this->_table_check( $table['data'], $this->_data_struct, $this->_data_table );
		$this->_table_check( $table['schema'], $this->_schema_struct, $this->_schema_table );

		return $this;
	}


	/**
	 * API: 创建一个新的数据结构记录，成功返回主键ID
	 * @return Int 数据表主键ID
	 */
	function createSchema( $name, $extData = array() ) {
		$table = $this->_table;
		$this->_filter( $extData, $this->_schema_table );
		$data = array_merge($extData, array(
			'_spt_schema_json' => '{}',
			'_spt_name' => $name
		));
		return $this->_create($table['schema'], $data, $this->_schema_table );
	}


	/**
	 * API: 根据ID读取一个数据结构
	 * @param  [type] $schema_id [description]
	 * @return [type]            [description]
	 */
	function getSchema( $schema_id, $allow_null=true ) {
		$table_name = $this->getDB('master')->real_escape_string( $this->_table['schema']);
		$primary_field = $this->_schema_table['primary']['COLUMN_NAME'];

		$sql = $this->prepare("SELECT * from `$table_name` WHERE `$primary_field`=?s AND `_spt_is_deleted` ='0' LIMIT 1", $schema_id);
		$data = $this->getLine( $sql, 'slave' );

		if ( $data == null ) {
			if ($allow_null) return null;
			throw new Exception("$schema_id 不存在 (SQL=$sql) ");
		}
		$data['_spt_schema_json'] = json_decode($data['_spt_schema_json'], true);
		$data['_spt_schema_json']  =  ( $data['_spt_schema_json']  == null )? array(): $data['_spt_schema_json'];
		$data['_id'] = $data[$primary_field];

		return $data;
	}


	/**
	 * API: 根据Name读取一个数据结构
	 * @param  [type] $schema_id [description]
	 * @return [type]            [description]
	 */
	function getSchemaByName( $schema_name, $allow_null=true ) {

		$table_name = $this->getDB('master')->real_escape_string( $this->_table['schema']);
		$primary_field = $this->_schema_table['primary']['COLUMN_NAME'];

		$sql = $this->prepare("SELECT * from `$table_name` WHERE `_spt_name`=?s AND `_spt_is_deleted` ='0'  LIMIT 1", $schema_name);
		$data = $this->getLine( $sql, 'slave' );

		if ( $data == null ) {
			if ($allow_null) return null;
			throw new Exception("$schema_name 不存在 (SQL=$sql) ");
		}

		$data['_spt_schema_json'] = json_decode($data['_spt_schema_json'], true);
		$data['_spt_schema_json']  =  ( $data['_spt_schema_json']  == null )? array(): $data['_spt_schema_json'];
		$data['_id'] = $data[$primary_field];
		return $data;
	}

	/**
	 * API: 根据ID更新一个数据结构的扩展信息
	 * @param  [type] $schema_id [description]
	 * @param  [type] $data      [description]
	 * @return [type]            [description]
	 */
	function updateSchemaData( $schema_id, $data ) {
		$table = $this->_table;
		$this->_filter( $data, $this->_schema_table );
		
		// 过滤系统相关字段
		foreach ($data as $key => $value) {
			if ( preg_match('/_spt_(.+)/', $key, $match) ){
				unset($data[$key]);
			}
		}
		// echo "<pre>\n";
		// print_r($data);
		$primary_field = $this->_schema_table['primary']['COLUMN_NAME'];
		$data[$primary_field] = $schema_id;
		$this->_update( $table['schema'], $data,  $this->_schema_table );
		return $schema_id;
	}


	/**
	 * API: 数据表查询接口
	 * @param  Array|String  $options 查询条件  array('f1'=>'6', '@order'=>'order by id desc', '@limit'=>'3,5') | "f1='6' order by id desc limit 3,5 "
	 * @param  integer $page    当前页码
	 * @param  integer $perpage 每页显示几条记录 （默认为 20 ）
	 * @param  integer $maxrows 最多返回几条记录 （ 默认为 0 表示无限 ）
	 * @return cblObjectList 返回List对象
	 */
	function querySchema(  $options, $page=null,  $perpage=20, $maxrows=0  ){

		// 查询条件
		$where = " 1 ";
		$limit = null;
		$order = "";
		$table = $this->_table['schema'];
		$primary_field = $this->_schema_table['primary']['COLUMN_NAME'];

		if ( is_array($options) ) {

			if ( !isset($options['_spt_is_deleted'])) {
				$options['_spt_is_deleted'] = "0";
			}

			// 处理LIMIT语法
			if ( isset( $options['@limit'] ) ) {
				$limit = $options['@limit'];
			}
			if ( isset( $options['@order'] ) ) {
				$order = $options['@order'];
			}
			$this->_filter( $options, $this->_schema_table );

			$filed_list_arr = array();
			$filed_value = array();
			foreach ($options as $k => $v) {
				array_push($filed_list_arr, "`$k` =?s ");
				array_push( $filed_value, $v );
			}
			$where = implode(' AND ', $filed_list_arr);
		} else if ( is_string($options) ) {

			// 处理LIMIT语法
			if( preg_match("/([Ll]{1}[Ii]{1}[Mm]{1}[Ii]{1}[Tt]{1}[ ]+([0-9]+)[,]*([0-9]*))[ ]*/", $options, $match ) ) {

				$limit_str = $match[0];
				$offset = ( is_numeric($match[3]) ) ? $match[2] : 0;
				$rows = ( is_numeric($match[3]) ) ? $match[3] : $match[2];
				$limit = "$offset,$rows";
				$options = str_replace($limit_str, '', $options );
			}

			$where = $options;
		}

		$record_total = null;
		$from = 0;


		$items = new Items();

		// 如果设置页号，则计算分页
		$record_limit =( $limit != null) ? "LIMIT $limit" : "";

		if ( $page !== null && is_numeric($page) ) {
			

			$sql_record_total = $this->prepare( "SELECT count($primary_field) as cnt FROM `{$table}` WHERE $where", $filed_value );
			$record_total = $this->getVar($sql_record_total);

			if ( ($maxrows > 0 ) &&  ($record_total > $maxrows) ) {
				$record_total = $maxrows;
			}

			
			$record_total = intval($record_total);

			// 计算分页
			$record_limit = $items->pagination( $page, $perpage, $record_total );
		}

		// 查询数据
		$sql = $this->prepare( "SELECT * FROM `{$table}` WHERE $where $order $record_limit", $filed_value );
		$rows = $this->getData( $sql );

		if ( $rows == null ) { // 记录不存在
			return $items;
		}

		foreach ($rows as $row ) {

			$row['_spt_schema_json'] = json_decode($row['_spt_schema_json'], true);
			$row['_spt_schema_json']  =  ( $row['_spt_schema_json']  == null )? array(): $row['_spt_schema_json'];
			$row['_id'] = $row[$primary_field];

			// ？过滤数据 
			/* foreach ($row as $k => $v) {
				if ( preg_match('/_spt_(.+)/', $k, $match) ){
					unset($row[$k]);
				}
			} */
			$item = new Item( $row );
			$items->push( $item );
		}

		return $items;
	}



	/**
	 * API: 根据ID更新一个数据结构
	 * @param  [type] $schema_id [description]
	 * @param  [type] $data      [description]
	 * @return $schema_id
	 */
	function updateSchema( $schema_id, $data ) {
		$table = $this->_table;
		$this->_filter( $data, $this->_schema_table );
		$primary_field = $this->_schema_table['primary']['COLUMN_NAME'];
		$data[$primary_field] = $schema_id;
		$data['_spt_schema_json'] = json_encode($data['_spt_schema_json']);
		$this->_update( $table['schema'], $data,  $this->_schema_table );
		return $schema_id;
	}



	/**
	 * API: 回滚操作
	 * @return [type] [description]
	 */
	function rollbackSchema( $schema_id ) {
		if (isset($this->_schemaBackup[$schema_id])) {
			$this->updateSchema( $schema_id, $this->_schemaBackup[$schema_id] );
		}
		return $schema_id;
	}
	

	/**
	 * API: 删除一个数据结构记录
	 * @param  [type]  $name             [description]
	 * @param  boolean $allow_not_exists [description]
	 * @return [type]                    [description]
	 */
	function deleteSchema( $schema_id, $allow_not_exists=false ) {
		$affected_rows = $this->_delete( $this->_table['schema'], $schema_id,  $this->_schema_table );
		if ( !$allow_not_exists  && $affected_rows == 0) {
			// var_dump("$affect_rows");
			throw new Exception("$schema_id maybe not exists! nothing done!");
		}
		return $schema_id;
	}


	/**
	 * API: 删除一个数据结构记录，同步删除数据
	 * @param  [type]  $schema_id [description]
	 * @param  boolean $mark_only [description]
	 * @return [type]             [description]
	 */
	function dropSchema( $schema_id, $mark_only=true ) {
		$table = $this->_table;
		$primary_field = $this->_schema_table['primary']['COLUMN_NAME'];

		if ( $mark_only == true ) {
			$affected_rows = 0;
			$sqlSchema = $this->prepare("UPDATE {$table['schema']} SET `_spt_is_deleted`='1', _spt_name=CONCAT('_DEL_', _spt_id ,'_', _spt_name) WHERE  `$primary_field` = ?s LIMIT 1 ", $schema_id);
			$sqlData =  $this->prepare("UPDATE {$table['data']} SET `_spt_is_deleted`='1' WHERE  `_spt_schema_id` = ?s", $schema_id);
			$this->run_sql($sqlSchema, 'master');
			$affected_rows = $affected_rows + $this->affected_rows();
			$this->run_sql($sqlData, 'master');
			$affected_rows = $affected_rows + $this->affected_rows();
			return $affected_rows;

		} else {

			$affected_rows = 0;
			$sqlSchema = $this->prepare("DELETE {$table['schema']}  WHERE  `$primary_field` = ?s LIMIT 1 ", $schema_id);
			$sqlData =  $this->prepare("DELETE {$table['data']}  WHERE  `_spt_schema_id` = ?s", $schema_id );
			$this->run_sql($sqlSchema, 'master');
			$affected_rows = $affected_rows + $this->affected_rows();
			$this->run_sql($sqlData, 'master');
			$affected_rows = $affected_rows + $this->affected_rows();
			return $affected_rows;
		}
	}




	/**
	 * API: 添加一个字段
	 * @param [type] $schema_id [description]
	 * @param [type] $name      [description]
	 * @param [type] $value     [description]
	 */
	function addField( $schema_id, $name, $value ) {

		$data = $this->getSchema( $schema_id, false );
		if ( isset($data['_spt_schema_json'][$name]) ) {
			throw new Exception("$name is exists! please run update() or replace() method!");
		}

		//+ 旧数据备份用于回滚
		$this->_schemaBackup[$schema_id] = $data;

		//+ 版本号
		$value['_version'] = 1;
		$data['_spt_schema_json'][$name] = $value;
		$data['_spt_schema_revision'] = "increase";
		$data['_spt_update_at'] = "now";
		return  $this->updateSchema( $schema_id, $data );
	}

	/**
	 * API: 读取一个字段
	 * @param  [type] $schema_id [description]
	 * @param  [type] $name      [description]
	 * @param  [type] $value     [description]
	 * @return [type]            [description]
	 */
	function getField( $schema_id, $name ) {

		$data = $this->getSchema( $schema_id, false );
		if ( !isset($data['_spt_schema_json'][$name]) ) {
			throw new Exception("$name not exists!");
		}

		return $data['_spt_schema_json'][$name];
	}



	/**
	 * API: 更新一个字段
	 * @param  [type] $schema_id [description]
	 * @param  [type] $name      [description]
	 * @param  [type] $value     [description]
	 * @return [type]            [description]
	 */
	function alterField( $schema_id, $name, $value ) {

		$data = $this->getSchema( $schema_id, false );
		if ( !isset($data['_spt_schema_json'][$name]) ) {
			throw new Exception("$name not exists! please run createField() or putField() method!");
		}

		$oldValue = $data['_spt_schema_json'][$name];unset($oldValue['_version']);
		if ( json_encode($oldValue) == json_encode($value)) {
			throw new Exception("$name not change, nothing done! ");
		}


		//+ 旧数据备份用于回滚
		$this->_schemaBackup[$schema_id] = $data;

		// 更新字段版本号
		$value['_version'] = intval($data['_spt_schema_json'][$name]['_version']) + 1;
		$data['_spt_schema_json'][$name] = $value;
		$data['_spt_schema_revision'] = "increase";
		$data['_spt_update_at'] = "now";
		return  $this->updateSchema( $schema_id, $data );
	}


	/**
	 * API: 替换一个字段（如不存在则创建, 如果已存在则忽略 ）
	 * @param  [type] $schema_id [description]
	 * @param  [type] $name      [description]
	 * @param  [type] $value     [description]
	 * @return [type]            [description]
	 */
	function putField( $schema_id, $name, $value ) {

		$data = $this->getSchema( $schema_id, false );
		$oldValue = null;
		if ( isset($data['_spt_schema_json'][$name]) ) {
			$oldValue = $data['_spt_schema_json'][$name];unset($oldValue['_version']);
		}

		if ( json_encode($oldValue) == json_encode($value)) {
			return array('errno'=>0, 'error'=>'$name not change, nothing done!', 'schema_id'=>$schema_id );
		}

		//+ 旧数据备份用于回滚
		$this->_schemaBackup[$schema_id] = $data;

		// 更新字段版本号
		$value['_version'] = isset($data['_spt_schema_json'][$name]['_version']) ? intval($data['_spt_schema_json'][$name]['_version']) + 1 : 1;
		$data['_spt_schema_json'][$name] = $value;
		$data['_spt_schema_revision'] = "increase";
		$data['_spt_update_at'] = "now";
		return  $this->updateSchema( $schema_id, $data );
	}

	
	/**
	 * API: 删除一个字段
	 * @param  [type]  $schema_id        [description]
	 * @param  [type]  $name             [description]
	 * @param  boolean $allow_not_exists 不存在时是否抛出异常，默认 false, 如果不存在，抛出异常 
	 * @return [type]                    [description]
	 */
	function dropField( $schema_id, $name, $allow_not_exists=false ) {

		$data = $this->getSchema( $schema_id, false );

		// 如果Field不存在
		if ( !isset($data['_spt_schema_json'][$name]) ) {
			if ($allow_not_exists) {
				return array('errno'=>0, 'error'=>'$name not exists! no need drop!', 'schema_id'=>$schema_id );;
			} else {
				throw new Exception("$name not exists! no need drop!");
			}
		}

		unset($data['_spt_schema_json'][$name]);
		$data['_spt_schema_revision'] = "increase";
		$data['_spt_update_at'] = "now";
		return  $this->updateSchema( $schema_id, $data );
	}



	//数据插入
	// ===== 数据操作 API

	function getDataByID( $id ) {
		$row = $this->_getDataByID( $id );
		unset($row['_spt_id']);
		unset($row['_spt_data_json']);
		unset($row['_spt_schema_id']);
		unset($row['_spt_create_at']);
		unset($row['_spt_update_at']);
		unset($row['_spt_is_deleted']);
		return $row;
	}

	function getTableNextID() {
		return $this->_getTableNextID();
	}

	function createData( $data, $sheet ) {
		$sheet_id = $sheet['_id'];
		$table_name = $this->_table['data'];
		$data_json = $this->_filter_data_json( $data, $sheet );

		$data['_spt_data_json'] = json_encode( $data_json );
		$data['_spt_schema_id'] = $sheet_id;
		$this->_filter( $data, $this->_data_table );
		return $this->_create($table_name, $data, $this->_data_table );
	}

	function deleteData( $id, $mark_only=false ) {
		$table_name = $this->_table['data'];
		if ( $mark_only === false ) {
			$rows = $this->_delete( $table_name, $id, $this->_data_table );
			if ( $rows > 0 ) {
				return true;
			}

		} else{
			$this->_update($table_name, ['_spt_id'=>$id,'_spt_is_deleted'=>1], $this->_data_table );
			return true;
		}

		return false;
	}

	function updateData( $id, $data , $sheet ) {

		if ( !is_array($data) ) {
			throw new Exception("data 不是数组", 500);
		}

		$sheet_id = $sheet['_id'];
		$table_name = $this->_table['data'];
		$data_old = $this->_getDataByID($id);
		$data = array_merge($data_old, $data);
		$data_json = $this->_filter_data_json( $data, $sheet );
		$data['_spt_data_json'] = json_encode( $data_json );
		$data['_spt_update_at'] = 'now';
		$data['_spt_schema_id'] = $sheet_id;
		$this->_filter( $data, $this->_data_table );
		$this->_update($table_name, $data, $this->_data_table );
		return $id;
	}


	// ================================================  以下MySQL特有

	private function _filter_data_json( $data, $sheet ) {
		$data_json = [];
		foreach ($sheet['columns'] as $field => $type ) {
			if ( isset($data[$field])) {
				// echo "\n\t TField: $field  " . var_export( $data[$field], true ) . "\n";
				$data_json[$field] = $data[$field];
			} else {
				$default = $type->data('default');
				if ( $default !== null ) {
					$data_json[$field] = $default;
				}
				// echo "\n\t field: $field THE DEFAULT :  " . var_export($default, true). "\n";
			}
		}

		return $data_json;
	}

	private function _filter( & $data, $scheme_table ) {
		unset($scheme_table['primary']);
		foreach ( $data as $field=>$value ) {
			if ( !isset($scheme_table[$field]) ) {
				unset($data[$field]);
			}
		}
	}


	private function _getDataByID( $id ) {

		$table_name = $this->_table['data'];
		$primary_key = $this->_data_table['primary']['COLUMN_NAME'];
		$sql = $this->prepare("SELECT * from `$table_name` WHERE `$primary_key`=?s AND `_spt_is_deleted`='0' LIMIT 1", array($id) );
		$row = $this->getLine($sql);
		if ( $row == null ) {
			throw new Exception("id=$id data does not exists!");
		}

		$data = json_decode($row['_spt_data_json'], true );
		if( json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception("Storage: updateData JSON Parser Error( " . json_last_error_msg() . ')'. $_spt_data_json);
		}
		
		$row = array_merge($data, $row);

		// FixData
		$row['_id'] = $row[$primary_key];
		$row['_sheet_id'] =  $row['_spt_schema_id'];
		$row['_create_at'] = $row['_spt_create_at'];
		$row['_update_at'] = $row['_spt_update_at'];
		$row['_is_deleted'] = $row['_spt_is_deleted'];
		return $row;
	}


	private function _getTableNextID() {
		$table_name = $this->_table['data'];
		$sql = $this->prepare("SELECT AUTO_INCREMENT as last_id FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME=?s", array($table_name) );
		$last_id = $this->getVar($sql);
		if ( $last_id == null ) {
			throw new Exception("no data!");
		}
		return intval($last_id);
	}	


	private function _create( $table_name, $data, $scheme_table ) {
		
		$table_name = $this->getDB('master')->real_escape_string($table_name);

		// 数据入库
		$filed_list_arr = array();
		$filed_value = array();
		foreach ($data as $k => $v) {
			array_push($filed_list_arr, "`$k` =?s ");
			array_push( $filed_value, $v );
		}

		// 补全数据表相关信息
		if ( !isset($data['_spt_create_at']) && isset($scheme_table['_spt_create_at']) ) {
			array_push($filed_list_arr, "`_spt_create_at` = NOW() ");
		}
		if ( !isset($data['_spt_is_deleted']) && isset($scheme_table['_spt_is_deleted']) ) {
			array_push($filed_list_arr, "`_spt_is_deleted` = 0 ");
		}

		if ( !isset($data['_spt_schema_version']) && isset($scheme_table['_spt_schema_version']) ) {
			array_push($filed_list_arr, "`_spt_schema_version`=1 ");
		}

		if ( !isset($data['_spt_schema_revision']) && isset($scheme_table['_spt_schema_revision']) ) {
			array_push($filed_list_arr, "`_spt_schema_revision`=1 ");
		}

		// Field List
		$filed_list = implode(',', $filed_list_arr);
		$sql = $this->prepare( "INSERT INTO `$table_name` SET $filed_list", $filed_value);
		$this->run_sql( $sql, 'master');

		return $this->last_id('master');
	}


	private function _update( $table_name, $data, $scheme_table, $where=null ) {
		
		$primary_key = $scheme_table['primary']['COLUMN_NAME'];
		$table_name = $this->getDB('master')->real_escape_string($table_name);

		// 未指定查询条件，但在参数中指定了 id
		if ( $where == null && isset($data[$primary_key]) ) {
			$where  = $this->prepare("WHERE `$primary_key` = ?s LIMIT 1", array($data[$primary_key]));
		}
		if ( $where == null ) {
			throw new Exception("未知数据记录 ( where: not set, $primary_key : not set )");	
		}

		// 数据入库
		$filed_list_arr = array();
		$filed_value = array();

		// 追加记录更新信息
		if ( $data['_spt_update_at'] == 'now' && isset($scheme_table['_spt_update_at']) ) {
			unset($data['_spt_update_at']);
			array_push($filed_list_arr, "`_spt_update_at` = NOW() ");
		}

		// 追加版本信息
		if ( $data['_spt_schema_revision'] == 'increase'  ) {
			unset( $data['_spt_schema_revision'] );
			array_push($filed_list_arr, "`_spt_schema_revision` =_spt_schema_revision+1 ");
		}

		// 生成数据结构
		foreach ($data as $k => $v) {
			array_push($filed_list_arr, "`$k` =?s ");
			array_push( $filed_value, $v );
		}

		$filed_list = implode(',', $filed_list_arr);
		$sql = $this->prepare( "UPDATE `{$table_name}` SET $filed_list $where", $filed_value);
		$this->run_sql($sql, 'master');
		return $this;
	}


	private function _delete( $table_name, $primary, $scheme_table ) {

		$primary_key = $scheme_table['primary']['COLUMN_NAME'];
		$table_name = $this->getDB('master')->real_escape_string($table_name);
		$sql  = $this->prepare("DELETE FROM `{$table_name}` WHERE `$primary_key` = ?s LIMIT 1", array($primary));
		$this->run_sql($sql, 'master');
		return $this->affected_rows('master');
	}




	/**
	 * 检查数据表结构，如不存在则抛出异常
	 * 
	 * @param  [type] $table_name [description]
	 * @param  [type] $struct     [description]
	 * @param  [type] $return     [description]
	 * @return [type]             [description]
	 */
	private function _table_check( $table_name, $struct, & $return )  {

		$table_name = $this->getDB('slave')->real_escape_string($table_name);
		$sql = "SELECT *  FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table_name' ";
		$data = $this->getData( $sql, 'slave');
		
		$primary_key = '_spt_id';
		foreach ($data as $row ) {
			$field = $row['COLUMN_NAME'];
			$data[$field] = $row;
			if ($row['COLUMN_KEY'] == 'PRI') {
				$primary_key = $field;
				$return['primary'] = $row;
			} else {
				$return[$field] = $row;
			}
		}

		if ( $primary_key != '_spt_id' ) {
			unset( $struct['_spt_id']);
		}

		$errlist = array();
		$haserr = false;
		foreach ($struct as $field => $info) {
			if ( !isset( $data[$field] ) ) {
				array_push($errlist, "$table_name.$field 不存在");
				$haserr = true;
			} else {
				$return[$field] = $data[$field];
			}
		}

		if ( $haserr ) {
			$errstr = implode("\n", $errlist );
			throw new Exception($errstr);
		}

		// echo "<pre>\n";
		// echo "Table-Check $table_name \n";
		// print_r($return);
		// echo "</pre>\n\n";

		return true;
	}



	/**
	 * 检查数据表是否存在
	 * @param  [type] $table_name 数据表名
	 * @return [type] 成功返回 true , 失败返回 false
	 */
	private function _table_exists(  $table_name ) {
		$table_name = $this->getDB('slave')->real_escape_string($table_name);
		$sql = "DESC `$table_name` ";
		$data = $this->getData( $sql, 'slave', 1146 );
		if ( isset($data['errno']) && $data['errno'] == 1146 ) {
			return  false;
		}
		return true;
	}


	/**
	 * 自动创建数据表  
	 * @param  [type] $table_name 数据表名
	 * @param  [type] $struct     表结构
	 * @return [type]             [description]
	 */
	private function _table_create( $table_name, $struct ) {

		$table_name = $this->getDB('master')->real_escape_string($table_name);
		$sql = "CREATE TABLE  `$table_name` ( ";

		$fieldr = array();
		$indexr = array();
		foreach ($struct as $field=>$info ) {

			$type_str = (isset($info['length']))?"{$info['type']}({$info['length']})":$info['type'];
			$default_str = 	(isset($info['default']))?"DEFAULT '{$info['default']}'":"";
			$null_str = (isset($info['allow_null']))?"DEFAULT NULL":"NOT NULL";
			$auto_str  = (isset($info['auto']))?"AUTO_INCREMENT":"";

			array_push($fieldr, "\n`$field` $type_str $null_str $auto_str $default_str");

			if ( isset($info['index']) ) {
				$info['index'] = (is_array($info['index']))?$info['index']:array($info['index']);
				
				foreach ($info['index'] as $index ) {
					array_push($indexr, "\n{$index} `$field` (`$field`)");
				}
			}
		}

		$statementr = array_merge($fieldr, $indexr);

		$sql = $sql . implode(',', $statementr );
		$sql = $sql . ') ENGINE=InnoDB DEFAULT CHARSET=utf8';

		$this->run_sql( $sql );
		return true;
	}



	private function options( $options ) {
		$this->_opts = $options;
		$this->_opts['master'] = (is_array($this->_opts['master'])) ? $this->_opts['master']: array($this->_opts['master']);
		$this->_opts['slave'] = (is_array($this->_opts['slave'])) ? $this->_opts['slave']: array($this->_opts['slave']);
	}


	///===== DB OP 

	private function run_sql( $sql , $type = 'master', $ignore = array() ) {
		$result = $this->getDB($type)->query($sql);
		if ($result === false) {
			if ( in_array($this->dbs[$type]->errno, $ignore) ) {
				return array('errno'=> $this->dbs[$type]->errno , 'error'=> $this->dbs[$type]->error );
			}

		    throw new Exception('Query Error (' . $this->dbs[$type]->error . ' SQL='. $sql .') '
		            . $this->dbs[$type]->errno );
		}
		return $result;
	}


	private function last_id( $type = 'master' ) {
		return $this->getVar( "SELECT LAST_INSERT_ID() " , $type );
	}

	private function affected_rows( $type = 'master' ) {
		return $this->getDB($type)->affected_rows;;
	}

	private function getData( $sql, $type='slave', $ignore = array() ) {

		$ignore = (is_array($ignore)) ? $ignore : array($ignore);

		$data = array();
		$result = $this->getDB($type)->query($sql);
		// 检测数据库连接
		if ($result === false ) {
			if ( in_array($this->dbs[$type]->errno, $ignore) ) {
				return array('errno'=> $this->dbs[$type]->errno , 'error'=> $this->dbs[$type]->error );
			}

		    throw new Exception('Query Error (' . $this->dbs[$type]->error . ' SQL='. $sql .') '
		            . $this->dbs[$type]->errno );
		}

		while ($row = $result->fetch_array(MYSQL_ASSOC) ) {
			array_push($data, $row);
		}

		$result->free();
		return $data;
	}

	private function getLine( $sql , $type='slave' , $ignore = array()) {
		$data = $this->getData( $sql , $type, $ignore );
		return @reset($data);
	}

	private function getVar( $sql , $type='slave', $ignore = array() ) {
		$data = $this->getLine( $sql , $type, $ignore );
		if ( $data == null ) return $data;

		return $data[ @reset(@array_keys( $data )) ];
	}



	// $sql = "SELECT * FROM `user` WHERE `name` = ?s AND `id` = ?i LIMIT 1 "
	private function prepare( $sql , $array )
	{
		
		if(!is_array($array)) $array = array($array);

		foreach( $array as $k=>$v ) {
			$array[$k] = $this->getDB('master')->real_escape_string($v);
		}
		
		$reg = '/\?([is])/i';
		$sql = preg_replace_callback( $reg , '\Tuanduimao\Supertable\Storage\Mysql::prepair_string' , $sql  );
		$count = count( $array );
		for( $i = 0 ; $i < $count; $i++ )
		{
			$str[] = '$array[' .$i . ']';	
		}
		
		$statement = '$sql = sprintf( $sql , ' . join( ',' , $str ) . ' );';
		eval( $statement );
		return $sql;
		
	}

	static function prepair_string( $matches ) {
			if( $matches[1] == 's' ) return "'%s'";
			if( $matches[1] == 'i' ) return "'%d'";	
	}



	private function getDB( $type='master') {


		if ( isset($this->dbs[$type]) && is_a($this->dbs[$type], 'mysqli') ) {
			return $this->dbs[$type];
		}


		// 随机选择一个
		$idx = intval(rand(0, count($this->_opts[$type])-1));
		$conf = $this->_opts[$type][$idx];

			$conf['host'] = (isset( $conf['host']) ) ?  $conf['host'] : null;
			$conf['user'] = (isset( $conf['user']) ) ?  $conf['user'] : null;
			$conf['pass'] = (isset( $conf['pass']) ) ?  $conf['pass'] : null;
			$conf['db_name'] = (isset( $conf['db_name']) ) ?  $conf['db_name'] : null;
			$conf['socket'] = (isset( $conf['socket']) ) ?  $conf['socket'] : null;
		
		try {
			$this->dbs[$type] = new Mysqli( $conf['host'], $conf['user'], $conf['pass'], $this->_opts['db_name'],  $conf['socket'] );
		} catch( Exception $e ) {
			throw new Exception($e->getMessage(), $e->getCode() );
		}

		// 检测数据库连接
		if ($this->dbs[$type]->connect_error) {
		    throw new Exception('Connect Error (' . $this->dbs[$type]->connect_errno . ') '
		            . $this->dbs[$type]->connect_error);
		}
		$this->dbs[$type]->query("SET NAMES utf8");
		return $this->dbs[$type];
	}

}