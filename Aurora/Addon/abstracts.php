<?php
//!	@file Aurora/Addon/abstracts.php
//!	@brief abstract classes
//!	@author SignpostMarv

namespace Aurora\Addon{

	class APIAccessFailedException extends RuntimeException{
	}

	abstract class APIMethodException extends RuntimeException{

//!	string name of API method that access was forbidden to.
//!	@see Aurora::Addon::APIAccessForbiddenException::GetAPIMethod()
		protected $method;
//!	@see Aurora::Addon::APIAccessForbiddenException::$method
		public function GetAPIMethod(){
			return $this->method;
		}

//!	Since we want to allow exception handlers to know which method access was forbidden to, we need to override the default exception constructor
/**
*	@param string $method API method name
*	@param string $message Exception message
*	@param integer $code Exception code
*/
		public function __construct($method, $message, $code=0){
			if(is_string($method) === true){
				$method = trim($method);
			}
			if(is_string($method) === false){
				throw new InvalidArgumentException('Method names should be strings.');
			}else if(ctype_graph($method) === false){
				throw new InvalidArgumentException('Method name should contain only visible characters.');
			}

			$this->method = $method;
			parent::__construct($message, $code);
		}
	}

	class APIAccessForbiddenException extends APIMethodException{
	}

	class APIAccessRateLimitException extends APIMethodException{
	}

	use Iterator;
	use SeekableIterator;
	use Countable;
	use ArrayAccess;

