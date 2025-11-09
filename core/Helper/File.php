<?php
namespace Helper;
/**
 * File
 *
 */
class File {
	/*
	 * Path of file.
	 * Ex: /path/to/file/
	 */
	public $path;
	/*
	 * Full path of file (path + filename).
	 * Ex: /path/to/file/filename.extension
	 */
	public $full_path;
	/*
	 * Name of file without extension.
	 * Ex: filename
	 */
	public $name;
	/*
	 * Name of file with extension.
	 * Ex: filename.extension
	 */
	public $full_name;
	/*
	 * Extension of file.
	 * Ex: extension
	 */
	public $extension;
	/*
	 * Size of file in Bytes.
	 */
	public $size;
	/*
	 * Human readable file size.
	 * Ex: 3.2Mio
	 */
	public $human_readable_size;
	
	public function __construct($file_path = ''){
		if(!empty($file_path)){
			$this->load($file_path);
		}
	}
	
	private function load($file_path){
		$path_info = pathinfo($file_path);
		$this->path = $path_info['dirname'].'/';
		$this->full_path = $file_path;
		$this->name = $path_info['filename'];
		$this->full_name = $path_info['basename'];
		$this->extension = $path_info['extension'] ?? null;
		$this->size = filesize($file_path);
		$this->human_readable_size = self::get_human_readable_filesize($this->size);
	}
	
	public static function get_human_readable_filesize($bytes, $decimals = 2) {
		// FROM: http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
		$size = array('o','Kio','Mio','Gio','Tio','Pio','Eio','Zio','Yio');
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . " " .@$size[$factor];
	}
	
	public static function get_computer_readable_filesize($value, $size_name) {
		$size_name = strtolower($size_name);
		switch($size_name){
			case 'ko':
			case 'kio':
				$factor = 1;
				break;
			case 'mo':
			case 'mio':
				$factor = 2;
				break;
			case 'go':
			case 'gio':
				$factor = 3;
				break;
			case 'to':
			case 'tio':
				$factor = 4;
				break;
			case 'po':
			case 'pio':
				$factor = 5;
				break;
			case 'eo':
			case 'eio':
				$factor = 6;
				break;
			case 'zo':
			case 'zio':
				$factor = 7;
				break;
			case 'yo':
			case 'yio':
				$factor = 8;
				break;
			case 'o':
			default:
				$factor = 0;
				break;
		}
		return $value * pow(1024, $factor);
	}
	
	/*
	 * Callback function to use with usort / uasort.
	 * Compare file by size in ascending order.
	 *
	 * return int -1, 0 or 1
	 */
	final static public function compare_by_size_ascending($a,$b) {
		$rtn = 1;
		
		if($a->size > $b->size){
			$rtn = 1;
		} elseif($a->size < $b->size){
			$rtn = -1;
		}
		
		return $rtn;
	}
	
	/*
	 * Callback function to use with usort / uasort.
	 * Compare file by size in descending order.
	 *
	 * return int -1, 0 or 1
	 */
	final static public function compare_by_size_descending($a,$b) {
		$rtn = 1;
		
		if($a->size < $b->size){
			$rtn = 1;
		} elseif($a->size > $b->size){
			$rtn = -1;
		}
		
		return $rtn;
	}
}
