<?php

use League\CommonMark\GithubFlavoredMarkdownConverter;

switch($action){
	case 'add_item':
		$card_id = trim($input['card_id'] ?? '');
		$item_content = trim($input['item_content'] ?? '');
		if(empty($card_id)){
			$JSON['error'] = true;
			$JSON['error_message'] = 'Card id is empty';
			break;
		}
		if(empty($item_content)){
			$JSON['error'] = true;
			$JSON['error_message'] = 'Can\'t set an empty item';
			break;
		}
		$file_path = ROOT_PATH.'public/cards/'.$card_id.'.md';
		if(!file_exists($file_path)){
			$JSON['error'] = true;
			$JSON['error_message'] = 'Card file does not exist';
		}
		$item_content = "\n".'- [ ] '.$item_content;
		if(!file_put_contents($file_path,$item_content,FILE_APPEND)){
			$JSON['error'] = true;
			$JSON['error_message'] = 'Error while creating file';
			break;
		}
		$converter = new GithubFlavoredMarkdownConverter();
		$JSON['card_content'] = $converter->convert(file_get_contents($file_path))->getContent();
		
		break;
	case 'save':
		$card_id = trim($input['card_id'] ?? '');
		$card_content = trim($input['card_content'] ?? '');
		if(empty($card_id)){
			$JSON['error'] = true;
			$JSON['error_message'] = 'Card id is empty';
			break;
		}
		if(empty($card_content)){
			$JSON['error'] = true;
			$JSON['error_message'] = 'Can\'t set an empty card';
			break;
		}
		$file_path = ROOT_PATH.'public/cards/'.$card_id.'.md';
		if(!file_exists($file_path)){
			$JSON['error'] = true;
			$JSON['error_message'] = 'Card file does not exist';
		}
		if(!file_put_contents($file_path,$card_content)){
			$JSON['error'] = true;
			$JSON['error_message'] = 'Error while creating file';
			break;
		}
		$converter = new GithubFlavoredMarkdownConverter();
		$JSON['card_content'] = $converter->convert(file_get_contents($file_path))->getContent();
		
		break;
}
