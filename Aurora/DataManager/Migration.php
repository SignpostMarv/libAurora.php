<?php
/**
*	This file is based on c# code from the Aurora-Sim project.
*	As such, the original header text is included.
*/

/*
 * Copyright (c) Contributors, http://aurora-sim.org/
 * See Aurora-CONTRIBUTORS.TXT for a full list of copyright holders.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Aurora-Sim Project nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE DEVELOPERS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

//!	Transposition of automated table installation/upgrading code from aurora-sim c#
namespace Aurora\DataManager\Migration{

	use libAurora\Version;

	use Aurora\Framework\IDataConnector;
	use Aurora\Framework\ColumnDefinition\Iterator as ColDefs;
	use Aurora\Framework\IndexDefinition\Iterator as IndexDefs;

//!	This interface exists purely to give client code the ability to detect all Migration-specific exception classes in one go.
//!	The purpose of this behaviour is that instances of Aurora::DataManager::Migration::Exception will be more or less "safe" for public consumption.
	interface Exception{
	}

//!	Migration-specific invalid argument exception
	class InvalidArgumentException extends \Aurora\InvalidArgumentException implements Exception{
	}

//!	Migration-specific bad method call exception
	class BadMethodCallException extends \Aurora\BadMethodCallException implements Exception{
	}

//!	Migration-specific runtime exception
	class RuntimeException extends \Aurora\RuntimeException implements Exception{
	}

//!	The c# uses a couple of interfaces that we're not using
	abstract class Migrator{

//!	string The version of this migrator (to automate upgrades)
//!	In c# this is an instance of System.Version from mscorlib.dll, v2.0.50727
//!	we're making it a string here because we're being lazy.
		const Version = '0.0.0';

//!	string the module migration name (to automate upgrades)
//!	string in c# this is an property with a getter only.
		const MigrationName = '-Example'; // this deliberately starts with an invalid character

//!	string a space-seperated list of tables that have major changes made to them, requiring portions of a website that use them to be disabled until after an upgrade has been performed.
		const BreakingChanges = '';

//!	array tables to rename
		protected $renameSchema  = array();

//!	array fields to rename
		protected $renameColumns = array();

//!	array of Aurora::DataManager::Migration::Migrator::Schema instances
		protected $schema        = array();

//!	array indexes on the tables to drop
		private $dropIndices   = array();

//!	object instance of libAurora::Version
		private $Version;

//!	Since we don't have proper getters & setters in PHP, we wrap to the magic method
/**
*	@param string $name name of property access is attempted on
*	@return mixed
*	@see Aurora::DataManager::Migration::Migrator::$Version
*	@see Aurora::DataManager::Migration::Migrator::Version
*/
		public function __get($name){
			if($name === 'Version'){
				if(isset($this->Version) === false){
					$this->Version = new Version(static::Version);
				}
				return $this->Version;
			}
			return null;
		}

//!	We're going to hide this behind factory methods
		protected function __construct(){
			if(preg_match('/^(\d+\.\d+|\d+\.\d+\.\d+|\d+\.\d+\.\d+\.\d+)$/', static::Version) != 1){
				throw new InvalidArgumentException('Version number was invalid, should take the form of a.b, a.b.c or a.b.c.d');
			}else if(preg_match('/^[A-Z][A-z0-9_]*$/', static::MigrationName) != 1){
				throw new InvalidArgumentException('Migration name was invalid, should start with an upper-case letter and contain only letters, numbers and underscores');
			}
		}

//!	factory method
/**
*	@return object instance of Aurora::DataManager::Migration::Migrator
*/
		public static function f(){
			return new static();
		}

//!	Performs a table restoration when a migration failed
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		public function DoRestore(IDataConnector $genericData){
			RestoreTempTablesToReal($genericData);
		}

//!	Performs validation if necessary
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*	@return boolean not entirely sure what TRUE or FALSE indicates ~SignpostMarv
*/
		public final function Validate(IDataConnector $genericData){
			if($genericData->GetAuroraVersion(static::MigrationName) != $this->__get('Version')){
				return false;
			}
			return $this->DoValidate($genericData);
		}

//!	Performs the actual validation
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*	@return boolean not entirely sure what TRUE or FALSE indicates ~SignpostMarv
*/
		abstract protected function DoValidate(IDataConnector $genericData);

//!	prepares a restore point
		public final function PrepareRestorePoint(IDataConnector $genericData){
			$this->DoPrepareRestorePoint($genericData);
			return $this;
		}

//!	Performs the actual restore point preparation
		abstract protected function DoPrepareRestorePoint(IDataConnector $genericData);

//!	Performs the migration from the current version to this version
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		public final function Migrate(IDataConnector $genericData){
			$this->DoMigrate($genericData);
			$genericData->WriteAuroraVersion(new Version(static::Version), static::MigrationName);
		}

