<?php

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'inc/init.php');

// var_dump($_GET);
// var_dump($_POST);
$input = json_decode(@file_get_contents('php://input'),true);
$JSON = ['error' => false,'error_message' => ''];
// var_dump($input);
$endpoint = $_GET['endpoint'] ?? '';
$action = $_GET['action'] ?? '';
$param = $_GET['param'] ?? '';

switch($endpoint){
	case 'deck':
		require_once './api_endpoint/deck.php';
		break;
	case 'card':
		require_once './api_endpoint/card.php';
		break;
}

echo json_encode($JSON,JSON_FORCE_OBJECT);