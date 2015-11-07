<?php
/**
 * Search 接口: Elasticsearch 
 *
 * CLASS 
 *
 * 	   Tuanduimao\Supertable\Search\Elasticsearch 
 *
 * USEAGE: 
 *
 *     不要直接使用
 * 
 */

 namespace Tuanduimao\Supertable\Search;

 use Elasticsearch\Client as ESClient;
 use Tuanduimao\Supertable\Type;

 class Elasticsearch {
 	
 	private $_stor;
 	private $_client=null;

 	private $_bucket;
 	private $_index;
 	private $_option;

 	private $_error = null;
 	private $_errno = null;


 	/**
 	 * 构造函数
 	 * @param Array  $bucket 存储空间结构 array('data'=>'name', 'schema'=>'schema_name')
 	 * @param Array  $index  索引结构  array('index'=>'index_name', 'type'=>'prefix_' )
 	 * @param [type] $option 服务器配置信息 array('host'=>array("127.0.0.1:9200") )
 	 * @param [type] $db    
 	 */
 	function __construct( $bucket, $index, $option, $stor ) {
 		$this->_stor = $stor;
 		$this->_bucket = $bucket;
 		$this->_index = $index;
 		$this->_option = $option;
 		$this->_stor;
 		$this->_client = new ESClient( $option );

 	}

 	/**
 	 * API: 创建一个新Type 
 	 * @param  String  $name         Type名称
 	 * @param  boolean $allow_exists true: 若Type已存在，返回true; false: 若Type已存在，抛出异常。
 	 * @return Mix  成功返回true , 失败返回false
 	 */
 	function createType( $name, $version, $allow_exists=false ) {

 		$alias = $this->_index['index'];
 		$index = "{$alias}_{$version}";

 		$type = $this->_index['type'] . $name;
 		$emptyMapping = array(
 			'_source' => array('enabled'=>true),
 			"_id" => array('path'=>'_spt_data_id'),
 			'properties' => array(
 				'_spt_data_id' => array(
 					 'type' => 'integer',
 				),
 				'_spt_data' => array(
	 				 'type' => 'object',
	 				 "enabled"=>false
	 			),
 			),
 		);

 		// 检查类型是否存在
 		$typeExistsParam = array( 'index'=>$index,  'type'=>$type);
 		if ( $this->_client->indices()->existsType($typeExistsParam) ) {
 			if ( $allow_exists ) {
 				return true;
 			}
 			$this->_error = "Index: $index/$name has exists! ";
 			return false;
 		}

 		// 检查索引是否存在
 		$indexExistsParam = array( 'index'=>$index );

 		if ($this->_client->indices()->exists($indexExistsParam)) {
 			var_dump($this->_client->indices()->exists($indexExistsParam));
 			return $this->updateType($name, $emptyMapping);
 		}


 		// 新建索引
 		// 创建索引/类型
 		$emptyTypeCreateParam = array(
 			'index'=>$index,
 			'body' => array(
 				'mappings'=> array( 
 					$type =>$emptyMapping
 				)
 			)
 		);

 		$result =  $this->_client->indices()->create($emptyTypeCreateParam );
 		if (!$result['acknowledged']) {
 			$this->_error = "Index: Create $index/$name Error (".json_encode($result).")";
 			return false;
 		}

 		$aliasParam = array( 'index'=>$index, 'name'=>$alias );
 		if ( !$this->_client->indices()->existsAlias($aliasParam) ) {
 			$result = $this->_client->indices()->putAlias( $aliasParam);
 			if (!$result['acknowledged']) {
 				$this->_error = "Index: Create $alias > $index Error (".json_encode($result).")";
	 			return false;
	 		}
 		}

 		return true;
 	}



 	/**
 	 * API: 更新Type结构
 	 * @param  [type] $name   [description]
 	 * @param  [type] $schema [description]
 	 * @return [type]         [description]
 	 */
 	function updateType( $name, $schema  ) {

 		$index = $this->_index['index'];
 		$type = $this->_index['type'] . $name;

 		// 新建索引
 		$properties = array(
 			'_spt_data_id' => array(
 				 'type' => 'integer',
 			),
 			'_spt_data' => array(
 				 'type' => 'object',
 				 "enabled"=>false
 			),
 		);

 		foreach ($schema as $field => $info) {
 			if ( $info['option']['searchable'] ) {
 				$field = "{$field}_{$info['_version']}";
 				$properties[$field]['type'] = $info['format'];
 				// $properties[$field]['fields'][$ver] = array('type'=>$info['format']);
 				if ( !isset($info['option']['fulltext']) && $info['format'] == 'string' ) {
 					$properties[$field]['index'] = 'not_analyzed';
 				}
 			}
 		}

 		$updateMapping = array(
 			"_source" => array('enabled'=>true),
 			"_id" => array('path'=>'_spt_data_id'),
 			"properties" => $properties
 		);


 		$typeUpdateParam = array(
 			'index'=>$index,
 			'type' =>$type,
 			'ignore_conflicts'=>true,
 			'body' => array(
 				$type =>$updateMapping
 			)
 		);

 		$result = $this->_client->indices()->putMapping( $typeUpdateParam );

 		if (!$result['acknowledged']) {
 			return false;
 		}
 		return true;
	}


	/**
	 * 创建数据
	 * @param  [type] $sheet [description]
	 * @param  [type] $id    [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	function createData( $sheet, $id, $data ) {

		$index = $this->_index['index'];
 		$type = $this->_index['type'] . $sheet['name'];
 		$index_data = $this->getIndexData( $data, $sheet );

 		if( $this->uniqueCheck($sheet['name'], $index_data['unique']) == false ) {
 			return false;
 		}

 		$doc = array(
 			'_spt_data_id' =>$id,
 			'_spt_data' => $data,
 		);


 		$doc = array_merge($index_data['index'], $doc );
 		$docInputParam = array(
 			'index' => $index,
 			'type' => $type,
 			'body' => $doc,
 		);

 		$result = $this->_client->index( $docInputParam );
 		if ( $result['_id'] != $id ) {
 			$this->_error = "Index: createData /$index/{$sheet['name']}/$id Error (".json_encode($result).")";
 			return false;
 		}

 		return true;
	}


 	/**
     * @param       $method
     * @param       $uri
     * @param null  $params
     * @param null  $body
     * @param array $options
     *
     * @return mixed
     */
	public function selectSQL( $sheet, $where, $fields ) {

		$index = $this->_index['index'];
 		$type = $this->_index['type'] . $sheet['name'];
 		$table = "$index/$type";		
		$where = $this->sqlFilter( "$where", $sheet );
		$fields = $this->fieldFilter($fields, $sheet);

		$sql = "SELECT " . implode(',', $fields) . " FROM $index/$type $where";
		// echo " SQL = $sql \n";
		
		$conn = $this->_client->transport->getConnection();
		$resp = $conn->performRequest('GET', '/_sql', array('sql'=>$sql));
		return $this->resultFilter( $resp, $sheet );

	}


	public function querySQL( $sheet, $sql ) {
		// echo " SQL = $sql \n";
		$conn = $this->_client->transport->getConnection();
		$sql = $this->sqlFilter( "$sql", $sheet );
		try{
			$resp = $conn->performRequest('GET', '/_sql', array('sql'=>$sql));
		} catch( Exception $e ) {
			$this->_errno = 500;
			$this->_error = "Index: selectSQL Request Error (". $e->getMessage() . ")";
			return false;
		}
		return $this->resultFilter( $resp, $sheet );

		//echo $resp['text'];
	}




	/**
	 * 检查唯一数值
	 * @param  [type] $name        [description]
	 * @param  [type] $unique_data [description]
	 * @return [type]              [description]
	 */
	private function uniqueCheck( $name, $unique_data ) {
	
		$query =array(
			'index' => $this->_index['index'],
			'type'  => $this->_index['type'] . $name,
		);

		foreach ($unique_data as $field => $value) {
			$query['body']['query']['term'][$field] = $value;
			$result = $this->_client->search($query);
			$hits = $result['hits'];

			if ( !isset($hits['total']) ) {
				$this->_error = "Index: uniqueCheck /{$this->_index['index']}/$name/$field Error (".json_encode($result).")";
				return false;
			}

			if ($hits['total'] != 0 ) {
				$this->_errno = 1062;
				$this->_error =  "Index: uniqueCheck /{$this->_index['index']}/$name/$field/$value duplicate ID={$hits['hits'][0]['_id']} Exisit ！(".json_encode($result).")";
				return false;
			}

			// 清空查询条件
			unset($query['body']['query']['term']);
		}

		return true;
	}

	/**
	 * 给待输入字段增加版本号
	 * @param  [type] $data  [description]
	 * @param  [type] $sheet [description]
	 * @return [type]        [description]
	 */
	private function getIndexData( $data, $sheet ) {
		$index_data = array();
		$unique_data = array();
		foreach ($data as $field => $value ) {
			if ($sheet['columns'][$field]->isSearchable()) {
				$ver = $sheet['_spt_schema_json'][$field]['_version'];
				$name = "{$field}_{$ver}";
				$index_data[$name] = $value;
				if ($sheet['columns'][$field]->isUnique() ) {
					$unique_data[$name] = $value;
				}
			}
		}
		return array('index'=>$index_data, 'unique'=>$unique_data);
	}


	/**
	 * 去掉搜索返回结果中的版本号
	 * @param  [type] $data  [description]
	 * @param  [type] $sheet [description]
	 * @return [type]        [description]
	 */
	private function indexRecover( $data, $sheet ) {

		$source = array_merge($data, array());

		if ( isset($data['_spt_data_id']) ) {
			$data['_id'] = $data['_spt_data_id'];
			unset($data['_spt_data_id']);
		}

		if ( is_array($data['_spt_data']) ){

			$newData = $data['_spt_data'];
			unset($data['_spt_data']);
			if ( isset($data['_id']) ) {
				$newData['_id'] = $data['_id'];
			}

			if ( count($data) >= count($newData) ) {
				return $newData;
			}

			$source = array_merge( $data, $newData);
		}

		foreach ($sheet['_spt_schema_json'] as $field => $row ) {
			if ($sheet['columns'][$field]->isSearchable()) {
				$ver = $row['_version'];
				$name = "{$field}_{$ver}";
				if ( isset($data[$name]) ) {
					$data[$field] = $source[$name];
					unset($data[$name]);
				}
			} else { // 不是索引
				if ( isset($source[$field]) ) {
					$data[$field] = $source[$field];
				}
			}
		}

		return $data;
	}


	private function resultFilter( $resp, $sheet ) {

		if ( !isset($resp['status']) || !isset($resp['text']) ) {
			$this->_errno = 500;
			$this->_error =  "Index: resultFilter Error " . json_encode($resp);
			return false;
		}

		$result = json_decode($resp['text'], true );
		if( json_last_error() !== JSON_ERROR_NONE) {
			$this->_errno = 500;
			$this->_error =  "Index: resultFilter JSON Parser Error( " . json_last_error_msg() . ')'. json_encode($resp);
			return false;
		}

		$data = array();
		$row_ext = array();
		if (!is_array($result['hits']['hits'])) {
			$this->_errno = 500;
			$this->_error =  "Index: resultFilter Result Error " . json_encode($result);
			return false;
		}

		// 处理函数等数值
		if ( is_array($result['aggregations']) ) {
			foreach ($result['aggregations'] as $field => $arr ) {
				$row_ext[$field] = $arr['value'];
			}
		}

		// 处理资源等数据
		foreach ($result['hits']['hits'] as $hits ) {
			$row = array();
			if ( is_array($hits['_source']) ) {
				$row = array_merge($this->indexRecover($hits['_source'], $sheet ), $row_ext);
				array_push($data, $row);
			}
		}

		if ( count($data) == 0 && count($row_ext) > 0 ) {
			array_push($data, $row_ext);
		}

		return array('rows'=>$data, 'total'=>$result['hits']['total']);
	}


	/**
	 * 给输入的字段增加版本号
	 * @param  [type] $fields [description]
	 * @param  [type] $sheet  [description]
	 * @return [type]         [description]
	 */
	private function fieldFilter( $fields, $sheet) {
		$fields = (is_array($fields))?$fields:explode(',', $fields);
		$needAddId = true;
		foreach ($fields as $idx=>$field ) {
			
			if ( $field == '*' ) {
				$fields = array();
				$needAddId = false;
				break;
			}

			if ( !isset($sheet['columns'][$field]) ) {
				// unset($fields[$idx]);
				continue;
			}
			if ($sheet['columns'][$field]->isSearchable()) {
				$ver = $sheet['_spt_schema_json'][$field]['_version'];
				$name = "{$field}_{$ver}";
				$fields[$idx] = $name;
			} else {
				array_push($fields, '_spt_data');
			}
		}
		
		if ( count($fields) == 0){
			 array_push($fields, '*');
		} else if ( $needAddId ) {
			 array_push($fields, '_spt_data_id');
		}
		return array_unique($fields);
	}


	/**
	 * 给待查询SQL，中字段增加版本号
	 * @param  [type] $sql   [description]
	 * @param  [type] $sheet [description]
	 * @return [type]        [description]
	 */
	private function sqlFilter( $sql, $sheet ) {

		$fields = array();
		// $sql = "SELECT SUM(content),COUNT(*),COUNT(b) FROM bd/bdtype_bd_test WHERE content=matchQuery('美女') AND Map=SUM(age) AND uk LIKE '%0' LIMIT 3,20";

		// $sql = "SELECT distinct content,url,content,sum(a) FROM bd/bdtype_bd_test where content_1=matchQuery('美女') and url like '%D' order by url,id group by content desc LIMIT 3,2";
		$key = array( 
			'select' => '[S][E][L][E][C][T]',
			'order' => '([Oo][Rr][Dd][Ee][Rr])[ ]{1}[ ]*[Bb][Yy]',
			'group' => '([Gg][Rr][Oo][Uu][Pp])[ ]{1}[ ]*[Bb][Yy]',
			'from' => '[Ff][Rr][Oo][Mm]',
			'distinct' => '([Dd][Ii][Ss][Tt][Ii][Nn][Cc][Tt])',
			'limit' => '([Ll][Ii][Mm][Ii][Tt])',
			'where'=>array(
				'[Ww][Hh][Ee][Rr][Ee]', // where
				'[Aa][Nn][Dd]',  // and
				'[Oo][Rr]' // or
			),

			'function'=>array(
				'[Ss][Uu][Mm]|[Mm]', // sum
				'[Mm][Aa][Xx]', // max
				'[Mm][Ii][Nn]', // min
				'[Aa][Vv][Gg]', // avg
				'|[Cc][Oo][Uu][Nn][Tt]' //count
			)
		);


		$regDistinctFields= "/{$key['distinct']}[ ]{1}[ ]*([a-zA-Z0-9\_]+)/";
		$regSelectFields = "/({$key['select']})(.+){$key['from']}/"; // SELECT语句中的 Field
		$regOrderFields = "/{$key['order']}[ ]{1}[ ]*(.+)/";
		$regGroupFields = "/{$key['group']}[ ]{1}[ ]*([a-zA-Z0-9\_]+)/";
		$regWhereFields = "/(".implode('|', $key['where']).")[ ]{1}[ ]*([a-zA-Z0-9\_]+)/";   // WHERE语句中的 Field
		$regFunctionFields = "/(".implode('|', $key['function']).")\(([a-zA-Z0-9\_]+)\)/";   // 函数中的 Field


		$GLOBALS['_the_sheet'] = $sheet;
		$newSql = preg_replace_callback( array(
				  $regSelectFields,$regDistinctFields, 
				  $regGroupFields, $regWhereFields, 
				  $regFunctionFields, $regOrderFields), function($match){

			$_the_sheet = $GLOBALS['_the_sheet'];
			$type = $match[1];

			// 处理SELECT 中的数据
			if ( strtolower($type) == 'select' ) {
				$match[0] = preg_replace_callback( "/([a-zA-Z0-9\_]+)/", function($match) {
					$_the_sheet = $GLOBALS['_the_sheet'];
					$field = $match[0];
					if ( !isset($_the_sheet['columns'][$field]) ) {
						return $field;
					}

					if ($_the_sheet['columns'][$field]->isSearchable()) {
						$ver = $_the_sheet['_spt_schema_json'][$field]['_version'];
						$name = "{$field}_{$ver}";

						return $name;
					}
					return $field;

				},$match[0]);
				return $match[0];
			} else if ( strtolower($type) == 'order' ) {
				$match[0] = preg_replace_callback( "/([a-zA-Z0-9\_]+)/", function($match) {
					$_the_sheet = $GLOBALS['_the_sheet'];
					$field = $match[0];
					if ( !isset($_the_sheet['columns'][$field]) ) {
						return $field;
					}

					if ($_the_sheet['columns'][$field]->isSearchable()) {
						$ver = $_the_sheet['_spt_schema_json'][$field]['_version'];
						$name = "{$field}_{$ver}";

						return $name;
					}
					return $field;

				},$match[0]);
				return $match[0];
			}

			$ostr = $match[0];
			$name = $field = $match[2];
			if ( !isset($_the_sheet['columns'][$field]) ) {
				//echo "Not Found: $field & NAME=$name \n";
				return $ostr;
			}

			if ($_the_sheet['columns'][$field]->isSearchable()) {
				$ver = $_the_sheet['_spt_schema_json'][$field]['_version'];
				$name = "{$field}_{$ver}";
				$ostr = str_replace($field, $name, $ostr);
				// echo "Found: $field : $name  & ";
			}

			// echo "Other: NAME=$name\n";
			return $ostr;
		},$sql);
		unset($GLOBALS['_the_sheet']);
		return $newSql;
	}



 	function error() {
 		return $this->_error;
 	}


 	function errno() {
 		return $this->_errno;
 	}
 }