	use libAurora\abstractIterator;

//!	Since libAurora.php now houses code for two API implementations, we're adding a common abstract class
//!	@todo On Aurora::Addon::abstractAPI::makeCallToAPI(), implement a fallback response structure for error messages sent by the API, rather than just failing and throwing an exception.
	abstract class abstractAPI{

//!	makes a call to the API end point running on an instance of Aurora.
/**
*	@param string $method the API method to call
*	@param boolean $readOnly TRUE if API method is read-only, FALSE otherwise
*	@param mixed $arguments NULL if a method is being called with no arguments or an array of named arguments
*	@param array $expectedResponse a specially-constructed array indicating the expected response format of the API call
*	@return mixed API results should be JSON-encoded, implementations of Aurora::Addon::abstractAPI::makeCallToAPI() should perform json_decode()
*/
		abstract protected function makeCallToAPI($method, $readOnly=false, array $arguments=null, array $expectedResponse);

//!	Validates JSON API response
/**
*	@param string JSON response from API call
*	@param array structured array defining the expected response
*/
		protected static function validateJSONResponse($result, array $expectedResponse){
			if(is_string($result) === false){
				throw new UnexpectedValueException('API result expected to be a string, ' . gettype($result) . ' found.');
			}else{
				$result = json_decode($result);
				if(is_object($result) === false){
					throw new UnexpectedValueException('API result expected to be object, ' . gettype($result) . ' found.');
				}
				$exprsp = 0;
				foreach($expectedResponse as $k=>$v){
					++$exprsp;
					if(property_exists($result, $k) === false){
						throw new UnexpectedValueException('Call to API was successful, but required response properties were missing.', $exprsp * 6);
					}else if(in_array(gettype($result->{$k}), array_keys($v)) === false){
						throw new UnexpectedValueException('Call to API was successful, but required response property was of unexpected type.', ($exprsp * 6) + 1);
					}else if(count($v[gettype($result->{$k})]) > 0){
						$validValue = false;
						foreach($v[gettype($result->{$k})] as $_k => $possibleValue){
							if(is_integer($_k) === true){
								if(gettype($result->{$k}) === 'boolean'){
									if(is_bool($possibleValue) === false){
										throw new InvalidArgumentException('Only booleans can be given as valid values to a boolean type.');
									}else if($result->{$k} === $possibleValue){
										$validValue = true;
										break;
									}
								}else{
									$subPropertyKeys = array_keys($possibleValue);
									switch(gettype($result->{$k})){
										case 'array':
											foreach($result->{$k} as $_v){
												if(in_array(gettype($_v), $subPropertyKeys) === false){
													throw new UnexpectedValueException('Call to API was successful, but required response sub-property was of unexpected type.', ($exprsp * 6) + 3);
												}else if(gettype($_v) === 'object' && isset($possibleValue[gettype($_v)]) === true){
													foreach($possibleValue[gettype($_v)] as $__k => $__v){
														if(isset($__v['float']) == true){
															$possibleValue[gettype($_v)]['double'] = $__v['float'];
														}
													}
													$pos = $possibleValue[gettype($_v)];
													if(gettype($_v) === 'object'){
														$pos = current($pos);
														if($pos !== false){
															foreach($pos as $__k => $__v){
																if(isset($__v['float']) === true){
																	$pos[$__k]['double'] = $__v['float'];
																}
															}
														}
													}
													if($pos !== false){
														foreach($pos as $__k => $__v){
															if(isset($_v->{$__k}) === false){
																throw new UnexpectedValueException('Call to API was successful, but required response sub-property property was of missing.', ($exprsp * 6) + 4);
															}else{
																if(in_array(gettype($_v->{$__k}), array_keys($__v)) === false){
																	throw new UnexpectedValueException('Call to API was successful, but required response sub-property was of unexpected type.', ($exprsp * 6) + 5);
																}
															}
														}
													}
												}
											}
											$validValue = true;
										break;
										case 'object':
											foreach($possibleValue as $_k => $_v){
												if(isset($_v['float']) === true){
													$possibleValue[$_k]['double'] = $_v['float'];
												}
											}
											foreach($possibleValue as $_k => $_v){
												if(isset($result->{$k}->{$_k}) === false){
													throw new UnexpectedValueException('Call to API was successful, but required response sub-property property was of missing.', ($exprsp * 6) + 4);
												}else{
													if(in_array(gettype($result->{$k}->{$_k}), array_keys($possibleValue[$_k])) === false){
														throw new UnexpectedValueException('Call to API was successful, but required response sub-property was of unexpected type.', ($exprsp * 6) + 5);
													}
												}
											}
											$validValue = true;
										break;
									}
								}
							}else if($result->{$k} === $possibleValue){
								$validValue = true;
								break;
							}
						}
						if($validValue === false){
							throw new UnexpectedValueException('Call to API was successful, but required response property had an unexpected value.', ($exprsp * 6) + 2);
						}
					}
				}
				return $result;
			}
		}

//!	array stores attached APIs
		protected $attachedAPIs = array();

//!	Attaches instances of Aurora::Addon::abstractAPI to this instance, while excluding implementations of the same API being attached to each other.
/**
*	@param object $API an instance of an implementation for an API
*/
		public function attachAPI(abstractAPI $API){
			if(is_a($API, get_class($this)) === true){
				throw new InvalidArgumentException('Cannot attach an instnace of an API or a child-implementation of an API to itself.');
			}
			$pos = strrpos(get_class($API), '\\');
			$name = substr(get_class($API), $pos !== false ? $pos + 1 : 0);
			if(isset($this->attachedAPIs[$name]) === true){
				throw new InvalidArgumentException('An API of that type has already been attached.');
			}
			$this->attachedAPIs[$name] = $API;
		}

//!	Attempt to get the attached API according to the class name of it's implementation
/**
*	@param string $className class name of API implementation
*	@return mixed NULL if the $className is not on Aurora::Addon::abstractAPI::$attachedAPIs, otherwise an instance of Aurora::Addon::abstractAPI
*/
		public function getAttachedAPI($className){
			return isset($this->attachedAPIs[$className]) ? $this->attachedAPIs[$className] : null;
		}
	}

//!	class for APIs that user password-based authentication
	abstract class abstractPasswordAPI extends abstractAPI{

//!	This is protected because we're going to use a registry method to access it.
/**
*	The WIREDUX_PASSWORD constant was never used without being passed as an md5() hash, so we immediately do this on instantiation.
*	@param string $serviceURL API end point.
*	@param string $password API password
*/
		protected function __construct($serviceURL, $password){
			if(is_string($serviceURL) === false){
				throw new InvalidArgumentException('API end point must be a string');
			}else if(strpos($serviceURL, 'http://') === false && strpos($serviceURL, 'https://') === false){ // for now, we're not doing any paranoid regex-based validation.
				throw new InvalidArgumentException('API end point must begin with http:// or https://');
			}else if(is_string($password) === false){
				throw new InvalidArgumentException('API password should be a string');
			}
			$this->serviceURL = $serviceURL;
			$this->password   = md5($password);
		}

//!	string API end point.
		protected $serviceURL;

//!	string API password
		protected $password;

//!	registry method. Sets & gets instances of Aurora::Addon::abstractPasswordAPI
/**
*	@param string $serviceURL
*	@param mixed $password should be NULL if getting, otherwise should be string. defaults to NULL.
*	@return Aurora::Addon::abstractPasswordAPI
*	@see Aurora::Addon::abstractPasswordAPI::__construct()
*/
		public static function r($serviceURL, $password=null){
			static $registry = array();
			if(isset($registry[$serviceURL]) === false){
				if(isset($password) === false){
					throw new InvalidArgumentException('Cannot create an instance of abstractPasswordAPI interface without a password');
				}
				$instance = new static($serviceURL, $password); // we're assigning it to a local variable as a lazy means of avoiding doing valid type checks for array keys. any errors that would crop up about that would trigger InvalidArgumentException in Aurora::Addon::abstractUsernamePasswordAPI::__construct()
				$registry[$serviceURL] = $instance; // any child implementation of Aurora::Addon::abstractPasswordAPI that breaks this laziness is on their own at this point.
			}
			return $registry[$serviceURL];
		}

//!	makes a call to the WebUI API end point running on an instance of Aurora.
/**
*	@param string $method
*	@param bool $readOnly ignored.
*	@param array $arguments being lazy and future-proofing API methods that have no arguments.
*	@param array $expectedResponse a specially-constructed array indicating the expected response format of the API call
*	@return mixed All instances of do_post_request() in Aurora-WebUI that act upon the result call json_decode() on the $result prior to acting on it, so we save ourselves some time and execute json_decode() here.
*/
		protected function makeCallToAPI($method, $readOnly=false, array $arguments=null, array $expectedResponse){
			if(is_string($method) === false || ctype_graph($method) === false){
				throw new InvalidArgumentException('API method parameter was invalid.');
			}
			$arguments = isset($arguments) ? $arguments : array();
			$arguments = array_merge(array(
				'Method'      => $method,
				'WebPassword' => $this->password
			), $arguments);
			$ch = curl_init($this->serviceURL);
			curl_setopt_array($ch, array(
				CURLOPT_HEADER         => false,
				CURLOPT_POST           => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POSTFIELDS     => implode(',', array(json_encode($arguments)))
			));
			$result = curl_exec($ch);
			$error = $result ? null : curl_error($ch);
			$info  = $result ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : 0;
			curl_close($ch);
			if($info === 403){
				throw new APIAccessForbiddenException($method, sprintf('Access to the API method \'%s\' for the configured credentials has been denied.'));
			}else if($info === 429){
				throw new APIAccessRateLimitException($method, sprintf('Access to the API method \'%s\' for the configured credentials has been denied.'));
			}else if($result === false){
				if($info === 0){
					throw new APIAccessFailedException('API end-point is either not reachable or not online.');
				}
			}
			return static::validateJSONResponse($result, $expectedResponse);
		}
	}

//!	class for APIs that user username & password-based authentication
	abstract class abstractUsernamePasswordAPI extends abstractPasswordAPI{

//!	This is protected because we're going to use a registry method to access it.
/**
*	The WIREDUX_PASSWORD constant was never used without being passed as an md5() hash, so we immediately do this on instantiation.
*	@param string $serviceURL API end point.
*	@param string $username API username
*	@param string $password API password
*/
		protected function __construct($serviceURL, $username, $password){
			if(is_string($username) === false){
				throw new InvalidArgumentException('API username should be a string');
			}
			parent::__construct($serviceURL, $password);
			$this->username   = $username;
		}

//!	string username
		protected $username;

//!	registry method. Sets & gets instances of Aurora::Addon::abstractUsernamePasswordAPI
/**
*	@param string $serviceURL
*	@param mixed $username should be NULL if getting, otherwise should be string. defaults to NULL.
*	@param mixed $password should be NULL if getting, otherwise should be string. defaults to NULL.
*	@return Aurora::Addon::abstractUsernamePasswordAPI
*	@see Aurora::Addon::abstractUsernamePasswordAPI::__construct()
*/
		public static function r($serviceURL, $username=null, $password=null){
			static $registry = array();
			if(isset($registry[$serviceURL]) === false){
				if(isset($username, $password) === false){
					throw new InvalidArgumentException('Cannot create an instance of abstractUsernamePasswordAPI interface without a username & password');
				}
				$instance = new static($serviceURL, $username, $password); // we're assigning it to a local variable as a lazy means of avoiding doing valid type checks for array keys. any errors that would crop up about that would trigger InvalidArgumentException in Aurora::Addon::abstractUsernamePasswordAPI::__construct()
				$registry[$serviceURL] = $instance; // any child implementation of Aurora::Addon::abstractUsernamePasswordAPI that breaks this laziness is on their own at this point.
			}
			return $registry[$serviceURL];
		}
	}

//!	class for APIs that use HTTP Digest Authentication
	abstract class abstractAuthDigestAPI extends abstractUsernamePasswordAPI{

//!	makes a call to the API end point running on an instance of Aurora.
/**
*	@param string $method
*	@param boolean $readOnly TRUE if API method is read-only, FALSE otherwise
*	@param array $arguments being lazy and future-proofing API methods that have no arguments.
*	@param array $expectedResponse a specially-constructed array indicating the expected response format of the API call
*	@return mixed API results are expected to be JSON-encoded, this method decodes them and libAurora methods will convert those to strongly-typed results as necessary.
*/
		protected function makeCallToAPI($method, $readOnly=false, array $arguments=null, array $expectedResponse){
			if(is_string($method) === false || ctype_graph($method) === false){
				throw new InvalidArgumentException('API method parameter was invalid.');
			}
			$arguments = isset($arguments) ? $arguments : array();
			if($readOnly === true){
				foreach($arguments as $k=>$v){
					$arguments[$k] = json_encode($v);
				}
			}
			$ch = curl_init($this->serviceURL . '/' . rawurlencode($method) . ($readOnly === true ? '?' . http_build_query($arguments) : ''));
			curl_setopt_array($ch, array(
				CURLOPT_HEADER         => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
				CURLOPT_USERPWD        => $this->username . ':' . $this->password
			));
			if($readOnly !== true){
				curl_setopt_array($ch, array(
					CURLOPT_POST           => true,
					CURLOPT_POSTFIELDS     => implode(',', array(json_encode($arguments)))
				));
			}
			$result = curl_exec($ch);
			$error = $result ? null : curl_error($ch);
			$info  = $result ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : 0;
			curl_close($ch);
			if($info === 403){
				throw new APIAccessForbiddenException($method, sprintf('Access to the API method \'%s\' for the configured credentials has been denied.'));
			}else if($info === 429){
				throw new APIAccessRateLimitException($method, sprintf('Access to the API method \'%s\' for the configured credentials has been denied.'));
			}else if($result === false){
				if($info === 0){
					throw new APIAccessFailedException('API end-point is either not reachable or not online.');
				}
			}
			return static::validateJSONResponse($result, $expectedResponse);
		}
	}

//!	class for Write-Once-Read-Many implementations of ArrayAccess
	abstract class WORM extends abstractIterator implements ArrayAccess{

//!	protected constructor, hidden behind a singleton, factory or registry method.
		protected function __construct(){
		}

//!	Determines if a value exists at the specified offset
		public function offsetExists($offset){
			return isset($offset, $this->data[$offset]);
		}

//!	Attempts to get the value at the specified offset
		public function offsetGet($offset){
			return isset($this[$offset]) ? $this->data[$offset] : null;
		}

//!	WORM instances can't have properties removed.
		public function offsetUnset($offset){
			throw new BadMethodCallException('data cannot be unset.');
		}

//!	Attempts to return the offset on Aurora::Addon::WORM::$data for $value if $value exists in the instance.
/**
*	@param mixed $value
*	@return mixed FALSE if the value was not found, otherwise returns the offset.
*/
		public function valueOffset($value){
			return array_search($value, $this->data);
		}
	}