//!	pre-flights the migration ? ~SignpostMarv
		abstract protected function DoMigrate(IDataConnector $genericData);

//!	creates or populates the database ? ~SignpostMarv
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		public final function CreateDefaults(IDataConnector $genericData){
			$this->DoCreateDefaults($genericData);
			$genericData->WriteAuroraVersion(new Version(static::Version), static::MigrationName);
		}

//!	performs the actual CreateDefaults operation ? ~SignpostMarv
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		abstract protected function DoCreateDefaults(IDataConnector $genericData);

//!	Queues a schema for creation
/**
*	@param string $table name of schema
*	@param object $definitions an instance of Aurora::Framework::ColumnDefinition::Iterator specifying column definitions
*	@param mixed $indices NULL indicating no indices or an instance of Aurora::Framework::IndexDefinition::Iterator specifying index definitions
*/
		protected final function AddSchema($table, ColDefs $definitions, IndexDefs $indices=null){
			$this->schema[$table] = new Migrator\Schema($table, $definitions, $indices);
		}

//!	queues a schema rename operation
/**
*	@param string $oldTable current table name
*	@param string $newTable new table name
*/
		protected final function RenameSchema($oldTable, $newTable){
			if(is_string($oldTable) === false){
				throw new InvalidArgumentException('oldTable must be specified as string.');
			}else if(preg_match(Migrator\Schema::regex_Query_arg_table, $oldTable) != 1){
				throw new InvalidArgumentException('oldTable name was invalid.');
			}else if(is_string($newTable) === false){
				throw new InvalidArgumentException('newTable must be specified as string.');
			}else if(preg_match(Migrator\Schema::regex_Query_arg_table, $newTable) != 1){
				throw new InvalidArgumentException('newTable name was invalid.');
			}
			$this->renameSchema[$oldTable] = $newTable;
		}

//!	remove a schema from the queue
/**
*	@param string $table schema name
*/
		protected function RemoveSchema($table){
			$remove = array();
			foreach($this->schema as $k=>$schema){
				if(strtolower($schema->Name) === strtolower($table)){
					$remove[] = $k;
				}
			}
			foreach($remove as $removeTable){
				unset($this->schema[$removeTable]);
			}
		}

//!	queue an index for removal
		protected function RemoveIndices($table, IndexDefs $indices){
			if(is_string($table) === false){
				throw new InvalidArgumentException('table must be specified as string.');
			}else if(preg_match(Migrator\Schema::regex_Query_arg_table, $table) != 1){
				throw new InvalidArgumentException('table was invalid.');
			}else if(isset($this->dropIndices[$table]) === false){
				$this->dropIndices[$table] = new IndexDefs;
			}
			foreach($indices as $index){
				$this->dropIndices[] = $index;
			}
		}

//!	ensures all tables in schema exist
		protected function EnsureAllTablesInSchemaExist(IDataConnector $genericData){
			foreach($this->renameSchema as $k=>$v){
				$genericData->RenameTable($k, $v);
			}
			foreach($this->schema as $def){
				$genericData->EnsureTableExists($def->Name, $def->ColDefs, $def->IndexDefs, $this->renameColumns);
			}
		}

//!	tests that all tables validate
		protected function TestThatAllTablesValidate(IDataConnector $genericData){
			foreach($this->schema as $def){
				if($genericData->VerifyTableExists($def->Name, $def->ColDefs, $def->IndexDefs) === false){
					return false;
				}
			}
			return true;
		}

//!	debug-mode version of Aurora::DataManager::Migration::Migrator::TestThatAllTablesValidate()
		protected function DebugTestThatAllTablesValidate(IDataConnector $genericData, Migrator\Schema & $reason=null){
			foreach($this->schema as $def){
				if($genericData->VerifyTableExists($def->Name, $def->ColDefs, $def->IndexDefs) === false){
					$reason = $def;
					return false;
				}
			}
			return true;
		}

//!	copies all tables to temp versions
		protected function CopyAllTablesToTempVersions(IDataConnector $genericData){
			foreach($this->schema as $def){
				$this->CopyTableToTempVersion($genericData, $def);
			}
		}

//!	restores all temp tables to real tables
		protected function RestoreTempTablesToReal(IDataConnector $genericData){
			foreach($this->schema as $def){
				$this->RestoreTempTableToReal($genericData, $def);
			}
		}

//!	copies a table to a temporary table
/**
*	@param object $genericData Aurora::Framework::IDataConnector database tables are on
*	@param object $table table definition to copy
*/
		protected function CopyTableToTempVersion(IDataConnector $genericData, Migrator\Schema $table){
			$genericData->CopyTableToTable($table->Name, static::GetTempTableNameFromTableName($table->Name), $table->ColDefs, $table->IndexDefs);
		}

