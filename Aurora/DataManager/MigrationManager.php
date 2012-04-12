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


namespace Aurora\DataManager\Migration{

	use libAurora\Version;

	use Aurora\Framework\IDataConnector;


	class MigrationOperationTypes{


		const CreateDefaultAndUpgradeToTarget = 0;


		const UpgradeToTarget                 = 1;


		const DoNothing                       = 2;
	}


	class MigrationOperationDescription{


		private $CurrentVersion;


		private $StartVersion;


		private $EndVersion;


		private $OperationType;


		public function __get($name){
			return ($name === 'CurrentVersion' || $name === 'StartVersion' || $name === 'EndVersion' || $name === 'OperationType') ? $this->$name : null;
		}


		public function __construct($createDefaultAndUpgradeToTarget, Version $currentVersion, Version $startVersion=null, Version $endVersion=null){
			if(is_integer($createDefaultAndUpgradeToTarget) === false){
				throw new InvalidArgumentException('OperationType must be specified as integer.');
			}else if($createDefaultAndUpgradeToTarget < 0 || $createDefaultAndUpgradeToTarget > 2){
				throw new InvalidArgumentException('OperationType must be 0, 1 or 2.');
			}else if(isset($endVersion) === true && isset($currentVersion, $startVersion) === false){
				throw new BadMethodCallException('Cannot specify endVersion without specifying startVersion.');
			}

			$this->CurrentVersion = $currentVersion;
			$this->StartVersion   = $startVersion  ;
			$this->EndVersion     = $endVersion    ;
		}
	}


	class MigrationManager{

//!	object instance of Aurora::Framework::IDataConnector
		private $genericData;

//!	string migrator module name
		private $migratorName;

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


		public function GetDescriptionOfCurrentOperation(){
			return $this->operationDescription;
		}


		public function DetermineOperation(){
			if(trim($this->migratorName) === ''){
				return false;
			}
			$this->executed = false;

			$currentVersion = $this->genericData->GetAuroraVersion($this->migratorName);
			if($currentVersion == null){

				$defaultMigrator = $this->GetHighestVersionMigratorThatCanProvideDefaultSetup();
				$this->currentVersion = $defaultMigrator->Version;

				$startMigrator   = $this->GetMigratorAfterVersion($defaultMigrator->Version);
				$latestMigrator  = $this->GetLatestVersionMigrator();

				$targetMigrator  = $defaultMigrator->Version === $latestMigrator ? null : $latestMigrator;

				$this->operationDescription = new MigrationOperationDescription(MigrationOperationTypes::CreateDefaultAndUpgradeToTarget, $currentVersion, isset($startMigrator) ? $startMigrator->Version : null, isset($targetMigrator) ? $targetMigrator->Version : null);
			}else{

				$startMigrator   = $this->GetMigratorAfterVersion($currentVersion);
				if(isset($startMigrator) === true){

					$targetMigrator = $this->GetLatestVersionMigrator();
					$this->operationDescription = new MigrationOperationDescription(MigrationOperationTypes::UpgradeToTarget, $currentVersion, $startMigrator->Version, $targetMigrator->Version);
				}else{

					$this->operationDescription = new MigrationOperationDescription(MigrationOperationTypes::DoNothing, $currentVersion);
				}
			}
		}


		public function GetMigratorAfterVersion(Version $version=null){
			if(isset($version) === false){
				return null;
			}
			$migrators = $this->migrators;
			usort($migrators, function(Migrator $a, Migrator $b){
				return Version::cmp($a->Version, $b->Version);
			});
			foreach($migrators as $migrator){
				if(Version::cmp($version, $migrator->Version) > 0){
					return $migrator;
				}
			}
			return null;
		}


		public function GetLatestVersionMigrator(){
			$migrators = $this->migrators;
			usort($migrators, function(Migrator $a, Migrator $b){
				return Version::cmp($a->Version, $b->Version);
			});
			return end($migrators);
		}


		public function GetHighestVersionMigratorThatCanProvideDefaultSetup(){
			$migrators = $this->migrators;
			usort($migrators, function(Migrator $a, Migrator $b){
				return Version::cmp($a->Version, $b->Version);
			});
			return current($migrators);
		}
	}
}
?>
