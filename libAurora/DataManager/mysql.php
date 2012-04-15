<?php
//!	@file libAurora/DataManager/mysql.php
//!	@brief mysql-specific PDO implementation of Aurora::Framework::IDataConnector
//!	@author SignpostMarv

namespace libAurora\DataManager{

	use PDOException;
	use libAurora\InvalidArgumentException;
	use libAurora\RuntimeException;

	use PDOStatement;

	use Aurora\Framework\QueryFilter;

	use Aurora\Framework\ColumnType;
	use Aurora\Framework\ColumnTypeDef;
	use Aurora\Framework\ColumnDefinition;
	use Aurora\Framework\ColumnDefinition\Iterator as ColDefs;

	use Aurora\Framework\IndexType;
	use Aurora\Framework\IndexDefinition;
	use Aurora\Framework\IndexDefinition\Iterator as IndexDefs;

//!	mysql-specific PDO implementation of Aurora::Framework::IDataConnector
	class MySQLDataLoader extends PDO{

//!	Name of the connector
		const Identifier = 'MySQLData';


		public function TableExists($table){
			parent::TableExists($table);

			$retVal = array();

			$sth = null;
			static::prepareSth($this->PDO, $sth, 'SHOW TABLES');
			static::returnExecute($sth);

			return in_array($table, static::linearResults($sth));
		}


		public function CreateTable($table, ColDefs $columns, IndexDefs $indices){
			parent::CreateTable($table, $columns, $indices);

			$table = strtolower($table);

			$primary = null;
			foreach($indices as $index){
				if($index->Type == IndexType::Primary){
					$primary = $index;
					break;
				}
			}

			$columnDefinition = array();

			foreach($columns as $column){
				$columnDefinition[] = '`' . $column->Name . '` ' . static::GetColumnTypeStringSymbol($column->Type);
			}
			if($primary != null && $primary->Fields->count() > 0){
				$columnDefinition[] = 'PRIMARY KEY (`' . implode('`, `', $primary->Fields->getArrayCopy()) . '`)';
			}

			$indicesQuery = array();
			foreach($indices as $index){
				$type = false;
				switch($index->Type){
					case IndexType::Primary:
						continue 2;
					break;
					case IndexType::Unique:
						$type = 'UNIQUE';
					break;
					case IndexType::Index:
					default:
						$type = 'KEY';
					break;
				}
				$indicesQuery[] = sprintf('%s( %s )', $type, '`' . implode('`, `', $index->Fields->getArrayCopy()) . '`');
			}

			$query = sprintf('CREATE TABLE ' . $table . ' ( %s %s) ', implode(', ', $columnDefinition), count($indicesQuery) > 0 ? ', ' . implode(', ', $indicesQuery) : '');

			try{
				if($this->PDO->exec($query) === false){
					throw new RuntimeException('Failed to create table but did not throw an exception.');
				}
			}catch(PDOException $e){
				throw new RuntimeException('Failed to create table.', $e->getCode());
			}
		}