//!	get the temporary table name
/**
*	@param string $tablename name of the table
*	@return string temporary table name
*/
		protected final static function GetTempTableNameFromTableName($tablename){
			if(is_string($tablename) === false){
				throw new InvalidArgumentException('tablename must be specified as string.');
			}else if(preg_match(Migrator\Schema::regex_Query_arg_table, $tablename) != 1){
				throw new InvalidArgumentException('tablename was invalid.');
			}
			return $table . '_temp';
		}

//!	Restores a temporary table to a real table
		private function RestoreTempTableToReal(IDataConnector $genericData, Migrator\Schema $table){
			$genericData->CopyTableToTable(static::GetTempTableNameFromTableName($table->Name), $table->Name, $table->ColDefs, $this->IndexDefs);
		}

//!	Clears a restore point
		public function ClearRestorePoint(IDataConnector $genericData){
			foreach($this->schema as $def){
				$this->DeleteTempVersion($genericData, $def->Name);
			}
		}

//!	Deletes the temporary version of a table.
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*	@param string $tableName name of the table
*/
		private final function DeleteTempVersion(IDataConnector $genericData, $tableName){
			$tempTableName = static::GetTempTableNameFromTableName($tableName);
			if($genericData->TableExists($tempTableName) === true){
				$genericData->DropTable($tempTableName);
			}
		}

//!	performed when migration finishes ? ~SignpostMarv
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		abstract public function FinishedMigration(IDataConnector $genericData);

//!	non-transposed method, used to determine if a migrator has breaking changes that required changes in code so if the old tables run with the new code, stuff will break.
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		public function HasBreakingchanges(IDataConnector $genericData){
			static $hasBreakingChanges = null;
			if(isset($hasBreakingChanges) === false){
				if(count($this->schema) === 0 || trim(static::BreakingChanges) === ''){
					$hasBreakingChanges = false;
				}else{
					$breakingChanges = explode(' ', static::BreakingChanges);
					$regex = constant(get_class($genericData) . '::regex_Query_arg_table');
					$tables = array();
					foreach($this->schema as $schema){
						$tables[] = $schema->Name;
					}
					foreach($breakingChanges as $breakingChange){
						if(preg_match($regex, $breakingChange) != 1){
							throw new RuntimeException('A table listed in the breaking changes list has an invalid name.');
						}else if(in_array($breakingChange, $tables) === false){
							throw new RuntimeException('A table listed in the breaking changes list is not listed in the schema for this migrator.');
						}
					}
					$hasBreakingChanges = count($breakingChanges) > 0;
				}
			}
			return $hasBreakingChanges;
		}
	}
}

//!	This namespace holds code that avoids transposing the C5 library to PHP
namespace Aurora\DataManager\Migration\Migrator{

	use Aurora\DataManager\Migration\InvalidArgumentException;

	use Aurora\Framework\ColumnDefinition\Iterator as ColDefs;
	use Aurora\Framework\IndexDefinition\Iterator as IndexDefs;

//!	Table definition
	class Schema{

//!	string regular expression for validating table names
		const regex_Query_arg_table = '/^[A-z0-9_]+$/';

//!	string name of schema
		private $Name;

//!	object instance of Aurora::Framework::ColumnDefinition::Iterator specifying column definitions
		private $ColDefs;

//!	object instance of Aurora::Framework::IndexDefinition::Iterator specifying index definitions
		private $IndexDefs;


//!	Since we don't have proper getters & setters in PHP, we wrap to the magic method
/**
*	@param string $name name of property access is attempted on
*	@return mixed
*	@see Aurora::DataManager::Migration::Migrator::Schema::$Name
*	@see Aurora::DataManager::Migration::Migrator::Schema::$ColDefs
*	@see Aurora::DataManager::Migration::Migrator::Schema::$IndexDefs
*/
		public function __get($name){
			return ($name === 'Name' || $name === 'ColDefs' || $name === 'IndexDefs') ? $this->$name : null;
		}

//!	Creates a table definition
/**
*	@param string $table name of table
*	@param object $ColDefs instance of Aurora::Framework::ColumnDefinition::Iterator specifying column definitions
*	@param mixed $IndexDefs NULL or instance of Aurora::Framework::IndexDefinition::Iterator specifying index definitions
*/
		public function __construct($table, ColDefs $ColDefs, IndexDefs $IndexDefs=null){
			if(is_string($table) === false){
				throw new InvalidArgumentException('Schema name must be specified as string.');
			}else if(preg_match(static::regex_Query_arg_table, $table) != 1){
				throw new InvalidArgumentException('Schema name was invalid.');
			}

			$this->Name      = $table;
			$this->ColDefs   = $ColDefs;
			$this->IndexDefs = isset($IndexDefs) ? $IndexDefs : new IndexDefs;
		}
	}
}
?>
