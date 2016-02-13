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
use Tuanduimao\Supertable\Items;
use Tuanduimao\Supertable\Item;


/**
 * SuperTable
 */
class Table {
	
	private $_stor = array();
	private $_type = null;
	private $_cache = null;


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
	 * 读取摘要清单
	 * @param  integer $limit [description]
	 * @return [type]         [description]
	 */
	public function summary( $limit=null ) {
		$summary = [];
		$cnt = 0;
		$columns = $this->sheet()['columns'];

		$columns_sort = $this->_columns_sort( $columns );
		foreach ( $columns_sort as $idx=>$column ) {
			$field = $column['field'];
			$type = $column['type'];

			if ( $type->isSummary() ) {
				array_push($summary, $field );
				$cnt++;

				if ( $cnt == $limit) {
					break;
				}
			}
		}
		return $summary;
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

		$this->_sheet_id = $sheet['_id'];
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
	

	/**
	 * 更新一个数据表 (Sheet )
	 * @param  array  $data 扩展数据 (如果有自定字段，则填写这些字段的数值) 
	 * @return [type]       [description]
	 */
	public function updateSheet( $data = array() ) {
		
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$sheet_id = $this->_schema->updateSheet( $this->_sheet_id, $data );
		return $this->selectSheet( $sheet_id );
	}

	// 读取所有表格
	public function querySheet(  $options, $page=null,  $perpage=20, $maxrows=0 ) {
		return $this->_schema->querySheet( $options, $page, $perpage, $maxrows );
	}

	/**
	 * 删除一张数据表( Sheet )
	 * @param  boolean $mark_only true: 标记删除已有数据记录 is_delete=1 (可恢复) , false: 删除已有数据记录 (毁灭性)
	 * @return [type]              [description]
	 */
	public function deleteSheet( $mark_only = true ) {

		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		return $this->_schema->deleteSheet( $this->_sheet_id, $mark_only );

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
	public function putColumn( $column_name, Type $type ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		if (!preg_match('/^([a-zA-Z]{1})([a-zA-Z0-9\_])/', $column_name) ) {
			throw new Exception("列名称格式不正确，由字符、数字和下划线组成，且开头必须为字符。(column_name= $column_name) ");
		}

		$this->_schema->putField( $this->_sheet_id, $column_name, $type );
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


	public function synColumn( $data ) {
		
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		if ( !is_array($data) ) {
			throw new Exception(" Input Error. data is not array!");
		}
		foreach ($data as $method => $datar ) {
			$method = "{$method}Column";
			foreach ($datar as $field => $data ) {
				if ($method == 'dropColumn' ) {
					$this->$method( $field);
				} else {

					$type_name = $data['_type'];
					$type = $this->type($type_name, $data);
					// echo "field:$field ====================\n";
					// print_r( $data );
					// print_r( $type->toArray());
					// echo "\n\n";

					$this->$method( $field, $type );

				}
			}
		}
		return $this->selectSheet( $this->_sheet_id );
	}

	/**
	 * 读取全文检索清单
	 * @return [type] [description]
	 */
	public function getFullTextColumns( $columns = null ){
		$columns = ( $columns ==null ) ? $this->sheet()['columns'] : $columns;
		$fulltext_list_arr = [];
		foreach ($columns as $k => $type) {
			if ( $type->isFulltext() ) {
				$field = $type->get('column_name');
				$screen_name = $type->get('screen_name');
				$fulltext_list_arr[$field] = $screen_name;
			}
		}
		return $fulltext_list_arr;
	}
	


	// === 数据 (Data) 相关操作 CRUD ==========================
	

	/**
	 * 在当前的数据表(Sheet)中检索 (从索引库中查询，数据有不到一秒延迟)
	 * @param  string $where  检索条件 EG: "where name='张三' and mobile like '188%' order by mobile desc limit 40,20"
	 * @param  string|array  $fields 返回字段，多个用","分割 EG: "name,mobile,company" 或者 array('name','mobile', 'company')
	 * @return array  符合条件的记录集合 array('data'=>array(...), 'total'=>9109); 
	 */
	public function select( $where, $fields=array() ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		try {
			$data = $this->_search->selectSQL( $this->_sheet, $where, $fields );
		} catch( Exception $e) {
			throw new Exception($e->getMessage());
		}

		if ( $data == false ) {
			return false;
		}

		return $data;
	}


	/**
	 * 在当前的数据(Sheet)中检索 (从索引库中查询，数据有不到一秒延迟)
	 * @param  [type] $option [description]
	 * @param  array  $fields [description]
	 * @return [type]         [description]
	 */
	public function query( $options, $page=null, $perpage=20, $fields=array(), $maxrows=0  ){
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$columns = $this->sheet()['columns'];
		$items = new Items();

		// 查询条件
		$where = "";
		$order = "";
		$other = "";
		$limit = null;
		

		if ( is_array($options) ) {

			// 处理LIMIT语法
			if ( isset( $options['@limit'] ) ) {
				$limit = $options['@limit'];
				$items->query('@limit', ['name'=>'最多记录', 'value'=>$limit, 'screen_value'=>$limit, 'encode_value'=>$limit] );
			}
			if ( isset( $options['@order'] ) ) {
				$order = $options['@order'];
				$items->query('@order',  ['name'=>'排序方式', 'value'=>$order, 'screen_value'=>$order, 'encode_value'=>$order] );
			}

			$filed_list_arr = array();
			$filed_list_nested = array();
			foreach ($options as $k => $v) {
				
				if ( $v != "" && isset($columns[$k])) {
					if( trim($columns[$k]->valueString($v))  != "" ) {
						if ( $columns[$k]->dataFormat() == 'nested' ) {
							array_push($filed_list_nested, $columns[$k]->valueString($v) );
						} else {
							array_push($filed_list_arr, $columns[$k]->valueString($v) );
						}

						$screen_name = $columns[$k]->get('screen_name');
						if ( $screen_name == "" ) {
							$screen_name = '未知字段';
						}
						$items->query( $k, [
							'name'=>$screen_name, 
							'value'=>$v, 
							'screen_value'=>$columns[$k]->valueScreen($v), 
							'encode_value'=>$columns[$k]->valueEncode($v)
						]);
					}
				} else if ( in_array($k, ['_id','_update_at','_create_at','_is_deleted'])) {
					$key_map = [
						'_id'=>'ID',
						'_update_at'=>'更新时间',
						'_create_at'=>'创建时间',
						'_is_deleted'=>'删除时间',
					];

					$items->query( $k, [
							'name'=>$key_map[$k], 
							'value'=>$v, 
							'screen_value'=>$v, 
							'encode_value'=>$v,
						]);

					array_push($filed_list_arr, "$k='$v'" );

				}

				if ( $v != "" &&  $k == '@fulltext' )  { // 全文检索
					// $v = str_replace('-', '', $v);
					$fulltext_list_arr = [ "_id='".intval($v)."'"];
					foreach ($columns as $k => $type) {
						if ( $type->isFulltext() ) {
							array_push($fulltext_list_arr, $columns[$k]->valueString($v) );
						}
					}

					if ( count($fulltext_list_arr) > 0 ) {
						$fulltext_str = implode(' OR ', $fulltext_list_arr);
						array_push($filed_list_arr, "( $fulltext_str )" );
						$items->query( '@fulltext', ['name'=>'全文检索', 'value'=>$v, 'screen_value'=>$v, 'encode_value'=>$v ] );
					}
				}
			}

			$filed_list_arr = array_merge($filed_list_arr, $filed_list_nested );

			// 手写条件
			if ( isset( $options['@where'] ) ) {
				$other = $options['@where'];
				array_push($filed_list_arr, $other );
				$items->query( '@where',  ['@where'=>'更多条件', 'value'=>$options['@where'],'screen_value'=>$options['@where'], 'encode_value'=>$options['@where']] );
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


		$record_limit =( $limit != null) ? "LIMIT $limit" : "LIMIT $perpage";
		if ( $page !== null && is_numeric($page) ) {
			$from = ($page == null)? 0 : ($page-1) * $perpage;
			$record_limit = " LIMIT $from,$perpage";
			$items->query( '@page', ['name'=>'页码', 'value'=>$page, 'screen_value'=>$page, 'encode_value'=>$page ] );
		}

		$sql ="$where $order $record_limit";
		$sql = ( trim($where) != "" )? "WHERE $sql" : "$sql";
	

		// 查询记录
		$resp = $this->select( $sql, $fields );

		$record_total = $resp['total'];
		$rows = $resp['data'];
		$rows_map = [];
		$items->pagination( $page, $perpage, $record_total );

		foreach ($rows as $line ) {
			$row = [];
			$function_flag = false;
			if ( !isset($line['_data_revision']) ||
				 !isset($line['_schema_revision']) || 
				 ( $line['_data_revision'] != $this->sheet()['revision'] ) ) {

				if ( !isset($line['_function']) ) {
					$line = $this->get($line['_id'], true);
				} else {
					$function_flag = true;
					unset($line['_function']);
				}
				// echo "<pre>";	
				// print_r($line);
				// echo "</pre>";
			}


			foreach ($line as $column_name=>$value )  {

				if ( count($fields) > 0 && !in_array($column_name,$fields) && !$function_flag ) {
					continue;
				}


				$row[$column_name]['value'] = $value;
				$row[$column_name]['type'] = 'UNKNOWN';
				$row[$column_name]['html'] = $value;

				if ( isset( $columns[$column_name]) ) {
					$screen_name = $columns[$column_name]->get('screen_name');
					$row[$column_name]['type'] = $columns[$column_name]->toArray();
					$row[$column_name]['html'] = $columns[$column_name]->valueHTML($value);

					if ($screen_name != "") {
						$row[$screen_name]['value'] = $value;
						$row[$screen_name]['type'] = $row[$column_name]['type'] ;
						$row[$screen_name]['html'] = $row[$column_name]['html'] ;
						$row[$screen_name]['width'] = $row[$column_name]['width'] ;
					}
				}
			}

			$item = new Item( $row );
			$items->push( $item );
		}

		return $items;
	}


	/**
	 * 读取当前的数据表(Sheet)中一条记录 (实时，从存储引擎中直接提取)
	 * @param  [type] $data_id [description]
	 * @return [type]          [description]
	 */
	public function get( $data_id, $update_index=false ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}
		
		$data =  $this->_stor->getDataByID($data_id);
		if ( $update_index ) {
			$this->_search->updateData( $this->_sheet, $data_id, $data ); // 应该被优化掉
		}
		return $data;
	}
	


	/**
	 * 对表单提交的数据进行解码
	 * @param  [type] $form_data [description]
	 * @return $this
	 */
	public function decode( & $form_data ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}
		foreach ($form_data as $field => $value) {
			if ( isset($this->sheet()['columns'][$field]) ) {
				$form_data[$field] = $this->sheet()['columns'][$field]->valueDecode( $value );
			}
		}
		return $this;
	}

	/**
	 * 对单个数据进行解码
	 * @param  [type] $name  [description]
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function decodeColumn( $name , $value ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}
		if ( isset($this->sheet()['columns'][$name]) ) {
			$value = $this->sheet()['columns'][$name]->valueDecode( $value );
		}
		return $value;
	}


	/**
	 * 在当前的数据表(Sheet)中，插入一条记录
	 * @param  [type] $data Array('field'=>'value' ... )
	 * @return [type]       [description]
	 */
	public function create( $data ) {

		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}


		// 根据数据结构，检查数据是否合法
		if ( $this->validation( $data ) === false ) {
			return false;
		}

		// 数据入库
		$data_id = $this->_stor->createData( $data, $this->_sheet );
		$newData = $this->_stor->getDataByID( $data_id );
		
		// 添加索引
		if ( $this->_search->createData( $this->_sheet, $data_id, $newData ) == false ){
			$this->_stor->deleteData( $data_id );

			if ( $this->_search->errno() == "1062" ) {
				$column = $this->_search->errdt();
				if (isset($this->sheet()['columns'][$column]) ) {
					$screen_name = $this->sheet()['columns'][$column]->get('screen_name');
					 $this->errors = array_merge( $this->errors, [$screen_name=>[
							[ 
							  "message"=>"{$screen_name}已存在", 
							  'method'=>'unique', 
							  'format'=>'unique', 
							  'field' => $column,
							  'name'=>$screen_name,
							  'value'=>$data[$column], 
							]
						]
					]);
				} else {
					$this->errors =  array_merge( $this->errors, ['未知数据'=>[[
						"message"=>"数据有重复", 
						'method'=>'unique', 
						'format'=>'unique', 
						'field' => '<unknown>',
						'name'=>'未知数据',
						'value'=>'未知数据',
					]]]);
				}

			} else {
				array_push( $this->errors, $this->_search->error() );
			}

			return false;
		}
		return $newData;
	}

	/**
	 * 在当前的数据表(Sheet)中，更新一条记录
	 * @param  [type] $id   [description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function update( $data_id, $data ) {

		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}


		// 根据数据结构，检查数据是否合法
		if ( $this->validation( $data, true ) === false ) {
			return false;
		}

		// 数据存储更新
		$this->_stor->updateData( $data_id, $data, $this->sheet() );
		$newData = $this->_stor->getDataByID( $data_id);

		// 更新索引
		if ( $this->_search->updateData( $this->_sheet, $data_id, $newData ) == false ){
			array_push( $this->errors, $this->_search->error() );

			if ( $this->_search->errno() == "1062" ) {
				$column = $this->_search->errdt();
				if (isset($this->sheet()['columns'][$column]) ) {
					$screen_name = $this->sheet()['columns'][$column]->get('screen_name');
					 $this->errors = array_merge( $this->errors, [$screen_name=>[
							[ 
							  "message"=>"{$screen_name}已存在", 
							  'method'=>'unique', 
							  'format'=>'unique', 
							  'field' => $column,
							  'name'=>$screen_name,
							  'value'=>$data[$column], 
							]
						]
					]);
				} else {
					$this->errors =  array_merge( $this->errors, ['未知数据'=>[[
						"message"=>"数据有重复", 
						'method'=>'unique', 
						'format'=>'unique', 
						'field' => '<unknown>',
						'name'=>'未知数据',
						'value'=>'未知数据',
					]]]);
				}

			} else {
				array_push( $this->errors, $this->_search->error() );
			}
			
			return false;
		}

		return $newData;
	}


	/**
	 * 在当前的数据表(Sheet)中，更新一条记录( 如果不存在则创建 )
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function save( $data ) {
		$data_id = (isset($data['_id']))? $data['_id'] : null;
		if ( $data_id != null ) {
			unset($data['_id']);
			return $this->update( $data_id, $data );
		} else {
			return $this->create( $data );
		}
	}


	/**
	 * 再当前数据表(Sheet)中，删除一条记录
	 * @param  [type] $data_id [description]
	 * @return [type]          [description]
	 */
	public function delete( $data_id ) {

		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}


		// 更新索引
		if ( $this->_search->deleteData( $this->_sheet, $data_id ) == false ){
			array_push( $this->errors, $this->_search->error() );
			return false; 
		}

		// 删除数据
		$this->_stor->deleteData( $data_id );
		return true;
	}



	
	/**
	 * 校验数据是否合法
	 * @param  [type]  $data       输入的数据
	 * @param  boolean $input_only 是否仅校验输入的数据是否合法, 不校验必填字段。默认为false
	 * @return [type]              [description]
	 */
	public function validation( $data, $input_only=false ) {
		
		$this->errors = array();

		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$errflag = false;
		if ( $input_only ) {  // 仅校验输入字段
			foreach ($data as $field=>$value ) {

				if ( !isset($this->_sheet['columns'][$field]) ) { // 忽略未知字段
					continue;
				}

				$type = $this->_sheet['columns'][$field];
				if ( !$type->validation( $value ) ) {
					$errflag = true;
					$this->errors = array_merge($this->errors, $type->errors);
				}
			}

		} else {  // 校验必填
			foreach ($this->_sheet['columns'] as $name=>$type ) {

				
				if ( !$type->validation( $data[$name] ) ) {
					$errflag = true;
					$this->errors = array_merge($this->errors, $type->errors);
				}

			}
		}
		return !$errflag;
	}

	public function error_reporting(){
		$errors = [];
		foreach ($this->errors as $name => $value ) {
			if (is_array($value) ) {
				$msg_title = "$name: ";
				foreach ($value as $v ) {
					if ( isset($v['message']) ) {
						$msg = $msg_title . $v['message'];
					}
					array_push($errors, $msg );
				}

			} else if ( is_string($value) ) {
				$msg_title = "$name: ";
				$msg = $msg_title . $value;
				array_push($errors, $value );
			}
		}
		return $errors;
	}


	// === 页面渲染相关Helper ==========================
	// 1. Column 创建、修改表单 （ HTML + JS组件 ) renderColumn*
	// 2. Column 创建、修改、删除和查询处理  actionColumn*
	// 3. Data 创建、修改和查询(列表)表单  （ HTML + JS组件 )  renderData*
	// 4. Data 创建、修改、删除和查询处理  actionData*
	

	/**
	 * Column 创建Column表单和JS组件 
	 * @param  string $type_name 类型名称，默认为 inlineText
	 * @param  string $tpl       自定义模板文件，默认为NULL，该类型默认模板
	 * @return array  表单HTML代码和类型数据  ['status'=>'success','data'=>[...], 'html'=>'<div>...</div>' ]
	 */
	public function renderColumnCreate( $type_name='inlineText', $option=array() ) {

		$this->errors = array();
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$Type = $this->type($type_name);
		return $Type->renderCreate( $this->_sheet_id, $option );
	}



	/**
	 * Column 更新Column 表单和JS组件 
	 * @param  string $column_name 字段名称
	 * @param  string $tpl       自定义模板文件，默认为NULL，该类型默认模板
	 * @return string html代码
	 */
	public function renderColumnUpdate( $data, $option=array() ) {
		$this->errors = array();
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$field_name = $data['column_name'];
		if ( $data['render_only'] == true && $field_name != "" ) { // 直接渲染
			$type_name = $data['_type'];
			$Type = $this->type( $type_name, $data );
			$Type->bindField($this->_sheet_id, $field_name );


		} else if( isset($this->_sheet['columns'][$field_name]) ) {  // 渲染服务器数据
			$Type = $this->_sheet['columns'][$field_name];			
		}

		return $Type->renderUpdate( $this->_sheet_id, $option );
	}


	/**
	 * Column  预览Column表单和JS组件 
	 * @param  string $type_name 类型名称，默认为 inlineText
	 * @param  array  $data 用户提交的数据
	 * @param  string $tpl       自定义模板文件，默认为NULL，该类型默认模板
	 * @return string html代码
	 */
	public function renderColumnPreview( $data, $option=array() ) {
		$this->errors = array();
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}
		
		$field_name = $data['column_name'];

		if ( $data['render_only'] == true && $field_name != "" ) { // 直接渲染
			$type_name = $data['_type'];
			$Type = $this->type( $type_name, $data );
			$Type->bindField($this->_sheet_id, $field_name );

		} else if( isset($this->_sheet['columns'][$field_name]) ) {  // 渲染服务器数据
			$Type = $this->_sheet['columns'][$field_name];
		}

		return $Type->renderPreview( $this->_sheet_id, $option );
	}



