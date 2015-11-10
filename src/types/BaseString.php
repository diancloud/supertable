<?php
/**
 * Type BaseString
 *
 * CLASS 
 *
 * 	   BaseString 
 *
 * USEAGE: 
 *
 *   $table = new YourSuperTable(...);
 *   
 *   $TypeInt = $table->type('BaseString');
 *
 * 	 参数表:
 * 	 
 *   $TypeInt = $table->type('BaseString',[
 *   	'screen_name' => '数量',  // 字段显示名称 ( 默认生成 BaseInt_198232381 字符串 ) 
 *   	'maxlength' => 500, // 字符串长度大值, 默认 200
 *   	'minlength' => 20,  // 字符串长度最小值,  默认 0
 *   	'default' => 10,  // 字段默认数值  默认为空
 *
 * 		'placeholder' => '请填写数量', // 字段 PlaceHolder 默认值 SuperTable BaseString (string)
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

class BaseString extends Type {

	function __construct( $opts = array() ) {
		
		// FORMINPUT DATA
		$opts['maxlength'] = (isset($opts['maxlength']))? $opts['maxlength'] : 200; 
		$opts['minlength'] = (isset($opts['minlength']))? $opts['minlength'] : 0;
		$opts['default'] = (isset($opts['default']))? $opts['default'] : "";
		$opts['placeholder'] = (isset($opts['placeholder']))? $opts['placeholder'] : 'SuperTable BaseString (string)';	

		// OPTINONS 
		$opts['screen_name'] = (isset($opts['screen_name']))? $opts['screen_name'] : 'BaseString_' . time() . rand(100000,999999);
		$opts['required'] = (isset($opts['required']))? $opts['required'] : 0;
		$opts['searchable'] = (isset($opts['searchable']))? $opts['searchable'] : 1;
		$opts['summary'] = (isset($opts['summary']))? $opts['summary'] : 0;
		$opts['unique'] = (isset($opts['unique']))? $opts['unique'] : 0;
		$opts['order'] = (isset($opts['order']))? $opts['order'] : 1;
		$opts['hidden'] = (isset($opts['hidden']))? $opts['hidden'] : 1;
		
		$option = [
			'screen_name' => $opts['screen_name'] ,
		 	'required' => $opts['required'],
		 	'summary' => $opts['summary'],
		 	'searchable' => $opts['searchable'],
		 	'unique' => $opts['unique'],
		 	'order' => $opts['order'],
		];

		$data = [
			'maxvalue' => $opts['maxlength'],
			'minvalue' => $opts['minlength'],
			'default' => $opts['default'],
			'placeholder' => $opts['placeholder'],
		];

		$data_input = [
			'maxvalue' => [
				'screen_name' => '最多字数',
				'placeholder' => '最多可以输入字数',
				'input_type' => 'text',
				'default' => 9223372036854775807,
				'validation' =>[
					'required' => false,
					'digits'=>true,
					'min'=>0,
					'max'=>9223372036854775807,
				],
				'message' => [
					'digits' => '{screen_name}({name})格式不正确，请输入数字',
					'min' => '{screen_name}({name})数值不能少于{value}',
					'max' => '{screen_name}({name})数值不能大于{value}',
				],
			],

			'minvalue' => [
				'screen_name' => '最少字数',
				'placeholder' => '至少输入字数',
				'input_type' => 'text',
				'default' => 0,
				'validation' =>[
					'required' => false,
					'digits'=>true,
					'min'=>0,
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
					'minwlength'=>'{minvalue}.value',
					'maxwlength'=>'{maxvalue}.value',
				],

				'message' => [
					'maxwlength' => '{screen_name}({name})不能超过{value}字',
					'minwlength' => '{screen_name}({name})不能少于{value}个字',
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
			'min' => '{screen_name}至少输入{minvalue}个字',
			'max' => '{screen_name}最多输入{maxvalue}个字',
		];

		parent::__construct( $data, $option );
		$this->setDataInput( $data_input );
		$this->setDataMessage( $data_message );
		$this->setDataFormat('string');
	}


	/**
	 * 重载数据校验函数
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function validation( & $value ) {
		
		$this->cleanError();
		$name = $this->_option['screen_name'];
		$rule = [
			"$name" => [
				'validation' => [
					'required' => $this->_option['required'],
					'type' => ['string','numeric'],
					'minwlength' => $this->_data['minvalue'],
					'maxwlength' => $this->_data['maxvalue'],
				],

				'message' => [
					'required' => $this->_message('required', ['screen_name'=>$name]),
					'type' => $this->_message('type', ['screen_name'=>$name]),
					'minwlength' => $this->_message('min', [
						'screen_name'=>$name,
						'minvalue'=>$this->_data['minvalue'],
					]),
					'maxwlength' => $this->_message('max', [
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