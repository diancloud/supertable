<?php
/**
 * SuperTable 基类
 *
 * CLASS 
 *
 * 	   SuperTable 
 *
 * USEAGE: 
 *
 *     不要直接使用
 * 
 */

namespace Tuanduimao\Supertable;
use Elasticsearch\Client as SEClient;
use \Exception as Exception;
use Tuanduimao\Supertable\Schema;
use Tuanduimao\Supertable\Type;


/**
 * SuperTable
 */
class Table {
	
	protected $_conf = array();

	protected $_schema = null;
	protected $_search = null;

	protected $_table  = array('schema' => null, 'data'=>null );
	protected $_index  = array('index' => null, 'type'=>null );

	protected $_sheet_id = null;
	protected $_sheet_plug = null;
	protected $_sheet = null;
	protected $_support_types = array();

	protected $_attrs = array();
	protected $_attrs_ext = array();

	function __construct( $conf = null ) {
		
		if ( is_array($conf) ) {
			$this->_conf = $conf;
		}
	}

	// === 数据表(Sheet)相关操作 CRUD ==========================
	
	/**
	 * 根据ID/NAME选中一个数据表(Sheet), 如果数据表不存在则创建
	 * @param  [type] $sheet_plug Sheet ID/NAME
	 * @param  array  $data       扩展数据 (如果有自定字段，则填写这些字段的数值)，默认为array()
	 * @return [type]             $this
	 */
	public function selectSheet( $sheet_plug, $data = array() ) {
		if ( $this->getSheet( $sheet_plug, true ) === null ) {
			$name = $sheet_plug;
			if ( is_numeric($sheet_plug) ) {
				$name = null;
			}
			$sheet_id = $this->createSheet( $name, $data, true );
			$this->getSheet( $sheet_id );
		}
		return $this;
	}


	/**
	 * 读取一个数据表 (Sheet)
	 * @param  [type]  $sheet_plug ID或NAME
	 * @param  boolean $allow_null 如果为true, 如果Sheet不存在，返回null。 默认为 false 抛出异常
	 * @return [mix]    如果 $allow_null 为true, 且Sheet不存在，返回null, 返回数据表结构数组。
	 */
	public function getSheet( $sheet_plug, $allow_null=false ) {

		$sheet = array();
		if ( is_numeric($sheet_plug) ) {
			$sheet = $this->_schema->getSheetByID( $sheet_plug, $allow_null);
		} else {
			$sheet = $this->_schema->getSheetByName( $sheet_plug, $allow_null );
		}

		$this->_sheet_id = $sheet['_spt_id'];
		$this->_sheet_plug = $sheet_plug;
		$this->_sheet = $sheet;
		return $this->_sheet;
	}


	/**
	 * 创建一个数据表 (Sheet)
	 * @param  [string]  $name        数据表名，默认为NULL，自动生成 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param  array   $data        扩展数据 (如果有自定字段，则填写这些字段的数值)
	 * @param  boolean $create_only 为true返回刚创建的数据表ID，默认为false，选中新创建的数据表
	 * @return [mix]                $create_only 为true返回刚创建的数据表ID; $create_only 为false，选中新创建的数据表, 返回 $this
	 */
	public function createSheet( $name=null, $data = array(), $create_only=false ) {
		$name = ($name==null) ? $this->_table['data'] . '_'. time() . rand(10000,99999):$name;
		if (!preg_match('/^([a-zA-Z]{1})([a-zA-Z0-9\_])/', $name) ) {
			throw new Exception("数据表名称格式不正确，由字符、数字和下划线组成，且开头必须为字符。(name= $name) ");
		}

		$sheet_id = $this->_schema->createSheet( $name, $data );
		if ( $create_only) {
			return $sheet_id;
		}

		return $this->selectSheet( $sheet_id );
	}




	// 删除一个表格
	public function deleteSheet( $removedata = false ) {
	}


	// 读取所有表格
	public function getSheetList() {
	}


	// === 数据表列结构 (Sheet Column) 相关操作 CRUD ==========================
	
