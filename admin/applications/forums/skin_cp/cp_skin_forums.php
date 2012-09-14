<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forums skin functions
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
 
class cp_skin_forums
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
 * Forum wrapper
 *
 * @param	string	Content
 * @param	array 	Forum data
 * @return	string	HTML
 */
public function forumWrapper( $content, $r ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
	<div id='cat_{$r['id']}' class='isDraggable'>
		<div class='root_item item category clearfix ipsControlRow'>
			<div class='col_buttons right' style='white-space: nowrap'>
				<ul class='ipsControlStrip'>
					<li class='i_add'>
						<a href='{$this->settings['base_url']}{$this->form_code}do=forum_add&amp;p={$r['id']}' title='{$this->lang->words['frm_newforum']}'>{$this->lang->words['frm_newforum']}</a>
					</li>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;f={$r['id']}' title='{$this->lang->words['frm_editsettings']}'>{$this->lang->words['frm_editsettings']}</a>
					</li>
					<li class='ipsControlStrip_more ipbmenu' id="menum-{$r['id']}">
						<a href='#'>{$this->lang->words['frm_options']}</a>
					</li>
				</ul>
				<ul class='acp-menu' id='menum-{$r['id']}_menucontent' style='display: none'>
					<li class='icon info'><a href='{$this->settings['base_url']}{$this->form_code}do=pedit&amp;f={$r['id']}'>{$this->lang->words['frm_permissions']}</a></li>
					<li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}do=delete&amp;f={$r['id']}'>{$this->lang->words['frm_deletecat']}</a></li>
					<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;f={$r['id']}&amp;nocat=1'>{$this->lang->words['cat_to_forum']}</a></li>
					<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}do=skinedit&amp;f={$r['id']}'>{$this->lang->words['frm_skinopt']}</a></li>
				</ul>
			</div>
			<div class='draghandle'>&nbsp;</div>
			<div class='item_info'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/folder.png' />
				&nbsp;&nbsp;<strong class='larger_text'>{$r['name']}</strong>
			</div>
		</div>
		<div id='cat_wrap_{$r['id']}' class='item_wrap'>
			{$content}
		</div>
	</div>
	<script type="text/javascript">
		dropItLikeItsHot{$r['id']} = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'cat_wrap_{$r['id']}', { tag: 'div', name: 'forums' } )
						};
 
			new Ajax.Request( "{$this->settings['base_url']}{$this->form_code_js}&do=doreorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

			return false;
		};

		Sortable.create( 'cat_wrap_{$r['id']}', { tag: 'div', only: 'isDraggable', revert: true, format: 'forum_([0-9]+)', onUpdate: dropItLikeItsHot{$r['id']}, handle: 'draghandle' } );
	</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum header
 *
 * @return	string	HTML
 */
