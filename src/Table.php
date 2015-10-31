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

namespace Tuanduimao;
use Tuanduimao\Schema;


/**
 * SuperTable
 */
class Table {
	
	protected $_conf = array(

		"search"=>array(
			"engine"=> "elasticsearch", 
			"options" => array()
		),

		"database" =>array(
			"engine"=> "mysql", 
			"options" => array()
		),

		"path" => array(
			"type" => "",
			"templete" => "",
			"static" => "",
		)
	);

	protected $_schema = null;
	protected $_attrs = array();
	protected $_attrs_ext = array();

	function __construct( $conf = null ) {
		
		$this->_schema = new Schema();
		if ( is_array($conf) ) {
			$this->_conf = $conf;
		}
	}


	//===== 属性操作
	public function get() {
	}

	public function set( $name, $value ) {
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