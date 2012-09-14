<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP profile fields skin file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 *
 */
 
class cp_skin_profilefields
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
 * Add/edit a group
 *
 * @param	int			ID
 * @param	array 		Group data
 * @param	string		Page title
 * @param	string		Action
 * @return	string		HTML
 */
public function groupForm( $id, $data, $title, $do ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->memberData['form_hash']}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_g_name']}</strong>
				</td>
				<td class='field_field'>
					<input type='text' name='pf_group_name' value="{$data['pf_group_name']}" size='30' class='textinput'>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_g_key']}</strong>
				</td>
				<td class='field_field'>
					<input type='text' name='pf_group_key' value="{$data['pf_group_key']}" size='30' class='textinput'><br />
					<span class='desctext'>{$this->lang->words['cf_g_key_desc']}</span>
				</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['cf_g_save']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * List custom profile fields
 *
 * @param	array 		Fields
 * @return	string		HTML
 */
public function customProfileFieldsList( $rows )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['cf_management']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['cf_addbutton']}</a></li>
		<li><a href='{$this->settings['base_url']}{$this->form_code}do=group_form_add'><img src='{$this->settings['skin_acp_url']}/images/icons/folder_add.png' alt='' /> {$this->lang->words['cf_g_add']}</a></li>
	</ul>
</div>

<div class='acp-box'>
 	<h3>{$this->lang->words['cf_management']}</h3>
	<div>
		<table class='ipsTable'>
			<tr>
				<th style='width: 2%'>&nbsp;</td>
				<th style='width: 24%'>{$this->lang->words['cf_title']}</td>
				<th style='width: 12%'>{$this->lang->words['cf_type']}</td>
				<th style='width: 15%; text-align: center;'>{$this->lang->words['cf_required']}</td>
				<th style='width: 15%; text-align: center;'>{$this->lang->words['cf_notpublic']}</td>
				<th style='width: 15%; text-align: center;'>{$this->lang->words['cf_showreg']}</td>
				<th style='width: 15%; text-align: center;'>{$this->lang->words['cf_adminonly']}</td>
				<th class='col_buttons'>&nbsp;</td>
			</tr>
HTML;

