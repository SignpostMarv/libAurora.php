<?php
//!	@file libAurora/DataManager/pdo.php
//!	@brief PDO implementation of Aurora::Framework::IDataConnector
//!	@author SignpostMarv

namespace libAurora\DataManager{

	use PDOException;
	use libAurora\InvalidArgumentException;
	use libAurora\RuntimeException;

	use PDOStatement;

	use Aurora\Framework\QueryFilter;

//!	PDO implementation of Aurora::Framework::IDataConnector
	abstract class PDO extends DataManagerBase{

//!	Name of the connector
		const Identifier = 'PDO';

//!	object an instance of PDO
		protected $PDO;

//!	string regular expression for finding username & password that have been fudged into the PDO DSN string.
		const regex_up = '/(;user=(.+);password=(.+);?)/';

//! Connect to database. We're deviating from the c# design here, using the constructor to connect to the database rather than a method that can be used anywhere during runtime
/**
*	Important note! Unlike the equivalent c#, this class does not create databases- the database must be created beforehand by the user (except in the case of SQLite)
*	@param string $connectionString database connection string
*	@param string $migratorName migrator module
*	@param boolean $validateTables specifying TRUE must attempt to validate tables after connecting to the database
*/
		public function __construct($connectionString, $migratorName, $validateTables){

			if(is_string($connectionString) === false){
				throw new InvalidArgumentException('connection string must be specified as string.');
			}else if(is_string($migratorName) === false){
				throw new InvalidArgumentException('migrator name must be specified as string.');
			}else if(trim($migratorName) === ''){
				throw new InvalidArgumentException('migrator name cannot be an empty string.');
			}else if(is_bool($validateTables) === false){
				throw new InvalidArgumentException('validateTables flag must be specified as boolean.');
			}

			$username = $password = null; // not all PDO drivers require credentials

			if(substr($connectionString, 0, 6) === 'mysql:' && preg_match(static::regex_up, $connectionString, $matches) == 1){
				list(,,$username, $password) = $matches;
				$connectionString = preg_replace(static::regex_up, '', $connectionString);
			}

			try{
				$this->PDO = new \PDO($connectionString, $username, $password);
			}catch(PDOException $e){
				throw new RuntimeException('Failed to connect to database with supplied connection string.');
			}
		}

//!	Prepares a query
		private static function prepareSth(\PDO $PDO, PDOStatement & $sth=null, $query){
			try{
				$sth = $PDO->prepare($query);
			}catch(PDOException $e){
				throw new RuntimeException('Exception occurred when preparing query.', $e->getCode());
			}
		}

//!	Binds values to queries
		private static function bindValues(PDOStatement $sth, array $ps){
			try{
				foreach($ps as $k=>$v){
					$type = \PDO::PARAM_STR;
					switch(gettype($v)){
						case 'boolean':
							$type = \PDO::PARAM_BOOL;
						break;
						case 'integer':
							$type = \PDO::PARAM_INT;
						break;
						case 'NULL':
							throw new RuntimeException('NULL is not a supported parameter.');
						break;
						default:
							$v = (string)$v;
						break;
					}
					$sth->bindValue($k, $v, $type);
				}
			}catch(PDOException $e){
				throw new RuntimeException('Exception occurred when binding values to query.', $e->getCode());
			}
		}

//!	Returns and executes
		private static function returnExecute(PDOStatement $sth){
			try{
				$exec = $sth->execute();
				if(!$exec){
					print_r($sth->errorInfo());
				}
				return $exec;
			}catch(PDOException $e){
				throw new RuntimeException('Execution of the query threw an exception.', $e->getCode());
			}
		}

//!	Performs a select query
/**
*	@param array $wantedValue an array of fields or operations to return. Must contain only strings.
*	@param string $table the name of the table to perform the query on.
*	@param object $queryFilter an instance of Aurora::Framework::QueryFilter
*	@param array $sort an array with strings for field names/operations for keys and booleans for values- TRUE to sort in ascending order, FALSE to sort in descending order.
*	@param mixed $start if NULL, the query expects to return all results from result zero. If $count is NULL and $start is an integer, will return $start results
*	@param mixed $count if NULL and $start is an integer, will return $start results. If $start and $count are integers, will return $count results from point $start
*	@return array A one-dimensional array of all fields in the result rows.
*/
		public function Query(array $wantedValue, $table, QueryFilter $queryFilter=null, array $sort=null, $start=null, $count=null){
			parent::Query($wantedValue, $table, $queryFilter, $sort, $start, $null);

			$query = sprintf('SELECT %s FROM %s', implode(', ', $wantedValue), $table);
			$ps = array();
			$retVal = array();

			if(isset($queryFilter) === true && $queryFilter->count() > 0){
				$query .= ' WHERE ' . $queryFilter->ToSQL($ps);
			}

			if(isset($sort) === true && count($sort) > 0){
				$parts = array();
				foreach($sort as $k=>$v){
					if(preg_match('/^(\`[A-z0-9_]+\`|[A-z0-9_]+)$/', $k) != 1){
						throw new InvalidArgumentException('sort key is invalid.');
					}
					$parts[] = sprintf('%s %s', $k, $v ? 'ASC' : 'DESC');
				}
				$query .= ' ORDER BY ' . implode(', ', $parts);
			}

			if(isset($start) === true){
				$query .= ' LIMIT ' . (string)$start;
				if(isset($count) === true){
					$query .= ', ' . (string)$count;
				}
			}

			$sth = null;
			static::prepareSth($this->PDO, $sth, $query);
			static::bindValues($sth, $ps);

			try{
				if($sth->execute() === false){
					throw new RuntimeException('Execution of the query failed.', $sth->errorCode());
				}
			}catch(PDOException $e){
				throw new RuntimeException('Execution of the query threw an exception.', $e->getCode());
			}

			$parts = array();
			try{
				$parts = $sth->fetchAll(\PDO::FETCH_NUM);
			}catch(PDOException $e){
				throw new RuntimeException('Failed to fetch query results.');
			}

			foreach($parts as $v){
				$retVal = array_merge($retVal, $v);
			}

			return $retVal;
		}

//!	Performs an insert query
/**
*	@param string $table the name of the table to perform the query on.
*	@param array $values if the keys are numeric will INSERT INTO $table VALUES($values), if the keys are strings will INSERT INTO $table (keys($values)) VALUES($values)
*	@return bool TRUE on success, FALSE otherwise
*/
		public function Insert($table, array $values){
			parent::Insert($table, $values);

			reset($values);
			$specifyKeys = is_string(key($values));
			$fields = '';
			if($specifyKeys === true){
				$fields   = '(' . implode(', ', array_keys($values)) . ')';
			}

			$ps = array();
			foreach($values as $k=>$v){
				$ps[':' . QueryFilter::preparedKey($k)] = $v;
			}
			unset($values);

			$query = sprintf('INSERT INTO %s %s VALUES(%s)', $table, $fields, implode(', ', array_keys($ps)));

			$sth = null;
			static::prepareSth($this->PDO, $sth, $query);
			static::bindValues($sth, $ps);
			return static::returnExecute($sth);
		}

//!	Performs an update query
/**
*	@param string $table the name of the table to perform the query on.
*	@param array $set an array of field names for keys
*	@param object $queryFilter an instance of Aurora::Framework::QueryFilter
*	@return bool TRUE on success, FALSE otherwise
*/
		public function Update($table, array $set, QueryFilter $queryFilter=null){
			parent::Update($table, $set, $queryFilter);

			$parts = array();
			$ps = array();
			foreach($set as $k=>$v){
				$key = ':UPDATE_' . QueryFilter::preparedKey($k);
				$ps[$key] = $v;
				$parts[] = sprintf('%s = %s', $k, $key);
			}
			$_ps = array();

			$query = sprintf('UPDATE %s SET %s', $table, implode(', ', $parts));
			if(isset($queryFilter) && $queryFilter->count() > 0){
				$query .= ' WHERE ' . $queryFilter->toSQL($_ps);
				$ps = array_merge($ps, $_ps);
				unset($_ps);
			}

			$sth = null;
			static::prepareSth($this->PDO, $sth, $query);
			static::bindValues($sth, $ps);
			return static::returnExecute($sth);
		}

//!	Performs a delete query
/**
*	@param string $table the name of the table to perform the query on.
*	@param object $queryFilter an instance of Aurora::Framework::QueryFilter
*	@return bool TRUE on success, FALSE otherwise
*/
		public function Delete($table, QueryFilter $queryFilter=null){
			parent::Delete($table, $queryFilter);

			$ps = array();
			$query = 'DELETE FROM ' . $table . (($queryFilter !== null && $queryFilter->count() >= 1) ? ' WHERE ' . $queryFilter->ToSQL($ps) : '');

			$sth = null;
			static::prepareSth($this->PDO, $sth, $query);
			static::bindValues($sth, $ps);
			return static::returnExecute($sth);
		}
	}
}
?>
