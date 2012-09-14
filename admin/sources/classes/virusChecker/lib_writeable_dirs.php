<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Virus scanner: writable directories
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Tue. 17th August 2004
 * @version		$Rev: 10721 $
 *
 */


$WRITEABLE_DIRS = array(
'cache',
'cache/skin_cache',
'cache/lang_cache',
PUBLIC_DIRECTORY . '/style_emoticons',
PUBLIC_DIRECTORY . '/style_images',
PUBLIC_DIRECTORY . '/style_css',
'uploads'
);
