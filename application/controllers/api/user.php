<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends REST_Controller
{
	public $methods = array(
		'index_get'		=> array('key' => FALSE),
		'index_post'	=> array('level' => ADMIN_LEVEL),
		'index_put'		=> array('level' => ADMIN_LEVEL),
		'index_delete'	=> array('level' => ADMIN_LEVEL)
	);
	
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('user_model');
	}

	public function index_get($id = NULL)
	{
	
		if (is_numeric($id))
		{
			$this->response(
				$this->user_model->get_by_id(
					$id,
					$this->get('fields')
				)
			);
		}
		
		else
		{
			$this->response(
				$this->user_model->get_all(
					FALSE,
					$this->get('key'),
					$this->get('fields'),
					$this->get('page'),
					$this->get('limit'),
					$this->get('sort_field'),
					$this->get('sort_order')
				)
			);
		}
	}
	
	public function index_post()
	{
		$this->response(
			$this->user_model->create(
				$this->post(),
				$_GET['fields']
			)
		);
	}
	
	public function index_put($id)
	{
		$this->response(
			$this->user_model->update(
				$id,
				$this->put(),
				$_GET['fields']
			)
		);
	}
	
	
	public function index_delete($id)
	{	
		$this->user_model->delete($id);
		$this->response(array('message' => 'Delete successful.'));
	}
}

