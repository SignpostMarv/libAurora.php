<?php
/**
*	This file is based on c# code from the Aurora-Sim project.
*	As such, the original header text is included.
*/

/*
 * Copyright (c) Contributors, http://aurora-sim.org/
 * See CONTRIBUTORS.TXT for a full list of copyright holders.
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

//!	Acts as an enum for the various migration operation types
	class MigrationOperationTypes{

//!	integer Tables probably doesn't exist and this is likely a fresh install
		const CreateDefaultAndUpgradeToTarget = 0;

//!	integer Tables probably exist and likely need upgrading
		const UpgradeToTarget                 = 1;

//!	integer Tables probably exist and are at latest version
		const DoNothing                       = 2;
	}

//!	A description of a migration operation to be performed
	class MigrationOperationDescription{

//! object An instance of libAurora::Version corresponding to the current version of the migrator installed
		private $CurrentVersion;

//!	mixed An instance of libAurora::Version or NULL corresponding to the version of the migrator to start at
		private $StartVersion;

//!	mixed An instance of libAurora::Version or NULL corresponding to the version of the migrator to end at
		private $EndVersion;

//!	integer corresponds to Aurora::DataManager::Migration::MigrationOperationTypes contants
		private $OperationType;

//!	boolean TRUE if there are breaking changes, FALSE otherwise
		private $BreakingChanges = false;

//!	Since we don't have proper getters & setters in PHP, we wrap to the magic method
/**
*	@param string $name name of property access is attempted on
*	@return mixed
*	@see Aurora::DataManager::Migration::MigrationOperationDescription::$CurrentVersion
*	@see Aurora::DataManager::Migration::MigrationOperationDescription::$StartVersion
*	@see Aurora::DataManager::Migration::MigrationOperationDescription::$EndVersion
*	@see Aurora::DataManager::Migration::MigrationOperationDescription::$OperationType
*	@see Aurora::DataManager::Migration::MigrationOperationDescription::$BreakingChanges
*/
		public function __get($name){
			return ($name === 'CurrentVersion' || $name === 'StartVersion' || $name === 'EndVersion' || $name === 'OperationType' || $name === 'BreakingChanges') ? $this->$name : null;
		}

//!	Sets the parameters of the operation description
/**
*	@param integer $createDefaultAndUpgradeToTarget corresponds to Aurora::DataManager::Migration::MigrationOperationTypes contants
*	@param object $currentVersion An instance of libAurora::Version corresponding to the current version of the migrator installed
*	@param mixed $startVersion An instance of libAurora::Version or NULL corresponding to the version of the migrator to start at
*	@param mixed $endVersion An instance of libAurora::Version or NULL corresponding to the version of the migrator to end at
*	@param boolean $breakingChanges TRUE if there are breaking changes, FALSE otherwise
*/
		public function __construct($createDefaultAndUpgradeToTarget, Version $currentVersion, Version $startVersion=null, Version $endVersion=null, $breakingChanges=false){
			if(is_integer($createDefaultAndUpgradeToTarget) === false){
				throw new InvalidArgumentException('OperationType must be specified as integer.');
			}else if($createDefaultAndUpgradeToTarget < 0 || $createDefaultAndUpgradeToTarget > 2){
				throw new InvalidArgumentException('OperationType must be 0, 1 or 2.');
			}else if(is_bool($breakingChanges) === false){
				throw new InvalidArgumentException('breaking changes flag must be specified as boolean.');
			}

			$this->OperationType   = $createDefaultAndUpgradeToTarget;
			$this->CurrentVersion  = $currentVersion                 ;
			$this->StartVersion    = $startVersion                   ;
			$this->EndVersion      = $endVersion                     ;
			$this->BreakingChanges = $breakingChanges                ;
		}
	}

