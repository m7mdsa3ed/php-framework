<?php

use App\Vendor\Route;

// Commonn 
Route::any("/", "Home@index");
Route::get('seed', 'Home@seed');
Route::get('stats', 'Home@stats');

// Auth
Route::post('user','Auth@validateUser');
Route::post('login','Auth@login');
Route::post('register','Auth@register');

// Users
Route::get('users', 'Users@all');
Route::get('users/{id}', 'Users@show');
Route::put('users/{id}', 'Users@update');
Route::delete('users/{id}', 'Users@delete');

// Products
Route::resources('products', 'Products');
Route::get('products/{id}/comments', 'Comments@product');
Route::get('products/{id}/categories', 'Categories@product');

// Categories 
Route::resources('categories', 'Categories');