		public function UpdateTable($table, ColDefs $columns, IndexDefs $indices, array $renameColumns){
			parent::UpdateTable($table, $columns, $indices, $renameColumns);

			$table = strtolower($table);
			if($this->TableExists($table) === false){
				throw new RuntimeException('Trying to update a table with name of one that does not exist.');
			}

			$oldColumns = $this->ExtractColumnsFromTable($table);

			$removedColums   = array();
			$modifiedColumns = array();
			$addedColumns    = array();

			foreach($columns as $column){
				$isOld = false;
				foreach($oldColumns as $oldColumn){
					if($column->Type->Equals($oldColumn->Type) === true){
						$isOld = true;
						break;
					}
				}
				if($isOld === false){
					$addedColumns[strtolower($column->Name)] = $column;
				}
			}

			foreach($oldColumns as $column){
				$isNew = false;
				foreach($columns as $newColumn){
					if($column->Type->Equals($newColumn->Type) === true){
						$isNew = true;
						break;
					}
				}
				if($isNew === false){
					if(isset($addedColumns[strtolower($column->Name)]) === true){
						if(strtolower($column->Name) !== strtolower($addedColumns[strtolower($column->Name)]->Name) || $column->Type !== $addedColumns[strtolower($column->Name)]->Type){
							$modifiedColumns[strtolower($column->Name)] = $addedColumns[strtolower($column->Name)];
						}
						unset($addedColumns[strtolower($column->Name)]);
					}else{
						$removedColums[strtolower($column->Name)] = $column;
					}
				}
			}

			try{
				foreach($addedColumns as $column){
					$addedColumnsQuery = 'add `' . $column->Name . '` ' . static::GetColumnTypeStringSymbol($column->Type) . ' ';
					$query             = 'alter table ' . $table . ' ' . $addedColumnsQuery;
					$this->PDO->exec($query);
				}
				foreach($modifiedColumns as $column){
					$modifiedColumnsQuery = 'modify column `' . $column->Name . '` ' . static::GetColumnTypeStringSymbol($column->Type) . ' ';
					$query                = 'alter table ' . $table . ' ' . $modifiedColumnsQuery;
					$this->PDO->exec($query);
				}
				foreach($removedColums as $column){
					$droppedColumnsQuery = 'drop `' . $column->Name . '` ';
					$query               = 'alter table ' . $table . ' ' . $droppedColumnsQuery;
					$this->PDO->exec($query);
				}
			}catch(PDOException $e){
				throw new RuntimeException('Failed to update table.', $e->getCode());
			}

			$oldIndicesDict = $this->ExtractIndicesFromTable($table);

			$removeIndices  = array();
			$oldIndexNames  = array();
			$oldIndices     = array();
			$newIndices     = array();

			foreach($oldIndicesDict as $k=>$v){
				$oldIndexNames[] = $k;
				$oldIndices[]    = $v;
			}
			$i = 0;
			foreach($oldIndices as $oldIndex){
				$found = false;
				foreach($newIndices as $newIndex){
					if($oldIndex->Equals($newIndex) === true){
						$found = true;
						break;
					}
				}
				if($found === false){
					$removeIndices[] = $oldIndexNames[$i];
				}
				++$i;
			}

			foreach($indices as $newIndex){
				$found = false;
				foreach($oldIndices as $oldIndex){
					if($oldIndex->Equals($newIndex) === true){
						$found = true;
						break;
					}
				}
				if($found === false){
					$newIndices[] = $newIndex;
				}
			}

			try{
				foreach($removeIndices as $oldIndex){
					$this->PDO->exec(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $oldIndex));
				}
				foreach($newIndices as $newIndex){
					$this->PDO->exec(sprintf('ALTER TABLE `%s` ADD %s (`%s`)', $table, $newIndex->Type === IndexType::Primary ? 'PRIMARY KEY' : ($newIndex->Type === IndexType::Unique ? 'UNIQUE' : 'INDEX'), implode('`, `', $newIndex->Fields->getArrayCopy())));
				}
			}catch(PDOException $e){

			}
		}

//!	syntax differences aside, this is lifted straight from the c#
        protected static function GetColumnTypeStringSymbol(ColumnTypeDef $coldef){
            $symbol = '';
            switch($coldef->Type){
                case ColumnType::Blob:
                    $symbol = 'BLOB';
				break;
                case ColumnType::LongBlob:
                    $symbol = 'LONGBLOB';
				break;
                case ColumnType::Boolean:
                    $symbol = 'TINYINT(1)';
				break;
                case ColumnType::Char:
                    $symbol = 'CHAR(' . $coldef->Size . ')';
				break;
                case ColumnType::Date:
                    $symbol = 'DATE';
				break;
                case ColumnType::DateTime:
                    $symbol = 'DATETIME';
				break;
                case ColumnType::Double:
                    $symbol = 'DOUBLE';
				break;
                case ColumnType::Float:
                    $symbol = 'FLOAT';
				break;
                case ColumnType::Integer:
                    $symbol = 'INT(' . $coldef->Size . ')' . ($coldef->unsigned ? ' unsigned' : '');
				break;
                case ColumnType::TinyInt:
                    $symbol = 'TINYINT(' . $coldef->Size . ')' . ($coldef->unsigned ? ' unsigned' : '');
				break;
                case ColumnType::String:
                    $symbol = 'VARCHAR(' . $coldef->Size . ')';
				break;
                case ColumnType::Text:
                    $symbol = 'TEXT';
				break;
                case ColumnType::MediumText:
                    $symbol = 'MEDIUMTEXT';
				break;
                case ColumnType::LongText:
                    $symbol = 'LONGTEXT';
				break;
                case ColumnType::UUID:
                    $symbol = 'CHAR(36)';
				break;
                default:
                    throw new InvalidArgumentException("Unknown column type.");
				break;
            }

            return $symbol . ($coldef->isNull ? ' NULL' : ' NOT NULL') . (($coldef->isNull && $coldef->defaultValue == null) ? ' DEFAULT NULL' : ($coldef->defaultValue != null ? ' DEFAULT ' . ($coldef->defaultValue === 'NULL' ? 'NULL' : '\'' . preg_replace('/([\r\n\x00\x1a\\\'"])/', '\\\1', $coldef->defaultValue) . '\'') : '')) . (($coldef->Type == ColumnType::Integer || $coldef->Type == ColumnType::TinyInt) && $coldef->auto_increment ? ' AUTO_INCREMENT' : '');
        }