if( ! count( $rows ) )
{
$IPBHTML .= <<<HTML
			<tr>
				<td colspan='8' class='no_messages'>
					{$this->lang->words['cf_nonefound']}
				</td>
			</tr>
HTML;
}
else
{
	$incrementer	= 1;

	foreach( $rows as $group => $fields )
	{
		if( is_array($fields) )
		{
			$_groupData = array_rand($fields, 1);
			$_id		= $fields[ $_groupData ]['pf_group_id'];
		}
		else
		{
			$_id	= $fields;
		}

$IPBHTML .= <<<HTML
		<tr>
			<td colspan='8' class='no_pad'>
				<table class='ipsTable' id='handle_{$incrementer}'>
					<tr class='ipsControlRow'>
						<th class='subhead col_drag'>&nbsp;</th>
						<th colspan='6' class='subhead'>{$group}</th>
						<th class='subhead col_buttons'>
							<ul class='ipsControlStrip'>
								<li class='i_edit'>
									<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=group_form_edit&amp;id={$_id}'>{$this->lang->words['cf_g_edit']}</a>
								</li>
								<li class='i_delete'>
									<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=group_form_delete&id={$_id}")'>{$this->lang->words['cf_g_delete']}</a>
								</li>
							</ul>
						</th>
					</tr>
HTML;

		if( is_array($fields) AND count($fields) )
		{
		foreach( $fields as $r )
		{
$IPBHTML .= <<<HTML
					<tr id='fields_{$r['pf_id']}' class='ipsControlRow isDraggable sortable_{$incrementer}'>
						<td class='col_drag'>
							<div class='draghandle'>Drag</div>
						</td>
						<td style='width: 24%'>
HTML;
					if( $r['pf_icon'] )
					{
						$IPBHTML .= "<img src='{$this->settings['public_dir']}{$r['pf_icon']}' alt='Icon' />&nbsp;";
					}
					
					$IPBHTML .= <<<HTML
							<a href="{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$r['pf_id']}"><span class='larger_text'>{$r['pf_title']}</span></a>
HTML;
					if( $r['pf_desc'] )
					{
						$IPBHTML .= <<<HTML
							<br /><span class='desctext'>{$r['pf_desc']}</span>
HTML;
					}
					
					$IPBHTML .= <<<HTML
						</td>
						<td style='width: 12%'>
							<span class='desctext'>{$r['pf_type']}</span>
						</td>
						<td style='width: 15%; text-align: center;'>
							<img src='{$this->settings['skin_acp_url']}/images/icons/{$r['_req']}' alt='Icon' />
						</td>
						<td style='width: 15%; text-align: center;'>
							<img src='{$this->settings['skin_acp_url']}/images/icons/{$r['_hide']}' alt='Icon' />
						</td>
						<td style='width: 15%; text-align: center;'>
							<img src='{$this->settings['skin_acp_url']}/images/icons/{$r['_regi']}' alt='Icon' />
						</td>
						<td style='width: 15%; text-align: center;'>
							<img src='{$this->settings['skin_acp_url']}/images/icons/{$r['_admin']}' alt='Icon' />
						</td>
						<td class='col_buttons'>
							<ul class='ipsControlStrip'>
								<li class='i_edit'>
									<a href="{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$r['pf_id']}" title='{$this->lang->words['cf_edit']}'>{$this->lang->words['cf_edit']}</a>
								</li>
								<li class='i_delete'>
									<a href="{$this->settings['base_url']}{$this->form_code}do=delete&amp;id={$r['pf_id']}" title='{$this->lang->words['cf_delete']}'>{$this->lang->words['cf_delete']}</a>
								</li>
							</ul>
						</td>
					</tr>
HTML;
		}
		}
		
	$IPBHTML .= <<<HTML
			</table>
			<script type='text/javascript'>
				jQ("#handle_{$incrementer}").ipsSortable('table', { items: "tr.sortable_{$incrementer}", url: "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )});
			</script>
		</td>
	</tr>
HTML;
		
		$incrementer++;
	}
}

$IPBHTML .= <<<HTML

</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add/edit profile field form
 *
 * @param	int			ID
 * @param	string		Action
 * @param	string		Button text
 * @param	array 		Field data
 * @param	string		Page title
 * @return	string		HTML
 */
public function customProfileFieldForm( $id, $do, $button, $data, $title )
{
$IPBHTML = "";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['cf_settings']}</h3>
		
		<table class='ipsTable'>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_title']}</strong>
				</td>
		 		<td class='field_field'>
		 			<input type='text' name='pf_title' value="{$data['pf_title']}" size='30' class='input_text' maxlength='200' /><br />
					<span class='desctext'>{$this->lang->words['cf_f_title_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_desc']}</strong>
				</td>
		 		<td class='field_field'>
		 			<input type='text' name='pf_desc' value="{$data['pf_desc']}" size='50' class='input_text' maxlength='250' /><br />
					<span class='desctext'>{$this->lang->words['cf_f_desc_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_type']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_type']}
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_group']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_group_id']}
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_icon']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_icon']}
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_key']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_key']}<br />
					<span class='desctext'>{$this->lang->words['cf_f_key_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_max']}</strong>
				</td>
		 		<td class='field_field'>
		 			<input type='text' name='pf_max_input' value="{$data['pf_max_input']}" size='30' class='input_text' /><br />
					<span class='desctext'>{$this->lang->words['cf_f_max_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_order']}</strong>
				</td>
		 		<td class='field_field'>
		 			<input type='text' name='pf_position' value="{$data['pf_position']}" size='30' class='input_text' /><br />
					<span class='desctext'>{$this->lang->words['cf_f_order_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_form']}</strong>
				</td>
		 		<td class='field_field'>
		 			<input type='text' name='pf_input_format' value="{$data['pf_input_format']}" size='30' class='input_text' /><br />
					<span class='desctext'>{$this->lang->words['cf_f_form_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_option']}</strong>
				</td>
		 		<td class='field_field'>
		 			<textarea name='pf_content' cols='45' rows='5' wrap='soft' id='pf_content' class='multitext'>{$data['pf_content']}</textarea><br />
					<span class='desctext'>{$this->lang->words['cf_f_option_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_srch_type']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_search_type']}<br />
					<span class='desctext'>{$this->lang->words['cf_srch_type_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_filter_type']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_filtering']}<br />
					<span class='desctext'>{$this->lang->words['cf_filter_type_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_reg']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_show_on_reg']}<br />
					<span class='desctext'>{$this->lang->words['cf_f_reg_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_must']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_not_null']}<br />
					<span class='desctext'>{$this->lang->words['cf_f_must_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_edit']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_member_edit']}<br />
					<span class='desctext'>{$this->lang->words['cf_f_edit_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_priv']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_member_hide']}<br />
					<span class='desctext'>{$this->lang->words['cf_f_priv_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_admin']}</strong>
				</td>
		 		<td class='field_field'>
		 			{$data['pf_admin_only']}<br />
					<span class='desctext'>{$this->lang->words['cf_f_admin_info']}</span>
		 		</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_f_view']}</strong>
				</td>
		 		<td class='field_field'>
		 			<textarea name='pf_topic_format' cols='60' rows='5' wrap='soft' id='pf_topic_format' class='multitext'>{$data['pf_topic_format']}</textarea><br />
					<span class='desctext'>{$this->lang->words['cf_f_view_info']}</span>
		 		</td>
		 	</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' accesskey='s' />
		</div>
	</div>
</form>
	
HTML;

return $IPBHTML;		
}

/**
 * Delete field confirmation
 *
 * @param	int			ID
 * @param	string		Title
 * @return	string		HTML
 */
public function customProfileFieldDelete( $id, $title )
{
$IPBHTML = "";

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dodelete' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['cf_removeconf']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['cf_removeto']}</strong>
				</td>
				<td class='field_field'>
					{$title}
				</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['cf_deletebutton']}' class='button primary' accesskey='s' />
		</div>
	</div>
</form>
HTML;

return $IPBHTML;		
}

}