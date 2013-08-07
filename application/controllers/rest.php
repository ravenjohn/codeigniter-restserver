<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rest extends REST_Controller
{

	protected $methods	= array(
		'index_get'	=> array('oauth' => FALSE)
	);
	
	function __construct()
	{
		parent::__construct();
		$this->load->helper('common');
	}

	public function index_get($id = NULL)
	{
		if(ENVIRONMENT !== 'development')
		{
			throw new Exception('API list is available for development environment only.');
		}
		
		$this->load->config('rest');
	
		$data	= array();
		
		if(config_item('rest_enable_oauth'))
		{
			$data['oauth_token_name'] = config_item('rest_token_name');
		}
		
		$dir	= APPPATH.'/controllers/*.php';

        foreach (glob($dir) as $file)
		{
            require_once($file);
			
			$filename	= substr($file,strrpos($file, '/') + 1);
			$name		= substr($filename, 0, strrpos($filename, '.'));
			$classname	= ucfirst($name);
			
			if ($classname === 'Rest' || (!config_item('rest_enable_oauth') && $classname === 'Oauth'))
			{
				continue;
			}
			
			$modelname	= $classname . '_model';
			$ctlr		= new $classname();
			$model		= new $modelname();
			
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
			
				$method			= $value;
				$method_name	= substr($key, 0, strrpos($key, '_'));
				$verb			= strtoupper(substr($key, strrpos($key, '_') + 1));
				$method[$verb]	= getApiURL();
					
				if($verb === 'GET' && $method_name === 'index')
				{
					$method['params'] = getDefaultGETParams();
				}
				
				if($method_name !== 'index')
				{
					$method[$verb] .= $name . '/'.$method_name;
				}
				
				if(isset($method['url_format']))
				{
					$method[$verb] = array();
					
					foreach($method['url_format'] as $format)
					{
						$method[$verb][] = getApiURL() . $format;
					}
					unset($method['url_format']);
				}
				
				else if($method_name === 'index')
				{
					$method[$verb] .= $name;
				}
				
				if(!config_item('rest_enable_oauth') && isset($method['oauth']))
				{
					unset($method['oauth']);
				}
					
				$methods[] = $method;
			}

			$api['methods']				= $methods;
			$data['API'][$classname]	= $api;
		}

		$this->response($data);
	}
}

