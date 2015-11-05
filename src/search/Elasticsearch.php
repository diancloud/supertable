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




 	function error() {
 		return $this->_error;
 	}


 	function errno() {
 		return $this->_errno;
 	}
 }

