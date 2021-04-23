<?php
/**
 *	@func memcached 缓存 百度云空间Memcached
 *	@author midoks
 *	@link midoks.cachecha.com
 */
class BaeMcCache{

	public $cfg = array(
		'cacheid'=>'euandhnwDycznzlnCblG',
		'host' => 'cache.duapp.com',
		'port' => 20243,
		'user' => 'RFfiWYPrPrGQktVRoD6GozeI',
		'pwd' =>'9pm6ONa1II0fgNAgQEzIAf8acfOOhUCg',
		'timeout'=> 30,
		'time'	=> '',	//保存时间特殊设置
	
	);				//$cfg配置
	private $link;	//连接的资源

	public function __construct(){
		require_once(CACHE_PATH.'api/CacheSdk/BaeMemcache.class.php');
		$this->link = new BaeMemcache($this->cfg['cacheid'],
							$this->cfg['host'].':'.$this->cfg['port'],
							$this->cfg['user'],
							$this->cfg['pwd']);

	}
	/**
	 *	@func 向memcache服务器中,插入值
	 *	@param $key key值
	 *	@param $value value值
	 *	@return 返回
	 */
	public function write($key, $value){
		if(!empty($this->cfg['time']))
			$bool = $this->link->set($key, $value, $this->cfg['time']);
		$bool = $this->link->set($key, $value, $this->cfg['timeout']);
		return $bool;
	}

	/**
	 * @func 向memcache服务器中,获取值
	 * @param $key key值
	 * @ret
	 */
	public function read($key){
		$data = $this->link->get($key);
		if(!is_array($data)){
			return false;
		}
		return $data;
	}

	/**
	 *	@func 在memcache服务器中删除一个元素
	 *	@param string $key 要删除的key值
	 */
	public function delete($key){
		return $this->link->delete($key);
	}

	//@func 清空memcache中所有数据
	public function flush(){}

	//清空过期数据
	public function flush_expire(){}
}
?>
