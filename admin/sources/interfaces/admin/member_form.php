<?php
/**
 * @file		member_form.php 	Member form plugin interface
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
 * @interface	admin_member_form
 * @brief		Member form plugin interface
 *
 */
interface admin_member_form
{
	/**
	 * Returns sidebar links for a tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the sidebar for this block.
	 *
	 * The links must have 'section=xxxxx&module=xxxxx[&do=xxxxxx]'. The rest of the URL
	 * is added automatically. member_id will contain the Member ID
	 *
	 * The image must be a full URL or blank to use a default image.
	 *
	 * Use the format:
	 * $array[] = array( 'img' => '', 'url' => '', 'title' => '' );
	 *
	 * @param	array	$member		Member data
	 * @return	@e array Array of links
	 */
	public function getSidebarLinks( $member=array() );
	
	/**
	 * Returns HTML tabs content for the page
	 *
	 * @param	array		$member		Member data
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content)
	 */
	public function getDisplayContent( $member=array() );
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Multi-dimensional array (core, extendedProfile) for saving
	 */
	public function getForSave();
}