<?php
//!	@file libAurora/Template.php
//!	@author SignpostMarv
//!	@brief Template helpers.
//!	@todo Should probably be moved to another namespace at somepoint.

//!	Template-related methods
namespace Aurora\Addon\WebUI\Template{

	use Globals;

	use Aurora\Addon;
	use Aurora\Addon\WORM;
	use Aurora\Addon\WebUI;
	use Aurora\Addon\WebUI\InvalidArgumentException;

//!	Generates specific links for various entities as well as altering all results to work with mod_rewrite enabled or disabled.
/**
*	@param mixed $url either a string url in mod_rewrite-esque form, or an instance of Aurora::Addon::WebUI::abstractUser, Aurora::Addon::WebUI::EstateSettings, Aurora::Addon::WebUI::GridRegion, Aurora::Addon::WebUI::GroupRecord, Aurora::Addon::WebUI::LandData
*	@return string an URL reformated to work depending on whether the site is configured for mod_rewrite or not.
*/
	function link($url){
		if(is_object($url) === true){
			$args = func_get_args();
			$extra = '';
			if(count($args) > 1){
				$extra = $args[1];
			}

			if($url instanceof WebUI\abstractUser){
				return link('/world/user/' . urlencode($url->Name()) . $extra);
			}else if($url instanceof WebUI\EstateSettings){
				return link('/world/place/' . urlencode($url->EstateName()) . $extra);
			}else if($url instanceof WebUI\GridRegion){
				$Estate = Globals::i()->WebUI->GetEstate($url->EstateID());
				return link('/world/place/' . urlencode($Estate->EstateName()) . '/' . urlencode($url->RegionName()) . $extra);
			}else if($url instanceof WebUI\GroupRecord){
				return link('/world/group/' . urlencode($url->GroupName()));
			}else if($url instanceof WebUI\LandData){
				$region = Globals::i()->WebUI->GetRegion($url->RegionID());
				$estate = Globals::i()->WebUI->GetEstate($region->EstateID());
				return link('/world/place/' . urlencode($estate->EstateName()) . '/' . urlencode($region->RegionName()) . '/' . urlencode($url->Name()) . '/' . urlencode(preg_replace_callback('/0{3,}/',function($matches){return 'g' . strlen($matches[0]);}, rtrim(str_replace('-','',$url->InfoUUID()),'0'))));
			}
		}
		
		$baseURI = parse_url(Globals::i()->baseURI);
		if(substr($baseURI['path'],0,1) === '/'){
			$baseURI['path'] = substr($baseURI['path'],1);
		}
		if(substr($baseURI['path'],-1) === '/'){
			$baseURI['path'] = substr($baseURI['path'],0,-1);
		}
		
		if(substr($url,0,1) === '/'){
			$url = substr($url,1);
		}

		$url = parse_url(Globals::i()->baseURI . $url);

		if(substr($url['path'],0,1) === '/'){
			$url['path'] = substr($url['path'],1);
		}
		if(substr($url['path'],-1) === '/'){
			$url['path'] = substr($url['path'],0,-1);
		}
		
		$output = '';
		$pos = strpos($url['path'], $baseURI['path']);
		if($pos !== false && $pos >= 0){
			$output = '.';
			$url['path'] = substr($url['path'], strlen($baseURI['path']));
		}


		switch(Globals::i()->linkStyle){
			case 'mod_rewrite':
				$output .= $url['path'];
			break;
			case 'query':
			default:
				$query = '';
				if($url['path'] !== '/'){
					$query = 'path=' . urlencode($url['path']);
				}
				$url['query'] = empty($url['query']) ? $query : $url['query'] . '&amp;' . $query;
			break;
		}

		if(empty($url['query']) === false){
			$output .= '?' . $url['query'];
		}
		if(empty($url['fragment']) === false){
			$output .= '#' . $url['fragment'];
		}

		return $output;
	}

//!	Converts a UUID to a base-36 string
/**
*	@param string $uuid the UUID to squish
*	@return string the UUID stripped of hyphens then converted to a base-36 string.
*/
	function squishUUID($uuid){
		if(Addon\is_uuid($uuid) === false){
			throw new InvalidArgumentException('Input value must be a valid UUID');
		}
		static $chars;
		if(isset($chars) === false){
			$chars = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_');
		}
		$binary = '';
		foreach(str_split(str_replace('-', '', $uuid), 8) as $v){
			$binary .= str_pad(base_convert($v, 16, 2), 32, '0', STR_PAD_LEFT);
		}
		$binary = str_split($binary, 6);
		$squish = '';
		foreach($binary as $v){
			$squish .= $chars[base_convert($v, 2, 10)];
		}
		return $squish;
	}

//!	Converts a squished UUID to a valid UUID string.
/**
*	@param string $string the squished UUID string.
*	@return a valid UUID
*/
	function unsquishUUID($string){
		static $chars;
		if(isset($chars) === false){
			$chars = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_');
		}

		$binary = '';
		$parts = str_split($string);
		$last = array_pop($parts);
		foreach($parts as $v){
			$binary .= str_pad(base_convert(array_search($v, $chars), 10, 2), 6, '0', STR_PAD_LEFT);
		}
		$binary .= str_pad(base_convert(array_search($last, $chars), 10, 2), 2, '0', STR_PAD_LEFT);

		$uuid = '';
		foreach(str_split($binary, 8) as $v){
			$uuid .= str_pad(base_convert($v, 2, 16), 2, '0', STR_PAD_LEFT);
		}
		$uuid = str_split($uuid, 4);

		$uuid =
			$uuid[0] . $uuid[1] . '-' .
			$uuid[2] . '-' .
			$uuid[3] . '-' .
			$uuid[4] . '-' .
			$uuid[5] . $uuid[6] . $uuid[7]
		;
		if(Addon\is_uuid($uuid) === false){
			throw new InvalidArgumentException('Input value was not a valid squished UUID');
		}
		return $uuid;
	}

//!	Used to store user-correctable problems with forms
	class FormProblem extends WORM{

//!	Sets the problem for a given form section
/**
*	@param string $offset string indicating the form section
*	@param string $value the problem
*/
		public function offsetSet($offset, $value){
			if(is_string($value) === true){
				$value = trim($value);
			}

			if(is_string($offset) === false){
				throw new InvalidArgumentException('FormProblem offsets must be strings.');
			}else if(preg_match('/^[a-z][a-z0-9\-]+$/S', $offset) !== 1){
				throw new InvalidArgumentException('FormProblem offset was invalid.');
			}else if(is_string($value) === false){
				throw new InvalidArgumentException('FormProblem value must be a string.');
			}else if($value === ''){
				throw new InvalidArgumentException('FormProblem value cannot be an empty string.');
			}

			$this->data[$offset] = $value;
		}

//!	Since only one form can be submitted at a time, we use a singleton pattern
		public static function i(){
			static $instance;
			if(isset($instance) === false){
				$instance = new static;
			}
			return $instance;
		}
	}
}

namespace{
	require_once('Template/navigation.php');
}
?>