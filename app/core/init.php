<?php 

// Enable debug 

use App\Vendor\Route;

ini_set('display_errors', 'On');
ini_set('max_execution_time', 300); //300 seconds = 5 minutes

error_reporting(-1);

// Start session in case we need it
session_start();

// Run Autoloaders
require_once 'autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Include Functions
require_once 'functions.php';

setHeaders();
require_once __DIR__ . '/../../routes/routes.php';
Route::run();