<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Canonical Model
 *
 * A canonical model for CodeIgniter inspire from Mark Mirandilla's work.
 *
 * @package        	CodeIgniter
 * @subpackage    	Core
 * @category    	Models
 * @author        	Raven John Lagrimas | rjlagrimas08@gmail.com
 * @license         GNU General Public License (GPL)
 * @version 		1.0
 */
 
class REST_Model extends CI_Model 
{

	/**
	 * Get time once and store it in the model
	 *
	 * @var unixtimestamp
	 */
	protected $_time = NULL;
	
	/**
	 * Model's table name in the database
	 *
	 * @var string
	 */
    protected $table_name = NULL;	
	
	/**
	 * List of columns of the table in the database
	 *
	 * @var array
	 */
	public $columns = array();
	
	/**
	 * List of sortable columns of the table
	 * subset of $columns
	 *
	 * @var array
	 */
	public $sortable_columns = array();
	
	/**
	 * Get selectable columns of the table, passwords shouldn't be here, 
	 * subset of $columns
	 *
	 * @var array
	 */
	public $selectable_columns = array();
	
	/**
	 * Get searchable columns of the table
	 * subset of $columns
	 *
	 * @var array
	 */
	public $searchable_columns = array();
	
	/*
	 * Constructor function
	 */
    public function __construct()
    {
    	parent::__construct();
		
		// initialize time once
		$this->_time = time();
    }
	
	/**
	 * Batch Create
	 *
	 * Create a single sql statement for inserting lots of data
	 * Very usable for CSV imports
	 *
	 * @param	array	$data	Array of data to be inserted
	 * @param	string	$table	In case, you don't want to use the model's default table
	 */
	public function batch_create($data, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		$insert_data = array();

		// for every data, initialize their time
		foreach($data as $datum)
		{
			$datum['date_created'] 	= $this->_time;
			$datum['date_updated'] 	= $this->_time;
			$insert_data[] = $datum;
		}
		
		// now, let CI do it
		$this->db->insert_batch($table, $insert_data);
	}
	
	/**
	 * Create
	 *
	 * Creates/Inserts a row in the database
	 *
	 * @param	array	$data	An associative array complementing the table
	 * @param	array	$fields	Fields to be selected on the return
	 * @param	string	$table	In case, you don't want to use the model's default table
	 */
	public function create($data, $fields = FALSE, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		
		// make sure there are no excess data
		$this->_validate_data($data);
		
		// initialize dates
		$data['date_created'] = $this->_time;
		$data['date_updated'] = $this->_time;
		
		// insert
		$this->db->insert($table, $data);
		
		// get last insert id
		$id = intval($this->db->insert_id());

		// if less than 0, database insertion failed
		if ($id < 0)
		{
			// shoot an exception
			throw new Exception('Create failed.', 424);
		}
		// catch if ID in the DB is not int
		else if ($id === 0){
			$data = $this->get_all($data, FALSE, $fields, 1, 1, 'date_created', 'desc');
			return $data['data'][0];
		}
		
		// get the created row and throwback
		return $this->get_by_id($id, $fields);
	}
	
	/**
	 * Update
	 *
	 * Updates a row in the database
	 *
	 * @param	int		$id		The ID of the row
	 * @param	array	$data	An associative array containing the column-value to be updated
	 * @param	array	$fields	Fields to be selected on the return
	 * @param	string	$table	In case, you don't want to use the model's default table
	 */
	public function update($id, $data, $fields = FALSE, $table = FALSE)
	{
		$table OR $table = $this->table_name;

		// check first if the data to be updated exists
		if (!$this->exists($id))
		{
			// if not existing, throw
			throw new Exception('Data does not exist.', 404);
		}
		
		// check for excess data
		$this->_validate_data($data);
		
		// update the time
		$data['date_updated'] = $this->_time;
		
		// update the table
		$this->db->where('id', $id)->update($table, $data);
		
		// check if there's an affected row
		if ($this->db->affected_rows() < 1)
		{
			// if none, update failed
			throw new Exception('Update failed.', 424);
		}
		
		// get the updated row and throwback
		return $this->get_by_id($id, $fields);
	}
	
