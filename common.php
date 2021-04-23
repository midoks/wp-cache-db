<?php
/**
 *	@func wp-cache-db 插件公用方法
 *	@author midoks
 *	@link midoks.cachecha.com
 */

//名字设置
function wp_db_cache_name($name){
	$name = CACHE_PATH.$name.'.conf';
	return $name;
}

/**
 * @func 获取配置
 * @param string $name 配置名称
 * @return array
 */
function get_wp_db_cache_options($name){
	//static $option = array();
	//if(empty($option)){
		$option = json_decode(file_get_contents(wp_db_cache_name($name)), true);
		return $option;
	//}
	//return $option;	
}

/**
 * @func 更新配置
 * @param string $name 配置名称
 * @param string $option 配置选项
 * @return void;
 */
function update_wp_db_cache_options($name, $option){
	return init_wp_db_cache_options($name, $option);
}

/**
 * @func 初始化配置
 * @param string $name 配置名称
 * @param string $option 配置选项
 * @return void;
 */
function init_wp_db_cache_options($name, $option){
	$name = wp_db_cache_name($name);
	file_put_contents($name, json_encode($option));
	return true;
}

/**
 * @func 删除配置
 * @param string $name 配置名称
 * @return void;
 */
function delete_wp_db_cache_options($name){
	@unlink(wp_db_cache_name($name));//删除配置
}


//lzw压缩算法
function wp_db_cache_lzw($str){
	$dic = array_flip(range("\0","\xFF"));//产生一个字典
	$word = "";//字典元素
	$codes = array();//自动生成的字典元素 | 记录位置
	$len = strlen($str);//要压缩的字符串长度
	for($i=0; $i<=$len; ++$i){// <= | 能得到最后的字典元素
		$x = $str[$i];//取一个字符[1B]
		if(strlen($x) && isset($dic[$word.$x])){//字符长度不为空且在字典存在
			$word .= $x;
		}elseif($i){//不存在字典中
			$codes[] = $dic[$word];//保存上一个元素为自动生成的字典元素
			$dic[$word.$x] = count($dic);//保存新元素在字典中 | 如果在字典中存在,就会覆盖原字典 | 但解压时,不影响
			$word = $x;
		}
	}

	//把自动生成元素转化成二进制字符串
	$dic_count = 256;//原来字典数
	$bits = 8;//ceil(log($dic_count,2))二进制多少位
	$rest = 0;//保留值
	$rest_len = 0;//保留值,二进制长度
	$return = '';//返回的值
	foreach($codes as $a=>$code){
		$rest = ($rest << $bits) + $code;//保留值
		$rest_len += $bits;//保留上一值 2进制位数
		/**
		 *	当词典的元素数目超过 1<<$bits 时:
		 *	$bits就增加1
		 */
		$dic_count++;
		if($dic_count > (1 << $bits)){
			$bits++;
		}
		//while循环一次,$rest_len减8
		//$code变为字符
		//保留$code的余值
		while( $rest_len > 7 ){
			$rest_len -= 8;
			$return .= chr($rest >> $rest_len);
			$rest &= (1 << $rest_len) -1;//计算出丢掉的值
		}
	}
	//如果最后一次压缩中,$code有余值并保留$code的余值,否则为空。
	$return .= $rest_len ? chr($rest << (8-$rest_len)) :'';
	return $return;
}

//lzw解压算法
function wp_db_cache_unlzw($bstr){
	/* @func 解压二进制数据 */
	$dic_count = 256;//原字典的长度
	$bits = 8;
	$codes = array();//保留解压后的值
	$rest = 0;
	$rest_len = 0;
	$blen = strlen($bstr);//二进制数据长度
	for($i=0; $i<$blen; ++$i){
		$rest = ($rest << 8) + ord($bstr[$i]);
		$rest_len += 8;
		if( $rest_len >= $bits){
			$rest_len -= $bits;
			$codes[] = $rest >> $rest_len;//解压
			$rest &= (1<< $rest_len) - 1;
			$dic_count++;
			if($dic_count > (1 << $bits)){//当字典每升一个数量级,位数增加1
				$bits++;
			}
		}
	}

	$dic = range("\0", "\xFF");//字典数据 | 与加压时一致
	$return = '';
	foreach($codes as $i=>$code){
		$e = $dic[$code];

		if(!isset($e)){//如果资源元素不存在
			$e = $word.$word[0];
			//echo $e,'这时不在<br>';
		}
		$return .= $e;
		if($i){//当$i=1,开始运行
			$dic[] = $word.$e[0];
		}
		$word = $e;
	}
	
	return $return;
}

//信息调式
function T($value){
	$name = 'qq.text';
	$name = wp_db_cache_name($name);
	file_put_contents($name, $value);
}
?>
