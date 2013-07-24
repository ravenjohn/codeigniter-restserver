<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Key extends REST_Controller
{
	public $methods = array(
		'index_post'		=> array('level' => ADMIN_LEVEL),
		'index_put'			=> array('level' => ADMIN_LEVEL),
		'index_delete'		=> array('level' => ADMIN_LEVEL),
		'regenerate_put'	=> array('level' => ADMIN_LEVEL)
	);
	
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('Utils');
		$this->load->model('key_model');
	}
	
	public function index_post()
	{
		$data = array();
		$data['key']			= $this->utils->uuid();
		$data['level']			= $this->post('level') ? $this->post('level') : 1;
		$data['ignore_limits']	= $this->post('ignore_limits') ? $this->post('ignore_limits') : 1;
		$this->response( $this->key_model->create( $data ) );
	}
	
	public function index_put($id)
	{
		$this->response(
			$this->key_model->update(
				$id,
				$this->put()
			)
		);
	}
	
	
	public function index_delete($id)
	{	
		$this->key_model->delete($id);
		$this->response(array('message' => 'Delete successful.'));
	}
	
	
	
	public function regenerate_put($id)
	{
		$this->response(
			$this->key_model->update(
				$id,
				array('key' => $this->utils->uuid())
			)
		);
	}
}

