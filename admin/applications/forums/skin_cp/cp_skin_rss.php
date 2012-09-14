<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * RSS skin functions
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 10721 $
 */
 
class cp_skin_rss
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
 * Export overview page
 *
 * @param	string	Rows html
 * @param	string	Page links
 * @return	string	HTML
 */
public function rssExportOverview( $content, $page_links ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ex_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssexport_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />{$this->lang->words['rss_ex_create']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssexport_recache&amp;rss_export_id=all'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' />{$this->lang->words['rss_ex_update']}</a>
			</li>
		</ul>
	</div>
</div>

HTML;

if( $page_links != "" )
{
	$IPBHTML .= <<<HTML
	{$page_links}
HTML;
}

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['rss_ex_streams']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th style='width: 15px'>&nbsp;</th>
			<th style='width: 50px'>&nbsp;</th>
			<th width='85%'>{$this->lang->words['rss_title']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if( count( $content ) )
{
	foreach( $content as $data )
	{
		$badge = ( $data['rss_export_enabled'] ) ? "<span class='ipsBadge badge_green'>{$this->lang->words['enabled']}</span>" : "<span class='ipsBadge badge_red'>{$this->lang->words['disabled']}</span>";
		
$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>
				<a target='_blank' href='{$this->settings['board_url']}/index.php?app=core&amp;module=global&amp;section=rss&amp;type=forums&amp;id={$data['rss_export_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/feed.png' alt='RSS' /></a>
			</td>
			<td>
				{$badge}
			</td>
			<td>
				<strong class='larger_text'>{$data['rss_export_title']}</strong>
			</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_refresh'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssexport_recache&amp;rss_export_id={$data['rss_export_id']}'>{$this->lang->words['rss_ex_recache']}</a>
					</li>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssexport_edit&amp;rss_export_id={$data['rss_export_id']}'>{$this->lang->words['rss_ex_edit']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=rssexport_delete&amp;rss_export_id={$data['rss_export_id']}");'>{$this->lang->words['rss_ex_delete']}</a>
					</li>
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
			<td colspan='4' class='no_messages'>
				{$this->lang->words['ex_none']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssexport_add' class='mini_button'>{$this->lang->words['ex_createone']}</a>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>

HTML;

if( $page_links != "" )
{
	$IPBHTML .= <<<HTML
	{$page_links}
	<br style='clear: both' />
HTML;
}


//--endhtml--//
return $IPBHTML;
}

/**
 * Export form
 *
 * @param	array 	Form fields
 * @param	string	Title
 * @param	string	Action code
 * @param	string	Button text
 * @param	array	RSS Stream info
 * @return	string	HTML
 */
public function rssExportForm( $form, $title, $formcode, $button, $rssstream ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ex_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=$formcode&amp;rss_export_id={$rssstream['rss_export_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		<table class="ipsTable double_pad">
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_ex_title']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_title']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_desc']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_desc']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_ex_img']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_image']}<br />
					<span class='desctext'>{$this->lang->words['rss_ex_img_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_ex_enabled']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_enabled']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_ex_firstpost']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_include_post']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_ex_numitem']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_count']}<br />
					<span class='desctext'>{$this->lang->words['rss_ex_numitem_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_ex_order']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_order']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_ex_sort']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_sort']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_ex_forums']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_forums']}<br />
					<span class='desctext'>{$this->lang->words['rss_ex_forums_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_ex_cache']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_export_cache_time']}<br />
					<span class='desctext'>{$this->lang->words['rss_ex_cache_info']}</span>
				</td>
			</tr>
		</table>
	</div>
	
	<div class='acp-actionbar'>
		<input type='submit' class='button primary' value='{$button}' />
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Splash page for removing imported items
 *
 * @param	array 	RSS Record
 * @param	integer	Remove article count
 * @return	string	HTML
 */
public function rssImportRemoveArticlesForm( $rssstream, $article_count ) {

$article_count_text = sprintf( $this->lang->words['rss_im_articlecount'], $article_count ); 
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['im_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_remove_complete&amp;rss_import_id={$rssstream['rss_import_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['rss_im_removetopics']} {$rssstream['rss_import_title']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>{$article_count_text}</th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_removelast']}</strong>
				</td>
				<td class='field_field'>
					<input type='text' name='remove_count' value='10' /><br />
					<span class='desctext'>{$this->lang->words['rss_im_blankall']}</span>
				</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$this->lang->words['rss_im_removenoconf']}' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * RSS Import form
 *
 * @param	array 	Form fields
 * @param	string	Title
 * @param	string	Action code
 * @param	string	Button text
 * @param	array 	RSS Record
 * @return	string	HTML
 */
public function rssImportForm( $form, $title, $formcode, $button, $rssstream ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['im_title']}</h2>
</div>

<script type="text/javascript" src='{$this->settings['js_app_url']}acp.rss.js'></script>
<form id='rssimport_form' action='{$this->settings['base_url']}{$this->form_code}&amp;do={$formcode}&amp;rss_import_id={$rssstream['rss_import_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<input id='rssimport_validate' type='hidden' name='rssimport_validate' value='0' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>{$this->lang->words['rss_im_basics']}</th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_title']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_title']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_url']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_url']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_url_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_enabled']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_enabled']}
				</td>
			</tr>

			<tr>
				<th colspan='2'>{$this->lang->words['rss_im_htaccess']}</th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_ht_require']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_auth']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_ht_info']}</span>
				</td>
			</tr>
			<tr id='rss_import_auth_userinfo_1' style='display: none'>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_ht_user']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_auth_user']}
				</td>
			</tr>
			<tr id='rss_import_auth_userinfo_2' style='display: none'>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_ht_pass']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_auth_pass']}
				</td>
			</tr>

			<tr>
				<th colspan='2'>{$this->lang->words['rss_im_content']}</th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_forum']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_forum_id']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_forum_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_html']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_allow_html']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_html_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_poster']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_mid']}<br /> <br />
					<span class='desctext'>{$this->lang->words['rss_im_poster_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_link']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_showlink']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_link_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_open']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_topic_open']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_open_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_hidden']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_topic_hide']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_hidden_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_prefix']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_topic_pre']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_prefix_info']}</span>
				</td>
			</tr>

  			<tr>
				<th colspan='2'>{$this->lang->words['rss_im_settings']}</th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_pergo']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_pergo']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_pergo_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_refresh']}</strong>
				</td>
				<td class='field_field'>
					{$form['rss_import_time']}<br />
					<span class='desctext'>{$this->lang->words['rss_im_refresh_info']}</span>
				</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$button}' /> &nbsp;&nbsp;&nbsp;
			<input type='button' class='button primary' value='{$this->lang->words['rss_im_valbutton']}' onclick='ACPRss.validate();' /></div>
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * RSS Import stream overview
 *
 * @param	string	HTML content
 * @param	string	Page links
 * @return	string	HTML
 */
