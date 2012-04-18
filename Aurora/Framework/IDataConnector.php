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

//!	transposition of aurora-sim c# code from it's Aurora::Framework namespace
namespace Aurora\Framework{

	use libAurora\Version;

	use Aurora\Framework\ColumnDefinition\Iterator as ColDefs;
	use Aurora\Framework\IndexDefinition\Iterator as IndexDefs;

//!	Transposition of the IDataConnector interface from aurora-sim
	interface IDataConnector extends IGenericData{

//!	Name of the connector
/**
*	interface constants can't be overriden so we're going to make this a public static method.
*	@return string Name of the connector
*/
		public static function Identifier();

//!	Indicates whether a migrator has breaking changes or not.
/**
*	@return boolean TRUE if the migrator makes breaking changes to tables, FALSE otherwise
*/
		public function HasBreakingChanges();

//!	Checks to see if $table exists
/**
*	@param string $table name of the table
*	@return boolean TRUE if $table exists, FALSE otherwise
*/
		public function TableExists($table);

//!	Create a table with the supplied columns and indices
/**
*	@param string $table name of the table
*	@param object $columns instance of Aurora::DataManager::Migration::ColumnDefinition::Iterator
*	@param object $indexDefinitions instance of Aurora::DataManager::Migration::IndexDefinition::Iterator
*/
		public function CreateTable($table, ColDefs $columns, IndexDefs $indexDefinitions);

//!	Gets the latest version of the database
/**
*	@param string $migratorName corresponds to Aurora::DataManager::Migration::Migrator::MigrationName
*	@return object instance of libAurora::Version corresponds to Aurora::DataManager::Migration::Migrator::Version
*/
		public function GetAuroraVersion($migratorName);

//!	Set the version of the database
/**
*	@param string $version version to write to the database
*	@param string $MigrationName migrator module to write to the database
*/
		public function WriteAuroraVersion(Version $version, $MigrationName);

//!	copy tables
/**
*	@param string $sourceTableName name of table to copy from
*	@param string $destinationTableName name of table to copy to
*	@param object $columnDefinitions instance of Aurora::DataManager::Migration::ColumnDefinition::Iterator
*	@param object $indexDefinitions instance of Aurora::DataManager::Migration::IndexDefinition::Iterator
*/
		public function CopyTableToTable($sourceTableName, $destinationTableName, ColDefs $columnDefinitions, IndexDefs $indexDefinitions);

//!	Check whether the data table exists and that the columns and indices are correct
/**
*	@param string $table name of the table
*	@param object $columns instance of Aurora::DataManager::Migration::ColumnDefinition::Iterator
*	@param object $indexDefinitions instance of Aurora::DataManager::Migration::IndexDefinition::Iterator
*	@return boolean TRUE if the table exists as described, FALSE otherwise
*/
		public function VerifyTableExists($table, ColDefs $columns, IndexDefs $indexDefinitions);

//!	Check whether the data table exists and that the columns and indices are correct, creating the table if it doesn't exist.
/**
*	@param string $table name of the table
*	@param object $columns instance of Aurora::DataManager::Migration::ColumnDefinition::Iterator
*	@param object $indexDefinitions instance of Aurora::DataManager::Migration::IndexDefinition::Iterator
*	@param mixed $renameColumns array of columns to rename or NULL
*	@return boolean TRUE if the table exists as described, FALSE otherwise
*/
		public function EnsureTableExists($table, ColDefs $columns, IndexDefs $indexDefinitions, array $renameColumns=null);

//!	Rename the table from $oldTableName to $newTableName
/**
*	@param string $oldTableName current table name
*	@param string $newTableName new table name
*/
		public function RenameTable($oldTableName, $newTableName);

//!	Drop a table
/**
*	@param string $tableName table to drop
*/
		public function DropTable($tableName);
	}
}
?>
