<?php

// Check HTML generated file, return if exists
// if(file_exists('index.html')){
// 	echo file_get_contents('public/index.html');
// 	exit(0);
// }

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'inc/init.php');

use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Renderer\HtmlRenderer;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;


// var_dump(HOST);
// var_dump(ROOT_PATH);

///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////

$path_query = explode('/',parse_url($_GET['path'] ?? '')['path'] ?? '');
$path = $path_query[0] ?? '';
$js_lib = '';
$specific_js = '';

// init commonmark
$environment = new Environment();
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new GithubFlavoredMarkdownExtension());
$parser = new MarkdownParser($environment);
$htmlRenderer = new HtmlRenderer($environment);


// var_dump($path);
if(!empty($path)){
	// Display one card
	$file_path = ROOT_PATH.'public/cards/'.$path.'.md';
	if(file_exists($file_path)){
		$markdown = file_get_contents($file_path);
		$document = $parser->parse($markdown);
		$card_title = \Helper\CommonmarkTreeModificator::extract_h1($document);

		$content = \Helper\Template::parse_template('card.html',[
			'CARD_ID' => $path,
			'CARD_TITLE' => $card_title,
			// 'CARD_CONTENT' => $converter->convert(file_get_contents($file_path))->getContent(),
			'CARD_CONTENT' => $htmlRenderer->renderDocument($document),
			'ADD_ITEM_URL' => HOST.'api/card/add_item/',
			'SAVE_CARD_URL' => HOST.'api/card/save/',
		]);
	} else {
		$content = \Helper\Template::parse_template('empty_card.html',[
			
		]);
	}
} else {
	// Display all
	$cards = \Helper\FileSystemIO::get_files_in_dir(ROOT_PATH.'public/cards/*.md');
	$cards_content = '';
	foreach($cards as $card){
		// var_dump($card);
		$markdown = file_get_contents($card->full_path);
		$document = $parser->parse($markdown);
		$card_title = \Helper\CommonmarkTreeModificator::extract_h1($document);
		
		$cards_content .= \Helper\Template::parse_template('deck.item.html',[
			// 'TITLE' => $card->name,
			'TITLE' => $card_title,
			'CARD_URL' => HOST.$card->name,
 		]);
	}
	$content = \Helper\Template::parse_template('deck.html',[
		'DECK' => $cards_content,
		'ADD_CARD_URL' => HOST.'api/deck/add_card',
	]);
}





///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////

///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////


// Main Template
$HTML = \Helper\Template::parse_template('main.html',[
	'CONTENT' => $content,
]);


// Insert JS
$js = file_get_contents('public/build/js/cocoro.min.js');
if($js === false){
	exit('js file not found');
}
$HTML = preg_replace('/{JS}/',$js_lib.$js.$specific_js,$HTML);

// Insert CSS
$css_normalize = file_get_contents('public/lib/normalize.css');
if($css_normalize === false){
	exit('css normalizer file not found');
}
$css = file_get_contents('public/build/css/cocoro.css');
if($css === false){
	exit('css file not found');
}
$HTML = preg_replace('/{CSS}/',$css_normalize.$css,$HTML);


// Generate HTML file
// file_put_contents('public/index.html',$HTML);

echo $HTML;