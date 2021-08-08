<?php
//本地保存目录
define('CACHE_LOCAL', CACHE_PATH . 'cache/');

/**
 *    @func MemCache 数据缓存
 *    @author midoks
 *    @link midoks.cachecha.com
 */
class LocalCache {
    //配置数据
    public $cfg = array();

    //读取数据
    public function read($id) {
        $id = CACHE_LOCAL . $id;
        //文件是否存在
        if (!file_exists($id)) {
            return false;
        }

        //文件是否过期
        if (filemtime($id) + $this->cfg['timeout'] < time()) {
            return false;
        }

        //读取文件
        $handler = fopen($id, 'r+');
        $content = fread($handler, filesize($id));
        fclose($handler);

        $content = json_decode($content);
        if (!is_array($content)) {
            return false;
        }

        return $content;
    }

    //写数据
    public function write($id, $value) {
        $value   = json_encode($value);
        $id      = CACHE_LOCAL . $id;
        $handler = fopen($id, 'w+');
        fwrite($handler, $value);
        fclose($handler);
        return true;
    }

    //删除函数
    public function delete($id) {
        $id = CACHE_LOCAL . $id;
        @unlink($id);
        return true;
    }

    //全部清空
    public function flush() {
        $fh = opendir(CACHE_LOCAL);
        while (($file = readdir($fh)) !== false) {
            //$fn = CACHE_LOCAL.$file;
            //echo $flie;
            $this->delete($file);
        }
        closedir($fh);
    }

    //清空过期内容
    public function flush_expire() {
        $fh = opendir(CACHE_LOCAL);
        while (($file = readdir($fh)) !== false) {
            //echo $flie;
            //检查缓存是否超时.
            if (filemtime($fn) + $this->cfg['timeout'] > time()) {
                $this->delete($file);
            }
        }
        closedir($fh);
    }
}
?>