public function renderForumHeader() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['for_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}module=forums&amp;do=forum_add&amp;forum_id={$this->request['f']}&amp;type=forum'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />
				{$this->lang->words['forums_context_add_forum']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}module=forums&amp;do=forum_add&amp;forum_id={$this->request['f']}&amp;type=category'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/folder_add.png' alt='' />
				{$this->lang->words['forums_context_add_category']}
			</a>
		</li>
	</ul>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.forums.js'></script>
<div class='acp-box'>
	<h3>{$this->lang->words['for_forumscap']}</h3>
	<div class='ipsExpandable' id='forum_wrapper'>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display single forum moderator entry
 *
 * @param	array 	Moderator data
 * @param	integer	Forum ID
 * @return	string	HTML
 */
public function renderModeratorEntry( $data=array() ) {

$return_id = intval( $this->request['f'] );

$IPBHTML = "";
//--starthtml--//
//print_r( $data );
if( count( $data ) )
{
	$c = count( $data );
	
	$IPBHTML .= <<<HTML
		<ul class='multi_menu' id='modmenu{$data[0]['randId']}'>
			<li>
				<a href='#' class='ipsBadge badge_green'>{$c} {$this->lang->words['frm_moderators']}</a>
				<ul class='acp-menu'>
HTML;
	
	foreach( $data as $i => $d )
	{
		$IPBHTML .= <<<HTML
					<li>
						<span class='clickable'>{$d['_fullname']}</span>
						<ul class='acp-menu'>
							<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}section=moderator&amp;act=mod&amp;do=remove&amp;mid={$d['mid']}&amp;fid=all");'>{$this->lang->words['frm_modremoveall']}</a></li>
							<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}section=moderator&amp;act=mod&amp;do=remove&amp;mid={$d['mid']}&amp;fid={$d['forum_id']}");'>{$this->lang->words['frm_modremove']}</a></li>
							<li class='icon edit'><a href='{$this->settings['base_url']}section=moderator&amp;act=mod&amp;do=edit&amp;mid={$d['mid']}&amp;return_id={$this->registry->class_forums->forum_by_id[ $d['forum_id'] ]['parent_id']}'>{$this->lang->words['frm_modedit']}</a></li>
						</ul>
					</li>
HTML;
	}
	
	$IPBHTML .= <<<HTML
				</ul>
			</li>
		</ul>
		<script type='text/javascript'>
			jQ("#modmenu{$data[0]['randId']}").ipsMultiMenu();
		</script>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum footer
 *
 * @return	string	HTML
 */
public function renderForumFooter() {

$return_id	= intval( $this->request['f'] );

//-----------------------------------------
// Member groups menu
//-----------------------------------------

$mem_group	= "<select name='group' class='dropdown'>";
	
foreach( $this->caches['group_cache'] as $r )
{
 	$mem_group	.= "<option value='{$r['g_id']}'>{$r['g_title']}</option>\n";
}

$mem_group	.= "</select>";
		
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
	</div>
	
	<form method='post' action='{$this->settings['base_url']}module=forums&amp;section=moderator&amp;do=add' onsubmit='return ACPForums.submitModForm()'>
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
		<input type='hidden' name='modforumids' id='modforumids' />
		<input type='hidden' name='return_id' value='{$return_id}' />
		
		<div class='acp-actionbar rightaction'>
			{$this->lang->words['frm_modname']} <input class='input_text' type='text' name='name' id='modUserName' size='20' value='' /> {$this->lang->words['frm_modorgroup']} {$mem_group}
			<input type='submit' class='realbutton' value='{$this->lang->words['frm_modaddtxt']}' />
		</div>
	</form>
</div>
<script type="text/javascript">
dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'forum_wrapper', { tag: 'div', name: 'forums' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=doreorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};

Sortable.create( 'forum_wrapper', { tag: 'div', only: 'isDraggable', revert: true, format: 'cat_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Render a forum row
 *
 * @param	string	Description
 * @param	array 	Forum data
 * @param	string	Depth guide
 * @param	string	Skin used
 * @return	string	HTML
 */
public function renderForumRow( $desc, $r, $depth_guide, $skin ) {

$IPBHTML = "";
//--starthtml--//

if( $depth_guide )
{
	$IPBHTML .= <<<HTML
	<div class='item parent ipsControlRow isDraggable' id='forum_{$r['id']}'>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<div class='item ipsControlRow isDraggable' id='forum_{$r['id']}'>
HTML;
}

$IPBHTML .= <<<HTML
		<table style='width: 100%'>
			<tr>
				<td style='width: 20px !important; vertical-align: top;'>
					<div class='draghandle'>&nbsp;</div>
				</td>
				<td style=''>
					<div class='item_info'>
						<strong class='forum_name'>{$r['name']}</strong>
						<br />
						<span class='desctext'>{$desc}</span>
					</div>
				</td>
				<td style='width: 120px; text-align: left; vertical-align: top;'>
					{$r['_modstring']}
				</td>
				<td style='width: 120px; vertical-align: top;'>
					<input class='right' type='checkbox' title='{$this->lang->words['frm_modcheck']}' id='id_{$r['id']}' value='1' /> 
					<div class='col_buttons right'>
						<ul class='ipsControlStrip'>
							<li class='i_edit'>
								<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;f={$r['id']}'>{$this->lang->words['frm_editsettings']}</a>
							</li>
							<li class='ipsControlStrip_more ipbmenu' id="menu{$r['id']}">
								<a href='#'>{$this->lang->words['frm_options']}</a>
							</li>
						</ul>
						<ul class='acp-menu' id='menu{$r['id']}_menucontent' style='display: none'>
							<li class='icon info'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=pedit&amp;f={$r['id']}'>{$this->lang->words['frm_permissions']}</a></li>
							<li class='icon delete'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=empty&amp;f={$r['id']}'>{$this->lang->words['frm_emptyforum']}</a></li>
							<li class='icon delete'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete&amp;f={$r['id']}'>{$this->lang->words['frm_deleteforum']}</a></li>
							<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=frules&amp;f={$r['id']}'>{$this->lang->words['frm_forumrules']}</a></li>
							<li class='icon manage'><a href='{$this->settings['base_url']}&amp;module=tools&amp;section=tools&amp;do=clearforumsubs&amp;f={$r['id']}'>{$this->lang->words['m_clearsubs']}</a></li>
							<li class='icon view'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=skinedit&amp;f={$r['id']}'>{$this->lang->words['frm_skinopt']}</a></li>
							<li class='icon manage'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=forum_add&amp;duplicate=1&amp;f={$r['id']}'>{$this->lang->words['forum_duplicate']}</a></li>
							<li class='icon refresh'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=recount&amp;f={$r['id']}'>{$this->lang->words['frm_resync']}</a></li>
						</ul>
					</div>
				</td>
			</tr>
		</table>
	</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display "no forums" row
 *
 * @param	integer	Parent ID
 * @return	string	HTML
 */
public function renderNoForums( $parent_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='item parent ipsControlRow' id='forum_none{$parent_id}'>
	<strong style='font-size:11px;color:red;'>{$this->lang->words['frm_noforums']}</strong>
	<div><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=forum_add&amp;p={$parent_id}'>{$this->lang->words['frm_noforumslink']}</a></div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum permissions matrix
 *
 * @param	array 	Forum data
 * @param	array 	Relative links
 * @param	string	Matrix HTML
 * @param	array 	..of Forum Data
 * @return	string	HTML
 */
public function forumPermissionForm( $forum, $relative, $perm_matrix, $forumData=array(), $forumCopyDropDown ) {

$IPBHTML = "";
//--starthtml--//

$title	= urlencode($forum['name']);

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['head_forum_permissions']} {$forumData['name']}</h2>
</div>

<form name='permCopyForm' id='permCopyForm' class='information-box' style='margin-bottom:10px;' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=pedit&amp;f={$this->request['f']}' method='post'>
	{$this->lang->words['copy_perms_from']} {$forumCopyDropDown}
	<input type='submit' name='submit' value='{$this->lang->words['copy_perms_go']}' class='button primary' /> 
</form>

<form name='adminform' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=pdoedit&amp;f={$this->request['f']}&amp;name={$title}&amp;nextid={$relative['next']}&amp;previd={$relative['previous']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	{$perm_matrix }
	
	<div class='acp-box'>
		<div class='acp-actionbar'>
HTML;
if ( $relative['next'] > 0 )
{
$IPBHTML .= <<<HTML
			<input type='submit' name='donext' value='{$this->lang->words['frm_savenext']}' class='button primary' /> 
HTML;
}
$IPBHTML .= <<<HTML
			<input type='submit' value='{$this->lang->words['frm_saveonly']}' class='button primary' /> 
			<input type='submit' name='reload' value='{$this->lang->words['frm_savereload']}' class='button primary' /> 
HTML;
if ( $relative['next'] > 0 )
{
$IPBHTML .= <<<HTML
			<input type='submit' name='doprevious' value='{$this->lang->words['frm_saveprev']}' class='button primary' />
HTML;
}
$IPBHTML .= <<<HTML
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum rules form
 *
 * @param	integer	Forum ID
 * @param	array 	Forum data
 * @return	string	HTML
 */
public function forumRulesForm( $id, $data )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['frm_rulessetup']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dorules' />
	<input type='hidden' name='f' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['forum_rules_head']} {$data['name']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_rulesdisplay']}</strong>
				</td>
				<td class='field_field'>
					{$data['_show_rules']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_rulestitle']}</strong>
				</td>
				<td class='field_field'>
					{$data['_title']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_rulestext']}</strong>
				</td>
				<td class='field_field'>
					{$data['_editor']}
				</td>
			</tr>
			<!--<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_rule_raw_html_title']}</strong>
				</td>
				<td class='field_field'>
					{$data['rules_raw_html']}<br />
					<span class='desctext'>{$this->lang->words['frm_rule_raw_html_desc']}</span>
				</td>
			</tr>-->
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['frm_rulesbutton']}' class='button primary' accesskey='s' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum skin options form
 *
 * @param	integer	Forum id
 * @param	array 	Forum data
 * @return	string	HTML
 */
public function forumSkinOptions( $id, $data )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['modify_skin_head']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doskinedit' />
	<input type='hidden' name='f' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_skinchoice']} {$data['name']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_skinapply']}</strong>
				</td>
				<td class='field_field'>
					{$data['fsid']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_skinsub']}</strong>
				</td>
				<td class='field_field'>
					{$data['apply_to_children']}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['frm_skinbutton']}' class='button primary' accesskey='s' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
	
}

/**
 * Display form to empty a forum
 *
 * @param	array 	Forum data
 * @return	string	HTML
 */
public function forumEmptyForum( $forum )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['frm_emptytitle']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doempty' />
	<input type='hidden' name='f' value='{$forum['id']}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_emptysubtitle']} {$forum['name']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_emptywhich']}</strong>
				</td>
				<td class='field_field'>
					{$forum['name']}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['frm_emptybutton']}' class='button primary' accesskey='s' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display form to delete a forum
 *
 * @param	integer	Forum ID
 * @param	string	Name
 * @param	string	Options HTML for move to dropdown
 * @param	integer	[Category=1|Forum=0]
 * @param	string	Options HTML for move subforums to dropdown
 * @return	string	HTML
 */
public function forumDeleteForm( $id, $name, $move, $is_cat, $subs )
{
$IPBHTML = "";
//--starthtml--//

$text	= $is_cat ? $this->lang->words['for_iscat_y'] : $this->lang->words['for_iscat_n'];
$title	= sprintf( $this->lang->words['for_removing'], $text, $name );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dodelete' />
	<input type='hidden' name='f' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_deletetitle']} {$name}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_deletewhich']}</strong>
				</td>
				<td class='field_field'>
					{$name}
				</td>
			</tr>
HTML;

if( $move )
{
	$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_deletemove']}</strong>
				</td>
				<td class='field_field'>
					{$move}
				</td>
			</tr>
HTML;
}

if( $subs )
{
	$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_moveparent']}</strong>
				</td>
				<td class='field_field'>
					{$subs}
				</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['frm_deletebutton']}' class='button primary' accesskey='s' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;	
}

/**
 * Display form to add/edit a forum
 *
 * @param	array 	Form fields
 * @param	string	Button text
 * @param	string	Action code
 * @param	string	Title
 * @param	array 	Forum data
 * @param	string	Permissions matrix
 * @param	string	Default tab to open
 * @return	string	HTML
 */
public function forumForm( $form, $button, $code, $title, $forum, $perm_matrix, $firsttab='' ) {

$IPBHTML = "";
//--starthtml--//

$_forUrl	= urlencode($forum['name']);

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.forums.js'></script>
<div class='section_title'>
	<h2>{$title}</h2>
</div>
HTML;

if( $code == 'donew'){
	$IPBHTML .= "<div class='ipsSteps_wrap'>";
}

$IPBHTML .= <<<HTML
	<form name='adminform' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$code}&amp;f={$this->request['f']}&amp;name={$_forUrl}' method='post' enctype='multipart/form-data'>
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
		<input type='hidden' name='convert' id='convert' value='0' />
		<input type='hidden' name='type' value='{$this->request['type']}' />
HTML;

//---------------------------------------------
// If we're adding a new forum, show the wizard
//---------------------------------------------
if( $code == 'donew' )
{
	$steps = 1;

	// Basic settings
	$IPBHTML .= <<<HTML
		<div class='ipsSteps clearfix' id='steps_bar'>
			<ul>
				<li class='steps_active' id='step_basic'>
					<strong class='steps_title'>{$this->lang->words['admin_step']} {$steps}</strong>
					<span class='steps_desc'>{$this->lang->words['frm_f_basic']}</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
HTML;
	$steps++;
	
	// Postable forum settings
	if( $form['addnew_type'] != 'category' )
	{
		$IPBHTML .= <<<HTML
			<li id='step_postable'>
				<strong class='steps_title'>{$this->lang->words['admin_step']} {$steps}</strong>
				<span class='steps_desc'>{$this->lang->words['from_f_postable_settings']}</span>
				<span class='steps_arrow'>&nbsp;</span>
			</li>
HTML;
		$steps++;
	}
	
	if( $form['tabStrip'] && $form['tabContent'] )
	{
		//print_r( $form );
		$IPBHTML .= <<<HTML
			<li id='step_additional'>
				<strong class='steps_title'>{$this->lang->words['admin_step']} {$steps}</strong>
				<span class='steps_desc'>{$this->lang->words['forum_tab_3rd_party']}</a>
				<span class='steps_arrow'>&nbsp;</span>
			</li>
HTML;
		$steps++;
	}
	
	//Permission matrix
	if( $perm_matrix )
	{
		$IPBHTML .= <<<HTML
			<li id='step_perms'>
				<strong class='steps_title'>{$this->lang->words['admin_step']} {$steps}</strong>
				<span class='steps_desc'>{$this->lang->words['from_f_perms']}</span>
				<span class='steps_arrow'>&nbsp;</span>
			</li>
HTML;
		$steps++;
	}
	
	$IPBHTML .= <<<HTML
		</ul>
	</div>
HTML;
}

$IPBHTML .= <<<HTML
	<div class='acp-box'>
HTML;

if( $code != 'donew'  && ( ( $forum['parent_id'] == 'root' && ! empty($form['tabStrip']) ) or $forum['parent_id'] != 'root' or $this->request['nocat'] ) )
{
	$IPBHTML .= <<<HTML
	<h3>{$this->lang->words['frm_settings']}</h3>
	<div class='ipsTabBar with_left with_right' id='tabstrip_forums'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_basic'>{$this->lang->words['frm_f_basic']}</li> 
HTML;
		if( $form['addnew_type'] != 'category' && ( $forum['parent_id'] != 'root' or $this->request['nocat'] ) )
		{
			if( !$form['sub_can_post'] ){
				$IPBHTML .= "<li id='tab_postable' style='display: none'>{$this->lang->words['from_f_postable_settings']}</li> ";
			} else {
				$IPBHTML .= "<li id='tab_postable'>{$this->lang->words['from_f_postable_settings']}</li> ";
			}
		}
		
		if( $perm_matrix )
		{
			$IPBHTML .= "<li id='tab_perms'>{$this->lang->words['from_f_perms']}</li> ";
		}
		
		if( $form['tabStrip'] )
		{
			$IPBHTML .= $form['tabStrip'];
		}
		
		$IPBHTML .= <<<HTML
		</ul>
	</div>
	<div class='ipsTabBar_content' id='tabstrip_forums_content'>
HTML;
}

if( $code == 'donew' )
{
$IPBHTML .= <<<HTML
	<div class='ipsSteps_wrapper' id='ipsSteps_wrapper'>
		<div id='step_basic_content' class='steps_content'>
			<div class='acp-box'>
				<h3>{$this->lang->words['frm_f_basic']}</h3>
HTML;
}
else
{
	$_firstTab = $firsttab ? $firsttab : 'basic';
	
	$IPBHTML .= <<<HTML
	<div id='tab_basic_content'>
HTML;
}
	
	$IPBHTML .= <<<HTML
		 <table class='ipsTable double_pad'>
HTML;
			if( $form['addnew_type'] != 'category' && ( $forum['parent_id'] != 'root' or $this->request['nocat'] ) )
			{
				$checked_standard	= '';
				$checked_cat		= '';
				$checked_redirect	= '';
				
				if( $code != 'donew' )
				{
					if( $forum['parent_id'] == 'root' || (!$forum['sub_can_post'] && !$forum['redirect_url']) )
					{
						$checked_cat		= " checked='checked'";
					}
					else if( $forum['redirect_url'] )
					{
						$checked_redirect	= " checked='checked'";
					}
					else
					{
						$checked_standard	= " checked='checked'";
					}
				}
				else
				{
					$checked_standard = " checked='checked'";
				}
				
				$IPBHTML .= <<<HTML
					<tr>
						<th colspan='2'>{$this->lang->words['frm_type_header']}</th>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_type']}</strong>
						</td>
						<td class='field_field'>
							<table id='forum_type_chooser'>
								<tr>
									<td style='width: 25px'>
										<input type='radio' name='forum_type' id='type_standard' value='standard'{$checked_standard} />
									</td>
									<td>
										<strong><label for='type_standard'>{$this->lang->words['frm_type_stand']}</label></strong><br />
										<span class='desctext'>{$this->lang->words['frm_type_stand_d']}</span>
									</td>
								</tr>
								<tr>
									<td>								
										<input type='radio' name='forum_type' id='type_category' value='category'{$checked_cat} />
									</td>
									<td>
										<strong><label for='type_category'>{$this->lang->words['frm_type_cat']}</label></strong><br />
										<span class='desctext'>{$this->lang->words['frm_type_cat_d']}</span>
									</td>
								</tr>
								<tr>
									<td>
										<input type='radio' name='forum_type' id='type_redirect' value='redirect'{$checked_redirect} />
									</td>
									<td>								
										<strong><label for='type_redirect'>{$this->lang->words['frm_type_redirect']}</label></strong><br />
										<span class='desctext'>{$this->lang->words['frm_type_redirect_d']}</span>
									</td>
								</tr>
							</table>
						</td>
					</tr>
HTML;
			}
		
		$IPBHTML .= <<<HTML
					<tr>
						<th colspan='2'>{$this->lang->words['frm_settings']}</th>
					</tr>
		    		<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_name_' . $form['addnew_type'] ]}</strong>
						</td>
						<td class='field_field'>
		   					{$form['name']}
						</td>
		 			</tr>
HTML;

		if( $form['addnew_type'] != 'category' && ( $forum['parent_id'] != 'root' or $this->request['nocat'] ) )
		{
		$IPBHTML .= <<<HTML
		 			<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_desc']}</strong>
						</td>
						<td class='field_field'>
							{$form['description']}<br />
							<span class='desctext'>{$this->lang->words['frm_f_desc_info']}</span>
						</td>
					</tr>

					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_parent']}</strong>
						</td>
						<td class='field_field'>
							{$form['parent_id']}
						</td>
					</tr>

					<tbody id='forum_redirect' style='display: none'>
						<tr>
							<th colspan='2'>
								{$this->lang->words['frm_f_redirect']}
							</th>
						</tr>

						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['frm_f_redirect_url']}</strong>
							</td>
							<td class='field_field'>
								{$form['redirect_url']}
							</td>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['frm_f_redirect_num']}</strong>
							</td>
							<td class='field_field'>
								{$form['redirect_hits']}
							</td>
						</tr>
					</tbody>
			
					<tbody id='forum_perms' style='display: none'>
						<tr>
							<th colspan='2'>
								{$this->lang->words['frm_f_perm_title']}
							</th>
						</tr>

						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['disable_sharelinks']}</strong>
							</td>
							<td class='field_field'>
								{$form['disable_sharelinks']}<br />
								<span class='desctext'>{$this->lang->words['disable_sharelinks_desc']}</span>
							</td>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['frm_f_perm_hide']}</strong>
							</td>
							<td class='field_field'>
								{$form['hide_last_info']}<br />
								<span class='desctext'>{$this->lang->words['frm_f_perm_hide_info']}</span>
							</td>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['frm_f_perm_list']}</strong>
							</td>
							<td class='field_field'>
								{$form['permission_showtopic']}<br />
								<span class='desctext'>{$this->lang->words['frm_f_perm_list_info']}</span>
							</td>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['frm_f_perm_cust']}</strong>
							</td>
							<td class='field_field'>
								{$form['permission_custom_error']}<br />
								<span class='desctext'>{$this->lang->words['frm_f_perm_cust_info']}</span>
							</td>
						</tr>
					</tbody>
					<tbody id='forum_password' style='display: none'>
						<tr>
							<th colspan='2'>
								{$this->lang->words['frm_f_password']}
							</th>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['frm_f_mod_pass']}</strong>
							</td>
							<td class='field_field'>
								{$form['password']}<br />
								<span class='desctext'>{$this->lang->words['frm_f_mod_pass_info']}</span>
							</td>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['frm_f_mod_exempt']}</strong>
							</td>
							<td class='field_field'>
								{$form['password_override']}<br />
								<span class='desctext'>{$this->lang->words['frm_f_mod_exempt_info']}</span>
							</td>
						</tr>
					</tbody>
