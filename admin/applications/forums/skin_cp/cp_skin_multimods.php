<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Multimods skin functions
 * Last Updated: $LastChangedDate: 2012-05-25 13:17:47 -0400 (Fri, 25 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 10798 $
 */
 
class cp_skin_multimods
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
 * Form to add/edit multimods
 *
 * @param	integer	MM ID
 * @param	string	Action
 * @param	string	Description
 * @param	array 	Form fields
 * @param	string	Button text
 * @return	string	HTML
 */
public function multiModerationForm( $id, $do, $description, $form, $button ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['mm_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['mm_title']}</h3>
		<table class="ipsTable double_pad">
				<tr>
					<th colspan='2'>{$description}</th>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_titlefor']}</strong>
					</td>
					<td class='field_field'>
						{$form['mm_title']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_activein']}</strong>
					</td>
					<td class='field_field'>
						{$form['forums']}<br />
						<span class='desctext'>{$this->lang->words['mm_activein_desc']}</span>
					</td>
				</tr>
				
				<tr>
					<th colspan='2'>{$this->lang->words['mm_modoptions']}</th>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_start']}</strong>
					</td>
					<td class='field_field'>
						{$form['topic_title_st']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_end']}</strong>
					</td>
					<td class='field_field'>
						{$form['topic_title_end']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_state']}</strong>
					</td>
					<td class='field_field'>
						{$form['topic_state']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_pinned']}</strong>
					</td>
					<td class='field_field'>
						{$form['topic_pin']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_approved']}</strong>
					</td>
					<td class='field_field'>
						{$form['topic_approve']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_move']}</strong>
					</td>
					<td class='field_field'>
						{$form['topic_move']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_link']}</strong>
					</td>
					<td class='field_field'>
						{$form['topic_move_link']}
					</td>
				</tr>
				
				<tr>
					<th colspan='2'>{$this->lang->words['mm_postoptions']}</th>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_addreply']}</strong>
					</td>
					<td class='field_field'>
						{$form['topic_reply']}<br /><br />
						{$form['topic_reply_content']}<br />
						<span class='desctext'>{$this->lang->words['mm_addreply_desc']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['mm_postcount']}</strong>
					</td>
					<td class='field_field'>
						{$form['topic_reply_postcount']}
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
 * Show multimod overview page
 *
 * @param	array 	MM Rows
 * @return	string	HTML
 */
public function multiModerationOverview( $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['mm_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}do=new' title='{$this->lang->words['mm_addnew']}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/lightning_add.png' alt='' />
				{$this->lang->words['mm_addnew']}
			</a>
		</li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['mm_current']}</h3>

	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['mm_wordtitle']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if( ! count( $rows ) )
{
$IPBHTML .= <<<HTML
		<tr>
			<td colspan='3' class='no_messages'>
				{$this->lang->words['mm_none']} <a href='{$this->settings['base_url']}{$this->form_code}do=new' title='{$this->lang->words['mm_addnew']}' class='mini_button'>{$this->lang->words['mm_createnow']}</a>
			</td>
		</tr>
HTML;
}
else
{
	foreach( $rows as $r )
	{
$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td><strong>{$r['mm_title']}</strong></td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$r['mm_id']}' title='{$this->lang->words['mm_wordedit']}'>{$this->lang->words['mm_wordedit']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=delete&amp;id={$r['mm_id']}");' title='{$this->lang->words['mm_remove']}'>{$this->lang->words['mm_remove']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

}