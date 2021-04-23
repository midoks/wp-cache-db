<?php
/**
 *    @func memcached 缓存
 *    @author midoks
 *    @link midoks.cachecha.com
 */
class LocalMcCache {

    public $cfg = array(
        'host'    => '127.0.0.1',
        'port'    => 11211,
        'timeout' => 30,
        'time'    => '300', //保存时间特殊设置

    ); //$cfg配置
    private $link; //连接的资源

    public function __construct() {

        $this->link = new Memcached();

        $this->connect();
    }

    public function __destruct() {
        if ($this->link) {
            // $this->link->close();
        }
    }

    //@func 连接memcache服务器
    public function connect() {
        $cfg = $this->cfg;
        // var_dump($cfg);
        $this->link->addServer($cfg['host'], $cfg['port']);
    }

    /**
     *    @func 向memcache服务器中,插入值
     *    @param $key key值
     *    @param $value value值
     *    @return 返回
     */
    public function write($key, $value) {
        //var_dump($value);
        //$value = json_encode($value);
        //echo "<pre>";
        // var_dump($this->cfg);
        //var_dump($value);
        //var_dump(strlen($value));
        if (!empty($this->cfg['time'])) {
            $bool = $this->link->set($key, $value, $this->cfg['time']);
        }

        $bool = $this->link->set($key, $value, $this->cfg['timeout']);
        //var_dump($bool);
        return $bool;
    }

    /**
     * @func 向memcache服务器中,获取值
     * @param $key key值
     * @ret
     */
    public function read($key) {
        $data = $this->link->get($key);
        if (!is_array($data)) {
            return false;
        }
        return $data;
    }

    /**
     *    @func 在memcache服务器中删除一个元素
     *    @param string $key 要删除的key值
     */
    public function delete($key) {
        return $this->link->delete($key);
    }

    //@func 清空memcache中所有数据
    public function flush() {
        $b = $this->link->flush();
        return $b;
    }

    //清空过期数据
    public function flush_expire() {
        return $this->link->flush();
    }
}
?>