	/**
	 * Delete
	 *
	 * Deletes a row in the database
	 *
	 * @param	int		$id		The ID of the row to be deleted
	 * @param	string	$table	In case, you don't want to use the model's default table
	 */
	public function delete($id, $table = FALSE)
	{
		$table OR $table = $this->table_name;

		// check if data with the ID exists
		if (!$this->exists($id))
		{
			// if none, throw an error
			throw new Exception('Data does not exist.', 404);
		}
		
		// delete the row in the database
		$this->db->delete($table, array('id' => $id));
		
		// check if there's an affected row
		if ($this->db->affected_rows() < 1)
		{
			// if none, delete failed
			throw new Exception('Delete failed.', 424);
		}
	}
	
	/**
	 * Delete by fields
	 *
	 * Deletes a row in the database that matches the given fields
	 *
	 * @param	array	$fields	An associative array containing the column-value to be deleted
	 * @param	string	$table	In case, you don't want to use the model's default table
	 */
	public function delete_by_fields($fields, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		
		// delete
		$this->db->delete($table, $fields);
	}
	
	/**
	 * Exists
	 *
	 * Checks if row exists
	 *
	 * @param	int		$id		The ID of the row
	 * @param	string	$table	In case, you don't want to use the model's default table
	 */
	public function exists($id, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		return $this->db->from($table)->where(array('id' => $id))->count_all_results() === 1;
	}
	
	/**
	 * Exists by fields
	 *
	 * Checks if row exists given the following fields
	 *
	 * @param	int		$id		The ID of the row
	 * @param	string	$table	In case, you don't want to use the model's default table
	 */
	public function exists_by_fields($fields,$table = FALSE)
	{
		$table OR $table = $this->table_name;
		return $this->db->from($table)->where($fields)->count_all_results() >= 1;
	}
	
	/**
	 * Get by id
	 *
	 * Checks if row exists given the following fields
	 *
	 * @param	int		$id			ID of the row
	 * @param	array	$fields		the fields to be selected
	 * @param	string	$table		In case, you don't want to use the model's default table
	 */
	public function get_by_id($id,$fields = FALSE,$table = FALSE)
	{
		$table OR $table = $this->table_name;
		
		// validate fields to select
		$fields	= $this->_select_fields($fields);
		
		// check if row with id exists
		if ($this->exists($id))
		{
			// select fields, get, return
			return $this->db->select($fields)->get_where($table, array('id' => $id))->row_array();
		}
		
		else
		{
			// throw error if id does not exist
			throw new Exception('Data does not exist.', 404);
		}
	}
	
	/**
	 * Get All
	 *
	 * Gets all data based on the query
	 *
	 * @param	array			$where			if you want to filter using where
	 * @param	string/array	$like			if you want to filter using like
	 * @param	array			$fields			the fields to be selected
	 * @param	array			$page			the page
	 * @param	array			$limit			number of data per page
	 * @param	array			$sort_field		if you want to sort
	 * @param	array			$sort_orer		order of sorting
	 * @param	string			$table			In case, you don't want to use the model's default table
	 */
	public function get_all($where = FALSE,$like = FALSE,$fields = FALSE,$page = 1,$limit = DEFAULT_QUERY_LIMIT,$sort_field = FALSE,$sort_order = 'asc',$table = FALSE)
	{
		$table OR $table = $this->table_name;
		
		// validate parameters
		$page			= self::_page($page);
		$limit			= self::_limit($limit);
		$offset			= self::_offset($limit, $page);
		$sort_order		= self::_sort_order($sort_order);
		$like			= $this->_validate_like($like);
		$fields			= $this->_select_fields($fields);
		$sort_field		= $this->_sort_field($sort_field);
		
		// select fields from the table
		$this->db->select($fields)->from($table);
		
		// if where is supplied
		if ($where)
		{
			$this->db->where($where);
		}
		
		// if like is supplied
		if ($like)
		{
			$this->db->or_like($like);
		}
		
		// if sort_field is supplied
        if ($sort_field)
		{
        	$this->db->order_by($sort_field, $sort_order);
		}
			
			
		// if limit and page are valid
        if ($limit > 0 && $page > 0)
		{
			$this->db->limit($limit, $offset);
		}
		
		// get the query object
		$query = $this->db->get();
		
		// build data
		$return						 = array();
    	$return['data']				 = $query->result_array();
		
		if (ENVIRONMENT === 'development')
		{
			$return['query'] = $this->db->last_query();
		}
		
		$return['page']				 = $page;
		$return['items_count']		 = $query->num_rows();
		$return['items_per_page']	 = $limit;
		$return['items_total_count'] = $this->get_total_count($where, $like);
		
		
		// free result
		$query->free_result();
		
		// return data
		return $return;
	}
	
