<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Badwords skin file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10721 $
 */
 
class cp_skin_badwords
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
 * Edit badwords
 *
 * @param	int			ID
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function badwordEditForm( $id, $form ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bwl_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='badword_doedit' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />

<div class="acp-box">
	<h3>{$this->lang->words['bwl_edit_filter']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bwl_before']}</title></td>
			<td class='field_field'>{$form['before']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bwl_after']}</strong></td>
			<td class='field_field'>{$form['after']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bwl_method']}</strong></td>
			<td class='field_field'>{$form['match']}</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['bwl_edit_filter']}' class='button primary' accesskey='s'>
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Badwords wrapper
 *
 * @param	array 		Badword rows
 * @return	string		HTML
 */
public function badwordsWrapper( $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bwl_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=badword_export'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' />
				{$this->lang->words['bwl_export']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='badword_add' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />

<div class="acp-box">
	<h3>{$this->lang->words['bwl_current']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='40%'>{$this->lang->words['bwl_before']}</th>
			<th width='40%'>{$this->lang->words['bwl_after']}</th>
			<th width='15%'>{$this->lang->words['bwl_method']}</th>
			<th width='5%'>{$this->lang->words['bwl_options']}</th>
		</tr>
HTML;

foreach( $rows as $row )
{
$IPBHTML .= <<<HTML
			<tr class='ipsControlRow'>
				<td>{$row['type']}</td>
				<td>{$row['replace']}</td>
				<td>{$row['method']}</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=badword_edit&id={$row['wid']}' title='{$this->lang->words['bwl_filter_edit']}'>{$this->lang->words['bwl_filter_edit']}</a></li>
						<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=badword_remove&id={$row['wid']}");' title='{$this->lang->words['bwl_filter_remove']}'>{$this->lang->words['bwl_filter_remove']}</a></li>
					</ul>
				</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
	</div>
	<br />
	
<div class="acp-box">
	<h3>{$this->lang->words['bwl_filter_add']}</h3>
	<table class="ipsTable">
		<tr>
			<th width="40%">{$this->lang->words['bwl_before']}</th>
			<th width="40%">{$this->lang->words['bwl_after']}</th>
			<th width="20%">{$this->lang->words['bwl_method']}</th>
		</tr>
		<tr>
			<td width="40%"><input name="before" value="" size="30" class="textinput" type="text"></td>
			<td width="40%"><input name="after" value="" size="30" class="textinput" type="text"></td>
			<td width="20%">
				<select name="match" class="dropdown">
					<option value="1">{$this->lang->words['bwl_exact']}</option>
					<option value="0">{$this->lang->words['bwl_loose']}</option>
				</select>
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input value="{$this->lang->words['bwl_filter_add']}" class="button primary" accesskey="s" type="submit">
	</div>	
</div>
</form>

<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
	<input type='hidden' name='do' value='badword_import' />
	<input type='hidden' name='MAX_FILE_SIZE' value='10000000000' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
<div class="acp-box">
	<h3>{$this->lang->words['bwl_import']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['bwl_import_upload']}</strong></td>
				<td class='field_field'><input class='textinput' type='file' size='30' name='FILE_UPLOAD'><br /><span class="desctext">{$this->lang->words['bwl_import_info']}</span></td>
			</tr>
		</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['bwl_import']}' class='button primary' accesskey='s'>
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


}