<?php
/**
 * SuperTable 列表类
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



/**
 * Item List
 */
class Items {

	private $list = array();
	private $_pages = array();
	private $_total = null;
	private $_page = null;
	private $_perpage = null;
	private $_totalpage = null;
	private $_offset = null;
	
	function __construct() {
	}


	/**
	 * 记录集分页
	 * @param  [int] $page    当前页码 ( 1... n )
	 * @param  [int] $perpage 每页记录数量
	 * @param  [int] $total   记录总量
	 * @return [string] LIMIT SQL字符串  eg: LIMIT 3,3
	 */
	function pagination( $page, $perpage, $total )  {

		$this->_total = $total;
		$this->_perpage = $perpage;
		$this->_totalpage = ceil( $total / $perpage );

		if ( $page > $total )  {
			$this->_page = $total;
		} 

		if ( $page < 1 ) {
			$this->_page = 1;
		} else {
			$this->_page = $page;
		}

		$this->_offset = ($this->_page -1) * $perpage;

		for( $i=1; $i<=$this->_totalpage; $i++ ) {
			array_push($this->_pages, $i);
		}

		return "LIMIT $this->_offset, $perpage ";
	}

	/**
	 * 读取记录集(当前页）
	 * @return [type] [description]
	 */
	function data() {
		return $this->list;
	}


	/**
	 * 遍历ITEM 
	 * @param  [type] $method [description]
	 * @return [type]         [description]
	 */
	function each( $method ) {
		$args = func_get_args();
		foreach ($this->list as $id => $item ) {
			$args[0] = $this->list[$id];
			$this->list[$id] = call_user_func_array( $method, $args );
		}
	}


	/**
	 * 当前页页码
	 * @return [type] [description]
	 */
	function currPage() {
		
		if ( $this->_page === null ) {
			return 1;
		}
		return $this->_page;
	}

	/**
	 * 下一页的页码
	 * @return [type] [description]
	 */
	function nextPage() {
		
		if ( $this->_page === null ) {
			return false;
		}

		$next = $this->_page + 1;
		if ( $next > $this->_totalpage ) return false;
		return $next;
	}

	/**
	 * 上一页页码
	 * @return [type] [description]
	 */
	function prevPage() {
		if ( $this->_page === null ) {
			return false;
		}

		$prev  = $this->_page -1;
		if ( $prev < 1 )  return false;

		return $prev;
	}


	/**
	 * 读取所有页码
	 * @return [type] [description]
	 */
	function pages() {
		if ( $this->_page === null ) {
			return false;
		}

		return $this->_pages;
	}

	/**
	 * 读取每页显示几条记录集
	 */
	function perpage() {
		if ( $this->_page === null ) {
			return false;
		}
		return $this->_perpage; 
	}
	

	/**
	 * 记录集总数
	 * @return [type] [description]
	 */
	function total() {
		
		if ( $this->_total !== null ) {
			return $this->_total;
		}

		return count( $this->list );
	}

	/**
	 * 当前页记录集总数
	 * @return [type] [description]
	 */
	function currTotal() {
		return count($this->list);
	}


	/**
	 * 新增记录
	 * @param  [type] $obj [description]
	 * @return [type]      [description]
	 */
	function push( $obj ) {
		array_push($this->list, $obj );
	}


	function has( $attr, $value ) {
		foreach ($this->list  as $obj ) {
			if ( method_exists($obj, 'get') ) {
				if ( $value == $obj->get($attr) ) {
					return true;
				}
			}
		}
		return false;
	}

	function toArray() {
		$array = array();
		foreach ($this->list as $obj ) {
			if ( method_exists($obj, 'toArray') ) {
				array_push( $array, $obj->toArray() );
			}
		}
		return $array;
	}

	function toJSON() {
		$array = $this->toArray();
		return json_encode( $array );
	}

}