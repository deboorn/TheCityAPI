<?php 


/**
 * The City Admin API
 * @author Daniel Boorn - daniel.boorn@gmail.com
 * @copyright Daniel Boorn
 * @license Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
 * @namespace TheCity
 */

/**
 * 6/11/2013 - Daniel Boorn
 * The class uses a JSON api_path.js file that defines the API endpoints and paths. 
 * The package include a DocGen utillity for generating and saving the JSON api_path.js file.
 * However, you do NOT need to edit or geneate this file as it already includes all methods.
 * This class is chainable! Please see examples before use.
 * 
*/


namespace TheCity;

class Exception extends \Exception{
	
	public $response;
	
	public function __construct($message, $code = 0, $response=null, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->response = $response;
	}
	
	public function getResponse(){
		return $this->response;
	}
}

class Admin{
	
	const BASE_URL = 'https://api.onthecity.org';
	const ALGO = 'sha256';
	
	protected $settings = array(
		'secretKey' => '',
		'userToken' => '',
	);
	
	protected $endpointId;
	protected $pathIds = array();
	protected $paths;	
	protected $response;
	
	public $debug = false;
	
	/**
	 * construct
	 * @param array $settings=null
	 * @returns void
	 * @throws \Exception
	 */
	public function __construct($settings=null){
		if($settings) $this->settings = $settings;
		if(!$this->settings['secretKey']) throw new \Exception("\TheCity\Admin.settings['secretKey'] is required!");
		if(!$this->settings['userToken']) throw new \Exception("\TheCity\Admin.settings['userToken'] is required!");
		$this->loadApiPaths();
	}
	
	/**
	 * forge factory
	 * @param array $settings=null
	 * @returns void
	 */
	public function forge($settings=null){
		return new self($settings);
	}	
	
	/**
	 * deboug output
	 * @returns void
	 */
	public function d($obj){
		if($this->debug) var_dump($obj);
	}
	
	/**
	 * magic method for building chainable api path with trigger to invoke api method
	 * @param string $name
	 * @param array $args
	 * @returns $this
	 */
	public function __call($name, $args){
		$this->endpointId .= $this->endpointId ? "_{$name}" : $name;
		$this->d($this->endpointId);
		$this->d($args);
		if(count($args)>0 && gettype($args[0]) != "array" && gettype($args[0]) != "object") $this->pathIds[] = array_shift($args);
		if(isset($this->paths[$this->endpointId])){
			$r = $this->invoke($this->endpointId, $this->paths[$this->endpointId]['verb'],$this->paths[$this->endpointId]['path'],$this->pathIds,current($args));
			$this->reset();
			return $r;
		}
		return $this;		
	}
	
	/**
	 * clear properties used by chain requests
	 * @returns void
	 */
	public function reset(){
		$this->endpointId = null;
		$this->pathIds = array();
	}
	
	/**
	 * returns sorted query string
	 * @param mixed $params
	 * @returns string
	 */
	public function getSortedQueryString($params){
		if(!is_array($params)) return "";
		ksort($params);
		return http_build_query($params);
	}
	
	/**
	 * return signed request
	 * @param string $verb
	 * @param string $path
	 * @param mixed $params
	 * @returns array $request
	 */
	protected function genRequest($verb,$path,$params){
		
		$time = time();
		$request = array(
			'verb' => $verb,
			'url' => self::BASE_URL . $path,
			"body" => "",
			'secret_key' => $this->settings['secretKey'],
			'user_token' => $this->settings['userToken'],
			'headers' => array(
				'Accept: application/vnd.thecity.admin.v1+json',
				"X-City-User-Token: {$this->settings['userToken']}",
				"X-City-Time: {$time}",
			),
		);	
		if($request['verb'] == "POST" || $request['verb'] == "PUT"){
			$request['body'] = gettype($params)=="object" ? json_encode($params) : $this->getSortedQueryString($params);
		}else{
			$str = $this->getSortedQueryString($params);
			if($str!="") $request['url'] .= "?{$str}";
		}
		
		$request['query'] = sprintf("%s%s%s%s", $time, $verb, $request['url'], $request['body']);	
		$request['unencoded_hmac'] = hash_hmac(self::ALGO, $request['query'], $this->settings['secretKey'],true);
		$request['unescaped_hmac'] = chop(base64_encode($request['unencoded_hmac']));
		$request['hmac_signature'] = urlencode($request['unescaped_hmac']);
		$request['headers'][] = "X-City-Sig: {$request['hmac_signature']}";
		
		if($request['body']!=""){
			$request['headers'][] = 'Content-Type: application/json';
			$request['headers'][] = 'Content-Length: ' + strlen($request['body']);
		}
		
		$this->d(array('Request'=>$request));
		return $request;
	}
	
