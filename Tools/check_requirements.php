<?php

define( 'IPB_VERSION', '3.2' );

?>
<!DOCTYPE html 
	     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>IP.Board <?php print IPB_VERSION; ?> - Requirements Checker</title>
	<style type='text/css'>
		.pass {
			color: green;
			font-weight: bold;
		}
		
		.fail {
			color: red;
			font-weight: bold;
		}
		
		.warn {
			color: orange;
			font-weight: bold;
		}
		
		.what-you-should-do {
			color: grey;
			font-size: 12px;
			font-style: italic;
			padding-left: 15px;
		}
		
		h1 {
			border-bottom: 1px solid black;
		}
		
		p {
			margin: 2px;
			padding: 4px;
			border-bottom: 1px dotted grey;
			font-size: 15px;
			font-family: "Arial, Verdana";
		}
		
		p.final {
			border-bottom: 0px;
			padding-top: 20px;
		}
	
	</style>
	
</head>
<body>

	<h1>IP.Board <?php print IPB_VERSION; ?> Requirements Checker</h1>

<?php

$extensions	= get_loaded_extensions();
$version	= phpversion();
$minVersion	= '5.2.0';

//-----------------------------------------
// Checking against required version
//-----------------------------------------

$checkRequiredVersion	= version_compare( $minVersion, $version, '<=' );
flushOutput( "Checking minimum version ({$minVersion})...", $checkRequiredVersion, "You must be running PHP v{$minVersion} or greater to use IP.Board " . IPB_VERSION . ".  You are currently running version {$version}.  Please ask your host to move you to a server running PHP v{$minVersion} or greater." );

print "<p>You are running PHP <strong>{$version}</strong></p>";

//-----------------------------------------
// Check for zend guard
//-----------------------------------------

$hasZend = false;

if ( function_exists( 'zend_loader_enabled' ) )
{
	if ( @zend_loader_enabled() === true )
	{
		$hasZend = true;
	}
}

flushOutput( "IP.Nexus Only: Checking for ability to load Zend Guard encoded files...", $hasZend, "Not able to run encoded files such as IP.Nexus. You can still use IP.Board, IP.Downloads. IP.Blog, IP.Gallery, IP.Content, IP.Chat" );

//-----------------------------------------
// Memory limit
//-----------------------------------------

$_memLimit	= null;
$_recLimit	= 128;

if( @ini_get('memory_limit') )
{
	$_memLimit	= @ini_get('memory_limit');
}

if( $_memLimit )
{
	$_intLimit	= $_memLimit;
	$_intRec	= $_recLimit * 1024 * 1024;
	
	preg_match( "#^(\d+)(\w+)$#", strtolower($_intLimit), $match );
	
	if( $match[2] == 'g' )
	{
		$_intLimit = intval( $_intLimit ) * 1024 * 1024 * 1024;
	}
	else if ( $match[2] == 'm' )
	{
		$_intLimit = intval( $_intLimit ) * 1024 * 1024;
	}
	else if ( $match[2] == 'k' )
	{
		$_intLimit = intval( $_intLimit ) * 1024;
	}
	else
	{
		$_intLimit = intval( $_intLimit );
	}
	
	if( $_intLimit >= $_intRec )
	{
		flushOutput( "Checking memory limit ({$_recLimit}M or better recommended)...", true, "Your memory limit: {$_memLimit}" );
	}
	else
	{
		flushOutput( "Checking memory limit ({$_recLimit}M or better recommended)...", 2, "Your memory limit: {$_memLimit}. You can still proceed but we recommend you contact your host and request the memory limit be raised to {$_recLimit}M to prevent possible issues." );
	}
}
else
{
	flushOutput( "Checking memory limit ({$_recLimit}M or better recommended)...", 2, "Could not determine your memory limit" );
}

//-----------------------------------------
// Checking for SPL
//-----------------------------------------

$splExists	= in_array( 'SPL', $extensions );
flushOutput( "Checking for SPL...", $splExists, "The PHP SPL extension is required for IP.Board " . IPB_VERSION . ".  Please ask your host to install the <a href='http://www.php.net/manual/en/spl.setup.php'>SPL library</a>." );

//-----------------------------------------
// Checking for DOM XML
//-----------------------------------------

$domXML	= in_array( 'dom', $extensions );
flushOutput( "Checking for DOM XML Handling...", $domXML, "The DOM XML Handling extension is required for IP.Board " . IPB_VERSION . ".  Please ask your host to install the <a href='http://www.php.net/manual/en/dom.setup.php'>libxml2 library</a>." );

//-----------------------------------------
// Check for GD2
//-----------------------------------------

$gd2	= in_array( 'gd', $extensions );
flushOutput( "Checking for GD library...", $gd2, "The GD2 library is required for IP.Board " . IPB_VERSION . ".  Please ask your host to install the <a href='http://us.php.net/manual/en/image.setup.php'>libgd library</a>." );

