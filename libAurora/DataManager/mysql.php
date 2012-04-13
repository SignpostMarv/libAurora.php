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
	use Aurora\Framework\IndexType;
	use Aurora\Framework\ColumnType;
	use Aurora\Framework\ColumnTypeDef;

	use Aurora\DataManager\Migration\ColumnDefinition\Iterator as ColDefs;
	use Aurora\DataManager\Migration\IndexDefinition\Iterator as IndexDefs;

//!	mysql-specific PDO implementation of Aurora::Framework::IDataConnector
	class MySQLDataLoader extends PDO{

//!	Name of the connector
		const Identifier = 'MySQLData';


		public function TableExists($table){
			parent::TableExists($table);

			$retVal = array();

			try{
				$sth = null;
				static::prepareSth($this->PDO, $sth, 'SHOW TABLES');
				$sth->execute();
				$parts = $sth->fetchAll(\PDO::FETCH_NUM);
				foreach($parts as $v){
					$retVal = array_merge($retVal, $v);
				}
			}catch(PDOException $e){
			}

			return in_array($table, $retVal);
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
			if($primary != null & $primary->Fields->count() > 0){
				$columnDefinition[] = 'PRIMARY KEY (`' . implode('`, ', $primary->Fields->getArrayCopy()) . '`)';
			}

			$indicesQuery = array();
			foreach($indices as $index){
				$type = 'KEY';
				switch($index->Type){
					case IndexType::Primary:
						continue;
					break;
					case IndexType::Unique:
						$type = 'UNIQUE';
					break;
					case IndexType::Index:
					default:
						$type = 'KEY';
					break;
				}
				$indicesQuery[] = sprintf('%s( %s )', $type, '`' . implode('`, ', $index->Fields->getArrayCopy()));
			}

			$query = sprintf('CREATE TABLE ' . $table . ' ( %s %s) ', implode(', ', $columnDefinition), count($indicesQuery) > 0 ? ', ' . implode(', ', $indicesQuery) : '');

			try{
				$this->PDO->exec($query);
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
					if($column->Equals($oldColumn) === true){
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
					if($column->Equals($newColumn) === true){
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
					$addedColumnsQuery = 'add `' . $column->Name . '` ' . $this->GetColumnTypeStringSymbol($column->Type) . ' ';
					$query             = 'alter table ' . $table . ' ' . $addedColumnsQuery;
					$this->PDO->exec($query);
				}
				foreach($modifiedColumns as $column){
					$modifiedColumnsQuery = 'modify column `' . $column->Name . '` ' . GetColumnTypeStringSymbol($column->Type) . ' ';
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

            return $symbol . ($coldef->isNull ? ' NULL' : ' NOT NULL') + (($coldef->isNull && $coldef->defaultValue == null) ? ' DEFAULT NULL' : ($coldef->defaultValue != null ? ' DEFAULT \'' . mysql_real_escape_string($coldef->defaultValue) . '\'' : '')) . (($coldef->Type == ColumnType::Integer || $coldef->Type == ColumnType::TinyInt) && $coldef->auto_increment ? ' AUTO_INCREMENT' : '');
        }


		protected function ForceRenameTable($oldTableName, $newTableName){
			parent::ForceRenameTable($oldTableName, $newTableName);

			try{
				$this->PDO->exec(sprintf('RENAME TABLE %s TO %s', $oldTableName, $newTableName));
			}catch(PDOException $e){
				throw new RuntimeException('Failed to rename table.', $e->getCode());
			}
		}
	}
}
?>
