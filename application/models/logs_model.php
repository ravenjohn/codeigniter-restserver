<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logs_model extends REST_Model
{

	function __construct()
	{
		parent::__construct();
		
		$this->table_name = TABLE_LOGS;
		
		$this->columns = array(
			'id',
			'app_or_user_id',
			'uri',
			'method',
			'params',
			'access_token',
			'ip_address',
			'authorized',
			'date_created',
			'date_updated'
		);
	}
	
	//model specific methods...
}

