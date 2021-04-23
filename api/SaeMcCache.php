<?php
/**
*	@func SaeKv 新浪云MemCache(键值保存)
 *	@author midoks
 *	@blog midoks.cachecha.com
 */
class SaeMcCache{

	public $cfg = array();

	public $linkID = null;
	public $linkSign = false;

	//构造函数
	public function __construct(){
		$this->linkID = memcache_init();
		if($this->linkID == false){
			//echo 'SAE memcache init fail !!!';
		}else{
			$this->linkSign = true;
		}
	}

	//析构函数
	public function __destruct(){}

	/**
	 * @func 向SaeKv中写入值
	 * @param $key key值
	 * @param $value value值
	 */
	public function write($key, $value){
		/*static $c;
		++$c;
		echo '执行了多少次写..'.$c;	
		*/
		//$stat = Memcache::getStats();
		//var_dump($stat);
		if($this->linkSign){
			$time = $this->cfg['timeout'];
			$value = base64_encode(serialize($value));
			//var_dump(strlen($value));
			$b = memcache_set($this->linkID, $key , $value, $time);
			return $b;
		}
		return false;
	}

	/**
	 *	@func 向SaeKv中获取值
	 *	@param $key key值
	 */
	public function read($key){
		/*static $c;
		++$c;
		echo '执行了多少次读..'.$c;*/	
		//var_dump($key);
		if($this->linkSign){
			$value = memcache_get($this->linkID, $key);
			if($value){
				$value = unserialize(base64_decode($value));
				return $value;
			}
			return false;
		}
		return false;
	}

	/**
	 * @func 删除元素
	 */
	public function delete($key){}

	//清空所有元素
	public function flush(){}

	//清空过期元素
	public function flush_expire(){}

}
?>
