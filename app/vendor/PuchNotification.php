<?php 

namespace App\Vendor;

use Pusher\Pusher;

class PuchNotification {

  private static $data;
  private static $channel;
  private static $event;

  public static function send() {

    $pusher = new Pusher(
      env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET') , env('PUSHER_APP_ID'),
      [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'useTLS' => true
      ]
    );
    
    $pusher->trigger(self::$channel, self::$event, self::$data);    
    
    self::$data = null;
    self::$channel = null;
    self::$event = null;
  }

  public static function data(array $data =null) {
    self::$data = $data;
    return new static;
  }

  public static function channel($channel) {
    self::$channel = $channel;
    return new static;
  }

  public static function event($event) {
    self::$event = $event;
    return new static;
  }


}
