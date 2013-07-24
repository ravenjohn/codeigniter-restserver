<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Key_model extends MY_Model
{

	function __construct()
	{
		parent::__construct();
		
		$this->table_name = config_item('rest_keys_table');
		
		$this->columns = array(
			'id',
			'key',
			'level',
			'ignore_limits',
			'is_private_key',
			'ip_addresses',
			'date_created',
			'date_updated'
		);
		
		$this->selectable_columns = array(
			'id',
			'key',
			'level',
			'ignore_limits',
			'is_private_key',
			'ip_addresses',
			'date_created',
			'date_updated'
		);
	}
	
	//model specific methods...
}

