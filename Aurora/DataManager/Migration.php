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

namespace Aurora\DataManager\Migration{
	use Aurora\Framework\IDataConnector;

//!	This interface exists purely to give client code the ability to detect all Migration-specific exception classes in one go.
//!	The purpose of this behaviour is that instances of Aurora::DataManager::Migration::Exception will be more or less "safe" for public consumption.
	interface Exception{
	}

	class InvalidArgumentException extends \Aurora\InvalidArgumentException implements Exception{
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


		private $renameSchema = array();


		private $renameColumns = array();


		private $schema = array();


		private $dropIndices = array();

//!	We're going to hide this behind registry methods
		protected function __construct(){
			if(preg_match('/^(\d+\.\d+|\d+\.\d+\.\d+|\d+\.\d+\.\d+\.\d+)$/', static::Version) != 1){
				throw new InvalidArgumentException('Version number was invalid, should take the form of a.b, a.b.c or a.b.c.d');
			}else if(preg_match('/^[A-Z][A-z0-9_]*$/', static::MigrationName) != 1){
				throw new InvalidArgumentException('Migration name was invalid, should start with an upper-case letter and contain only letters, numbers and underscores');
			}
		}

//!	Performs validation if necessary
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*	@return boolean not entirely sure what TRUE or FALSE indicates ~SignpostMarv
*/
		public final function Validate(IDataConnector $genericData){
			if($genericData->GetAuroraVersion(static::MigrationName) != static::Version){
				return false;
			}
			return DoValidate($genericData);
		}

//!	Performs the actual validation
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*	@return boolean not entirely sure what TRUE or FALSE indicates ~SignpostMarv
*/
		abstract protected function DoValidate(IDataConnector $genericData);

//!	Performs the migration from the current version to this version
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		public final function Migrate(IDataConnector $genericData){
			DoMigrate($genericData);
			$genericData->WriteAuroraVersion(static::Version, static::MigrationName);
		}

//!	pre-flights the migration ? ~SignpostMarv
		abstract protected function DoMigrate(IDataConnector $genericData);

//!	creates or populates the database ? ~SignpostMarv
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		public final function CreateDefaults(IDataConnector $genericData){
			DoCreateDefaults($genericData);
			$genericData->WriteAuroraVersion(static::Version, static::MigrationName);
		}

//!	performs the actual CreateDefaults operation ? ~SignpostMarv
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		abstract protected function DoCreateDefaults(IDataConnector $genericData);

//!	queues a schema rename operation
/**
*	@param string $oldTable current table name
*	@param string $newTable new table name
*/
		protected final function RenameSchema($oldTable, $newTable){
			if(is_string($oldTable) === false){
				throw new InvalidArgumentException('oldTable must be specified as string.');
			}else if(is_string($newTable) === false){
				throw new InvalidArgumentException('newTable must be specified as string.');
			}
			$this->renameSchema[$oldTable] = $newTable;
		}

//!	get the temporary table name
/**
*	@param string $tablename name of the table
*/
		protected final static function GetTempTableNameFromTableName($tablename){
			if(is_string($tablename) === false){
				throw new InvalidArgumentException('tablename must be specified as string.');
			}
			return $table . '_temp';
		}

//!	Deletes the temporary version of a table.
/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*	@param string $tableName name of the table
*/
		private final DeleteTempVersion(IDataConnector $genericData, $tableName){
			$tempTableName = static::GetTempTableNameFromTableName($tableName);
			if($genericData->TableExists($tempTableName) === true){
				$genericData->DropTable($tempTableName);
			}
		}

/**
*	@param object $genericData instance of Aurora::Framework::IDataConnector
*/
		abstract public function FinishedMigration(IDataConnector $genericData);
	}
}
?>
