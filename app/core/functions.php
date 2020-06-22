<?php

use App\Vendor\Route;
use App\Vendor\View;
use Firebase\JWT\JWT;

function setHeaders() {
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Max-Age: 3600");
	header("Access-Control-Allow-Methods: *");
	header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
}

function isDev() {
	return in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);
}

// Get configrations from ENV file
function env(String $string){
	$configFileName = (isDev()) 
		? 'config.env.local' 
		: 'config.env';
	$configFileName = __DIR__ . "/../../$configFileName";
	if ( file_exists($configFileName) ) {
		return parse_ini_file("$configFileName")[$string];
	}
}

function json($array, Int $response_code = 200) {
	header("Content-Type: application/json; charset=UTF-8");
	http_response_code($response_code);
	echo json_encode($array, JSON_PRETTY_PRINT, 512);
}

function getTokenDataOrFail($token = null) {
	if ($token == null) { 
		list(, $token) = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
	}
	
	try {
		return JWT::decode($token, env('APP_KEY'), ['HS256'])->data;
	} catch (\Exception $e) {
		return false;
	}
}

function getDirname(bool $full = true) {
	$https = isset($_SERVER['HTTPS']);
	$protocol = ($https) ? 'https://' : 'http://';
	$host = $_SERVER['HTTP_HOST'];
	$isDirExists = trim(dirname($_SERVER['PHP_SELF']), '/\\');
	if ($full) {
		return "$protocol$host/$isDirExists";
	}
	return "/$isDirExists";      
}


function generateAppKey($config_file, $config_name) {

	$done = false;

	// Generate the key 
	$app_key = \bin2hex(random_bytes(16));

	// Configration file name
	$file = $config_file;

	// Open it
	$lines = file($file, FILE_IGNORE_NEW_LINES);
	
	// Loop through lines
	foreach ( $lines as $key => &$line) {

		// If it's $config_name
		if (isset(parse_ini_string($line)[$config_name])) {

			// Add it
			$lines[$key] = "$config_name=$app_key";
			$done = true;
			
		}
	}
	
	// Update the file
	file_put_contents($file , implode("\n", $lines));

	return ($done) ? $app_key : 'Something is wrong';
	
}

if (!function_exists('view')) {

	function view(string $template, array $data) {
		echo View::template($template)->data($data)->render();
		return;
	}

}

if (!function_exists('pluck')) {
	function pluck($array, $key, $default = null) {

		$array = array_map(function($v) use ($key) {
			return is_object($v) ? $v->$key : $v[$key];
		}, $array);
	
		return ($default) ?? $array;
	}

}

if (!function_exists('is_assoc_array')) {
	function is_assoc_array(array $arr) {
    if ( array() === $arr) {
			return false;
		}

    return array_keys($arr) !== range(0, count($arr) - 1);
	}
}

if (!function_exists('route')) {
	function route(string $name, array $params = null) {
		return Route::getRoute($name, $params);
	}
}

