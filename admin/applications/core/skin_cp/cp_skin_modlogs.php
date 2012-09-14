<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Moderator log skin file
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
 
class cp_skin_modlogs
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
 * Moderator logs wrapper
 *
 * @param	array 		Rows
 * @param	array 		Moderators
 * @return	string		HTML
 */
public function modlogsWrapper( $rows, $admins ) {

$form_array 		= array(
							0 => array( 'topic_title'	, $this->lang->words['mlog_topictitle'] ),
							1 => array( 'ip_address'	, $this->lang->words['mlog_ip'] ),
							2 => array( 'member_name'	, $this->lang->words['mlog_name'] ),
							3 => array( 'topic_id'		, $this->lang->words['mlog_tid'] ),
							4 => array( 'forum_id'		, $this->lang->words['mlog_fid'] ),
							5 => array( 'action'		, $this->lang->words['mlog_action'] )
						);
$form				= array();

$form['search_for']	= $this->registry->output->formInput( "search_string" );
$form['search_in']	= $this->registry->output->formDropdown( "search_type", $form_array );

$IPBHTML = "";
//--starthtml--//

$this->registry->output->setMessage( $this->lang->words['stop_being_alarmed'], true );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['mlog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['mlog_last5']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='15%'>{$this->lang->words['mlog_member']}</th>
			<th width='15%'>{$this->lang->words['mlog_action']}</th>
			<th width='15%'>{$this->lang->words['mlog_forum']}</th>
			<th width='25%'>{$this->lang->words['mlog_topictitle']}</th>
			<th width='20%'>{$this->lang->words['mlog_date']}</th>
			<th width='10%'>{$this->lang->words['mlog_ip']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$row['members_display_name']	= $row['members_display_name'] ? $row['members_display_name'] : $this->lang->words['noname_availablm'];
		
		$IPBHTML .= <<<HTML
		<tr>
			<td><span class='larger_text'>{$row['members_display_name']}</span></td>
			<td><span style='font-weight:bold;color:red'>{$row['action']}</span></td>
			<td><b>{$row['name']}</b></td>
			<td>{$row['topic_title']}{$row['topic']}</td>
			<td>{$row['_time']}</td>
			<td>{$row['ip_address']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' align='center'>{$this->lang->words['mlog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class="acp-box">
	<h3>{$this->lang->words['mlog_thelogs']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='30%'>{$this->lang->words['mlog_member']}</th>
			<th width='20%'>{$this->lang->words['mlog_action']}</th>
			<th width='20%'>{$this->lang->words['mlog_viewall']}</th>
			<th width='30%'>{$this->lang->words['mlog_removeall']}</th>
		</tr>
HTML;

if( count($admins) AND is_array($admins) )
{
	foreach( $admins as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><span class='larger_text'>{$row['members_display_name']}</span></td>
			<td>{$row['act_count']}</td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=view&amp;mid={$row['member_id']}'>{$this->lang->words['mlog_view']}</a></td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remove&amp;mid={$row['member_id']}'>{$this->lang->words['mlog_remove']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='4' align='center'>{$this->lang->words['mlog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='view' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />

	<div class="acp-box">
		<h3>{$this->lang->words['mlog_search']}</h3>
	
		<table class="ipsTable double_pad">
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['mlog_searchfor']}</strong></td>
				<td class='field_field'>{$form['search_for']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['mlog_searchin']}</strong></td>
				<td class='field_field'>{$form['search_in']}</td>
			</tr>
		</table>	

		<div class="acp-actionbar">
			<input value="{$this->lang->words['mlog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View a single moderator log entry
 *
 * @param	array 		Records
 * @param	string		Page links
 * @return	string		HTML
 */
public function modlogsView( $rows, $pages ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['mlog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['mlog_thelogs']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='15%'>{$this->lang->words['mlog_member']}</th>
			<th width='15%'>{$this->lang->words['mlog_action']}</th>
			<th width='15%'>{$this->lang->words['mlog_forum']}</th>
			<th width='25%'>{$this->lang->words['mlog_topictitle']}</th>
			<th width='20%'>{$this->lang->words['mlog_date']}</th>
			<th width='10%'>{$this->lang->words['mlog_ip']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><span class='larger_text'>{$row['members_display_name']}</span></td>
			<td><span style='font-weight:bold;color:red'>{$row['action']}</span></td>
			<td><b>{$row['name']}</b></td>
			<td>{$row['topic_title']}{$row['topic']}</td>
			<td>{$row['_time']}</td>
			<td>{$row['ip_address']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' align='center'>{$this->lang->words['mlog_noresults']}</td>
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

}