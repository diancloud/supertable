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
	
	public $errors = array();

	protected $_cname = '';
	protected $_data_format = 'string';
	protected $_data_input = array();
	protected $_data_message = array();
	protected $_data = array();
	protected $_option = array();
	protected $_value = null;
	protected $_public = array(); // 普通用户可通过工具编辑分类列表

	private $_path;
	private $_instance;

	function __construct( $data=array(), $option=array() ) {
		$this->setData($data);
		$this->setOption( $option );
	}


	public function setData( $data ) {
		$this->_data = $data;
		return $this;
	}

	public function bindField( $schema_id, $field_name ) {

		if (!preg_match('/^([a-zA-Z]{1})([a-zA-Z0-9\_])/', $field_name) ) {
			throw new Exception("字段名称不正确，由字符、数字和下划线组成，且开头必须为字符。(column_name= $column_name) ");
		}
		$this->_option['schema_id'] = $schema_id;
		$this->_option['field_name'] = $field_name;
		$this->_option['column_name'] = $field_name;
		
		return $this;
	}

	public function option( $name ) {
		if ( isset($this->_option[$name])) {
			return $this->_option[$name];
		}
		return null;
	}

	public function data( $name ) {
		if ( isset($this->_data[$name])) {
			return $this->_data[$name];
		}
		return null;
	}

	public function get( $name ) {

		if ( isset($this->_data[$name])) {
			return $this->_data[$name];
		}

		if ( isset($this->_option[$name])) {
			return $this->_option[$name];
		}

		return null;
	}


	public function setOption( $option ) {
		$this->_option = $option;
		return $this;
	}

	protected function setDataInput( $data_input ) {
		$this->_data_input = $data_input;
		return $this;
	}

	protected function setDataMessage( $data_message ) {
		$this->_data_message = $data_message;
		return $this;
	}

	protected function setDataFormat( $data_format ) {
		$this->_data_format = $data_format;
	}

	public function isSearchable(){
		if ( $this->_option['searchable'] )  {
			return true;
		}

		if ( $this->_data['searchable'] )  {
			return true;
		}
		return  false;
	}

	public function isRequired(){
		if ( $this->_option['required'] )  {
			return true;
		}

		if ( $this->_data['required'] )  {
			return true;
		}
		return  false;
	}


	public function isHidden(){
		if ( $this->_option['hidden'] )  {
			return true;
		}

		if ( $this->_data['hidden'] )  {
			return true;
		}
		return  false;
	}

	public function order() {
		if ( isset($this->_option['order']) ) {
			return intval($this->_option['order']);
		}
		return 1;
	}

	public function isUnique() {
		if ( $this->_option['unique'] )  {
			return true;
		}

		if ( $this->_data['unique'] )  {
			return true;
		}
		return  false;
	}

	/**
	 * 设定自定义类型路径信息
	 * @param [type] $path [description]
	 */
	public function setPath( $path ) {
		$this->_path = $path;
		return $this;
	}

	public function setPublic( $public_list ) {
		$this->_public = $public_list;
		return $this;
	}

	/**
	 * 载入类型定义类
	 * @param  [type] $name 类型名称 (区分大小写)
	 * @param  [type] $data 自定义数据 ( 表单验证等 )
	 * @param  [type] $option 字段选项
	 * @return [type] $this
	 */
	final public function load( $name, $data, $option ) {

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
		return (new $class_name( $data, $option ))
					->setPath($this->_path)
					->setPublic( $this->_public );
	}


	/**
	 * 数据验证定义 (构建类型时，重载此方法)
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function validation( & $value ) {
		return true;
	}



	/**
	 * JS 验证规则 (构建类型时，重载此方法)
	 * @return [type] [description]
	 */
	public function jsValidation() {
		$rules = [];
		return json_encode($rules);
	}


	/**
	 * 对类型实例数值进行编码 （构建类型时，如需对传入数值解析， 可重载此方法）
	 * 注意: 返回数值必须为字符串，如为数组或对象，JSON encode 后存入
	 * @param [type] $column_value [description]
	 */
	public function valueEncode( $value ) {
		return (string) $value;
	}


	/**
	 * 对类型实例数值进行解码 （构建类型时，如需对传出数值解析， 可重载此方法）
	 * 注意: 返回数值必须为字符串，如为数组或对象，JSON encode 后存入
	 * @param [type] $column_value [description]
	 */
	public function valueDecode( $value ) {
		return $value;
	}


	/**
	 * 设定类型实例数值
	 * @param mix $value 
	 * @return $this
	 */
	final public function setValue( $value ) {
		$this->_value = $this->valueEncode($value);
		return $this;
	}

	/**
	 * 读取类型实例的数值 ( 解码之后的数值 )
	 * @return mix $value
	 */
	final public function getValue() {
		return $this->valueDecode($this->_value);
	}


	// === 页面渲染相关Helper ==========================
	public function renderCreate( $sheet_id, $option ) {
		$tpl = (isset($option['tpl']))? $option['tpl']: $this->getTplFile('column.create');
		$typeName  = @end(@explode('\\', get_class($this)));
		$option['sheet_id'] = $sheet_id;
		$data =[
			'instance' => $option,
			'input'=>$this->_data_input,
			'_data' =>$this->_data,
			'data' => array_merge($this->_data, $this->_option, array('_type'=>$typeName)),
			'option' => $this->_option,
			'type' => $typeName,
			'public' => $this->_public,
			'cname' => $this->_cname,
		];
		$html = $this->_render( $data, $tpl );
		return ['status'=>'success','html'=>$html, 'data'=>$data];
	}

	public function renderUpdate( $sheet_id, $option ) {
		$tpl = (isset($option['tpl']))? $option['tpl']: $this->getTplFile('column.update');
		$typeName  = @end(@explode('\\', get_class($this)));
		$option['sheet_id'] = $sheet_id;
		$data =[
			'instance' => $option,
			'input'=>$this->_data_input,
			'_data' =>$this->_data,
			'data' => array_merge($this->_data, $this->_option, array('_type'=>$typeName)),
			'option' => $this->_option,
			'type' => $typeName,
			'public' => $this->_public,
			'cname' => $this->_cname,
		];

		$html = $this->_render( $data, $tpl );
		return ['status'=>'success','html'=>$html, 'data'=>$data];
	}

	public function renderPreview( $sheet_id, $option ) {
		$tpl = (isset($option['tpl']))? $option['tpl']: $this->getTplFile('column.preview');
		$typeName  = @end(@explode('\\', get_class($this)));
		$option['sheet_id'] = $sheet_id;
		$data =[
			'instance' => $option,
			'input'=>$this->_data_input,
			'_data' =>$this->_data,
			'data' => array_merge($this->_data, $this->_option, array('_type'=>$typeName)),
			'option' => $this->_option,
			'type' => $typeName,
			'cname' => $this->_cname,
		];

		$data['value'] = (isset($data['data']['default']))? $this->valueDecode( $data['data']['default'] ) : "";
		$html = $this->_render( $data, $tpl );
		return ['status'=>'success','html'=>$html, 'data'=>$data];
	}



	public function renderItem( $sheet_id, $field_name, $option ) {
		$templete = (isset($option['templete']))? "{$option['templete']}-item" : null; // 模板名称
		$tpl = (isset($option['tpl']))? "{$option['tpl']}" : null;	// 模板文件地址
		if ( $tpl != null ){
			$file_name = basename($tpl);
			$item_file_name = str_replace('.tpl.html', '-item.tpl.html', $file_name);
			$tpl = str_replace($file_name, $item_file_name, $tpl );
		}
		$tpl = ($tpl != null)? $tpl : $this->getTplFile( $templete );

		if (!file_exists($tpl)  ) {  // 载入默认模板 form-item
			$tpl = $this->getTplFile('form-item');
		}

		$typeName  = @end(@explode('\\', get_class($this)));
		$option['sheet_id'] = $sheet_id;
		$option['fillter'] = (isset($option['fillter']))? $option['fillter'] : [];
		$data =[
			'instance' => $option,
			'input'=>$this->_data_input,
			'_data' =>$this->_data,
			'data' => array_merge($this->_data, $this->_option, array('_type'=>$typeName)),
			'option' => $this->_option,
			'type' => $typeName,
			'cname' => $this->_cname,
			'field' => $field_name,
			'validation' => $this->jsValidation(),
		];


		// 设定当前实例数值
		if ( $this->_value == null ) {
			$data['value'] = (isset($data['data']['default']))? $this->valueDecode( $data['data']['default'] ) : "";
		} else {
			$data['value'] = $this->getValue();
		}


		$html = null;
		if ( file_exists($tpl)) {
			$html = $this->_render( $data, $tpl );
		}

		return ['status'=>'success','html'=>$html, 'data'=>$data];
	}



	public function getTplFile( $name ) {

		$data['_type'] = $class_name = get_class($this);
		$namer = explode('\\', $class_name);
		$view_name = end($namer);
		$view_file =  $this->_path['templete'] . "/$view_name/$name.tpl.html";  // 用户自定义模板路径    eg: /data/my_view/InlineText/$name.tpl.html
		if ( !file_exists($view_file) ) {
			$view_file =  $this->_path['templete'] . "/$name.tpl.html";  // 用户自定义模板路径    eg: /data/my_view/$name.tpl.html
		}
		if ( !file_exists($view_file) ) {
			$view_file = __DIR__ . "/view/$view_name/$name.tpl.html";   // 默认模板路径   eg: <supter_table_root>/view/InlineText/$name.tpl.html
		}

		if ( !file_exists($view_file) ) {
			$view_file = __DIR__ . "/view/$name.tpl.html";   // 默认模板路径   eg: <supter_table_root>/view/$name.tpl.html
		}

		return $view_file;
	}



	/**
	 * 渲染模板
	 * @param  [type] $data [description]
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	protected function _render( $data,  $tpl=null ) {
		$data['_type'] = $class_name = get_class($this);

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
        return $content;
	}


	public function toJSON() {
		
		return json_encode($this->toArray());
	}


	public function toArray() {
		$typeName  = @end(@explode('\\', get_class($this)));
		return array(
			'format' => $this->_data_format,
			'type' => @end(@explode('\\', get_class($this))),
			'option' => $this->_option,
			'_data' =>$this->_data,
			'data' => array_merge($this->_data, $this->_option, array('_type'=>$typeName)),
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

	protected function _message( $name, $data =array() ) {
		$message = $this->_data_message[$name];

		foreach ($data as $key => $val ) {
			$message = str_replace('{'.$key.'}', $val, $message );
		}
		return $message;
	}

	protected function cleanError() {
		$this->errors = array();
	}


}