//!	Manages the installation & upgrading of various tables described in migrators
	class MigrationManager{

//!	object instance of Aurora::Framework::IDataConnector
		private $genericData;

//!	string migrator module name
		private $migratorName = '';

//!	array all versions for the specified module
		private $migrators = array();

//!	boolean validate tables
		private $validateTables;

//!	boolean flag indicating if execution of the migrators have occurred.
		private $executed;

//!	object instance of MigrationOperationDescription
		private $operationDescription;

//!	object instance of Migrator
		private $restorePoint;

//!	boolean flag controlling rollback operation
		private $rollback;

//!	array names of migrator classes
		private static $allMigrators = array();

//!	integer Holds the number of declared classes in case more classes are loaded at runtime between instantiation
		private static $declaredClassCount = 0;

//!	Determines what operation if any is necessary and performs it
/**
*	@param object $genericData the database connector that houses tables
*	@param string $migratorName the name of the migrator
*	@param boolean $validateTables Validate tables if TRUE
*/
		public function __construct(IDataConnector $genericData, $migratorName, $validateTables){
			if(is_string($migratorName) === false){
				throw new InvalidArgumentException('migrator name must be specified as string.');
			}else if(trim($migratorName) === ''){
				throw new InvalidArgumentException('migrator name cannot be an empty string.');
			}else if(is_bool($validateTables) === false){
				throw new InvalidArgumentException('validate tables flag must be specified as boolean.');
			}

			$this->genericData    = $genericData;
			$this->migratorName   = trim($migratorName);
			$this->validateTables = $validateTables;

			$declared = get_declared_classes();
			if(isset(static::$allMigrators) === false || count($declared) !== static::$declaredClassCount){
				$migrators = array();
				foreach($declared as $possibleMigrator){
					if(is_subclass_of($possibleMigrator, 'Aurora\DataManager\Migration\Migrator') === true){
						$migrators[] = $possibleMigrator;
					}
				}
				static::$allMigrators = $migrators;
			}

			foreach(static::$allMigrators as $migrator){
				if(constant($migrator . '::MigrationName') === $migratorName){
					$this->migrators[] = call_user_func($migrator . '::f');
				}
			}
		}

//!	Gets the description of the current migration operation
/**
*	@return Aurora::DataManager::Migration::MigrationOperationDescription
*/
		public function GetDescriptionOfCurrentOperation(){
			return $this->operationDescription;
		}

//!	Determines which operation should be performed.
/**
*	@see Aurora::DataManager::Migration::MigrationManager::$genericData
*	@see Aurora::DataManager::Migration::MigrationManager::GetHighestVersionMigratorThatCanProvideDefaultSetup()
*	@see Aurora::DataManager::Migration::MigrationManager::GetMigratorAfterVersion()
*	@see Aurora::DataManager::Migration::MigrationManager::GetLatestVersionMigrator()
*	@see Aurora::DataManager::Migration::MigrationManager::$operationDescription
*/
		public function DetermineOperation(){
			if(trim($this->migratorName) === ''){
				return false;
			}
			$this->executed = false;

			$currentVersion = $this->genericData->GetAuroraVersion($this->migratorName);
			if($currentVersion == null){

				$defaultMigrator = $this->GetHighestVersionMigratorThatCanProvideDefaultSetup();
				$currentVersion  = $defaultMigrator->Version;

				$startMigrator   = $this->GetMigratorAfterVersion($defaultMigrator->Version);
				$latestMigrator  = $this->GetLatestVersionMigrator();

				$targetMigrator  = $defaultMigrator->Version === $latestMigrator ? null : $latestMigrator;

				$this->operationDescription = new MigrationOperationDescription(
					MigrationOperationTypes::CreateDefaultAndUpgradeToTarget,
					$currentVersion,
					isset($startMigrator) ? $startMigrator->Version : null,
					isset($targetMigrator) ? $targetMigrator->Version : null,
					(isset($startMigrator) ? $startMigrator->HasBreakingchanges($this->genericData) : false) || (isset($targetMigrator) ? $targetMigrator->HasBreakingchanges($this->genericData) : false)
				);
			}else{

				$startMigrator   = $this->GetMigratorAfterVersion($currentVersion);
				if(isset($startMigrator) === true){

					$targetMigrator = $this->GetLatestVersionMigrator();
					$this->operationDescription = new MigrationOperationDescription(
						MigrationOperationTypes::UpgradeToTarget,
						$currentVersion,
						$startMigrator->Version,
						$targetMigrator->Version,
						$targetMigrator->HasBreakingchanges($this->genericData)
					);
				}else{
					$this->operationDescription = new MigrationOperationDescription(
						MigrationOperationTypes::DoNothing,
						$currentVersion
					);
				}
			}
		}

//!	Gets the next migrator after the specified version
/**
*	@param mixed $version an instance of libAurora::Version or NULL
*	@return mixed An instance of Aurora::DataManager::Migration::Migrator or NULL
*/
		public function GetMigratorAfterVersion(Version $version=null){
			if(isset($version) === false){
				return null;
			}
			$migrators = $this->migrators;
			usort($migrators, function(Migrator $a, Migrator $b){
				return Version::cmp($a->Version, $b->Version);
			});
			foreach($migrators as $migrator){
				if(Version::cmp($migrator->Version, $version) > 0){
					return $migrator;
				}
			}
			return null;
		}

//!	Gets migrator with highest version number
/**
*	@return object An instance of Aurora::DataManager::Migration::Migrator
*/
		public function GetLatestVersionMigrator(){
			$migrators = $this->migrators;
			usort($migrators, function(Migrator $a, Migrator $b){
				return Version::cmp($a->Version, $b->Version);
			});
			return end($migrators);
		}

//!	Gets the migrator with highest version number that can provide setup
/**
*	@return object An instance of Aurora::DataManager::Migration::Migrator
*/
		public function GetHighestVersionMigratorThatCanProvideDefaultSetup(){
			$migrators = $this->migrators;
			usort($migrators, function(Migrator $a, Migrator $b){
				return Version::cmp($a->Version, $b->Version);
			});
			return current($migrators);
		}

//!	Executes the operation determined by Aurora::DataManager::Migration::MigrationManager::DetermineOperation()
/**
*	@see Aurora::DataManager::Migration::MigrationManager::$migratorName
*	@see Aurora::DataManager::Migration::MigrationManager::$operationDescription
*	@see Aurora::DataManager::Migration::MigrationManager::GetMigratorByVersion()
*	@see Aurora::DataManager::Migration::MigrationManager::$executed
*	@see Aurora::DataManager::Migration::MigrationManager::$genericData
*	@see Aurora::DataManager::Migration::MigrationManager::$restorePoint
*	@see Aurora::DataManager::Migration::MigrationManager::RollBackOperation()
*	@see Aurora::DataManager::Migration::MigrationManager::GetMigratorAfterVersion()
*/
		public function ExecuteOperation(){
			if($this->migratorName === ''){
				return;
			}

			if($this->operationDescription != null && $this->executed === false && $this->operationDescription->OperationType !== MigrationOperationTypes::DoNothing){
				$currentMigrator = $this->GetMigratorByVersion($this->operationDescription->CurrentVersion);

				if($this->operationDescription->OperationType === MigrationOperationTypes::CreateDefaultAndUpgradeToTarget){
					try{
						$currentMigrator->CreateDefaults($this->genericData);
					}catch(\Exception $e){
						error_log($e);
					}
					$this->executed = true;
				}

				$validated = $currentMigrator != null && $currentMigrator->Validate($this->genericData);

				if($validated === false && $validateTables === true && $currentMigrator !== null){
					error_log(sprintf('Failed to validated migration %s-%s, retrying...', $currentMigrator->MigrationName, $currentMigrator->Version));

					$currentMigrator->Migrate($this->genericData);
					$validated = $currentMigrator->Validate($this->genericData);
					if($validated === false){
						$rec = null;
						$currentMigrator->DebugTestThatAllTablesValidate($this->genericData, $rec);
						error_log(sprintf(
							'FAILED TO REVALIDATE MIGRATION %s-%s, FIXING TABLE FORCIBLY... NEW TABLE NAME %s',
							$currentMigrator->MigrationName,
							$currentMigrator->Version,
							$rec->Name . '_broken'
						));
						$this->genericData->RenameTable($rec->Name, $rec->Name . '_broken');
						$currentMigrator->Migrate($this->genericData);
						$validated = $currentMigrator->Validate($this->genericData);
						if($validated === false){
							throw new RuntimeException(sprintf(
								'Current version %s-%s did not validate. Stopping here so we don\'t cause  any trouble. No changes were made.',
								$currentMigrator->MigrationName,
								$currentMigrator->Version
							));
						}
					}
				}


				$restoreTaken = false;
				$executingMigrator = $this->GetMigratorByVersion($this->operationDescription->StartVersion);

				if($executingMigrator !== null){
					if($validateTables == true && $currentMigrator !== null){
						//prepare restore point if something goes wrong
						$this->restorePoint = $currentMigrator->PrepareRestorePoint($this->genericData);
						$restoreTaken = true;
					}
				}

				while($executingMigrator !== null){
					try{
						$executingMigrator->Migrate($this->genericData);
					}catch(\Exception $ex){
						error_log($ex);
						if($currentMigrator != null){
							throw new RuntimeException(sprintf('Migrating to version %s failed, exception class %s thrown with code %s', $currentMigrator->Version, get_class($ex), $ex->getCode()));
						}
					}
					$executed = true;
					$validated = $executingMigrator->Validate($this->genericData);

					//if it doesn't validate, rollback
					if($validated === false && $validateTables){
						$this->RollBackOperation();
						if($currentMigrator != null){
							throw new RuntimeException(sprintf('Migrating to version %s did not validate. Restoring to restore point.', $currentMigrator->Version));
						}
					}else{
						$executingMigrator->FinishedMigration($this->genericData);
					}
					if($executingMigrator->Version == $this->operationDescription->EndVersion){
						break;
					}

					$executingMigrator = $this->GetMigratorAfterVersion($executingMigrator->Version);
				}

				if($restoreTaken){
					$currentMigrator->ClearRestorePoint($this->genericData);
				}
			}
		}

//!	Undoes any migration work
		public function RollBackOperation(){
			if($this->operationDescription !== null && $this->executed === true && $this->rollback === false && $this->restorePoint != null){
				$this->restorePoint->DoRestore($this->genericData);
				$this->rollback = true;
			}
		}

//!	Performs validation on the specified version
/**
*	@param mixed $version an instance of libAurora::Version or NULL
*	@return boolean TRUE if data is valid, FALSE otherwise
*	@see Aurora::DataManager::Migration::MigrationManager::$genericData
*/
		public function ValidateVersion(Version $version){
			return $this->GetMigratorByVersion($version)->Validate($this->genericData);
		}

//!	Attempts to find an instance of the migrator for this manager with the specified libAurora::Version object
/**
*	@param mixed $version an instance of libAurora::Version or NULL
*	@return mixed An instance of Aurora::DataManager::Migration::Migrator or NULL
*/
		public function GetMigratorByVersion(Version $version=null){
			if(isset($version) === true){
				foreach($this->migrators as $m){
					if(Version::cmp($m->Version, $version) === 0){
						return $m;
					}
				}
			}
			return null;
		}
	}
}
?>
