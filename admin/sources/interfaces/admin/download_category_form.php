<?php
/**
 * @file		download_category_form.php 	Download category editing form interface
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		02nd March 2011
 * $LastChangedDate: 2011-03-02 13:57:10 -0500 (Wed, 02 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 7941 $
 */

/**
 *
 * @interface	admin_download_category_form
 * @brief		Download category editing form interface
 *
 */
interface admin_download_category_form
{
	/**
	 * Returns HTML tabs content for the page
	 *
	 * @param	array		$category	Download category data
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $category, $tabsUsed );
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave();
}