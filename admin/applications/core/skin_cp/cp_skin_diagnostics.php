<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Diagnostics skin file
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
 
class cp_skin_diagnostics
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
 * Connection checker
 *
 * @param	string		Raw HTTP headers
 * @param	string		Request result
 * @param	string		Raw HTTPS headers
 * @param	string		Request result
 * @return	string		HTML
 */
public function connectionCheckerResult( $headers='', $output='', $headers_ssl='', $output_ssl='' ) {

$IPBHTML = "";
//--starthtml--//

$output	= IPSText::mbsubstr( htmlspecialchars($output), 0, 2000 );
$output_ssl	= IPSText::mbsubstr( htmlspecialchars($output_ssl), 0, 2000 );

$IPBHTML .= <<<EOF
<div class='information-box'>
	{$this->lang->words['connections__message']}
</div>
<br />
<div class='section_title'>
	<h2>{$this->lang->words['connectionchecker']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['connections__headers']}</h3>
	<pre style='overflow: auto;'>{$headers}</pre>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['connections__output']}</h3>
	<pre style='overflow: auto;'>{$output}</pre>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['connections__headers1']}</h3>
	<pre style='overflow: auto;'>{$headers_ssl}</pre>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['connections__output1']}</h3>
	<pre style='overflow: auto;'>{$output_ssl}</pre>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * DB Index checker
 *
 * @param	array 		Errors
 * @param	array 		Tables
 * @return	string		HTML
 */
public function indexChecker( $errors=array(), $tables=array(), $queriesRan=array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['d_ititle']}</h2>
</div>
HTML;

if( count( $errors ) )
{
$IPBHTML .= <<<HTML
<div class='warning'>
	{$this->lang->words['d_ifixall']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;section=diagnostics&amp;do=dbindex&amp;fix=all'>{$this->lang->words['d_ifixall_link']}</a>
</div>
<br />
HTML;
}

if ( !empty( $queriesRan ) )
{
	foreach ( $queriesRan as $data )
	{
		if ( $data['status'] === TRUE )
		{
			$IPBHTML .= <<<HTML
<div class='information-box'>
	{$data['q']}<br />
	<strong>{$this->lang->words['q_ok']}</strong>
</div>
<br />
HTML;
		}
		else
		{
			$IPBHTML .= <<<HTML
<div class='warning'>
	{$data['q']}<br />
	<strong>{$this->lang->words['q_fail']}</strong>
</div>
<br />
HTML;
		}
	}
}

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['d_dnav']}</h3>

	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['chckr_table']}</th>
			<th>{$this->lang->words['chckr_status']}</th>
			<th>{$this->lang->words['chckr_fix']}</th>
		</tr>
HTML;

