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

namespace Aurora\Framework{

//!	We don't have enums, so we need a class with constants
	class IndexType{

//!	integer Indicates the index is a primary key
		const Primary = 0;

//!	integer Indicates the index is a general index
		const Index   = 1;

//!	integer Indicates the index is a unique index
		const Unique  = 2;
	}

//!	Transposition of c# Aurora::Framework::IndexDefinition
	class IndexDefinition{

//!	object instance of ArrayObject
//!	We're making this private and wrapping it via the __get() magic method as a proxy to prevent runtime code from altering the property itself.
		private $Fields;

//!	integer A flag to indicate the type of index the definition represents.
//!	We're making this private and wrapping it via the __get() and __set() magic methods as a proxy to prevent runtime code from altering the property itself (e.g. replacing it with another type).
//!	@see Aurora::Framework::IndexType
//!	Defaults to Aurora::Framework::IndexType::Index
		private $Type = 1;

//!	we're providing a public constructor to mimic usage in c#
		public function __construct(array $fields=null, $type=1){
			$this->__set('Type', $type);
			$this->Fields = new IndexDefinition\fieldArray(isset($fields) ? $fields : array());
		}

//!	Compares two index definitions
/**
*	@param mixed $other an instance of IndexDefinition or null
*	@return boolean TRUE if $other is considered identical to this instance, FALSE otherwise
*/
		public function Equals(IndexDefinition $other=null){
			if(isset($other) === false || $this->Type != $other->Type || $this->Fields->count() !== $other->Fields->count()){
				return false;
			}else if(spl_object_hash($this) === spl_object_hash($other)){
				return true;
			}
			$i=0;
			foreach($other->Fields as $field){
				if($field !== $this->Fields[$i++]){
					return false;
				}
			}
			return true;
		}

//!	Since we don't have proper getters & setters in PHP, we wrap to the magic method
/**
*	@param string $name name of property access is attempted on
*	@return mixed
*	@see Aurora::Framework::IndexDefinition::$Fields
*	@see Aurora::Framework::IndexDefinition::$Type
*/
		public function __get($name){
			return ($name === 'Fields' || $name === 'Type') ? $this->$name : null;
		}

//!	magic method used to act as a proxy to Aurora::Framework::IndexDefinition::$Type since PHP doesn't support strongly-typed properties
		public function __set($name, $value){
			if($name !== 'Type'){
				throw new RuntimeException('Only Aurora::Framework::IndexDefinition::$Type is supported as a property.');
			}else if(is_integer($value) === false){
				throw new RuntimeException('Aurora::Framework::IndexDefinition::$Type must be an integer.');
			}else if($value !== IndexType::Primary && $value !== IndexType::Index && $value !== IndexType::Unique){
				throw new RuntimeException('Aurora::Framework::IndexDefinition::$Type must be a constant from Aurora::Framework::IndexType');
			}
			$this->Type = $value;
		}
	}
}

namespace Aurora\Framework\IndexDefinition{

	use Aurora\Framework\InvalidArgumentException;

	use ArrayObject;

	use Aurora\Framework\ColumnDefinition;

//!	strongly-typed array for index definitions
	class Iterator extends ArrayObject{

//!	sets up the array object and passes values onto Aurora::Framework::IndexDefinition::Iterator::offsetSet()
		public function __construct(){
			parent::__construct(array(), \ArrayObject::STD_PROP_LIST);
			$values = func_get_args();
			if(is_array($values) === true){
				foreach($values as $k => $v){
					$this[$k] = $v;
				}
			}
		}

//!	This is identical to the regex on libAurora::DataManager::DataManagerBase::regex_Query_arg_table, but the idea of referencing the libAurora namespace from the Aurora namespace made me uncomfortable. ~SignpostMarv
		const regex_Query_arg_table = '/^[A-z0-9_]+$/';

//!	Restricts values to instances of Aurora::Framework::IndexDefinition
		public function offsetSet($offset, $value){
			if(is_string($offset) === true && preg_match(ColumnDefinition::regex_fieldName, $offset) != 1){
				throw new InvalidArgumentException('When array offsets are strings, they must be valid field names.');
			}else if($value instanceof \Aurora\Framework\IndexDefinition){
				parent::offsetSet($offset, $value);
			}else{
				throw new InvalidArgumentException('Values must be instances of IndexDefinition.');
			}
		}
	}

//!	array of field names, lazy way of ensuring the field names will be valid
	class fieldArray extends ArrayObject{

//!	sets up the array object and passes values onto Aurora::Framework::IndexDefinition::fieldArray::offsetSet()
		public function __construct(){
			parent::__construct(array(), \ArrayObject::STD_PROP_LIST);
			$values = func_get_args();
			if(is_array($values) === true && count($values) > 0 && is_array($values[0])){
				$values = current($values);
				foreach($values as $k => $v){
					$this[$k] = $v;
				}
			}
		}

//!	Restricts values to strings
		public function offsetSet($offset, $value){
			if(is_string($value) === false){
				throw new InvalidArgumentException('values must be strings.');
			}else if(preg_match(ColumnDefinition::regex_fieldName, $value) != 1){
				throw new InvalidArgumentException('value was not a valid field.');
			}
			parent::offsetSet($offset, $value);
		}
	}
}
?>
