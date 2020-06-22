<?php

namespace App\Vendor;

class Route{

  // Callable methods range
  private static $allowedMethods = ['ANY', 'GET', 'POST', 'PUT', 'DELETE', 'MATCH'];

  // Routes 
  public static $routes = [];

  static function __callStatic($method, $args) {

    // Extract $args to $pattern and $callback function.
    list($pattern, $callback) = $args;

    // Check whether method is allowed or not.
    if (in_array(strtoupper($method), self::$allowedMethods)) {

      $method = strtoupper($method);

      $methods = [];
      switch ($method) {
        case 'ANY';
          array_push($methods, 'GET', 'POST', 'PUT', 'DELETE');
          break;
        case 'MATCH':
          list($methods, $pattern, $callback) = $args;
          foreach ($methods as &$m) {
            $m = strtoupper($m);
          }
          break;
        default:
          $methods = [$method];
      }

      // Push new route
      self::$routes[] = [
        'methods'   => $methods,      // Methods
        'pattern'   => $pattern,      // Pattern
        'callback'  => $callback,     // ControllerName@method or function()
        'name'      => NULL,          // Route Name
      ];
    }

    // Method not exits
    else {
      return json(['error' => 'Method Not Exits'], 400);
    }
    return new static;
  }

  public static function run() {

    // Current method 
    $method = $_SERVER['REQUEST_METHOD'];

    // Vars 
    $path_found = false;
    $route_found = false;
    $methods = [];

    // Loop through all routes
    foreach (self::$routes as $route) {

      // Prepare Pattern
      $regex = "/\{(.*?)\}/";
      $pattern_regex = preg_replace_callback($regex, function ($match) {

        $a = strtolower($match[1]);

        // Param is optional
        $a = strstr($a, '?')
          ? str_replace($a, '(?P<' . str_replace('?', '', $a) . '>[\w-]+)?', $a)
          : str_replace($a, '(?P<' . $a . '>[\w-]+)', $a);

        return $a;
      }, $route['pattern']);
      $pattern_regex = "#^" . trim($pattern_regex, '/') . "$#";


      // URL/Pattern matching 
      if (preg_match($pattern_regex, self::getCurrentPath(), $params)) {

        // Now we have matched pattern
        $path_found = true;

        // Check whether method is allowed or not  
        if (in_array($method, $route['methods'])) {

          // Clean up parameters  
          foreach ($params as $key => &$value) {

            // Get only named values
            if (!is_string($key)) {
              unset($params[$key]);
            }
          }

          // Route Found
          $route_found = true;

          // Call the callback
          self::call($route['callback'], $params);

          // Break the loop
          break;
        }

        // Couldn't found the right route but the path matched 
        // so the methods didn't match 
        else {

          // If you're here that means request methods not matched
          // So extract allowed methods to display to error  
          $methods[] = implode(', ', $route['methods']);
        }
      }
    }

    // No Route Found
    if (!$route_found) {

      // but PATH found, that means request method not allowed
      if ($path_found)
        return json(['error' => '405: Method Not Allowed', 'expected_methods' => $methods], 405);

      // but if PATH not found, so you're in the area which is not exits 
      else return json(['error' => '404: Not Found'], 404);
    }
  }

  private static function getCurrentPath() {

    // Actual URL
    $path = $_SERVER['REQUEST_URI'];

    // if the project located on subdir whether localhost or server 
    // delete its path from url since we matching with pattern 
    // that doesn't have subdir folder 
    // PHP_SELF = '/subdir/public/index.php'
    $path = str_replace(dirname($_SERVER['PHP_SELF'], 2), '', $path);

    // Remove slashes
    $path = ltrim($path, '/\\');

    // Get URI without query strings
    $path = parse_url($path, PHP_URL_PATH);
    return $path;
  }

  private static function call($callback, $params) {

    // If callback is function()
    if (is_callable($callback)) {

      //  Call the function and pass the params
      call_user_func_array($callback, $params);
    }

    // If callback is method in class
    else {

      // Extract controller and method
      $a = explode('@', $callback);
      $class  = "App\Controllers\\" . $a[0];
      $method = $a[1];

      if (class_exists($class)) {

        // Create the controller
        $controller = new $class;
        if (method_exists($controller, $method)) {

          // Call the method
          call_user_func_array([$controller, $method], $params);
        }
      }
    }
  }

  public static function name(String $name) {

    // Get last added route
    $lastRoute = array_key_last(self::$routes);

    // Set "name" to it 
    self::$routes[$lastRoute]['name'] = $name;
    return new static;
  }

  public static function resources(String $path, String $controller) {

    // Create resources routes 
    self::get("$path", "$controller@all");
    self::post("$path", "$controller@create");
    self::get("$path/{id}", "$controller@show");
    self::put("$path/{id}", "$controller@update");
    self::delete("$path/{id}", "$controller@delete");
  }

  public static function hasRoute(string $routeName) {
    return in_array($routeName, array_values(pluck(self::$routes, 'name')));
  }

  public static function getRoute(string $routeName, ...$params) {

    // Get all routes
    $routes = self::$routes;

    // Loop through 
    foreach ($routes as $route) {

      // Check whether it's "name" asigned before or not 
      if ($route['name'] == $routeName) {

        $regex = "/\{(.*?)\}/";

        // Check if route has params required
        if (preg_match($regex, $route['pattern'], $matches)) {

          // Create url with given $params, if there's
          $url = preg_replace_callback(
            $regex,

            function ($a) use ($params) {

              $optional = false;
              static $x = 0;

              // Remove "?" from optional params 
              if (strstr($a[1], '?')) {
                $a[1] = str_replace('?', '', $a[1]);
                $optional = true;
              }

              // Params os assoc array
              if (is_assoc_array($params[0])) {

                // Return null if the param is optional
                if ($optional) {
                  return null;
                }

                // Replace with values
                return $params[0][$a[1]];
              }

              // inline params and indexed array 
              else {

                // Check params is array or not 
                if (\func_num_args() == 1 && is_array($params[0])) {
                  $params = $params[0];
                }

                $r = null;
                if (isset($params[$x])) {
                  $r =  $params[$x];
                }

                $x++;
                return $r;
              }
            },
            $route['pattern']
          );
          return $url;
        }

        // N params required
        else {
          return $route['pattern'];
        }
      }
    }
  }
}
