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

namespace Tuanduimao\Supertable;
use Tuanduimao\Supertable\Validation;
use \Exception as Exception;



class Type {
	
	protected $_data_input = array();
	protected $_data = array();
	protected $_option = array();
	public $errors = array();

	private $_path;
	private $instance;




	function __construct( $data=array(), $option=array(), $data_input=array() ) {
		$this->setData($data);
		$this->setOption( $option );
		$this->setDataInput( $data_input );
	}


	public function setData( $data ) {
		$this->_data = $data;
	}

	public function setOption( $option ) {
		$this->_option = $option;
	}

	public function setDataInput( $data_input ) {
		$this->_data_input = $data_input;
	}

	/**
	 * 设定自定义类型路径信息
	 * @param [type] $path [description]
	 */
	public function setPath( $path ) {
		$this->_path = $path;
		return $this;
	}

	/**
	 * 载入类型定义类
	 * @param  [type] $name 类型名称 (区分大小写)
	 * @param  [type] $data 自定义数据 ( 表单验证等 )
	 * @param  [type] $option 字段选项
	 * @return [type] $this
	 */
	public function load( $name, $data, $option ) {

		$class_path = $this->_path['type'] . "/$name.php";
		$class_name = "\\Tuanduimao\\Supertable\\Types\\$name";

		// 优先载入用户定义的类型
		if ( file_exists($class_path) ) {
			require_once( $class_path );
			if ( class_exists($class_name) ) { 
				return new $class_name( $data, $option );
			}
		}

		// 载入系统默认类型
		if ( !class_exists($class_name) ) {
			throw new Exception("Type Not Found (class_path=$class_path,  class_name=$class_name or $name ) ");
		}

		// 创建实例
		return new $class_name( $data, $option );
	}


	/**
	 * 渲染代码
	 * @return [type] [description]
	 */
	public function previewHTML( $instance=null, $tpl=null ) {
		if ( $instance == null ) {
			$instance =array(
			 	"name" =>'tmp_'. time() .rand(10000,99999),
			 	'screen_name' => @end(@explode('\\', get_class($this))).'示例',
			 	'value' => null,
			);
		}

		$data = array(
			'data' =>$this->_data,
			'_type' => @end(@explode('\\', get_class($this))),
			'_instance' => $instance,
		);
		return $this->_render( $data, 'preview' );
	}

	public function previewJSON( $instance=null ) {
		if ( $instance == null ) {
			$instance =array(
			 	"name" =>'tmp_'. time() .rand(10000,99999),
			 	'screen_name' => @end(@explode('\\', get_class($this))).'示例',
			 	'value' => null,
			);
		}

		$data = array(
			'data' =>$this->_data,
			'_type' => @end(@explode('\\', get_class($this))),
			'_instance' => $instance,
		);
		return json_encode($data);
	}

	public function inputFormHTML( $instance="", $tpl=null ) {

		if ( $instance == null ) {
			$instance =array(
			 	"name" =>'tmp_'. time() .rand(10000,99999),
			 	'screen_name' => @end(@explode('\\', get_class($this))).'示例',
			 	'value' => null,
			);
		}

		$data = array(
			'input'=>$this->_data_input,
			'data' =>$this->_data,
			'_type' => @end(@explode('\\', get_class($this))),
			'_instance' => $instance,
		);
		return $this->_render( $data, 'form', $tpl );
	}



	public function inputFormJSON( $instance="") {

		if ( $instance == null ) {
			$instance =array(
			 	"name" =>'tmp_'. time() .rand(10000,99999),
			 	'screen_name' => @end(@explode('\\', get_class($this))).'示例',
			 	'value' => null,
			);
		}

		$data = array(
			'input'=>$this->_data_input,
			'data' =>$this->_data,
			'_type' => @end(@explode('\\', get_class($this))),
			'_instance' => $instance,
		);
		return json_encode($data);
	}


	public function inputValidationJSCODE() {
	}

	public function dataValidationJSCODE() {
	}
	

	/**
	 * 渲染模板
	 * @param  [type] $data [description]
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	protected function _render( $data, $name, $tpl=null ) {
		
		$data['_type'] = $class_name = get_class($this);

		$namer = explode('\\', $class_name);
		$view_name = end($namer);

		if ( $tpl != null ) {
			$view_file = $tpl;
			if ( !file_exists($view_file) ) {
				throw new Exception("呈现模板文件不存在! file=$tpl");
			}
		} else {
			$view_file =  $this->_path['templete'] . "/$view_name/$name.tpl.html";
			if ( !file_exists($view_file) ) {
				$view_file = __DIR__ . "/view/$view_name/$name.tpl.html";
			}
		}



		ob_start();

		$html = "";
		@extract( $data );
		if ( file_exists($view_file) ) {
			require( $view_file );
		}
		$content = ob_get_contents();

        ob_clean();
        return $content;
	}


	public function toJSON() {
		
		if ( !$this->_dataInputValidation() ) {
			print_r($this->errors );
		}
		return json_encode($this->toArray());
	}

	public function toArray() {
		return array(
			'name' => $this->_name,
			'type' => get_class($this),
			'option' => $this->_option,
			'data' => $this->_data,
		);
	}

	/**
	 * 验证输入数据是否合法
	 * @return [type] [description]
	 */
	protected function _dataInputValidation() {

		$errflag = false;
		$data = $this->_data;
		$check = new Validation();
		foreach ($this->_data_input as $name => $input ) {

			// 如果非必填字段，且数值为空则跳过验证
			if ( !$input['validation']['required']  && ( $this->_data[$name] === "" || $this->_data[$name] === null ) ) {
				//echo "$name data:= {$this->_data[$name]}\n";
				continue;
			}


			// 验证数据是否合法
			foreach ($input['validation'] as $method => $format ) {
				// echo "\t $name={$this->_data[$name]} $method ".var_export($format,true)." \n";
				$this->_parseFormat( $format );
				if ( !method_exists($check, $method) ) {
					continue;
				}
				if ( $check->$method( $this->_data[$name], $format ) === false ) {
					$errflag = true;
					$message = (isset($input['message'][$method]))?$this->_parseFormatMessage($input['message'][$method], $format, $input, $name):"{$input['screen_name']}格式不正确 ( name=$name )";
					$this->errors[$name][] = array("message"=>$message, 'method'=>$method, 'format'=>$format);
				}
			}
		}

		return !$errflag;
	}

	protected function _parseFormat( & $format ) {
		if( preg_match("/^\{([0-9a-zA-Z_]+)\}\.([0-9a-zA-Z]+)$/", $format, $match) ) {
			//print_r($this->_data);
			$name = $match[1];
			$method = $match[2];
			switch ($method) {
				case 'value':
					if ( isset($this->_data[$name]) ) {
						$format = $this->_data[$name];
					} else {
						$format = false;
					}
					break;
				
				default:
					# code...
					break;
			}
		}

		return false;
	}


	protected function _parseFormatMessage( & $message, $format, $input, $name="" ) {

		$message = str_replace('{value}', $format, $message );
		$message = str_replace('{screen_name}', $input['screen_name'], $message );
		$message = str_replace('{name}', $name, $message );
		return $message;
	}


}