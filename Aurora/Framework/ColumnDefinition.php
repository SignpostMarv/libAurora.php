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
	class ColumnType{


        const Blob       = 0;


        const LongBlob   = 1;


        const Char       = 2;


        const Date       = 3;


        const DateTime   = 4;


        const Double     = 5;


        const Integer    = 6;


        const String     = 7;


        const Text       = 8;


        const MediumText = 9;


        const LongText   = 10;


        const TinyInt    = 11;


        const Float      = 12;


        const Boolean    = 13;


        const UUID       = 14;


        const Unknown    = 15;
	}


	class ColumnTypeDef{

//!	integer A flag to indicate the type of column the definition represents.
//!	We're making this private and wrapping it via the __get() and __set() magic methods as a proxy to prevent runtime code from altering the property itself (e.g. replacing it with another type).
//!	@see Aurora::Framework::ColumnType
		private $Type;

//!	integer size of the field. Does not apply to all types.
//!	We're making this private and wrapping it via the __get() and __set() magic methods as a proxy to prevent runtime code from altering the property itself (e.g. replacing it with another type).
		private $Size;

//!	string default field value
//!	We're making this private and wrapping it via the __get() and __set() magic methods as a proxy to prevent runtime code from altering the property itself (e.g. replacing it with another type).
		private $defaultValue;

//!	bool TRUE if the field can hold NULL values, FALSE otherwise.
		private $isNull = false;

//!	bool TRUE if the numeric field is an unsigned number, FALSE otherwise
		private $unsigned = false;

//!	bool TRUE if the numeric field should auto-increment, FALSE otherwise
		private $auto_increment=false;

//!	we're providing a public constructor to mimic usage in c#
		public function __construct(array $args=null){
			if(isset($args) === true){
				foreach($args as $k=>$v){
					$this->__set($k, $v);
				}
			}
		}

//!	magic method used to act as a proxy to the private properties
/**
*	@see Aurora::Framework::ColumnTypeDef::$Type
*	@see Aurora::Framework::ColumnTypeDef::$Size
*	@see Aurora::Framework::ColumnTypeDef::$defaultValue
*	@see Aurora::Framework::ColumnTypeDef::$isNull
*	@see Aurora::Framework::ColumnTypeDef::$unsigned
*	@see Aurora::Framework::ColumnTypeDef::$auto_increment
*/
		public function __get($name){
			return ($name === 'Type' || $name === 'Size' || $name === 'defaultValue' || $name === 'isNull' || $name === 'unsigned' || $name === 'auto_increment') ? $this->$name : null;
		}

//!	magic method used to act as a proxy to the private properties
		public function __set($name, $value){
			switch($name){
				case 'Type':
					if(is_integer($value) === false){
						throw new RuntimeException('Type must be specified as an integer.');
					}else if($value < 0 || $value > 15){
						throw new RuntimeException('Type was not a supported value.');
					}
				break;
				case 'Size':
					if(is_integer($value) === false){
						throw new RuntimeException('Size must be specified as an integer.');
					}else if($value < 0){
						throw new RuntimeException('Size must be greater than or equal to zero.');
					}
				break;
				case 'defaultValue':
					if(is_string($value) === false && $value !== null){
						throw new RuntimeException('defaultValue must be string or NULL');
					}
				break;
				case 'isNull':
				case 'unsigned':
				case 'auto_increment':
					if(is_bool($value) === false){
						throw new RuntimeException($name . ' must be specified as a boolean.');
					}
				break;
				default:
					throw new RuntimeException($name . ' is not a supported property.');
				break;
			}
			$this->$name = $value;
		}

//!	Compares two ColumnTypeDef objects
		public function Equals(ColumnTypeDef $other=null){
			return (
				isset($other)          === true                  &&
				$other->Type           === $this->Type           &&
				$other->Size           === $this->Size           &&
				$other->defaultValue   === $this->defaultValue   &&
				$other->isNull         === $this->isNull         &&
				$other->unsigned       === $this->unsigned       &&
				$other->auto_increment === $this->auto_increment
			);
		}

	}


	class ColumnDefinition{

		const regex_fieldName = '/^[A-z][A-z0-9_]*$/';

//!	string name of the field.
//!	We're making this private and wrapping it via the __get() and __set() magic methods as a proxy to prevent runtime code from altering the property itself (e.g. replacing it with another type).
		private $Name;

//!	object an instance of Aurora::Framework::ColumnTypeDef
//!	We're making this private and wrapping it via the __get() and __set() magic methods as a proxy to prevent runtime code from altering the property itself (e.g. replacing it with another type).
//!	@see Aurora::Framework::ColumnTypeDef
		private $Type;

//!	we're providing a public constructor to mimic usage in c#
		public function __construct($name, array $type=null){
			$this->__set('Name', $name);
			$this->Type = new ColumnTypeDef($type=null);
		}


//!	magic method used to act as a proxy to the private properties
/**
*	@see Aurora::Framework::ColumnDefinition::$Name
*	@see Aurora::Framework::ColumnDefinition::$Type
*/
		public function __get($name){
			return ($name === 'Name' || $name === 'Type') ? $this->$name : null;
		}

//!	magic method used to act as a proxy to the private properties
		public function __set($name, $value){
			if($name === 'Name'){
				if(is_string($value) === false){
					throw new RuntimeException('Name must be specified as string.');
				}else if(preg_match(static::regex_fieldName, $value) != 1){
					throw new RuntimeException('Field name was not valid.');
				}
			}
			throw new RuntimeException($name . ' is an unsupported property.');
		}
	}
}


namespace Aurora\Framework\ColumnDefinition{

	use ArrayObject;


	abstract class abstractIterator extends ArrayObject{


		public function __construct(array $values=null){
			if(isset($values) === true){
				foreach($values as $v){
					$this[] = $v;
				}
			}
			parent::__construct(null, \ArrayObject::STD_PROP_LIST);
		}
	}


	class Iterator extends abstractIterator{

//!	Restricts values to instances of Aurora::Framework::ColumnDefinition
		public function offsetSet($offset, $value){
			if($value instanceof \Aurora\Framework\ColumnDefinition){
				parent::offsetSet($offset, $value);
			}else{
				throw new InvalidArgumentException('Values must be instances of ColumnDefinition.');
			}
		}
	}
}
?>
