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

namespace Aurora\Framework{


	interface IDataConnector extends IGenericData{

//!	Name of the module
/**
*	interface constants can't be overriden so we're going to make this a public static method.
*	@return string Name of the module
*/
		public static function Identifier();

//!	Checks to see if $table exists
/**
*	@param string $table name of the table
*	@return boolean TRUE if $table exists, FALSE otherwise
*/
		public function TableExists($table);

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
		public function WriteAuroraVersion($version, $MigrationName);

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
