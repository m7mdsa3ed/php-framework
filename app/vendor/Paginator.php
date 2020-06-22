<?php 

namespace App\Vendor;

use App\Vendor\Request\Request;

class Paginator {

  public static function paginate(
    Array $items = [], 
    Int $perPage, 
    String $name = 'page',
    Int $total = null ) {
    
    $request = new Request;
    $results = [];

    // Current PAge
    $currentPage = isset($_GET[$name]) 
      ? $_GET[$name] 
      : 1;
      
    /* Static : based on SQL limit query, that needs total records with the data
     * NonStatic : with all data provided ( Common ) */
    $data  = ($total) 
      ? $items
      : array_slice($items, ( $currentPage - 1) * $perPage, $perPage);
      
    // Total items if it's provided
    $total  = ($total) ? $total : count($items);

    // Total Pages
    $lastPage = $pages = ceil($total / $perPage);

    // Links Range
    $range  = 1;      // Before and after
    $staticPages = 1; // At first and the end

    // Generate pages numbers
    $pagesNum = range(1,$lastPage);
    for ($i = $lastPage - $staticPages - 1; $i > $staticPages - 1; $i--) {
      if(abs($currentPage - $i - 1) > $range) {
        unset($pagesNum[$i]);
      } 
    }

    // Base path
    $path = $request->url();
    
    // Query String handler
    $query = null;
    $hasQuery = false;

    // if there's any query string
    if (!empty($request->queryStringArray())) {
      
      // Remove ours from it
      $query = $request->queryStringArray();
      unset($query[$name]);

      if (!empty($query)) {

        // if not empty after ewmoving ours
        //thne it's has query string
        $hasQuery = true;
        
        // Build new query
        $query = http_build_query($query);
        
        // Add new query to base path
        $path .= '?' . $query;
      }
    }

    // Query String concatenation operator
    $operator = ($hasQuery && !empty($query))
      ? '&'
      : '?';
    
    $pagePathBase = "$path$operator$name=";

    foreach ($pagesNum as &$pageNam) {
      $pageNam = [ 'page' => $pageNam, 'url'  => "$pagePathBase$pageNam" ];
    }

    $results['data'] = $data;
    $results['paginator'] = [
      'page'                => $currentPage,
      'pages'               => $pages,
      'path'                => $path,
      'total'               => $total,
      'from'                => ( $currentPage - 1) * $perPage + 1,
      'to'                  => ( $currentPage - 1) * $perPage + ( ($currentPage == $lastPage) ?: $perPage ),
      'first_page_url'      => $pagePathBase . 1,
      'last_page_url'       => $pagePathBase . $lastPage,
      'next_page_url'       => ( $currentPage == $lastPage ) ? NULL : $pagePathBase . ($currentPage + 1),
      'prev_page_url'       => ( $currentPage == 1 ) ? NULL : $pagePathBase . ($currentPage - 1),
      'query_string_name'   => $name,
      'urls'                => array_values($pagesNum),
    ];

    return $results;
  }
}
