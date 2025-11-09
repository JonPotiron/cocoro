<?php
namespace Helper;
class Utils {

	public static function remove_punctuation(string $string): string
	{
		return trim(preg_replace('/ +/', ' ', mb_ereg_replace('[^0-9\p{L}]+', ' ', $string)));
	}

	public static function remove_quote(string $string): string
	{
		$rtn = str_replace('"','',$string);
		$rtn = str_replace("'",'',$rtn);
		return $rtn;
	}
	
	public static function remove_space(string $string, string $replacement = '-'): string
	{
		return preg_replace('/\s+/',$replacement,$string);
	}
	
	public static function remove_newline(string $string, string $replacement = ' '): string
	{
		$rtn = preg_replace('/\r?\n|\r/',$replacement,$string);
		return preg_replace('/<br\s?\/?>/',$replacement,$rtn);
	}
	
	public static function replace_diacritics(string $string): string
	{
		return str_replace(
			array(
				'à', 'á', 'â', 'ã', 'ä', 'å', 'æ',
				'ç',
				'ð',
				'è', 'é', 'ê', 'ë',
				'ì', 'í', 'î', 'ï',
				'ñ',
				'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'œ',
				'š', 'ß',
				'ù', 'ú', 'û', 'ü', 'μ',
				'ÿ', 'ý',
				'ž',
				'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ',
				'Ç',
				'Ð',
				'È', 'É', 'Ê', 'Ë',
				'Ì', 'Í', 'Î', 'Ï',
				'Ñ',
				'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Œ',
				'Š',
				'Ù', 'Ú', 'Û', 'Ü',
				'Ÿ', 'Ý', '¥',
				'Ž'
			),
			array(
				'a', 'a', 'a', 'a', 'a', 'a', 'ae',
				'c',
				'd',
				'e', 'e', 'e', 'e',
				'i', 'i', 'i', 'i',
				'n',
				'o', 'o', 'o', 'o', 'o', 'o', 'oe',
				's', 's',
				'u', 'u', 'u', 'u', 'u',
				'y', 'y',
				'z',
				'A', 'A', 'A', 'A', 'A', 'A', 'AE',
				'C',
				'D',
				'E', 'E', 'E', 'E',
				'I', 'I', 'I', 'I',
				'N',
				'O', 'O', 'O', 'O', 'O', 'O', 'OE',
				'S',
				'U', 'U', 'U', 'U',
				'Y', 'Y', 'Y',
				'Z' 
			),$string);
	}
	
	public static function snake_case_transform(string $string): string
	{
		$string = self::remove_punctuation($string);
		$string = self::replace_diacritics($string);
		$string = iconv('UTF-8', 'UTF-8//TRANSLIT//IGNORE', $string);
		$s = array(
			'/[ -]/i',
			'/[^a-zA-Z0-9_-]/'
		);
		$r = array(
			'_',
			''
		);
		$string = preg_replace($s,$r,$string);
		
		return mb_strtolower(preg_replace('/[_]+/','_',$string));
	}
	
	public static function kebab_case_transform(string $string): string
	{
		$string = self::remove_punctuation($string);
		$string = self::replace_diacritics($string);
		$string = iconv('UTF-8', 'UTF-8//TRANSLIT//IGNORE', $string);
		$s = array(
			'/[ -]/i',
			'/[^a-zA-Z0-9_-]/'
		);
		$r = array(
			'-',
			''
		);
		$string = preg_replace($s,$r,$string);
		
		return mb_strtolower(preg_replace('/[-]+/','-',$string));
	}

	public static function get_arithmetic_progression(int $from, int $to, int $difference = 1): array
	{
		$rtn = [];
		if($difference !== 0){
			$min = min($from,$to);
			$max = max($from,$to);
			$common_difference = abs($difference);
			for($i = $min; $i <= $max; $i += $common_difference){
				$rtn[$i] = $i;
			}
			// Sort array if $difference is negative
			if($difference < 0){
				$rtn = array_reverse($rtn);
			}
		}
		
		return $rtn;
	}

