<?php
/**
 *    @func 各种换缓存的接口
 *    @author midoks
 *    @link midoks.cachecha.com
 */

//缓存接口
define('CACHE_API', CACHE_PATH . 'api/');
class ApiCache {

    //默认保存类型
    //public $type = 'Mc';
    //配置数据
    public $cfg = array();
    //缓存是咧
    public $CacheID = null;

    public function __construct($cfg) {
        $this->cfg            = $cfg;
        $this->cfg['timeout'] = $this->cfg['timeout'] * 60;
        $this->init();
    }

    //初始化一些必要的数据
    public function init() {
        $this->config = get_wp_db_cache_options('options');
        //$this->type = ucwords($this->cfg['method']);
        $cn = ucwords($this->cfg['method']) . 'Cache';
        include_once CACHE_API . $cn . '.php';
        //echo $cn;
        $this->CacheID      = new $cn;
        $this->CacheID->cfg = array_merge($this->CacheID->cfg, $this->cfg);
        //var_dump($this->CacheID);

        $this->config['save_link'] = '_l';
        $this->config['save_type'] = '_t';
    }

    /**
     *    @func 设置类型
     *    @param string $type 保存的方式
     *    @param string $table_type 保存表的格式
     */
    public function set($type, $table_type) {
        $this->config['save_link'] = $type;
        $this->config['save_type'] = $table_type;
        //var_dump($type, $table_type);

    }

    //读取数据
    public function read($id) {
        $data = $this->CacheID->read($this->config['save_type'] . '_' . $id);
        if (!$data) {
            return false;
        }
        return $data;
    }

    //写数据
    public function write($id, $value) {
        return $this->CacheID->write($this->config['save_type'] . '_' . $id, $value);
    }

    //删除函数
    public function delete($id) {
        return $this->CacheID->delete($this->config['save_type'] . '_' . $id);
    }

    //全部清空
    public function flush() {
        return $this->CacheID->flush();
    }

    //清空已经过期的数据
    public function flush_expire() {
        return $this->CacheID->flush_expire();
    }
}
?>
