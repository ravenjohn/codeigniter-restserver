<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Rest Controller
 *
 * A fully RESTful server implementation for CodeIgniter
 *
 * @package        	CodeIgniter
 * @subpackage    	Libraries
 * @category    	Libraries
 * @authors        	Phil Sturgeon
 * @license         http://philsturgeon.co.uk/code/dbad-license
 * @link			https://github.com/philsturgeon/codeigniter-restserver
 * @version 		2.6.2
 */
 
 
/**
 * CodeIgniter Rest Controller
 *
 * A fully RESTful server implementation for CodeIgniter
 *
 * @package        	CodeIgniter
 * @subpackage    	Libraries
 * @category    	Libraries
 * @authors        	Raven Lagrimas
 * @license         MITP
 * @link			https://github.com/ravenjohn/codeigniter-restserver
 * @version 		1.0.0
 */
class REST_Controller extends CI_Controller
{
	/**
	 * This defines the rest format.
	 *
	 * Must be overridden it in a controller so that it is set.
	 *
	 * @var string|null
	 */
	protected $rest_format = NULL;

	/**
	 * Defines the list of method properties such as limit, log and level
	 *
	 * @var array
	 */
	protected $methods = array();

	/**
	 * List of allowed HTTP methods
	 *
	 * @var array
	 */
	protected $allowed_http_methods = array('get', 'delete', 'post', 'put');

	/**
	 * General request data and information.
	 * Stores accept, language, body, headers, etc.
	 *
	 * @var object
	 */
	protected $request = NULL;

	/**
	 * What is gonna happen in output?
	 *
	 * @var object
	 */
	protected $response = NULL;

	/**
	 * The arguments for the GET request method
	 *
	 * @var array
	 */
	protected $_get_args = array();

	/**
	 * The arguments for the POST request method
	 *
	 * @var array
	 */
	protected $_post_args = array();

	/**
	 * The arguments for the PUT request method
	 *
	 * @var array
	 */
	protected $_put_args = array();

	/**
	 * The arguments for the DELETE request method
	 *
	 * @var array
	 */
	protected $_delete_args = array();

	/**
	 * The arguments from GET, POST, PUT, DELETE request methods combined.
	 *
	 * @var array
	 */
	protected $_args = array();

	/**
	 * Determines if output compression is enabled
	 *
	 * @var boolean
	 */
	protected $_zlib_oc = FALSE;

	/**
	 * List all supported methods, the first will be the default format
	 *
	 * @var array
	 */
	protected $_supported_formats = array(
		'json'			=> 'application/json',
		'xml'			=> 'application/xml',
		'jsonp'			=> 'application/javascript',
		'serialized'	=> 'application/vnd.php.serialized',
		'php'			=> 'text/plain',
		'html'			=> 'text/html',
		'csv'			=> 'application/csv'
	);
	
	/**
	 * The method to fire
	 *
	 * @var string
	 */
	protected $current_method = NULL;
	
	/**
	 * User
	 *
	 * @var array
	 */
	protected $_user = NULL;
	
	/**
	 * Access Token
	 *
	 * @var string
	 */
	protected $access_token = '';
	
	/**
	 * Fields
	 *
	 * @var array
	 */
	protected $_fields = '';
	
	/**
	 * App ID or User ID
	 *
	 * @var string
	 */
	protected $app_or_user_id = '';

