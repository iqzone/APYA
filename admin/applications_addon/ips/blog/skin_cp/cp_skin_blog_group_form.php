<?php
/**
 * @file		cp_skin_blog_group_form.php 	IP.Blog group form skin file
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
 * @class		cp_skin_blog_group_form
 * @brief		IP.Blog group form skin file
 */
class cp_skin_blog_group_form
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
 * Main form to edit group settings
 *
 * @param	array		$group		Group data
 * @param	mixed		$tabId		Tab ID
 * @return	@e string	HTML
 */
function acp_blog_group_form_main( $group, $tabId ) {

$form							 = array();
$form['g_blog_do_html']          = $this->registry->output->formYesNo( "g_blog_do_html"        , $group['g_blog_do_html'] );
$form['g_blog_do_commenthtml']   = $this->registry->output->formYesNo( "g_blog_do_commenthtml" , $group['g_blog_do_commenthtml'] );
$form['g_blog_allowpoll']        = $this->registry->output->formYesNo( "g_blog_allowpoll"      , $group['g_blog_allowpoll'] );
$form['g_blog_allowprivate']     = $this->registry->output->formYesNo( "g_blog_allowprivate"   , $group['g_blog_allowprivate'] );
$form['g_blog_allowprivclub']    = $this->registry->output->formYesNo( "g_blog_allowprivclub"  , $group['g_blog_allowprivclub'] );
$form['g_blog_alloweditors']     = $this->registry->output->formYesNo( "g_blog_alloweditors"   , $group['g_blog_alloweditors'] );
$form['g_blog_allowskinchoose']  = $this->registry->output->formYesNo( "g_blog_allowskinchoose", $group['g_blog_allowskinchoose'] );
$form['g_blog_preventpublish']   = $this->registry->output->formYesNo( "g_blog_preventpublish" , $group['g_blog_preventpublish'] );
$form['g_blog_attach_max']       = $this->registry->output->formInput( "g_blog_attach_max"      , $group['g_blog_attach_max'] );
$form['g_blog_attach_per_entry'] = $this->registry->output->formInput( "g_blog_attach_per_entry", $group['g_blog_attach_per_entry'] );
$form['g_blog_allowview']        = $this->registry->output->formYesNo( "g_blog_allowview"      , $group['g_blog_settings']['g_blog_allowview'] );
$form['g_blog_allowcomment']     = $this->registry->output->formYesNo( "g_blog_allowcomment"   , $group['g_blog_settings']['g_blog_allowcomment'] );
$form['g_blog_allowcreate']      = $this->registry->output->formYesNo( "g_blog_allowcreate"    , $group['g_blog_settings']['g_blog_allowcreate'] );
$form['g_blog_allowlocal']       = $this->registry->output->formYesNo( "g_blog_allowlocal"     , $group['g_blog_settings']['g_blog_allowlocal'] );
$form['g_blog_allowownmod']      = $this->registry->output->formYesNo( "g_blog_allowownmod"    , $group['g_blog_settings']['g_blog_allowownmod'] );
$form['g_blog_maxblogs']		 = $this->registry->output->formInput( 'g_blog_maxblogs'	   , $group['g_blog_settings']['g_blog_maxblogs'] );
$form['g_blog_allowdelete']		 = $this->registry->output->formYesNo( 'g_blog_allowdelete'	   , $group['g_blog_settings']['g_blog_allowdelete'] );
$form['g_blog_rsspergo']		 = $this->registry->output->formInput( 'g_blog_rsspergo'	   , $group['g_blog_settings']['g_blog_rsspergo'] );

$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_GROUPS_{$tabId}_content'>
	<table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['gf_bl_permissions']}</th>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_viewblog']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowview']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_postcomments']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowcomment']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_createlinked']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowcreate']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_createhosted']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowlocal']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bl_allow_delete']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowdelete']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_modownblog']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowownmod']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_rsspergo']}</strong></td>
			<td class='field_field'>{$form['g_blog_rsspergo']}<br /><span class='desctext'>{$this->lang->words['gf_bl_rsspergo_desc']}</span></td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['bl_securitysettings']}</th>
		</tr>
		<tr>
	 		<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_ghtmlentry']}</strong></td>
			<td class='field_field'>{$form['g_blog_do_html']}</td>
	 	</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_gpoll']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowpoll']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_gprivate']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowprivate']}<br /><span class='desctext'>{$this->lang->words['gf_bl_gprivate_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_gclub']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowprivclub']}<br /><span class='desctext'>{$this->lang->words['gf_bl_gclub_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_geditors']}</strong></td>
			<td class='field_field'>{$form['g_blog_alloweditors']}<br /><span class='desctext'>{$this->lang->words['gf_bl_geditors_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_gskin']}</strong></td>
			<td class='field_field'>{$form['g_blog_allowskinchoose']}<br /><span class='desctext'>{$this->lang->words['gf_bl_gskin_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_gpublish']}</strong></td>
			<td class='field_field'>{$form['g_blog_preventpublish']}<br /><span class='desctext'>{$this->lang->words['gf_bl_gpublish_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_maxblogs']}</strong></td>
			<td class='field_field'>{$form['g_blog_maxblogs']} <br /><span class='desctext'>{$this->lang->words['gf_bl_maxblogs_info']}</span></td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['gf_bl_attachmentsettings']}</th>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_gtotalsize']}</strong></td>
			<td class='field_field'>{$form['g_blog_attach_max']}<br /><span class='desctext'>{$this->lang->words['gf_bl_gtotalsize_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_bl_gmaxsize']}</strong></td>
			<td class='field_field'>{$form['g_blog_attach_per_entry']}<br /><span class='desctext'>{$this->lang->words['gf_bl_gmaxsize_info']}</span></td>
		</tr>
	</table>
</div>
EOF;

return $IPBHTML;
}

/**
 * Tabs for the group form
 *
 * @param	array		$group		Group data
 * @param	mixed		$tabId		Tab ID
 * @return	@e string	HTML
 */
function acp_blog_group_form_tabs( $group, $tabId ) {

$IPBHTML = "<li id='tab_GROUPS_{$tabId}'>" . IPSLib::getAppTitle('blog') . "</li>";

return $IPBHTML;
}

}