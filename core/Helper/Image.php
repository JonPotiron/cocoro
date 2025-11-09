<?php
namespace Helper;

if(extension_loaded('imagick')){
	define('IMAGICK_INSTALLED',true);
} else {
	define('IMAGICK_INSTALLED',false);
}

class Image {
	public static $FILE_EXTENSION = array('jpg','png','jpeg','gif','bmp');
	
	public static function is_animated_gif($image_path) {
		if(!($handle = @fopen($image_path, 'rb'))){
			return false;
		}
		$count = 0;
		//an animated gif contains multiple "frames", with each frame having a 
		//header made up of:
		// * a static 4-byte sequence (\x00\x21\xF9\x04)
		// * 4 variable bytes
		// * a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)
		
		// We read through the file til we reach the end of the file, or we've found 
		// at least 2 frame headers
		while(!feof($handle) && $count < 2) {
			$chunk = fread($handle, 1024 * 100); //read 100kb at a time
			$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
		}
		
		fclose($handle);
		return $count > 1;
	}
	
	/**
	 * Gets an image object from a source.
	 *
	 * @param string $source_path
	 *
	 * @return object|null Returns Imagick or GD object depending on library (or null if impossible to do)
	 *
	 */
	public static function get_source_object($source_path){
		$source_image = null;
		if(is_file($source_path)){
			if(IMAGICK_INSTALLED){
				$source_image = new \Imagick($source_path);
				//$source_image->setImageInterpolateMethod(\Imagick::INTERPOLATE_BILINEAR);
			} else {
				$image_info = @getimagesize($source_path);
				if($image_info !== false){
					// set parameters from mime type
					switch($image_info[2]){
						case 1:
							$source_image = imagecreatefromgif($source_path);
							break;
						case 2:
							$source_image = imagecreatefromjpeg($source_path);
							break;
						case 3:
							$source_image = imagecreatefrompng($source_path);
							break;
						case 15:
							$source_image = imagecreatefromwbmp($source_path);
							break;
						case 16:
							$source_image = imagecreatefromxbm($source_path);
							break;
					}
					imagepalettetotruecolor($source_image);
				}
			}
		}
		
		return $source_image;
	}

	/**
	 * Rotate an image clockwise.
	 * 
	 * @param object $source_image
	 * @param int $angle
	 * @param string $background_color Color as rgb(r,g,b) format
	 * 
	 * @return object $source_image
	 * 
	 */
	public static function rotate(&$source_image,$angle,$background_color = 'rgb(0,0,0)'){
		if(IMAGICK_INSTALLED){
			$source_image->rotateImage(new \ImagickPixel($background_color),$angle);
		} else {
			$rgb = explode(',',str_replace(['rgb(',')'],'',$background_color));
			$color = imagecolorclosest($source_image,$rgb[0],$rgb[1],$rgb[2]);
			$source_image = imagerotate($source_image,$angle*(-1),$color);
		}
		
		return $source_image;
	}
	
	/**
	 * Crop an image.
	 *
	 * @param object $source_image
	 * @param int $width
	 * @param int $height
	 * @param int $x
	 * @param int $y
	 *
	 * @return object $source_image
	 *
	 */
	public static function crop(&$source_image,$width,$height,$x,$y){
		if(IMAGICK_INSTALLED){
			$source_image->cropImage($width,$height,$x,$y);
		} else {
			$crop_info =
				[
					'x' => $x,
					'y' => $y,
					'width' => $width,
					'height' => $height
				];
			$source_image = imagecrop($source_image,$crop_info);
		}
		
		return $source_image;
	}
	
	/**
	 * Resize (scale) an image.
	 *
	 * @param object $source_image
	 * @param int $width
	 * @param int $height
	 * @param boolean $boxed Set to True to keep ratio and restrain image to width x height box
	 *
	 * @return object $source_image
	 *
	 */
	public static function scale(&$source_image,$width,$height,$boxed = true){
		if(IMAGICK_INSTALLED){
			$source_image->thumbnailImage($width,$height,$boxed);
			$source_image->sharpenimage(0.25,0.25);
		} else {
			$new_width = $width;
			$new_height = $height;
			if($boxed){
				// CALCULATE HEIGHT AND WIDTH //
				$source_width = imagesx($source_image);
				$source_height = imagesy($source_image);
				$source_ratio = $source_width/$source_height;
				if($source_ratio > 1){
					$new_height = $new_width/$ratio;
				} else {
					$new_width = $new_height*$ratio;
				}
			}
			$source_image = imagescale($source_image,$new_width,$new_height,IMG_BILINEAR_FIXED);
		}
		
		return $source_image;
	}
	