	/**
	 * Constructor function
	 * @todo Document more please.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_zlib_oc = @ini_get('zlib.output_compression');

		// Lets grab the config and get ready to party
		$this->load->config('rest');

		// let's learn about the request
		$this->request = new stdClass();

		// Is it over SSL?
		$this->request->ssl = $this->_detect_ssl();

		// How is this request being made? POST, DELETE, GET, PUT?
		$this->request->method = $this->_detect_method();

		// Create argument container, if non-existent like OPTIONS
		if ( ! isset($this->{'_'.$this->request->method.'_args'}))
		{
			$this->{'_'.$this->request->method.'_args'} = array();
		}

		// Set up our GET variables
		$this->_get_args = array_merge($this->_get_args, $this->uri->ruri_to_assoc());

		$this->load->library('security');

		// This library is bundled with REST_Controller 2.5+, but will eventually be part of CodeIgniter itself
		$this->load->library('format');

		// Try to find a format for the request (means we have a request body)
		$this->request->format = $this->_detect_input_format();

		// Some Methods cant have a body
		$this->request->body = NULL;

		$this->{'_parse_' . $this->request->method}();

		// Now we know all about our request, let's try and parse the body if it exists
		if ($this->request->format and $this->request->body)
		{
			$this->request->body = $this->format->factory($this->request->body, $this->request->format)->to_array();
			// Assign payload arguments to proper method container
			$this->{'_'.$this->request->method.'_args'} = $this->request->body;
		}

		// Merge both for one mega-args variable
		$this->_args = array_merge($this->_get_args, $this->_put_args, $this->_post_args, $this->_delete_args, $this->{'_'.$this->request->method.'_args'});

		// Which format should the data be returned in?
		$this->response = new stdClass();
		$this->response->format = $this->_detect_output_format();

		// Which format should the data be returned in?
		$this->response->lang = $this->_detect_lang();
		
		// only allow ajax requests
		if ( ! $this->input->is_ajax_request() AND config_item('rest_ajax_only'))
		{
			$this->response(array('error' => 'Only AJAX requests are accepted.'), 505);
		}
		
		// get fields to be selected
		$this->_fields = isset($_GET['fields']) ? $_GET['fields'] : FALSE;

		// load model
		if (isset($this->_model))
		{
			$this->load->model(strtolower($this->_model . '_model'));
		}
	}

	/**
	 * Remap
	 *
	 * Requests are not made to methods directly, the request will be for
	 * an "object". This simply maps the object and method to the correct
	 * Controller method.
	 *
	 * @param string $object_called
	 * @param array $arguments The arguments passed to the controller method.
	 */
	public function _remap($object_called, $arguments)
	{
		try
		{
		
			if (is_numeric($object_called) || strlen($object_called) === 32)
			{
				$arguments[] = $object_called;
				$object_called = 'index';
			}
			
			// Should we answer if not over SSL?
			if (config_item('force_https') AND !$this->_detect_ssl())
			{
				throw new Exception('Unsupported protocol', 403);
			}

			$pattern = '/^(.*)\.('.implode('|', array_keys($this->_supported_formats)).')$/';
			if (preg_match($pattern, $object_called, $matches))
			{
				$object_called = $matches[1];
			}

			$this->current_method = $object_called.'_'.$this->request->method;
			
			// Use OAuth for this method?
			if(config_item('rest_enable_oauth') && isset($this->methods[$this->current_method]['scope']))
			{
				$this->_detect_access_token($this->methods[$this->current_method]['scope']);
			}
			else
			{
				$this->_log_request();
			}

			// Sure it exists, but can they do anything with it?
			if ( ! method_exists($this, $this->current_method))
			{
				throw new Exception('Unknown method.', 404);
			}
			
			// Get params if specified
			$params				= isset($this->methods[$this->current_method]['params']) ? $this->methods[$this->current_method]['params'] : array();
			$required_fields	= array();
			
			// filter required fields
			foreach($params as $param)
			{
				if($param[0] != '?')
				{
					$required_fields[] = $param;
				}
			}			
			
			// get data based on the request method
			$this->data = call_user_func_array(array($this, $this->request->method), array());
			
			// verify every required fields
			$this->data = $this->_require_fields($required_fields, $this->data);
			
			
			// check if we will ignore extra data
			if(	config_item('ignore_extra_data') === FALSE ||
				(isset($this->methods[$this->current_method]['ignore_extra_data']) &&
				$this->methods[$this->current_method]['ignore_extra_data'] === FALSE))
			{
				foreach($this->data as $param => $datum)
				{
					if(!in_array($param, $params))
					{
						throw new Exception('Sorry we don\' accept requests with extra data: [  ' . $param . ' => ' . $datum .' ]');
					}
				}
			}
			
			
			// get locked columns
			$locked	= isset($this->methods[$this->current_method]['lock']) ? $this->methods[$this->current_method]['lock'] : array();
			
			
			// lock it
			self::_lock_fields($locked, $this->data);

			// And...... GO!
			$this->_fire_method(array($this, $this->current_method), $arguments);
		}
		catch(Exception $e)
		{
			$this->response(array('error' => $e->getMessage()), $e->getCode() > 0 ? $e->getCode() : 400);
		}
	}