HTML;
		}
		
		$IPBHTML .= <<<HTML
					
				</table>
				<input type='hidden' name='sub_can_post' value='0' />
				<input type='hidden' name='redirect_on' value='0' />

				<script type='text/javascript'>
					updateForumForm();
				
					jQ("input[name=forum_type]:radio").click( function(e){
						updateForumForm();
					});
HTML;

				if( $code != 'donew' )
				{
					$IPBHTML .= <<<HTML
					jQ(document).ready(function() {
						updateForumForm();
					});
HTML;
				}
					
				$IPBHTML .= <<<HTML
					function updateForumForm()
					{
						var curVal = jQ("input[name=forum_type]:checked").val();

						switch( curVal ){
							case "standard":
								jQ("input:hidden[name=sub_can_post]").val(1);
								jQ("input:hidden[name=redirect_on]").val(0);
								jQ("#forum_redirect").hide();
								jQ("#forum_perms").show();
								jQ("#forum_password").show();
								jQ("#step_postable").removeClass('steps_disabled').addClass('clickable');
								jQ("#tab_postable").show();
							break;
							case "category":
								jQ("input:hidden[name=sub_can_post]").val(0);
								jQ("input:hidden[name=redirect_on]").val(0);
								jQ("#forum_redirect").hide();
								jQ("#forum_perms").hide();
								jQ("#forum_password").show();
								jQ("#step_postable").addClass('steps_disabled').removeClass('clickable');
								jQ("#tab_postable").hide();
							break;
							case "redirect":
								jQ("input:hidden[name=sub_can_post]").val(0);
								jQ("input:hidden[name=redirect_on]").val(1);
								jQ("#forum_redirect").show();
								jQ("#forum_perms").hide();
								jQ("#forum_password").hide();
								jQ("#step_postable").addClass('steps_disabled').removeClass('clickable');
								jQ("#tab_postable").hide();
							break;
						}
					}
				</script>
			</div>
