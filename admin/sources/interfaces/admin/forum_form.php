<?php
/**
 * @file		forum_form.php 	Forum editing form interface
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		16 Dec 2011
 * $LastChangedDate: 2011-12-16 18:58:20 -0500 (Fri, 16 Dec 2011) $
 * @version		v3.3.3
 * $Revision: 10016 $
 */

/**
 * @interface	admin_forum_form
 * @brief		Forum editing form interface
 */
interface admin_forum_form
{
	/**
	 * Returns HTML tabs content for the page
	 *
	 * @param	array		$forum		Forum data
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $forum=array(), $tabsUsed=1 );
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave();
}