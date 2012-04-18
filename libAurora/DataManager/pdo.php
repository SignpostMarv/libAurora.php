<?php
//!	@file libAurora/DataManager/pdo.php
//!	@brief PDO implementation of Aurora::Framework::IDataConnector
//!	@author SignpostMarv

//!	non-transposed implementation of Aurora::DataManager classes
namespace libAurora\DataManager{

	use PDOException;
	use libAurora\InvalidArgumentException;
	use libAurora\RuntimeException;

	use PDOStatement;

	use Aurora\Framework\QueryFilter;
	use Aurora\DataManager\Migration\MigrationManager;

	use Aurora\Framework\ColumnType;
	use Aurora\Framework\ColumnTypeDef;
	use Aurora\Framework\ColumnDefinition\Iterator as ColDefs;
	use Aurora\Framework\IndexDefinition\Iterator as IndexDefs;

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
*	@param boolean $forceBreakingChanges TRUE forces breaking changes to be applied
*/
		public function __construct($connectionString, $migratorName, $validateTables, $forceBreakingChanges=false){

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

			$migrationManager = new MigrationManager($this, $migratorName, $validateTables);
			$migrationManager->DetermineOperation();
			$this->hasBreakingChanges = $migrationManager->GetDescriptionOfCurrentOperation()->BreakingChanges;
			if($this->hasBreakingChanges === false || $forceBreakingChanges === true){
				$migrationManager->ExecuteOperation();
			}
		}

//!	Prepares a query
		protected static function prepareSth(\PDO $PDO, PDOStatement & $sth=null, $query){
			try{
				$sth = $PDO->prepare($query);
			}catch(PDOException $e){
				throw new RuntimeException('Exception occurred when preparing query.', $e->getCode());
			}
		}

//!	Binds values to queries
		protected static function bindValues(PDOStatement $sth, array $ps){
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
							$type = \PDO::PARAM_NULL;
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
		protected static function returnExecute(PDOStatement $sth){
			try{
				$exec = $sth->execute();
				return $exec;
			}catch(PDOException $e){
				throw new RuntimeException('Execution of the query threw an exception.', $e->getCode());
			}
		}

//!	Gets the results of a query as a one-dimensional array
		protected static function linearResults(PDOStatement $sth){
			$retVal = array();

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

			return static::linearResults($sth);
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
				$ps[':' . QueryFilter::preparedKey(is_integer($k) ? 'insert_' . $k : $k )] = $v;
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

//!	Drops a table
/**
*	@param string $tableName name of table to drop
*/
		public function DropTable($tableName){
			parent::DropTable($table);
			$table = strtolower($table);

			try{
				$this->PDO->exec(sprintf('DROP TABLE %s', $tableName));
			}catch(PDOException $e){
				throw new RuntimeException('Failed to drop table.');
			}
		}

//!	Copies data between tables
/**
*	@param string $sourceTableName table to copy from
*	@param string $destinationTableName table to copy to
*	@param object $columnDefinitions instance of Aurora::Framework::ColumnDefinition::Iterator column definitions 
*	@param object $indexDefinitions instance of Aurora::Framework::IndexDefinition::Iterator index definitions 
*/
		protected function CopyAllDataBetweenMatchingTables($sourceTableName, $destinationTableName, ColDefs $columnDefinitions, IndexDefs $indexDefinitions){
			static::validateArg_table($sourceTableName, $destinationTableName);
			$sourceTableName      = strtolower($sourceTableName);
			$destinationTableName = strtolower($destinationTableName);
			try{
				$this->PDO->exec(sprintf('INSERT INTO %s SELECT * FROM %s', $destinationTableName, $sourceTableName));
			}catch(PDOException $e){
				throw new RuntimeException('An exception was thrown when copying data between tables.', $e->getCode());
			}
		}

//!	Converts a type string to a column type definition object
/**
*	@param string $typeString
*	@return object an instance of Aurora::Framework::ColumnTypeDef
*/
		protected static function ConvertTypeToColumnType($typeString){
			if(is_string($typeString) === false){
				throw new InvalidArgumentException('typeString must be specified as string.');
			}

			$tStr = strtolower($typeString);

			$typeDef = new ColumnTypeDef;

			switch($tStr){
                case 'blob':
                    $typeDef->Type = ColumnType::Blob;
				break;
                case 'longblob':
                    $typeDef->Type = ColumnType::LongBlob;
				break;
                case 'date':
                    $typeDef->Type = ColumnType::Date;
				break;
                case 'datetime':
                    $typeDef->Type = ColumnType::DateTime;
				break;
                case 'double':
                    $typeDef->Type = ColumnType::Double;
				break;
                case 'float':
                    $typeDef->Type = ColumnType::Float;
				break;
                case 'text':
                    $typeDef->Type = ColumnType::Text;
				break;
                case 'mediumtext':
                    $typeDef->Type = ColumnType::MediumText;
				break;
                case 'longtext':
                    $typeDef->Type = ColumnType::LongText;
				break;
                case 'uuid':
                    $typeDef->Type = ColumnType::UUID;
				break;
                case 'integer':
                    $typeDef->Type = ColumnType::Integer;
                    $typeDef->Size = 11;
				break;
				default:
					static $regexTypes = array(
						'/^int\((\d+)\)( unsigned)?$/'     => ColumnType::Integer,
						'/^tinyint\((\d+)\)( unsigned)?$/' => ColumnType::TinyInt,
						'/^char\((\d+)\)$/'                => ColumnType::Char,
						'/^varchar\((\d+)\)$/'             => ColumnType::String
					);

					foreach($regexTypes as $regex => $regexType){
						if(preg_match($regex, $tStr, $matches) == 1){
							$typeDef->Type     = $regexType;
							$typeDef->Size     = (integer)$matches[1];
							$typeDef->unsigned = ($regexType === ColumnType::Integer || $regexType === ColumnType::TinyInt) ? isset($matches[2]) && $matches[2] === ' unsigned' : false;
							break 2;
						}
					}

					throw new RuntimeException('You\'ve discovered some type that\'s not reconized by Aurora, please place the correct conversion in ConvertTypeToColumnType. Type: ' + $tStr);
				break;
			}

			return $typeDef;
		}
	}
}
?>
