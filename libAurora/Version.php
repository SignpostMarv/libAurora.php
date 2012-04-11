<?php
//!	@brief this is based on the metadata of mscorlib.dll, v2.0.50727

namespace libAurora{


	class Version{


		const regex_asString = '/^(\d+\.\d+|\d+\.\d+\.\d+|\d+\.\d+\.\d+\.\d+)$/';


		private $major    = 0;


		private $minor    = 0;


		private $revision = 0;


		private $build    = 0;


		public function __get($name){
			return ($name === 'major' || $name == 'minor' || $name === 'revision' || $name === 'build') ? $this->$name : null;
		}


		public function __toString(){
			return $this->major . '.' . $this->minor . '.' . $this->revision . '.' . $this->build;
		}


		public function __construct($major=0, $minor=0, $revision=0, $build=0){
			if(isset($major) === true && isset($minor, $revision, $build) === false && is_string($major) === true && preg_match(static::regex_asString, $major) == 1){
				list($major, $minor, $revision, $build) = array_pad(explode('.', $major), 4, 0);
			}
			if(is_string($major) === true && ctype_digit($major) === true){
				$major = (integer)$major;
			}
			if(is_string($minor) === true && ctype_digit($minor) === true){
				$minor = (integer)$minor;
			}
			if(is_string($revision) === true && ctype_digit($revision) === true){
				$revision = (integer)$revision;
			}
			if(is_string($build) === true && ctype_digit($build) === true){
				$build = (integer)$build;
			}

			if(is_integer($major) === false){
				throw new InvalidArgumentException('Major number should be specified as integer.');
			}else if(is_integer($minor) === false){
				throw new InvalidArgumentException('Minor number should be specified as integer.');
			}else if(is_integer($revision) === false){
				throw new InvalidArgumentException('Revision number should be specified as integer.');
			}else if(is_integer($build) === false){
				throw new InvalidArgumentException('Build number should be specified as integer.');
			}
		}


		public static function cmp(Version $a, Version $b){
			return ($a->__toString() === $b->__toString()) ? 0 : ((
				($a->major  <  $b->major                                                                                       ) ||
				($a->major === $b->major && $a->minor  <  $b->minor                                                          ) ||
				($a->major === $b->major && $a->minor === $b->minor && $a->revision  <  $b->revision                         ) ||
				($a->major === $b->major && $a->minor === $b->minor && $a->revision === $b->revision && $a->build < $b->build)
			) ? -1 : 1;
		}
	}
}
?>
