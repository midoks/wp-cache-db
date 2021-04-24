<?php
/**
 *    @func cache 缓存使用
 *    @func 此文件代替wp-includes/wp-db.php文件
 *    @file:参考文件wp-includes/wp-db.php
 *    @author midoks
 *    @link midoks.cachecha.com
 */
//版本信息
define('PLUGINS_CACHE_VERSION', '1.0');
include_once 'common.php';
require_once 'php-sql-parser/php-sql-parser.php';

///以上的为一些定义
class DbCache extends wpdb {
    // use CacheFunc;

    //数据缓存接口
    public $api_cache = null;

    //默认缓存查询true
    public $cache_bool = true;

    public $parser = null;

    public $group_key = 'wp_cache_db_g_';

    //架构函数|初始化数据
    public function __construct($dbuser, $dbpassword, $dbname, $dbhost) {
        parent::__construct($dbuser, $dbpassword, $dbname, $dbhost);

        $this->parser = new PHPSQLParser();

        if (@include_once (CACHE_PATH . 'apicache.php')) {
            //接口函数
            $this->api_cache = new ApiCache(get_wp_db_cache_options('options'));

            //只缓存前端需要的文件
            if (((defined('WP_ADMIN') && WP_ADMIN) ||
                (defined('DOING_CRON') && DOING_CRON) ||
                (defined('DOING_AJAX') && DOING_AJAX) ||
                strpos($_SERVER['REQUEST_URI'], 'wp-admin') ||
                strpos($_SERVER['REQUEST_URI'], 'wp-login') ||
                strpos($_SERVER['REQUEST_URI'], 'wp-register') ||
                strpos($_SERVER['REQUEST_URI'], 'wp-signup'))) {
                $this->cache_bool = false;
            }

        } else {
            $this->cache_bool = false;
        }

    }

    //架构函数|销毁数据
    public function __destruct() {
        // var_dump($this);
        return true;
    }

    public function is_admin_page() {
        if (is_admin() ||
            strpos($_SERVER['REQUEST_URI'], 'wp-json') ||
            strpos($_SERVER['REQUEST_URI'], 'wp-login') ||
            strpos($_SERVER['REQUEST_URI'], 'wp-register') ||
            strpos($_SERVER['REQUEST_URI'], 'wp-signup')
        ) {
            return true;
        }
        return false;
    }

    public function get_sql_table($sql, $uid = '') {
        // $start     = microtime(true);

        // var_dump($sql);
        $parser    = $this->parser;
        $statement = $parser->parse($sql, false);

        $table_name = [];

        $st_key = array_keys($statement);
        // var_dump($st_key);
        $type = $st_key[0];
        if ($type == "SELECT") {
            // var_dump($statement);
            if (isset($statement['FROM'])) {
                $sk = $statement['FROM'];
                if (!empty($sk)) {
                    foreach ($sk as $option => $expr) {
                        // var_dump($expr);
                        if ($expr['expr_type'] == 'table') {
                            $table_name[] = trim($expr['table'], '`');
                        }
                    }
                }
            }

        } elseif ($type == "UPDATE") {
            $sk = $statement['UPDATE'];
            if (!empty($sk)) {
                foreach ($sk as $option => $expr) {
                    // var_dump($expr);
                    if ($expr['expr_type'] == 'table') {
                        $table_name[] = trim($expr['table'], '`');
                    }
                }
            }
        } elseif ($type == "INSERT") {
            $sk = $statement['INSERT'];
            if (!empty($sk)) {

                foreach ($sk as $option => $expr) {
                    foreach ($expr as $expr_k => $expr_v) {
                        if ($expr_k == 'table') {
                            $table_name[] = trim($expr_v, '`');
                        }
                    }
                }
            }
        } elseif ($type == "DELETE") {
            $sk = $statement['DELETE'];
            if (!empty($sk)) {
                foreach ($sk as $option => $expr) {
                    if ($option == 'TABLES') {
                        $table_name[] = trim($expr[0], '`');
                    }
                }
            }
        }

        // if ($uid == '9ef2ee9b053ddc3b556fe8f0dd55efb3') {
        // print_r($statement);
        // var_dump($table_name);
        // }
        // echo "Parse time complex statement: " . (microtime(true) - $start) . "\n";
        return $table_name;
    }

