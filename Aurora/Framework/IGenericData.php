<?php
/**
*	This file is based on c# code from the Aurora-Sim project.
*	As such, the original header text is included.
*/

/*
 * Copyright (c) Contributors, http://aurora-sim.org/, http://opensimulator.org/
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

//!	Implemenetation of IGenericData from Aurora-Sim
	interface IGenericData{

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
		public function Query(array $wantedValue, $table, QueryFilter $queryFilter=null, array $sort=null, $start=null, $count=null);

//!	Performs an insert query
/**
*	@param string $table the name of the table to perform the query on.
*	@param array $values if the keys are numeric will INSERT INTO $table VALUES($values), if the keys are strings will INSERT INTO $table (keys($values)) VALUES($values)
*	@return bool TRUE on success, FALSE otherwise
*/
		public function Insert($table, array $values);

//!	Performs an update query. At the time of writing, the c# IGenericData does not have an Update method.
/**
*	@param string $table the name of the table to perform the query on.
*	@param array $set an array of field names for keys
*	@param object $queryFilter an instance of Aurora::Framework::QueryFilter
*	@return bool TRUE on success, FALSE otherwise
*/
		public function Update($table, array $set, QueryFilter $queryFilter=null);

//!	Performs a delete query
/**
*	@param string $table the name of the table to perform the query on.
*	@param object $queryFilter an instance of Aurora::Framework::QueryFilter
*	@return bool TRUE on success, FALSE otherwise
*/
		public function Delete($table, QueryFilter $queryFilter=null);
	}
}


?>
