<?php 

namespace App\Controllers;

use App\Models\Comment;

class Comments extends Controller {

  public function product($id) {
    return json(Comment::where('product_id', $id)->get());
  }
}