	/**
	 * Column  查询Column 列表页面和JS组件
	 * @param  [type] $tpl [description]
	 * @return String HTML 代码
	 */
	public function renderColumnQuery( $option ) {
		$this->errors = array();
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}
		$templete = $option['templete'] = (isset($option['templete']))? $option['templete'] : 'columns.container';
		$tpl = (isset($option['tpl']))? $option['tpl'] : $this->_tpl_filename($templete);
		$allow_types = $this->C('type/public/list'); // 开放型字段列表

		$data = ['items' => [], 'instance'=>$option];
		foreach ($this->_sheet['columns'] as $field=>$type ) {
			// display_hidden=0 不显示隐藏字段
			if ( !$option['display_hidden'] && $type->option('hidden') ) { 
				continue;
			}

			//忽略非开放字段类型
			if ( !in_array(@end(explode('\\', get_class($type))), $allow_types) ) { 
				continue;
			}

			//忽略 hidden_column = 1 的类型
			if ( !$option['display_hidden'] && $type->option('hidden_column') ) { 
				continue;
			}
			

			$data['items'][$field] = $type->renderItem( $this->_sheet_id, $field, $option );
		}
		$html = $this->_render( $data, $tpl );
		return ['status'=>'success','html'=>$html, 'data'=>$data];
	}

	/**
	 * Column  查询Column Items 列表页面和JS组件 (仅显示Item)
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	public function renderColumnQueryItem( $option ) {
		$this->errors = array();
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$columns = (isset($option['columns']))? $option['columns'] : [];
		if ( isset($option['columns'])) { unset( $option['columns']); }

		$templete = $option['templete'] = (isset($option['templete']))? $option['templete'] : 'columns.container';
		$tpl = (isset($option['tpl']))? $option['tpl'] : $this->_tpl_filename($templete);
		$allow_types = $this->C('type/public/list'); // 开放型字段列表


		$data = ['items' =>[], 'instance'=>$option, 'item_only'=>true ];
		foreach ( $columns as $field=>$type ) {
			
			// display_hidden=0 不显示隐藏字段
			if ( !$option['display_hidden'] && $type->option('hidden') ) { 
				continue;
			}

			//忽略非开放字段类型
			if ( !in_array(@end(explode('\\', get_class($type))), $allow_types) ) { 
				continue;
			}

			//忽略 hidden_column = 1 的类型
			if ( !$option['display_hidden'] && $type->option('hidden_column') ) { 
				continue;
			}
			

			$data['items'][$field] = $type->renderItem( $this->_sheet_id, $field, $option );
		}
		$html = $this->_render( $data, $tpl );

		return ['status'=>'success','html'=>$html, 'data'=>$data];
	}



	/**
	 * 数据查询: 搜索器
	 * @cache /_spt/queryform/$sheet_id/md5(json_encode($option))
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	public function renderQueryForm( $option ) {
		
		// _xhprfo_start();
		$this->errors = array();
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		// 从缓存中读取数据
		if ( $this->_cache != null && !defined('SUPERTABLE_DEBUG_ON') ) {
			$cache_name ="/_spt/queryform/{$this->_sheet_id}/" . md5(json_encode($option));
			$result = $this->_cache->get($cache_name);
			if ($result !== false ) {
				return json_decode($result,true);
			}
		}


		$columns = (isset($option['columns']))? $option['columns'] : $this->_sheet['columns'];
		if ( isset($option['columns'])) { unset( $option['columns']); }

		$templete = $option['templete'] = (isset($option['templete']))? $option['templete'] : 'query.form';
		$tpl = (isset($option['tpl']))? $option['tpl'] : $this->_tpl_filename($templete);
		
		$option['display_only'] = (isset($option['display_only']))? $option['display_only'] : [];
		$option['fillter'] = (isset($option['fillter']))? $option['fillter'] : [];
		$option['display_submit'] = (isset($option['display_submit']))? $option['display_submit'] : 1;
		$option['sheet_id'] = $this->_sheet_id;
		
		$data = ['items' =>[], 'instance'=>$option, 'item_only'=>false ];
		$columns_sort = $this->_columns_sort( $columns );

		foreach ( $columns_sort as $idx=>$column ) {
			$field = $column['field'];
			$type = $column['type'];
			
			if ( !method_exists($type, 'isSearchable') ) {
				continue;
			}

			// display_hidden=0 不显示隐藏字段
			if ( !$option['display_hidden'] && $type->isHidden() ) { 
				array_push($data['instance']['fillter'], $field);
			}


			if ( !$type->isSearchable() ) { 
				continue;
			}
			$data['items'][$field] = $type->renderItem( $this->_sheet_id, $field, $option );
		}

		$html = $this->_render( $data, $tpl );

		if ( $this->_cache != null && is_string($cache_name)  ) {
			$this->_cache->set($cache_name, json_encode(['status'=>'success','html'=>$html, 'data'=>$data]));
		}

		// _xhprof_end();

		return ['status'=>'success','html'=>$html, 'data'=>$data];
	}


	/**
	 * 数据增加/修改表单
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	public function renderDataForm( $option ) {
		$this->errors = array();
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$columns = (isset($option['columns']))? $option['columns'] : $this->_sheet['columns'];
		if ( isset($option['columns'])) { unset( $option['columns']); }

		$templete = $option['templete'] = (isset($option['templete']))? $option['templete'] : 'data.form';
		$tpl = (isset($option['tpl']))? $option['tpl'] : $this->_tpl_filename($templete);

		$option['fillter'] = (isset($option['fillter']))? $option['fillter'] : [];
		$option['display_submit'] = (isset($option['display_submit']))? $option['display_submit'] : 0;
		$option['sheet_id'] = $this->_sheet_id;

		// 读取数值
		$option['value'] = (is_numeric($option['_id']))? $this->get($option['_id']) : [];
		

		$data = ['items' =>[], 'instance'=>$option, 'item_only'=>false ];
		$columns_sort = $this->_columns_sort( $columns );

		foreach ( $columns_sort as $idx=>$column ) {
			$field = $column['field'];
			$type = $column['type'];
			
			if ( !method_exists($type, 'isSearchable') ) {
				continue;
			}

			// 给已有数据赋值
			if ( isset($option['value'][$field]) ) {
				$type->setValue( $option['value'][$field] );
			}


			// display_hidden=0 不显示隐藏字段
			if ( !$option['display_hidden'] && $type->isHidden() ) { 
				array_push($data['instance']['fillter'], $field);
			}

			//忽略 hidden_column = 1 的类型
			if ( !$option['display_hidden'] && $type->option('hidden_data') ) { 
				continue;
			}

			$data['items'][$field] = $type->renderItem( $this->_sheet_id, $field, $option );
		}


		$html = $this->_render( $data, $tpl );
		return ['status'=>'success','html'=>$html, 'data'=>$data];
		
	}



	/**
	 * 渲染模板
	 * @cache /_spt/cache/view/$tpl
	 * @param  [type] $data [description]
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	private function _render( $data,  $tpl=null ) {
		$cache_name = "/_spt/cache/view/$tpl";
		$view_content = false;

		// 读取缓存
		if ( $this->_cache != null ) {
			$view_content = $this->_cache->get($cache_name);
		}

	    if ( $view_content === false || defined('SUPERTABLE_DEBUG_ON') ) { // 从文件中载入模板

			if ( !file_exists($tpl) ) {
				throw new Exception("Templete Not Found! file=$tpl");
			}
			ob_start();
			$html = "";
			@extract( $data );
			if ( file_exists($tpl) ) {
				require( $tpl );
			}
			$content = ob_get_contents();
	        ob_end_clean();

	        // 将数据写入缓存
			if ( $this->_cache != null ) {
	        	$this->_cache->set($cache_name, file_get_contents($tpl) );
	        }

	    } else {
	    	ob_start();
			$html = "";
			@extract( $data );
			eval("?>" . $view_content . "<?php ");
			$content = ob_get_contents();
			ob_end_clean();
	    }

        return $content;
	}


	/**
	 * 获取模板路径
	 * @cache  /_spt/cache/view/*
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	private function _tpl_filename( $name ) {

		$cache_name = "/_spt/cache/view/";
		$path = $this->C('path');
		$view_file =  $path['templete'] . "$name.tpl.html";

		// 检查模板再缓存中是否存在
		if ( $this->_cache != null && !defined('SUPERTABLE_DEBUG_ON') ) {
			$content = $this->_cache->get( "{$cache_name}$view_file" );
			if ( $content !== false ) {
				return $view_file;
			}

			$view_file = __DIR__ . "/view/$name.tpl.html";
			$content = $this->_cache->get( "{$cache_name}$view_file" );
			if ( $content !== false ) {
				return $view_file;
			}

			// reset viewfile
			$view_file =  $path['templete'] . "$name.tpl.html";
		}


		if ( !file_exists($view_file) ) {
			$view_file = __DIR__ . "/view/$name.tpl.html";
		}

		return $view_file;
	}


	/**
	 * 对字段进行排序
	 * @return [type] [description]
	 */
	private function _columns_sort( $columns ) {

		$this->errors = array();
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}

		$sort = [];
		foreach ($columns as $field => $type ) {
			$order = $type->order();
			if ($type->isHidden() && $order == 1) {
				$order = 0;
			}
			array_push( $sort, ['order'=>$order, 'field'=>$field, 'type'=>$type] );
		}

		$sort = $this->_array_sort( $sort, 'order' );
		return $sort;
	}


	private function _array_sort($array,$keys,$type='asc'){
		if(!isset($array) || !is_array($array) || empty($array)){
			return '';
		}

		if(!isset($keys) || trim($keys)==''){
			return '';
		}

		if(!isset($type) || $type=='' || !in_array(strtolower($type),array('asc','desc'))){
			return '';
		}
		$keysvalue=array();
		foreach($array as $key=>$val){
			$val[$keys] = str_replace('-','',$val[$keys]);
			$val[$keys] = str_replace(' ','',$val[$keys]);
			$val[$keys] = str_replace(':','',$val[$keys]);
			$keysvalue[] =$val[$keys];
		}
		asort($keysvalue); //key值排序
		reset($keysvalue); //指针重新指向数组第一个
		foreach($keysvalue as $key=>$vals) {
			$keysort[] = $key;
		}
		$keysvalue = array();
		$count=count($keysort);
		if(strtolower($type) != 'asc'){
			for($i=$count-1; $i>=0; $i--) {
				$keysvalue[] = $array[$keysort[$i]];
			}
		}else{
			for($i=0; $i<$count; $i++){
				$keysvalue[] = $array[$keysort[$i]];
			}
		}
		return $keysvalue;
	}




	// === 类型 (Type) 相关Helper ==========================
	
	public function type( $name=null, $data=array(), $option=array() ) {
		
		$this->_cacheInit();
		if ( $name == null ) {
			if ( is_a($this->_type, "Tuanduimao\Supertable\Type") ) {
				return $this->_type;
			}

			$this->_type = (new Type())
								->setPath( $this->C('path') )
								->setPublic( $this->C('type/public'))
								->setCache( $this->_cache );

			return $this->_type;
		}

		return (new Type())
			 ->setPath( $this->C('path') )
			 ->setPublic( $this->C('type/public'))
			 ->setCache( $this->_cache )
			 ->load( $name, $data, $option );

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
	 * 		0）创建缓存对象
	 * 		1）创建数据库对象
	 * 		2) 创建搜索引擎对象
	 * 		3）创建类型对象
	 * 		4) 创建 schema 对象
	 * @return [type] [description]
	 */
	protected function init() {
		$this->_cacheInit();
		$this->_storInit();
		$this->_searchInit();
		$this->type();
		$this->_schema = new Schema( $this->_bucket,  $this->_stor, $this->_search, $this->_type, $this->_cache );
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


	// ====== 以下部分为私有函数
	private function indexName( $index_only=false ) {
		$index = $this->_index['index'];
		if ( $index_only ) {
			return $index;
		}
 		$type = $this->_index['type'] . $this->_sheet['name'];
 		$table = "$index/$type";
 		return $table;
	}

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


	/**
	 * 初始化Cache引擎
	 */
	private function _cacheInit() {
		if ( $this->_cache == null ){
			$engine = $this->C('cache/engine');
			$class_name = "\\Tuanduimao\\Supertable\\Cache\\{$engine}";
			if ( !class_exists($class_name) ) {
				$this->_cache = null;
			} else {
				$this->_cache  = new $class_name( $this->C('cache/option'));
			}
		}
		return  $this;
	}

	private function runsql( $sql ) {
		if ( $this->_sheet_id === null ) {
			throw new Exception("No sheet selected. Please Run selectSheet() or createSheet() first!");
		}
		$data = $this->_search->runSQL( $this->_sheet, $sql );
	}



}