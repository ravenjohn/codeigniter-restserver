<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class OAuth extends REST_Controller
{
	public $methods = array(
		'index_post'		=> array('oauth' => FALSE, 'description' => 'Register your application here.'),
		'token_post'		=> array('oauth' => FALSE, 'params' => '!app_id, !app_secret', 'description' => 'Get your access token here.'),
		'regenerate_put'	=> array('params' => '!app_id, !app_secret', 'description' => 'Renew your app_secret and access_token here.')
	);
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('oauth_model');
	}
	
	public function index_post()
	{
		$data			= array();
		$data['name']	= $this->post('name');
		
		if ($data['name'] === FALSE)
		{
			throw new Exception('Parameter name is missing.');
		}
		
		$this->oauth_model->validate_name_uniqueness($data['name']);
		
		$data['id']				= uuid();
		$data['app_secret']		= uuid();
		$data['access_token']	= uuid();
		$data = $this->oauth_model->create($data, 'id,app_secret');
		$this->response($data);
	}

	public function token_post()
	{
		$data = $this->oauth_model->get_access_token($this->post('app_id'), $this->post('app_secret'));
		$this->response($data);
	}
	
	public function regenerate_put($id = NULL)
	{
		$this->oauth_model->get_access_token($id, $this->put('app_secret'));
		$data					= array();
		$data['app_secret']		= uuid();
		$data['access_token']	= uuid();
		$data = $this->oauth_model->update($id, $data, 'id,app_secret');
		$this->response($data);
	}
}

