<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP reputation skin file
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
 * @version		$Revision: 10721 $
 *
 */
 
class cp_skin_reputation
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
 * Reputation form
 *
 * @param	int		ID
 * @param	string	Action
 * @param	string	Title
 * @param	array 	Form elements
 * @param	array 	Errors
 * @return	string	HTML
 */
public function reputationForm( $id, $do, $title, $form, $errors ) {
$IPBHTML = "";
//--starthtml--//

if( count( $errors ) )
{
$IPBHTML .= <<<HTML
<h2>{$this->lang->words['errors']}</h2>
<div class='warning'>
HTML;

	foreach( $errors as $err )
	{
$IPBHTML .= <<<HTML
	<p>$err</p>
HTML;
	}
	
$IPBHTML .= <<<HTML
</div><br />
HTML;
}

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}'>
	<input type='hidden' name='id' value='{$id}'>
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['rep_form_title']}</strong></td>
				<td class='field_field'>{$form['level_title']}<br /><span class='desctext'>{$this->lang->words['rep_form_title_help']}</span></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['rep_form_image']}</strong></td>
				<td class='field_field'>{$form['level_image']}<br /><span class='desctext'>{$this->lang->words['rep_form_image_help']}{$this->settings['public_dir']}style_extra/reputation_icons/</span></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['rep_form_points']}</strong></td>
				<td class='field_field'>{$form['level_points']}<br /><span class='desctext'>{$this->lang->words['rep_form_points_help']}</span></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['rep_save_changes']}' class='button primary'/>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Reputation overview screen
 *
 * @param	array 	Rep levels
 * @return	string	HTML
 */
public function reputationOverview( $levels=array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['rep_lvl_manage']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add_level_form'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['rep_level_new']}</a></li>
	</ul>
</div>
<div class='acp-box'>
	<h3>{$this->lang->words['rep_lvl_manage']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='40%'>{$this->lang->words['rep_form_title']}</th>
			<th width='20%'>{$this->lang->words['rep_form_image']}</th>
			<th width='30%'>{$this->lang->words['rep_form_points']}</th>
			<th width='10%'>&nbsp;</th>
		</tr>
HTML;

if( is_array( $levels ) && count( $levels ) )
{
	foreach( $levels as $r )
	{
$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td><span class='larger_text'>{$r['level_title']}</span></td>
			<td>{$r['level_image']}</td>
			<td>{$r['level_points']}</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit_level_form&amp;id={$r['level_id']}' title='{$this->lang->words['edit']}'>{$this->lang->words['edit']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete( "{$this->settings['base_url']}{$this->form_code}&amp;do=delete_level&amp;id={$r['level_id']}" );' title='{$this->lang->words['delete']}'>{$this->lang->words['delete']}</a></li>
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
			<td colspan='4'><em>{$this->lang->words['rep_no_levels']}</em></td>
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


}