<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class OAuth_model extends REST_Model
{

	function __construct()
	{
		parent::__construct();
		
		$this->table_name = TABLE_APPLICATIONS;
		
		$this->columns = array(
			'id',
			'name',
			'app_secret',
			'access_token',
			'status',
			'date_created',
			'date_updated'
		);
		
		$this->selectable_columns = array(
			'id',
			'name',
			'app_secret',
			'access_token',
			'status'
		);
	}
	
	/**
	 * Gets Access Token
	 *
	 * Gets the applcation's access token
	 *
	 * @param	string		$app_id				Application's ID
	 * @param	string		$app_secret			Application's Secret Key
	 */
	public function get_access_token($app_id, $app_secret, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		
		// check if row with id exists
		if ($this->exists_by_fields(array('id' => $app_id, 'app_secret' => $app_secret)))
		{
			// get and return
			$data = $this->db->select('access_token, status')->get_where($table, array('id' => $app_id, 'app_secret' => $app_secret))->row_array();
			
			if ($data['status'] != 'APPROVED')
			{
				throw new Exception('Application status is ' . $data['status']);
			}
			
			unset($data['status']);
			
			return $data;
		}
		
		else
		{
			// throw error if id does not exist
			throw new Exception('Unauthorized.');
		}
	}
	
	/**
	 * Get ID by Access Token
	 *
	 * Gets the applcation's ID using an access token
	 *
	 * @param	string		$access_token			Application's access token
	 */
	public function get_id_by_access_token($access_token, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		
		// check if row with id exists
		if ($this->exists_by_fields(array('access_token' => $access_token)))
		{
			// get and return
			return $this->db->select('id')->get_where($table, array('access_token' => $access_token))->row_array();
		}
		
		else
		{
			// throw error if id does not exist
			throw new Exception('Invalid access token.');
		}
	}
	
	/**
	 * Validate application name's uniqueness
	 *
	 * Look for duplicate name
	 *
	 * @param	string		$name			Application's name
	 */
	public function validate_name_uniqueness($name, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		
		// check if row with name exists
		if ($this->exists_by_fields(array('name' => $name)))
		{
			// get and return
			throw new Exception('Sorry but the name you\'ve chosen is already bounded.');
		}
	}
}

