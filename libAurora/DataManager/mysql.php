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
				print_r($e);exit;
			}

			return in_array($table, $retVal);
		}
	}
}
?>
