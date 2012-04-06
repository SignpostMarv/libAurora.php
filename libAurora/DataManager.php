<?php
//!	@file libAurora/DataManager.php
//!	@brief abstract implementation of Aurora::Framework::IGenericData
//!	@author SignpostMarv

namespace libAurora\DataManager{

	use libAurora\InvalidArgumentException;

	use Aurora\Framework\QueryFilter;
	use Aurora\Framework\IGenericData;

//!	abstract implementation of Aurora::Framework::IGenericData
	abstract class DataManagerBase implements IGenericData{

//!	string Regular expression used to validate the $table argument of libAurora::DataManager::DataManagerBase::Query()
		const regex_Query_arg_table = '/^[A-z0-9_]+$/';

//!	This method only performs argument validation to save duplication of code.
		public function Query(array $wantedValue, $table, QueryFilter $queryFilter=null, array $sort=null, $start=null, $count=null){
			foreach($wantedValue as $value){
				if(is_string($value) === false){
					throw new InvalidArgumentException('wantedValue must contain only strings');
				}
			}

			if(is_string($table) === false){
				throw new InvalidArgumentException('table name must be specified as string.');
			}else if(ctype_graph($table) === false){
				throw new InvalidArgumentException('table name must not contain whitespace characters');
			}else if(preg_match(static::regex_Query_arg_table, $table) != 1){
				throw new InvalidArgumentException('table name is invalid.');
			}

			if(isset($sort) === true){
				foreach($sort as $k=>$v){
					if(is_string($k) === false){
						throw new InvalidArgumentException('sort keys must be strings.');
					}else if(preg_match('/^[\ A-z0-9_\)\(\,\+\-\*\/]+$/', $k) != 1){
						throw new InvalidArgumentException('sort key appears to be invalid.');
					}else if(is_bool($v) === false){
						throw new InvalidArgumentException('values must be boolean.');
					}
				}
			}

			if(isset($start) === true && is_integer($start) === false){
				throw new InvalidArgumentException('if start is specified, it must be an integer.');
			}else if(isset($start) === true && is_integer($start) === true && $start < 0){
				throw new InvalidArgumentException('if start is specified, it must be greater than or equal to zero.');
			}else if(isset($count) === true && is_integer($count) === false){
				throw new InvalidArgumentException('if count is specified, it must be an integer.');
			}else if(isset($count) === true && is_integer($count) === true && $count < 1){
				throw new InvalidArgumentException('if count is specified, it must be greater than or equal to one.');
			}
		}
	}
}

namespace{
	require_once('DataManager/pdo.php');
}
?>
