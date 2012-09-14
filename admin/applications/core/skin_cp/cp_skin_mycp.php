<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Dashboard skin file
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
 
class cp_skin_mycp
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
 * Main dashboard template
 *
 * @param	array 		Content blocks
 * @param	array 		URLs
 * @return	string		HTML
 */
public function mainTemplate( $content, $ipsNewsData=array(), $nagEntries=array(), $stats=array() ) {

$version = IPB_VERSION;

$stats_html['performance'] = $stats['performance'] ? "<span class='ipsBadge badge_red'>{$this->lang->words['gbl_on']}</span>" : "<span class='ipsBadge badge_green'>{$this->lang->words['gbl_off']}</span>";

$stats_html['server_load'] = "<span class='ipsBadge badge_green'>" . floatval($stats['server_load']) . "</span>";

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2><p class='right' id='dashboard_online'>{$content['acp_online']}</p> {$this->lang->words['welcome']} {$this->memberData['members_display_name']}</h2>
</div>

<div id='dash_server_stats' class='clearfix'>
	<div class='left'>
		<h3>{$this->lang->words['version']}</h3>
		IP.Board {$version}
	</div>
	<div class='right'>
		<h3>{$this->lang->words['server_status']}</h3>
		<ul class='ipsList_inline'>
			<!--<li>
				<a href='{$this->settings['base_url']}&amp;module=tools&amp;section=performance' title='{$this->lang->words['toggle_perf_mode']}'>
					{$stats_html['performance']}
				</a>
				&nbsp;{$this->lang->words['cp_performance']}
			</li>-->
			<li>
				{$stats_html['server_load']}
				&nbsp;{$this->lang->words['cp_serverload']}
			</li>
			<li>
				<span class='ipsBadge badge_grey'>{$stats['active_users']}</span>
				&nbsp;{$this->lang->words['cp_onlineusers']}
			</li>
		</ul>
	</div>
</div>

<!--in_dev_notes-->
<!--in_dev_check-->

<!-- Version Check -->
<div id='ips_update' style='display:none'>
	<div class='warning'>
		<h4>{$this->lang->words['cp_newversion']}: <span id='acp-update-version'></span></h4>
		{$this->lang->words['cp_newversion_info']} <a href='' id='acp-update-link' target='_blank'>{$this->lang->words['cp_newversion_link']}</a>
	</div>
	<br />
</div>

<div id='ips_bulletin' style='display:none'>
	<div class='warning'>
		<h4>{$this->lang->words['cp_ipsbulletin']}</h4>
		<p id='ips_supportbox_content'></p>
	</div>
	<br />
</div>

<div>
	<div style='width: 69%;' class='left' id='dashboard'>
EOF;

if( is_array( $nagEntries ) && count( $nagEntries ) )
{
	$count = count( $nagEntries );
	
	$IPBHTML .= <<<EOF
	<div class='acp-box alt' id='notification_table'>
		<h3>{$count} {$this->lang->words['items_requiring_action']}</h3>
		<table class='ipsTable'>
EOF;

	foreach( $nagEntries as $r )
	{
$IPBHTML .= <<<EOF
		<tr>
			<td style='width: 1%; vertical-align: top'><img src='{$this->settings['skin_acp_url']}/images/icons/warning.png' /></td>
			<td>
				<strong class='larger_text'>{$r[0]}</strong>
				<div style='line-height: 1.5; display: none' class='notice_info'>
					{$r[1]}
				</div>
			</td>
			<td style='width: 80px; vertical-align: top'>
				<spsn class='desctext'><a href='#' class='notice_link'>{$this->lang->words['more_detailslink']}</a></span>
			</td>
		</tr>
EOF;
	}
	
	$IPBHTML .= <<<EOF
		</table>
	</div>
	<script type='text/javascript'>
		jQ(document).ready(function() {
			jQ("#notification_table .notice_link").click( function(e){
				e.preventDefault();
				jQ(this).hide().closest("tr").find(".notice_info").slideDown();
			});
		});	
	</script>
	<br /><br />
EOF;
}

$total_posts	= $this->registry->getClass('class_localization')->formatNumber( $this->caches['stats']['total_replies'] + $this->caches['stats']['total_topics'] );
$total_members	= $this->registry->getClass('class_localization')->formatNumber( $this->caches['stats']['mem_count'] );

$_lang			= sprintf( $this->lang->words['reg_chart_stats'], 7 );

$IPBHTML .= <<<EOF
		<div class='acp-box' style='background: #FFF'>
			<div class='center section_title'><h2>{$_lang}</h2></div>
			<div class='center' id='dashboard_graph' style='padding: 1px;'><img src="{$this->settings['base_url']}module=system&amp;section=charts&amp;days=7" alt="{$this->lang->words['reg_trends_chart']}" style='width:98%;' /></div>
		</div>
		<br /><br />
		<div class='acp-box'>
			<h3>{$this->lang->words['cp_adminnotes']}</h3>
			<form action='{$this->settings['base_url']}&amp;app=core&amp;module=mycp&amp;section=dashboard&amp;save=1' method='post'>
				{$content['ad_notes']}
			</form>
		</div>
	</div>
	<div style='width: 29%;' class='right'>
		<div class='acp-box'>
			<table class='ipsTable'>
				<tr>
					<td style='width: 40%'><strong>{$this->lang->words['details__members']}</strong></td>
					<td style='width: 60%'>{$total_members}</td>
				</tr>
				<tr>
					<td><strong>{$this->lang->words['details__posts']}</strong></td>
					<td>{$total_posts}</td>
				</tr>
			</table>
		</div>
		<br /><br />
		<!--acplogins--><br />
		<div class='acp-box'>
			<h3>{$this->lang->words['cp_ipslatestnews']}</h3>
			<table id='ips_news_content' class='ipsTable'></table>
		</div>
	</div>
</div>

<script type='text/javascript'>
	jQ(".dashboard_note h4").css( { cursor: 'pointer' } ).click( function(e){
		$( e.target ).next(".note_content:first").toggle();
	});
</script>

<br />
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.homepage.js?ipbv={$this->registry->output->antiCacheHash}'></script>

<!-- HIDDEN "INFORMATION" DIV -->
<div id='acp-update-info-wrapper' style='display:none'>
	<h3>{$this->lang->words['cp_noticeupdate']}</h3>
	<div class='acp-box'>
		<p style='text-align: center;padding:6px;padding-top:24px'>
			{$this->lang->words['cp_update_info']}
			<br />
			<br />
			<input type='button' value='{$this->lang->words['cp_visitcc']}' onclick='upgradeContinue()' class='button' />
		</p>
	</div>
</div>
<!-- / HIDDEN "INFORMATION" DIV -->


<script type='text/javascript'>
function upgradeMoreInfo()
{
	curPop = new ipb.Popup( 'acpVersionInfo', {
							type: 'pane',
							modal: true,
							initial: $('acp-update-info-wrapper').innerHTML,
							hideAtStart: false,
							w: '400px',
							h: '150px'
						});
						
	return false;
}

function upgradeContinue()
{
	acp.openWindow( IPSSERVER_download_link, 800, 600 );
}

/* Warning CONTINUE / CANCEL */
function resetContinue()
{
	if ( confirm( "{$this->lang->words['cp_wannareset']}" ) )
	{
		acp.redirect( ipb.vars['base_url'] + "&amp;app=core&amp;module=mycp&amp;section=dashboard&amp;reset_security_flag=1&amp;new_build=" + IPSSERVER_download_ve + "&amp;new_reason=" + IPSSERVER_download_vt, 1 );
	}
}


/* Set up global vars */
var _newsFeed     = null;
var _blogFeed     = null;
var _versionCheck = null;
var _keithFeed    = null;

{$ipsNewsData['news']}
{$ipsNewsData['vcheck']}

/* ---------------------- */
/* ONLOAD: IPS widgets    */
/* ---------------------- */

function onload_ips_widgets()
{		
	/* ---------------------- */
	/* Feeds                  */
	/* ---------------------- */
	
	_newsFeed = setTimeout( '_newsFeedFunction()', 1000 );
	
	/* ---------------------- */
	/* Update boxes           */
	/* ---------------------- */
	
	_versionCheck = setTimeout( '_versionCheckFunction()', 1000 );
	
	/* ---------------------- */
	/* Load Keith             */
	/* ---------------------- */
	
	_keithFeed = setTimeout( '_keithFeedFunction()', 1000 );
}

/* ---------------------- */
/* Keith Feed YumYum      */
/* ---------------------- */

function _keithFeedFunction()
{
	if ( typeof( IPS_KEITH_CONTENT ) != 'undefined' )
	{
		clearTimeout( _keithFeed );
		
		if ( IPS_KEITH_CONTENT && IPS_KEITH_CONTENT != 'none' )
		{
			/* Show version numbers */
			$( 'ips_bulletin' ).style.display = '';
			$( 'ips_supportbox_content' ).innerHTML = IPS_KEITH_CONTENT.replace( /&#0039;/g, "'" );
		}
	}
	else
	{
		_keithFeed = setTimeout( '_keithFeedFunction()', 1000 );
	}
}

/* ---------------------- */
/* Version Check          */
/* ---------------------- */

function _versionCheckFunction()
{
	if ( typeof( IPSSERVER_update_type ) != 'undefined' )
	{
		clearTimeout( _versionCheck );
		
		if ( IPSSERVER_update_type && IPSSERVER_update_type != 'none' )
		{
			$( 'ips_update' ).style.display                = '';

			/* Show version numbers */
			$( 'acp-update-version' ).innerHTML = IPSSERVER_download_vh;
			$( 'acp-update-link' ).href = IPSSERVER_link;
		}
	}
}

/* ---------------------- */
/* NEWS FEED              */
/* ---------------------- */


function _newsFeedFunction()
{
	if ( typeof( ipsNewsFeed ) != 'undefined' )
	{
		clearTimeout( _newsFeed );
		
		eval( ipsNewsFeed );
		var finalString = [];
		var _len        = ipsNewsFeed['items'].length;

		if( typeof( ipsNewsFeed['error'] ) == 'undefined' )
		{
			for( i = 0; i < _len; i++ )
			{
				var _title   = ( ipsNewsFeed['items'][i]['title'].length > 50 ) ? ipsNewsFeed['items'][i]['title'].substr( 0, 47 ) + '...' : ipsNewsFeed['items'][i]['title'];
				$('ips_news_content').insert( (new Element("tr")).insert( new Element("td", { style: "padding: 5px" }).update("<img src='{$this->settings['skin_acp_url']}/images/icons/ipsnews_item.gif' /> <a href='" + ipsNewsFeed['items'][i]['link'] + "' target='_blank' title='" + ipsNewsFeed['items'][i]['title'] + "'>" + _title + "</a>")));
			}
		}
		
		if( !_len ){
			$('ips_news_content').hide();
		}
	}
	else
	{
		_newsFeed = setTimeout( '_newsFeedFunction()', 1000 );
	}
}

/* Set up onload event */
Event.observe( window, 'load', onload_ips_widgets );
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Wrapper for validating users
 *
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_validating_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='dashboard_border'>
	<div class='dashboard_header'>{$this->lang->words['cp_adminvalidationqueue']}</div>
	{$content}
	<div align='right'>
		<a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=validating'>{$this->lang->words['cp_more']} {$this->lang->words['_raquo']}</a>
	 </div>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Validating users row
 *
 * @param	array 		Data
 * @return	string		HTML
 */
public function acp_validating_block( $data ) {

$IPBHTML = "";
//--starthtml--//

$data['url']	= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showuser=' . $data['member_id'], 'none', $data['members_seo_name'], 'showuser' );

$IPBHTML .= <<<EOF
<div class='dashboard_sub_row_alt'>
 <div style='float:right;'>
  <a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=domod&amp;_admin_auth_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}&amp;mid_{$data['member_id']}=1&amp;type=approve'><img src='{$this->settings['skin_acp_url']}/images/aff_tick.png' alt='{$this->lang->words['cp_yes']}' /></a>&nbsp;
  <a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=domod&amp;_admin_auth_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}&amp;mid_{$data['member_id']}=1&amp;type=delete'><img src='{$this->settings['skin_acp_url']}/images/aff_cross.png' alt='{$this->lang->words['cp_no']}' /></a>
 </div>
 <div>
  <strong><a href='{$data['url']}' target='_blank'>{$data['members_display_name']}</a></strong>{$data['_coppa']}<br />
  &nbsp;&nbsp;{$data['email']}</a><br />
  <div class='desctext'>&nbsp;&nbsp;{$this->lang->words['cp_ip']}: <a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=toolsdo=learn_ip&amp;ip={$data['ip_address']}'>{$data['ip_address']}</a></div>
  <div class='desctext'>&nbsp;&nbsp;{$this->lang->words['cp_registered']} {$data['_entry']}</div>
 </div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Show the ACP notes block
 *
 * @param	string		Current notes
 * @return	string		HTML
 */
public function acp_notes($notes) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div style='padding: 5px;'>
	<div id='notes_wrap'>
		<textarea name='notes' id='dashboard_notes' class="dashboard_notes" rows='8' cols='25'>{$notes}</textarea>
	</div>
	<div style='text-align: left; margin-top: 5px; display: none' id='notes_save'>
		<input type='submit' value='{$this->lang->words['cp_savenotes']}' class='button primary' />
	</div>
</div>
<script type='text/javascript'>
	jQ(document).ready(function() {
		jQ("#dashboard_notes").focus( function(e){
			jQ("#notes_save").slideDown();
		});
	});
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a latest login record
 *
 * @param	array 		Record
 * @return	string		HTML
 */
public function acp_last_logins_row( $r ) {

$IPBHTML = "";
//--starthtml--//
if( empty( $r['admin_username'] ) )
{
	$r['admin_username'] = "<em class='desctext'>" . $this->lang->words['cp_none_entered'] . "</em>";
}

$IPBHTML .= <<<EOF
<tr class='ipsControlRow'>
 	<td>
		<strong>{$r['admin_username']}</strong><br />
		<span class='desctext'>
			{$r['_admin_time']}
		</span>
 	</td>
 	<td class='col_buttons'>
		<ul class='ipsControlStrip'>
			<li class='i_cog'>
 				<a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}module=logs&amp;section=loginlog&amp;do=view_detail&amp;detail={$r['admin_id']}', 600, 400)" title='{$this->lang->words['failedlogin_viewdetails']}'>{$this->lang->words['cp_view']}</a>
			</li>
		</ul>
    </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Wrapper for latest ACP logins
 *
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_last_logins_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class="acp-box">
    <h3>
		<a href='{$this->settings['base_url']}&amp;app=core&amp;module=logs&amp;section=loginlog' class='ipsBlock_titlelink'>{$this->lang->words['cp_seeall_logins']} {$this->lang->words['_raquo']}</a>
		{$this->lang->words['cp_latestadminlogins']}
	</h3>
	<table class='ipsTable'>
EOF;

	if( $content ){
		$IPBHTML .= $content;
	} else {
		$IPBHTML .= <<<EOF
		<tr>
			<td class='no_messages'>
				{$this->lang->words['cp_no_failed_logins']}
			</td>
		</tr>
EOF;
	}
	
	$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Admins online wrapper
 *
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_onlineadmin_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

if( count( $content ) )
{	
	foreach( $content as $r ){
		$data[] = "<a href='{$r['seo_link']}' target='_blank' title='{$r['session_location']} {$this->lang->words['cp_from']} {$r['session_ip_address']}'>{$r['members_display_name']}</a>";
	}
	
	$IPBHTML .= "<span class='desctext' style='font-size: 12px'>({$this->lang->words['admins_online']} " . implode(", ", $data) . ")</span>";
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Show latest actions record
 *
 * @param	array 		Record
 * @return	string		HTML
 */
public function acp_lastactions_row( $rowb ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td width='1'>
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/index/user.png' alt='-' />
 </td>
 <td>
	<b>{$rowb['members_display_name']}</b>
	<div class='desctext'>{$this->lang->words['cp_ip']}: {$rowb['ip_address']}</div>
 </td>
 <td>{$rowb['_ctime']}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a warning box
 *
 * @param	string		Title
 * @param	string		Content
 * @return	string		HTML
 */
public function warning_box($title, $content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='warning dashboard_note'>
	<h4>{$title}</h4>
	<div class='note_content' style='display: none'>
		{$content}
	</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a warning that the rebuild following the upgrade hasn't been completed
 *
 * @return	string		HTML
 */
public function warning_rebuild_upgrade() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
   {$this->lang->words['cp_warning_rebuild']}
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show form to change details
 *
 * @return	string		HTML
 */
public function showChangeForm() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform' action='{$this->settings['base_url']}&amp;module=mycp&amp;section=details&amp;do=save' method='post'>
	<div class='acp-box'>
 		<h3>{$this->lang->words['mycp_change_details']}</h3>
		
		<div class='acp_box'>
			<table class='ipsTable double_pad'>
				<tr>
				   <th colspan='2'>{$this->lang->words['change_email_details']}</th>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['change__email']}</strong></td>
					<td class='field_field'><input class='textinput' type='text' name='email' size='30' /></td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['change__email_confirm']}</strong></td>
					<td class='field_field'><input class='textinput' type='text' name='email_confirm' size='30' /></td>
				</tr>
				
				<tr><th colspan='2'>{$this->lang->words['change_pass_details']}</th></tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['change__pass']}</strong></td>
					<td class='field_field'><input class='textinput' type='password' name='password' size='30' /></td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['change__pass_confirm']}</strong></td>
					<td class='field_field'><input class='textinput' type='password' name='password_confirm' size='30' /></td>
				</tr>
			</table>
		</div>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['change__confirm']}' class='button primary' />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}
}