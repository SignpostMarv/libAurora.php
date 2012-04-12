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

//!	syntax differences aside, this is lifted straight from the c#
        protected static function GetColumnTypeStringSymbol(ColumnTypeDef $coldef){
            $symbol = '';
            switch($coldef->Type)
            {
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