	/**
	 * Get Total Count
	 *
	 * Counts data based on the query
	 *
	 * @param	array	$where			if you want to filter using where
	 * @param	array	$like			if you want to filter using like
	 * @param	string	$table			In case, you don't want to use the model's default table
	 */
	public function get_total_count($where = FALSE,$like = FALSE,$table = FALSE)
	{
		$table OR $table = $this->table_name;
		
		// select from table
		$this->db->from($table);
		
		// if where is supplied
		if ($where)
		{
			$this->db->where($where);
		}
		
		// if like is supplied
		if ($like)
		{
			$this->db->or_like($like);
		}
		
		return $this->db->count_all_results();
	}
	
	/**
	 * Check if data keys are in the table's columns
	 *
	 * @param	array	$data	the data to be validated
	 */
	private function _validate_data($data)
	{
		foreach($data as $key => $value)
		{
			// if not in the columns
			if (!in_array($key, $this->columns))
			{
				// shoot exception
				throw new Exception('Request contains unknown field: ' . $key, 400);
			}
		}
	}
	
	/**
	 * Check if fields to be selected is selectable
	 *
	 * @param	string	$fields	comma separate values of fields
	 */
	private function _select_fields($fields)
	{
		if ($fields)
		{
			// convert to array
			$fields = explode(',', $fields);
			
			// check if there are fields that is not on the selectable columns
			$wrong_fields = array_diff($fields, $this->selectable_columns);
			
			// if there is, throw an exception
			if (!empty($wrong_fields))
			{
				throw new Exception('Request contains invalid field.');
			}
			
			// else return the shit
			return $fields;
		}
		else
		{
			// if fields are not supplied throwback selectable columns
			return $this->selectable_columns;
		}
	}
	
	/**
	 * Check if fields to be sorted is valid
	 *
	 * @param	string	$sort_field		the field to be sorted
	 */
	private function _sort_field($sort_field)
	{
		if ($sort_field && !in_array($sort_field, $this->sortable_columns))
		{
			throw new Exception('Request contains invalid sort field.');
		}
	
		return $sort_field;
	}
	
	/**
	 * Check if the order is valid
	 *
	 * @param	string	$sort_order		the order
	 */
	private static function _sort_order($sort_order)
	{
		if ($sort_order && !in_array(strtolower($sort_order), array(
			'asc',
			'desc',
			'ascending',
			'descending'
		)))
		{
			throw new Exception('Request contains invalid sort order.');
		}

		return $sort_order ? $sort_order : 'asc';
	}
	
	/**
	 * Check if the like parameter is valid
	 *
	 * @param	string/array	$like	the paramater
	 */
	private function _validate_like($like)
	{
	
		if (is_array($like))
		{
			return $like;
		}
		
		else if (is_string($like))
		{
			$return = array();
		
			foreach($this->searchable_columns as $column)
			{
				$return[$column] = $like;
			}
			
			return $return;
		}
		
		return FALSE;
	}
	
	/**
	 * Cleans limit parameter
	 *
	 * @param	int	$limit	the limit
	 */
	private static function _limit($limit)
	{
		return is_numeric($limit) ? intval($limit) : DEFAULT_QUERY_LIMIT;
	}
	
	
	/**
	 * Cleans page parameter
	 *
	 * @param	int	$page	the page number
	 */
	private static function _page($page)
	{
		return is_numeric($page) ? intval($page) : 1;
	}
	
	/**
	 * Computes the offset
	 *
	 * @param	int	$limit	the limit
	 * @return	int	the offset
	 */
	private static function _offset($limit, $page)
	{
		return (intval($page) - 1) * $limit;
	}
}