	public static function truncate_string(
		string $string,
		int $length = 20,
		string $trim_marker = '&hellip;'
	): string
	{
		return mb_strimwidth($string,0,$length,$trim_marker,'UTF-8');
	}
	
	/**
	 * Gets object property value from string.
	 * You can pass multiple property separated by spaces, the method will make something of the values depending on the first passed property type.
	 * boolean: a && b
	 * integer or float: a + b
	 * string: a b (concatenate with space in-between)
	 * array: array_merge(a,b)
	 * object: merge properties, skip methods and returns an object
	 *
	 * Types different from the first will be ignored ... 
	 *
	 * @param instance $object Object to search property in
	 * @param string $property_as_string Property to find in object (for example: attrib->foo[8][3]->bar). 
	 *
	 * @return mixed Property value
	 *
	 */
	public static function get_property_value_from_string(object $object, string $property_as_string): mixed
	{
		$current_value = $object;
		// finds sub properties
		$sub_properties = explode(' ',$property_as_string);
		if(count($sub_properties) > 1){
			$current_value = null;
			foreach($sub_properties AS $index => $sub_property){
				$sub_value = self::get_property_value_from_string($object, $sub_property);
				$sub_value_type = gettype($sub_value);
				if($index == 0){
					settype($current_value,$sub_value_type);
				}
				$current_value_type = gettype($current_value);
				if($current_value_type == $sub_value_type){
						switch($current_value_type){
						case 'boolean':
							$current_value = $current_value && $sub_value;
							break;
						case 'integer':
						case 'double':
							$current_value = $current_value + $sub_value;
							break;
						case 'string':
							$current_value = $current_value.' '.$sub_value;
							break;
						case 'array':
							$current_value = array_merge($current_value,$sub_value);
							break;
						case 'object':
							$current_value = (object) array_merge((array) $current_value, (array) $sub_value);
							break;
					}
				}
			}
		} else {
			// finds sub objects calls
			$sub_objects = explode('->',$property_as_string);
			foreach($sub_objects AS $sub_object){
				if(preg_match_all('/(\[?[a-zA-Z0-9_-]+\]?)(\((.*)\))?/',$sub_object,$matches)){
					if(is_object($current_value)){
						if(empty($matches[2][0])){
							$array_property = $current_value->{array_shift($matches[1])};
						} else {
							if(empty($matches[3][0])){
								$array_property = $current_value->{array_shift($matches[1])}();
							} else {
								$array_property = $current_value->{array_shift($matches[1])}($matches[3][0]);
							}
						}
						$s = array(
							'[',']'
						);
						$r = array('','');
						foreach($matches[1] AS $key => $match){
							$array_property = $array_property[str_replace($s,$r,$match)] ?? '';
						}
					} else {
						$array_property = null;
					}
					$current_value = $array_property;
				} else {
					if($property_as_string != null){
						$current_value = $current_value->{$property_as_string};
					} else {
						$current_value = '';
					}
				}
			}
		}
		
		return $current_value;
	}

	public static function is_distant_file(string $url):bool
	{
		$context = stream_context_create(array('http'=>array('timeout'=>1)));
		return !(@fopen($url,'r',false,$context) === false);
	}
	
	/**
	 * Format number using locale.
	 *
	 * Note: We force use of mon_decimal_point and mon_thousand_sep instead of decimal_point and thousand_sep since locale is not set for numeric
	 * (we don't want any number to be formated with locale separators)
	 *
	 */
	public static function number_format_locale(
		float $number,
		int $decimals = 2,
		bool $remove_trailing_zeros = false
	): string
	{
		$locale = localeconv();
		$decimal_point = !empty($locale['mon_decimal_point']) ? $locale['mon_decimal_point'] : $locale['decimal_point'];
		$thousand_sep = str_replace("\xa0",' ',$locale['mon_thousands_sep']);
		$rtn = number_format($number,$decimals,$decimal_point,$thousand_sep);
		if($remove_trailing_zeros){
			$rtn = rtrim($rtn,'0');
			$rtn = rtrim($rtn,$decimal_point);
		}
		return $rtn;
	}
	
