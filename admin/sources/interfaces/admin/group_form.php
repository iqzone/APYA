<?php
/**
 * @file		group_form.php 	Group editing form interface
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		-
 * $LastChangedDate: 2011-03-11 09:29:56 -0500 (Fri, 11 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 8036 $
 */

/**
 *
 * @interface	admin_group_form
 * @brief		Group editing form interface
 *
 */
interface admin_group_form
{
	/**
	 * Returns HTML tabs content for the page
	 *
	 * @param	array		$group		Group data
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $group=array(), $tabsUsed=2 );
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave();
}