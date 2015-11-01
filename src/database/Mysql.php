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

Namespace Tuanduimao\Supertable\Database;
use \Mysqli as Mysqli;
use \Exception as Exception;

class Mysql {
	
	private $dbs = array();
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
	function checktable() {
		 
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

		$sql = $this->prepare("SELECT * from `$table_name` WHERE `$primary_field`=?s LIMIT 1", $schema_id);
		$data = $this->getLine( $sql, 'slave' );

		if ( $data == null ) {
			if ($allow_null) return null;
			throw new Exception("$schema_id 不存在 (SQL=$sql) ");
		}
		$data['_spt_schema_json'] = json_decode($data['_spt_schema_json'], true);
		$data['_spt_schema_json']  =  ( $data['_spt_schema_json']  == null )? array(): $data['_spt_schema_json'];
		$data['primary'] = $data[$primary_field];

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

		$sql = $this->prepare("SELECT * from `$table_name` WHERE `_spt_name`=?s LIMIT 1", $schema_name);
		$data = $this->getLine( $sql, 'slave' );

		if ( $data == null ) {
			if ($allow_null) return null;
			throw new Exception("$schema_id 不存在 (SQL=$sql) ");
		}

		$data['_spt_schema_json'] = json_decode($data['_spt_schema_json'], true);
		$data['_spt_schema_json']  =  ( $data['_spt_schema_json']  == null )? array(): $data['_spt_schema_json'];
		$data['primary'] = $data[$primary_field];
		return $data;
	}


	/**
	 * API: 根据ID更新一个数据结构
	 * @param  [type] $schema_id [description]
	 * @param  [type] $data      [description]
	 * @return [type]            [description]
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
		$data['_spt_schema_json'][$name] = $value;
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
			throw new Exception("$name not exists! please run create() or replace() method!");
		}

		$data['_spt_schema_json'][$name] = $value;
		return  $this->updateSchema( $schema_id, $data );
	}


	/**
	 * API: 替换一个字段（如不存在则创建）
	 * @param  [type] $schema_id [description]
	 * @param  [type] $name      [description]
	 * @param  [type] $value     [description]
	 * @return [type]            [description]
	 */
	function replaceField( $schema_id, $name, $value ) {

		$data = $this->getSchema( $schema_id, false );
		$data['_spt_schema_json'][$name] = $value;
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
		if ( !isset($data['_spt_schema_json'][$name]) && !$allow_not_exists ) {
			throw new Exception("$name not exists! no need drop!");
		} 
		
		unset($data['_spt_schema_json'][$name]);
		return  $this->updateSchema( $schema_id, $data );
	}



	//数据插入
	


	// ================================================  以下MySQL特有

	private function _filter( & $data, $scheme_table ) {
		unset($scheme_table['primary']);
		foreach ( $data as $field=>$value ) {
			if ( !isset($scheme_table[$field]) ) {
				unset($data[$field]);
			}
		}
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
		foreach ($data as $k => $v) {
			array_push($filed_list_arr, "`$k` =?s ");
			array_push( $filed_value, $v );
		}
		// 补全数据表相关信息
		if ( !isset($data['_spt_update_at']) && isset($scheme_table['_spt_update_at']) ) {
			array_push($filed_list_arr, "`_spt_update_at` = NOW() ");
		}

		$filed_list = implode(',', $filed_list_arr);
		$sql = $this->prepare( "UPDATE `{$table_name}` SET $filed_list $where", $filed_value);
		$this->run_sql($sql);

		return $this;
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
		if ( $data['errno'] == 1146 ) {
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

		return $data[ @reset(@array_keys( $data )) ];
	}



	// $sql = "SELECT * FROM `user` WHERE `name` = ?s AND `id` = ?i LIMIT 1 "
	private function prepare( $sql , $array )
	{
		if(!is_array($array)) $array = array($array);

		foreach( $array as $k=>$v )
			$array[$k] = s($v );
		
		$reg = '/\?([is])/i';
		$sql = preg_replace_callback( $reg , 'prepair_string' , $sql  );
		$count = count( $array );
		for( $i = 0 ; $i < $count; $i++ )
		{
			$str[] = '$array[' .$i . ']';	
		}
		
		$statement = '$sql = sprintf( $sql , ' . join( ',' , $str ) . ' );';
		eval( $statement );
		return $sql;
		
	}

	private function prepair_string( $matches )
	{
		if( $matches[1] == 's' ) return "'%s'";
		if( $matches[1] == 'i' ) return "'%d'";	
	}

	private function getDB( $type='master') {

		if ( is_a($this->dbs[$type], 'mysqli') ) {
			return $this->dbs[$type];
		}

		// 随机选择一个
		$idx = intval(rand(0, count($conf)));
		$conf = $this->_opts[$type][$idx];
		$this->dbs[$type] = new Mysqli( $conf['host'], $conf['user'], $conf['pass'], $this->_opts['db_name'],  $conf['socket'] );
		
		// 检测数据库连接
		if ($this->dbs[$type]->connect_error) {
		    throw new Exception('Connect Error (' . $this->dbs[$type]->connect_errno . ') '
		            . $this->dbs[$type]->connect_error);
		}

		return $this->dbs[$type];
	}

}