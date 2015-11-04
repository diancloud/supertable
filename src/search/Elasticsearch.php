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

 class Elasticsearch {
 	
 	private $_stor;
 	private $_client=null;

 	private $_bucket;
 	private $_index;
 	private $_option;

 	private $_error = null;


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
 	function createType( $name, $allow_exists=false ) {

 		$index = $this->_index['index'];
 		$type = $this->_index['type'] . $name;
 		$emptyMapping = array(
 			'_source' => array('enabled'=>true),
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

 		// 检查索引是否存在
 		$typeExistsParam = array( 'index'=>$index,  'type'=>$type);
 		if ( $this->_client->indices()->existsType($typeExistsParam) ) {
 			if ( $allow_exists ) {
 				return true;
 			}
 			$this->_error = "Index: $index/$name has exists! ";
 			return false;
 		}


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
 			return false;
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

 		// 删除Mapping ( 有文档时会有问题吗 ？)
 		/* $result = $this->_client->indices()->deleteMapping( array('index'=>$index, 'type'=>$type) );
 		if (!$result['acknowledged']) {
 			$this->_error = "Index: $index/$name remove old Mappings Error! ";
 			return false;
 		} */


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
 				$properties[$field]['type'] = $info['format'];

 				if ( !isset($info['option']['fulltext']) && $info['format'] == 'string' ) {
 					$properties[$field]['index'] = 'not_analyzed';
 				}
 			}
 		}

 		$updateMapping = array(
 			'_source' => array('enabled'=>true),
 			'properties' => $properties
 		);

 		$typeUpdateParam = array(
 			'index'=>$index,
 			'type' =>$type,
 			'ignore_conflicts'=>true,
 			'body' => array(
 				$type =>$updateMapping
 			)
 		);

 		print_r( $typeUpdateParam );
 		

 		$result = $this->_client->indices()->putMapping( $typeUpdateParam );

 		if (!$result['acknowledged']) {
 			return false;
 		}
 		return true;
	}


	function createData( $name, $id, $data ) {

		$index = $this->_index['index'];
 		$type = $this->_index['type'] . $name;

 		$doc = array(
 			'_spt_data_id' =>$id,
 			'_spt_data' => $data,
 		);

 		$doc = array_merge($data, $doc );

 		$docInputParam = array(
 			'index' => $index,
 			'type' => $type,
 			'body' => $doc,
 		);

 		echo "==ES Create Data ======\n";
 		echo "CREATE INDEX: NAME=$name ID=$id \n";
 		print_r($doc);

 		$result = $this->_client->index( $docInputParam );
 		print_r("=====Call END =======");
 		print_r($result);

	}


 	function error() {
 		return $this->_error;
 	}
 }