	/**
	 * Save an image to file.
	 *
	 * @param object $source_image
	 * @param string $destination_path
	 *
	 * @return object $source_image
	 *
	 */
	public static function save(&$source_image,$destination_path){
		if(IMAGICK_INSTALLED){
			//$source_image->posterizeImage(136,false); // TOO SLOW ON PROD (???) AND NOT SO USEFULL :( //
			$source_image->transformimagecolorspace(\Imagick::COLORSPACE_SRGB);
			$source_image->setInterlaceScheme(\Imagick::INTERLACE_PLANE); // PROGRESSIVE JPG IS USELESS WITH LAZY LOADING //
			$source_image->setImageCompression(\Imagick::COMPRESSION_JPEG);
			$source_image->setImageCompressionQuality(82);
			$extension = preg_match('/^.+\.(\w+)$/',$destination_path,$match);
			if(in_array($match[1],['jpg','jpeg'])){
				$source_image->setImageProperty('jpeg:sampling-factor','4:2:0'); 
				$source_image->setImageFormat('jpg');
			}
			$source_image->stripImage();
			$source_image->writeImage($destination_path);
		} else {
			// GET EXTENSION //
			if(preg_match('/^.+\.(\w+)$/',$destination_path,$match)){
				$need_alpha = false;
				$third_parameter = '';
				// set parameters from mime type
				switch($match[1]){
					case 'gif':
						$saving_function = 'imagegif';
						break;
					case 'jpg':
					case 'jpeg':
						$saving_function = 'imagejpeg';
						$third_parameter = '80';
						break;
					case 'png':
						$saving_function = 'imagepng';
						$third_parameter = '9';
						$need_alpha = true;
						break;
					case 'bmp':
						$saving_function = 'imagewbmp';
						break;
					case 'xbm':
						$saving_function = 'imagexbm';
						break;
					default:
						break;
				}
				/*
				 * @todo Need to be further tested.
				 */
				//if($need_alpha){
				//	imagealphablending($source_image,false);
				//	imagesavealpha($source_image,true);
				//	$transparent = imagecolorallocatealpha($source_image, 255, 255, 255, 127);
				//	imagefilledrectangle($source_image, 0, 0, $new_width, $new_height, $transparent);
				//}
				imageantialias($source_image,true);
				imageinterlace($source_image,1);
				if(!empty($third_parameter)){
					$saving_function($source_image,$destination_path,$third_parameter);
				} else {
					$saving_function($source_image,$destination_path);
				}
			}
		}
		
		return $source_image;
	}
	
	/**
	 * Destroy an image ressource.
	 *
	 * @param object $source_image
	 *
	 * @return void
	 *
	 */
	public static function destroy(&$source_image){
		if(IMAGICK_INSTALLED){
			$source_image->clear();
		} else {
			imagedestroy($source_image);
		}
	}
	
	public static function get_pixel_color($source_image,$x,$y){
		if(IMAGICK_INSTALLED){
			$pixel = $source_image->getImagePixelColor($x,$y);
			$color = $pixel->getColor();
		} else {
			$rgb_index = imagecolorat($source_image,$x,$y);
			$color = imagecolorsforindex($source_image,$rgb_index);
			$color = array(
				'r' => $color['red'],
				'g' => $color['green'],
				'b' => $color['blue'],
				'a' => 1-$color['alpha']
			);
		}
		
		return $color;
	}
	
	public static function remove_image_extension($file_name){
		$rtn = preg_replace('/\.('.implode('|',self::$FILE_EXTENSION).')$/i','',$file_name);
		
		return $rtn;
	}
	
	/*
	 * Check image file existence.
	 *
	 * @param string $file_path Path to the image file
	 * @param boolean $find_extension Set to true to test for different extensions
	 *
	 * @return mixed image extension if is an image, false otherwise
	 *
	 */
	public static function is_image_file($file_path, $find_extension = false){
		$rtn = false;
		if(!$find_extension){
			if(
				preg_match('/^.+\.('.implode('|',self::$FILE_EXTENSION).')$/i',$file_path,$matches)
				&& (
					is_file($file_path)
					||
					\Helper\Utils::is_distant_file($file_path)
				)
				&& getimagesize($file_path) !== false
			){
				$rtn = $matches[1];
			}
		} else {
			foreach(self::$FILE_EXTENSION AS $image_extension){
				$rtn = self::is_image_file($file_path.'.'.$image_extension);
				if($rtn !== false){
					break;
				}
			}
		}
		
		return $rtn;
	}
	
