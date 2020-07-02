<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\Seed;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;

class Home extends Controller {
  
  public function index() {
    
    return view('welcome');
  }

  public function stats() {
    return json([
      'users' => User::get()->count(),
      'products' => Product::get()->count(),
      'categories' => Category::get()->count(),
      'comments' => Comment::get()->count(),
      'usersByYear' => DB::RAW("SELECT COUNT(id) as count, year(created_at) as year FROM users GROUP BY year(created_at)"),
    ]);
  }

  function seed() {
    $seed = new Seed;
    $seed->init();
    $seed->feed();
    $configFileName = (isDev()) ? 'config.env.local' : 'config.env';  
    echo generateAppKey(__DIR__ . "/../../$configFileName", 'APP_KEY');
  }

}