<?php
/**
 *	@func wp-cache-db 替换db类
 *	@author midoks
 *	@link midoks.cachecha.com
 */
//必须的配置
defined( 'EZSQL_VERSION') or define( 'EZSQL_VERSION', 'WP1.25' );
defined( 'OBJECT') or define( 'OBJECT', 'OBJECT', true );
defined( 'OBJECT_K') or define( 'OBJECT_K', 'OBJECT_K' );
defined( 'ARRAY_A') or define( 'ARRAY_A', 'ARRAY_A' );
defined( 'ARRAY_N') or define( 'ARRAY_N', 'ARRAY_N' );

// --- 缓存设置 start ---
//插件地址
if ( !defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins' );
}
//缓存插件地址
if ( !defined( 'CACHE_PATH' ) ) {
	define( 'CACHE_PATH', WP_PLUGIN_DIR.'/wp-cache-db/' );
}
//如果我们有所需的功能
if ( !function_exists('is_multisite')){
	function is_multisite() {
		return false;
	}
}
// --- 缓存设置 end ---
require_once CACHE_PATH.'cache.class.php';
$GLOBALS['wpdb'] = new DbCache( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