if( is_array( $tables ) && count( $tables ) )
{
	$i = 0;
	foreach( $tables as $app_title => $_tables )
	{
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='3'>{$app_title}</th>
		</tr>
HTML;
		foreach( $_tables as $r )
		{
			if( $r['status'] == 'ok' )
			{
$IPBHTML .= <<<HTML
		<tr>
			<td><span style='color:green'>{$r['table']}</span></td>
			<td>
				<span style='color:green'>
				<ul class='bullets'>
					<li>
HTML;
if ( is_array( $r['index'] ) )
{
$IPBHTML .= implode( "</li><li>", $r['index'] );
}
$IPBHTML .= <<<HTML
					</li>
				</ul>
				</span>
			</td>
			<td>&nbsp;</td>
		</tr>
HTML;
			}
			else
			{
$IPBHTML .= <<<HTML
		<tr>
			<td><span style='color:red'>{$r['table']}</span></td>
			<td>
				<ul class='bullets'>
HTML;

foreach( $r['index'] as $index )
{
	if( in_array( $index, $r['missing'] ) )
	{
		$IPBHTML .= "<li><span style='color:red'>Missing index: {$index}</span></li>";
	}
	else
	{
		$IPBHTML .= "<li><span style='color:green'>{$index}</li>";
	}
}
$IPBHTML .= <<<HTML
				</ul>
			</td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;section=diagnostics&amp;do=dbindex&amp;fix={$r['table']}'>{$this->lang->words['d_inauto']}</a>{$this->lang->words['d_iman']}
				<div>
					<ul class='bullets'>
						<li>
HTML;

$IPBHTML .= implode( "</li><li>", $r['fixsql'] );
$IPBHTML .= <<<HTML
						</li>
					</ul>
				</div>
			</td>
		</tr>
HTML;
			}
		}
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
 * DB checker
 *
 * @param	array 		Errors
 * @param	array 		Tables
 * @return	string		HTML
 */
public function dbChecker( $errors=array(), $tables=array(), $queriesRan=array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['d_dtitle']}</h2>
</div>
HTML;

if( count( $errors ) )
{
$IPBHTML .= <<<HTML
<div class='warning'>
	{$this->lang->words['d_ifixall']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;section=diagnostics&amp;do=dbchecker&amp;fix=all'>{$this->lang->words['d_ifixall_link']}</a>
</div>
<br />
HTML;
}

if ( !empty( $queriesRan ) )
{
	foreach ( $queriesRan as $data )
	{
		if ( $data['status'] === TRUE )
		{
			$IPBHTML .= <<<HTML
<div class='information-box'>
	{$data['q']}<br />
	<strong>{$this->lang->words['q_ok']}</strong>
</div>
<br />
HTML;
		}
		else
		{
			$IPBHTML .= <<<HTML
<div class='warning'>
	{$data['q']}<br />
	<strong>{$this->lang->words['q_fail']}</strong>
</div>
<br />
HTML;
		}
	}
}

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['d_dnav']}</h3>

	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['d_dtbl']}</th>
			<th>{$this->lang->words['d_dstatus']}</th>
			<th>{$this->lang->words['d_dfix']}</th>
		</tr>
HTML;

if( count( $tables ) )
{
	$i = 0;
	foreach( $tables as $app_title => $_tables )
	{	
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='3'>{$app_title}</th>
		</tr>
HTML;
		if( !is_array($_tables) OR !count($_tables) )
		{
			continue;
		}
		
		foreach( $_tables as $r )
		{
			$_column	= '';
			
			if( $r['column'] )
			{
				$_column	= ' (' . $r['column'] . ')';
			}
			
			if( $r['status'] == 'ok' )
			{
$IPBHTML .= <<<HTML
		<tr>
			<td><span style='color:green'>{$r['table']}{$_column}</span></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/aff_tick.png' alt='YN' /></td>
			<td>&nbsp;</td>
		</tr>
HTML;
			}
			else
			{
$IPBHTML .= <<<HTML
		<tr>
			<td><span style='color:red'>{$r['table']}</span></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/aff_cross.png' alt='YN' /></td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;section=diagnostics&amp;do=dbchecker&amp;fix={$r['key']}'>{$this->lang->words['d_iauto']}</a>{$this->lang->words['d_iman']}
				<div>
					<ul class='bullets'>
						<li>
HTML;

$IPBHTML .= implode( "</li><li>", $r['fixsql'] );
$IPBHTML .= <<<HTML
						</li>
					</ul>
				</div>
			</td>
		</tr>
HTML;
			}
		}
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
 * Diagnostics overview
 *
 * @param	array 		Data
 * @return	string		HTML
 */
public function diagnosticsOverview( $data=array(), $statsbox ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['d_atitle']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['cp_systemstats']}</h3>
	<table class='ipsTable'>
		{$statsbox}
	</table>
</div>
<br />

<div class="acp-box">
	<h3>{$this->lang->words['sys_system_overview']}</h3>
	<table class='ipsTable'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_ipboard_version']}</strong></td>
			<td class='field_field'>{$data['version']} (ID:{$data['version_full']})</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$data['driver_type']} {$this->lang->words['sys_version']}</strong></td>
			<td class='field_field'>{$data['version_sql']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_php_version']}</strong></td>
			<td class='field_field'>{$data['version_php']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_disabled_funcs']}</strong></td>
			<td class='field_field'>{$data['disabled']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_loaded_ext']}</strong></td>
			<td class='field_field'>{$data['extensions']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_safe_mod']}</strong></td>
			<td class='field_field'>{$data['safe_mode']}</td>
		</tr>
EOF;
	if ( defined( 'IPS_TOPICMARKERS_DEBUG' ) and IPS_TOPICMARKERS_DEBUG === TRUE )
	{
		$IPBHTML .= <<<EOF
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['topicmarkingdebug']}</strong></td>
			<td class='field_field'>{$this->lang->words['tmdebug_on']} ( <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=tm_index'>{$this->lang->words['tmdebug_logs']}</a> )</td>
		</tr>
EOF;
	}

$IPBHTML .= <<<EOF
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_sys_software']}</strong></td>
			<td class='field_field'>{$data['server']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_current_load']}</strong></td>
			<td class='field_field'>{$data['load']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_total_mem']}</strong></td>
			<td class='field_field'>{$data['total_memory']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_avail_mem']}</strong></td>
			<td class='field_field'>{$data['avail_memory']}</td>
		</tr>
	</table>
</div>
<br />
<div class="acp-box">
	<h3>{$this->lang->words['system_processes']}</h3>
	<table class='ipsTable'>
		<tr>
			<td width='100%'>{$data['tasks']}</td>
		</tr>
	</table>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Permission checker results
 *
 * @param	array 		Results from permission checking
 * @return	string		HTML
 */
public function permissionsResults( $results=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['d_ptitle']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['perm_check_results']}</h3>
	<table class='ipsTable'>
EOF;

if( count($results) AND is_array($results) )
{
	foreach( $results as $result )
	{
		$IPBHTML .= <<<EOF
			<tr>
				<td>{$result}</td>
			</tr>
EOF;
	}
}

$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Whitespace checker
 *
 * @param	array 		Results from checking for whitespace
 * @return	string		HTML
 */
public function whitespaceResults( $results=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['d_wtitle']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['d_wnav']}</h3>
	<table class='ipsTable'>
EOF;

if( count($results) AND is_array($results) )
{
	foreach( $results as $result )
	{
		$IPBHTML .= <<<EOF
			<tr>
				<td><strong>{$result} {$this->lang->words['d_wfound']}</strong></td>
			</tr>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
	<tr>
		<td>{$this->lang->words['d_wclear']}</td>
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
 * phpinfo() output
 *
 * @param	string		Content from running phpinfo()
 * @return	string		HTML
 */
public function phpInfo( $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<style type='text/css'>
.center {text-align: center;}
.center table { margin-left: auto; margin-right: auto; text-align: left; }
.center th { text-align: center; }
h1 {font-size: 150%;}
h2 {font-size: 125%;}
.p {text-align: left;}
.e {background-color: #ccccff; font-weight: bold;}
.h {background-color: #9999cc; font-weight: bold;}
.v {background-color: #cccccc; white-space: normal;}
</style>

{$content}
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * ACP Statistics wrapper
 *
 * @param	array		Content
 * @return	string		HTML
 */
public function acp_stats_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<tr>
		<td class='field_title'>
			<strong class='title'>{$this->lang->words['cp_totalmembers']}</strong>
			<div class='sub desctext'>
				{$this->lang->words['cp_onlinenow']}<br />
				{$this->lang->words['cp_awaitingvalidation']}<br />
				{$this->lang->words['cp_lockedaccounts']}<br />
				{$this->lang->words['cp_spammeraccounts']}
			</div>				
		</td>
		<td class='field_field' style='vertical-align: top;'>
			{$content['members']}
			<div class='sub desctext'>
				<a href='{$this->settings['board_url']}/index.php?app=members&amp;section=online&amp;module=online' target='_blank' title='{$this->lang->words['cp_onlinenow_info']}'>{$this->lang->words['cp_view']}</a> ({$content['sessions']})<br />
				<a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;do=members_overview&amp;type=validating'>{$this->lang->words['cp_manage']}</a> ({$content['validate']})<br />
				<a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;do=members_overview&amp;type=locked'>{$this->lang->words['cp_manage']}</a> ({$content['locked']})<br />
				<a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;do=members_overview&amp;type=spam'>{$this->lang->words['cp_manage']}</a> ({$content['spammer']})
			</div>
		</td>
	</tr>
	<tr>
		<td class='field_title'>
			<strong class='title'>{$this->lang->words['cp_topics']}</strong>
			<div class='sub desctext'>
				{$this->lang->words['cp_awaitingmoderation']}
			</div>
		</td>
		<td class='field_field'>{$content['topics']}<br /><span class='desctext'>{$content['topics_mod']}</span></td>
	</tr>
	<tr>
		<td class='field_title'>
			<strong class='title'>{$this->lang->words['cp_posts']}</strong>
			<div class='sub desctext'>
				{$this->lang->words['cp_awaitingmoderation']}
			</div>
		</td>
		<td class='field_field'>{$content['replies']}<br /><span class='desctext'>{$content['posts_mod']}</span></td>
	</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Email tester
 *
 * @param	sring		Error message
 * @return	string		HTML
 */
public function emailChecker( $error='' ) {

$form = array();
$form['to']	= $this->registry->output->formInput( 'to', $this->memberData['email'] );
$form['from'] = $this->registry->output->formInput( 'from', $this->settings['email_out'] );
$form['subject'] = $this->registry->output->formInput( 'subject', $this->lang->words['email_tester_subject_default'] );
$form['message'] = $this->registry->output->formTextArea( 'message', $this->lang->words['email_tester_message_default'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['email_tester']}</h2>
</div>

EOF;

if ( $error )
{
	$IPBHTML .= <<<EOF
<div class='warning'>
	{$error}
</div>
<br />
EOF;
} 

$IPBHTML .= <<<EOF
<div class='information-box'>
	{$this->lang->words['email_tester_desc']}
</div>
<br />

<form action='{$this->settings['base_url']}&amp;module=diagnostics&section=diagnostics&do=doemail' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['email_tester']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['email_tester_to']}</strong></td>
				<td class='field_field'>
					{$form['to']}<br />
					<span class='desctext'>{$this->lang->words['email_tester_to_desc']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['email_tester_from']}</strong></td>
				<td class='field_field'>
					{$form['from']}
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['email_tester_subject']}</strong></td>
				<td class='field_field'>
					{$form['subject']}
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['email_tester_message']}</strong></td>
				<td class='field_field'>
					{$form['message']}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='realbutton' value='{$this->lang->words['email_tester_go']}' />
		</div>
	</div>
</form>

EOF;

//--endhtml--//
return $IPBHTML;
}

}