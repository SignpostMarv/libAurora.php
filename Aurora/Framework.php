<?php


namespace Aurora\Framework{

//!	This interface exists purely to give client code the ability to detect all Aurora::Framework-specific exception classes in one go.
//!	The purpose of this behaviour is that instances of Aurora::Framework::Exception will be more or less "safe" for public consumption.
	interface Exception extends \Aurora\Exception{
	}

//!	Aurora::Framework-specific runtime exception
	class RuntimeException extends \Aurora\RuntimeException implements Exception{
	}

//!	Aurora::Framework-specific invalid argument exception
	class InvalidArgumentException extends \Aurora\InvalidArgumentException implements Exception{
	}

//!	Aurora::Framework-specific bad method call exception
	class BadMethodCallException extends \Aurora\BadMethodCallException implements Exception{
	}
}


namespace{
	require_once('Framework/Services/IRegionData.php');
	require_once('Framework/GroupRecord.php');
	require_once('Framework/GroupNoticeData.php');
	require_once('Framework/LandData.php');
	require_once('Framework/EstateSettings.php');
	require_once('Framework/EventData.php');
	require_once('Framework/QueryFilter.php');
	require_once('Framework/IGenericData.php');
	require_once('Framework/IDataConnector.php');
	require_once('Framework/ColumnDefinition.php');
	require_once('Framework/IndexDefinition.php');
}
?>
