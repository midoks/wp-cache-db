<?php
/**
 * Plugin Name: DB缓存
 * Description: 保存查询数据
 * Author: midoks
 * Version: 2.0
 * Author URI: http://midoks.cachecha.com/
 */

// --- 缓存设置 start ---
//插件地址
if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

//缓存插件地址
if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', WP_PLUGIN_DIR . '/wp-cache-db/');
}
// --- 缓存设置 end ---

function wp_query_is_cache($bool) {
    if (is_admin()) {
        return false;
    }
    return $bool;
}

function wp_query_is_cache_false($bool) {
    return false;
}

function wp_cache_check_init_api($bool) {
    global $wpdb;
    global $wp_query;

    $request_uri = $_SERVER['REQUEST_URI'];
    preg_match('/^\/wp-json/i', $request_uri, $matchs);
    if (!empty($matchs)) {
        $wpdb->cache_bool = false;
    }
}

add_action('rest_api_init', 'wp_cache_check_init_api');
add_filter('query_is_cache', 'wp_query_is_cache');

include_once 'common.php';
//更新配置
if (isset($_POST['wpcachedb_options']) && $_POST['submit'] == '保存设置') {
    $option['method']         = $_POST['wpcachedb_options']['method']; //保存的方式
    $option['enabled']        = isset($_POST['wpcachedb_options']['enabled']) ? $_POST['wpcachedb_options']['enabled'] : false; //是否支持插件启用
    $option['trigger_update'] = isset($_POST['wpcachedb_options']['trigger_update']) ? $_POST['wpcachedb_options']['trigger_update'] : false; //是否支持触发更新

    if (is_null($option['enabled'])) {
        $option['enabled'] = false;
    }

    if (is_null($option['trigger_update'])) {
        $option['trigger_update'] = false;
    }

    $option['timeout'] = $_POST['wpcachedb_options']['timeout']; //保存的时间(分钟)
    update_wp_db_cache_options('options', $option);
}

if (isset($_POST['submit']) && '清空数据' == $_POST['submit']) {
    wp_db_cache_clean();
} else if (isset($_POST['submit']) && '清空过期数据' == $_POST['submit']) {
    wp_db_cache_clean_expire();
}

//清空数据
function wp_db_cache_clean() {
    global $wpdb;
    $wpdb->wp_db_cache_clean();
}

//清空过期数据
function wp_db_cache_clean_expire() {
    global $wpdb;
    $wpdb->wp_db_cache_clean_expire();
}

class wpcachedb {

    //配置名字
    public $cfg_name = 'options';

    //架构函数|插件初始化
    public function __construct() {
        //初始化插件
        add_action('init', array(&$this, 'init'));
        //初始化后台选项
        add_action('activate_' . plugin_basename(__FILE__), array(&$this, 'wpcachedb_install'));
        //删除配置信息
        add_action('deactivate_' . plugin_basename(__FILE__), array(&$this, 'wpcachedb_uninstall'), 10);
        //$url = plugin_dir_url(__FILE__);
        //后台选项面板初始化
        if (is_admin()) {
            add_action('admin_init', array($this, 'wpcachedb_menu_init'));
            add_filter('plugin_action_links', array($this, 'wpcachedb_action_links'), 10, 2);
            add_action('admin_menu', array($this, 'wpcachedb_menu'));
        }
    }

    //插件初始化
    public function init() {}

    //插件卸载执行
    public function wpcachedb_uninstall() {
        //T(WP_CONTENT_DIR.'/db.php');
        @unlink(WP_CONTENT_DIR . '/db.php');
        delete_wp_db_cache_options($this->cfg_name);
    }

    //初始化后台选项
    public function wpcachedb_install() {
        $option['method']         = 'local'; //保存的方式
        $option['enabled']        = false; //是否支持插件启用
        $option['timeout']        = 5; //保存的时间(分钟)
        $option['trigger_update'] = false; //触发更新
        init_wp_db_cache_options($this->cfg_name, $option);
        @copy(CACHE_PATH . 'db.php', WP_CONTENT_DIR . '/db.php');
    }

