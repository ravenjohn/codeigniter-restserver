<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Utilities library
*/
class Utils {

  /**
   * Returns the ip address of the client
   * @return  client's ip address
   **/
  public static function get_client_ip() {
  
    $clientIp = null;
    $localhost = "127.0.0.1";

    if (!empty($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != $localhost) {
        $clientIp = $_SERVER['HTTP_CLIENT_IP'];
    }
    
    if (!$clientIp && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != $localhost) {
        $ips = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ip = explode(", ", $ips);
        if (isset($ip[2]) && $ip[2] != $localhost) {
        $clientIp = $ip[2]; 
        } else if (isset($ip[1]) && $ip[1] != $localhost) { //if second ip add is available
        $clientIp = $ip[1];
        } else if (isset($ip[0]) && $ip[0] != $localhost) {
        $clientIp = $ip[0];
        }
    }
    //fallback
    if (!$clientIp) {
          $url = 'http://jsonip.com/';
          $res = @file_get_contents($url);
          $json_res = json_decode($res);
          $clientIp = (isset($json_res->ip)?$json_res->ip:NULL);
    }
    return $clientIp;
  }

}

/* End of file Utils.php */
/* Location: ./system/application/libraries/Utils.php */