		protected function ForceRenameTable($oldTableName, $newTableName){
			parent::ForceRenameTable($oldTableName, $newTableName);

			try{
				$this->PDO->exec(sprintf('RENAME TABLE %s TO %s', $oldTableName, $newTableName));
			}catch(PDOException $e){
				throw new RuntimeException('Failed to rename table.', $e->getCode());
			}
		}


		protected function ExtractColumnsFromTable($tableName){
			static::validateArg_table($tableName);

			$defs = new ColDefs;
			$tableName = strtolower($tableName);

			$sth = null;
			static::prepareSth($this->PDO, $sth, 'DESC ' . $tableName);
			static::returnExecute($sth);

			$results = array();
			$parts   = array();
			try{
				$parts = $sth->fetchAll(\PDO::FETCH_NUM);
			}catch(PDOException $e){
				throw new RuntimeException('Failed to fetch query results.');
			}

			foreach($parts as $v){
				$results = array_merge($results, $v);
			}

			if(count($results) % 6 !== 0){
				throw new RuntimeException('MySQL table description should consist of 6 fields per row.');
			}

			$j = count($results);
			for($i=0;$i<$j;$i += 6){
				list($name, $type, $null, $key, $default, $extra) = array_slice($results, $i, 6);

				$column                       = new ColumnDefinition($name);
				$type                         = static::ConvertTypeToColumnType($type);
				$column->Type->Type           = $type->Type;
				if(isset($column->Type->Size) === true){
					$column->Type->Size       = $type->Size;
				}
				$column->Type->unsigned       = $type->unsigned;
				$column->Type->isNull         = $null === 'YES';
				$column->Type->auto_increment = strpos($extra, 'auto_increment') >= 0;
				$column->Type->defaultValue   = $default;

				$defs[] = $column;
			}

			return $defs;
		}


		protected function ExtractIndicesFromTable($tableName){
			static::validateArg_table($tableName);

			$defs          = new IndexDefs;
			$tableName     = strtolower($tableName);
			$indexLookup   = array();
			$indexIsUnique = array();

			$sth = null;
			static::prepareSth($this->PDO, $sth, 'SHOW INDEX IN ' . $tableName);
			static::returnExecute($sth);

			$rdr = static::linearResults($sth);
			if(count($rdr) % 13 !== 0){
				throw new RuntimeException('MySQL index description should consist of 6 fields per row.');
			}

			$j = count($rdr);
			for($i=0;$i<$j;$i+=13){
				list($table, $non_unique, $key_name, $seq_in_index, $column_name, $collation, $cardinality, $sub_part, $packed, $null, $index_type, $comment, $index_comment) = array_slice($rdr, $i, 13);
				$seq_in_index = (integer)$seq_in_index;

				if(isset($indexLookup[$key_name]) === false){
					$indexLookup[$key_name] = array();
				}
				$indexIsUnique[$key_name] = (integer)$non_unique === 0;
				$indexLookup[$key_name][$seq_in_index - 1] = $column_name;
			}

			foreach($indexLookup as $indexKey => $index){
				sort($index);
				$defs[$indexKey] = new IndexDefinition(
					array_values($index),
					$indexIsUnique[$indexKey] ? ($indexKey === 'PRIMARY' ? IndexType::Primary : IndexType::Unique) : IndexType::Index
				);
			}

			return $defs;
		}
	}
}
?>
