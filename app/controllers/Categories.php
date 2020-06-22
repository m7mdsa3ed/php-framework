<?php 

namespace App\Controllers;

use App\Models\Category;
use App\Models\ProductCategory;
use App\Vendor\Request\Request;

class Categories extends Controller {

  public function product($id) {
    
    $categories = ProductCategory::where('product_id', $id)->get()->pluck('category_id')->toArray();
    $categoriesFull = [];
    $request = new Request;
    if ($request->has('full')) {
      foreach ($categories as $cid) {
        $categoriesFull[] = Category::find($cid)->first();
      }
      return json($categoriesFull);
    }
    return json($categories);
  }

  public function all() {
    return json(Category::get());
  }


}