	/**
	 * returns parsed path with ids (if any)
	 * @param string $path
	 * @param array $ids
	 * @returns string
	 * @throws \Exception
	 */
	protected function parsePath($path, $ids){
		$parts = explode("/",ltrim($path,'/'));
		for($i=0; $i<count($parts); $i++){
			if($parts[$i]{0}==":"){
				if(count($ids)==0) throw new \Exception("Api Endpont Path is Missing 1 or More IDs [path={$path}].");
				$parts[$i] = array_shift($ids);
			}
		}
		return '/'.implode("/",$parts);
	}
	
	/**
	 * parse header string to array
	 * @source http://php.net/manual/en/function.http-parse-headers.php#77241
	 * @param string $header
	 * @return array $retVal
	 */
	public static function http_parse_headers( $header ){
		$retVal = array();
		$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
		foreach( $fields as $field ) {
			if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
				$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
				if( isset($retVal[$match[1]]) ) {
					$retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
				} else {
					$retVal[$match[1]] = trim($match[2]);
				}
			}
		}
		return $retVal;
	}	
	
	/**
	 * fetch request form api
	 * @param array $request (see genRequest())
	 * @returns string $result
	 */
	protected function fetch($request){
		$ch = curl_init($request['url']);
		curl_setopt_array($ch, array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => $request['verb'],
			CURLOPT_HTTPHEADER => $request['headers'],
			CURLOPT_SSL_VERIFYPEER => false,
			CURLINFO_HEADER_OUT => true,
		));
		if($request['verb'] == "POST") curl_setopt($ch, CURLOPT_POSTFIELDS, $request['body']);
		
		if(($response = curl_exec($ch))===false){
			throw new \Exception(curl_error($ch));
		}
		$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		
		list($header, $body) = explode("\r\n\r\n", $response, 2);		
		return array(
			'output' => curl_getinfo($ch),
			'headers' => $this->http_parse_headers($header),
			'data' => json_decode($body),
			'code' => $code,	
			'error' => ($code >= 400 ? true : false),
		);
	}
	
	/**
	 * invoke api endpoint method
	 * @param string $id
	 * @param string $verb
	 * @param string $path
	 * @param array $ids=null
	 * @param mixed $params=null
	 */
	public function invoke($id, $verb, $path, $ids=null, $params=null){
		$path = $this->parsePath($path, $ids);
		$this->d("Invoke[$id]: {$verb} {$path}",$params);
		$request = $this->genRequest($verb, $path, $params);
		$this->response = $this->fetch($request);
		$this->d($this->response);
		return $this;
	}

	/**
	 * return api data object or false on error
	 * @returns object|boolean
	 */
	public function get(){
		if($this->response['error']) throw new Exception($this->response['data']->error_message, $this->response['data']->error_code, $this->response);
		return $this->response['data'];
	}
	
	/**
	 * return error data
	 * @returns object
	 */
	public function error(){
		return $this->response['data'];//error_code, error_message
	}
	
	/**
	 * loads api paths list from json file
	 * @returns void
	 */
	protected function loadApiPaths(){
		$filename = __DIR__ . "/api_paths.json";
		$this->paths = json_decode(file_get_contents($filename),true);
	}
	
}
