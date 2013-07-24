<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Canonical Model
 *
 * A canonical model for CodeIgniter.
 *
 * @package        	CodeIgniter
 * @subpackage    	Core
 * @category    	Models
 * @author        	Raven John Lagrimas | rjlagrimas08@gmail.com
 * @license         GNU General Public License (GPL)
 * @link			Note yet uploaded
 * @version 		1.0
 */
class MY_Model extends CI_Model 
{

	protected $_time				= NULL;
    protected $table_name			= NULL;	
	protected $columns				= array();
	protected $sortable_columns		= array();
	protected $selectable_columns	= array();

    public function __construct()
    {
    	parent::__construct();
		$this->_time = time();
    }

	public function batch_create($data, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		$insert_data = array();
		foreach($data as $datum){
			$datum['date_created'] 	= $this->_time;
			$datum['date_updated'] 	= $this->_time;
			$insert_data[] = $datum;
		}
		$this->db->insert_batch($table, $insert_data);
	}

	
	public function create($data, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		$this->validate_data($data);
		$data['date_created'] = $this->_time;
		$data['date_updated'] = $this->_time;
		$this->db->insert($table, $data);
		$data['id'] = intval($this->db->insert_id());
		if($data['id'] < 1)
		{
			throw new Exception('Create failed.');
		}
		return $data;
	}


	public function update($id, $data, $table = FALSE)
	{
		$table OR $table = $this->table_name;

		if(!$this->exists($id))
		{
			throw new Exception('Data does not exist.');
		}
		
		$this->validate_data($data);
		
		$data['date_updated'] = $this->_time;
		$this->db->where('id', $id)->update($table, $data);
		if($this->db->affected_rows() < 1)
		{
			throw new Exception('Update failed.');
		}
			
		return $this->get_by_id($id);
	}


	public function delete($id, $table = FALSE)
	{
		$table OR $table = $this->table_name;

		if(!$this->exists($id))
		{
			throw new Exception('Data does not exist.');
		}
			
		$this->db->delete($table, array('id' => $id));
		if($this->db->affected_rows() < 1)
		{
			throw new Exception('Delete failed.');
		}
	}

	
	public function delete_by_fields($fields, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		$this->db->delete($table, $fields);
		if($this->db->affected_rows() < 1)
		{
			throw new Exception('Delete failed.');
		}
	}

	public function exists($id, $table = FALSE)
	{
		$table OR $table = $this->table_name;
		return $this->db->from($table)->where(array('id' => $id))->count_all_results() === 1;
	}

	public function exists_by_fields($fields,$table = FALSE)
	{
		$table OR $table = $this->table_name;
		return $this->db->from($table)->where($fields)->count_all_results() >= 1;
	}


	public function get_by_id($id,$table = FALSE)
	{
		$table OR $table = $this->table_name;
		if($this->exists($id))
		{
			return $this->db->get_where($table, array('id' => $id))->row_array();
		}
		else
		{
			throw new Exception('Data does not exist.');
		}
	}
	
	public function get_by_fields($fields,$page = 1,$limit = DEFAULT_QUERY_LIMIT,$sort_field = NULL,$sort_order = 'ASC',$table = FALSE)
	{
		$table OR $table = $this->table_name;
        $this->db->select()->from($this->table_name)->where($fields);
        if($sort_field !== NULL)
        	$this->db->order_by($sort_field, $sort_order);
		$query = $this->db->limit($limit, self::_offset($limit, $page))->get();
    	return $query->result_array();
	}
	
	public function get_like_by_fields($fields,$page = 1,$limit = DEFAULT_QUERY_LIMIT,$sort_field = NULL,$sort_order = 'asc',$table = FALSE)
	{
		$table OR $table = $this->table_name;
		$this->db->select()->from($this->table_name)->like($fields);
		if($sort_field !== NULL)
        	$this->db->order_by($sort_field, $sort_order);
    	$query = $this->db->limit($limit, self::_offset($limit, $page))->get();
		return $query->result_array();
	}

	public function get_all($fields = FALSE,$page = 1,$limit = DEFAULT_QUERY_LIMIT,$sort_field = NULL,$sort_order = 'asc',$table = FALSE)
	{
		$table OR $table = $this->table_name;
		$page			= self::_page($page);
		$limit			= self::_limit($limit);
		$offset			= self::_offset($limit, $page);
		$sort_order		= self::_sort_order($sort_order);
		$fields			= $this->_select_fields($fields);
		$sort_field		= $this->_sort_field($sort_field);
		
        $this->db->select($fields)->from($table);
		
        if($sort_field !== NULL)
		{
        	$this->db->order_by($sort_field, $sort_order);
		}
			
        if($limit > 0 && $page > 0)
		{
			$this->db->limit($limit, $offset);
		}
			
		$query = $this->db->get();
		
		$return						 = array();
    	$return['data']				 = $query->result_array();
		$return['page']				 = $page;
		$return['items_count']		 = $query->num_rows();
		$return['items_per_page']	 = $limit;
		$return['items_total_count'] = $this->get_total_count();
		return $return;
	}


	public function get_count_like_by_fields($fields = array(),$table = FALSE)
	{
		$table OR $table = $this->table_name;
		return $this->db->select()->from($table)->like($fields)->count_all_results();
	}

	public function get_count_by_fields($fields = array(),$table = FALSE)
	{
		$table OR $table = $this->table_name;
		return $this->db->get_where($table, $fields)->count_all_results();
	}

	public function get_total_count($table = FALSE)
	{
		$table OR $table = $this->table_name;
		return $this->db->count_all($table);
	}
	
	private function validate_data($data)
	{
		foreach($data as $key => $value)
		{
			if(!in_array($key, $this->columns))
			{
				throw new Exception('Request contains unknown field.');
			}
		}
	}
	
	private function _select_fields($fld_str)
	{
		if($fld_str)
		{
			$fields = explode(',', $fld_str);
			$wrong_fields = array_diff($fields, $this->selectable_columns);
			
			if(!empty($wrong_fields))
			{
				throw new Exception('Request contains invalid field.');
			}
			
			return $fld_str;
		}
		else
		{
			return implode(',', $this->selectable_columns);
		}
	}
	
	private function _sort_field($fld_str)
	{
		if ($fld_str && !in_array($fld_str, $this->sortable_columns))
		{
			throw new Exception('Request contains invalid sort field.');
		}
	
		return $fld_str ? $fld_str : NULL;
	}
	
	
	private static function _sort_order($sort_order)
	{
		if($sort_order && !in_array(strtolower($sort_order), array(
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
	
	private static function _limit($limit)
	{
		return is_numeric($limit) ? intval($limit) : DEFAULT_QUERY_LIMIT;
	}
	
	private static function _page($page)
	{
		return is_numeric($page) ? intval($page) : 1;
	}
	
	private static function _offset($limit, $page)
	{
		return (intval($page) - 1) * $limit;
	}
}
