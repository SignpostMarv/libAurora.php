<?php
//!	@file libAurora/DataManager.php
//!	@brief abstract implementation of Aurora::Framework::IDataConnector
//!	@author SignpostMarv

namespace libAurora\DataManager{

	use libAurora\RuntimeException;
	use libAurora\BadMethodCallException;
	use libAurora\InvalidArgumentException;

	use libAurora\Version;

	use Aurora\Framework\QueryFilter;
	use Aurora\Framework\IGenericData;
	use Aurora\Framework\IDataConnector;

	use Aurora\Framework\ColumnType;
	use Aurora\Framework\ColumnTypeDef;
	use Aurora\Framework\ColumnDefinition;

	use Aurora\Framework\IndexDefinition;

	use Aurora\Framework\ColumnDefinition\Iterator as ColDefs;
	use Aurora\Framework\IndexDefinition\Iterator as IndexDefs;

//!	abstract implementation of Aurora::Framework::IDataConnector
	abstract class DataManagerBase implements IDataConnector{

//!	string although interface constants can't be overridden, class constants can.
		const Identifier = 'DataManagerBase';

//!	string name of version control table
		const VERSION_TABLE_NAME = 'aurora_migrator_version';

//!	string name of migrator name column
		const COLUMN_NAME = 'name';

//!	string name of version column
		const COLUMN_VERSION = 'version';

//!	Name of the connector
/**
*	@return string Name of the connector
*/
		public static final function Identifier(){
			return static::Identifier;
		}

//! Connect to database. We're deviating from the c# design here, using the constructor to connect to the database rather than a method that can be used anywhere during runtime
/**
*	@param string $connectionString database connection string
*	@param string $migratorName migrator module
*	@param boolean $validateTables specifying TRUE must attempt to validate tables after connecting to the database
*/
		abstract public function __construct($connectionString, $migratorName, $validateTables);

//!	string Regular expression used to validate the $table argument of libAurora::DataManager::DataManagerBase::Query()
		const regex_Query_arg_table = '/^[A-z0-9_]+$/';

//!	string Regular expression used to validate field names
		const regex_Query_arg_field = '/^[A-z][A-z0-9_]+$/';

//!	boolean TRUE if the migrator makes breaking changes to tables, FALSE otherwise
		protected $hasBreakingChanges = false;

//!	Indicates whether a migrator has breaking changes or not.
/**
*	@return boolean TRUE if the migrator makes breaking changes to tables, FALSE otherwise
*/
		public function HasBreakingChanges(){
			return $this->hasBreakingChanges;
		}

//!	Performs validation on table names
		protected static function validateArg_table(){
			$args = func_get_args();
			if(count($args) < 1){
				throw new BadMethodCallException('No table names were supplied for validation.');
			}
			foreach($args as $table){
				if(is_string($table) === false){
					throw new InvalidArgumentException('table name must be specified as string.');
				}else if(ctype_graph($table) === false){
					throw new InvalidArgumentException('table name must not contain whitespace characters');
				}else if(preg_match(static::regex_Query_arg_table, $table) != 1){
					throw new InvalidArgumentException('table name is invalid.');
				}
			}
		}

//!	Performs validation on field names
		protected static function validateArg_field(){
			$args = func_get_args();
			if(count($args) < 1){
				throw new BadMethodCallException('No field names were supplied for validation.');
			}
			foreach($args as $field){
				if(is_string($field) === false){
					throw new InvalidArgumentException('field name must be specified as string.');
				}else if(ctype_graph($field) === false){
					throw new InvalidArgumentException('field name must not contain whitespace characters');
				}else if(preg_match(static::regex_Query_arg_field, $field) != 1){
					throw new InvalidArgumentException('field name is invalid.');
				}
			}
		}

//!	This implementation only performs argument validation to save duplication of code.
		public function Query(array $wantedValue, $table, QueryFilter $queryFilter=null, array $sort=null, $start=null, $count=null){
			foreach($wantedValue as $value){
				if(is_string($value) === false){
					throw new InvalidArgumentException('wantedValue must contain only strings');
				}
			}

			static::validateArg_table($table);

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

//!	This implementation only performs argument validation to save duplication of code.
		public function Insert($table, array $values){
			static::validateArg_table($table);

			if(count($values) < 1){
				throw new InvalidArgumentException('Insert query must include at least one value.');
			}
			$keys = array_keys($values);
			$int = is_integer(current($keys));
			$str = is_string(current($keys));
			foreach($keys as $k){
				if(is_integer($k) !== $int || is_string($k) !== $str){
					throw new InvalidArgumentException('value array keys must be all strings or all integers.');
				}else if($str === true && preg_match('/^(\`[A-z0-9_]+\`|[A-z0-9_]+)$/', $k) != 1){
					throw new InvalidArgumentException('field name was invalid.');
				}
			}
		}

//!	This implementation only performs argument validation to save duplication of code.
		public function Update($table, array $set, QueryFilter $queryFilter=null){
			static::validateArg_table($table);

			if(count($set) < 1){
				throw new InvalidArgumentException('Insert query must include at least one value.');
			}
			$keys = array_keys($set);
			foreach($keys as $k){
				if(preg_match('/^(\`[A-z0-9_]+\`|[A-z0-9_]+)$/', $k) != 1){
					throw new InvalidArgumentException('field name was invalid.');
				}
			}
		}

//!	This implementation only performs argument validation to save duplication of code.
		public function Delete($table, QueryFilter $queryFilter=null){
			static::validateArg_table($table);
		}

//!	This implementation only performs argument validation to save duplication of code.
		public function TableExists($table){
			static::validateArg_table($table);
		}

//!	This implementation only performs argument validation to save duplication of code.
		public function CreateTable($table, ColDefs $columns, IndexDefs $indexDefinitions){
			static::validateArg_table($table);
			if($this->TableExists(strtolower($table)) === true){
				throw new InvalidArgumentException('Trying to create a table with name of one that already exists.');
			}
		}

//!	This implementation only performs argument validation to save duplication of code.
		public function UpdateTable($table, ColDefs $column, IndexDefs $indices, array $renameColumns){
			static::validateArg_table($table);

			if(count($renameColumns) > 0){
				call_user_func_array(get_class($this) . '::validateArg_field', array_merge(array_keys($renameColumns), array_values($renameColumns)));
			}
		}

//!	Checks if the version table exists in the database and creates it if necessary
		protected function ensureVersionTableExists(){
			if($this->TableExists(static::VERSION_TABLE_NAME) === false){
				$this->CreateTable(static::VERSION_TABLE_NAME, new ColDefs(
					new ColumnDefinition(
						static::COLUMN_VERSION,
						array( 'Type' => ColumnType::Text, 'Size' => 100 )
					),
					new ColumnDefinition(
						static::COLUMN_NAME,
						array( 'Type' => ColumnType::Text, 'Size' => 100 )
					)
				), new IndexDefs);
			}
		}

//!	Gets the latest version of the database
/**
*	@param string $migratorName corresponds to Aurora::DataManager::Migration::Migrator::MigrationName
*	@return object instance of libAurora::Version corresponds to Aurora::DataManager::Migration::Migrator::Version
*/
		public function GetAuroraVersion($migratorName){
			if(is_string($migratorName) === false){
				throw new InvalidArgumentException('migrator name must be specified as string.');
			}else if(trim($migratorName) === ''){
				throw new InvalidArgumentException('migrator name cannot be an empty string.');
			}
			$migratorName = trim($migratorName);
			$this->ensureVersionTableExists();

			$filter = new QueryFilter;
			$filter->andFilters[static::COLUMN_NAME] = $migratorName;
			$results = $this->Query(array( static::COLUMN_VERSION ), static::VERSION_TABLE_NAME, $filter);

			$highestVersion = null;
			if(count($results) > 0){
				foreach($results as $result){
					if(trim($result) !== ''){
						$version = new Version($result);
						if($highestVersion === null || Version::cmp($version, $highestVersion) > 0){
							$highestVersion = $version;
						}
					}
				}
			}
			return $highestVersion;
		}

//!	Set the version of the database
/**
*	@param string $version version to write to the database
*	@param string $MigrationName migrator module to write to the database
*/
		public function WriteAuroraVersion(Version $version, $MigrationName){
			if(is_string($MigrationName) === false){
				throw new InvalidArgumentException('migrator name must be specified as string.');
			}else if(trim($MigrationName) === ''){
				throw new InvalidArgumentException('migrator name cannot be an empty string.');
			}
			$MigrationName = trim($MigrationName);
			$this->ensureVersionTableExists();

			$filter = new QueryFilter;
			$filter->andFilters[static::COLUMN_NAME] = $MigrationName;
			$this->Delete(static::VERSION_TABLE_NAME, $filter);

			$this->Insert(static::VERSION_TABLE_NAME, array((string)$version, $MigrationName));
		}

//!	copy tables
/**
*	@param string $sourceTableName name of table to copy from
*	@param string $destinationTableName name of table to copy to
*	@param object $columnDefinitions instance of Aurora::DataManager::Migration::ColumnDefinition::Iterator
*	@param object $indexDefinitions instance of Aurora::DataManager::Migration::IndexDefinition::Iterator
*/
		public function CopyTableToTable($sourceTableName, $destinationTableName, ColDefs $columnDefinitions, IndexDefs $indexDefinitions){
			static::validateArg_table($sourceTableName, $destinationTableName);

			if($this->TableExists($sourceTableName) === false){
				throw new InvalidArgumentException('Cannot copy table to new name, source table does not exist: ' . $sourceTableName);
			}else if($this->TableExists($destinationTableName) === true){
				$this->DropTable($destinationTableName);
				if($this->TableExists($destinationTableName) === true){
					throw new InvalidArgumentException('Cannot copy table to new name, source table does not match columnDefinitions: ' . $destinationTableName);
				}
			}

			$this->EnsureTableExists($destinationTableName, $columnDefinitions, $indexDefinitions, null);
			$this->CopyAllDataBetweenMatchingTables($sourceTableName, $destinationTableName, $columnDefinitions, $indexDefinitions);
		}

//!	Check whether the data table exists and that the columns and indices are correct
/**
*	@param string $tableName name of the table
*	@param object $columnDefinitions instance of Aurora::DataManager::Migration::ColumnDefinition::Iterator
*	@param object $indexDefinitions instance of Aurora::DataManager::Migration::IndexDefinition::Iterator
*	@return boolean TRUE if the table exists as described, FALSE otherwise
*/
		public function VerifyTableExists($tableName, ColDefs $columnDefinitions, IndexDefs $indexDefinitions){
			static::validateArg_table($tableName);
			if($this->TableExists($tableName) === false){
				error_log('Issue finding table ' . $tableName . ' when verifying table exist!');
				return false;
			}

			$extractedColumns = $this->ExtractColumnsFromTable($tableName);
			$newColumns       = array();
			foreach($columnDefinitions as $columnDefinition){
				$newColumns[strtolower($columnDefinition->Name)] = $columnDefinition;
				foreach($extractedColumns as $extractedDefinition){
					if($columnDefinition->Type->Equals($extractedDefinition->Type) === true){
						continue 2;
					}
				}
				$thisDef = null;
				foreach($extractedColumns as $extractedDefinition){
					if(strtolower($extractedDefinition->Name) === strtolower($columnDefinition->Name)){
						$thisDef = $extractedDefinition;
						break;
					}
				}

				if($thisDef !== null){
					if(static::GetColumnTypeStringSymbol($thisDef->Type) === static::GetColumnTypeStringSymbol($columnDefinition->Type)){
						continue;
					}else{
						error_log('Mismatched Column Type on ' . $tableName . '.' . $thisDef->Name . ': ' . static::GetColumnTypeStringSymbol($thisDef->Type) . ', ' . static::GetColumnTypeStringSymbol($columnDefinition->Type));
					}
				}
				error_log('Issue verifying table ' . $tableName . ' column ' . $columnDefinition->Name . ' when verifying tables exist, problem with new column definitions.');
				return false;
			}
			foreach($extractedColumns as $columnDefinition){
				if(isset($newColumns[strtolower($columnDefinition->Name)]) === false){
					$thisDef = null;
					foreach($newColumns as $extractedDefinition){
						if(strtolower($extractedDefinition->Name) === strtolower($columnDefinition->Name)){
							$thisDef = $extractedDefinition;
							break;
						}
					}
					if($thisDef === null){
						if(static::GetColumnTypeStringSymbol($thisDef->Type) === static::GetColumnTypeStringSymbol($columnDefinition->Type)){
                            continue; //They are the same type, let them go on through
						}
					}
					error_log('Issue verifing table ' . $tableName . ' column ' . $columnDefinition->Name . ' when verifing tables exist, problem with old column definitions');
					return false;
				}
			}

			$ei               = $this->ExtractIndicesFromTable($tableName);
			$extractedIndices = array();
			foreach($ei as $v){
				$extractedIndices[] = $v;
			}
			$newIndices       = array();
			foreach($indexDefinitions as $indexDefinition){
				$newIndices[] = $indexDefinition;
				$found = false;
				foreach($extractedIndices as $v){
					if($indexDefinition->Equals($v) === true){
						$found = true;
						break;
					}
				}
				if($found === false){
					$thisDef = null;
					foreach($extractedIndices as $extractedDefinition){
						if($extractedDefinition->Equals($indexDefinition) === true){
							$thisDef = $extractedDefinition;
							break;
						}
					}
					if($thisDef === null){
						error_log('Issue verifying table ' . $tableName . ' index ' . (string)$indexDefinition->Type . ' (' . implode(', ', $indexDefinition->Fields->getArrayCopy()) . ') when verifying table exist');
						return false;
					}
				}
			}
			foreach($extractedIndices as $indexDefinition){
				$found = false;
				foreach($newIndices as $v){
					if($indexDefinition->Equals($v) === true){
						$found = true;
						break;
					}
				}
				if($found === false){
					$thisDef = null;
					foreach($newIndices as $extractedDefinition){
						if($extractedDefinition->Equals($indexDefinition) === true){
							$thisDef = $extractedDefinition;
							break;
						}
					}
					if($thisDef === null){
						error_log('Issue verifying table ' . $tableName . ' index ' . (string)$indexDefinition->Type . ' (' . implode(', ', $indexDefinition->Fields->getArrayCopy()) . ') when verifying table exist');
						return false;
					}
				}
			}

			return true;
		}

//!	Check whether the data table exists and that the columns and indices are correct, creating the table if it doesn't exist.
/**
*	@param string $tableName name of the table
*	@param object $columnDefinitions instance of Aurora::DataManager::Migration::ColumnDefinition::Iterator
*	@param object $indexDefinitions instance of Aurora::DataManager::Migration::IndexDefinition::Iterator
*	@param mixed $renameColumns array of columns to rename or NULL
*	@return boolean TRUE if the table exists as described, FALSE otherwise
*/
		public function EnsureTableExists($tableName, ColDefs $columnDefinitions, IndexDefs $indexDefinitions, array $renameColumns=null){
			static::validateArg_table($tableName);
			if($this->TableExists($tableName) === true){
				if($this->VerifyTableExists($tableName, $columnDefinitions, $indexDefinitions) === false){
					$this->UpdateTable($tableName, $columnDefinitions, $indexDefinitions, $renameColumns);
				}
				return;
			}

			$this->CreateTable($tableName, $columnDefinitions, $indexDefinitions);
		}

//!	Converts a column type definition object to an implementation-appropriate string
/**
*	In c#, this is an object method, not a class method. It's also public, whereas here we make it protected
*	@param object $coldef an instance of Aurora::Framework::ColumnTypeDef
*	@return string implementation-appropriate string
*/
		abstract protected static function GetColumnTypeStringSymbol(ColumnTypeDef $coldef);

//!	This method performs argument validation then passes the task off to a protected method to be implemented elsewhere.
		public function RenameTable($oldTableName, $newTableName){
			static::validateArg_table($oldTableName, $newTableName);

			if($this->TableExists($oldTableName) === true && $this->TableExists($newTableName) === false){
				$this->ForceRenameTable($oldTableName, $newTableName);
			}
		}

//!	Performs table renaming without checking for table existance.
//!	This implementation only performs argument validation to save duplication of code.
/**
*	Unlike the c# code, we're making this a protected method
*	@param string $oldTableName current table name
*	@param string $newTableName new table name
*	@see Aurora::Framework::IDataConnector::RenameTable()
*/
		protected function ForceRenameTable($oldTableName, $newTableName){
			static::validateArg_table($oldTableName, $newTableName);
		}

//!	This implementation only performs argument validation to save duplication of code.
		public function DropTable($tableName){
			static::validateArg_table($tableName);
		}

//!	Copies data between tables
/**
*	@param string $sourceTableName table to copy from
*	@param string $destinationTableName table to copy to
*	@param object $columnDefinitions instance of Aurora::Framework::ColumnDefinition::Iterator column definitions 
*	@param object $indexDefinitions instance of Aurora::Framework::IndexDefinition::Iterator index definitions 
*/
		abstract protected function CopyAllDataBetweenMatchingTables($sourceTableName, $destinationTableName, ColDefs $columnDefinitions, IndexDefs $indexDefinitions);

//!	Extract the existing column definitions from a table
/**
*	@param string $tableName name of table to extract columns from.
*	@return object instance of Aurora::Framework::ColumnDefinition::Iterator
*/
		abstract protected function ExtractColumnsFromTable($tableName);

//!	Extract the existing index definitions from a table
/**
*	@param string $tableName name of table to extract columns from.
*	@return object instance of Aurora::Framework::IndexDefinition::Iterator with array keys being the names of indices
*/
		abstract protected function ExtractIndicesFromTable($tableName);
	}
}

namespace{
	require_once('DataManager/pdo.php');
	require_once('DataManager/mysql.php');
}
?>
