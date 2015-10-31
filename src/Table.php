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
	protected $_support_types = array();

	protected $_attrs = array();
	protected $_attrs_ext = array();

	function __construct( $conf = null ) {
		
		if ( is_array($conf) ) {
			$this->_conf = $conf;
		}
	}

	// 表格相关操作
	public function selectSheet( $sheet_id ) {
		$this->_sheet_id = $sheet_id;
		return $this;
	}

	// 读取所有表格
	public function getSheetList() {
	}

	// 创建一个表格
	public function createSheet( $data = array() ) {
		$sheet_id = $this->_schema->create( $data );
		return $this->selectSheet( $sheet_id );
	}


	// 删除一个表格
	public function deleteSheet( $removedata = false ) {

	}

	// 数据表结构相关操作
	
	// 添加一列
	public function addColumn( $column_name, Type $type ) {

		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$this->_schema->addField( $this->_sheet_id, $column_name, $type );
		return $this->selectSheet( $this->_sheet_id );
	}


	// 更新一列
	public function alterColumn( $column_name, Type $type ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$this->_schema->alterField( $this->_sheet_id, $column_name, $type );
		return $this->selectSheet( $this->_sheet_id );
	}


	// 替换一列 （ 如不存在则创建 )
	public function replaceColumn( $column_name, Type $type ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$this->_schema->replaceField( $this->_sheet_id, $column_name, $type );
		return $this->selectSheet( $this->_sheet_id );
	}

	// 删除一列
	public function dropColumn( $column_name, $allow_not_exists=false ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
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