public function rssImportOverview( $content, $page_links ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['im_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />{$this->lang->words['rss_im_create']}</a>
				</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_recache&amp;rss_import_id=all'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' />{$this->lang->words['rss_im_updateall']}</a>
			</li>
		</ul>
	</div>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_validate' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['rss_im_quickval']}</h3>
		<table class='ipsTable'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rss_im_enturl']}</strong>
				</td>
				<td class='field_field'>
					<input type='text' size='70' name='rss_url' value='http://' class='input_text' /> <br />
					<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$this->lang->words['rss_im_valbutton']}' />
		</div>
	</div>
</form>
<br />

{$page_links}
<br class='clear' />

<div class='acp-box'>
	<h3>{$this->lang->words['rss_im_thefeeds']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th style='width: 30px'></th>
			<th style='width: 5%'></th>
			<th>{$this->lang->words['rss_title']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

foreach( $content as $data )
{
	$enabled = ( $data['rss_import_enabled'] ) ? "<span class='ipsBadge badge_green'>{$this->lang->words['enabled']}</span>" : "<span class='ipsBadge badge_red'>{$this->lang->words['disabled']}</span>";

$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td class='center'>
				<a target='_blank' href='{$data['rss_import_url']}'><img src='{$this->settings['skin_acp_url']}/images/icons/feed.png' alt='{$this->lang->words['rss_rss']}' style='vertical-align:middle' /></a>
			</td>
			<td class='center'>
				{$enabled}
			</td>
			<td>
				<strong class='larger_text'>{$data['rss_import_title']}</strong><br />
				<span class='desctext'>{$data['rss_import_url']}</span>
			</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_edit&amp;rss_import_id={$data['rss_import_id']}' title='{$this->lang->words['rss_im_edit']}'>{$this->lang->words['rss_im_edit']}</a>
					</li>
					<li class='i_refresh'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_recache&amp;rss_import_id={$data['rss_import_id']}'>{$this->lang->words['rss_im_update']}</a></li>
					<li class='ipsControlStrip_more ipbmenu' id="menu{$data['rss_import_id']}">
						<a href='#'>More</a>
					</li>
				</ul>
				<ul class='acp-menu' id='menu{$data['rss_import_id']}_menucontent' style='display: none'>
					<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_validate&amp;rss_id={$data['rss_import_id']}'>{$this->lang->words['rss_im_validate']}</a></li>
					<li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_remove&amp;rss_import_id={$data['rss_import_id']}'>{$this->lang->words['rss_im_remove']}</a></li>
					<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_delete&amp;rss_import_id={$data['rss_import_id']}");' title='{$this->lang->words['rss_im_delete']}'>{$this->lang->words['rss_im_delete']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>

{$page_links}
HTML;

//--endhtml--//
return $IPBHTML;
}

}