	//!	abstract seekable iterator
	abstract class abstractSeekableIterator extends abstractIterator{

//!	object instance of Aurora::Addon::abstractAPI
		protected $API;

//!	Because we use a seekable iterator, we hide the constructor behind a registry method to avoid needlessly calling the end-point if we've rewound the iterator, or moved the cursor to an already populated position.
		protected function __construct(abstractAPI $API, $start=0, $total=0){
			if(is_integer($total) === false){
				throw new InvalidArgumentException('Total number of entities must be an integer.');
			}else if($total < 0){
				throw new InvalidArgumentException('Total number of entities must be greater than or equal to zero.');
			}
			$this->API = $API;
			$this->total = $total;
			$this->seek($start);
		}

//!	integer total number of groups
		protected $total;

//!	@return integer
		public function count(){
			return $this->total;
		}

//!	@return integer
		public function key(){
			return ($this->pos < $this->count()) ? $this->pos : null;
		}

//!	@return bool TRUE if the current cursor position is valid, FALSE otherwise.
		public function valid(){
			return ($this->key() !== null);
		}

//!	advance the cursor
		public function next(){
			++$this->pos;
		}

//!	integer cursor position
		protected $pos=0;

//!	Move the cursor to the specified point.
		public function seek($to){
			if(is_string($to) === true && ctype_digit($to) === true){
				$to = (integer)$to;
			}
			if(is_integer($to) === true && $to < 0){
				$to = abs($to) % $this->count();
				$to = $this->count() - $to;
			}

			if(is_integer($to) === false){
				throw new InvalidArgumentException('Seek point must be an integer.');
			}else if($to > 0 && $to >= $this->count()){
				throw new LengthException('Cannot seek past Aurora::Addon::abstractSeekableIterator::count()');
			}

			$this->pos = $to;
		}
	}

//!	abstract class for filterable iterators for API method results that use sort arrays and boolean field flags for output filters.
	abstract class abstractSeekableFilterableIterator extends abstractSeekableIterator{

//!	mixed either NULL indicating no sort filters, or an array of field name keys and boolean values indicating sort order.
		private $sort;

//!	mixed either NULL indicating no boolean filters, or an array of field name keys and boolean values.
		private $boolFields;

//!	Because we use a seekable iterator, we hide the constructor behind a registry method to avoid needlessly calling the end-point if we've rewound the iterator, or moved the cursor to an already populated position.
/**
*	@param object $API instance of Aurora::Addon::abstractAPI We need to specify this in case we want to iterate past the original set of results.
*	@param integer $start initial cursor position
*	@param integer $total Total number of results possible with specified filters
*	@param array $sort optional array of field names for keys and booleans for values, indicating ASC and DESC sort orders for the specified fields.
*	@param array $boolFields optional array of field names for keys and booleans for values, indicating 1 and 0 for field values.
*/
		protected function __construct(abstractAPI $API, $start=0, $total=0, array $sort=null, array $boolFields=null){
			parent::__construct($API, $start, $total);
			$this->sort = $sort;
			$this->boolFields = $boolFields;
		}

//! This is a registry method for a class that implements the SeekableIterator class, so we can save ourselves some API calls if we've already fetched some entities.
/**
*	@param object $API instance of Aurora::Addon::abstractAPI We need to specify this in case we want to iterate past the original set of results.
*	@param integer $start initial cursor position
*	@param integer $total Total number of results possible with specified filters
*	@param array $sort optional array of field names for keys and booleans for values, indicating ASC and DESC sort orders for the specified fields.
*	@param array $boolFields optional array of field names for keys and booleans for values, indicating 1 and 0 for field values.
*	@param array $entities if specified, should be an array of entity objects to be validated by the child constructor
*	@return object an instance of Aurora::Addon::abstractSeekableFilterableIterator
*/
		public static function r(abstractAPI $API, $start=0, $total=0, array $sort=null, array $boolFields=null, array $entities=null){
			static $registry = array();
			$hash1 = spl_object_hash($API);
			$hash2 = md5(print_r($sort,true));
			$hash3 = md5(print_r($boolFields,true));

			if(isset($registry[$hash1]) === false){
				$registry[$hash1] = array();
			}
			if(isset($registry[$hash1][$hash2]) === false){
				$registry[$hash1][$hash2] = array();
			}

			$create = (isset($registry[$hash1][$hash2][$hash3]) === false) || ($create === false && ($total !== null && $registry[$hash1][$hash2][$hash3]->count() !== $total));

			if($create === true){
				$registry[$hash1][$hash2][$hash3] = new static($API, $start, $total, $sort, $boolFields, $entities);
			}

			$registry[$hash1][$hash2][$hash3]->seek($start);

			return $registry[$hash1][$hash2][$hash3];
		}
	}
}
?>
