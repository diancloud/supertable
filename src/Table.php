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
use \Exception as Exception;
use Tuanduimao\Supertable\Schema;
use Tuanduimao\Supertable\Type;


/**
 * SuperTable
 */
class Table {
	
	private $_stor = array();
	private $_type = null;
	private $_mc = null;


	public $errors = array();
	protected $_conf = array();

	protected $_schema = null;
	protected $_search = null;

	protected $_bucket  = array('schema' => null, 'data'=>null );
	protected $_index  = array('index' => null, 'type'=>null );

	protected $_sheet_id = null;
	protected $_sheet_plug = null;
	protected $_sheet = null;
	protected $_support_types = array();

	protected $_attrs = array();
	protected $_attrs_ext = array();

	function __construct( $conf = null ) {
		
		if ($conf !== null && !is_array($conf) ) {
			throw new Exception("Please Check Configure (conf=".var_export($conf,true).")");
		}

		if ( is_array($conf) ) {
			$this->_conf = $conf;
			$this->_type = $this->type();
		}
	}

	// === 数据表(Sheet)相关操作 CRUD ==========================
	
	public function sheet() {
		return $this->_sheet;
	}

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

		$this->_sheet_id = $sheet['primary'];
		$this->_sheet_plug = $sheet_plug;
		$this->_sheet = $sheet;
		return $this->_sheet;
	}


	/**
	 * 创建一个数据表 (Sheet)
	 * @param  string  $name      数据表名，默认为NULL，自动生成 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param  array   $data        扩展数据 (如果有自定字段，则填写这些字段的数值)
	 * @param  boolean $create_only 为true返回刚创建的数据表ID，默认为false，选中新创建的数据表
	 * @return mix               $create_only 为true返回刚创建的数据表ID; $create_only 为false，选中新创建的数据表, 返回 $this
	 */
	public function createSheet( $name=null, $data = array(), $create_only=false ) {
		$name = ($name==null) ? $this->_bucket['data'] . '_'. time() . rand(10000,99999):$name;
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
	 * 读取当前数据表 $column_name 列结构
	 * @param  [type] $column_name [description]
	 * @return [Type] 返回Type对象
	 */
	public function getColumn( $column_name ) {

		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		if (!preg_match('/^([a-zA-Z]{1})([a-zA-Z0-9\_])/', $column_name) ) {
			throw new Exception("列名称格式不正确，由字符、数字和下划线组成，且开头必须为字符。(column_name= $column_name) ");
		}

		return $this->_schema->getField( $this->_sheet_id, $column_name );
	}


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
	



	// === 数据 (Data) 相关操作 CRUD ==========================
	/**
	 * 在当前的数据表(Sheet)中，插入一行数据
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function create( $data ) {

		// 根据数据结构，检查数据是否合法
		if ( $this->validation( $data ) === false ) {
			return false;
		}

		// 数据入库
		$data_id = $this->_stor->createData( $data );

		$data_index = $data;
		
		// 添加索引
		$this->_search->createData( $this->_sheet['name'], $data_id, $data );
	}


	public function update( $data ) {
	}

	public function delete( $data ) {
	}

	public function getLine( $data ) {
	}

	public function getData( $options ) {
	}


	/**
	 * 校验数据是否合法
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function validation( $data ) {
		
		$this->errors = array();

		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$errflag = false;
		foreach ($this->_sheet['columns'] as $name=>$type ) {
			if ( !$type->validation( $data[$name] ) ) {
				$errflag = true;
				$this->errors = array_merge($type->errors, $this->errors);
			}
		}

		return !$errflag;
	}



	// 类型相关操作
	
	public function type( $name=null, $data=array(), $option=array() ) {
		
		if ( $name == null ) {
			if ( is_a($this->_type, "Tuanduimao\Supertable\Type") ) {
				return $this->_type;
			}
			return (new Type())->setPath( $this->C('path') );
		}
		
		return (new Type())
			 ->setPath( $this->C('path') )
			 ->load( $name, $data, $option )->setPath( $this->C('path') );
	}




	// === 对象初始化 相关操作 ==========================

	/**
	 * 绑定数据存储空间
	 * 
	 * @param  Array  $option 存储空间配置
	 *         		  $option['data'] 存储空间名称 
	 *         		  $option['schema'] 数据结构存储空间名称 (选填)
	 *                           	
	 *         		  EG:  $conf = array(...'storage'=>array('prefix'=>"prefix_") ...)
	 *         		  
	 *         		  	   $this->bindBucket( $option )
	 * 						    ->bindIndex()
	 * 						    ->init();
	 * 						        
	 * 					   $this->selectSheet('customer_boss');
	 * 					   
	 *         		  	   $option = array('data'=>'customer', 'schema'=>"customer_typelist")
	 *         		  	   数据存储空间: prefix_customer ( 存放具体客户数据，如 {name:"张三", mobile:"13611281054"...} )
	 *         		  	   数据结构存储空间: prefix_customer_typelist ( 存放字段结构数据，如 {"customer_boss":{"姓名":"InlineText", "手机号码":"InlineText" ...}} )
	 *         		  	   
	 *         		  	   $option = array('data'=>'customer')  自动创建一张 prefix_customer_supertable 数据表，用来存储数据结构
	 *         		  	   数据存储空间: prefix_customer
	 *         		  	   数据结构存储空间: prefix_customer_supertable
	 *         		  	   
	 *         		  	   
	 * @return Table  $this Table对象
	 * @see  数据存储空间
	 */
	protected function bindBucket( $option ) {
		
		if ( !isset($option['data']) ) {
			throw new Exception("please enter data Table name at least !");
		}

		// Schema 表
		if ( !isset($option['schema']) ) {
			$option['schema'] = $this->C('storage/prefix') . $option['data'] . '_supertable';
		} else {
			$option['schema'] = $this->C('storage/prefix') . $option['schema'];
		}

		$option['data'] = $this->C('storage/prefix') . $option['data'];
		$this->_bucket = $option;
		return $this;
	}


	/**
	 * 绑定索引(搜索引擎)
	 * 
	 * @param  Array    $option 搜索引擎索引和类型配置
	 *         			$option['index'] 索引名称（选填）(相当于关系型数据库的[数据库名称] )
	 *         							 默认为绑定存储空间名称
	 *         							 @see bindBucket  
	 *         							 
	 *         			$option['type']  类型名称前缀（选填） ( 相当于关系型数据的[数据表名称] ) 
	 *         							 类型名称结构: "{$conf['storage']['prefix']}{$option['type']}$sheet_name"
	 *         							 @see selectSheet 
	 *
	 * 					EG:  $conf = array(...'storage'=>array('prefix'=>"prefix_") ...) 
	 * 					
	 * 						 $this->bindBucket( array( 'data'=>'customer', 'schema'=>'customer_type') )
	 * 						      ->bindIndex( $option )
	 * 						      ->init();
	 * 						 $this->selectSheet('customer_boss');
	 * 						
	 * 						 $option = array()
	 * 						 索引名称 Index: 'prefix_customer' ( $this->_bucket['data'] )
	 * 						 类型名称  Type: 'prefix_customer_boss' ( $this->_sheet['name'] )
	 *
	 * 						 $option = array('index'=>'app_customer')
	 * 						 索引名称 Index: 'app_customer'
	 * 						 类型名称  Type: 'prefix_customer_boss' ( $this->_sheet['name'] )
	 * 						 
	 * 						 $option = array('index'=>'app_customer', 'type'=>'cust_')
	 * 						 索引名称 Index: 'app_customer'
	 * 						 类型名称  Type: 'cust_customer_boss' ( "cust_{$this->_sheet['name']}" )
	 *         			
	 * @return Table  $bucket Table对象
	 */
	protected function bindIndex( $option = array() ) {

		$option['index'] = (isset($option['index']))?$option['index']:$this->_bucket['data'];
		$option['type'] = (isset($option['type']))?$option['type']:"";

		$this->_index = $option;
		return $this;
	}


	/**
	 * 系统初始化：( 在 bindBucket 和 bindIndex之后调用 )
	 * 		1）创建数据库对象
	 * 		2) 创建搜索引擎对象
	 * 		3）创建类型对象
	 * 		4) 创建 schema 对象
	 * @return [type] [description]
	 */
	protected function init() {
		$this->_storInit();
		$this->_searchInit();
		$this->type();
		$this->_schema = new Schema( $this->_bucket,  $this->_stor, $this->_search, $this->_type, $this->_mc );
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


	// ====== 以下部分为私有函数

	/**
	 * 连接数据库，并创建数据库对象
	 * @return [type] [description]
	 */
	private function _storInit() {
		$bucket = $this->_bucket;
		$engine = $this->C('storage/engine');
		$class_name = "\\Tuanduimao\\Supertable\\Storage\\{$engine}";
		if ( !class_exists($class_name) ) {
			throw new Exception("$class_name not exists!");
		}
		$this->_stor = new $class_name( $bucket, $this->C('storage/option') );
		return $this;
	}


	/**
	 * 连接索引库，并创建对象
	 * 
	 * @return [type] [description]
	 */
	private function _searchInit() {
		
		if ( count($this->_stor) == 0 ) {
			throw new Exception("Please create storage connection use _storInit() first !");
		}

		$bucket = $this->_bucket;
		$engine = $this->C('search/engine');
		$class_name = "\\Tuanduimao\\Supertable\\Search\\{$engine}";
		if ( !class_exists($class_name) ) {
			throw new Exception("$class_name not exists!");
		}

		$this->_search = new $class_name( $this->_bucket, $this->_index, $this->C('search/option'), $this->_stor );

		return $this;
	}


}