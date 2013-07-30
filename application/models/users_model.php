<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Users_model extends REST_Model
{

	function __construct()
	{
		parent::__construct();
		
		$this->table_name = TABLE_USERS;
		
		$this->columns = array(
			'id',
			'name',
			'date_created',
			'date_updated'
		);
		
		$this->selectable_columns = array(
			'id',
			'name',
			'date_created',
			'date_updated'
		);
		
		$this->sortable_columns = array(
			'id',
			'name',
			'date_created',
			'date_updated'
		);
		
		$this->searchable_columns = array(
			'name'
		);
	}
	
	//model specific methods...
}

