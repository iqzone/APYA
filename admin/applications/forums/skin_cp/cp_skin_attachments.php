<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Attachments skin functions
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
 
class cp_skin_attachments
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
 * Display attachment stats
 *
 * @param	array 	Overall stats
 * @param	array 	Last 5 attachments
 * @param	array 	Largest 5 attachments
 * @param	array 	Most viewed 5 attachments
 * @return	string	HTML
 */
public function attachmentStats( $overall_stats, $last_5, $largest_5, $most_viewed_5 )
{
 
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['attach_stats_head']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['atc_overview']}</h3>

	<table class='ipsTable'>
		<tr>
			<td width='30%'>{$this->lang->words['atc_number']}</td>
			<td width='20%'>{$overall_stats['total_attachments']}</td>
			<td width='30%'>{$this->lang->words['atc_diskusage']}</td>
			<td width='20%'>{$overall_stats['total_size']}</td>
		</tr>
	</table>
</div><br />

<form action='{$this->settings['base_url']}&amp;module=attachments&amp;section=search' method='post'>
	<input type='hidden' name='do' value='attach_bulk_remove' />
	<input type='hidden' name='return' value='stats' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['atc_last5']}</h3>

		<table class='ipsTable'>
			<tr>
				<th style='width: 2%'>&nbsp;</th>
				<th style='width: 20%'>{$this->lang->words['atc_attachment']}</th>
				<th style='width: 11%'>{$this->lang->words['atc_size']}</th>
				<th style='width: 15%'>{$this->lang->words['atc_author']}</th>
				<th style='width: 25%'>{$this->lang->words['atc_topic']}</th>
				<th style='width: 25%'>{$this->lang->words['atc_posted']}</th>
				<th style='width: 2%'>&nbsp;</th>
			</tr>
HTML;

