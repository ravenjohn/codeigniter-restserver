<?php
/*
 * Common Functions
 *
 * @authors        	Raven Lagrimas | rjlagrimas08@gmail.com
 * @license         FOSS
 * @version 		1.0
 */

/**
 * Gets the URL of the server
 * @return 	string
 **/
function getServerURL()
{
	$pageURL = 'http';
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
	{
		$pageURL .= 's';
	}
	
	$pageURL .= '://';
	
	if ($_SERVER['SERVER_PORT'] != '80')
	{
		$pageURL .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
	}
	
	else
	{
		$pageURL .= $_SERVER['SERVER_NAME'];
	}
	
	return $pageURL;
}

/**
 * Gets the URL of API
 * @return 	string
 **/
function getApiURL()
{
	$url = getServerURL() . $_SERVER['REQUEST_URI'];
	return substr($url, 0, strrpos($url, '/') + 1);
}

/**
 * Returns the default parameters of GET method
 * @return 	CSV of parameters
 **/
function getDefaultGETParams()
{
	return '?search_key, ?fields, ?limit, ?page, ?sort_field, ?sort_order';
}


/**
 * Returns a uuid which confirms to RFC 4122 section 4.1.2
 * @return 	unique id composed of 32 characters
 **/
function uuid()
{
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