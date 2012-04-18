<?php
//!	@brief this is based on the metadata of mscorlib.dll, v2.0.50727

//!	general purpose libAurora code goes in this namespace
namespace libAurora{

//!	class for handling version definitions and comparisons where version numbers are in the major.minor.revision.build format
	class Version{

//!	string regular expression for validating version strings
		const regex_asString = '/^(\d+\.\d+|\d+\.\d+\.\d+|\d+\.\d+\.\d+\.\d+)$/';

//!	integer major release number
		private $major    = 0;

//!	integer minor release number
		private $minor    = 0;

//!	integer revision number
		private $revision = 0;

//!	integer build number
		private $build    = 0;

//!	Since we don't have proper getters & setters in PHP, we wrap to the magic method
/**
*	@param string $name name of property access is attempted on
*	@return integer
*	@see libAurora::Version::$major
*	@see libAurora::Version::$minor
*	@see libAurora::Version::$revision
*	@see libAurora::Version::$build
*/
		public function __get($name){
			return ($name === 'major' || $name == 'minor' || $name === 'revision' || $name === 'build') ? $this->$name : null;
		}

//!	Converts a version object back to a version string.
/**
*	@return string the major.minor.revision.build version string.
*/
		public function __toString(){
			return $this->major . '.' . $this->minor . '.' . $this->revision . '.' . $this->build;
		}

//!	Instantiates a version object either from a version string or explicit numbering
/**
*	@param mixed $major Either a string representation of a version number, or an integer specifying the major release number
*	@param integer $minor minor release number. Ignored when $major is string.
*	@param integer $revision revision number. Ignored when $major is string.
*	@param integer $build build number. Ignored when $major is string.
*	@see libAurora::Version::regex_asString
*	@see libAurora::Version::$major
*	@see libAurora::Version::$minor
*	@see libAurora::Version::$release
*	@see libAurora::Version::$build
*/
		public function __construct($major=0, $minor=0, $revision=0, $build=0){
			if(is_string($major) === true && ((string)$minor . (string)$revision . (string)$build) === '000' && preg_match(static::regex_asString, $major) == 1){
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

			$this->major    = $major;
			$this->minor    = $minor;
			$this->revision = $revision;
			$this->build    = $build;
		}

//!	Peforms usort-compatible comparisons between instances of libAurora::Version
/**
*	@param object $a instance of libAurora::Version
*	@param object $b instance of libAurora::Version
*	@return integer
*/
		public static function cmp(Version $a, Version $b){
			return ($a->__toString() === $b->__toString()) ? 0 : ((
				($a->major  <  $b->major                                                                                       ) ||
				($a->major === $b->major && $a->minor  <  $b->minor                                                          ) ||
				($a->major === $b->major && $a->minor === $b->minor && $a->revision  <  $b->revision                         ) ||
				($a->major === $b->major && $a->minor === $b->minor && $a->revision === $b->revision && $a->build < $b->build)
			) ? -1 : 1);
		}
	}
}
?>
