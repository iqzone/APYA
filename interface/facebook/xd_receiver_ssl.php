<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		3.0.0
 * @version		$Rev: 10721 $
 *
 */
 
// cache the xd_receiver
header('Cache-Control: max-age=225065900');
header('Expires:');
header('Pragma:');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>cross domain receiver page</title>
</head>
<body>

<!--
This is a cross domain (XD) receiver page. It needs to be placed on your domain so that the Javascript
  library can communicate within the iframe permission model. Put it here:

  http://www.example.com/xd_receiver.php
-->
 <script
   src="https://ssl.connect.facebook.com/js/api_lib/v0.4/XdCommReceiver.js" 
   type="text/javascript"></script>

</body>
</html>
