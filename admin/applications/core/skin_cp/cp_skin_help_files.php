<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Manage help files skin file
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
 
class cp_skin_help_files
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
 * Form to add/edit a help file
 *
 * @param	string		Action
 * @param	int			ID
 * @param	array 		Form elements
 * @param	string		Button text
 * @return	string		HTML
 */
public function helpFileForm( $do, $id, $form, $button )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['h_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$button}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['h_filetitle']}</strong>
				</td>
				<td class='field_field'>
					{$form['title']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['h_filedesc']}</strong>
				</td>
				<td class='field_field'>
					{$form['description']}
				</td>
			</tr>			
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['h_fileapp']}</strong>
				</td>
				<td class='field_field'>
					{$form['appDir']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['h_filetext']}</strong>
				</td>
				<td class='field_field'>
					<textarea id='editor_main' name='editor_main' style='width: 100%; height: 300px;'>{$form['text']}</textarea>
				</td>
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
 * List the current help files
 *
 * @param	array 		Rows
 * @return	string		HTML
 */
public function helpFilesList( $rows )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['h_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
HTML;

if ( $this->registry->class_permissions->checkPermission( 'help_manage' ) )
{
	$IPBHTML .= <<<HTML
		<li class='ipsActionButton'>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=new' title='{$this->lang->words['h_addnew']}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/help_add.png' alt='' />
				{$this->lang->words['h_addnew']}
			</a>
		</li>
HTML;
}

	$IPBHTML .= <<<HTML
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=exportXml' title='{$this->lang->words['h_export']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' />
					{$this->lang->words['h_export']}
				</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=importXml' title='{$this->lang->words['h_import']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/import.png' alt='' />
					{$this->lang->words['h_import']}
				</a>
			</li>
		</ul>
	</div>
</div>
HTML;

$IPBHTML .= <<<HTML
	<div class='acp-box'>
		<h3>{$this->lang->words['h_current']}</h3>
		
		<table class='ipsTable' id='helpfiles_list'>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
			<tr id='faq_{$r['id']}' class='ipsControlRow isDraggable'>
				<td class='col_drag'>
					<span class='draghandle'>&nbsp;</span>
				</td>
				<td>
					<strong class='larger_text'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$r['id']}'>{$r['title']}</a></strong><br />
					<span class='desctext'>{$r['description']}</span>
				</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
HTML;
	
	if ( $this->registry->class_permissions->checkPermission( 'help_manage' ) )
	{
					$IPBHTML .= <<<HTML
						<li class='i_edit'>
							<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$r['id']}' title='{$this->lang->words['h_edit']}'>{$this->lang->words['h_edit']}</a>
						</li>
HTML;
	}
	if ( $this->registry->class_permissions->checkPermission( 'help_remove' ) )
	{
					$IPBHTML .= <<<HTML
						<li class='i_delete'>
							<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;id={$r['id']}");' title='{$this->lang->words['h_remove']}'>{$this->lang->words['h_remove']}</a>
						</li>
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
	</div>
	<br />
</form>
<script type='text/javascript'>
	jQ("#helpfiles_list").ipsSortable( 'table', { 
		url: "{$this->settings['base_url']}&{$this->form_code_js}&do=doreorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>
HTML;

//--endhtml--//
return $IPBHTML;

}

}