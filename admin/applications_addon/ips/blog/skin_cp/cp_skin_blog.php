<?php
/**
 * @file		cp_skin_blog.php 	IP.Blog main skin templates
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
 * @class		cp_skin_blog
 * @brief		IP.Blog main skin templates
 */
class cp_skin_blog
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
 * Akismet log view
 * 
 * @param	array		$log		Log data
 * @param	string		$entry		Entry data
 * @return	@e string	HTML
 */
public function akismetViewLogEntry( $log, $entry ) {
$IPBHTML = "";
//--starthtml--//

$_entry = $entry['entry_id'] ? "<a href='{$this->settings['board_url']}/index.php?app=blog&module=display&section=blog&blogid={$entry['blog_id']}&showentry={$entry['entry_id']}' target='_blank'>{$entry['entry_name']}</a>" : '--';

$IPBHTML .= <<<HTML
<div class='acp-box'> 
	<h3>{$log['log_msg']}</h3> 

	<table class='ipsTable'>
		<tr> 
			<td>
				<strong>{$this->lang->words['bl_logdate']}:</strong> {$log['_log_date']}
				<br /><strong>{$this->lang->words['bl_logmessage']}:</strong> {$log['log_msg']}
				<br /><strong>{$this->lang->words['bl_entry']}:</strong> {$_entry}
				<br /><strong>{$this->lang->words['bl_communicationerror']}:</strong> {$log['comm']}
				<br /><strong>{$this->lang->words['bl_markedasspam']}:</strong> {$log['spam']}
				<br /><strong>{$this->lang->words['bl_actiontaken']}:</strong> {$log['log_action']}
				<br /><strong>{$this->lang->words['bl_submittoakismet']}:</strong> {$log['submit']}
				<hr>
				<strong>{$this->lang->words['bl_communication errors']}</strong>
				<br /><pre>{$log['errors']}</pre>
				<hr>
				<strong>{$this->lang->words['bl_datarecorded']}</strong>
				<br /><pre>{$log['data']}</pre>
			</td> 
		</tr> 
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Custom theme popup preview
 * 
 * @param	string		$title		Theme title
 * @param	string		$css		Theme CSS
 * @param	integer		$id			Theme ID
 * @return	@e string	HTML
 */
public function memberCustomThemeViewPopup( $title, $css, $id ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='information-box'>{$this->lang->words['bl_csswarning']}</div>
<br />

<table class='ipsTable'>
	<tr>
		<th>{$title} :: {$this->lang->words['bl_customthemecss']}</th>
	</tr>
	<tr> 
		<td><pre>{$css}</pre></td> 
	</tr>
	<tr> 
		<td class='center'><a href='#' onclick='return goToTheme( {$id} );'><span class='button primary'>{$this->lang->words['bl_previewtheme']} {$this->lang->words['_raquo']}</span></a></td> 
	</tr>
</table>
<br />
<script type='text/javascript'> 
	function goToTheme( blogid )
	{
		if( confirm( "{$this->lang->words['bl_cssareyousure']}" ) )
		{
			window.open( "{$this->settings['board_url']}/index.php?app=blog&module=display&section=blog&previewTheme=1&blogid=" + blogid, "mywindow", "status=1,toolbar=1,location=1,menubar=1,resizable=1,scrollbars=1,width=800,height=640" );
		}
		else
		{
			return false;
		}
	}
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Edit custom member theme
 * 
 * @param	integer		$id			Theme ID
 * @param	string		$title		Theme title
 * @param	string		$css		Theme CSS
 * @return	@e string	HTML
 */
public function memberCustomThemeEdit( $id, $title, $css ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='customSave' /> 
	<input type='hidden' name='id' value='{$id}' /> 
	<input type='hidden' name='_admin_auth_key' value='{$this->member->form_hash}' />
	
	<div class='acp-box'> 
		<h3>{$this->lang->words['bl_editcssfor']} {$title}</h3> 
 
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_editcss']}</strong></td>
				<td class='field_field'><textarea name='css' cols='70' rows='30' wrap='soft' id='css' class='input_text'>{$css}</textarea></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_savetheme']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * List of blog custom themes
 * 
 * @param	array		$rows		Themes data
 * @return	@e string	HTML
 */
public function memberCustomThemesList( $rows ) {
$IPBHTML = "";
//--starthtml--//

$onoff = $this->settings['blog_themes_custom'] ? "<span class='ipsBadge badge_green'>{$this->lang->words['cc_enabled']}</span>" : "<span class='ipsBadge badge_red'>{$this->lang->words['cc_disabled']}</span>";
$url   = "{$this->settings['base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;conf_title_keyword=blog";

$text = sprintf( $this->lang->words['bl_cthemes_info'], $onoff, $url );


$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_membercusttheme']}</h2>
</div>
<div class='information-box'>{$text}</div>
<br />

<script type="text/javascript" defer="defer">
	function checkall()
	{
		var formobj = document.getElementById('memberCustomThemesList');
		var checkboxes = formobj.getElementsByTagName('input');
		
		for ( var i = 0 ; i <= checkboxes.length ; i++ )
		{
			var e = checkboxes[i];
			var docheck = formobj.checkme.checked;
			
			if ( e && (e.type == 'checkbox') && (! e.disabled) && (e.id != 'checkme') && (e.name != 'type') )
			{
				if( docheck == false )
				{
					e.checked = false;
				}
				else
				{
					e.checked = true;
				}
			}
		}
		
		return false;
	}
</script>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' id='memberCustomThemesList'>
	<input type='hidden' name='do' value='customHandle' />
	<input type='hidden' name='full' value='1' />
	<input type='hidden' name='_admin_auth_key' value='{$this->member->form_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_membercusttheme']}</h3>

		<table class='ipsTable'>
			<tr>
				<th width='40%'>{$this->lang->words['bl_blog']}</th>
				<th width='20%'>{$this->lang->words['bl_member']}</th>
				<th width='5%' class='center'>{$this->lang->words['bl_suspicious']}</th>
				<th width='5%' class='center'>{$this->lang->words['bl_approved']}</th>
				<th width='10%' class='center'>{$this->lang->words['bl_view']}</th>
				<th width='10%' class='center'>{$this->lang->words['bl_edit']}</th>
				<th width='10%' class='center'><input type='checkbox' onclick='checkall();' id='checkme' /></th>
			</tr>
			<tr>
HTML;

if( is_array( $rows ) && count( $rows ) )
{
	foreach( $rows as $r )
	{
		$_suspicious = $r['_suspicious'] ? "<img src='{$this->settings['skin_acp_url']}/images/icons/exclamation.png' />" : '-';
		$_approved	 = $r['_approved'] ? 'tick' : 'cross';
		
		$IPBHTML .= <<<HTML
			<tr>
				<td><a href='{$this->settings['board_url']}/index.php?app=blog&module=display&section=blog&blogid={$r['blog_id']}' target='_blank'>{$r['blog_name']}</a></td>
				<td><a href='{$this->settings['board_url']}/index.php?showuser={$r['member_id']}' target='_blank'>{$r['members_display_name']}</a></td>
				<td class='center'>{$_suspicious}</td>
				<td class='center'><img src='{$this->settings['skin_acp_url']}/images/icons/{$_approved}.png' /></td>
				<td class='center'><a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}{$this->form_code_js}&do=customView&id={$r['blog_id']}",550,500,"{$r['blog_id']}");'><span class='ipsBadge badge_purple'>{$this->lang->words['bl_viewcss']}</span></a></td>
				<td class='center'><a href='{$this->settings['base_url']}{$this->form_code_js}&do=customEdit&id={$r['blog_id']}'><span class='ipsBadge badge_green'>{$this->lang->words['bl_editcss']}</span></a></td>
				<td class='center'><input type='checkbox' class='checkbox' name='id_{$r['blog_id']}' value='1' /></td>
			</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
				<td colspan='7' class='no_messages'>{$this->lang->words['bl_nothemesawait']}</td>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<div class='rightaction'>
				{$this->lang->words['bl_withselected']}: 
				<select name='action'>
					<option value='clear'>{$this->lang->words['bl_removetheme']}</option>
					<option value='approve'>{$this->lang->words['bl_approvetheme']}</option>							
				</select>&nbsp;
				<input type='submit' value='{$this->lang->words['bl_go']} {$this->lang->words['_raquo']}' class='button primary' />				
			</div>
		</div>
	</div>
</form>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a group blog
 * 
 * @param	string		$code		Form action
 * @param	string		$button		Button value
 * @param	array		$blog		Blog data
 * @return	@e string	HTML
 */
public function blogGroupForm( $code, $button, $blog ) {
$IPBHTML = "";
//--starthtml--//

$_m_groups = array();

foreach( ipsRegistry::cache()->getCache('group_cache') as $id => $data )
{
	if ( $id != $this->settings['guest_group'] )
	{
		$_m_groups[] = array( $data['g_id'], $data['g_title'] );
	}
}

$form['blog_group_ids']      = ipsRegistry::getClass('output')->formMultiDropdown( "blog_group_ids[]", $_m_groups, explode( ',', IPSText::cleanPermString( $blog['blog_groupblog_ids'] ) ), 8 );
$form['blog_name']           = ipsRegistry::getClass('output')->formInput( "blog_name", ( $_POST['blog_name'] ) ? $_POST['blog_name'] : $blog['blog_name'] );
$form['blog_desc']           = ipsRegistry::getClass('output')->formInput( "blog_desc", ( $_POST['blog_desc'] ) ? $_POST['blog_desc'] : $blog['blog_desc'] );
$form['blog_groupblog_name'] = ipsRegistry::getClass('output')->formInput( "blog_groupblog_name", ( $_POST['blog_groupblog_name'] ) ? $_POST['blog_groupblog_name'] : $blog['blog_groupblog_name'] );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$button}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$code}' />
	<input type='hidden' name='blogid' value='{$blog['blog_id']}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->member->form_hash}' />
	
	<div class='acp-box'>
		<h3>{$button}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_blogname']}</strong></td>
				<td class='field_field'>{$form['blog_name']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_blogdescription']}</strong></td>
				<td class='field_field'>{$form['blog_desc']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_bloggroupname']}</strong></td>
				<td class='field_field'>{$form['blog_groupblog_name']}<br /><span class='desctext'>{$this->lang->words['bl_bloggroupname_desc']}</span></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_blogsetgroups']}</strong></td>
				<td class='field_field'>{$form['blog_group_ids']}<br /><span class='desctext'>{$this->lang->words['bl_blogsetgroups_desc']}</span></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Blogs index screen
 * 
 * @param	array		$groupBlogs		Groups blog data
 * @param	array		$mods			Moderators data
 * @return	@e string	HTML
 */
public function blogManager( $groupBlogs=array(), $mods=array() ) {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['group_blogs_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=addGroupBlog'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['add_new_group_blog']}</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['group_blogs_title']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='2%'>&nbsp;</th>
			<th width='30%'>{$this->lang->words['bl_blog']}</th>
			<th width='7%' class='center'>{$this->lang->words['bl_entries']}</th>
			<th width='7%' class='center'>{$this->lang->words['bl_views']}</th>
			<th width='45%'>{$this->lang->words['blog_assigned_groups']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( is_array( $groupBlogs ) AND count( $groupBlogs ) )
{
	foreach( $groupBlogs as $gid => $data )
	{
		$gids  = explode( ',', IPSText::cleanPermString( $data['blog_groupblog_ids'] ) );
		$names = array();
		
		if ( is_array( $gids ) )
		{
			foreach( $gids as $gid )
			{
				$names[] = IPSMember::makeNameFormatted( $this->caches['group_cache'][ $gid ]['g_title'], $gid );
			}
		}
		
		$_name = implode( ', ', $names );
		
		$IPBHTML .= <<<HTML
			<tr class='ipsControlRow'>
				<td><img src='{$this->settings['skin_app_url']}/images/blog_group.png' alt='' /></td>
				<td>
					<a href='{$this->settings['base_url']}{$this->form_code}do=editGroupBlog&blogid={$data['blog_id']}'><strong>{$data['blog_name']}</strong></a>
					<div class='desctext'>{$data['blog_desc']}</div>
				</td>
				<td class='center'>{$data['blog_num_entries']}</td>
				<td class='center'>{$data['blog_num_views']}</td>
				<td>{$_name}</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=editGroupBlog&blogid={$data['blog_id']}' title='{$this->lang->words['bl_editblog']}'>{$this->lang->words['bl_editblog']}</a></li>
						<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=deleteGroupBlog&blogid={$data['blog_id']}");' title='{$this->lang->words['bl_deleteblog']}'>{$this->lang->words['bl_deleteblog']}</a></li>
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
			<td colspan='4' class='no_messages'>{$this->lang->words['no_group_blogs_yet']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
<br />

<div class='section_title'>
	<h2>{$this->lang->words['edit_blogs_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['edit_blogs_title']}</h3>

	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bl_findbybname']}</strong></td>
			<td class='field_field'>
				<form action='{$this->settings['base_url']}module=blogs&amp;section=manage' method='post' name='SearchBlogForm'>
					<input type='hidden' name='do' value='searchblog' />
					<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
					<input type='text' class='textinput' name='blogname' id='blog_name_search'>
					<input type='submit' value='{$this->lang->words['bl_findblog']}' id='button' class='button primary' />
				</form>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bl_findbymname']}</strong></td>
			<td class='field_field'>
				<form action='{$this->settings['base_url']}module=blogs&amp;section=manage' method='post' name='SearchBlogForm'>
					<input type='hidden' name='do' value='searchblog' />
					<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
					<input type='text' class='textinput' name='membername' id='member_name_search'>
					<input type='submit' value='{$this->lang->words['bl_findblog']}' id='button' class='button primary' />
				</form>	
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bl_findbydname']}</strong></td>
			<td class='field_field'>
				<form action='{$this->settings['base_url']}module=blogs&amp;section=manage' method='post' name='SearchBlogForm'>
					<input type='hidden' name='do' value='searchblog' />
					<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
					<input type='text' class='textinput' name='memberdisplayname' id='display_name_search'>
					<input type='submit' value='{$this->lang->words['bl_findblog']}' id='button' class='button primary' />
				</form>
			</td>
		</tr>
	</table>
</div>
<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var mem_search = new ipb.Autocomplete( $('member_name_search'), { 
																	multibox: false, 
																	url: ipb.vars['front_url'] + 'app=blog&secure_key='+ipb.vars['md5_hash']+'&&module=ajax&section=findblogs&do=get-by-member-name&name=', 
																	templates: { 
																					wrap: acp.autocompleteWrap, 
																					item: acp.autocompleteItem 
																				} 
																} 
									);
	
	var display_search = new ipb.Autocomplete( $('display_name_search'), { 
																	multibox: false, 
																	url: ipb.vars['front_url'] + 'app=blog&secure_key='+ipb.vars['md5_hash']+'&module=ajax&section=findblogs&do=get-by-display-name&name=', 
																	templates: { 
																					wrap: acp.autocompleteWrap, 
																					item: acp.autocompleteItem 
																				} 
																} 
									);
	
	var blog_search = new ipb.Autocomplete( $('blog_name_search'), { 
																	multibox: false, 
																	url: ipb.vars['front_url'] + 'app=blog&secure_key='+ipb.vars['md5_hash']+'&module=ajax&section=findblogs&do=get-by-blog-name&name=', 
																	templates: { 
																					wrap: acp.autocompleteWrap, 
																					item: acp.autocompleteItem 
																				} 
																} 
									);
});	
</script>
<br /><br />

<div class='section_title'>
	<h2>{$this->lang->words['bl_modoverview']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=addmodone&&amp;mod_type=member'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['blog_add_member']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=addmodone&amp;mod_type=group'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['blog_add_group']}</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['bl_modoverview']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='95%'>{$this->lang->words['bl_moderator']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if( is_array( $mods ) && count( $mods ) )
{
	foreach( $mods as $r )
	{
		$img   = ( $r['moderate_type'] == 'group' ) ? 'group' : 'user';
		$_name = ( $r['moderate_type'] == 'group' ) ? "{$this->lang->words['bl_group']}: {$r['g_title']}" : $r['members_display_name'];
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td width='1%'><img src='{$this->settings['skin_acp_url']}/images/icons/{$img}.png' /></td>
			<td width='98%'><strong>{$_name}</strong></td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=editmod&modid={$r['moderate_id']}' title='{$this->lang->words['bl_edit']}'>{$this->lang->words['bl_edit']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=dodelmod&modid={$r['moderate_id']}");' title='{$this->lang->words['bl_delete']}'>{$this->lang->words['bl_delete']}</a></li>
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
			<td colspan='4' class='no_messages'>{$this->lang->words['no_blogs_moderators_yet']}</td>
		</tr>
HTML;
}
$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Edit Blog Form
 * 
 * @param	integer		$id			Blog ID
 * @param	array		$form		Form data
 * @param	array		$errors		Errors data
 * @param	$blog		$blog		Blog data
 * @return	@e string	HTML
 */
public function blogEditForm( $id, $form, $errors, $blog ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_editblog']}: {$blog['blog_name']} (ID: {$blog['blog_id']})</h2>
</div>
HTML;

if( is_array( $errors ) && count( $errors ) )
{
	$IPBHTML .= <<<HTML
	<div class='warning'>
HTML;
	
	$IPBHTML .= implode( '<br />', $errors);
	
	$IPBHTML .= <<<HTML
	</div>
	<br />
HTML;
}

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doeditblog' />
	<input type='hidden' name='blogid' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->member->form_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_bloginformation']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_blogname']}</strong></td>
				<td class='field_field'>{$form['blog_name']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_blogdescription']}</strong></td>
				<td class='field_field'>{$form['blog_desc']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_blogtype']}</strong></td>
				<td class='field_field'>{$form['blog_type']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_externallink']}</strong></td>
				<td class='field_field'>{$form['blog_exturl']}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_editthisblog']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Blog Search Results
 * 
 * @param	array		$rows		Results data
 * @param	integer		$total		Number of results
 * @param	string		$pages		Pagination
 * @return	@e string	HTML
 */
public function blogSearchResults( $rows, $total, $pages ) {
$IPBHTML = "";
//--starthtml--//

$resultstotal = sprintf( $this->lang->words['bl_searchresults'], $total );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bm_title_search']}</h2>
</div>

<div class='acp-box'>
	<h3>{$resultstotal}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='2%'>&nbsp;</th>
			<th width='50%'>{$this->lang->words['bl_blog']}</th>
			<th width='10%' class='center'>{$this->lang->words['bl_entries']}</th>
			<th width='10%' class='center'>{$this->lang->words['bl_views']}</th>
			<th width='20%'>{$this->lang->words['bl_owner']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( is_array( $rows ) AND count( $rows ) )
{
	foreach( $rows as $data )
	{
		$IPBHTML .= <<<HTML
			<tr class='ipsControlRow'>
				<td><img src='{$this->settings['skin_app_url']}/images/blog.png' alt='' /></td>
				<td>
					<a href='{$this->settings['base_url']}{$this->form_code}do=editblog&blogid={$data['blog_id']}'><strong>{$data['blog_name']}</strong></a>
					<div class='desctext'>{$data['blog_desc']}</div>
				</td>
				<td class='center'>{$data['blog_num_entries']}</td>
				<td class='center'>{$data['blog_num_views']}</td>
				<td><a href='{$this->settings['base_url']}app=members&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$data['member_id']}' target='blank'>{$data['members_display_name']}</a></td>
				<td>
					<ul class='ipsControlStrip'>
						<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=editblog&blogid={$data['blog_id']}' title='{$this->lang->words['bl_editblog']}'>{$this->lang->words['bl_editblog']}</a></li>
						<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=do_deleteblog&blogid={$data['blog_id']}");' title='{$this->lang->words['bl_deleteblog']}'>{$this->lang->words['bl_deleteblog']}</a></li>
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
			<td colspan='4' class='no_messages'>{$this->lang->words['nogroupblogssetup']}</td>
		</tr>
HTML;
}
	
$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
		<div class='rightaction'>
			{$pages}
		</dsiv>
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit ping services
 * 
 * @param	integer		$id			Service ID
 * @param	string		$do			Form action
 * @param	array		$form		Form data
 * @param	array		$service	Service data
 * @return	@e string	HTML
 */
public function pingServiceForm( $id, $do, $form, $service ) {
$IPBHTML = "";
//--starthtml--//

if( $do == 'doeditservice' )
{
	$title	= $this->lang->words['bl_pingsetfor'] . $service['blog_service_name'];
}
else
{
	$title	= $this->lang->words['bl_newpingservset'];
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='service_id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_pingsettings']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_pingkey']}</strong></td>
				<td class='field_field'>{$form['blog_service_key']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_pingname']}</strong></td>
				<td class='field_field'>{$form['blog_service_name']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_pinghost']}</strong></td>
				<td class='field_field'>{$form['blog_service_host']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_pingport']}</strong></td>
				<td class='field_field'>{$form['blog_service_port']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_pingpath']}</strong></td>
				<td class='field_field'>{$form['blog_service_path']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_pingmethodname']}</strong></td>
				<td class='field_field'>{$form['blog_service_methodname']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_pingextended']}</strong></td>
				<td class='field_field'>{$form['blog_service_extended']}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_savechanges']}' class='button primary' accesskey='s'>
		</div>
	</ul>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}
	
/**
 * Tools list
 * 
 * @return	@e string	HTML
 */
public function toolsOverview() {
$IPBHTML = "";
//--starthtml--//

$_pergo50 = $this->registry->output->formSimpleInput( 'pergo', 50 ) . "&nbsp;{$this->lang->words['bl_percycle']}";
$_pergo25 = $this->registry->output->formSimpleInput( 'pergo', 25 ) . "&nbsp;{$this->lang->words['bl_percycle']}";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_blogtools']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doresyncentries' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_resynchent']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_resynchent']}</strong></td>
				<td class='field_field'>{$_pergo50}<br /><span class='desctext'>{$this->lang->words['bl_resynchent_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_resynchent']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doresyncblogs' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_resynchblogs']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_resynchblogs']}</strong></td>
				<td class='field_field'>{$_pergo50}<br /><span class='desctext'>{$this->lang->words['bl_resynchblogs_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_resynchblogs']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dorebuildthumbs' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_rebuildthumbs']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_rebuildthumbs']}</strong></td>
				<td class='field_field'>{$_pergo25}<br /><span class='desctext'>{$this->lang->words['bl_rebuildthumbs_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_rebuildthumbs']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dorebuildstats' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_rebuildstats']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_rebuildstats']}</strong></td>
				<td class='field_field'><span class='desctext'>{$this->lang->words['bl_rebuildstats_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_rebuildstats']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dorefreshrss' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_refreshrss']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_refreshrss']}</strong></td>
				<td class='field_field'><span class='desctext'>{$this->lang->words['bl_refreshrss_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_refreshrss']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doconvertattach' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_convertbb']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_convertbb']}</strong></td>
				<td class='field_field'>{$_pergo50}<br /><span class='desctext'>{$this->lang->words['bl_convertbb_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_convertbb']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Akismet logs list
 * 
 * @param	array		$logs			Logs data
 * @param	array		$$type_show		Filters dropdown
 * @param	string		$pages			Pagination
 * @return	@e string	HTML
 */
public function akismetLogsOverview( $logs, $type_show='', $pages='' ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_akismetlogs']}</h2>
</div>

{$pages}
<br class='clear' />
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' id='akismetLogsOverview'>
	<input type='hidden' name='do' value='remove' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_akismetlogs']}</h3>
		<table class='ipsTable'>
			<tr>
				<th width='1%' class='center'><input type='checkbox' onclick='checkall();' id='checkme' /></th>
				<th width='5%'>{$this->lang->words['bl_type']}</th>
				<th width='20%'>{$this->lang->words['bl_date']}</th>
				<th width='44%'>{$this->lang->words['bl_message']}</th>
				<th width='10%' class='center'>{$this->lang->words['bl_connecterror']}</th>
				<th width='10%' class='center'>{$this->lang->words['bl_isspam']}</th>
				<th width='10%' class='center'>{$this->lang->words['bl_submit']}</th>
			</tr>
HTML;

if( is_array( $logs ) && count( $logs ) )
{
	foreach( $logs as $r )
	{
		$_checkbox = $this->registry->output->formCheckbox( "id_{$r['log_id']}" );
		
		$IPBHTML .= <<<HTML
			<tr>
				<td class='center'>{$_checkbox}</td>
				<td>{$r['log_type']}</td>
				<td>{$r['_log_date']}</td>
				<td><a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}{$this->form_code_js}do=viewlog&id={$r['log_id']}",700,400,"{$this->lang->words['bl_log']}");' title='{$this->lang->words['bl_moredata']}'>{$r['log_msg']}</a></td>
				<td class='center'>{$r['comm']}</td>
				<td class='center'>{$r['spam']}</td>
				<td class='center'>{$r['submit']}</td>
			</tr>
HTML;
	}
}
else
{
$IPBHTML .= <<<HTML
			<tr>
				<td colspan='7' class='no_messages center'>{$this->lang->words['bl_noresults']}</td>
			</tr>
HTML;
}
$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<div class='leftaction'>
				<input type="submit" value="{$this->lang->words['bl_removedchecked']}" class="button primary" />&nbsp;
				<input type="checkbox" id="checkbox" name="type" value="all" />&nbsp;{$this->lang->words['bl_removeall']}
			</div>
		</div>
	</div>
	<script type="text/javascript" defer="defer">
	function checkall()
	{
		var formobj = document.getElementById('akismetLogsOverview');
		var checkboxes = formobj.getElementsByTagName('input');
		
		for ( var i = 0 ; i <= checkboxes.length ; i++ )
		{
			var e = checkboxes[i];
			var docheck = formobj.checkme.checked;
			
			if ( e && (e.type == 'checkbox') && (! e.disabled) && (e.id != 'checkme') && (e.name != 'type') )
			{
				if( docheck == false )
				{
					e.checked = false;
				}
				else
				{
					e.checked = true;
				}
			}
		}
		
		return false;
	}
</script>
</form>

<br class='clear' />
{$pages}
<br class='clear' /><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='list' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_filterlogs']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_onlyshow']}</strong></td>
				<td class='field_field'>{$type_show}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_search']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Theme Form
 * 
 * @param	integer		$id		Theme ID
 * @param	string		$do		Form action
 * @param	array		$form	Form data
 * @return	@e string	HTML
 */
public function themeForm( $id, $do, $form ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_configtheme']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_themename']}</strong></td>
				<td class='field_field'>{$form['theme_name']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_themedesc']}</strong></td>
				<td class='field_field'>{$form['theme_desc']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_themeen']}</strong></td>
				<td class='field_field'>{$form['theme_on']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_themeauthor']}</strong></td>
				<td class='field_field'>{$form['theme_author']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_themehomepage']}</strong></td>
				<td class='field_field'>{$form['theme_homepage']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_themeemail']}</strong></td>
				<td class='field_field'>{$form['theme_email']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_themeimg']}</strong></td>
				<td class='field_field'>{$form['theme_images']}<br /><span class='desctext'>{$this->lang->words['bl_themeimg_info']}</span></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_themecssoverwrite']}</strong></td>
				<td class='field_field'>{$form['theme_css_overwrite']}<br /><span class='desctext'>{$this->lang->words['bl_themecssoverwritedesc']}</span></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_themecss']}</strong></td>
				<td class='field_field'>{$form['theme_css']}<br /><span class='desctext'>{$this->lang->words['bl_themecss_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_savetheme']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Moderator Form
 * 
 * @param	integer		$mod_id		Moderator ID
 * @param	integer		$mg_id		Member/Group ID
 * @param	string		$button		Button title
 * @param	array		$form		Form data
 * @return	@e string	HTML
 */
public function moderatorForm( $mod_id, $mg_id, $do, $button, $form ) {
$IPBHTML = "";
//--starthtml--//

if( $do == 'doaddmod' )
{
	if( $this->request['mod_type'] == 'group' )
	{
		$title	= $this->lang->words['bl_addmodgroup'];
	}
	else
	{
		$title	= $this->lang->words['bl_addmod'];
	}
}
else
{
	$title	= $this->lang->words['bl_editingmod'];
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='modid' value='{$mod_id}' />
	<input type='hidden' name='mod_type' value='{$this->request['mod_type']}' />
	<input type='hidden' name='mgid' value='{$mg_id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>{$this->lang->words['bl_modblogsettings']}</th>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_canpin']}</strong></td>
				<td class='field_field'>{$form['moderate_can_pin']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_candisable']}</strong></td>
				<td class='field_field'>{$form['moderate_can_disable']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_caneditccb']}</strong></td>
				<td class='field_field'>{$form['moderate_can_editcblocks']}</td>
			</tr>
			<tr>
				<th colspan='2'>{$this->lang->words['bl_entryoptions']}</th>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_editothercomment']}</strong></td>
				<td class='field_field'>{$form['moderate_can_edit_comments']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_editotherentry']}</strong></td>
				<td class='field_field'>{$form['moderate_can_edit_entries']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_delothercomment']}</strong></td>
				<td class='field_field'>{$form['moderate_can_del_comments']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_delotherentry']}</strong></td>
				<td class='field_field'>{$form['moderate_can_del_entries']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_publishentries']}</strong></td>
				<td class='field_field'>{$form['moderate_can_publish']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_lockentries']}</strong></td>
				<td class='field_field'>{$form['moderate_can_lock']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_approvecomments']}</strong></td>
				<td class='field_field'>{$form['moderate_can_approve']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_deletetrack']}</strong></td>
				<td class='field_field'>{$form['moderate_can_del_trackback']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_viewdraft']}</strong></td>
				<td class='field_field'>{$form['moderate_can_view_draft']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_viewprivate']}</strong></td>
				<td class='field_field'>{$form['moderate_can_view_private']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_canwarn']}</strong></td>
				<td class='field_field'>{$form['moderate_can_warn']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_canfeature']}</strong></td>
				<td class='field_field'>{$form['moderator_can_feature']}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member Moderator Add Form (Step two)
 * 
 * @params	string		$members		Dropdown of found members
 * @return	@e string	HTML
 */
public function moderatorChooseMember( $members ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_addmod']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='addmodfinal' />
	<input type='hidden' name='mod_type' value='{$this->request['mod_type']}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_searchformember']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_choosematches']}</strong></td>
				<td class='field_field'>{$members}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_choosemember']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Group Moderator Add Form
 * 
 * @params	string		$members		Dropdown of available groups
 * @return	@e string	HTML
 */
public function moderatorGroupAddOne( $groups ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_addmod']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='addmodtwo' />
	<input type='hidden' name='mod_type' value='{$this->request['mod_type']}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_choosegroup']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_selectgroup']}</strong></td>
				<td class='field_field'>{$groups}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_addthisgroup']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>		
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member Moderator Add Form (Step one)
 * 
 * @return	@e string	HTML
 */
public function moderatorMemberAddOne() {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_addmod']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='addmodtwo' />
	<input type='hidden' name='mod_type' value='{$this->request['mod_type']}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_searchformember']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_enterpartorall']}</strong></td>
				<td class='field_field'><input type='text' name='USER_NAME' id='USER_NAME' value='' size='30' class='textinput' /></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_findmember']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var memberAutocomplete = new ipb.Autocomplete( $('USER_NAME'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});	
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Content block form
 * 
 * @param	integer		$id			Content block ID
 * @param	string		$do			Form action
 * @param	string		$title		Form title
 * @param	array		$form		Form data
 * @return	@e string	HTML
 */
public function cBlockForm( $id, $do, $title, $form ) {
$IPBHTML = "";
//--starthtml--//

if( $do == 'doaddcblock' )
{
	$title	= $this->lang->words['bl_addcb'];
}
else
{
	$title	= $this->lang->words['bl_editcb'];
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='information-box'>{$this->lang->words['bl_cb_info']}</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='cbdef_id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
	
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_cb_name']}</strong></td>
				<td class='field_field'>{$form['cblock_name']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_content']}</strong></td>
				<td class='field_field'>{$form['cblock_content']}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$title}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Content block list
 * 
 * @param	array		$cblocks		Content blocks installed
 * @param	string		$uninstalled	Content blocks that can be installed
 * @return	@e string	HTML
 */
public function cBlockList( $cblocks, $uninstalled ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_cbmanager']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=addcblock'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' />{$this->lang->words['bl_addnewcb']}</a>
			</li>
		</ul>
	</div>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='docblocks' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['bl_cboverview']}</h3>
		<table class='ipsTable' id='cblocks_installed'>
HTML;

foreach( $cblocks as $r )
{
$IPBHTML .= <<<HTML
			<tr class='ipsControlRow isDraggable' id='cblocks_{$r['cbdef_id']}'>
				<td class='col_drag'>
					<span class='draghandle'>&nbsp;</span>
				</td>
				<td width='70%'><strong>{$r['cbdef_name']}</strong></td>
				<td width='10%'>{$this->lang->words['bl_default']}:&nbsp;&nbsp;{$r['_default']}</td>
				<td width='10%'>{$this->lang->words['bl_enabled']}:&nbsp;&nbsp;{$r['_enabled']}</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
HTML;

	if( substr( $r['cbdef_function'], 0, 15 ) == 'get_admin_block' )
	{
$IPBHTML .= <<<HTML
 						<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=editcblock&id={$r['cbdef_id']}' title='{$this->lang->words['bl_edit']}'>{$this->lang->words['bl_edit']}</a></li>
						<li class='i_unlock'><a href='#' onclick='return acp.confirmDelete( "{$this->settings['base_url']}{$this->form_code}do=dodelcblock&id={$r['cbdef_id']}" )' title='{$this->lang->words['bl_delete']}'>{$this->lang->words['bl_delete']}</a></li>
HTML;
	}

$IPBHTML .= <<<HTML
						<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=uninstallcblock&block={$r['cbdef_function']}"); return false;' title='{$this->lang->words['bl_uninstall']}'>{$this->lang->words['bl_uninstall']}</a></li>
					</ul>
				</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['bl_savecbsettings']}' class='button primary' accesskey='s'>
		</div>
	</div>
	
	<script type='text/javascript'>
		jQ("#cblocks_installed").ipsSortable('table', { 
			url: "{$this->settings['base_url']}{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ),
			serializeOptions: { key: 'cblocks[]' }
		} );
	</script>
</form>
<br />

<div class='section_title'>
	<h2>{$this->lang->words['bl_cbplugins']}</h2>
</div>

<div class='information-box'>{$this->lang->words['bl_cbplugins_info']}</div>
<br />
HTML;

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['bl_uninstalledplugins']}</h3>
	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['bl_plugin']}</th>
			<th class='center'>{$this->lang->words['bl_install']}</th>
		</tr>
HTML;

if( is_array( $uninstalled ) && count( $uninstalled ) )
{
	foreach( $uninstalled as $r )
	{
$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$r['config']['name']}</strong><br /><span class='desctext'>{$r['config']['desc']}</span></td>
			<td class='center'><a href='{$this->settings['base_url']}{$this->form_code}do=installcblock&block={$r['block']}'><span class='ipsBadge badge_purple'>{$this->lang->words['bl_install']}</span></a></td>
		</tr>
HTML;
	}
}
else
{
$IPBHTML .= <<<HTML
		<tr>
			<td class='center no_messages' colspan='2'>{$this->lang->words['cm_no_uninstalled']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Blog Overview
 * 
 * @param	array		$stats		Statistics data
 * @param	array		$versions	Versions data
 * @param	array		$approve	Custom themes to approve
 * @return	@e string	HTML
 */
public function blogOverview( $stats, $versions, $approve ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['cp_welcome_dude']}</h2>
</div>

<table width='100%'>
	<tr>
		<td width='40%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['bl_communityblogstats']}</h3>
				<table class='ipsTable'>
					<tr>
						<th>{$this->lang->words['bl_numofblogs']}</th>
						<th>{$stats['num_blogs']}</th>
					</tr>
					<tr>
						<td>{$this->lang->words['bl_numofpblogs']}</td>
						<td>{$stats['num_private']}</td>
					</tr>
					<tr>
						<td>{$this->lang->words['bl_numofhblogs']}</td>
						<td>{$stats['num_local']}</td>
					</tr>
					<tr>
						<td>{$this->lang->words['bl_numoflblogs']}</td>
						<td>{$stats['num_external']}</td>
					</tr>
					
					<tr>
						<th>{$this->lang->words['bl_totalpentries']}</th>
						<th>{$stats['total_entries']}</th>
					</tr>
					<tr>
						<td>{$this->lang->words['bl_totaldentries']}</td>
						<td>{$stats['total_drafts']}</td>
					</tr>
					<tr>
						<td>{$this->lang->words['bl_totalcomments']}</td>
						<td>{$stats['total_comments']}</td>
					</tr>
				</table>
			</div>
			<br />
			<div class='acp-box'>
				<h3>{$this->lang->words['bl_bloghistory']}</h3>
				<table class='ipsTable'>
HTML;

foreach( $versions as $r )
{
	$IPBHTML .= <<<HTML
					<tr>
						<td width='50%'><strong class='title'>{$r['upgrade_version_human']}</strong> ({$r['upgrade_version_id']})</td>
						<td width='50%'>{$r['upgrade_date']}</td>
					</tr>
HTML;
}

$IPBHTML .= <<<HTML
				</table>
			</div>
		</td>
		<td width='1%'>&nbsp;</td>
		<td width='59%' valign='top'>
HTML;

if ( $this->settings['blog_themes'] )
{
$IPBHTML .= <<<HTML
			<form action='{$this->settings['base_url']}module=customize&amp;section=custom' method='post' id='blogThemesModerate'>
				<input type='hidden' name='do' value='customHandle' />
				<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
				
				<div class='acp-box'>
					<h3>{$this->lang->words['bl_themesawaiting']}</h3>
			
					<table class='ipsTable'>
						<tr>
							<th width='40%'>{$this->lang->words['bl_blog']}</th>
							<th width='30%'>{$this->lang->words['bl_member']}</th>
							<th width='10%' class='center'>{$this->lang->words['bl_suspicious']}</th>
							<th width='10%' class='center'>{$this->lang->words['bl_view']}</th>
							<th width='1%'><input type='checkbox' onclick='checkall();' id='checkme' /></th>
						</tr>
HTML;

			if( is_array( $approve ) && count( $approve ) )
			{
				foreach( $approve as $r )
				{
					$_suspicious = $r['_suspicious'] ? "<img src='{$this->settings['skin_acp_url']}/images/icons/exclamation.png' alt='' />" : '';
					
					$IPBHTML .= <<<HTML
						<tr>
							<td width='40%'><a href='{$this->settings['board_url']}/index.php?app=blog&amp;module=display&amp;section=blog&amp;blogid={$r['blog_id']}' target='_blank'>{$r['blog_name']}</a></td>
							<td width='40%'><a href='{$this->settings['board_url']}/index.php?showuser={$r['member_id']}' target='_blank'>{$r['members_display_name']}</a></td>
							<td width='10%' class='center'>{$_suspicious}</td>
							<td width='5%' class='center'><a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}module=customize&section=custom&do=customView&id={$r['blog_id']}",550,500,"{$r['blog_id']}");' title='{$this->lang->words['bl_viewcss']}'><img src='{$this->settings['skin_acp_url']}/images/icons/page_white_magnify.png' alt='' /></a></td>
							<td width='1%'><input type='checkbox' class='checkbox' name='id_{$r['blog_id']}' value='1' /></td>
						</tr>
HTML;
				}
			}

	$IPBHTML .= <<<HTML
					</table>
					<div class='acp-actionbar'>
						<div class='rightaction'>
							{$this->lang->words['bl_withselected']}: <select name='action'><option value='clear'>{$this->lang->words['bl_removetheme']}</option><option value='approve'>{$this->lang->words['bl_approvetheme']}</option></select>&nbsp;
									<input type='submit' value='{$this->lang->words['bl_go']} {$this->lang->words['_raquo']}' class='button primary' />&nbsp;{$this->lang->words['bl_or']}&nbsp;
									<a href='{$this->settings['base_url']}module=customize&amp;section=custom&amp;do=custom'>{$this->lang->words['bl_viewall']} {$this->lang->words['_raquo']}</a>
						</div>
					</div>
				</div>
			</form>
			<script type="text/javascript" defer="defer">
				function checkall()
				{
					var formobj    = document.getElementById('blogThemesModerate');
					var checkboxes = formobj.getElementsByTagName('input');
					
					for ( var i = 0 ; i <= checkboxes.length ; i++ )
					{
						var e = checkboxes[i];
						var docheck = formobj.checkme.checked;
						
						if ( e && (e.type == 'checkbox') && (! e.disabled) && (e.id != 'checkme') && (e.name != 'type') )
						{
							if( docheck == false )
							{
								e.checked = false;
							}
							else
							{
								e.checked = true;
							}
						}
					}
					
					return false;
				}
			</script>
			<br />
HTML;
}

$IPBHTML .= <<<HTML
			<div class='acp-box'>
				<h3>{$this->lang->words['bl_groupoverview']}</h3>
				<table class='ipsTable'>
HTML;

if( is_array( $this->caches['group_cache'] ) && count( $this->caches['group_cache'] ) )
{
	foreach( $this->caches['group_cache'] as $r )
	{
		$r['g_blog_settings'] = unserialize( $r['g_blog_settings'] );
		$r['g_title']         = IPSMember::makeNameFormatted( $r['g_title'], $r['g_id'] );
		$r['_noSetUp']        = ( ( ! is_array( $r['g_blog_settings'] ) OR ! count( $r['g_blog_settings'] ) ) AND ( ! $r['g_blog_settings']['g_blog_allowcreate'] OR ! $r['g_blog_settings']['g_blog_allowview'] ) ) ? 1 : 0;
		$r['_setUp']		  = ( $r['_noSetUp'] ) ? "&nbsp;<span class='desctex'>{$this->lang->words['overview_g_nosetup']}</span>" : '';
		
		$IPBHTML .= <<<HTML
					<tr class='ipsControlRow'>
						<td width='95%'>{$r['g_title']}{$r['_setUp']}</td>
						<td class='col_buttons'>
							<ul class='ipsControlStrip'>
								<li class='i_edit'><a href='{$this->settings['base_url']}app=members&amp;module=groups&amp;section=groups&amp;do=edit&amp;id={$r['g_id']}&amp;_initTab=blog' title='{$this->lang->words['bl_edit']}'>{$this->lang->words['bl_edit']}</a></li>
							</ul>
						</td>
					</tr>
HTML;
	}
}
$IPBHTML .= <<<HTML
				</table>
			</div>
		</td>
	</tr>
</table>
<br clear='both'>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Themes overview
 * 
 * @param	array		$themes		Themes data
 * @return	@e string	HTML
 */
public function themes_overview( $themes ) {

$onoff = $this->settings['blog_themes'] ? "<span class='ipsBadge badge_green'>{$this->lang->words['cc_enabled']}</span>" : "<span class='ipsBadge badge_red'>{$this->lang->words['cc_disabled']}</span>";
$url   = "{$this->settings['base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;conf_title_keyword=blog";
$text  = sprintf( $this->lang->words['bl_themes_info'], $onoff, $url );

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['cc_themecustomization']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=themeAdd'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['bl_createnewtheme']}</a>
			</li>
		</ul>
	</div>
</div>
<div class='information-box'>{$text}</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['bl_blogthemes']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='50%'>{$this->lang->words['bl_name']}</th>
			<th width='35%'>{$this->lang->words['bl_author']}</th>
			<th width='5%'>{$this->lang->words['bl_enabled']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( is_array($themes) && count($themes) )
{
	foreach( $themes as $data )
	{
		$_enabled			  = $data['theme_on'] ? 'tick' : 'cross';
		$data['theme_author'] = $data['theme_email'] ? "<a href='mailto:{$data['theme_email']}' title='{$data['theme_email']}'><img src='{$this->settings['skin_acp_url']}/images/icons/email.png' alt='' /></a>&nbsp;&nbsp;" . $data['theme_author'] : '';
		$data['theme_author'] = $data['theme_homepage'] ? "<a href='{$data['theme_homepage']}' title='{$data['theme_homepage']}'><img src='{$this->settings['skin_acp_url']}/images/icons/layout_content.png' alt='' /></a>&nbsp;&nbsp;" . $data['theme_author'] : '';
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td><strong class='title'>{$data['theme_name']}</strong><div class='desctext'>{$data['theme_desc']}</div></td>
			<td>{$data['theme_author']}</td>
			<td class='center'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=themeToggle&amp;id={$data['theme_id']}' title='{$this->lang->words['bl_toggleendis']}'><img src='{$this->settings['skin_acp_url']}/images/icons/{$_enabled}.png' alt='' /></a>
			</td>
			<td class='col buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=themeEdit&amp;id={$data['theme_id']}' title='{$this->lang->words['bl_edittheme']}'>{$this->lang->words['bl_edittheme']}</a></li>
					<li class='i_export'><a href='{$this->settings['base_url']}{$this->form_code}do=themeExport&amp;id={$data['theme_id']}' title='{$this->lang->words['bl_exportthemexml']}'>{$this->lang->words['bl_exportthemexml']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=themeDelete&amp;id={$data['theme_id']}");' title='{$this->lang->words['bl_deletetheme']}'>{$this->lang->words['bl_deletetheme']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br /><br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;req=custom&amp;code=themeImport' enctype='multipart/form-data' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->form_code->_admin_auth_key}' />
	<input type='hidden' name='MAX_FILE_SIZE' value='10000000000' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['theme_import_xml']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_uploadxml']}</strong></td>
				<td class='field_field'><input class='input_text' type='file' size='30' name='FILE_UPLOAD' /><br /><span class='desctext'>{$this->lang->words['bl_uploadxml_info']}</span></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bl_enterxml']}</strong></td>
				<td class='field_field'><input class='input_text' type='text' size='30' name='file_location' /><br /><span class='desctext'>{$this->lang->words['bl_enterxml_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$this->lang->words['bl_import']}' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Pings overview
 * 
 * @param	array		$content		Last trackbacks data
 * @param	array		$services		Ping services data
 * @return	@e string	HTML
 */
public function pingsIndex( $content, $services ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_tbsetup']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=service_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['bl_addservice']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=tblogs'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='' /> {$this->lang->words['tp_view_blockedtrackbacks']}</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['bl_last5tb']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='15%'>{$this->lang->words['bl_trackeddate']}</th>
			<th width='15%'>{$this->lang->words['bl_trackedblog']}</th>
			<th width='15%'>{$this->lang->words['bl_trackedentry']}</th>
			<th width='15%'>{$this->lang->words['bl_tb_blogname']}</th>
			<th width='35%'>{$this->lang->words['bl_tb_details']}</th>
			<th class='col_buttons'>&nbsp;</th>
 		</tr>
HTML;

if ( is_array($content) && count($content) )
{

	foreach( $content as $r )
	{
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>{$r['trackback_date']}</td>
			<td><a href='{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=blog&amp;blogid={$r['blog_id']}' target='_blank'>{$r['blog_name']}</a></td>
			<td><a href='{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=blog&amp;showentry={$r['entry_id']}' target='_blank'>{$r['entry_name']}</a></td>
			<td>{$r['trackback_blog_name']}</td>
			<td>
				<a href='{$r['trackback_url']}' target='_blank'>{$r['trackback_title']}</a> (<a href='#' class='ipbmenu' id='excerpt_{$r['trackback_id']}'>{$this->lang->words['bl_excerptdetails']}</a>)
				<ul class='acp-menu' id='excerpt_{$r['trackback_id']}_menucontent'>
					<li>{$r['trackback_excerpt']}</li>
				</ul>
			</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=restoretb&amp;tbid={$r['trackback_id']}' title='{$this->lang->words['bl_restoretb']}'>{$this->lang->words['bl_restoretb']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=dodeletetb&amp;tbid={$r['trackback_id']}");' title='{$this->lang->words['bl_deletetb']}'>{$this->lang->words['bl_deletetb']}</a></li>
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
			<td colspan='6' class='no_messages center'>{$this->lang->words['tp_no_trackbacks_yet']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['bl_installedservices']}</h3>

	<table class='ipsTable'>
		<tr>
			<th width='85%'>{$this->lang->words['bl_servicename']}</th>
			<th width='10%' class='center'>{$this->lang->words['bl_enabled']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

foreach( $services as $r )
{
	$r['_enabled_img'] = $r['blog_service_enabled'] ? 'tick' : 'cross';
	
	$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>{$r['blog_service_name']}</td>
			<td class='center'><a href='{$this->settings['base_url']}{$this->form_code}do=service_toggle_enabled&amp;service_id={$r['blog_service_id']}' title='{$this->lang->words['bl_toggleendis']}'><img src='{$this->settings['skin_acp_url']}/images/icons/{$r['_enabled_img']}.png' alt='' /></a></td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=service_edit&amp;service_id={$r['blog_service_id']}' title='{$this->lang->words['bl_editservice']}'>{$this->lang->words['bl_editservice']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=dodeleteservice&amp;service_id={$r['blog_service_id']}");' title='{$this->lang->words['bl_deleteservice']}'>{$this->lang->words['bl_deleteservice']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Lists all blocked trackbacks
 * 
 * @param	array		$rows		Trackbacks data
 * @param	string		$pages		Pagination
 * @return	@e string	HTML
 */
public function blockedTrackBacks( $rows, $pages ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bl_blockedtbs']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='#' onclick='return acp.confirmDelete( "{$this->settings['base_url']}{$this->form_code}do=dodeletetb&amp;tbid=all"); return false;'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' /> {$this->lang->words['bl_deleteall']}</a>
			</li>
		</ul>
	</div>	
</div>

<br class='clear' />
{$pages}
<br class='clear' />

<div class='acp-box'>
	<h3>{$this->lang->words['bl_blockedtbs']}</h3>

	<table class='ipsTable'>
		<tr>
			<th width='15%'>{$this->lang->words['bl_trackeddate']}</th>
			<th width='15%'>{$this->lang->words['bl_trackedblog']}</th>
			<th width='15%'>{$this->lang->words['bl_trackedentry']}</th>
			<th width='15%'>{$this->lang->words['bl_tb_blogname']}</th>
			<th width='35%'>{$this->lang->words['bl_tb_details']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

foreach( $rows as $r )
{
	$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td width='15%'>{$r['trackback_date']}</td>
			<td width='15%'><a href='{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=blog&amp;module=display&amp;section=blog&amp;blogid={$r['blog_id']}' target='_blank'>{$r['blog_name']}</a></td>
			<td width='15%'><a href='{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=blog&amp;module=display&amp;section=blog&amp;showentry={$r['entry_id']}' target='_blank'>{$r['entry_name']}</a></td>
			<td width='15%'>{$r['trackback_blog_name']}</td>
			<td width='35%'>
				<a href='{$r['trackback_url']}' target='_blank'>{$r['trackback_title']}</a> (<a href='#' class='ipbmenu' id='excerpt_{$r['trackback_id']}'>{$this->lang->words['bl_excerptdetails']}</a>)
				<ul class='acp-menu' id='excerpt_{$r['trackback_id']}_menucontent'>
					<li>{$r['trackback_excerpt']}</li>
				</ul>
			</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=restoretb&amp;tbid={$r['trackback_id']}' title='{$this->lang->words['bl_restoretb']}'>{$this->lang->words['bl_restoretb']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=dodeletetb&amp;tbid={$r['trackback_id']}");' title='{$this->lang->words['bl_deletetb']}'>{$this->lang->words['bl_deletetb']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br class='clear' />
{$pages}
HTML;

//--endhtml--//
return $IPBHTML;
}

}