	/**
	 * Format string case leaving only the first letter as uppercase.
	 *
	 * @param string $string
	 * @param int $first_letter_case (MB_CASE_UPPER, MB_CASE_LOWER, or MB_CASE_TITLE)
	 * @param mixed $other_letters_case (MB_CASE_UPPER, MB_CASE_LOWER, MB_CASE_TITLE, or null for no changes)
	 * 
	 * @return string
	 */
	public static function convert_case_first_letter(
		string $string,
		int $first_letter_case = MB_CASE_UPPER,
		?int $other_letters_case = MB_CASE_LOWER
	){
		$first_part = mb_convert_case(mb_substr($string,0,1),$first_letter_case);
		$second_part = mb_substr($string,1,null);
		if(!is_null($other_letters_case)){
			$second_part = mb_convert_case($second_part,$other_letters_case);
		}
		return $first_part.$second_part;
	}

	public static function get_ip(): string
	{
		if (getenv('HTTP_CLIENT_IP')) {
			$ip = getenv('HTTP_CLIENT_IP');
		} else if(getenv('HTTP_X_FORWARDED_FOR')) {
			$ip = getenv('HTTP_X_FORWARDED_FOR');
			// get first IP if multiple is sent (in case of proxies) //
			$tmp_ip = explode(',', $ip);
			$ip = trim($tmp_ip[0]);
		} else if(getenv('REMOTE_ADDR')) {
			$ip = getenv('REMOTE_ADDR');
		} else {
			$ip = '0';
		}
		return $ip;
	}
	
	public static function round_up(float $number, float $limit = 1): float
	{
		return ceil($number/$limit)*$limit;
	}

	public static final function url_safe_encode(string $base64_string): string
	{
		return strtr($base64_string,'+/=','-._');
	}

	public static final function url_safe_decode(string $string): string
	{
		return strtr($string,'-._','+/=');
	}

	public static final function get_token(string $value): string
	{
		$method = 'AES-256-CBC';
		$iv_length = openssl_cipher_iv_length($method);
		$iv = openssl_random_pseudo_bytes($iv_length);
		$rtn = openssl_encrypt(
			$value.' == ',
			$method,
			md5('baracouyeur'),
			0,
			$iv
		);
		return self::url_safe_encode(bin2hex($iv).$rtn);
	}

	public static final function decrypt_token(string $values_token): string
	{
		$rtn = self::url_safe_decode($values_token);
		$method = 'AES-256-CBC';
		$iv_length = openssl_cipher_iv_length($method);
		$hex_string = mb_substr($rtn,0,(($iv_length*2)),'UTF-8');
		if(ctype_xdigit($hex_string)){
			$iv = hex2bin($hex_string);
			$encrypted_data = mb_substr($rtn,$iv_length*2,mb_strlen($rtn),'UTF-8');
			$rtn = openssl_decrypt(
				$encrypted_data,
				$method,
				md5('baracouyeur'),
				0,
				$iv
			);
			if($rtn !== false){
				$token_info = explode(' == ',$rtn);
				$rtn = $token_info[0];
			}
		}
		
		return $rtn;
	}
	
	
	/**
	 * Get php config param value as byte.
	 * (https://stackoverflow.com/a/2840875)
	 */
	public static function php_param_return_bytes(string $val): float
	{
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last){
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}
	/**
	 * Get max upload file from php config.
	 * (https://stackoverflow.com/a/2840875)
	 */
	public static function php_max_file_upload_in_bytes(): float
	{
	    //select maximum upload size
	    $max_upload = self::php_param_return_bytes(ini_get('upload_max_filesize'));
	    //select post limit
	    $max_post = self::php_param_return_bytes(ini_get('post_max_size'));
	    //select memory limit
	    $memory_limit = self::php_param_return_bytes(ini_get('memory_limit'));
	    // return the smallest of them, this defines the real limit
	    return min($max_upload, $max_post, $memory_limit);
	}
	