foreach( $last_5 as $r )
{
$IPBHTML .= <<<HTML
			<tr>
				<td><img src='{$r['_icon']}' /></td>
				<td><a href='{$this->settings['board_url']}/index.php?app=core&amp;module=attach&amp;section=attach&amp;attach_rel_module=post&amp;attach_id={$r['attach_id']}' target='_blank'>{$r['attach_file']}</a></td>
				<td>{$r['attach_filesize']}</td>
HTML;

if( $r['attach_member_id'] )
{
	$IPBHTML .= <<<HTML
	<td><a href='{$this->settings['board_url']}/index.php?showuser={$r['attach_member_id']}'>{$r['members_display_name']}</a></td>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<td>{$r['members_display_name']}</td>
HTML;
}

$IPBHTML .= <<<HTML
				<td>{$r['stitle']}</td>
				<td>{$r['post_date']}</td>
				<td><div align='center'><input type='checkbox' name='attach_{$r['attach_id']}' value='1' /></div></td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>		
	</div><br />

<div class='acp-box'>
	<h3>{$this->lang->words['atc_largest5']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th>&nbsp;</th>
			<th>{$this->lang->words['atc_attachment']}</th>
			<th>{$this->lang->words['atc_size']}</th>
			<th>{$this->lang->words['atc_author']}</th>
			<th>{$this->lang->words['atc_topic']}</th>
			<th>{$this->lang->words['atc_posted']}</th>
			<th>&nbsp;</td>
		</tr>
HTML;

foreach( $largest_5 as $r )
{
$IPBHTML .= <<<HTML
			<tr>
				<td width='1%'><img src='{$r['_icon']}'/></td>
				<td width='20%'><a href='{$this->settings['board_url']}/index.php?app=core&amp;module=attach&amp;section=attach&amp;attach_rel_module=post&amp;attach_id={$r['attach_id']}' target='_blank'>{$r['attach_file']}</a></td>
				<td width='10%'>{$r['attach_filesize']}</td>
HTML;

if( $r['attach_member_id'] )
{
	$IPBHTML .= <<<HTML
	<td><a href='{$this->settings['board_url']}/index.php?showuser={$r['attach_member_id']}'>{$r['members_display_name']}</a></td>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<td>{$r['members_display_name']}</td>
HTML;
}

$IPBHTML .= <<<HTML
				<td width='25%'>{$r['stitle']}</td>
				<td width='25%'>{$r['post_date']}</td>
				<td width='1%'><div align='center'><input type='checkbox' name='attach_{$r['attach_id']}' value='1' /></div></td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div><br />

<div class='acp-box'>
	<h3>{$this->lang->words['atc_top5']}</h3>

	<table class='ipsTable'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='20%'>{$this->lang->words['atc_attachment']}</th>
			<th width='10%'>{$this->lang->words['atc_viewed']}</th>
			<th width='15%'>{$this->lang->words['atc_author']}</th>
			<th width='25%'>{$this->lang->words['atc_topic']}</th>
			<th width='25%'>{$this->lang->words['atc_posted']}</th>
			<th width='1%'>&nbsp;</th>
		</tr>
HTML;

foreach( $most_viewed_5 as $r )
{//print_r($r);exit();
$IPBHTML .= <<<HTML
			<tr>
				<td width='1%'><img src='{$r['_icon']}' /></td>
				<td width='20%'><a href='{$this->settings['board_url']}/index.php?app=core&amp;module=attach&amp;section=attach&amp;attach_rel_module=post&amp;attach_id={$r['attach_id']}' target='_blank'>{$r['attach_file']}</a></td>
				<td width='10%'>{$r['attach_hits']}</td>
HTML;

if( $r['attach_member_id'] )
{
	$IPBHTML .= <<<HTML
	<td><a href='{$this->settings['board_url']}/index.php?showuser={$r['attach_member_id']}'>{$r['members_display_name']}</a></td>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<td>{$r['members_display_name']}</td>
HTML;
}

$IPBHTML .= <<<HTML
				<td width='25%'>{$r['stitle']}</td>
				<td width='25%'>{$r['post_date']}</td>
				<td width='1%'><div align='center'><input type='checkbox' name='attach_{$r['attach_id']}' value='1' /></div></td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>		
	
		<div class='acp-actionbar'>
			<div class='rightaction'>
				<input type='submit' value='{$this->lang->words['atc_deletebutton']}' class='button primary'>
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit attachment types
 *
 * @param	string	Title
 * @param	string	Do action
 * @param	integer	Editing attachment type id
 * @param	array 	Form fields
 * @param	string	Button text
 * @param	string	Based on attach type
 * @return	string	HTML
 */
public function attachmentTypeForm( $title, $do, $id, $form, $button, $baseon='' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['add_attachment_type_head']}</h2>
</div>

{$baseon}
<div class='acp-box'>
	<h3>{$title}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />

	<table class="ipsTable double_pad">
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['atc_fileext']}</strong>
			</td>
			<td class='field_field'>
				{$form['atype_extension']}<br />
				<span class='desctext'>{$this->lang->words['atc_fileext_info']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['atc_mimetype']}</strong>
			</td>
			<td class='field_field'>
				{$form['atype_mimetype']}<br />
			<span class='desctext'>{$this->lang->words['atc_mimetype_info']}</span>
			</td>
		</tr>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['atc_inposts']}</strong>
			</td>
			<td class='field_field'>
				{$form['atype_post']}
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['atc_miniimg']}</strong>
			</td>
			<td class='field_field'>
				{$form['atype_img']}<br />
				<span class='desctext'>{$this->lang->words['atc_miniimg_info']}</span>
			</td>
		</tr>
	</table>
	
	<div class="acp-actionbar"><input type='submit' value='{$button}' class='realbutton' accesskey='s'></div>
	
	</form>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Build the "based on" dropdown
 *
 * @param	string	Dropdown HTML
 * @return	string	HTML
 */
public function attachmentTypeBaseOn( $dd ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<form method='post' class='information-box' style='margin-bottom: 10px' action='{$this->settings['base_url']}{$this->form_code}&do=attach_add'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<strong>{$this->lang->words['attach_baseon']}</strong> {$dd} &nbsp;<input type='submit' value='{$this->lang->words['atc_go']}' class='realbutton' />
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display attachment types
 *
 * @param	array 	attachment types
 * @return	string	HTML
 */
public function attachmentTypeOverview( $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ty_title']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=attach_add' title=''><img src='{$this->settings['skin_acp_url']}/images/icons/attach_add.png' alt='' /> {$this->lang->words['atc_add']}</a></li>
		<li><a href='{$this->settings['base_url']}module=attachments&amp;section=types&amp;do=attach_export'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['atc_export']}</a></li>
	</ul>
</div>


<div class="acp-box">
	<h3>{$this->lang->words['atc_atttypes']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='1%'>&nbsp;</td>
			<th>{$this->lang->words['atc_extension']}</td>
			<th width='10%' style='text-align: center'>{$this->lang->words['atc_post']}</td>
			<th class='col_buttons'></td>
 		</tr>
HTML;

foreach( $rows as $row )
{
$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td><img src='{$this->settings['mime_img']}/{$row['atype_img']}' /></td>
			<td>
				.<strong>{$row['atype_extension']}</strong><br />
				<span class='desctext'>{$row['atype_mimetype']}</span>
			</td>
			<td style='text-align: center'>{$row['apost_checked']}</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}do=attach_edit&amp;id={$row['atype_id']}' title='{$this->lang->words['atc_edit']}'>{$this->lang->words['atc_edit']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=attach_delete&amp;id={$row['atype_id']}");' title='{$this->lang->words['atc_delete']}'>{$this->lang->words['atc_delete']}</a>
					</li>
				</ul>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
 	</table>
	<div class='acp-actionbar'>&nbsp;</div>
</div>

<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
	<input type='hidden' name='do' value='attach_import' />
	<input type='hidden' name='MAX_FILE_SIZE' value='10000000000' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['atc_import']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['atc_uploadxml_info']}</strong></td>
			    <td class='field_field'><input class='textinput' type='file' size='30' name='FILE_UPLOAD'></td>
			</tr>
		</table>

		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['atc_importbutton']}' class="button primary" accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display attachment search form
 *
 * @param	array 	Form fields
 * @return	string	HTML
 */
public function attachmentSearchForm( $form ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['se_search']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['se_search']}</h3>

	<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='attach_search_complete' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['se_mext']}</strong>
			</td>
			<td class='field_field'>
				{$form['extension']}
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['se_msize']}</strong>
			</td>
			<td class='field_field'>
				{$form['filesize_gt']} {$form['filesize']}<br />
				<span class='desctext'>{$this->lang->words['se_msize_desc']}</span>
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['se_mposted']}</strong>
			</td>
			<td class='field_field'>
				{$form['days_gt']} {$form['days']}{$this->lang->words['se_ago']}<br />
				<span class='desctext'>{$this->lang->words['se_mposted_desc']}</span>
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['se_mviewed']}</strong>
			</td>
			<td class='field_field'>
				{$form['hits_gt']} {$form['hits']}{$this->lang->words['se_times']}<br />
				<span class='desctext'>{$this->lang->words['se_mviewed_desc']}</span>
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['se_mname']}</strong>
			</td>
			<td class='field_field'>
				{$form['filename']}<br />
				<span class='desctext'>{$this->lang->words['se_mname_desc']}</span>
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['se_mauthor']}</strong>
			</td>
			<td class='field_field'>
				{$form['authorname']}<br />
				<span class='desctext'>{$this->lang->words['se_mauthor_desc']}</span>
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['se_mimages']}</strong>
			</td>
			<td class='field_field'>
				{$form['onlyimage']}<br />
				<span class='desctext'>{$this->lang->words['se_mimages_desc']}</span>
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['se_morder']}</strong>
			</td>
			<td class='field_field'>
				{$form['orderby']} {$form['sort']}
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['se_showresults']}</strong>
			</td>
			<td class='field_field'>
				{$form['show']}<br />
				<span class='desctext'>{$this->lang->words['se_showresults_desc']}</span>
			</td>
		</tr>
	</table>
	
	<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['se_searchbutton']}' class='realbutton' accesskey='s' /></div>
	</form>
