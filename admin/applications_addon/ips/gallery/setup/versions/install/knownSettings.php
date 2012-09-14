<?php
/*
 * Returns known settings
 *
 * No, really it does!
 *
 * Remember: ipsRegistry::$settings probably won't be available.
 *
 *
 * IPSSetUp::getSavedData('admin_email')
 * IPSSetUp::getSavedData('install_dir')   [Example: /home/user/public_html/forums] - No trailing slash supplied
 * IPSSetUp::getSavedData('install_url')   [Example: http://www.domain.tld/forums]  - No trailing slash supplied
 */

$knownSettings = array( 
	 'gallery_images_path'		=> IPSSetUp::getSavedData('install_dir') ? IPSSetUp::getSavedData('install_dir') . '/uploads' : ipsRegistry::$settings['upload_dir'],
	 'gallery_images_url'		=> IPSSetUp::getSavedData('install_url') ? IPSSetUp::getSavedData('install_url') . '/uploads' : ipsRegistry::$settings['upload_url'],
	 'gallery_members_album'    => 1
);