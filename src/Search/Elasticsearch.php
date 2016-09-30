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
 	private $_cache;
 	private $_client=null;

 	private $_bucket;
 	private $_index;
 	private $_option;

 	private $_error = null;
 	private $_errno = null;
 	private $_errdt = null; //错误数据


 	/**
 	 * 构造函数
 	 * @param Array  $bucket 存储空间结构 array('data'=>'name', 'schema'=>'schema_name')
 	 * @param Array  $index  索引结构  array('index'=>'index_name', 'type'=>'prefix_' )
 	 * @param [type] $option 服务器配置信息 array('host'=>array("127.0.0.1:9200") )
 	 * @param [type] $db    
 	 */
 	function __construct( $bucket, $index, $option, $stor, $cache ) {
 		$this->_stor = $stor;
 		$this->_bucket = $bucket;
 		$this->_index = $index;
 		$this->_option = $option;
 		$this->_cache = $cache;
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
 				'_spt_data_revision' => array(
 					'type' => 'integer',
	 			),
	 			'_spt_schema_revision' => array(
	 				'type' => 'integer',
	 			),

	 			'_spt_create_at' => array(
	 				'type' => 'date',
	 			),
	 			'_spt_update_at' => array(
	 				'type' => 'date',
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

 				// 检查 Align
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
 			$this->_error = "Index: $index/$name has exists! ";
 			return false;
 		}

 		// 检查索引是否存在
 		$indexExistsParam = array( 'index'=>$index );

 		if ($this->_client->indices()->exists($indexExistsParam)) {
 			// var_dump($this->_client->indices()->exists($indexExistsParam));
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
 			'_spt_data_revision' => array(
 				'type' => 'integer',
 			),
 			'_spt_schema_revision' => array(
 				'type' => 'integer',
 			),
 			'_spt_create_at' => array(
	 				'type' => 'date',
	 		),
	 		'_spt_update_at' => array(
	 			'type' => 'date',
	 		),
 			'_spt_data' => array(
 				 'type' => 'object',
 				 "enabled"=>false
 			),
 		);

 		foreach ($schema as $field => $info) {


 			if ( isset($info['option']) && $info['option']['searchable'] ) {
 				$field = "{$field}_{$info['_version']}";
 				$properties[$field]['type'] = $info['format'];
 				// $properties[$field]['fields'][$ver] = array('type'=>$info['format']);
 				// 
 				
 				if ( $info['format'] == 'array' && isset( $info['data']['schema']) ) {
 					
 					
 					$properties[$field]['type'] = $info['data']['schema'];

 					// $properties[$field]['properties'] =[];
 					/*['data' => [
 						'type'=>$info['data']['schema']
 					]];
 					unset($properties[$field]['type']);
 					print_r($properties);*/
 				}


 				if ( !$info['option']['matchable'] && $info['format'] == 'string' ) {
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
	 * 删除一个TYPE
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	function deleteType( $name ) {
		
		$index = $this->_index['index'];
 		$type = $this->_index['type'] . $name;

 		$deleteMappingParam = [
 			'index' => $index,
 			'type' => $type,
 		];
 		$result = $this->_client->indices()->deleteMapping( $deleteMappingParam );
 		if (!$result['acknowledged']) {
 			// print_r($result);
 			return false;
 		}
 		return true;
	}



	/**
	 * 创建数据索引
	 * @param  [type] $sheet [description]
	 * @param  [type] $id    [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	function createData( $sheet, $id, $data ) {

		$index = $this->_index['index'];
 		$type = $this->_index['type'] . $sheet['name'];
 		$index_data = $this->getIndexData( $data, $sheet );

 		if( $this->uniqueCheck($sheet['name'], $index_data['unique'], null, $index_data['map']) == false ) {
 			return false;
 		}

 		$doc = array(
 			'_spt_data_id' =>$id,
 			'_spt_data' => $data,
 			'_spt_schema_revision' => $sheet['revision'],
 			'_spt_data_revision'  => $sheet['revision'],
 			'_spt_create_at' => $this->datetimeEncode( $data['_create_at']),
 			'_spt_update_at' => $this->datetimeEncode( $data['_update_at'])
 		);


 		$doc = array_merge($index_data['index'], $doc );
 		$docInputParam = array(
 			'index' => $index,
 			'type' => $type,
 			'body' => $doc,
 		);

 		// var_dump($docInputParam);
 		

 		$result = $this->_client->index( $docInputParam );
 		if ( $result['_id'] != $id ) {
 			$this->_error = "Index: createData /$index/{$sheet['name']}/$id Error (".json_encode($result).")";
 			return false;
 		}

 		$this->uniqueCached($result['_id'], $sheet['name'], $index_data['unique']);
 		return true;
	}


	/**
	 * 更新数据索引
	 * @param  [type] $sheet [description]
	 * @param  [type] $id    [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	function updateData( $sheet, $id, $data, $fixobjtype=true ) {
		
		$index = $this->_index['index'];
 		$type = $this->_index['type'] . $sheet['name'];
 		$index_data = $this->getIndexData( $data, $sheet );

 		// 修复Object 类型更新问题 
 		// PS: 如果有object类型的数据，需要更新两次，第一次设定为Null 第二次更新为新数据
 		$fixobjflag = false; // 
 		if ( $fixobjtype === true && count($index_data['object']) > 0 ) {
 			$fixobjflag = true;
 			$_spt_data = null; 
 			foreach ($index_data['object'] as $name=>$value ) {
 				$index_data['index'][$name] = null;
 			}
 		} // 修复Object 类型更新问题 END 

 		if( $this->uniqueCheck($sheet['name'], $index_data['unique'], $id, $index_data['map'] ) == false ) {
 			return false;
 		}

 		$doc = array(
 			'_spt_data_id' =>$id,
 			'_spt_data' => ($fixobjflag ===true)? null : $data,
 			'_spt_schema_revision' => $sheet['revision'],
 			'_spt_data_revision'  => $sheet['revision'],
 			'_spt_create_at' => $this->datetimeEncode( $data['_create_at']),
 			'_spt_update_at' => $this->datetimeEncode( $data['_update_at'])
 		);

 		$doc = array_merge($index_data['index'], $doc );
 		$docInputParam = array(
 			'body' => array(array(
 				'update' => array('_id'=>$id,'_index'=>$index, '_type'=>$type),
 				'doc' => $doc,
 			)),
 		);

 		$updateString = json_encode(array('update' => array('_id'=>$id,'_index'=>$index, '_type'=>$type)));
 		$updateString = json_encode(array('update' => array('_id'=>$id)));
 		$docString = json_encode(array('doc' => $doc));
 		$docInputParam = array(
 			'index' => $index,
 			'type' => $type,
 			'body'=>"\n$updateString\n$docString\n"
 		);

 		$result = $this->_client->bulk( $docInputParam );
 		if ( $result['errors'] != false || count($result['items']) != 1) {
 			$this->_error = "Index: updateData /$index/{$sheet['name']}/$id Error (".json_encode($result).")";
 			return false;
 		}

 		// 修复Object 类型更新问题
 		if ( $fixobjflag === true ) { 
 			return $this->updateData( $sheet, $id, $data, false );
 		} // 修复Object 类型更新问题 END 


 		$this->uniqueCached($id, $sheet['name'], $index_data['unique']);
 		return true;
	}


	function deleteData( $sheet, $id ) {

		$index = $this->_index['index'];
 		$type = $this->_index['type'] . $sheet['name'];
 		$conn = $this->_client->transport->getConnection();
		$resp = $conn->performRequest('DELETE', "/$index/$type/$id");

		if ( !isset($resp['status']) || !isset($resp['text']) ) {
			$this->_errno = 500;
			$this->_error =  "Index: deleteData Error " . json_encode($resp);
			return false;
		}

		$result = json_decode($resp['text'], true );
		if( json_last_error() !== JSON_ERROR_NONE) {
			$this->_errno = 500;
			$this->_error =  "Index: deleteData JSON Parser Error( " . json_last_error_msg() . ')'. json_encode($resp);
			return false;
		}

		if ( $result['found'] != true ) {
			$this->_errno = 404;
			$this->_error =  "Index: deleteData Error( "  . json_encode($resp);
			return false;
		}


		// 标记改ID数据已被删除
		if ( $this->_cache !== null ) {
			$cache_path = "unique:{$this->_index['index']}:{$this->_index['type']}{$name}:";
			$delete_cache = $cache_path . 'delete:' . $id;
			$resp = $this->_cache->set($delete_cache, time(), 90 ); // 缓存 90秒内的数据
			return $resp;
		}
		

		// 清空索引
		// if ( is_array($sheet['columns']) ) {

		// 	foreach ($sheet['columns'] as $type) {
		// 		if ( $type->isUnique() ) {
		// 			$index_data[]
		// 		}
		// 	}
		// }
		// $index_data = $this->getIndexData( $data, $sheet );



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
		// $where = $this->sqlFilter( "$where", $sheet );
		$fields = $this->fieldFilter($fields, $sheet);

		$sql = $this->sqlFilter("SELECT " . implode(',', $fields) . " FROM $index/$type $where", $sheet);
		// echo "<pre> SQL = $sql \n</pre>";
		
		$conn = $this->_client->transport->getConnection();
		try {
			$resp = $conn->performRequest('GET', '/_sql', array('sql'=>$sql));
		} catch( \Elasticsearch\Common\Exceptions\ServerErrorResponseException $e ) {
			
			if ( strpos($e->getMessage(), 'SqlParseException') > 0 
					||  strpos($e->getMessage(), 'ParserException') 
					||  strpos($e->getMessage(), 'illegal sql expr')  ) {
				return ['code'=>500, 'message'=>'SQL语法不正确', 'extra'=>['sql'=>$sql, 'where'=>$where, 'table'=>$table]];
			} else {
				throw $e;
			}
		}

		$result = $this->resultFilter( $resp, $sheet );;

		// echo "<pre>";
		// print_r($resp);
		// print_r($result['data'][0]['menu']);
		// echo "</pre>";

		return $result;
	}


	public function run_sql( $sheet, $sql ) {
		$conn = $this->_client->transport->getConnection();
		$sql = $this->sqlFilter( "$sql", $sheet );
		$resp = $conn->performRequest('GET', '/_sql', array('sql'=>$sql));
		return $resp;
	}


	private function datetimeEncode( $mysql_time ) {
		if ( $mysql_time == null) return null;
		return str_replace(' ', 'T', $mysql_time );
	}

	private function datetimeDecode( $es_time ) {
		if ( $es_time == null) return null;
		return str_replace('T', ' ', $es_time );
	}



	/**
	 * 使用唯一主键换取数据表ID 
	 * @param  [type] $uni_key [description]
	 * @param  [type] $value   [description]
	 * @return [type]          [description]
	 */
	public function uniqueToID( $sheet, $uni_key, $value ) {
		$fields = $this->fieldFilter([$uni_key], $sheet);
		$field = current($fields);
		$name = $sheet['name'];

		// 唯一索引缓存池名称
		if ( $this->_cache !== null ) {
			$cache_path = "unique:{$this->_index['index']}:{$this->_index['type']}{$name}:";
			$cache_name = $cache_path . $field. ':' . trim($value);
			$data_id = $this->_cache->get($cache_name);
			if (  $data_id !== false ) {
				return $data_id;
			}
		}

		$resp = $this->selectSQL($sheet,  "WHERE $uni_key='". $value . "' LIMIT 1", ['_id'] );
		if ( $resp['total'] != 1 ) {
			return null;
		}

		return current(current($resp['data']));
	}


	/**
	 * 清除缓存
	 * @return [type] [description]
	 */
	public function clearCache(){ 
		$ret = true;
		$cache = ["unique:{$this->_index['index']}:{$this->_index['type']}{$name}"];
		if ( $this->_cache !== null ) {
			foreach ($cache as $c ) {
				if ( $this->_cache->delete($c) === false ) {
					$ret = false;
				}
			}
		}
		return $ret;
	}



	/**
	 * 缓存唯一主键数值
	 * @param  int $id    数据表ID
	 * @param  string $name sheet名称
	 * @param  array  $unique_data 索引数据
	 * @return 成功返回 true, 失败返回false
	 */
	private function uniqueCached( $id, $name, $unique_data  ) {
		if ( $this->_cache === null ) {
			return false;
		}

		$result = true;

		// 唯一索引缓存池名称
		$cache_path = "unique:{$this->_index['index']}:{$this->_index['type']}{$name}:";
		foreach ($unique_data as $field => $value) {
			$cache_name = $cache_path . $field. ':' . trim($value);
			$resp = $this->_cache->set($cache_name, $id, 90 ); // 缓存 90秒内的数据
			if ( $resp === false ) {
				$result = false;
			}
		}
		return $result;
	}







	/**
	 * 检查唯一数值
	 * @param  [type] $name        [description]
	 * @param  [type] $unique_data [description]
	 * @param  [type] $except_id   无需检测的ID
	 * @return [type]              [description]
	 */
	private function uniqueCheck( $name, $unique_data, $except_id=null, $map=[], $allow_null = true ) {

		$query =array(
			'index' => $this->_index['index'],
			'type'  => $this->_index['type'] . $name,
		);

		// 唯一索引缓存池名称
		$cache_path = "unique:{$this->_index['index']}:{$this->_index['type']}{$name}:";

		foreach ($unique_data as $field => $value) {

			if ( $allow_null && trim($value) == "" ) {
				continue;
			}

			if ( !is_string($value) ) {
				continue;
			}

			// 检查缓存池中是否有重复数据
			$cache_name = $cache_path . $field . ':'. trim($value);
			if ( $this->_cache != null ) {
				$data_id = $this->_cache->get($cache_name);
				
				if (  $data_id !== false && $data_id != $except_id ) {
						
					// 验证删除标记 （ 该记录删除后，在缓存中增加一个标记 )
					$delete_cache = $cache_path . 'delete:' . $data_id;
					if ( $this->_cache->get($delete_cache) === false ) {
						// echo $cache_name . "=". var_export($data_id, true). "\n";
						$this->_errno = 1062;
						$this->_errdt = (isset($map[$field]))? $map[$field] : $field;
						$this->_error = "Index: uniqueCheck /{$this->_index['index']}/$name/$field Error";
						return false;
					}
				}
			}


			// $query['body']['query']['term'][$field] = $value;
			if ( $except_id != null ) {
				$except['not']['filter']['term']['_id'] = $except_id;
			}

			$term['term'][$field]=$value;
			$query['body']['query']["filtered"]['filter']['bool']['must'] = array(
				$except,$term
			);
			
			// print_r($query);

			$result = $this->_client->search($query);
			// print_r($result);

			$hits = $result['hits'];

			if ( !isset($hits['total']) ) {
				$this->_errno = 1062;
				$this->_errdt = (isset($map[$field]))? $map[$field] : $field;
				$this->_error = "Index: uniqueCheck /{$this->_index['index']}/$name/$field Error (".json_encode($result).")";
				return false;
			}


			if ($hits['total'] != 0 ) {

				if ( intval($hits['total']) == 1 &&  $except_id != null && $except_id == $hits['hits'][0]['_id'] ) {
					//忽略错误 donoting
				} else {

					$this->_errno = 1062;
					$this->_errdt = (isset($map[$field]))? $map[$field] : $field;
					$this->_error =  "Index: uniqueCheck /{$this->_index['index']}/$name/$field/$value duplicate ID={$hits['hits'][0]['_id']} HITS={$hits['total']} Exisit ！(".json_encode($result).")";
					return false;
				}
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
		$object_data = [];
		$field_map = array();
		
		foreach ($data as $field => $value ) {
			if ( !isset($sheet['columns'][$field]) ) {
				continue;
			}
			if ($sheet['columns'][$field]->isSearchable()) {
				$ver = $sheet['_spt_schema_json'][$field]['_version'];
				$name = "{$field}_{$ver}";
				$field_map[$name] = $field;
				$index_data[$name] = $value;

				if ( $sheet['columns'][$field]->dataFormat()  == 'date' ) {
					$index_data[$name] = $this->datetimeEncode($value);
				}


				if ($sheet['columns'][$field]->isUnique() ) {
					$unique_data[$name] = $value;
				}

				if ( $sheet['columns'][$field]->dataFormat()  == 'object' ) {
					$object_data[$name] = $value;
				}

			}
		}
		return array('index'=>$index_data, 'unique'=>$unique_data, 'map'=>$field_map, 'object'=>$object_data );
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

		// 处理 Version
		if ( isset($data['_spt_data_revision']) ) {
			$data['_data_revision'] = $data['_spt_data_revision'];
			unset($data['_spt_data_revision']);
		}

		if ( isset($data['_spt_schema_revision']) ) {
			$data['_schema_revision'] = $data['_spt_schema_revision'];
			unset($data['_spt_schema_revision']);
		}

		// 处理日期
		if ( isset($data['_spt_create_at']) ) {
			$data['_create_at'] = $this->datetimeDecode($data['_spt_create_at']);
			unset($data['_spt_create_at']);
		}

		if ( isset($data['_spt_update_at']) ) {
			$data['_update_at'] =  $this->datetimeDecode($data['_spt_update_at']);
			unset($data['_spt_update_at']);
		}


		if ( is_array($data['_spt_data']) ){

			$newData = $data['_spt_data'];
			unset($data['_spt_data']);
			if ( isset($data['_id']) ) {
				$newData['_id'] = $data['_id'];
			}

			if ( isset($data['_data_revision']) ) {
				$newData['_data_revision'] = $data['_data_revision'];
			}

			if ( isset($data['_schema_revision']) ) {
				$newData['_schema_revision'] = $data['_schema_revision'];
			}

			//处理日期
			if ( isset($data['_create_at']) ) {
				$newData['_create_at'] = $data['_create_at'];
			}
			if ( isset($data['_update_at']) ) {
				$newData['_update_at'] = $data['_update_at'];
			}

			if ( count($data) >= count($sheet['_spt_schema_json']) ) {
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

					if ( $sheet['columns'][$field]->dataFormat() == 'date'  ){
						$data[$field]  = $this->datetimeDecode($data[$field]);
					}
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

	/**
	 * 处理SQL查询结果
	 * @param  [type] $resp  [description]
	 * @param  [type] $sheet [description]
	 * @return [type]        [description]
	 */
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
		if ( isset($result['aggregations']) && is_array($result['aggregations']) ) {
			foreach ($result['aggregations'] as $field => $arr ) {
				$row_ext[$field] = $arr['value'];
				$row_ext['_function'] = true;
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

		return array('data'=>$data, 'total'=>$result['hits']['total']);
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
			$old_field = $field;
			$fieldr = explode('.', $field);

			$field = trim($fieldr[0]);

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
				$fieldr[0] = $name;
				$fields[$idx] = join('.',$fieldr);
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
				'[Oo][Rr]', // or
				'[\(]' // ( 
			),

			'function'=>array(
				'[Ss][Uu][Mm]|[Mm]', // sum
				'[Mm][Aa][Xx]', // max
				'[Mm][Ii][Nn]', // min
				'[Aa][Vv][Gg]', // avg
				'[Nn][Ee][Ss][Tt][Ee][Dd]', // nested
				'|[Cc][Oo][Uu][Nn][Tt]' //count
			)
		);


		$regDistinctFields= "/{$key['distinct']}[ ]{1}[ ]*([a-zA-Z0-9\.\_]+)/";
		$regSelectFields = "/({$key['select']})(.+){$key['from']}/"; // SELECT语句中的 Field
		$regOrderFields = "/{$key['order']}[ ]{1}[ ]*(.+)/";
		$regOrderFields2 = "/[@]*{$key['order']}[ ]{1}[ ]*(.+)/";
		$regGroupFields = "/{$key['group']}[ ]{1}[ ]*([a-zA-Z0-9\.\_]+)/";
		$regWhereFields = "/(".implode('|', $key['where']).")[ ]{1}[ ]*([a-zA-Z0-9\.\_]+)/";   // WHERE语句中的 Field
		$regFunctionFields = "/(".implode('|', $key['function']).")\(([a-zA-Z0-9\.\_]+)\)/";   // 函数中的 Field
		$regNestedFirstFields = "/([Nn][Ee][Ss][Tt][Ee][Dd])\(\'([a-zA-Z0-9\.\_]+)\'/";   // 函数中的 Field

		$GLOBALS['_the_sheet'] = $sheet;
		$newSql = preg_replace_callback( array(
				  $regSelectFields,$regDistinctFields, 
				  $regGroupFields, $regWhereFields, 
				  $regFunctionFields, $regNestedFirstFields, 
				  $regOrderFields2), function($match){

			$_the_sheet = $GLOBALS['_the_sheet'];
			$type = $match[1];

		

			// 处理SELECT 中的数据
			if ( strtolower($type) == 'select' ) {
				$match[0] = preg_replace_callback( "/([a-zA-Z0-9\.\_]+)/", function($match) {

					$_the_sheet = $GLOBALS['_the_sheet'];
					$field = $match[0];
					$fieldr = explode('.',$field);
					$field = $fieldr[0];

					if ( !isset($_the_sheet['columns'][$field]) ) {
						return join('.',$fieldr);
					}

					if ($_the_sheet['columns'][$field]->isSearchable()) {
						$ver = $_the_sheet['_spt_schema_json'][$field]['_version'];
						$name = "{$field}_{$ver}";
						$fieldr[0] = $name;
						return join('.',$fieldr);
					}

					return join('.',$fieldr);

				},$match[0]);
				return $match[0];
			} else if ( strtolower($type) == 'order' ||  strtolower($type) == '@order' ) {

					//  echo "<pre>";
					//  echo "=====: \n";
					//  print_r($match);
					//  echo "END ===== \n\n";
					//  echo "</pre>";

				$match[0] = preg_replace_callback( "/([@]*[a-zA-Z0-9\.\_]+)/", function($match) {
					$_the_sheet = $GLOBALS['_the_sheet'];
					$field = $match[0];
					$fieldr = explode('.',$field);
					$field = $fieldr[0];
					
					// echo "<pre>";
					// print_r($field);
					// echo "</pre>";

					// 系统关键词 _spt_data_id , _spt_create_at, _spt_update_at 
					if ( $field == '_id' ) {
						return '_spt_data_id';
					}

					if ( $field == '_create_at' ) {
						return '_spt_create_at';
					}

					if ( $field == '_update_at' ) {
						return '_spt_update_at';
					}

					if ( $field == '@order' ) {
						return 'order';
					}

					if ( $field == 'order' ) {
						return 'order';
					}


					if ( !isset($_the_sheet['columns'][$field]) ) {
						return join('.',$fieldr);
					}

					if ($_the_sheet['columns'][$field]->isSearchable()) {
						$ver = $_the_sheet['_spt_schema_json'][$field]['_version'];
						$name = "{$field}_{$ver}";
						$fieldr[0] = $name;
						return join('.',$fieldr);
					}
					return  join('.',$fieldr);

				},$match[0]);
				return $match[0];
			}

			$ostr = $match[0];
			$name = $field = $match[2];
			$fieldr = explode('.',$field);
			$field = $fieldr[0];

			// 系统关键词 _spt_data_id , _spt_create_at, _spt_update_at 
			if ( $field == '_id' ) {
				return str_replace('_id', '_spt_data_id', $ostr);
			}

			if ( $field == '_create_at' ) {
				return str_replace('_create_at', '_spt_create_at', $ostr);
			}

			if ( $field == '_update_at' ) {
				return str_replace('_update_at', '_spt_update_at', $ostr);
			}


			if ( !isset($_the_sheet['columns'][$field]) ) {
				// echo "Not Found: $field & NAME=$name ostr=$ostr \n";
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

		// echo "<pre>";
		// echo $newSql;
		// echo "</pre>";

		return $newSql;
	}



 	function error() {
 		return $this->_error;
 	}


 	function errno() {
 		return $this->_errno;
 	}

 	function errdt() {
 		return $this->_errdt;
 	}
 }

