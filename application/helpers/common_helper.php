<?php

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
	
	return $pageURL.'/';
}

function getApiURL()
{
	return getServerURL() . 'api/';
}