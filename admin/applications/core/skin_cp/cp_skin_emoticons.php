<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Emoticon manager skin file
 * Last Updated: $Date: 2012-05-24 16:20:59 -0400 (Thu, 24 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10792 $
 */
 
class cp_skin_emoticons
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
 * Emoticon pack splash page
 *
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function emoticonsPackSplash( $form )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['emo_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='emo_packexport' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['emote_export']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['emote_export_which']}</strong>
				</td>
				<td class='field_field'>
					{$form['emo_set']}<br />
					<span class='desctext'>{$this->lang->words['emote_xml_pack']}</span>
				</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['emote_export_button']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
	<input type='hidden' name='do' value='emo_packimport' />
	<input type='hidden' name='MAX_FILE_SIZE' value='10000000000' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['emote_import']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['emote_import_which']}</strong>
				</td>
				<td class='field_field'>
					{$form['emo_set']}<br />
					<span class='desctext'>{$this->lang->words['emote_xml_export']}</span>					
				</td>
			</tr>
			
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['emote_import_newgroup']}</strong>				
				</td>
				<td class='field_field'>
					{$form['new_emo_set']}<br />
					<span class='desctext'>{$this->lang->words['emote_import_name']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['emote_import_over']}</strong>					
				</td>
				<td class='field_field'>
					{$form['overwrite']}<br />
					<span class='desctext'>{$this->lang->words['emote_import_replace']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['emote_import_upload']}</strong>
				</td>
				<td class='field_field'>
					<input class='textinput' type='file' size='30' name='FILE_UPLOAD'><br />
					<span class='desctext'>{$this->lang->words['emote_import_browse']}</span>
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['emote_import_button']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

return $IPBHTML;
}

/**
 * Show the splash screen for the logs
 *
 * @param	array 		Db records
 * @param	array 		File records
 * @param	string		Width for table cells
 * @param	int			Number of emoticons per row
 * @return	string		HTML
 */
public function emoticonsDirectoryManagement( $db_rows, $file_rows )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['emo_control']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['emote_assigned']}'{$this->request['id']}'</h3>
	
	<form action='{$this->settings['base_url']}{$this->form_code}do=emo_doedit&id={$this->request['id']}' method='post'>
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
		<table class='ipsTable' id='emoticons_table'>
HTML;

/* Loop through the database emoticons */
$count   = 0;
$_public = PUBLIC_DIRECTORY;

foreach( $db_rows as $r )
{	
$IPBHTML .= <<<HTML
			<tr class='ipsControlRow isDraggable' id='emoticons_{$r['id']}'>
				<td class='col_drag'><div class='draghandle'>&nbsp;</div></td>
				<td width='60%'>
					<input type='hidden' name='emo_id_{$r['id']}' value='{$r['id']}' />	
					<img src='../{$_public}/style_emoticons/{$this->request['id']}/{$r['image']}' />
					&nbsp; &nbsp; <span class='ipsBadge badge_grey'>{$r['image']}</span>	
				</td>
				<td width='30%' align='right'>
HTML;
			if( $this->request['id'] == 'default' )
			{
$IPBHTML .= <<<HTML
						<input type='textinput' class='input_text' size='20' name='emo_type_{$r['id']}' value='{$r['typed']}' />
HTML;
			}
			else
			{
$IPBHTML .= <<<HTML
						<span class='button'>{$r['typed']}</span>
HTML;
			}

$IPBHTML .= <<<HTML
				</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
						<li class='i_delete'><a href='{$this->settings['base_url']}{$this->form_code}do=emo_remove&eid={$r['id']}&id={$this->request['id']}' title='{$this->lang->words['emote_delete_title']}'>{$this->lang->words['emote_delete_title']}</a></li>
					</ul>
				</td>
			</tr>
HTML;

}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$this->lang->words['emote_update_button']}' />
		</div>
	</form>
</div>
<script type='text/javascript'>
	jQ("#emoticons_table").ipsSortable('table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}&id={$this->request['id']}".replace( /&amp;/g, '&' ),
		serializeOptions: { key: 'emoticons[]' },
		sendType: 'post'
	} );
</script>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['emote_unassigned']}'{$this->request['id']}'</h3>	
	<table class='ipsTable'>
HTML;

