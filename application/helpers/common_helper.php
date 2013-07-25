<?php
/*
 * Common Functions
*/
function getServerURL()
{
	$pageURL = 'http';
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
	{
		$pageURL .= "s";
	}
	
	$pageURL .= "://";
	
	if ($_SERVER["SERVER_PORT"] != "80")
	{
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
	}
	
	else
	{
		$pageURL .= $_SERVER["SERVER_NAME"];
	}
	
	return $pageURL;
}

function getApiURL()
{
	$url = getServerURL() . $_SERVER['REQUEST_URI'];
	
	return substr($url, 0, strrpos($url, '_') - 3) . 'api/';
}