	/**
	 * 为当前数据表添加一列
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type        数据类型 (参考) @see \Tuanduimao\supertable\Type
	 * @return $this
	 */
	public function addColumn( $column_name, Type $type ) {

		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		if (!preg_match('/^([a-zA-Z]{1})([a-zA-Z0-9\_])/', $column_name) ) {
			throw new Exception("列名称格式不正确，由字符、数字和下划线组成，且开头必须为字符。(column_name= $column_name) ");
		}

		$this->_schema->addField( $this->_sheet_id, $column_name, $type );
		return $this->selectSheet( $this->_sheet_id );
	}


	/**
	 * 修改当前数据表 $column_name 列结构
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type        数据类型 (参考) @see \Tuanduimao\supertable\Type
	 * @return $this
	 */
	public function alterColumn( $column_name, Type $type ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		if (!preg_match('/^([a-zA-Z]{1})([a-zA-Z0-9\_])/', $column_name) ) {
			throw new Exception("列名称格式不正确，由字符、数字和下划线组成，且开头必须为字符。(column_name= $column_name) ");
		}


		$this->_schema->alterField( $this->_sheet_id, $column_name, $type );
		return $this->selectSheet( $this->_sheet_id );
	}


	/**
	 * 替换当前数据表 $column_name 列结构（ 如果列不存在则创建)
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type        数据类型 (参考) @see \Tuanduimao\supertable\Type
	 * @return $this
	 */
	public function replaceColumn( $column_name, Type $type ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		if (!preg_match('/^([a-zA-Z]{1})([a-zA-Z0-9\_])/', $column_name) ) {
			throw new Exception("列名称格式不正确，由字符、数字和下划线组成，且开头必须为字符。(column_name= $column_name) ");
		}

		$this->_schema->replaceField( $this->_sheet_id, $column_name, $type );
		return $this->selectSheet( $this->_sheet_id );
	}

	/**
	 * 删除当前数据表 $column_name 列
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type        数据类型 (参考) @see \Tuanduimao\supertable\Type
	 * @return $this
	 */
	public function dropColumn( $column_name, $allow_not_exists=false ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		if (!preg_match('/^([a-zA-Z]{1})([a-zA-Z0-9\_])/', $column_name) ) {
			throw new Exception("列名称格式不正确，由字符、数字和下划线组成，且开头必须为字符。(column_name= $column_name) ");
		}

		$this->_schema->dropField( $this->_sheet_id, $column_name, $allow_not_exists );
		return $this->selectSheet( $this->_sheet_id );
	}
	
	
	// 数据相关操作




	/**
	 * 绑定数据表
	 * @param  [type] $options [description]
	 * @return [type]          [description]
	 */
	protected function bindDB( $options ) {
		
		if ( !isset($options['data']) ) {
			return false;
		}

		// Schema 表
		if ( !isset($options['schema']) ) {
			$options['schema'] = $this->C('database/options/table_prefix') . $options['data'] . '_supertable';
		} else {
			$options['schema'] = $this->C('database/options/table_prefix') . $options['schema'];
		}

		$options['data'] = $this->C('database/options/table_prefix') . $options['data'];
		$this->_table = $options;
		$this->_schema = new Schema( $this->_table , $this->C('database'), $this->C('memcached') );

		return $this;
	}



	protected function bindSE( $options ) {
		$this->_index = $options;
		$this->_search = new SEClient( $this->C('search/options') );
		return $this;
	}

	protected function C($name) {

		// 从GLOBALS中载入
		$namer = explode('/', $name);
		if ( is_array($this->_conf) ) {
			$ret = $this->_conf;
			foreach ($namer as $n ) {
				if ( !isset($ret[$n]) ) {
					return false;
				}
				$ret = $ret[$n];
			}
			return $ret;
		}

		return false;
	}


	//===== 属性操作
	public function get() {
	}

	public function set( $name, $value ) {
	}

	public function setExt( $name, $value ) {

	}

	//===== 数据 CRUD
	public function create( $data ) {
	}

	public function update( $data ) {
	}

	public function delete( $data ) {
	}

	public function getLine( $data ) {
	}

	public function getData( $options ) {
	}


}