	/**
	 * Fire Method
	 *
	 * Fires the designated controller method with the given arguments.
	 *
	 * @param array $method The controller method to fire
	 * @param array $args The arguments to pass to the controller method
	 */
	protected function _fire_method($method, $args)
	{
		call_user_func_array($method, $args);
	}

	/**
	 * Response
	 *
	 * Takes pure data and optionally a status code, then creates the response.
	 *
	 * @param array $data
	 * @param null|int $http_code
	 */
	public function response($data = array(), $http_code = 200)
	{
		global $CFG;

		if(ENVIRONMENT === 'development')
		{
			$data['method']			= $this->current_method;
			$data['memory_usage']	= ( ! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB';
			
			if (isset($_SERVER['REQUEST_TIME_FLOAT']))
			{
				$data['response_time'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']; 
			}
			else
			{
				$data['response_time'] = floatval($this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end'));
			}
		}

		// Is compression requested?
		if ($CFG->item('compress_output') === TRUE
			&& $this->_zlib_oc == FALSE
			&& extension_loaded('zlib')
			&& isset($_SERVER['HTTP_ACCEPT_ENCODING'])
			&& strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE)
		{
			ob_start('ob_gzhandler');
		}

		is_numeric($http_code) OR $http_code = 200;

		// If the format method exists, call and return the output in that format
		if (method_exists($this, '_format_'.$this->response->format))
		{
			// Set the correct format header
			header('Content-Type: '.$this->_supported_formats[$this->response->format]);

			$output = $this->{'_format_'.$this->response->format}($data);
		}

		// If the format method exists, call and return the output in that format
		elseif (method_exists($this->format, 'to_'.$this->response->format))
		{
			// Set the correct format header
			header('Content-Type: '.$this->_supported_formats[$this->response->format]);

			$output = $this->format->factory($data)->{'to_'.$this->response->format}();
		}

		// Format not supported, output directly
		else
		{
			$output = $data;
		}

		header('HTTP/1.1: ' . $http_code);
		header('Status: ' . $http_code);

		// If zlib.output_compression is enabled it will compress the output,
		// but it will not modify the content-length header to compensate for
		// the reduction, causing the browser to hang waiting for more data.
		// We'll just skip content-length in those cases.
		if ( ! $this->_zlib_oc && ! $CFG->item('compress_output'))
		{
			header('Content-Length: ' . strlen($output));
		}

		exit($output);
	}

	/*
	 * Detect SSL use
	 *
	 * Detect whether SSL is being used or not
	 */
	protected function _detect_ssl()
	{
		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on");
	}


	/*
	 * Detect input format
	 *
	 * Detect which format the HTTP Body is provided in
	 */
	protected function _detect_input_format()
	{
		if ($this->input->server('CONTENT_TYPE'))
		{
			// Check all formats against the HTTP_ACCEPT header
			foreach ($this->_supported_formats as $format => $mime)
			{
				if (strpos($match = $this->input->server('CONTENT_TYPE'), ';'))
				{
					$match = current(explode(';', $match));
				}

				if ($match == $mime)
				{
					return $format;
				}
			}
		}

		return NULL;
	}

	/**
	 * Detect format
	 *
	 * Detect which format should be used to output the data.
	 *
	 * @return string The output format.
	 */
	protected function _detect_output_format()
	{
		$pattern = '/\.('.implode('|', array_keys($this->_supported_formats)).')$/';

		// Check if a file extension is used
		if (preg_match($pattern, $this->uri->uri_string(), $matches))
		{
			return $matches[1];
		}

		// Check if a file extension is used
		elseif ($this->_get_args AND !is_array(end($this->_get_args)) AND preg_match($pattern, end($this->_get_args), $matches))
		{
			// The key of the last argument
			$last_key = end(array_keys($this->_get_args));

			// Remove the extension from arguments too
			$this->_get_args[$last_key] = preg_replace($pattern, '', $this->_get_args[$last_key]);
			$this->_args[$last_key] = preg_replace($pattern, '', $this->_args[$last_key]);

			return $matches[1];
		}

		// A format has been passed as an argument in the URL and it is supported
		if (isset($this->_get_args['format']) AND array_key_exists($this->_get_args['format'], $this->_supported_formats))
		{
			return $this->_get_args['format'];
		}

		// Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
		if ($this->config->item('rest_ignore_http_accept') === FALSE AND $this->input->server('HTTP_ACCEPT'))
		{
			// Check all formats against the HTTP_ACCEPT header
			foreach (array_keys($this->_supported_formats) as $format)
			{
				// Has this format been requested?
				if (strpos($this->input->server('HTTP_ACCEPT'), $format) !== FALSE)
				{
					// If not HTML or XML assume its right and send it on its way
					if ($format != 'html' AND $format != 'xml')
					{

						return $format;
					}

					// HTML or XML have shown up as a match
					else
					{
						// If it is truly HTML, it wont want any XML
						if ($format == 'html' AND strpos($this->input->server('HTTP_ACCEPT'), 'xml') === FALSE)
						{
							return $format;
						}

						// If it is truly XML, it wont want any HTML
						elseif ($format == 'xml' AND strpos($this->input->server('HTTP_ACCEPT'), 'html') === FALSE)
						{
							return $format;
						}
					}
				}
			}
		} // End HTTP_ACCEPT checking

		// Well, none of that has worked! Let's see if the controller has a default
		if ( ! empty($this->rest_format))
		{
			return $this->rest_format;
		}

		// Just use the default format
		return config_item('rest_default_format');
	}

	/**
	 * Detect method
	 *
	 * Detect which HTTP method is being used
	 *
	 * @return string
	 */
	protected function _detect_method()
	{
		$method = strtolower($this->input->server('REQUEST_METHOD'));

		if ($this->config->item('enable_emulate_request'))
		{
			if ($this->input->post('_method'))
			{
				$method = strtolower($this->input->post('_method'));
			}
			else if ($this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE'))
			{
				$method = strtolower($this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE'));
			}
		}

		if (in_array($method, $this->allowed_http_methods) && method_exists($this, '_parse_' . $method))
		{
			return $method;
		}

		return 'get';
	}
	
	/**
	 * Detect Access Token
	 *
	 * See if the user has provided an access token
	 *
	 * @return boolean
	 */
	protected function _detect_access_token()
	{
		// Get the api token name variable set in the rest config file
		$access_token_variable = config_item('rest_token_name');

		// Work out the name of the SERVER entry based on config
		$token_name = 'HTTP_'.strtoupper(str_replace('-', '_', $access_token_variable));
		
		$access_token = '';

		// get access token
		if(isset($this->_args[$access_token_variable]))
		{
			$access_token = $this->_args[$access_token_variable];
		}
		else if($this->input->server($token_name))
		{
			$access_token = $this->input->server($token_name);
		}
		else
		{
			$this->_log_request();
			$this->response(array('error' => 'Missing access token.'), 403);
		}
		
		
		/*
		 *
		 * 	EDIT THIS PART TO FIT ON YOUR SYSTEM
		 *	GOAL: VERIFY ACCESS_TOKEN | $this->access_token
		 *
		 */
		
		
		// get user with that access token
		$this->load->model('users_model');
		$user = $this->users_model->get_user_by_access_token($this->access_token);
		
		// if not found
		if(empty($user))
		{
			$this->response(array('error' => 'Invalid access token.'), 403);
			$this->_log_request();
		}
		else
		{
			if(in_array($user['scope'], $scopes))
			{
				$this->user = $user;
				$this->_log_request(TRUE);
			}
			else
			{
				$this->_log_request();
				$this->response(array('error' => 'This method is not for you.'), 403);
			}
		}
		
		
		// remove access token
		unset($this->_args[$access_token_variable]);
		unset($this->{'_' . $this->request->method . '_args'}[$access_token_variable]);
	}
	
	/**
	 * Detect language(s)
	 *
	 * What language do they want it in?
	 *
	 * @return null|string The language code.
	 */
	protected function _detect_lang()
	{
		if ( ! $lang = $this->input->server('HTTP_ACCEPT_LANGUAGE'))
		{
			return NULL;
		}

		// They might have sent a few, make it an array
		if (strpos($lang, ',') !== FALSE)
		{
			$langs = explode(',', $lang);

			$return_langs = array();
			foreach ($langs as $lang)
			{
				// Remove weight and strip space
				list($lang) = explode(';', $lang);
				$return_langs[] = trim($lang);
			}

			return $return_langs;
		}

		// Nope, just return the string
		return $lang;
	}

	/**
	 * Log request
	 *
	 * Record the entry for awesome purposes
	 *
	 * @param boolean $authorized
	 */
	protected function _log_request($authorized = FALSE)
	{
		if(	(isset($this->methods[$this->current_method]['log']) && $this->methods[$this->current_method]['log']) ||
			(!isset($this->methods[$this->current_method]['log']) && config_item('rest_enable_logging'))){
			
			$this->load->model('logs_model');
			$this->logs_model->create(array(
				'app_or_user_id'	=> $this->app_or_user_id,
				'uri'				=> $this->uri->uri_string(),
				'method'			=> $this->request->method,
				'params'			=> json_encode($this->_args),
				'access_token'		=> $this->access_token,
				'ip_address'		=> $this->input->ip_address(),
				'authorized'		=> $authorized
			));
		}
	}

	/**
	 * Parse GET
	 */
	protected function _parse_get()
	{
		// Grab proper GET variables
		parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $get);

		// Merge both the URI segments and GET params
		$this->_get_args = array_merge($this->_get_args, $get);
	}

	/**
	 * Parse POST
	 */
	protected function _parse_post()
	{
		$this->_post_args = $_POST;

		$this->request->format and $this->request->body = file_get_contents('php://input');
	}

	/**
	 * Parse PUT
	 */
	protected function _parse_put()
	{
		// It might be a HTTP body
		if ($this->request->format)
		{
			$this->request->body = file_get_contents('php://input');
		}

		// If no file type is provided, this is probably just arguments
		else
		{
			parse_str(file_get_contents('php://input'), $this->_put_args);
		}
	}

	/**
	 * Parse DELETE
	 */
	protected function _parse_delete()
	{
		// Set up out DELETE variables (which shouldn't really exist, but sssh!)
		parse_str(file_get_contents('php://input'), $this->_delete_args);
	}

	// INPUT FUNCTION --------------------------------------------------------------

	/**
	 * Retrieve a value from the GET request arguments.
	 *
	 * @param string $key The key for the GET request argument to retrieve
	 * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
	 * @return string The GET argument value.
	 */
	public function get($key = NULL, $xss_clean = TRUE)
	{
		if ($key === NULL)
		{
			return $this->_get_args;
		}

		return array_key_exists($key, $this->_get_args) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : FALSE;
	}

	/**
	 * Retrieve a value from the POST request arguments.
	 *
	 * @param string $key The key for the POST request argument to retrieve
	 * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
	 * @return string The POST argument value.
	 */
	public function post($key = NULL, $xss_clean = TRUE)
	{
		if ($key === NULL)
		{
			return $this->_post_args;
		}

		return array_key_exists($key, $this->_post_args) ? $this->_xss_clean($this->_post_args[$key], $xss_clean) : FALSE;
	}

	/**
	 * Retrieve a value from the PUT request arguments.
	 *
	 * @param string $key The key for the PUT request argument to retrieve
	 * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
	 * @return string The PUT argument value.
	 */
	public function put($key = NULL, $xss_clean = TRUE)
	{
		if ($key === NULL)
		{
			return $this->_put_args;
		}

		return array_key_exists($key, $this->_put_args) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : FALSE;
	}

	/**
	 * Retrieve a value from the DELETE request arguments.
	 *
	 * @param string $key The key for the DELETE request argument to retrieve
	 * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
	 * @return string The DELETE argument value.
	 */
	public function delete($key = NULL, $xss_clean = TRUE)
	{
		if ($key === NULL)
		{
			return $this->_delete_args;
		}

		return array_key_exists($key, $this->_delete_args) ? $this->_xss_clean($this->_delete_args[$key], $xss_clean) : FALSE;
	}

	/**
	 * Process to protect from XSS attacks.
	 *
	 * @param string $val The input.
	 * @param boolean $process Do clean or note the input.
	 * @return string
	 */
	protected function _xss_clean($val, $process)
	{
		if (CI_VERSION < 2)
		{
			return $process ? $this->input->xss_clean($val) : $val;
		}

		return $process ? $this->security->xss_clean($val) : $val;
	}

	// FORMATING FUNCTIONS ---------------------------------------------------------
	// Many of these have been moved to the Format class for better separation, but these methods will be checked too

	/**
	 * Encode as JSONP
	 *
	 * @param array $data The input data.
	 * @return string The JSONP data string (loadable from Javascript).
	 */
	protected function _format_jsonp($data = array())
	{
		return $this->get('callback').'('.json_encode($data).')';
	}
	
	/**
	 * Require fields
	 *
	 * @param	array	$required_fields	array of string
	 * @param	array	$data				the array to check if fields exist and has value
	 * @return	array	cleansed data
	 *
	 * Note : This function considers 0 / 0.0 / "0" as empty value
	 */
	protected function _require_fields($required_fields, $data)
	{
		// loop through required fields
		foreach($required_fields as $field)
		{
			// check if not existing or empty
			if ( !isset($data[$field]) || empty($data[$field]))
			{
				throw new Exception('Oooops! It seems like you missed your ' . str_replace('_',' ',$field) . '.', 400);
			}
		}
		
		// loop through the data
		foreach ($data as $key => $param)
		{
			//remove empty parameters
			if(empty($param))
			{
				unset($data[$key]);
			}
			// else clean the parameter
			else
			{
				$data[$key] = $this->_xss_clean($param, TRUE);
			}
		}
		
		// return cleansed data
		return $data;
	}
	
	/**
	 * Lock fields
	 *
	 * @param	array	$lock_fields		array of string
	 * @param	array	$data				the array to check if fields exist
	 * @return	array	cleansed data
	 *
	 * Note : This function considers 0 / 0.0 / "0" as empty value
	 */
	protected static function _lock_fields($lock_fields, $data)
	{
		// loop through fields  to lock
		foreach($lock_fields as $field)
		{
			// check if field is set
			if (isset($data[$field]))
			{
				throw new Exception('Sorry to say but ' . str_replace('_',' ',$field) . ' is locked.', 400);
			}
		}
	}

	
	/**
	 * Check string length
	 *
	 * @param	string	$string		string
	 * @param	int		$len		min length
	 */
	protected static function check_strlen($string, $len = 6)
	{
		if($string && strlen($string) < $len)
		{
			throw new Execption('Ooops! Your ' . str_replace('_',' ',$string) . ' is too short. It should be at least ' . $len);
		}
	}
	
	/**
	 * Check if string is a valid email
	 *
	 * @param	string	$string		string
	 */
	protected static function _check_email($string)
	{
		if($string && ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $string))
		{
			throw new Exception('Are you sure about the email? It doesn\'t look like one.');
		}
	}
	
	/**
	 * Check if string is boolean
	 *
	 * @param	string	$string		string
	 */
	protected static function _check_boolean($string)
	{
		$allowed	= array('true', 'false', '1', '0');
		$string		= strtolower($string);
		if($string && !in_array($string, $allowed))
		{
			throw new Exception( 'Oops! You sent an invalid boolean value. For reference, these are the allowed boolean values : ' . implode(', ', $allowed));
		}
	}
	
	/**
	 * Check if string is numeric
	 *
	 * @param	string	$string		string
	 * @param	string	$name		name of the string
	 */
	protected static function _check_numeric($string, $name)
	{
		if($string && !is_numeric($string))
		{
			throw new Exception( 'Oops! ' . $name . ' is not numeric.');
		}
	}
	
	/**
	 * Check if string is a valid date
	 *
	 * @param	string	$string		string
	 * @param	string	$name		name of the string
	 * @param	string	$format		format of the date, optional
	 */
	protected static function _check_date($string, $name, $format = 'Y-m-d H:i:s')
	{
		if($string && DateTime::createFromFormat($format, $string) === FALSE)
		{
			throw new Exception('Ooops! ' . $name . ' does not look like a date. Here\'s a sample : ' . date($format, now()));
		}
	}
	
	protected static function _check_in_array($string, $array, $name)
	{
		if ($string && !in_array($string, $array))
		{
			throw new Exception('I\'m sorry but the ' . $name . ' did not match one of these: ' . implode(', ', $array), 400);
		}		
	}
	
	
	private static function _get_upload_error_message($error_code) {
		$error_message = NULL;
		switch($error_code) {
			case 0: break;
			case 2: $error_message = 'The uploaded file exceeds the File size limit of 4MB';
				break;
			case 3: $error_message = 'The uploaded file was only partially uploaded';
				break;
			case 4: $error_message = 'No file was uploaded';
				break;
			case 6: $error_message = 'Missing a temporary folder';
				break;
			case 7: $error_message = 'Failed to write file to disk';
				break;
			case 8: $error_message = 'A PHP extension stopped the file upload';
				break;
			default:
					$error_message = 'Unkown Error encountered while uploading the file';
		}
		return $error_message;
	}
	
	protected static function get_file($name, $allowed_extensions = array(), $allowed_size = 2097152)
	{
		$file = FALSE;
		if (isset($_FILES[$name]))
		{
			$file = $_FILES[$name];
			$path = $file['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			
			if($file['error'] > 0)
			{
				throw new Exception(self::get_upload_error_message($file['error']));
			}
			
			if(!in_array($ext, $allowed_extensions))
			{
				throw new Exception('Invalid file type. These are the allowed file types : ' . implode(', ', $allowed_extensions));
			}
			
			if($file['size'] > $allowed_size)
			{
				throw new Exception('File too large. Max file size is 2MB');
			}
		}
		return $file;
	}
	
	
	/**
	 * Simple CURL
	 *
	 * @param	string	$method		HTTP METHOD
	 * @param	string	$url		url
	 * @param	array	$fields		payload
	 */
	protected static function curl($method, $url, $fields=array())
	{
		//format fields
		$fields_string = array('message' => json_encode($fields));

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);
		
		if($method=='POST')
		{
			curl_setopt($ch,CURLOPT_POST, 1);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
		}

		//execute post
		$result = curl_exec($ch);

		//close connection
		curl_close($ch);

		//decode to array
		return json_decode($result,true);
	}
}

