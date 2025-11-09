<?php
namespace Helper;
class Html {
	/* Gets an html element
	 *
	 * @param string $tag_name
	 * @param string $content
	 * @param array $attributes
	 * 
	 * @return string Html code
	 * 
	 */
	public static function get_element($tag_name, $content = null, $attributes = array()){
		$rtn = '<'.$tag_name;
		foreach($attributes AS $name => $value){
			$rtn .= ' '.$name.(!is_null($value) ? '="'.$value.'"' : '');
		}
		if(is_null($content)){
			$rtn .= ' />';
		} else {
			$rtn .= '>'.$content.'</'.$tag_name.'>';
		}
		
		return $rtn;
	}
	
	/**
	 * Gets option list for a select item.
	 *
	 * @param array $select_values [value=>name] array or [label=>[value=>name]] if "optgroup" needed
	 * @param mixed $current_value Selected value or value array if multiple select
	 * @param mixed $disabled_value Disabled value or values array
	 * @param boolean $strict_mode True to compare value with php's in_array strict method
	 *
	 * @return string Html code
	 *
	 */
	public static function get_select_options($select_values, $current_value = null, $disabled_value = array(), $strict_mode = true){
		$rtn = '';
		if(!is_array($current_value)){
			$current_value = array($current_value);
		}
		if(!is_array($disabled_value)){
			$disabled_value = array($disabled_value);
		}
		foreach($select_values AS $value => $name){
			if(is_array($name)){
				// options are grouped //
				$sub_options = self::get_select_options($name,$current_value);
				$rtn .= self::get_element('optgroup',$sub_options,array('label' => $value));
			} else {
				$option_parameters = array('value' => $value);
				if(in_array($value,$current_value,$strict_mode)){
					$option_parameters['selected'] = 'selected';
				}
				if(in_array($value,$disabled_value,$strict_mode)){
					$option_parameters['disabled'] = 'disabled';
				}
				$rtn .= self::get_element('option',$name,$option_parameters);
			}
		}
		return $rtn;
	}
	
	/*
	 * Replace normal space with unbreakable ones in string.
	 *
	 * @param string $string
	 *
	 * @return string
	 *
	 */
	public static function transform_space_to_unbreakable($string){
		return str_replace(' ','&nbsp;',$string);
	}
}
?>