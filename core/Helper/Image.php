<?php
namespace Helper;

define('USE_IMAGICK',false);
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
			if(USE_IMAGICK && IMAGICK_INSTALLED){
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
		if(USE_IMAGICK && IMAGICK_INSTALLED){
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
		if(USE_IMAGICK && IMAGICK_INSTALLED){
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
		if(USE_IMAGICK && IMAGICK_INSTALLED){
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
		if(USE_IMAGICK && IMAGICK_INSTALLED){
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
		if(USE_IMAGICK && IMAGICK_INSTALLED){
			$source_image->clear();
		} else {
			imagedestroy($source_image);
		}
	}
	
	public static function get_pixel_color($source_image,$x,$y){
		if(USE_IMAGICK && IMAGICK_INSTALLED){
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
	
	/**
	 * Gets hsl values from a rgb array
	 *
	 * @param array $rgb [r,g,b]
	 * @return array [h,s,l]
	 *
	 */
	public static function rgb_to_hsl(array $rgb): array
	{
		$r = ($rgb['r'] ?? $rgb[0] ?? 0)/255;
		$g = ($rgb['g'] ?? $rgb[1] ?? 0)/255;
		$b = ($rgb['b'] ?? $rgb[2] ?? 0)/255;
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
	 * Dirzetcly from https://stackoverflow.com/a/20440417
	 */
	public static function hsl_to_rgb(array $hsl): array
	{
		$h = ($hsl['h'] ?? $hsl[0] ?? 0) / 360;
		$s = ($hsl['s'] ?? $hsl[1] ?? 0) / 100;
		$l = ($hsl['l'] ?? $hsl[2] ?? 0) / 100;

        $r = $l;
        $g = $l;
        $b = $l;
        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
        if ($v > 0){
			$m = $l + $l - $v;
			$sv = ($v - $m ) / $v;
			$h *= 6.0;
			$sextant = floor($h);
			$fract = $h - $sextant;
			$vsf = $v * $sv * $fract;
			$mid1 = $m + $vsf;
			$mid2 = $v - $vsf;
			switch ($sextant) {
				case 0:
					$r = $v;
					$g = $mid1;
					$b = $m;
					break;
				case 1:
					$r = $mid2;
					$g = $v;
					$b = $m;
					break;
				case 2:
					$r = $m;
					$g = $v;
					$b = $mid1;
					break;
				case 3:
					$r = $m;
					$g = $mid2;
					$b = $v;
					break;
				case 4:
					$r = $mid1;
					$g = $m;
					$b = $v;
					break;
				case 5:
					$r = $v;
					$g = $m;
					$b = $mid2;
					break;
			}
        }
        return array('r' => round($r * 255), 'g' => round($g * 255), 'b' => round($b * 255));
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
	
	/**
	* Lightens/darkens a given colour (hex format), returning the altered colour in hex format.7
	* @param string $hex Colour as hexadecimal (with or without hash);
	* @param float $percent Decimal ( 0.2 = lighten by 20%(), -0.4 = darken by 40%() )
	* @return string Lightened/Darkend colour as hexadecimal (with hash);
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

	public static function generate_github_like_avatar(
		string $string,
		string $destination_path
	): string
	{
		$hash = md5($string);
		$hash_parts = [];
		$min = hexdec('00');
		$max = hexdec('ff');
		while(strlen($hash) > 0 ){
			// transform every 2 bytes in percentage value
			$hash_parts[] = round((hexdec($hash[0].$hash[1]) - $min) / ($max - $min) * 100);
			$hash = substr($hash,2,null);
		}
		$img_width = 110;
		$avatar_width = 50;
		$avatar_offset = ($img_width - $avatar_width) / 2;
		$pixel_width = $avatar_width / 5;
		$color = 'hsl(0,100,50)';
		$background_color = 'hsl(0,100,50)';

		// GET EXTENSION //
		$image_type = 'png';
		if(preg_match('/^.+\.(\w+)$/',$destination_path,$match)){
			$image_type = in_array($match[1], ['png','svg']) ? $match[1] : $image_type;
		}
			
		if(count($hash_parts) == 16){
			$main_hue = round($hash_parts[15]*3.6);
			$has_top_pixel = false;
			$has_bottom_pixel = false;

			$pixel_matrix = [];
			for($i = 0; $i < 15; $i++){
				if($hash_parts[$i] >= 50){
					$y = $i%5;
					if($i < 5){
						// first and fifth col
						$pixel_matrix[] = [
							'x' => (0*$pixel_width)+$avatar_offset,
							'y' => ($y*$pixel_width)+$avatar_offset,
						];
						$pixel_matrix[] = [
							'x' => (4*$pixel_width)+$avatar_offset,
							'y' => ($y*$pixel_width)+$avatar_offset,
						];
					} elseif($i < 10){
						// second and fourth col
						$pixel_matrix[] = [
							'x' => (1*$pixel_width)+$avatar_offset,
							'y' => ($y*$pixel_width)+$avatar_offset,
						];
						$pixel_matrix[] = [
							'x' => (3*$pixel_width)+$avatar_offset,
							'y' => ($y*$pixel_width)+$avatar_offset,
						];
					} else {
						// third (middle) col
						$pixel_matrix[] = [
							'x' => (2*$pixel_width)+$avatar_offset,
							'y' => ($y*$pixel_width)+$avatar_offset,
						];
					}

					$has_top_pixel = $i%5 == 0 ? true : $has_top_pixel;
					$has_bottom_pixel = $i%5 == 4 ? true : $has_bottom_pixel;
				}
			}
			// Add at least one pixel at top and bottom on center col
			if(!$has_top_pixel){
				$pixel_matrix[] = [
					'x' => (2*$pixel_width)+$avatar_offset,
					'y' => (0*$pixel_width)+$avatar_offset,
				];
			}
			if(!$has_bottom_pixel){
				$pixel_matrix[] = [
					'x' => (2*$pixel_width)+$avatar_offset,
					'y' => (4*$pixel_width)+$avatar_offset,
				];
			}


			switch($image_type){
				case 'svg':
					$svg_rect_content = '';
					$color = 'hsl('.$main_hue.',100%,76%)';
					$background_color = 'hsl('.(($main_hue + 45) % 360).',70%,96%)';
					foreach($pixel_matrix as $pixel){
						$svg_rect_content .= '<rect x="'.$pixel['x'].'" y="'.$pixel['y'].'" width="'.$pixel_width.'" height="'.$pixel_width.'" fill="'.$color.'" />';
					}
					$svg = 
						'<?xml version="1.0" standalone="no"?>'
						.'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'
						.'<svg viewBox="0 0 '.$img_width.' '.$img_width.'" width="'.$img_width.'" height="'.$img_width.'" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'
							.'<rect width="100%" height="100%" fill="'.$background_color.'" />'
							.$svg_rect_content
						.'</svg>'
					;
					if(file_put_contents($destination_path,$svg)){
						return $destination_path;
					}
					break;
				case 'png':
				default :
					$png_image = imagecreate($img_width, $img_width);
					$rgb = self::hsl_to_rgb([$main_hue,100,76]);
					$color = imagecolorallocate($png_image, $rgb['r'], $rgb['g'], $rgb['b']);
					$rgb = self::hsl_to_rgb([$main_hue + 45,70,96]);
					$background_color = imagecolorallocate($png_image, $rgb['r'], $rgb['g'], $rgb['b']);
					imagefilltoborder($png_image, 0, 0, $background_color, $background_color);
					foreach($pixel_matrix as $pixel){
						imagefilledrectangle($png_image, $pixel['x'], $pixel['y'], $pixel['x']+$pixel_width, $pixel['y']+$pixel_width, $color);
					}
					self::save($png_image,$destination_path);
					return $destination_path;
					break;
			}
		}

		return '';
	}
}
