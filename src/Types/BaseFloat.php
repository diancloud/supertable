<?php
/**
 * Type BaseInt
 *
 * CLASS 
 *
 * 	   BaseInt 
 *
 * USEAGE: 
 *
 *   $table = new YourSuperTable(...);
 *   
 *   $TypeNumber = $table->type('BaseInt');
 *
 * 	 参数表:
 * 	 
 *   $TypeNumber = $table->type('BaseNum',[
 *   	'screen_name' => '数量',  // 字段显示名称 ( 默认生成 BaseNum_198232381 字符串 ) 
 *   	'max' => 500, // 最大值, 默认 9223372036854775807
 *   	'min' => 20,  //最小值,  默认 0
 *   	'default' => 10,  // 字段默认数值  默认为空
 *
 * 		'placeholder' => '请填写数量', // 字段 PlaceHolder 默认值 SuperTable BaseInt (Integer)
 * 		'required' => 1,  // 作为必填字段 1 必填 0 非必填 默认 0 
 * 		'searchable' => 0,  // 作为搜索条件 1 搜索 0 不搜素 默认 1
 * 		'summary' => 1,  // 作为摘要数据 1 作为摘要数据 0 不作为摘要数据 默认 0 
 * 		'unique' => 1,  // 是否要求字段唯一  1 唯一 0 不唯一 ( 仅 searchable = 1时生效) 默认 0
 * 		'order' => 1,  // 字段再结构列表中排序 （ hidden=0 时候生效， 数值越大越靠前) 默认为1
 * 		'hidden' => 1,  // 是否在表单中隐藏该字段 1 隐藏 0 不隐藏  默认为1
 *   ]);
 */

Namespace Tuanduimao\Supertable\Types;
use Tuanduimao\Supertable\Type;
use Tuanduimao\Supertable\Validation;
use \Exception as Exception;

class BaseFloat extends Type {

