<?php
//!	@file libAurora/Nonces.php
//!	@author SignpostMarv
//!	@brief Numbers used once


namespace libAurora{

	use Aurora\Addon\WebUI\InvalidArgumentException as WebUIInvalidArgumentException;

	use DirectoryIterator;

	use Aurora\Framework\IDataConnector;
	use Aurora\Framework\QueryFilter;
	use Aurora\Addon\WebUI\Template;
	use Aurora\DataManager\Migration\MigrationManager;


	class Nonces{

		const table = 'libaurora_nonces';


		private $genericData;


		protected function __construct(IDataConnector $genericData){
			if($genericData->TableExists(self::table) === false){
				$migrationManager = new MigrationManager($genericData, 'LibAurora_Nonces', true);
				$migrationManager->DetermineOperation();
				$migrationManager->ExecuteOperation();
			}
			$this->genericData = $genericData;
			$this->expire();
		}


		public static function r(IDataConnector $genericData){
			static $registry = array();
			$hash = spl_object_hash($genericData);
			if(isset($registry[$hash]) === false){
				$registry[$hash] = new self($genericData);
			}
			return $registry[$hash];
		}


		public function get($lifetime=3600){
			if(is_string($lifetime) === true && ctype_digit($lifetime) === true){
				$lifetime = (integer)$lifetime;
			}

			if(is_integer($lifetime) === false){
				throw new InvalidArgumentException('Lifetime must be specified as integer.');
			}

			$hash = str_split(sha1(uniqid(static::table, true) . php_uname() . microtime() . mt_rand()), 4);
			$hash =
				$hash[0] . $hash[1] . '-' .
				$hash[2] . '-' .
				$hash[3] . '-' .
				$hash[4] . '-' .
				$hash[5] . $hash[6] . $hash[7]
			;

			$this->genericData->Insert(self::table, array(
				$hash,
				time() + $lifetime
			));

			return Template\squishUUID($hash);
		}


		public function isValid($hash, $expire=true){
			if($expire === true){
				$this->expire();
			}
			if(is_string($hash) === false){
				return false;
			}
			try{
				$hash = Template\unsquishUUID($hash);
			}catch(WebUIInvalidArgumentException $e){
				error_log($e);
				return false;
			}

			$filter = new QueryFilter;
			$filter->andFilters['nonce'] = $hash;
			$query = $this->genericData->Query(array('COUNT(*)'), self::table, $filter);
			return (count($query) === 1 && $query[0] === '1');
		}


		public function useNonce($hash){
			if(is_string($hash) === false){
				throw new InvalidArgumentException('Nonce must be specified as string.');
			}
			try{
				$hash = Template\unsquishUUID($hash);
			}catch(WebUIInvalidArgumentException $e){
				throw new InvalidArgumentException($e->getMessage());
			}

			$filter = new QueryFilter;
			$filter->andFilters['nonce'] = $hash;
			$this->genericData->Delete(self::table, $filter);
		}


		public function expire(){
			$filter = new QueryFilter;
			$filter->andLessThanEqFilters['expiry'] = time();
			$this->genericData->Delete(self::table, $filter);
		}
	}

	$dir = new DirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . 'Nonces' . DIRECTORY_SEPARATOR);
	foreach($dir as $file){
		if($file->isFile() === true && $file->isReadable() === true && preg_match('/^[A-z_\-]+Migrator_[\d+]\.php$/', $file->getFilename()) === 1){
			require_once(__DIR__ . DIRECTORY_SEPARATOR . 'Nonces' . DIRECTORY_SEPARATOR . $file->getFilename());
		}
	}
}
?>