	/**
	 * Gets the average color of an image file by resizing it to a 1x1 px image.
	 * (thanks to: http://stackoverflow.com/questions/1746530/get-image-color/1746564#1746564)
	 *
	 * @param string $filename Path to file
	 *
	 * @return array [r,g,b,a]
	 *
	 */
	public static function get_average_color($filename){
		$image = self::get_source_object($filename);
		$color = ['r'=>128,'g'=>128,'b'=>128];
		if(!empty($image)){
			self::scale($image,1,1);
			$color = self::get_pixel_color($image,0,0);
			self::destroy($image);
		}
		
		return $color;
	}
	
	/**
	 * Gets rgb values from a hexadecimal color value
	 *
	 * @param string $hex
	 * @return array [r,g,b]
	 *
	 */
	public static function hex_to_rgb($hex){
		$hex = str_replace('#','',$hex);
		if(strlen($hex) == 3) {
			$r = hexdec(substr($hex,0,1).substr($hex,0,1));
			$g = hexdec(substr($hex,1,1).substr($hex,1,1));
			$b = hexdec(substr($hex,2,1).substr($hex,2,1));
		} else {
			$r = hexdec(substr($hex,0,2));
			$g = hexdec(substr($hex,2,2));
			$b = hexdec(substr($hex,4,2));
		}
		return array($r,$g,$b);
	}
	
	/* Gets hsl values from a rgb array
	 *
	 * @param array $rgb [r,g,b]
	 * @return array [h,s,l]
	 *
	 */
	public static function rgb_to_hsl($rgb){
		$r = $rgb[0]/255;
		$g = $rgb[1]/255;
		$b = $rgb[2]/255;
		$max = max($r,$g,$b);
		$min = min($r,$g,$b);
		$h = $s = $l = ($max+$min)/2;
		
		if($max == $min){
			// achromatic
			$h = $s = 0;
		} else {
			$diff = $max-$min;
			$s = $l > 0.5 ? $diff/(2-$max-$min) : $diff/($max+$min);
			switch($max){
				case $r:
					$h = ($g-$b)/$diff+($g < $b ? 6 : 0);
					break;
				case $g:
					$h = ($b-$r)/$diff+2;
					break;
				case $b:
					$h = ($r-$g)/$diff+4;
					break;
			}
			$h /= 6;
		}
		
		return array($h,$s,$l);
	}
	
	/**
	 * Gets the perveived brightness from a rgb array.
	 * (thanks to http://alienryderflex.com/hsp.html)
	 *
	 * @param array[int] $rgb
	 *
	 * @return float Between 0 and 1
	 *
	 */
	public static function rgb_to_perceived_brightness($rgb){
		return (sqrt((0.299*pow($rgb[0],2))+(0.587*pow($rgb[1],2))+(0.114*pow($rgb[2],2)))) / 255;
	}
	
	/*
	 * Tries to get a css "background-position" attribute from filename.
	 * Checks if filename contains positionning (as "center_left" for example) and returns it (as "center left").
	 * Defaults to "center center" if not found.
	 *
	 * @param string $filename
	 * @return string
	 *
	 */
	public static function get_image_positionning_from_filename($filename){
		$position = 'center center';
		if(preg_match('/(left|right|top|bottom|center)_(left|right|top|bottom|center)/',$filename,$matches)){
			$position = $matches[1].' '.$matches[2];
		}
		return $position;
	}
	
	/**
	* Lightens/darkens a given colour (hex format), returning the altered colour in hex format.7
	* @param str $hex Colour as hexadecimal (with or without hash);
	* @percent float $percent Decimal ( 0.2 = lighten by 20%(), -0.4 = darken by 40%() )
	* @return str Lightened/Darkend colour as hexadecimal (with hash);
	*/
	public static function color_luminance( $hex, $percent ) {
		$hex = preg_replace( '/[^0-9a-f]/i', '', $hex );
		$new_hex = '#';
		
		if ( strlen( $hex ) < 6 ) {
			$hex = $hex[0] + $hex[0] + $hex[1] + $hex[1] + $hex[2] + $hex[2];
		}
		
		// convert to decimal and change luminosity
		for ($i = 0; $i < 3; $i++) {
			$dec = hexdec( substr( $hex, $i*2, 2 ) );
			$dec = min( max( 0, $dec + $dec * $percent ), 255 ); 
			$new_hex .= str_pad( dechex( $dec ) , 2, 0, STR_PAD_LEFT );
		}		
		
		return $new_hex;
	}
}
?>