	function __construct( $data = array(), $option=array() ) {
		
		$opts = array_merge($data, $option );

		// FORMINPUT DATA
		$opts['max'] = (isset($opts['max']))? $opts['max'] : 9223372036854775807;
		$opts['min'] = (isset($opts['min']))? $opts['min'] : -9223372036854775807;
		$opts['default'] = (isset($opts['default']))? $opts['default'] : "";
		$opts['placeholder'] = (isset($opts['placeholder']))? $opts['placeholder'] : 'SuperTable BaseFloat';	

		// OPTINONS 
		$opts['screen_name'] = (isset($opts['screen_name']))? $opts['screen_name'] : 'BaseFloat_' . time() . rand(100000,999999);
		$opts['required'] = (isset($opts['required']))? $opts['required'] : 0;
		$opts['searchable'] = (isset($opts['searchable']))? $opts['searchable'] : 1;
		 	$opts['unique'] = (isset($opts['unique']))? $opts['unique'] : 0; // 不可重复 , 0: 可以重复 1: 不能重复
		 	$opts['matchable'] = (isset($opts['matchable']))? $opts['matchable'] : 0; // 匹配模式 , 0:精确匹配 1: 精确匹配
		 	$opts['fulltext'] = (isset($opts['fulltext']))? $opts['fulltext'] : 0; // 全文检索 , 0:不支持全文 1: 支持全文检索

		$opts['summary'] = (isset($opts['summary']))? $opts['summary'] : 0;
		$opts['order'] = (isset($opts['order']))? $opts['order'] : 1;
		$opts['hidden'] = (isset($opts['hidden']))? $opts['hidden'] : 0;
		$opts['hidden_column'] = (isset($opts['hidden_column']))? $opts['hidden_column'] : 1;
		$opts['hidden_data'] = (isset($opts['hidden_data']))? $opts['hidden_data'] : 1;
		$opts['dropable'] = (isset($opts['dropable']))? $opts['dropable'] : 0; // 能否移除 默认为0 不可移除
		$opts['alterable'] = (isset($opts['alterable']))? $opts['alterable'] : 0; // 能否移除 默认为0 不可移除
		$opts['column_name'] = (isset($opts['column_name']))? $opts['column_name'] : "";
		$opts['width'] = (isset($opts['width']))? $opts['width'] : 12;


		$data = [
			'maxvalue' => $opts['max'],
			'minvalue' => $opts['min'],
			'default' => $opts['default'],
			'placeholder' => $opts['placeholder'],
		];

		$data_input = [
			
			'maxvalue' => [
				'screen_name' => '最大值',
				'placeholder' => '最大可以输入的数值',
				'input_type' => 'text',
				'default' => 9223372036854775807,
				'validation' =>[
					'required' => false,
					'digits'=>true,
					'min'=>-9223372036854775807,
					'max'=>9223372036854775807,
				],
				'message' => [
					'digits' => '{screen_name}({name})格式不正确，请输入数字',
					'min' => '{screen_name}({name})数值不能少于{value}',
					'max' => '{screen_name}({name})数值不能大于{value}',
				],
			],

			'minvalue' => [
				'screen_name' => '最小值',
				'placeholder' => '最小可以输入的数值',
				'input_type' => 'text',
				'default' => -9223372036854775807,
				'validation' =>[
					'required' => false,
					'digits'=>true,
					'min'=>-9223372036854775807,
					'max'=>9223372036854775807,
				],
				'message' => [
					'digits' => '{screen_name}({name})格式不正确，请输入数字',
					'min' => '{screen_name}({name})数值不能少于{value}',
					'max' => '{screen_name}({name})数值不能大于{value}',
				],
			],

			'default' => [
				'screen_name' =>  '默认值',
				'placeholder' =>  '填写该字段的默认值',
				'input_type' => 'text',
				'validation' => [
					'required' => false,
					'min'=>'{minvalue}.value',
					'max'=>'{maxvalue}.value',
				],

				'message' => [
					'max' => '{screen_name}({name})数值不能大于{value}',
					'min' => '{screen_name}({name})数值不能少于{value}个字',
				],
			],

			'placeholder' => [
				'screen_name' =>  '填写提示',
				'placeholder' =>  '字段格式的提示信息',
				'input_type' => 'text',
				'validation' => [
					'required' => false,
					'minlength'=>1,
					'maxlength'=>50,
				],

				'message' =>[
					'minlength' => '{screen_name}({name})不能超过{value}个字',
					'maxlength' => '{screen_name}({name})至少输入{value}个字',
				],
			],
		];

		// 错误提示
		$data_message = [
			'required' =>  '请填写{screen_name}',
			'type' => '{screen_name}数据格式不正确',
			'min' => '{screen_name}不能少于{minvalue}',
			'max' => '{screen_name}不能大于{maxvalue}',
		];

		parent::__construct( $data, $opts );
		$this->setDataInput( $data_input );
		$this->setDataMessage( $data_message );
		$this->setDataFormat('float');
	}


	/**
	 * 重载数据校验函数
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function validation( & $value ) {
		
		$this->cleanError();
		$name = isset($this->_data['screen_name']) ? $this->_data['screen_name'] : null;
		$name = ( $name != "" ) ? $name : $this->_option['screen_name'];
		
		$rule = [
			"$name" => [
				'validation' => [
					'required' => $this->_option['required'],
					'type' => 'float',
					'min' => $this->_data['minvalue'],
					'max' => $this->_data['maxvalue'],
				],
				'message' => [
					'required' => $this->_message('required', ['screen_name'=>$name]),
					'type' => $this->_message('type', ['screen_name'=>$name]),
					'min' => $this->_message('min', [
						'screen_name'=>$name,
						'minvalue'=>$this->_data['minvalue'],
					]),
					'max' => $this->_message('max', [
						'screen_name'=>$name,
						'maxvalue'=>$this->_data['maxvalue'],
					]),
				],
			],
		];

		$v = new Validation();
		$valueCheck[$name] = $value;
		if ( $v->check($valueCheck, $rule, $errlist ) == false ) {
			$this->errors = array_merge( $this->errors, $errlist );
			return false;
		}
		
		return true;
	}

}