	public static function get_human_readable_distance(int $distance_in_meter): string
	{
		if($distance_in_meter < 1000){
			$rtn = round($distance_in_meter).' m';
		} else {
			$rtn = self::number_format_locale($distance_in_meter/1000,2,true).' km';
		}
		
		return $rtn;
	}

	public static function clean_html_node(string $html, array $xpath_filters): string
	{
		// Remove HTML comments
    	$html = preg_replace("~<!--.*?-->~", '', $html);

		// HTML has no node
		if((strlen(strip_tags($html)) > 0) && strlen(strip_tags($html)) == strlen($html)){
			return $html;
		}

		// Set a DOM object to parse and clean tags
		/**
		 * @note
		 * HTML5 are available after php >= 8.4 wityh new dom classes with following code.
		 * Using php 8.2 we must use the old dom parser and just ignore errors :(
		 */
		// PHP >= 8.4
		// $dom = new \Dom\HTMLDocument;
		// $dom->createFromString($html,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
		// $xpath = new \Dom\XPath($dom);
		$dom = new \DOMDocument;
		libxml_use_internal_errors(true);
		// Must use an encoding to prevent default use of ISO-8859-1 (the HTTP/1.1 default character set) :(
		$contentType = '<?xml encoding="utf-8" ?>';
		$dom->loadHTML($contentType.$html,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
		$xpath = new \DOMXPath($dom);
		libxml_clear_errors();
		
		foreach($xpath_filters as $xpath_filter){
			foreach($xpath->evaluate($xpath_filter) as $node) {
				$node->parentNode->removeChild($node);
			}
		}

		return $dom->saveHTML();
	}

	public static function keep_html_node(string $html, array $xpath_filters): string
	{
		// Remove HTML comments
    	$html = preg_replace("~<!--.*?-->~", '', $html);

		// HTML has no node
		if((strlen(strip_tags($html)) > 0) && strlen(strip_tags($html)) == strlen($html)){
			return $html;
		}

		// Set a DOM object to parse and clean tags
		/**
		 * @note
		 * HTML5 are available after php >= 8.4 with new dom classes with following code.
		 * Using php 8.2 we must use the old dom parser and just ignore errors :(
		 */
		// PHP >= 8.4
		// $dom = new \Dom\HTMLDocument;
		// $dom->createFromString($html,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
		// $xpath = new \Dom\XPath($dom);
		$dom = new \DOMDocument;
		libxml_use_internal_errors(true);
		// Must use an encoding to prevent default use of ISO-8859-1 (the HTTP/1.1 default character set) :(
		$contentType = '<?xml encoding="utf-8" ?>';
		$dom->loadHTML($contentType.$html,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
		$xpath = new \DOMXPath($dom);
		libxml_clear_errors();
		
		$nodes_to_keep = [];
		foreach($xpath_filters as $xpath_filter){
			foreach($xpath->evaluate($xpath_filter) as $node) {
				$parent_node = $node;
				while(!empty($parent_node)){
					$nodes_to_keep[] = $parent_node->getNodePath();
					$parent_node = $parent_node->parentNode;
				}
				$nodes_to_keep = array_merge($nodes_to_keep,self::get_node_all_child_path($node));
			}
		}
		
		$nodes_to_keep = array_unique($nodes_to_keep);
		// Remove all nodes not to keep
		foreach($xpath->evaluate('//*') as $node) {
			if(!in_array($node->getNodePath(),$nodes_to_keep)){
				$node->parentNode->removeChild($node);
			}
		}

		return $dom->saveHTML();
	}

	private static function get_node_all_child_path($node)
	{
		$node_list = [];
		foreach($node->childNodes as $child_node){
			$node_list[] = $child_node->getNodePath();
			if($child_node->hasChildNodes()){
				$node_list = array_merge($node_list,self::get_node_all_child_path($child_node));
			}
		}

		return array_unique($node_list);
	}
}
