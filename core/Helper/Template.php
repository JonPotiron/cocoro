<?php
namespace Helper;
class Template {
	public static function parse_template(string $template_path, array $vars): string
	{
		$content = file_get_contents(ROOT_PATH.'template/'.$template_path);
		if($content === false){
			exit($template_path.' template file not found');
		}
		$vars = array_merge(
			[
				'HOST' => HOST,
				'LOGO_CONTENT' => file_get_contents(ROOT_PATH.'public/img/cocoro_logo.svg'),
			],
			$vars
		);
		foreach($vars as $var_key => $var_value){
			$content = preg_replace('/{'.preg_quote($var_key).'}/',$var_value,$content);
		}
		return $content;
	}
}