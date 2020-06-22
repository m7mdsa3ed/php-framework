<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Vendor\Request\Request;
use App\Vendor\Validator;

class Products extends Controller {
  
  public function all() {
    $request = new Request;
    if ($request->has('paginate')) {
      return json(Product::paginate($request->paginate));
    }
    return json(Product::get());
  }

  public function show($id) {
    return json(Product::find($id));
  }

  public function create() {

    $request = new Request;
    
    if (!Auth::check()) return json(['error' => 'Unauthorized'], 401);

    $validator = new Validator($request->all(), [
      'name'          => ['required'],
      'price'         => ['required', 'int'],
      'description'   => ['required'],
      'quantity'      => ['required', 'int'],
    ]);

    if (!$validator->passed()) return json(['errors' => $validator->errors()], 400);

    $image = ($request->hasFile('image'))
     ? $request->file('image')->compress('50')
     : NULL;

    if ( $product = Product::create([
      'user_id'       => Auth::user()->id,
      'name'          => $request->name,
      'price'         => $request->price,
      'description'   => $request->description,
      'excerpt'       => $request->excerpt,
      'image'         => $image,
      'quantity'      => $request->quantity,
      'created_at'    => date("Y-m-d H:i:s"),
    ]) ) {

      foreach($request->categories as $category_id) {
        ProductCategory::create([
          'category_id' => $category_id,
          'product_id'  => $product->get('id')
        ]);
      }

      return json($product, 201);      
    }

    return json(['error' => 'Couldn\'t create' ]);

  }

  public function update($id) {
    
    $request = new Request;

    if (!Auth::check()) return json(['error' => 'Unauthorized'], 401);

    $validator = new Validator($request->all(), [
      'name'          => ['required'],
      'price'         => ['required', 'int'],
      'description'   => ['required'],
      'quantity'      => ['required', 'int'],
    ]);

    if (!$validator->passed()) return json(['errors' => $validator->errors()]);

    if ( $request->hasFile('image')) {

      // Delete old one
      echo $oldImage = ltrim(str_replace(getDirname(), '', Product::find($id)->first()->image), '/\\');
      if ( file_exists($oldImage) ) unlink($oldImage);

      // Upload the new one
      $image = $request->file('image')->compress('50');
    } 
    
    // The same exist avatar
    else {
      $image = Product::find($id)->first()->image;
    }

    if ( $product = Product::update($id, [
      'name'          => $request->name,
      'price'         => $request->price,
      'description'   => $request->description,
      'excerpt'       => $request->excerpt,
      'image'         => $image,
      'quantity'      => $request->quantity,
      'updated_at'    => date("Y-m-d H:i:s")
    ])) {

      if ( $request->has('categories') ) {
        
        // Get Old Categories then reomve 'em 
        $postCategories = ProductCategory::where('product_id', $id)->get();
        if ( $postCategories->count() ) {
          foreach( $postCategories as $postCategory) {
            ProductCategory::delete($postCategory->id);
          }
        }

        // Create New Categories
        foreach( $request->categories as $category_id) {
          ProductCategory::create([
            'product_id'  => $id,
            'category_id' => $category_id
          ]);
        }
      }
      
      return json($product);
    }
    return json(['error' => 'Couldn\'t Update']);
  }

  public function delete($id) {
    if (Auth::check()) {
      return Product::delete($id)
        ? json(['message' => 'Product Deleted'])
        : json(['error' => 'Could Not Delete'], 400);
    }
    return json(['error' => 'Unauthorized'], 401);
  }

}