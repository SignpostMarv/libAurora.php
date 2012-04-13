<?php
//!	@brief This file loads all the code.
//!	@author SignpostMarv

namespace Aurora{

//!	This interface exists purely to give client code the ability to detect all Aurora-specific exception classes in one go.
//!	The purpose of this behaviour is that instances of Aurora::Exception will be more or less "safe" for public consumption.
	interface Exception{
	}

//!	Aurora-specific RuntimeException
	class RuntimeException extends \RuntimeException implements Exception{
	}

//!	Aurora-specific InvalidArgumentException
	class InvalidArgumentException extends \InvalidArgumentException implements Exception{
	}

//!	Aurora-specific UnexpectedValueException
	class UnexpectedValueException extends \UnexpectedValueException implements Exception{
	}

//!	Aurora-specific LengthException
	class LengthException extends \LengthException implements Exception{
	}

//!	Aurora-specific BadMethodCallException
	class BadMethodCallException extends \BadMethodCallException implements Exception{
	}
}

namespace libAurora{

//!	This interface exists purely to give client code the ability to detect all libAurora-specific exception classes in one go.
//!	The purpose of this behaviour is that instances of libAurora::Exception will be more or less "safe" for public consumption.
	interface Exception{
	}

//!	libAurora-specific RuntimeException
	class RuntimeException extends \RuntimeException implements Exception{
	}

//!	libAurora-specific InvalidArgumentException
	class InvalidArgumentException extends \InvalidArgumentException implements Exception{
	}

//!	libAurora-specific UnexpectedValueException
	class UnexpectedValueException extends \UnexpectedValueException implements Exception{
	}

//!	libAurora-specific LengthException
	class LengthException extends \LengthException implements Exception{
	}

//!	libAurora-specific BadMethodCallException
	class BadMethodCallException extends \BadMethodCallException implements Exception{
	}
}

namespace{
	require_once('Aurora/load.php');
	require_once('libAurora/Version.php');
	require_once('libAurora/DataManager.php');
}
?>