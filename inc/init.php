<?php

// charset for HTTP requests
ini_set('default_charset', 'UTF-8');
// set separator to &amp; instead of & (W3C)
ini_set('arg_separator.output','&amp;');

// set  header
header('Content-Type: text/html; charset=utf-8');
// set encoding
mb_regex_encoding('UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// session_start();

// define('SUB_URL','');
define('SUB_URL','cocoro/');
define('PROTOCOL_PREFIX',(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://');
define('HOST_MINUS_PROTOCOL',$_SERVER['HTTP_HOST'].'/'.SUB_URL);
define('HOST',PROTOCOL_PREFIX.HOST_MINUS_PROTOCOL);
if(!defined('SERVER_ROOT')){
	define('SERVER_ROOT',preg_replace('/\\'.DIRECTORY_SEPARATOR.'inc$/','',__DIR__));
}
define('ROOT_PATH',SERVER_ROOT.DIRECTORY_SEPARATOR);
// define('ROOT_PATH',dirname(__FILE__).DIRECTORY_SEPARATOR);
define('IS_DEV',$_SERVER['HTTP_HOST'] == 'grodada.localhost');

if(IS_DEV) {
	ini_set('display_errors',1);
	error_reporting(E_ALL);
} else {
	ini_set('display_errors',0);
	error_reporting(E_ALL & ~E_NOTICE);
}

// set include path
$include_path = array(
	ROOT_PATH.'core/'
);
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.implode(PATH_SEPARATOR,$include_path));

// set autoload function
function autoloadcore($class_name) {
	// set namespace as dir path
	$class_path = str_replace('\\','/',$class_name);
	if(stream_resolve_include_path($class_path.'.php')){
		require_once $class_path.'.php';
	}
}
spl_autoload_register('autoloadcore');

// set composer autoload
include_once ROOT_PATH.'/vendor/autoload.php';