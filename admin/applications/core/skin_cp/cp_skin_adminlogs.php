<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Admin log skin file
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
 
class cp_skin_adminlogs
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
 * Archive log wrapper
 *
 * @param	array 		Rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function archiverlogsView( $rows, $links ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$this->lang->words['archiver_title']}</h3>
	<table class='ipsTable'>
HTML;

if( count( $rows ) AND is_array( $rows ) )
{
	foreach( $rows as $row )
	{
		$text = '';
		
		if ( $row['archlog_is_restore'] )
		{
			$text = sprintf( $this->lang->words['archlog_restored'], $row['archlog_count'] );	
		}
		else if ( $row['archlog_is_error'] )
		{
			$text = sprintf( $this->lang->words['archlog_error'], $row['archlog_msg'] );
		}
		else
		{
			$text = sprintf( $this->lang->words['archlog_archived'], $row['archlog_count'] );
		}
		
		$date = $this->lang->getDate( $row['archlog_date'], 'short' );
		
		$IPBHTML .= <<<HTML
		<tr>
			<td width="70%">{$text}</td>
			<td width="30%">{$date}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='2' align='center'>{$this->lang->words['error_log_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
        {$links}
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the splash screen for the logs
 *
 * @return	string		HTML
 */
public function logSplashScreen() {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['choose_splash']}</h3>
	
	<table class='ipsTable'>
HTML;
if ( $this->registry->class_permissions->checkPermission( 'errorlogs_view' ) )
{
	$IPBHTML .= <<<HTML
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=errorlogs'>{$this->lang->words['error_log_thelogs']}</a></td></tr>
HTML;
}
if ( $this->registry->class_permissions->checkPermission( 'adminlogs_view' ) )
{
	$IPBHTML .= <<<HTML
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=adminlogs'>{$this->lang->words['alog_adminlogs']}</a></td></tr>
HTML;
}
if ( $this->registry->class_permissions->checkPermission( 'modlogs_view' ) )
{
	$IPBHTML .= <<<HTML
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=modlogs'>{$this->lang->words['mlog_modlogs']}</a></td></tr>
HTML;
}
if ( $this->registry->class_permissions->checkPermission( 'emailerrorlogs_view' ) )
{
	$IPBHTML .= <<<HTML
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=emailerrorlogs'>{$this->lang->words['elog_email_err_logs']}</a></td></tr>
HTML;
}
if ( $this->registry->class_permissions->checkPermission( 'spiderlogs_view' ) )
{
	$IPBHTML .= <<<HTML
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=spiderlogs'>{$this->lang->words['slog_spider_logs']}</a></td></tr>
HTML;
}
if ( $this->registry->class_permissions->checkPermission( 'warnlogs_view' ) )
{
	$IPBHTML .= <<<HTML
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=warnlogs'>{$this->lang->words['wlog_warn_logs']}</a></td></tr>
HTML;
}
$IPBHTML .= <<<HTML
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=xmlrpclogs'>{$this->lang->words['api_error_logs']}</a></td></tr>
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=tasklogs'>{$this->lang->words['sched_error_logs']}</a></td></tr>
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=loginlog&amp;do=show'>{$this->lang->words['al_error_logs']}</a></td></tr>
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=spamlogs&amp;do=show'>{$this->lang->words['slog_spamlogs']}</a></td></tr>
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=mobilelogs&amp;do=show'>{$this->lang->words['mlog_mobilelogs']}</a></td></tr>
HTML;
if ( $this->registry->class_permissions->checkPermission( 'sqlerrorlogs_view' ) )
{
	$IPBHTML .= <<<HTML
		<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=sqlerror&amp;do=show'>{$this->lang->words['mlog_sqlerrors']}</a></td></tr>
HTML;
}
	$IPBHTML .= <<<HTML
	<tr><td><a href='{$this->settings['base_url']}module=logs&amp;section=adminlogs&amp;do=showArchived'>{$this->lang->words['mlog_showarchived']}</a></td></tr>
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the splash screen for the admin logs
 *
 * @param	array 		Rows
 * @param	array 		Admins
 * @return	string		HTML
 */
public function adminlogsWrapper( $rows, $admins ) {

$form_array 		= array(
							0 => array( 'member_id'		, $this->lang->words['alog_id'] ),
							1 => array( 'note'			, $this->lang->words['alog_performed'] ),
							2 => array( 'ip_address'	, $this->lang->words['alog_ip']  ),
							3 => array( 'appcomponent'	, $this->lang->words['alog_app']  ),
							4 => array( 'module'		, $this->lang->words['alog_mod']  ),
							5 => array( 'section'		, $this->lang->words['alog_sec']  ),
							6 => array( 'do'			, $this->lang->words['alog_do']  ),
						);
$form				= array();

$form['search_for']	= $this->registry->output->formInput( "search_string" );
$form['search_in']	= $this->registry->output->formDropdown( "search_type", $form_array );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['alog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['alog_last5']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='20%'>{$this->lang->words['alog_member']}</th>
			<th width='40%'>{$this->lang->words['alog_performed']}</th>
			<th width='20%'>{$this->lang->words['alog_date']}</th>
			<th width='20%'>{$this->lang->words['alog_ip']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><span class='larger_text'>{$row['members_display_name']}</span></td>
			<td>{$row['note']}</td>
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
			<td colspan='4' align='center'>{$this->lang->words['alog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class="acp-box">
	<h3>{$this->lang->words['alog_saved']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='20%'>{$this->lang->words['alog_member']}</th>
			<th width='40%'>{$this->lang->words['alog_performed']}</th>
			<th width='20%'>{$this->lang->words['alog_viewall']}</th>
			<th width='20%'>{$this->lang->words['alog_removeall']}</th>
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
			<td><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=view&amp;mid={$row['member_id']}'>{$this->lang->words['alog_view']}</a></td>
			<td><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;mid={$row['member_id']}'>{$this->lang->words['alog_remove']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='4' align='center'>{$this->lang->words['alog_noresults']}</td>
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
		<h3>{$this->lang->words['alog_search']}</h3>
		<table class='ipsTable double_pad'>
			<tr>	
				<td class='field_title'><strong class='title'>{$this->lang->words['alog_searchfor']}</strong></td>
				<td class='field_field'>{$form['search_for']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['alog_searchin']}</strong></td>
				<td class='field_field'>{$form['search_in']}</td>
			</tr>
		</table>
		<div class="acp-actionbar">
			<input value="{$this->lang->words['alog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * SQL log RAPPER LIKE ICE T BUT MORE LIKE COFFEE
 *
 * @param	array 		Rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function sqllogsWrapper( $rows, $latestError ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sqllog_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href="#" onclick="acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete_all');">
				<img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' />
				{$this->lang->words['delete_all_sqllogs']}
			</a>
		</li>
	</ul>
</div>
HTML;
if ( $latestError )
{
	$IPBHTML .= <<<HTML
	<div class='information-box'>
		$latestError
	</div>
	<br />
HTML;
}

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$this->lang->words['sqllog_title']}</h3>
	<table class="ipsTable">
		<tr>
			<th width="1%">&nbsp;</th>
			<th width='49%'>{$this->lang->words['sqllog_name']}</th>
			<th width='40%'>{$this->lang->words['sqllog_date']}</th>
			<th width='10%'>{$this->lang->words['sqllog_size']}</th>
			<th class="col_buttons">&nbsp;</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$size = IPSLib::sizeFormat( $row['size'] );
		$mtime = $this->registry->class_localization->getDate( $row['mtime'], 'SHORT' );
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/page_red.png' /></td>
			<td><a href="{$this->settings['base_url']}{$this->form_code}&amp;do=view&amp;file={$row['name']}">{$row['name']}</a></td>
			<td>{$mtime}</td>
			<td>{$size}</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_view'><a href="{$this->settings['base_url']}{$this->form_code}&amp;do=view&amp;file={$row['name']}" title='{$this->lang->words['slog_view']}'>{$this->lang->words['slog_view']}</a></li>
					<li class='i_delete'><a href="#" onclick="acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;file={$row['name']}' )" title='{$this->lang->words['delete']}'>{$this->lang->words['delete']}</a></li>
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
			<td colspan='5' class='no_messages'>{$this->lang->words['sqllog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
		<div class="leftaction">&nbsp;</div>
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * View a log
 *
 */
public function sqlLogsView( $file, $size, $content, $tailSize ) {

/* Display a message? */
if ( $tailSize < $size )
{
	$this->registry->output->setMessage( sprintf( $this->lang->words['sqllog_more_file'], $file ), true );
}

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sqllog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$file}</h3>
	<div style='padding:8px'>
		<div style="width:100%; height:400px; background-color:white;font-family:monospace;white-space:pre;overflow:auto">{$content}</div>
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View an individual admin's logs
 *
 * @param	array 		Rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function adminlogsView( $rows, $pages ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['alog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['alog_saved']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='20%'>{$this->lang->words['alog_member']}</th>
			<th width='40%'>{$this->lang->words['alog_performed']}</th>
			<th width='20%'>{$this->lang->words['alog_date']}</th>
			<th width='20%'>{$this->lang->words['alog_ip']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['members_display_name']}</td>
			<td><span style='color:{$row['color']}'>{$row['note']}</span></td>
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
			<td colspan='4' align='center'>{$this->lang->words['alog_noresults']}</td>
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