</div>

<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('authorname'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display attachment search results
 *
 * @param	string	URL
 * @param	array 	Search results
 * @return	string	HTML
 */
public function attachmentSearchResults( $url, $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.forms.js'></script>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
<input type='hidden' name='do' value='attach_bulk_remove' />
<input type='hidden' name='return' value='search' />
<input type='hidden' name='url' value='{$url}' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />

<div class="acp-box">
	<h3>{$this->lang->words['se_results']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='1%'>&nbsp;</tg>
			<th width='20%'>{$this->lang->words['se_attachment']}</th>
			<th width='10%'>{$this->lang->words['se_size']}</th>
			<th width='15%'>{$this->lang->words['se_author']}</th>
			<th width='25%'>{$this->lang->words['se_topic']}</th>
			<th width='25%'>{$this->lang->words['se_posted']}</th>
			<th width='1%'><input type='checkbox' name='checkAll' id='checkAll' value='0' /></th>
 		</tr>
HTML;

$_public	= PUBLIC_DIRECTORY;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
		<tr>
			<td><img src='{$this->settings['board_url']}/{$_public}/{$this->caches['attachtypes'][ $r['attach_ext'] ]['atype_img']}' /></td>
			<td><a href='{$this->settings['board_url']}/index.php?app=core&amp;module=attach&amp;type={$r['attach_rel_module']}&amp;attach_id={$r['attach_id']}'>{$r['attach_file']}</a></td>
			<td>{$r['attach_filesize']}</td>
HTML;

if( $r['attach_member_id'] )
{
	$IPBHTML .= <<<HTML
	<td><a href='{$this->settings['board_url']}/index.php?showuser={$r['attach_member_id']}'>{$r['members_display_name']}</a></td>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<td>{$r['members_display_name']}</td>
HTML;
}

$IPBHTML .= <<<HTML
			<td>{$r['stitle']}</td>
			<td>{$r['attach_date']}</td>
			<td><div align='center'><input class='checkAll' type='checkbox' name='attach_{$r['attach_id']}' value='1' /></div></td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
 	</table>
	<div class='acp-actionbar'>
		<div class='rightaction'>
			<input type='submit' value='{$this->lang->words['se_delete']}' class='button primary' />
		</div>
	</div>
</div>
</form>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

}