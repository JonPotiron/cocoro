<?php
namespace Helper;

class FileSystemIO {
	/**
	 * Get files in $dir folder(s)
	 * Ex: get_files_in_dir("{includes/*.php,core/*.php}");
	 */
	public static function get_files_in_dir(
		string $dir,
		bool $recursive = false,
		string $sort = 'asc'
	): array
	{
		$result = [];
		foreach (glob($dir,GLOB_BRACE) as $file_path) {
			if(is_file($file_path)){
				$file = new File($file_path);
				$result[] = $file;
			} elseif($recursive && is_dir($file_path)){
				$dirs = self::get_folders_in_dir($file_path.'*');
				foreach($dirs AS $sub_dir){
					$result = array_merge($result,self::get_files_in_dir($sub_dir->full_path.'/*',true));
				}
			}
		}
		if($sort == 'asc'){
			usort($result,'\Helper\FileSystemIO::compare_file_by_name_asc');
		} else {
			usort($result,'\Helper\FileSystemIO::compare_file_by_name_desc');
		}
		
		return $result;
	}

	public static function get_folders_in_dir(string $dir,string $sort = 'asc'): array
	{
		$result = [];
		foreach (glob($dir,GLOB_ONLYDIR) as $file_path){
			if(is_dir($file_path)){
				$file = new File($file_path);
				$result[] = $file;
			}
		}
		if($sort == 'asc'){
			usort($result,'\Helper\FileSystemIO::compare_file_by_name_asc');
		} else {
			usort($result,'\Helper\FileSystemIO::compare_file_by_name_desc');
		}

		return $result;
	}
	
	public static function delete_file(string $file_path): bool
	{
		return unlink($file_path);
	}
	
	/**
	 * Recursively remove a directory.
	 */
	public static function delete_dir(string $dir): void
	{
		$dir_search = preg_replace('/(\/)?$/','',$dir);
		foreach(glob($dir_search.'/*',GLOB_BRACE) AS $path) {
			if(is_file($path)){
				unlink($path);
			} elseif(is_dir($path)){
				self::delete_dir($path);
			}
		}
		if(is_dir($dir)){
			rmdir($dir);
		}
	}
	
	/**
	 * Save uploaded file with new name and automatically finds extension.
	 *
	 * @param array $uploaded_file_info What is retreived from $_FILES
	 * @param string $file_path Path to save (must end with "/")
	 * @param string $file_name New file name, extension will be copied from original. NULL to use original name.
	 *
	 * @return string Full file path or empty string if something went wrong
	 *
	 */
	public static function save_uploaded_file(
		array $uploaded_file_info,
		string $file_path,
		?string $file_name = null
	): string
	{
		$file_name = is_null($file_name) ? $uploaded_file_info['name'] : $file_name;
		// auto find extension if needed
		if(!preg_match('/^.+\.\w{2,4}$/',$file_name)){
			preg_match('/^.+\.(\w{2,4})$/',$uploaded_file_info['name'],$matches);
			$file_name = $file_name.'.'.$matches[1];
		}
		// create dir if needed //
		if(!is_dir($file_path)){
			self::create_unexistant_folders_from_path($file_path);
		}
		if(move_uploaded_file($uploaded_file_info['tmp_name'],$file_path.$file_name)){
			return $file_path.$file_name;
		}
		return '';
	}
	
	public static function create_unexistant_folders_from_path(
		string $path, string $base_path = ROOT_PATH
	): void
	{
		$path_to_check = explode($base_path,$path);
		$path_to_check = $path_to_check[1];
		
		$list_folder_to_check = explode('/',$path_to_check);
		$valid_path = $base_path;
		
		foreach($list_folder_to_check as $folder_name_to_check){
			$current_path_to_check = $valid_path.'/'.$folder_name_to_check;
			$folder_exists = is_dir($current_path_to_check);
			if(!$folder_exists){
				mkdir($current_path_to_check);
				chmod($current_path_to_check,0755);
			}
			$valid_path.= '/'.$folder_name_to_check;
		}
	}
	
	/**
	 * Gets provided dir as a ZipArchive object
	 *
	 * @param string $dir /path/to/dir/ (Please provide trailing slash to prevent file naming errors)
	 * @param string|null $file_path /path/to/file.zip
	 * 
	 * @return boolean
	 *
	 */
	public static function get_dir_as_zip_file(string $dir, ?string $file_path = null): bool
	{
		if(is_dir($dir)){
			if(empty($file_path)){
				$file_path = $dir.basename($dir).'.zip';
			}
			$files = self::get_files_in_dir($dir.'*',true);
			$zip = new \ZipArchive();
			if(!empty($files) && $zip->open($file_path,\ZipArchive::CREATE | \ZipArchive::OVERWRITE)){
				foreach($files AS $file){
					if($file->full_path != $file_path){
						$file_path = $file->full_path;
						$relative_path = substr($file_path,strlen($dir));
						$zip->addFile($file_path,$relative_path);
					}
				}
				$zip->close();
			} else {
				return false;
			}
		} else {
			return false;
		}
		
		return true;
	}

	public static function compare_file_by_name_asc($a,$b) {
		return strcmp($a->full_name,$b->full_name);
	}

	public static function compare_file_by_name_desc($a,$b) {
		return strcmp($b->full_name,$a->full_name);
	}
}
