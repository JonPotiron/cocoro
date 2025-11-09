<?php

switch($action){
	case 'add_card':
		$title = trim($input['card_title'] ?? '');
		if(!empty($title)){
			$file_path = ROOT_PATH.'public/cards/'.\Helper\Utils::kebab_case_transform($title).'.md';
			if(!file_exists($file_path)){
				\Helper\FileSystemIO::create_unexistant_folders_from_path(ROOT_PATH.'public/cards/');
				$content = '# '.$title;
				if(!file_put_contents($file_path,$content)){
					$JSON['error'] = true;
					$JSON['error_message'] = 'Error while creating file';
				}
			}
		} else {
			$JSON['error'] = true;
			$JSON['error_message'] = 'Can\'t use an empty title';
		}
		$JSON['card_url'] = HOST.\Helper\Utils::kebab_case_transform($title);
		break;
}
