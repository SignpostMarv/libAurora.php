<?php
//!	@file libs/Aurora/Addon/WebUI/abstracts.php
//!	@brief abstract WebUI classes
//!	@author SignpostMarv

namespace Aurora\Addon\WebUI{

	use SeekableIterator;

	use Aurora\Addon\WebUI;
	use Aurora\Addon\WORM;
	use Aurora\Addon\abstractIterator;

//!	abstract iterator for instances of Aurora::Addon::WebUI::abstractUser
	abstract class abstractUserIterator extends abstractIterator{

//!	public constructor
/**
*	Since Aurora::Addon::WebUI::abstractUserIterator does not implement methods for appending values, calling the constructor with no arguments is a shorthand means of indicating there are no users available.
*	@param mixed $archives an array of Aurora::Addon::WebUI::abstractUser instances or NULL
*/
		public function __construct(array $archives=null){
			if(isset($archives) === true){
				foreach($archives as $v){
					if(($v instanceof abstractUser) === false){
						throw new InvalidArgumentException('Only instances of Aurora::Addon::WebUI::abstractUser should be included in the array passed to Aurora::Addon::WebUI::abstractUserIterator::__construct()');
					}
				}
				reset($archives);
				$this->data = $archives;
			}
		}
	}


	abstract class abstractLazyLoadingSeekableIterator extends WORM implements SeekableIterator{

//!	object instance of Aurora::Addon::WebUI
		protected $WebUI;

//!	integer Since we're allowing non-contiguous, delayed access to the region list, we need to pre-fetch the total size of the request.
		private $total;

//!	integer cursor position
		private $pos = 0;

//!	We're hiding this behind factory or registry methods.
		protected function __construct(WebUI $WebUI, $start=0, $total=0){
			if(is_string($secondsAgo) && ctype_digit($secondsAgo) === true){
				$secondsAgo = (integer)$secondsAgo;
			}
			if(is_string($start) && ctype_digit($start) === true){
				$start = (integer)$start;
			}
			if(is_string($total) && ctype_digit($total) === true){
				$total = (integer)$total;
			}

			if(is_integer($start) === false){
				throw new InvalidArgumentException('Start point must be an integer.');
			}else if(is_integer($total) === false){
				throw new InvalidArgumentException('Total must be an integer.');
			}

			$this->WebUI = $WebUI;
			$this->total = $total;
			$this->pos   = $start;
		}

//!	We're not supporting external manipulation of Aurora::Addon::WebUI::abstractLazyLoadingSeekableIterator::$data
		public function offsetSet($offset, $value){
			throw new BadMethodCallException('Instances of Aurora::Addon::WebUI::abstractLazyLoadingSeekableIterator cannot be modified from outside of the object scope.');
		}

//!	Sets the cursor position
/**
*	@param integer $to desired cursor position
*/
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
			}else if($to >= $this->count() && $to !== 0){
				throw new LengthException('Cannot seek past Aurora::Addon::WebUI::abstractLazyLoadingSeekableIterator::count()');
			}

			$this->pos = $to;
		}

//!	Indicates the total size of the query, not the number of users currently on the iterator
/**
*	@return integer
*/
		public function count(){
			return $this->total;
		}

//!	Gets the cursor position
/**
*	@return mixed Integer if the cursor position is valid, NULL otherwise.
*/
		public function key(){
			return ($this->pos < $this->count()) ? $this->pos : null;
		}

//!	Determines if the current cursor position is valid
/**
*	@return boolean TRUE if the cursor position is valid, FALSE otherwise
*/
		public function valid(){
			return ($this->key() !== null);
		}

//!	advance the cursor
		public function next(){
			++$this->pos;
		}
	}

//!	Seekable iterator for instances of Aurora\Addon\WebUI\GridRegion
	abstract class RegionsIterator extends abstractLazyLoadingSeekableIterator{

//!	We're hiding this behind registry methods
/**
*	@param object $WebUI instance of Aurora::Addon::WebUI. Used to get instances of Aurora::Addon::WebUI::GridRegion that the instance wasn't instantiated with.
*	@param integer $start specifies the index that $regions starts at, if specified.
*	@param integer $total specifies the total number of regions in the grid.
*	@param mixed $regions Either NULL or an array of Aurora::Addon::WebUI::GridRegion instances.
**/
		protected function __construct(WebUI $WebUI, $start=0, $total=0, array $regions=null){
			if(is_string($start) === true && ctype_digit($start) === true){
				$start = (integer)$start;
			}
			if(is_string($total) === true && ctype_digit($total) === true){
				$total = (integer)$total;
			}

			parent::__construct($WebUI, $start, $total);

			$i = $start;
			if(isset($regions) === true){
				foreach($regions as $region){
					if($region instanceof GridRegion){
						$this->data[$i++] = $region;
					}else{
						throw new InvalidArgumentException('Values of instantiated regions array must be instances of Aurora::Addon::WebUI::GridRegion');
					}
				}
			}
		}
	}	
}
?>