if( function_exists( 'gd_info' ) )
{
	$gdInfo	= gd_info();
	$fail	= true;
	
	if( $gdInfo["GD Version"] )
	{
		preg_match( "/.*?([\d\.]+).*?/", $gdInfo["GD Version"], $matches );
		
		if( $matches[1] )
		{
			$compareVersions	= version_compare( '2.0', $matches[1], '<=' );
			
			if( !$compareVersions )
			{
				$fail = 2;
			}
		}
	}

	flushOutput( "Checking for GD2...", $fail, "While the GD library is installed, GD library version 2 is required for IP.Board " . IPB_VERSION . ".  The version reported (if at all) by your server is '{$gdInfo['GD Version']}'.  Please verify you are running GD version 2, and upgrade the <a href='http://us.php.net/manual/en/image.setup.php'>libgd library</a> if necessary." );
}

//-----------------------------------------
// Checking for mysql extension
//-----------------------------------------

$mysql	= in_array( 'mysql', $extensions );
$mysqli	= in_array( 'mysqli', $extensions );
flushOutput( "Checking for mysql support...", ( $mysql OR $mysqli ), "Your server does not appear to have a mysql library available, please ask your host to install the <a href='http://us.php.net/manual/en/mysqli.setup.php'>mysqli extension</a>." );

if( $mysql AND !$mysqli )
{
	flushOutput( "Checking for mysqli support...", false, "The mysqli extension is preferred over the original mysql extension.  This is not a requirement, but if possible we recommend you to install the <a href='http://us.php.net/manual/en/mysqli.setup.php'>mysqli extension</a> for better performance." );
}


//-----------------------------------------
// Checking for JSON
//-----------------------------------------

$json	= in_array( 'json', $extensions );
flushOutput( "Checking for JSON...", $json, "While not required, the PHP JSON extension is recommended.  The JSON extensions provides for improved efficiency in handling some functionality in IP.Board.  If possible, we recommend you to install the <a href='http://us.php.net/manual/en/json.setup.php'>json extension</a>." );

//-----------------------------------------
// Checking for openSSL
//-----------------------------------------

$openssl	= in_array( 'openssl', $extensions ) ? true : 2;
flushOutput( "Checking for openSSL...", $openssl, "OpenSSL is required for some Facebook, Twitter, and Subscription Manager functionality.  If you do not use any of these modules, you do not need this module, otherwise we recommend you to install the <a href='http://us2.php.net/manual/en/openssl.installation.php'>openssl extension</a>." );

//-----------------------------------------
// Suhosin
//-----------------------------------------

if( extension_loaded( 'suhosin' ) )
{
	$_postMaxVars	= @ini_get('suhosin.post.max_vars');
	$_reqMaxVars	= @ini_get('suhosin.request.max_vars');
	$_postMaxLen	= @ini_get('suhosin.post.max_value_length');
	$_reqMaxLen		= @ini_get('suhosin.request.max_value_length');
	$_reqMaxVar		= @ini_get('suhosin.request.max_varname_length');
	
	$_indPMV		= $_postMaxVars < 4096 ? 2 : true;
	$_indRMV		= $_reqMaxVars < 4096 ? 2 : true;
	$_indPML		= $_postMaxLen < 1000000 ? 2 : true;
	$_indRML		= $_reqMaxLen < 1000000 ? 2 : true;
	$_indRMVL		= $_reqMaxVar < 350 ? 2 : true;
	
	flushOutput( "suhosin.post.max_vars (4096 or better recommended)...", $_indPMV, "Your value: {$_postMaxVars}. Can prevent some forms (especially in the ACP) from saving properly." );
	flushOutput( "suhosin.request.max_vars (4096 or better recommended)...", $_indRMV, "Your value: {$_reqMaxVars}. Can prevent some forms (especially in the ACP) from saving properly." );
	flushOutput( "suhosin.post.max_value_length (1000000 or better recommended)...", $_indPML, "Your value: {$_postMaxLen}. Can prevent very large posts or other form submissions from saving properly." );
	flushOutput( "suhosin.request.max_value_length (1000000 or better recommended)...", $_indRML, "Your value: {$_reqMaxLen}. Can prevent very large posts or other form submissions from saving properly." );
	flushOutput( "suhosin.request.max_varname_length (350 or better recommended)...", $_indRMVL, "Your value: {$_reqMaxVar}. Can prevent long friendly URLs from loading correctly." );
}

//-----------------------------------------
// Flush output function
//-----------------------------------------

function flushOutput( $checking, $result, $errorText )
{
	print "<p>" . $checking;
	
	if( $result === 2 )
	{
		print "<span class='warn'>WARNING</span><br /><span class='what-you-should-do'>{$errorText}</span>";
	}
	else if( !$result )
	{
		print "<span class='fail'>FAIL</span><br /><span class='what-you-should-do'>{$errorText}</span>";
	}
	else
	{
		print "<span class='pass'>Pass</span>";
	}
	
	print "</p>";
	
	flush();
	ob_flush();
	
	return true;
}

?>

	<p class='final'>Please also remember that MySQL 4.1 or higher (MySQL 5.0 or higher preferred) is required for IP.Board <?php print IPB_VERSION; ?>.  Ask your server administrator to check the version of MySQL on your server if you are unsure.</p>
</body>
</html>