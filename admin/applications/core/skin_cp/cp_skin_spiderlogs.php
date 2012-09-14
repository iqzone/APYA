<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Spiderlogs skin file
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
 
class cp_skin_spiderlogs
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
 * Spider logs wrapper
 *
 * @param	array 		Spider log rows
 * @return	string		HTML
 */
public function spiderlogsWrapper( $rows ) {

$form				= array();

$form['search_for']	= $this->registry->output->formInput( "search_string" );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['slog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['slog_thelogs']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='20%'>{$this->lang->words['slog_botname']}</th>
			<th width='20%'>{$this->lang->words['slog_hits']}</th>
			<th width='20%'>{$this->lang->words['slog_last']}</th>
			<th width='20%'>{$this->lang->words['slog_viewall']}</th>
			<th width='20%'>{$this->lang->words['slog_removeall']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><span class='larger_text'>{$row['_bot_name']}</span></td>
			<td>{$row['cnt']}</td>
			<td>{$row['_time']}</td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=view&amp;bid={$row['_bot_url']}'>{$this->lang->words['slog_view']}</a></td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remove&amp;bid={$row['_bot_url']}'>{$this->lang->words['slog_remove']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' align='center'>{$this->lang->words['slog_noresults']}</td>
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
		<h3>{$this->lang->words['slog_search']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['slog_searchfor']}</strong></td>
				<td class='field_field'>{$form['search_for']} <br /> {$this->lang->words['slog_searchquery']}</td>
			</tr>
		</table>
		<div class="acp-actionbar">
			<input value="{$this->lang->words['slog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View a single spider's log entry
 *
 * @param	array 		Rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function spiderlogsView( $rows, $pages ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['slog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['slog_thelogs']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='25%'>{$this->lang->words['slog_botname']}</th>
			<th width='25%'>{$this->lang->words['slog_querystring']}</th>
			<th width='25%'>{$this->lang->words['slog_time']}</th>
			<th width='25%'>{$this->lang->words['slog_ip']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><span class='larger_text'>{$row['_bot_name']}</span></td>
			<td>{$row['_query_string']}</td>
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
			<td colspan='6' align='center'>{$this->lang->words['slog_noresults']}</td>
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