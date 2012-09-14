<?php
/**
 * @file		cp_skin_member_form_blog.php 	IP.Blog member form skin file
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		14th May 2003
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

/**
 *
 * @class		cp_skin_member_form_blog
 * @brief		IP.Blog member form skin file
 */
class cp_skin_member_form_blog
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
	}

/**
 * Main form to edit member settings
 *
 * @param	array		$member		Member data
 * @param	array		$rows		Member blogs data
 * @param	mixed		$tabId		Tab ID
 * @return	@e string	HTML
 */
public function acp_member_form_main( $member, $rows, $tabID ) {
$IPBHTML = "";

$surl = $this->settings['base_acp_url'] . '/' . IPSLib::getAppFolder('blog') . '/blog/skin_cp/';

$IPBHTML .= <<<HTML
<div id='tab_MEMBERS_{$tabID}_content'>
	<table class='ipsTable double_pad'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='50%'>{$this->lang->words['bl_blog']}</th>
			<th width='5%' class='center'>{$this->lang->words['bl_entries']}</th>
			<th width='5%' class='center'>{$this->lang->words['bl_views']}</th>
			<th width='35%'>{$this->lang->words['bl_lastentry']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;
	if ( is_array( $rows ) AND count( $rows ) )
	{
		foreach( $rows as $data )
		{
			$date = $this->registry->getClass('class_localization')->getDate( $data['blog_last_date'], 'SHORT' );
			
			$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td><img src='{$surl}/images/blog.png' alt='+' /></td>
			<td>
				<a href='{$this->settings['_base_url']}app=blog&module=blogs&section=manage&do=editblog&blogid={$data['blog_id']}'><strong>{$data['blog_name']}</strong></a>
				<div class='desctext'>{$data['blog_desc']}</div>
			</td>
			<td class='center'>{$data['blog_num_entries']}</td>
			<td class='center'>{$data['blog_num_views']}</td>
			<td>{$data['blog_last_entryname']}<div class='desctext'>{$date}</a></td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['_base_url']}app=blog&module=blogs&section=manage&do=editblog&blogid={$data['blog_id']}'>{$this->lang->words['bl_editblog']}...</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['_base_url']}app=blog&module=blogs&section=manage&do=do_deleteblog&blogid={$data['blog_id']}");'>{$this->lang->words['bl_deleteblog']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
		}
	}
	else
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6'><em>{$this->lang->words['bl_noblogs']}</em></td>
		</tr>
HTML;
	}
	
$IPBHTML .= <<<HTML
	</table>
</div>

HTML;

return $IPBHTML;
}

/**
 * Tabs for the member form
 *
 * @param	array		$member		Member data
 * @param	mixed		$tabId		Tab ID
 * @return	@e string	HTML
 */
public function acp_member_form_tabs( $member, $tabID ) {

$IPBHTML = "";

$IPBHTML .= "<li id='tab_MEMBERS_{$tabID}'>{$this->lang->words['bl_blogs']}</li>";

return $IPBHTML;
}

}