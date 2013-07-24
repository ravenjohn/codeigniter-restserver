<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api_List extends REST_Controller
{
	protected $methods = array(
		'index_get'		=> array('level' => NOOB_LEVEL, 'key' => FALSE)
	);
	
	
	function __construct()
	{
		parent::__construct();
	}

	public function index_get($id = NULL)
	{
		if(ENVIRONMENT !== 'development')
		{
			throw new Exception('Unknown method.');
		}
	
		$data	= array();
		
		$data['levels'][SUSPENDED_LEVEL] = 'SUSPENDED_LEVEL';
		$data['levels'][NOOB_LEVEL] = 'NOOB_LEVEL';
		$data['levels'][USER_LEVEL] = 'USER_LEVEL';
		$data['levels'][ADMIN_LEVEL] = 'ADMIN_LEVEL';
		$data['levels'][ROOT_LEVEL] = 'ROOT_LEVEL';
		
		
		$dir	= APPPATH.'/controllers/api/';
		$files	= scandir($dir);

		$files = array_filter($files, function($filename) {
			return (substr(strrchr($filename, '.'), 1)=='php') ? true : false;
		});

        foreach ($files as $filename)
        {
            require_once('./application/controllers/api/'.$filename);
			$name		= substr($filename, 0, strrpos($filename, '.'));
			$classname	= ucfirst($name);
			$ctlr = new $classname();
			
			$methods = array();
			
			foreach($ctlr->methods as $key => $value)
			{
			
				$method = $value;
				$method['method_name'] = substr($key, 0, strrpos($key, '_'));
				$method['HTTP_VERB'] = strtoupper(substr($key, strrpos($key, '_') + 1));

				$method['url'] = getApiURL().$name;
				
				if($method['method_name'] !== 'index')
					$method['url'] .= '/'.$method['method_name'];
					
				if($method['HTTP_VERB'] !== 'GET')
					$method['url'] .= '/:id';
					
				if($method['HTTP_VERB'] === 'GET' && $method['method_name'] === 'index')
					$method['url2'] = getApiURL().$name .'/:id';

				$methods[] = $method;
			}
			
			$controller['classname'] = $classname;

			$controller['base_url'] = getApiURL().$name;

			$controller['methods'] = $methods;
			
			$data['controllers'][] = $controller;
		}
		
		$this->response($data);
	}
}

