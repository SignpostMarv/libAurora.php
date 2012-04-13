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

	use Countable;

	use libAurora\abstractIterator;

	use Aurora\Framework\QueryFilter\scalarFilter;
	use Aurora\Framework\QueryFilter\scalarMultiFilter;
	use Aurora\Framework\QueryFilter\stringFilter;
	use Aurora\Framework\QueryFilter\stringMultiFilter;
	use Aurora\Framework\QueryFilter\unsignedIntegerFilter;
	use Aurora\Framework\QueryFilter\integerFilter;
	use Aurora\Framework\QueryFilter\nullFilter;

//! Implementation of QueryFilter class from Aurora-Sim
	class QueryFilter implements Countable{

		private $andFilters;
		private $orFilters;
		private $orMultiFilters;

		private $andLikeFilters;
		private $orLikeFilters;
		private $orLikeMultiFilters;

		private $andBitfieldAndFilters;
		private $orBitfieldAndFilters;

		private $andBitfieldNandFilters;

		private $andGreaterThanFilters;
		private $orGreaterThanFilters;

		private $andGreaterThanEqFilters;
		private $orGreaterThanEqFilters;

		private $andLessThanFilters;
		private $orLessThanFilters;

		private $andLessThanEqFilters;

		private $andNotFilters;

		private $andIsNullFilters;
		private $andIsNotNullFilters;

//!	To retain similar instantiation syntax, we're not hiding this behind a factory/registry method
		public function __construct(){
			$this->andFilters              = new scalarFilter;
			$this->orFilters               = new scalarFilter;
			$this->orMultiFilters          = new scalarMultiFilter;

			$this->andLikeFilters          = new stringFilter;
			$this->orLikeFilters           = new stringFilter;
			$this->orLikeMultiFilters      = new stringMultiFilter;

			$this->andBitfieldAndFilters   = new unsignedIntegerFilter;
			$this->orBitfieldAndFilters    = new unsignedIntegerFilter;

			$this->andBitfieldNandFilters  = new unsignedIntegerFilter;

			$this->andGreaterThanFilters   = new integerFilter;
			$this->orGreaterThanFilters    = new integerFilter;

			$this->andGreaterThanEqFilters = new integerFilter;
			$this->orGreaterThanEqFilters  = new integerFilter;

			$this->andLessThanFilters      = new integerFilter;
			$this->orLessThanFilters       = new integerFilter;

			$this->andLessThanEqFilters    = new integerFilter;

			$this->andNotFilters           = new scalarFilter;

			$this->andIsNullFilters        = new nullFilter;
			$this->andIsNotNullFilters     = new nullFilter;
		}

//!	Since PHP doesn't support setters & getters for properties, we need to use the __get() magic method
		public function __get($name){
			switch($name){
				case 'andFilters':
				case 'orFilters':
				case 'orMultiFilters':

				case 'andLikeFilters':
				case 'orLikeFilters':
				case 'orLikeMultiFilters':

				case 'andBitfieldAndFilters':
				case 'orBitfieldAndFilters':

				case 'andBitfieldNandFilters':

				case 'andGreaterThanFilters':
				case 'orGreaterThanFilters':

				case 'andGreaterThanEqFilters':
				case 'orGreaterThanEqFilters':

				case 'andLessThanFilters':
				case 'orLessThanFilters':

				case 'andLessThanEqFilters':

				case 'andNotFilters':

				case 'andIsNullFilters':
				case 'andIsNotNullFilters':

					return $this->$name;
				break;
				default:
					return null;
				break;
			}
		}

//!	Count is mainly used to check if there are any filters being used on the instance, so other code knows whether to act on the instance or not. Possibly just an artefact of sub-filter support.
		public function count(){
			return
				$this->andFilters->count()              +
				$this->orFilters->count()               +
				$this->orMultiFilters->count()          +

				$this->andLikeFilters->count()          +
				$this->orLikeFilters->count()           +
				$this->orLikeMultiFilters->count()      +

				$this->andBitfieldAndFilters->count()   +
				$this->orBitfieldAndFilters->count()    +

				$this->andGreaterThanFilters->count()   +
				$this->orGreaterThanFilters->count()    +

				$this->andGreaterThanEqFilters->count() +
				$this->orGreaterThanEqFilters->count()  +

				$this->andLessThanFilters->count()      +
				$this->orLessThanFilters->count()       +

				$this->andLessThanEqFilters->count()    +

				$this->andNotFilters->count()           +

				$this->andIsNullFilters->count()        +
				$this->andIsNotNullFilters->count()     +

			0;
		}

//!	strips special characters from keys
/**
*	@param string $key a key intended to used in a prepared SQL statement
*	@return string $key sans any special characters that would invalidate a prepared statement
*/
		public static function preparedKey($key){
			if(is_string($key) === false){
				throw new InvalidArgumentException('key msut be specified as string.');
			}

			return str_replace(array(
					'`',  // used to escape reserved SQL words that're used as field names
					')',  // closing parenthesis can be ignored
					'\'', // ' is a character that often needs escaping in SQL queries, so we just strip it from the prepared key
					',',  // , is not a valid prepared key character but can be used in SQL functions
				), '', // remove the character entirely
				str_replace(array(
						'(', // opening parenthesis gets used to differentiate this key from a similar one, so we need to replace it with a safe character rather than removing it entirely
						' ', // can't have whitespace in a prepared key
					), '_', // a safe character for prepared keys
					str_replace(array(
							'-', // minus symbol is valid SQL operator syntax, but not valid prepared key character
							'+', // add symbol is valid SQL operator syntax, but not valid prepared key character
							'/', // divide symbol is valid SQL operator syntax, but not valid prepared key character
							'*', // multiply symbol is valid SQL operator syntax, but not valid prepared key character
						), array(
							'minus', // we replace the minus symbol with the word "minus"
							'add', // we replace the add symbol with the word "add"
							'divide', // we replace the divide symbol with the word "divide"
							'multiply', // we replace the multiply symbol with the word "multiply"
						), $key
					)
				)
			);
		}

//!	Uses the filters on the instance to generate a string to be used with a WHERE statement in an SQL query
/**
*	The original c# supports a prefix character argument that is unneccessary in PHP
*	@param array $ps array of prepared statement keys and values
*	@return string SQL query
*/
		public function ToSQL(array & $ps){
			$ps = array();
			if($this->count() <= 0){
				return '';
			}

			$query = '';
			$parts = array();
			$i = 0;
			$had = false;

#region Equality
			$parts = array();
			foreach($this->andFilters as $key=>$value){
				$_key = ':where_AND_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s = %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->orFilters as $key=>$value){
				$_key = ':where_OR_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s = %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' OR ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->orMultiFilters as $key=>$args){
				foreach($args as $value){
					$_key = ':where_OR_' . (string)(++$i) . static::preparedKey($key);
					$ps[$_key] = $value;
					$parts[] = sprintf('%s = %s', $key, $_key);
				}
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' OR ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->andNotFilters as $key=>$value){
				$_key = ':where_AND_NOT_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s != %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

#endregion

#region LIKE
			$parts = array();
			foreach($this->andLikeFilters as $key=>$value){
				$_key = ':where_ANDLIKE_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s LIKE %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->orLikeFilters as $key=>$value){
				$_key = ':where_ORLIKE_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s LIKE %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' OR ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->orLikeMultiFilters as $key=>$args){
				foreach($args as $value){
					$_key = ':where_ORLIKE_' . (string)(++$i) . static::preparedKey($key);
					$ps[$_key] = $value;
					$parts[] = sprintf('%s LIKE %s', $key, $_key);
				}
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' OR ', $parts) . ')';
				$had = true;
			}

#endregion

#region bitfield &
			$parts = array();
			foreach($this->andBitfieldAndFilters as $key=>$value){
				$_key = ':where_bAND_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s & %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->orBitfieldAndFilters as $key=>$value){
				$_key = ':where_bOR_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s & %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' OR ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->andBitfieldNandFilters as $key=>$value){
				$_key = ':where_bNAND_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s & %s = 0', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

#endregion

#region greater than

			$parts = array();
			foreach($this->andGreaterThanFilters as $key=>$value){
				$_key = ':where_gtAND_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s > %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->orGreaterThanFilters as $key=>$value){
				$_key = ':where_gtOR_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s > %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' OR ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->andGreaterThanEqFilters as $key=>$value){
				$_key = ':where_gteqAND_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s >= %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->orGreaterThanEqFilters as $key=>$value){
				$_key = ':where_gteqOR_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s >= %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' OR ', $parts) . ')';
				$had = true;
			}

#endregion

#region less than

			$parts = array();
			foreach($this->andLessThanFilters as $key=>$value){
				$_key = ':where_ltAND_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s < %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->orLessThanFilters as $key=>$value){
				$_key = ':where_ltOR_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s < %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' OR ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->andLessThanEqFilters as $key=>$value){
				$_key = ':where_lteqAND_' . (string)(++$i) . static::preparedKey($key);
				$ps[$_key] = $value;
				$parts[] = sprintf('%s <= %s', $key, $_key);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

#endregion

#region NULL

			$parts = array();
			foreach($this->andIsNotNullFilters as $value){
				$parts[] = sprintf('%s IS NOT NULL', $value);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

			$parts = array();
			foreach($this->andIsNullFilters as $value){
				$parts[] = sprintf('%s IS NULL', $value);
			}
			if(count($parts) > 0){
				$query .= ($had ? ' AND' : '') . ' (' . implode(' AND ', $parts) . ')';
				$had = true;
			}

#endregion

			return $query;
		}

	}
}


namespace Aurora\Framework\QueryFilter{

	use ArrayAccess;

	use libAurora\abstractIteratorArrayAccess;

	use Aurora\Framework\InvalidArgumentException;

//!	Aurora::Framework::Filter requires a bunch of different filters to compile the WHERE statement in an SQL query
	abstract class Filter extends abstractIteratorArrayAccess implements ArrayAccess{

//!	To save duplicating code, we have Aurora::Framework::QueryFilter::Filter::offsetSet() handle the task of validating the offset.
		public function offsetSet($offset, $value){
			if(is_string($offset) === false){
				throw new InvalidArgumentException('Offset should be specified as string.');
			}
			$this->data[$offset] = $value;
		}
	}

//!	Filter for scalar values
	class scalarFilter extends Filter{

//!	Ensures values are scalar.
		public function offsetSet($offset, $value){
			if(is_scalar($value) === false){
				throw new InvalidArgumentException('Value was not scalar.');
			}
			parent::offsetSet($offset, $value);
		}
	}

//! Filter for string values
	class stringFilter extends Filter{

//!	Ensures values are strings
		public function offsetSet($offset, $value){
			if(is_string($value) === false){
				throw new InvalidArgumentException('Value was not an string.');
			}
			parent::offsetSet($offset, $value);
		}
	}

//!	Filter for integer values
	class integerFilter extends Filter{

//!	Ensures values are integers, auto-casting integer-as-string and floats.
		public function offsetSet($offset, $value){
			if((is_string($value) === true && ctype_digit($value) === true) || (is_float($value) === true && $value % 1 === 0)){
				$value = (integer)$value;
			}

			if(is_integer($value) === false){
				throw new InvalidArgumentException('Value was not an integer.');
			}
			parent::offsetSet($offset, $value);
		}
	}

//!	Filter for unsigned integer values
	class unsignedIntegerFilter extends Filter{

//!	Ensures values are integers, auto-casting integer-as-string and floats. Since PHP does not support unsigned integers natively, we just do a less-than check.
		public function offsetSet($offset, $value){
			if((is_string($value) === true && ctype_digit($value) === true) || (is_float($value) === true && $value % 1 === 0)){
				$value = (integer)$value;
			}

			if(is_integer($value) === false){
				throw new InvalidArgumentException('Value was not an integer.');
			}else if($value < 0){
				throw new InvalidArgumentException('Value should be equal to or greater than zero.');
			}
			parent::offsetSet($offset, $value);
		}
	}

//!	Filter for null value checking
	class nullFilter extends Filter{

//!	Since the c# is just List<string>, we need to override Aurora::Framework::QueryFilter::Filter::offsetSet() to accept only null arguments for $offset. We also check if the value was already added.
		public function offsetSet($offset, $value){
			if(is_null($offset) === false){
				throw new InvalidArgumentException('Offset should not be specified with Aurora::Framework::QueryFilter::nullFilter::offsetSet()');
			}else if(is_string($value) === false){
				throw new InvalidArgumentException('Value should be specified as string.');
			}else if(ctype_graph($value) === false){
				throw new InvalidArgumentException('Value must not contain whitespace');
			}
			$value = trim($value);
			if(in_array($value, $this->data) === false){
				$this->data[] = $value;
			}
		}
	}

//!	abstract filter for multiple values
	abstract class multiFilter extends Filter{

//!	To save duplicating code, we have Aurora::Framework::QueryFilter::multiFilter::offsetSet() handle the task of validating the offset.
		public function offsetSet($offset, $value){
			if(is_string($offset) === false){
				throw new InvalidArgumentException('Offset should be specified as string.');
			}else if($this->offsetSet($offset) === false){
				$this->data[$offset] = array();
			}
			$this->data[$offset][] = $value;
		}
	}

//!	Filter for multiple scalar values
	class scalarMultiFilter extends multiFilter{

//!	Ensures values are scalar.
		public function offsetSet($offset, $value){
			if(is_scalar($value) === false){
				throw new InvalidArgumentException('Value was not scalar.');
			}
			parent::offsetSet($offset, $value);
		}
	}

//!	Filter for multiple string values
	class stringMultiFilter extends multiFilter{

//!	Ensures values are strings.
		public function offsetSet($offset, $value){
			if(is_string($value) === false){
				throw new InvalidArgumentException('Value was not string.');
			}
			parent::offsetSet($offset, $value);
		}
	}
}
?>
