<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Facebook channel file
 * Last Updated: $Date: 2012-01-31 11:24:24 +0000 (Tue, 31 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2008 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10219 $
 *
 */

/**
 * @link http://developers.facebook.com/docs/reference/javascript/#auth-methods
 */
 
$cache_expire = 60*60*24*365;
header("Pragma: public");
header("Cache-Control: max-age=".$cache_expire);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$cache_expire) . ' GMT');
?>
<script src="//connect.facebook.net/en_US/all.js"></script>