HTML;
		
if( $code == 'donew' ){
	$IPBHTML .= <<<HTML
		</div>
HTML;

	if( $form['addnew_type'] != 'category' && ( $forum['parent_id'] != 'root' or $this->request['nocat'] ) )
	{
		$IPBHTML .= <<<HTML
		<div id='step_postable_content' style='display: none'>
			<div class='acp-box'>
				<h3>{$this->lang->words['from_f_postable_settings']}</h3>
HTML;
	}
}
elseif( $form['addnew_type'] != 'category' && ( $forum['parent_id'] != 'root' or $this->request['nocat'] ) )
{
	$IPBHTML .= <<<HTML
		<div id='tab_postable_content'>
HTML;
}

if( $form['addnew_type'] != 'category' && ( $forum['parent_id'] != 'root' or $this->request['nocat'] ) )
{
$IPBHTML .= <<<HTML
		 		<table class='ipsTable double_pad'>
					<tr>
						<th colspan='2'>
							{$this->lang->words['frm_f_post_title']}
						</th>
					</tr>			
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_post_html']}</strong>
						</td>
						<td class='field_field'>
							{$form['use_html']}<br />
							<span class='desctext'>{$this->lang->words['frm_f_post_html_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_post_bb']}</strong>
						</td>
						<td class='field_field'>
							{$form['use_ibc']}
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_post_poll']}</strong>
						</td>
						<td class='field_field'>
							{$form['allow_poll']}
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_post_bump']}</strong>
						</td>
						<td class='field_field'>
							{$form['allow_pollbump']}<br />
							<span class='desctext'>{$this->lang->words['frm_f_post_bump_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_post_rate']}</strong>
						</td>
						<td class='field_field'>
							{$form['forum_allow_rating']}
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_post_inc']}</strong>
						</td>
						<td class='field_field'>
							{$form['inc_postcount']}<br />
							<span class='desctext'>{$this->lang->words['frm_f_post_inc_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_min_posts_post']}</strong>
						</td>
						<td class='field_field'>
							{$form['min_posts_post']}<br />
							<span class='desctext'>{$this->lang->words['frm_f_min_posts_post_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_min_posts_view']}</strong>
						</td>
						<td class='field_field'>
							{$form['min_posts_view']}<br />
							<span class='desctext'>{$this->lang->words['frm_f_min_posts_view_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_canviewothers']}</strong>
						</td>
						<td class='field_field'>
							{$form['can_view_others']}
						</td>
					</tr>
			
					<tr>
						<th colspan='2'>
							{$this->lang->words['frm_f_mod_title']}
						</th>
					</tr>

					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_mod_en']}</strong>
						</td>
						<td class='field_field'>
							{$form['preview_posts']}<br />
							<span class='desctext'>{$this->lang->words['frm_f_mod_en_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_mod_email']}</strong>
						</td>
						<td class='field_field'>
							{$form['notify_modq_emails']}<br />
							<span class='desctext'>{$this->lang->words['frm_f_mod_email_info']}</span>
						</td>
					</tr>	

					<tr>
						<th colspan='2'>
							{$this->lang->words['frm_f_sort_title']}
						</th>
					</tr>

					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_sort_cutoff']}</strong>
						</td>
						<td class='field_field'>
							{$form['prune']}
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_sort_key']}</strong>
						</td>
						<td class='field_field'>
							{$form['sort_key']}
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_sort_order']}</strong>
						</td>
						<td class='field_field'>
							{$form['sort_order']}
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['frm_f_sort_filter']}</strong>
						</td>
						<td class='field_field'>
							{$form['topicfilter']}
						</td>
					</tr>
					<tr>
						<th colspan='2'>
							{$this->lang->words['frm_f_tagging_title']}
						</th>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['bw_disable_tagging']}</strong>
						</td>
						<td class='field_field'>
							{$form['bw_disable_tagging']}
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['bw_disable_prefixes']}</strong>
						</td>
						<td class='field_field'>
							{$form['bw_disable_prefixes']}<br />
							<span class="desctext">{$this->lang->words['bw_disable_prefixes_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['tag_predefined']}</strong>
						</td>
						<td class='field_field'>
							{$form['tag_predefined']}<br />
							<span class="desctext">{$this->lang->words['tag_predefined_desc']}</span>
						</td>
					</tr>
				</table>
HTML;

if( $code == 'donew' )
{
	$IPBHTML .= <<<HTML
			</div>
		</div>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	</div>
HTML;
}
}

if( $form['tabContent'] )
{
	if( $code == 'donew' )
	{
		$IPBHTML .= <<<HTML
			<div id='step_additional_content' style='display: none'>
				<h3>{$this->lang->words['forum_tab_3rd_party_settings']}</h3>
				<div class='ipsTabBar with_left with_right' id='tabstrip_3rdparty'>
					<span class='tab_left'>&laquo;</span>
					<span class='tab_right'>&raquo;</span>
					<ul>
						{$form['tabStrip']}
					</ul>
				</div>
				<div id='tabstrip_3rdparty_content' class='ipsTabBar_content'>
					{$form['tabContent']}
				</div>
				<script type='text/javascript'>
					jQ("#tabstrip_3rdparty").ipsTabBar({ tabWrap: "#tabstrip_3rdparty_content" });
				</script>
			</div>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
			{$form['tabContent']}
HTML;
	}
}

if ( $perm_matrix )
{
	if( $code == 'donew' )
	{
		$IPBHTML .= <<<HTML
			<div id='step_perms_content' style='display: none'>
				<div class='acp-box'>
					{$perm_matrix}
				</div>
			</div>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
			<div id='tab_perms_content'>
				{$perm_matrix}
			</div>
HTML;
	}
}

if( $form['addnew_type'] == 'category' )
{
	$IPBHTML .= <<<HTML
	<input type='hidden' name='parent_id' value='-1' />
	<input type='hidden' name='sub_can_post' value='0' />
	<input type='hidden' name='permission_showtopic' value='1' />
HTML;
}

$IPBHTML .= <<<HTML
	</div>
HTML;

if( $code == 'donew' )
{
	$_lang 	= $this->request['type'] == 'category' ? $this->lang->words['frm_savecat'] : $this->lang->words['frm_save'];
	
	$IPBHTML .= <<<HTML
	<div id='steps_navigation' class='clearfix' style='margin-top: 10px;'>
		<input type='button' class='realbutton left' value='{$this->lang->words['wiz_prev']}' id='prev' />
		<input type='button' class='realbutton right' value='{$this->lang->words['wiz_next']}' id='next' />
		<p class='right' id='finish' style='display: none'>
			<input type='submit' class='realbutton' value='{$_lang}' />
		</p>
	</div>
	<script type='text/javascript'>
		jQ("#steps_bar").ipsWizard( { allowJumping: true, allowGoBack: false } );
	</script>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<div class='acp-actionbar'>
		<input type='submit' class='realbutton' value='{$this->lang->words['frm_save']}' />
	</div>
	<script type='text/javascript'>
		jQ("#tabstrip_forums").ipsTabBar({ tabWrap: "#tabstrip_forums_content", defaultTab: "tab_{$_firstTab}" });
	</script>
HTML;
}

$IPBHTML .= <<<HTML
	</form>
</div>
HTML;


//--endhtml--//
return $IPBHTML;
}