    public function save_table_key($table, $key) {
        $gk = $this->group_key;
        foreach ($table as $v) {
            $group_key = $gk . $v;

            $list = $this->api_cache->read($group_key);
            if ($list) {
                if (!in_array($key, $list)) {
                    array_push($list, $key);
                    $this->api_cache->write($group_key, $list);
                }

            } else {
                $this->api_cache->write($group_key, [$key]);
            }
        }
    }

    public function clear_table_key($table) {
        $gk = $this->group_key;
        foreach ($table as $v) {
            $group_key = $gk . $v;
            $list      = $this->api_cache->read($group_key);
            if ($list) {
                foreach ($list as $value) {
                    $r = $this->api_cache->delete($value);
                }
                $this->api_cache->delete($group_key);
            }
        }
    }

    //查询
    public function query($query) {
        if (preg_match('/^\s*(create|alter|truncate|drop|insert|delete|update|replace)\s/i', $query)) {

            if (!$this->is_admin_page()) {
                return 1;
            }

            if ($this->api_cache->cfg['trigger_update'] && $this->api_cache->cfg['enabled']) {
                $table = $this->get_sql_table($query);
                $this->clear_table_key($table);
            }

            return parent::query($query);
        } else {

            $this->cache_bool = apply_filters('query_is_cache', $this->cache_bool);

            // if (isset($_GET['debug']) && $_GET['debug'] == 'ok') {
            //     var_dump('query:', $this->cache_bool);
            // }

            //DbCache start
            $cache_select = 'Local'; //默认缓存方式
            // $query_uid    = md5($query);
            $query_uid = hash('md5', $query);

            // var_dump($this->api_cache);

            $cached = false; //默认读取的数据为false && 是否支持DB插件缓存
            if ($this->cache_bool && $this->api_cache->cfg['enabled']) {
                $data = $this->api_cache->read($query_uid);

                if ($data) {
                    if (isset($data['tmp']) && $data['tmp'] == 'k') {
                        $this->last_result = [];
                        return 0;
                    }
                    // var_dump($data);
                    $this->last_result = $data;
                    return count($data);
                }
            }

            if (!$cached && $this->api_cache->cfg['enabled']) {
                $r         = parent::query($query);
                $save_data = $this->last_result;
                if (empty($save_data)) {
                    $save_data['tmp'] = 'k';
                }
                $this->api_cache->write($query_uid, $save_data);

                if ($this->api_cache->cfg['trigger_update']) {
                    $table = $this->get_sql_table($query, $query_uid);
                    $this->save_table_key($table, $query_uid);
                }
                return $r;
            }
            return parent::query($query);
        }
    }

    //弃用代码保留
    public function temp_alias() {
        if ($this->cache_bool) {
            //对各个不同的查询类型拆分
            if (strpos($query, '_options')) {
                //选项相关
                $this->api_cache->set($cache_select, 'option');
            } elseif (strpos($query, '_links')) {
                //友情连接相关
                $this->api_cache->set($cache_select, 'links');
            } elseif (strpos($query, '_terms')) {
                //条件
                $this->api_cache->set($cache_select, 'terms');
            } elseif (strpos($query, '_users')) {
                //用户相关
                $this->api_cache->set($cache_select, 'users');
            } elseif (strpos($query, '_post')) {
                //文章相关
                $this->api_cache->set($cache_select, 'post');
            } elseif (strpos($query, 'JOIN')) {
                //条件连接相关
                $this->api_cache->set($cache_select, 'joins');
            } else {
                //其他
                $this->api_cache->set($cache_select, 'other');
            }
        }
    }

    //清空缓存数据
    public function wp_db_cache_clean() {
        $this->api_cache->flush();
    }

    //清空过期数据
    public function wp_db_cache_clean_expire() {
        $this->api_cache->flush_expire();
    }
}
?>
