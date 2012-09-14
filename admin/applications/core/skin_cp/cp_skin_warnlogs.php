<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Warn log skin file
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
 
class cp_skin_warnlogs
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
 * Warn logs wrapper
 *
 * @param	array 		Records
 * @param	array 		Members
 * @return	string		HTML
 */
public function warnlogsWrapper( $rows, $members, $pagination ) {

$form				= array();

$form['search_for']	= $this->registry->output->formInput( "search_string" );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['wlog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['wlog_last10']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='25%'>{$this->lang->words['wlog_warned']}</th>
			<th width='25%'>{$this->lang->words['wlog_date']}</th>
			<th width='25%'>{$this->lang->words['wlog_warnedby']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['_a_name']}</td>
			<td>{$row['_date']}</td>
			<td>{$row['p_name']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' align='center'>{$this->lang->words['wlog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class="acp-box">
	<h3>{$this->lang->words['wlog_thelogs']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='30%'>{$this->lang->words['wlog_member']}</th>
			<th width='20%'>{$this->lang->words['wlog_times']}</th>
			<th width='20%'>{$this->lang->words['wlog_viewall']}</th>
			<th width='30%'>{$this->lang->words['wlog_removeall']}</th>
		</tr>
HTML;

if( count($members) AND is_array($members) )
{
	foreach( $members as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><span class='larger_text'>{$row['members_display_name']}</span></td>
			<td>{$row['act_count']}</td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=view&amp;mid={$row['member_id']}'>{$this->lang->words['wlog_viewlog']}</a></td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remove&amp;mid={$row['member_id']}'>{$this->lang->words['wlog_remove']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='4' align='center'>{$this->lang->words['wlog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
{$pagination}
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='view' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />

	<div class="acp-box">
		<h3>{$this->lang->words['wlog_search']}</h3>
		<table class="ipsTable double_pad">
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['wlog_searchin']}</strong></td>
				<td class='field_field'>{$form['search_for']}</td>
			</tr>
		</table>	
		<div class="acp-actionbar">
			<input value="{$this->lang->words['wlog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View an individual member's log
 *
 * @param	array 		Log rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function warnlogsView( $rows, $pages ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['wlog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['wlog_thelogs']}</h3>
	<table class="ipsTable">
		<tr>
			<th>{$this->lang->words['wlog_member']}</th>
			<th>{$this->lang->words['wlog_points']}</th>
			<th>{$this->lang->words['wlog_modq']}</th>
			<th>{$this->lang->words['wlog_susp']}</th>
			<th>{$this->lang->words['wlog_nopost']}</th>
			<th>{$this->lang->words['wlog_date']}</th>
			<th>{$this->lang->words['wlog_warnedby']}</th>
			<th>{$this->lang->words['wlog_reason']}</th>
			<th>{$this->lang->words['wlog_viewnotes']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['_a_name']}</td>
			<td>{$row['wl_points']}</td>
			<td>{$row['_mod']}</td>
			<td>{$row['_susp']}</td>
			<td>{$row['_post']}</td>
			<td>{$row['_date']}</td>
			<td>{$row['p_name']}</td>
			<td>{$row['wl_reason']}</td>
			<td><a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}&{$this->form_code}&do=viewnote&id={$row['wl_id']}",700,400,"{$this->lang->words['wlog_log']}"); return false;'>{$this->lang->words['wlog_viewlog']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='9' align='center'>{$this->lang->words['wlog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
		<div class="left">{$pages}</div>
		<br class='clear' />
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View a member's warn log note
 *
 * @param	array 		Note data
 * @return	string		HTML
 */
public function warnlogsNote( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$this->lang->words['wlog_warnnotes']}</h3>
	<table class="ipsTable">
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['wlog_log_to']}</strong></td>
			<td class='field_field'>{$row['a_name']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['wlog_log_from']}</strong></td>
			<td>{$row['p_name']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['wlog_log_sent']}</strong></td>
			<td class='field_field'>{$row['_date']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['wlog_log_note_member']}</strong></td>
			<td class='field_field'>{$row['wl_note_member']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['wlog_log_note_mods']}</strong></td>
			<td class='field_field'>{$row['wl_note_mods']}</td>
		</tr>
	</table>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

}