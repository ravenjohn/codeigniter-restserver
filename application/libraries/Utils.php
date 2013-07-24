<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Utilities library
*/
class Utils {

	/**
	 * Returns a uuid which confirms to RFC 4122 section 4.1.2
	 * @return 	unique id composed of 32 characters
	 **/
	public function uuid() {
        $pattern = '%04x%04x%04x%03x4%04x%04x%04x%04x';
        return sprintf($pattern,
                       mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
                       mt_rand(0, 65535), // 16 bits for "time_mid"
                       mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
                       bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
                       // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
                       // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
                       // 8 bits for "clk_seq_low"
                       mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"
                      );
  }

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