/* Display unassigned emoticons */
if( is_array( $file_rows ) && count( $file_rows ) )
{
	foreach( $file_rows as $r )
	{
		$safeName = str_replace( '.', '---', $r['image'] );
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td class='col_drag _amber'><div class='draghandle'>&nbsp;</div></td>
			<td width='60%' class='_amber'>
				<img src='../{$_public}/style_emoticons/{$this->request['id']}/{$r['image']}' />
				&nbsp; &nbsp; <span class='ipsBadge badge_grey'>{$r['image']}</span>	
			</td>
			<td width='30%' align='right' class='_amber'>
				<input type='textinput' disabled="disabled" class='input_text' size='20' name='emo_type_{$safeName}' value='{$r['poss_name']}' />
			</td>
			<td class='col_buttons _amber'>
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='{$this->settings['base_url']}{$this->form_code}do=emo_doadd&id={$this->request['id']}&emo_image={$safeName}'>{$this->lang->words['emote_delete_title']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td class='no_messages'>{$this->lang->words['emote_unassigned_none']}</td>
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
 * Overview of emoticon packs
 *
 * @param	array 		Records
 * @return	string		HTML
 */
public function emoticonsOverview( $rows )
{
	
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.emoticons.js"></script>
<script type='text/javascript'>
	
	ipb.templates['emo_manage'] = new Template( "<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>" +
												"<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />" +
												"<input type='hidden' name='do' value='#{form_do}' />" +
												"<input type='hidden' name='id' value='#{form_id}' />" +
												"<div class='acp-box'><h3>{$this->lang->words['emoticon_folder']}</h3><table class='ipsTable double_pad'><tr><td class='field_title'><strong class='title'>{$this->lang->words['emoticon_folder_name']}</strong></td><td class='field_field'><input type='text' size='30' class='input_text' id='name_#{form_id}' value='#{folder_name}' name='emoset'></td></tr></table><div class='acp-actionbar'><input type='submit' value='#{form_value}' class='realbutton' id='save_folder_#{id}' /></div></div>" + 
												"</form>" );
	
	ipb.lang['emoticons'] = [];								
	ipb.lang['emoticons']['add'] = "{$this->lang->words['emote_addfolder']}";
	ipb.lang['emoticons']['edit'] = "{$this->lang->words['emote_editfolder']}";
	
</script>

<div class='section_title'>
	<h2>{$this->lang->words['emo_control']}</h2>
	<ul class='context_menu'>
		<li><a href='#' id='folder_edit_0'><img src='{$this->settings['skin_acp_url']}/images/icons/folder_add.png' alt='' /> {$this->lang->words['emote_createnew']}</a></li>
		<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=emo_packsplash'><img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' /> {$this->lang->words['emote_impexpo']}</a></li>
	</ul>
	<script type='text/javascript'>
		$('folder_edit_0').observe('click', acp.emoticons.folder.bindAsEventListener( this, 0 ) );
	</script>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<input type='hidden' name='do' value='emo_upload'>
	<input type='hidden' name='MAX_FILE_SIZE' value='10000000000'>
	<input type='hidden' name='dir_default' value='1'>

	<div class='acp-box'>
		<h3>{$this->lang->words['emote_current']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th width='50%' align='center'>{$this->lang->words['emote_emofolder']}</th>
				<th width='5%' align='center'>{$this->lang->words['emote_upload']}</th>
				<th width='20%' align='center'>{$this->lang->words['emote_num_disk']}</th>
				<th width='20%' align='center'>{$this->lang->words['emote_num_group']}</th>
				<th width='5%' align='center'>{$this->lang->words['emote_options']}</th>
			</tr>
HTML;

foreach( $rows as $data )
{
$IPBHTML .= <<<HTML
			<tr class='ipsControlRow'>
	 			<td valign='middle'>
					<div style='width:auto;float:right;'><img src='{$this->settings['skin_acp_url']}/images/{$data['icon']}' title='{$data['title']}' alt='{$data['icon']}' /></div>
					{$data['line_image']}<img src='{$this->settings['skin_acp_url']}/images/emoticon_folder.png' />&nbsp;
					<a href='{$this->settings['base_url']}{$this->form_code}do=emo_manage&amp;id={$data['dir']}' title='{$this->lang->words['emote_manageset']}'><b>{$data['dir']}</b></a>
				</td>

				<td valign='middle'><center>{$data['checkbox']}</center></td>
				<td valign='middle'><center>{$data['count']}</center></td>
				<td valign='middle'><center>{$data['dir_count']}</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'>
							<a href='{$this->settings['base_url']}{$this->form_code_js}do=emo_manage&amp;id={$data['dir']}' title='{$data['link_text']}'>{$data['link_text']}</a>
						</li>
HTML;

if( $data['dir'] != 'default' OR IN_DEV == 1 )
{
$IPBHTML .= <<<HTML
						<li class='i_new_folder'>
							<a href='#' id="folder_edit_{$data['dir']}" title='{$this->lang->words['emote_editfolder']}'>{$this->lang->words['emote_editfolder']}</a>
							<script type='text/javascript'>
								$("folder_edit_{$data['dir']}").observe('click', acp.emoticons.folder.bindAsEventListener( this, "{$data['dir']}" ) );
							</script>
						</li>
						<li class='i_delete'>
							<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code_js}do=emo_setremove&amp;id={$data['dir']}");' title='{$this->lang->words['emote_deletefolder']}'>{$this->lang->words['emote_deletefolder']}</a>
						</li>
HTML;
}
else
{
$IPBHTML .= <<<HTML
						<li class='i_delete disabled'><a title='{$this->lang->words['emote_dontdeletedef']}'><em>{$this->lang->words['emote_dontdeletedef']}</em></a></li>
HTML;
}

$IPBHTML .= <<<HTML
					</ul>
				</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
	</div><br />
HTML;

if( SAFE_MODE_ON )
{
$IPBHTML .= <<<HTML
</form>
HTML;
}
else
{
$IPBHTML .= <<<HTML
	<div class='acp-box'>
		<h3>{$this->lang->words['emote_uploademos']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<td class='field_title'><input type='file' value='' name='upload_1' size='30' /></td>
				<td class='field_field'><input type='file' name='upload_2' size='30' /></td>
			</tr>
			<tr>
				<td class='field_title'><input type='file' name='upload_3' size='30' /></td>
				<td class='field_field'><input type='file' name='upload_4' size='30' /></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['emote_uploadtofolders']}' class='button primary' />
		</div>
	</div>
</form>
HTML;
}


return $IPBHTML;
}

}