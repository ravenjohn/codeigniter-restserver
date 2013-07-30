<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rest extends REST_Controller
{
	protected $methods = array(
		'index_get'		=> array('oauth' => FALSE)
	);
	
	
	function __construct()
	{
		parent::__construct();
	}

	public function index_get($id = NULL)
	{
		if(ENVIRONMENT !== 'development')
		{
			throw new Exception('API list is available for development environment only.');
		}
		
		$this->load->config('rest');
	
		$data	= array();
		
		$data['oauth_token_name'] = config_item('rest_token_name');
		
		$dir	= APPPATH.'/controllers/*.php';

        foreach (glob($dir) as $file)
		{
            require_once($file);
			
			$filename	= substr($file,strrpos($file, '/') + 1);
			
			$name		= substr($filename, 0, strrpos($filename, '.'));
			$classname	= ucfirst($name);
			
			if ($classname === 'Rest')
			{
				continue;
			}
			
			$modelname = $classname . '_model';
			
			$ctlr = new $classname();
			$model = new $modelname();
			
			$api = array();
		
			$api['columns']		= implode(', ', $model->columns);
			
			if (!empty($model->selectable_columns))
			{
				$api['selectable']	= implode(', ', $model->selectable_columns);
			}
			
			if (!empty($model->sortable_columns))
			{
				$api['sortable']	= implode(', ', $model->sortable_columns);
			}
			
			if (!empty($model->searchable_columns))
			{
				$api['searchable']	= implode(', ', $model->searchable_columns);
			}
						
			$methods = array();
			
			foreach($ctlr->methods as $key => $value)
			{
			
				$method = $value;
				
				$method_name = substr($key, 0, strrpos($key, '_'));
				
				$verb = strtoupper(substr($key, strrpos($key, '_') + 1));

				$method[$verb] = getApiURL().$name;
					
				if($verb === 'GET' && $method_name === 'index')
				{
					$method['params'] = getDefaultGETParams();
					$temp = $method['GET'];
					$method['GET'] = array();
					$method['GET'][] = $temp;
					$method['GET'][] = $temp .'/:id';
				}
				
				if($method_name !== 'index')
				{
					$method[$verb] .= '/'.$method_name;
				}
					
				if($verb !== 'GET' && $verb !== 'POST')
				{
					$method[$verb] .= '/:id';
				}
					
				$methods[] = $method;
			}

			$api['methods'] = $methods;
			
			$data['API'][$classname] = $api;
		}

		$this->response($data);
	}
}

