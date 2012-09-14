<?php
/**
 * @file		defaultSection.php 	Define the default section for the 'albums' module
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-05-26 13:03:06 -0400 (Thu, 26 May 2011) $
 * @version		v4.2.1
 * $Revision: 8902 $
 */

$DEFAULT_SECTION = empty(ipsRegistry::$settings['gallery_default_view']) ? 'home' : ipsRegistry::$settings['gallery_default_view'];