    //初始化后台选项设置
    public function wpcachedb_action_links($links, $file) {
        if (basename($file) != basename(plugin_basename(__FILE__))) {
            return $links;
        }
        $settings_link = '<a href="options-general.php?page=wp-cahce-db">设置</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    //点击选项页
    public function wpcachedb_menu() {
        add_options_page('DB缓存',
            'DB缓存',
            'manage_options',
            'wp-cahce-db',
            array($this, 'wpcachedb_menu_config'));
    }

    //面板设置
    public function wpcachedb_menu_config() {
        $options = get_wp_db_cache_options($this->cfg_name);
        echo '<div class="wrap">';
        echo '<h2>' . 'DB缓存设置' . '</h2>';
        echo '<div class="narrow"><form  method="post">';
        settings_fields('wpcachedb');
        do_settings_sections('wpcachedb');
        echo '<p class="submit"><input name="submit" type="submit" class="button-primary" value="保存设置" />';
        echo '<input style="margin-left:10px;" name="submit" type="submit" class="button-primary" value="清空数据" />
		<input style="margin-left:10px;" name="submit" type="submit" class="button-primary" value="清空过期数据" /></p>';
        echo '</form></div></div>';

        $this->readme();
    }

    //面板设置初始化
    public function wpcachedb_menu_init() {
        register_setting('wpcachedb_options', 'wpcachedb_options', 'wpcachedb_options_validate');
        add_settings_section('wpcachedb_main', __('设置', 'sh'), array($this, 'wpcachedb_section'), 'wpcachedb');

        //是否支持
        add_settings_field('enabled', __('是否支持', 'sh'), array($this, 'wpcachedb_enabled'), 'wpcachedb', 'wpcachedb_main');
        //保存的方式
        add_settings_field('method', __('保存的方式', 'sh'), array($this, 'wpcachedb_method'), 'wpcachedb', 'wpcachedb_main');
        //缓存时间
        add_settings_field('timeout', __('超时时间', 'sh'), array($this, 'wpcachedb_timeout'), 'wpcachedb', 'wpcachedb_main');
        //触发更新
        add_settings_field('trigger_update', __('触发更新', 'sh'), array($this, 'wpcachedb_trigger_update'), 'wpcachedb', 'wpcachedb_main');
    }

    public function wpcachedb_section() {
        echo __('<p>Please enter your configure.</p>', 'sh');
    }

    //保存的方式
    public function wpcachedb_method() {
        $options = get_wp_db_cache_options($this->cfg_name);?>
		<select name="wpcachedb_options[method]" id="method" />
			<option value="local" <?php if ('local' == $options['method']) {
            echo "selected='selected'";
        }
        ?>>local</option>
			<option value="localMc" <?php if ('localMc' == $options['method']) {
            echo "selected='selected'";
        }
        ?>>memcached(本地版)</option>
			<option value="baeMc" <?php if ('baeMc' == $options['method']) {
            echo "selected='selected'";
        }
        ?>>memcached(百度云版)</option>
			<option value="saeMc" <?php if ('saeMc' == $options['method']) {
            echo "selected='selected'";
        }
        ?>>Memcache(新浪云版)</option>
		</select><br /><?php
}

    public function wpcachedb_enabled() {
        $options = get_wp_db_cache_options($this->cfg_name);?>
		<input type="checkbox" name="wpcachedb_options[enabled]" id="enabled" value="true" <?php
if ($options['enabled'] == 'true') {echo 'checked="checked"';}?> /><br /><?php
}

    public function wpcachedb_timeout() {
        $options = get_wp_db_cache_options($this->cfg_name);?>
		<input type="text" name="wpcachedb_options[timeout]" id="timeout" value="<?php echo $options['timeout']; ?>" />(minutes)<br /><?php
}

    public function wpcachedb_trigger_update() {
        $options = get_wp_db_cache_options($this->cfg_name);?>
        <input type="checkbox" name="wpcachedb_options[trigger_update]" id="trigger_update" value="true" <?php
if ($options['trigger_update'] == 'true') {echo 'checked="checked"';}?> /><br /><?php
}

    public function readme() {
        echo '<hr/>';
        echo '<p>开启触发更新,可以加长缓存时间。每次更新都自动删除相应表的数据缓存值!<p>';
        echo '<hr/>';
        echo '<p>使用说明:(如果你服务器支持,使用本地化(local))<p>';
        echo '<p>1.local<p>';
        echo '<p>2.memcache<p>';
        echo '<p>3.memcache(百度)<p>';
        echo '<p>4.memcache(新浪)<p>';
        echo '<p>note:如果会PHP,可直接在lib下增加你的扩展!!!<p>';
        echo '<p>note:如果你发现有BUG,请立即通知我!!!<p>';
        echo '<p>事例文件api/expCache.php</p>';
        echo '<hr/>';
        echo '<p>请关注我的博客:<a href="http://www.cachecha.com/" target="_blank">www.cachecha.com</a></p>';
        echo '<p>能为你服务,我感到无限的兴奋</p>';
    }
}
//实例化
$w_p_d = new wpcachedb();
