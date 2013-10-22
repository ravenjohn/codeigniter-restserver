<?php defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| HTTP protocol
|--------------------------------------------------------------------------
|
| Should the service accept only HTTPS requests or not?
|
|	Default: FALSE
|
*/
$config['force_https'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Format
|--------------------------------------------------------------------------
|
| What format should the data be returned in by default?
|
|	Default: xml
|
*/
$config['rest_default_format'] = 'json';

/*
|--------------------------------------------------------------------------
| Enable emulate request
|--------------------------------------------------------------------------
|
| Should we enable emulation of the request (e.g. used in Mootools request)?
|
|	Default: false
|
*/
$config['enable_emulate_request'] = FALSE;


/*
|--------------------------------------------------------------------------
| REST Enable OAuth
|--------------------------------------------------------------------------
|
| When set to true REST_Controller will look for an access token.
| If no token is provided, the request will return an error.
|
|	FALSE
|
|	// recommended applications table
|	CREATE TABLE `applications` (
	  `id` varchar(40) NOT NULL,
	  `name` varchar(128) NOT NULL,
	  `app_secret` varchar(40) NOT NULL,
	  `status` enum('PENDING','APPROVED','REJECTED','BLOCKED') NOT NULL DEFAULT 'PENDING',
	  `date_created` int(11) NOT NULL,
	  `date_updated` int(11) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
|
*/
$config['rest_enable_oauth'] = FALSE;


/*
|--------------------------------------------------------------------------
| REST Access Token Variable
|--------------------------------------------------------------------------
|
| Which variable will provide us the access token
|
| Default: access_token
|
*/
$config['rest_token_name'] = 'access_token';

/*
|--------------------------------------------------------------------------
| REST Enable Logging
|--------------------------------------------------------------------------
|
| When set to true REST_Controller will log actions based on key, date,
| time and IP address. This is a general rule that can be overridden in the
| $this->method array in each controller.
|
|	FALSE
|
|	CREATE TABLE `logs` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `app_or_user_id` varchar(40) NOT NULL,
	  `uri` varchar(255) NOT NULL,
	  `method` varchar(6) NOT NULL,
	  `params` text,
	  `access_token` varchar(40) NOT NULL,
	  `ip_address` varchar(45) NOT NULL,
	  `authorized` tinyint(1) NOT NULL,
	  `date_created` int(11) NOT NULL,
	  `date_updated` int(11) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
|
*/
$config['rest_enable_logging'] = TRUE;

/*
|--------------------------------------------------------------------------
| REST Ignore HTTP Accept
|--------------------------------------------------------------------------
|
| Set to TRUE to ignore the HTTP Accept and speed up each request a little.
| Only do this if you are using the $this->rest_format or /format/xml in URLs
|
|	FALSE
|
*/
$config['rest_ignore_http_accept'] = TRUE;

/*
|--------------------------------------------------------------------------
| REST AJAX Only
|--------------------------------------------------------------------------
|
| Set to TRUE to only allow AJAX requests. If TRUE and the request is not
| coming from AJAX, a 505 response with the error message "Only AJAX
| requests are accepted." will be returned. This is good for production
| environments. Set to FALSE to also accept HTTP requests.
|
|	FALSE
|
*/
$config['rest_ajax_only'] = FALSE;

/*
|--------------------------------------------------------------------------
| Ignore extra data
|--------------------------------------------------------------------------
|
| Set to TRUE to allow extra data from request
|
|	FALSE
|
*/
$config['ignore_extra_data'] = FALSE;

/* End of file config.php */
/* Location: ./system/application/config/rest.php */
