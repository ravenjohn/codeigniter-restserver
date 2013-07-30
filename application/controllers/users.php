<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Users extends REST_Controller
{
	public $methods = array(
		'index_get'		=> array('oauth' => false, 'description' => 'Get paginated users.'),
		'index_post'	=> array('params' => '!name, ?fields', 'description' => 'Create a new user using this method. You can use ?fields to limit the returned data.'),
		'index_put'		=> array('params' => '!name, ?fields', 'description' => 'Use this method to update the name of the user. You can use ?fields to limit the returned data.'),
		'index_delete'	=> array('description' => 'Use this method to delete a user.')
	);
	
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('users_model');
	}

	public function index_get($id = NULL)
	{
	
		if (is_numeric($id))
		{
			$data = $this->users_model->get_by_id($id, $this->get('fields'));
		}
		
		else
		{
			$data = $this->users_model->get_all(
					FALSE,
					$this->get('search_key'),
					$this->get('fields'),
					$this->get('page'),
					$this->get('limit'),
					$this->get('sort_field'),
					$this->get('sort_order'));
		}
		
		$this->response($data);
	}
	
	
	public function index_post()
	{
		$data = $this->users_model->create($this->post(), $_GET['fields'] );
		$this->response($data);
	}
	
	
	public function index_put($id)
	{
		$data = $this->users_model->update(
				$id,
				$this->put(),
				$_GET['fields']
			);
		$this->response($data);
	}
	
	
	public function index_delete($id)
	{	
		$this->users_model->delete($id);
		$this->response(array('message' => 'Delete successful.'));
	}
}