/**
 * Select a moderator gateway page
 *
 * @param	integer	Forum ID
 * @param	string	Dropdown options of members
 * @return	string	HTML
 */
public function moderatorSelectForm( $fid, $member_drop ) {

$return_id = intval( $this->request['return_id'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='add_final' />
	<input type='hidden' name='fid' value='{$fid}' />
	<input type='hidden' name='return_id' value='{$return_id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />

	<div class='acp-box'>
		<h3>{$this->lang->words['frm_m_search']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_choose']}</strong>
				</td>
				<td class='field_field'>
					{$member_drop}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['frm_m_choosebutton']}' class='button primary' accesskey='s' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display moderator add/edit form
 *
 * @param	array 	Form fields
 * @param	string	Action code
 * @param	integer	Member ID
 * @param	string	Searched member text
 * @param	string	Type
 * @param	integer	Group ID
 * @param	string	Group name
 * @param	string	Button text
 * @return	string	HTML
 */
public function moderatorPermissionForm( $form, $form_code, $mid, $mem, $type, $gid, $gname, $button ) {

$return_id = intval( $this->request['return_id'] );

$IPBHTML = "";
//--starthtml--//

if( $form_code == 'doedit' )
{
	$title	= $this->lang->words['mod_edit'];
}
else if ( $this->request['group'] )
{
	$title	= $this->lang->words['mod_addgroup'];
}
else
{
	$title	= $this->lang->words['mod_add'];
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$form_code}' />
	<input type='hidden' name='mid' value='{$mid}' />
	<input type='hidden' name='mem' value='{$mem}' />
	<input type='hidden' name='mod_type' value='{$type}' />
	<input type='hidden' name='gid' value='{$gid}' />
	<input type='hidden' name='gname' value='{$gname}' />
	<input type='hidden' name='return_id' value='{$return_id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />

	<div class='acp-box'>
		<h3>{$this->lang->words['frm_m_genset']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_mod_forums']}</strong>
				</td>
				<td class='field_field'>
					{$form['forums']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_edit']}</strong>
				</td>
				<td class='field_field'>
					{$form['edit_post']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_topic']}</strong>
				</td>
				<td class='field_field'>
					{$form['edit_topic']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_delete']}</strong>
				</td>
				<td class='field_field'>
					{$form['delete_post']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_deletetop']}</strong>
				</td>
				<td class='field_field'>
					{$form['delete_topic']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_bw_soft_delete']}</strong>
				</td>
				<td class='field_field'>
					{$form['bw_mod_soft_delete']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_bw_un_soft_delete']}</strong>
				</td>
				<td class='field_field'>
					{$form['bw_mod_un_soft_delete']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_bw_soft_delete_see']}</strong>
				</td>
				<td class='field_field'>
					{$form['bw_mod_soft_delete_see']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_ip']}</strong>
				</td>
				<td class='field_field'>
					{$form['view_ip']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_open']}</strong>
				</td>
				<td class='field_field'>
					{$form['open_topic']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_close']}</strong>
				</td>
				<td class='field_field'>
					{$form['close_topic']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_move']}</strong>
				</td>
				<td class='field_field'>
					{$form['move_topic']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_pin']}</strong>
				</td>
				<td class='field_field'>
					{$form['pin_topic']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_unpin']}</strong>
				</td>
				<td class='field_field'>
					{$form['unpin_topic']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_split']}</strong>
				</td>
				<td class='field_field'>
					{$form['split_merge']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_opentime']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_set_open_time']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_closetime']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_set_close_time']}
				</td>
			</tr>
			<tr>
				<th colspan='2'>{$this->lang->words['frm_m_msettings']}</th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_massmove']}</strong>
				</td>
				<td class='field_field'>
					{$form['mass_move']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_massprune']}</strong>
				</td>
				<td class='field_field'>
					{$form['mass_prune']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_visible']}</strong>
				</td>
				<td class='field_field'>
					{$form['topic_q']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_visiblepost']}</strong>
				</td>
				<td class='field_field'>
					{$form['post_q']}
				</td>
			</tr>
			<tr>
				<th colspan='2'>{$this->lang->words['frm_m_asettings']}</th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_warn']}</strong>
				</td>
				<td class='field_field'>
					{$form['allow_warn']}<br />
					<span class='desctext'>{$this->lang->words['frm_m_warn_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_spam']}</strong>
				</td>
				<td class='field_field'>
					{$form['bw_flag_spammers']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['frm_m_mm']}</strong>
				</td>
				<td class='field_field'>
					{$form['can_mm']}<br />
					<span class='desctext'>( <a href='#' onClick="window.open('{$this->settings['_base_url']}app=core&amp;module=help&amp;id=mod_mmod','Help','width=450,height=400,resizable=yes,scrollbars=yes'); return false;">{$this->lang->words['frm_m_mm_info']}</a> )</span>
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' accesskey='s' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add a moderator when we already have records for some of the selected forums
 *
 * @param	string	'name' or 'group'
 * @param	array	Data for member or group
 * @param	array	Selected forums which we are already a member of
 * @param	array	Selected forums which we are not already a member of
 * @param	array	A multi-dimensional array specifying the forums we are already a moderator of by the database record
 * @return	string	HTML
 */
public function moderatorDuplicateForm( $type, $data, $alreadyAMod, $notCurrentlyAMod, $alreadyAModByRecord ) {

if ( $type == 'name' )
{
	$formattedName = sprintf( $this->lang->words['mod_member_is'], $data['members_display_name'] );
	$moderatorWord = $this->lang->words['mod_mods_singluar'];
	$qs = "member_id={$data['member_id']}";
}
else
{
	$formattedName = sprintf( $this->lang->words['mod_group_are'],  $data['g_title'] );
	$moderatorWord = $this->lang->words['mod_mods_plural'];
	$qs = "group={$data['g_id']}";
}

$intro = sprintf( $this->lang->words['mod_already_exists'], $formattedName, $moderatorWord, $this->implodeWithProperGrammar( $alreadyAMod ) );

$implodedIds = implode( ',', array_keys( $notCurrentlyAMod ) );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['mod_add']}</h2>
	
	<div class='information-box'>
		{$intro}
	</div>
	<br />
	
HTML;

foreach ( $alreadyAModByRecord as $recordId => $_fs )
{
	$message = sprintf( $this->lang->words['mod_add_with_same_perms'], ( $type == 'name' ) ? $data['members_display_name'] : $data['g_title'], $moderatorWord, $this->implodeWithProperGrammar( $notCurrentlyAMod ), $this->implodeWithProperGrammar( $_fs ) );

	$IPBHTML .= <<<HTML
<div class='redirector'>
	<div class='info'><a href='{$this->settings['base_url']}app=forums&amp;module=forums&amp;section=moderator&amp;do=add_forum_to_set&amp;mid={$recordId}&amp;forums={$implodedIds}'>{$message}</a></div>
</div>
<br /><br />
HTML;

}

$otherMessage = sprintf( $this->lang->words['mod_add_with_new_perms'], ( $type == 'name' ) ? $data['members_display_name'] : $data['g_title'], $moderatorWord, $this->implodeWithProperGrammar( $notCurrentlyAMod ) );

$IPBHTML .= <<<HTML

<div class='redirector'>
	<div class='info'><a href='{$this->settings['base_url']}app=forums&amp;module=forums&amp;section=moderator&amp;do=add_final&amp;{$qs}&amp;fid={$implodedIds}'>{$otherMessage}</a></div>
</div>
<br /><br />

</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

private function implodeWithProperGrammar( $array )
{
	$imploded = implode( ', ', $array );
	if ( count( $array ) > 1 )
	{
		$imploded = substr_replace( $imploded, $this->lang->words['mod_and'], strrpos( $imploded, ', ' ), 2 );
	}
	
	return $imploded;
}

}