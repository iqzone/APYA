<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * System tools skin file
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
 
class cp_skin_system
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
 * User interface content tabs
 *
 * @param	array 		Tabs
 * @param	array 		Content blocks to show
 * @param	string		Default tab
 * @return	string		HTML
 */
public function ui_content_tabs( $tabs, $contents, $default_tab='' )
{
$HTML = <<<EOF

<a name='#tabpane'></a>
<div class='ipsTabBar' id='tabstrip'>
	<span class='tab_left'>&laquo;</span>
	<span class='tab_right'>&raquo;</span>
	<ul>
EOF;

foreach( $tabs as $t )
{
$HTML .= <<<EOF
			<li id='tab_{$t['id']}' {$t['js']}>
				{$t['text']}
			</li>
EOF;

}

$HTML .= <<<EOF
	</ul>
</div>

<div id='tabContent' class='ipsTabBar_content'>
EOF;

foreach( $contents as $c )
{
$HTML .= <<<EOF
	<div id='tab_{$c['id']}_content'>
		{$c['content']}
	</div>
EOF;
}

$HTML .= <<<EOF
</div>
<script type='text/javascript'>
	jQ("#tabstrip").ipsTabBar({tabWrap: "#tabContent"});
</script>
EOF;

return $HTML;

}

/**
 * View task manager logs
 *
 * @param	array 		Rows
 * @return	string		HTML
 */
public function taskManagerLogsShowWrapper( $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
 <h3>{$this->lang->words['sys_task_manager_logs']}</h3>
 <table class='ipsTable'>
 	<tr>
  		<th>{$this->lang->words['sys_task_run']}</th>
  		<th>{$this->lang->words['sys_date_run']}</th>
  		<th>{$this->lang->words['sys_log_info']}</th>
 	</tr>
HTML;

foreach( $rows as $data )
{
$IPBHTML .= <<<HTML
<tr>
 <td><strong>{$data['log_title']}</strong></td>
 <td>{$data['log_date']}</td>
 <td>{$data['log_desc']}</td>
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

/**
 * Task manager logs overview
 *
 * @param	array 		Last 5 log rows
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function taskManagerLogsOverview( $last5, $form ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['sys_last_5_run_tasks']}</h3>
	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['sys_task_run']}</th>
			<th>{$this->lang->words['sys_date_run']}</th>
			<th>{$this->lang->words['sys_log_info']}</th>
		</tr>
HTML;

foreach( $last5 as $data )
{
$IPBHTML .= <<<HTML
		<tr>
			 <td><strong>{$data['log_title']}</strong></td>
			 <td>{$data['log_date']}</td>
			 <td>{$data['log_desc']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
 	</table>
</div>

<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=task_log_show' method='post'>
<div class="acp-box">
	<h3>{$this->lang->words['sys_view_task_manager_logs']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_view_logs_for_task']}</strong></td>
			<td class='field_field'>{$form['task_title']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_show_n_log_entries']}</strong></td>
			<td class='field_field'>{$form['task_count']}</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input class='button primary' type='submit' value='{$this->lang->words['sys_view_logs']}' />
	</div>
</div>
</form>

<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=task_log_delete' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['sys_delete_task_manager_logs']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_delete_logs_for_task']}</strong></td>
			<td class='field_field'>{$form['task_title_delete']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_delete_logs_older_than_n_days']}</strong></td>
			<td class='field_field'>{$form['task_prune']}</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input class='button primary' type='submit' value='{$this->lang->words['sys_delete_logs']}' />
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Task manager last 5 row entry
 *
 * @param	array 		Log data
 * @return	string		HTML
 */
public function task_manager_last5_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
	<tr>
		 <td width='25%'><strong>{$data['log_title']}</strong></td>
		 <td width='15%'>{$data['log_date']}</td>
		 <td width='45%'>{$data['log_desc']}</td>
	</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a task
 *
 * @param	array 		Form elements
 * @param	string		Button text
 * @param	string		Form action
 * @param	string		Type (add|edit)
 * @param	string		Form title
 * @param	array 		Task data
 * @return	string		HTML
 */
public function taskManagerForm( $form, $button, $formbit, $type, $title, $task ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript' language='javascript'>
function updatepreview()
{
	var formobj  = document.adminform;
	var dd_wday  = new Array();
	
	dd_wday[0]   = '{$this->lang->words['sys_sunday']}';
	dd_wday[1]   = '{$this->lang->words['sys_monday']}';
	dd_wday[2]   = '{$this->lang->words['sys_tuesday']}';
	dd_wday[3]   = '{$this->lang->words['sys_wednesday']}';
	dd_wday[4]   = '{$this->lang->words['sys_thursday']}';
	dd_wday[5]   = '{$this->lang->words['sys_friday']}';
	dd_wday[6]   = '{$this->lang->words['sys_saturday']}';
	
	var output       = '';
	
	chosen_min   = formobj.task_minute.options[formobj.task_minute.selectedIndex].value;
	chosen_hour  = formobj.task_hour.options[formobj.task_hour.selectedIndex].value;
	chosen_wday  = formobj.task_week_day.options[formobj.task_week_day.selectedIndex].value;
	chosen_mday  = formobj.task_month_day.options[formobj.task_month_day.selectedIndex].value;
	
	var output_min   = '';
	var output_hour  = '';
	var output_day   = '';
	var timeset      = 0;
	
	if ( chosen_mday == -1 && chosen_wday == -1 )
	{
		output_day = '';
	}
	
	if ( chosen_mday != -1 )
	{
		output_day = '{$this->lang->words['sys_on_day']} '+chosen_mday+'.';
	}
	
	if ( chosen_mday == -1 && chosen_wday != -1 )
	{
		output_day = '{$this->lang->words['sys_on']} ' + dd_wday[ chosen_wday ]+'.';
	}
	
	if ( chosen_hour != -1 && chosen_min != -1 )
	{
		output_hour = '{$this->lang->words['sys_at']} '+chosen_hour+':'+formatnumber(chosen_min)+'.';
	}
	else
	{
		if ( chosen_hour == -1 )
		{
			if ( chosen_min == 0 )
			{
				output_hour = '{$this->lang->words['sys_on_every_hour']}';
			}
			else
			{
				if ( output_day == '' )
				{
					if ( chosen_min == -1 )
					{
						output_min = '{$this->lang->words['sys_every_minute']}';
					}
					else
					{
						output_min = '{$this->lang->words['sys_every']} '+chosen_min+' {$this->lang->words['sys_minutes']}.';
					}
				}
				else
				{
					output_min = '{$this->lang->words['sys_at']} '+formatnumber(chosen_min)+' {$this->lang->words['sys_minutes_past_the_first_availab']}';
				}
			}
		}
		else
		{
			if ( output_day != '' )
			{
				output_hour = '{$this->lang->words['sys_at']} ' + chosen_hour + ':00';
			}
			else
			{
				output_hour = '{$this->lang->words['sys_every']} ' + chosen_hour + ' {$this->lang->words['sys_hours']}';
			}
		}
	}
	
	output = output_day + ' ' + output_hour + ' ' + output_min;
	
	$('handy_hint').update( output );
}
							
function formatnumber(num)
{
	if ( num == -1 )
	{
		return '00';
	}
	if ( num < 10 )
	{
		return '0'+num;
	}
	else
	{
		return num;
	}
}

</script>
<form name='adminform' action='{$this->settings['base_url']}{$this->form_code}&amp;do={$formbit}&amp;task_id={$task['task_id']}&amp;type={$type}&amp;app_dir={$task['task_application']}' method='post' id='task_manager'>
<input type='hidden' name='task_cronkey' value='{$task['task_cronkey']}' />
<div class="acp-box">
	<h3>{$title}</h3>
  	
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_title']}</strong></td>
			<td class='field_field'>{$form['task_title']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_short_description']}</strong></td>
			<td class='field_field'>{$form['task_description']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_application']}</strong></td>
			<td class='field_field'>{$form['task_application']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_php_file_to_run']}</strong></td>
			<td class='field_field'>/admin/applications/{task_application}/tasks/{$form['task_file']}<br /><span class="desctext">{$this->lang->words['sys_this_is_the_php_file_that_is_r']}</span></td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sys_time_options']}</th>
		</tr>
	    <tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_time_minutes']}</strong></td>
			<td class='field_field'>{$form['task_minute']}<br /><span class="desctext">{$this->lang->words['sys_choose_every_minute_to_run_eac']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_time_hours']}</strong></td>
			<td class='field_field'>{$form['task_hour']}<br /><span class="desctext">{$this->lang->words['sys_choose_every_hour_to_run_each_']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_time_week_day']}</strong></td>
			<td class='field_field'>{$form['task_week_day']}<br /> <span class="desctext">{$this->lang->words['sys_choose_every_day_to_run_each_d']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_time_month_day']}</strong></td>
			<td class='field_field'>{$form['task_month_day']}<br /><span class="desctext">{$this->lang->words['sys_choose_every_day_to_run_each_d_1']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['taskthereforrun']}</strong></td>
			<td class='field_field'><em id='handy_hint'><span style='color: gray;'>{$this->lang->words['selectimeunitsabove']}</span></em></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_enable_task_logging']}</strong></td>
			<td class='field_field'>{$form['task_log']}<br /><span class="desctext">{$this->lang->words['sys_will_write_to_the_task_log_eac']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_enable_task']}</strong></td>
			<td class='field_field'>{$form['task_enabled']}<br /><span class="desctext">{$this->lang->words['sys_if_you_are_using_cron_you_migh']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_key']}</strong></td>
			<td class='field_field'>{$form['task_key']}<br /><span class="desctext">{$this->lang->words['sys_this_is_used_to_call_a_task_wh']}</span></td>
		</tr>
HTML;
//startif
if ( IN_DEV )
{		
$IPBHTML .= <<<HTML
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sys_task_safe_mode']}</strong></td>
			<td class='field_field'>{$form['task_safemode']}<br /><span class="desctext">{$this->lang->words['sys_if_set_to_yes_this_will_not_be']}</span></td>
		</tr>
HTML;
}//endif
$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
		<input type='submit' class='button primary' value='{$button}' />
	</div>
</div>
</form>

<script type='text/javascript'>
	updatepreview();
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Task manager overview
 *
 * @param	array 		Tasks
 * @param	string		Current date
 * @return	string		HTML
 */
public function taskManagerOverview( $tasks, $date ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sys_system_schedular']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'><a href='{$this->settings['base_url']}module=system&amp;section=taskmanager&amp;do=task_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['sym_add_new_task']}</a></li>
			<li class='ipsActionButton'><a href='javascript:void(0);' onclick='importTask()'><img src='{$this->settings['skin_acp_url']}/images/icons/import.png' alt='' /> {$this->lang->words['t_import_single']}</a></li>
			<li class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=cron'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['task_use_cron']}</a></li>
			<li class='ipsActionButton inDev'><a href='{$this->settings['base_url']}module=system&amp;section=taskmanager&amp;do=task_export_xml'><img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' /> {$this->lang->words['sym_export_tasksxml']}</a></li>
			<li class='ipsActionButton inDev'><a href='{$this->settings['base_url']}module=system&amp;section=taskmanager&amp;do=task_rebuild_xml'><img src='{$this->settings['skin_acp_url']}/images/icons/import.png' alt='' /> {$this->lang->words['sym_import_tasksxml']}</a></li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['sys_system_schedular']}</h3>
	<div id='tabstrip_taskmanager' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
HTML;

$_default_tab = 'tab_core';

foreach( ipsRegistry::$applications as $app_dir => $app_data )
{
	if ( ipsRegistry::$request['tab'] AND $app_dir == ipsRegistry::$request['tab'] )
	{
		$_default_tab = 'tab_' . $app_dir;
	}
	
	if ( isset( $tasks[ $app_dir ] ) && is_array( $tasks[ $app_dir ] ) and count( $tasks[ $app_dir ] ) )
	{
$IPBHTML .= <<<HTML
			<li id='tab_{$app_dir}'>{$app_data['app_title']}</li>
HTML;
	}
}

$IPBHTML .= <<<HTML
		</ul>
	</div>
	<div id='tabstrip_taskmanager_content' class='ipsTabBar_content'>
HTML;

foreach( ipsRegistry::$applications as $app_dir => $app_data )
{
	if ( isset( $tasks[ $app_dir ] ) && is_array( $tasks[ $app_dir ] ) and count( $tasks[ $app_dir ] ) )
	{
$IPBHTML .= <<<HTML
		<div id='tab_{$app_dir}_content'>
			<table class='ipsTable'>
				<tr>
					<th>{$this->lang->words['sys_title']}</th>
					<th>{$this->lang->words['sys_next_run']}</th>
					<th width='5%'>{$this->lang->words['sys_min']}</th>
					<th width='5%'>{$this->lang->words['sys_hour']}</th>
					<th width='5%'>{$this->lang->words['sys_mday']}</th>
					<th width='5%'>{$this->lang->words['sys_wday']}</th>
					<th width='1%'>&nbsp;</th>
				</tr>
HTML;
		foreach( $tasks[ $app_dir ] as $row )
		{
			$row['_style']	= isset( $row['_style'] ) ? $row['_style'] : '';
			$row['_title']	= isset( $row['_title'] ) ? $row['_title'] : '';
			
			if ( $row['task_enabled'] && IPS_UNIX_TIME_NOW > $row['task_next_run'] )
			{
				$row['_next_run'] = "<strong>{$row['_next_run']}</strong>";
			}
			
$IPBHTML .= <<<HTML
				<tr class='ipsControlRow'>
					<td>
						<strong{$row['_style']}>{$row['task_title']}{$row['_title']}</strong>
						<div class='desctext'>{$row['task_description']}</div>
					</td>
					<td{$row['_style']}>{$row['_next_run']}</td>
					<td>{$row['task_minute']}</td>
					<td>{$row['task_hour']}</td>
					<td>{$row['task_month_day']}</td>
					<td>{$row['task_week_day']}</td>
					<td class='col_buttons'>
						<ul class='ipsControlStrip'>
HTML;

	if ( !$row['task_enabled'] )
	{
		$IPBHTML .= <<<HTML
							<li class='i_cog'><a href='#' onclick="cronPopup({$row['task_id']})" title='{$this->lang->words['sys_how_curl_to_use_in_a_cron']}'>{$this->lang->words['sys_how_curl_to_use_in_a_cron']}</a></li>
HTML;
	}
	elseif( $row['task_locked'] )
	{
		$IPBHTML .= <<<HTML
							<li class='i_unlock'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=task_unlock&amp;task_id={$row['task_id']}&amp;tab={$app_dir}' title='{$this->lang->words['sys_unlock_task']}'>{$this->lang->words['sys_unlock_task']}</a></li>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
							<li class='i_refresh'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=task_run_now&amp;task_id={$row['task_id']}&amp;tab={$app_dir}' title='{$this->lang->words['run_task_now']}'>{$this->lang->words['sys_run']}</a></li>
HTML;
	}
	
	$IPBHTML .= <<<HTML
							<li class='ipsControlStrip_more ipbmenu' id='menu_{$row['task_id']}'><a href='#'>&nbsp;</a></li>
						</ul>
						<ul class='acp-menu' id='menu_{$row['task_id']}_menucontent'>
							<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=task_edit&amp;task_id={$row['task_id']}' title='{$this->lang->words['sys_edit_task']}'>{$this->lang->words['sys_edit_task']}</a></li>
							<li class='icon export'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=task_export&amp;task_id={$row['task_id']}'>{$this->lang->words['t_export_single']}</a></li>
							<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=task_delete&amp;task_id={$row['task_id']}");' title='{$this->lang->words['sys_delete_task']}'>{$this->lang->words['sys_delete_task']}</a></li>
						</ul>
					</td>
				</tr>
HTML;
		}
$IPBHTML .= <<<HTML
			</table>
		</div>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</div>
</div>
<br />
<div align='center' class='desctext'><em>{$this->lang->words['sys_all_times_gmt_gmt_time_now_is']} {$date}</em></div>
<br />

<div class='acp-box' id='importTaskForm' style='display:none'>
	<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=task_import' method='post' enctype='multipart/form-data'>
		<h3 class='ipsBlock_title'>{$this->lang->words['t_import_single']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['upload_task_xml']}</strong></td>
				<td class='field_field'><input type='file' name='FILE_UPLOAD' /><br /><span class='desctext'>{$this->lang->words['upload_task_dupe']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['task_import_button']}' class="button primary" />
		</div>
	</form>
</div>

HTML;

$path = DOC_IPS_ROOT_PATH . 'interface/task.php';
foreach( ipsRegistry::$applications as $app_dir => $app_data )
{
	if ( isset( $tasks[ $app_dir ] ) && is_array( $tasks[ $app_dir ] ) and count( $tasks[ $app_dir ] ) )
	{
		foreach( $tasks[ $app_dir ] as $row )
		{
			$IPBHTML .= <<<HTML
<div class='acp-box' id='cron_info_{$row['task_id']}' style='display:none'>
	<h3 class='ipsBlock_title'>{$row['task_title']}</h3>
	<table class='ipsTable'>
		<tr>
			<td class='center'>
				{$this->lang->words['task_cron_single_desc']}<br />
				<br />
				<textarea cols='100' rows='1'>{$path} {$row['task_cronkey']}</textarea>
			</td>
		</tr>
	</table>
</div>
HTML;
		}
	}
}

$IPBHTML .= <<<HTML
<script type='text/javascript'>
	jQ("#tabstrip_taskmanager").ipsTabBar({tabWrap: "#tabstrip_taskmanager_content", defaultTab: "{$_default_tab}" });
	
	function cronPopup( id )
	{
		curPop = new ipb.Popup( 'cronInfo', {
								type: 'pane',
								modal: true,
								initial: $( 'cron_info_' + id ).innerHTML,
								hideAtStart: false,
								w: '800px',
								h: '150px'
							});
		
		return false;
	}
	
	function importTask()
	{
		curPop = new ipb.Popup( 'cronInfo', {
								type: 'pane',
								modal: true,
								initial: $( 'importTaskForm' ).innerHTML,
								hideAtStart: false,
								w: '600px',
								h: '150px'
							});
		
		return false;
	}
	
</script>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Task Cron Information
 *
 * @return	string		HTML
 */
public function taskCrons() {

$masterCron = DOC_IPS_ROOT_PATH . 'interface/task.php all ' . $this->settings['task_cron_key'];

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML

<div class='section_title'>
	<h2>{$this->lang->words['sys_system_schedular']}</h2>
</div>
HTML;

if ( !is_executable( DOC_IPS_ROOT_PATH . 'interface/task.php' ) )
{
	$IPBHTML .= <<<HTML
	<p class='warning'>{$this->lang->words['task_interface_warn']}</p>
	<br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['task_use_cron']}</h3>
HTML;
	if ( $this->settings['task_use_cron'] )
	{
		$IPBHTML .= <<<HTML
		<form action='{$this->settings['base_url']}module=system&amp;section=taskmanager&amp;do=cron&toggle=0' method='post'>
		<table class='ipsTable'>
			<tr>
				<td>
					{$this->lang->words['task_cronmode_on']}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='realbutton' value='{$this->lang->words['task_cronmode_disable']}' />
		</div>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
		<form action='{$this->settings['base_url']}module=system&amp;section=taskmanager&amp;do=cron&toggle=1' method='post'>
		<table class='ipsTable'>
			<tr>
				<td>
					{$this->lang->words['task_cronmode_desc_1']}<br />
					<br />
					<p class='warning'>{$this->lang->words['task_cronmode_desc_2']}</p>
					<br />
					<br />
					<strong>{$this->lang->words['task_cronmode_desc_3']}</strong><br />
					<br />
					{$this->lang->words['task_cronmode_desc_4']}<br />
					{$this->lang->words['task_cronmode_desc_5']}<br />
					{$this->lang->words['task_cronmode_desc_6']}<br />
					<br />
					{$this->lang->words['task_cronmode_desc_7']}<br />
					<br />
					<em>{$this->lang->words['task_cronmode_desc_8']}</em>
					<br />
					<br />
					<br />
					<strong>{$this->lang->words['task_cronmode_desc_9']}</strong><br />
					<br />
					{$this->lang->words['task_cronmode_desc_10']}<br />
					<br />
					{$this->lang->words['task_cronmode_desc_4']}<br />
					{$this->lang->words['task_cronmode_desc_11']}<br />
					{$this->lang->words['task_cronmode_desc_12']}<br />
					{$this->lang->words['task_cronmode_desc_13']}<br />
					<br />
					<textarea cols='100' rows='1'>{$masterCron}</textarea><br />
					<br />
					<br />
					{$this->lang->words['task_cronmode_desc_14']}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='realbutton' value='{$this->lang->words['task_cronmode_enable']}' />
		</div>
HTML;
	}
	$IPBHTML .= <<<HTML
</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * ACP latest logins row
 *
 * @param	array 		Log records
 * @return	string		HTML
 */
public function acp_last_logins_row( $r ) {

$IPBHTML = "";
//--starthtml--//

$image = $r['admin_success'] ? 'tick' : 'cross';

$IPBHTML .= <<<HTML
<tr>
    <td>
    	<img src='{$this->settings['skin_acp_url']}/images/icons/user.png' alt='-' />
    </td>
	<td>
		<strong>{$r['admin_username']}</strong>
		<div class='desctext'>{$this->lang->words['ipaddyprefix']}{$r['admin_ip_address']}</div>
	</td>
    <td>{$r['_admin_time']}</td>
    <td>
    	<img src='{$this->settings['skin_acp_url']}/images/icons/{$image}.png' alt='' />
    </td>
    <td>
		<a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}module=logs&amp;section=loginlog&amp;do=view_detail&amp;detail={$r['admin_id']}', 700, 500)" title='{$this->lang->words['sys_view_details']}'><img src='{$this->settings['skin_acp_url']}/images/folder_components/index/view.png' alt='-' /></a>
	</td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * ACP latest logins wrapper
 *
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_last_logins_wrapper($content,$links) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$this->lang->words['sys_last_5_acp_log_in_attempts']}</h3>
    <table class="ipsTable">
        <tr>
            <th width='1%'>&nbsp;</th>
            <th width='40%'><span class='larger_text'>{$this->lang->words['sys_name']}</th>
            <th width='49%'>{$this->lang->words['sys_date']}</th>
            <th width='5%'>{$this->lang->words['sys_status']}</th>
            <th width='5%'>{$this->lang->words['sys_log']}</th>
        </tr>
    	{$content}
    </table>
	<div class='acp-actionbar'>
		<div class="left">{$links}</div>
		<br class='clear' />
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Latest logins detail
 *
 * @param	array 		Log data
 * @return	string		HTML
 */
public function acp_last_logins_detail( $log ) {

$IPBHTML = "";
//--starthtml--//

$image = $log['admin_success'] ? 'tick' : 'cross';

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['logindetails']}</h3>
	<table class='ipsTable'>
		<tr>
			<th colspan='2'>{$this->lang->words['logindetail_basic']}</th>
		</tr>
		<tr>
			<td width='30%'>{$this->lang->words['logindetail_username']}</td>
			<td width='70%'>{$log['admin_username']}</td>
		</tr>
		<tr>
			<td>{$this->lang->words['logindetail_ip']}</td>
			<td>{$log['admin_ip_address']}</td>
		</tr>
		<tr>
			<td>{$this->lang->words['logindetail_time']}</td>
			<td>{$log['_admin_time']}</td>
		</tr>
		<tr>
			<td>{$this->lang->words['logindetail_success']}</td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$image}.png' alt='' /></td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['logindetail_post']}</th>
		</tr>
HTML;
		if ( is_array( $log['_admin_post_details']['post'] ) AND count( $log['_admin_post_details']['post'] ) )
		{
			foreach( $log['_admin_post_details']['post'] as $k => $v )
			{
				$IPBHTML .= "<tr>
								<td width='30%'>{$k}</td>
								<td width='70%'>{$v}</td>
							</tr>";
			}
		}
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='2'>{$this->lang->words['logindetail_get']}</th>
		</tr>
HTML;
		if ( is_array( $log['_admin_post_details']['get'] ) AND count( $log['_admin_post_details']['get'] ) )
		{
			foreach( $log['_admin_post_details']['get'] as $k => $v )
			{
				$IPBHTML .= "<tr>
								<td width='30%'>{$k}</td>
								<td width='70%'>{$v}</td>
							</tr>";
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
 * Online user record entry
 *
 * @param	string		Name
 * @param	string		IP address
 * @param	string		Login time/date
 * @param	string		Last click time/date
 * @param	string		Current location
 * @return	string		HTML
 */
public function online_user_row($name, $ip_address, $log_in, $click, $location) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
 <td width='20%'>{$name}</td>
 <td width='20%'>{$ip_address}</td>
 <td width='15%' align='center'>{$log_in}</td>
 <td width='15%' align='center'>{$click}</td>
 <td width='20%'>{$location}</td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Translation Session
 *
 * @param	array 		Languages
 * @return	string		HTML
 */
public function languages_translateExt( $data, $lang )
{
$HTML = <<<HTML
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.languages.js"></script>
<div class='information-box'>
 <h4>{$this->lang->words['ext_top_title']}</h4>
 {$this->lang->words['ext_top_desc']}
</div>
<br />
<div class='section_title'>
	<h2>{$data['lang_title']}</h2>
	<ul class='context_menu'>
		<li class='closed'>
			<a id='langKill' href='#'> <img src='{$this->settings['skin_acp_url']}/images/icons/cross.png' alt='' />{$this->lang->words['ext_button_finish']}</a>
		</li>
		<li>
			<a id='sel__none' href='#'> <img src='{$this->settings['skin_acp_url']}/images/icons/template.png' alt='' /> {$this->lang->words['ext_button_unselect']}</a>
		</li>
		<li>
			<a id='sel__all' href='#'> <img src='{$this->settings['skin_acp_url']}/images/icons/page_add.png' alt='' /> {$this->lang->words['ext_button_selectall']}</a>
		</li>
		<li>
			<a id='sel__modified' href='#'> <img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' />{$this->lang->words['ext_button_smodified']}</a>
		</li>
	</ul>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=translateImport' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['ext_title_for']} {$data['lang_title']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='1%'>&nbsp;</th>
			<th width='40%'>{$this->lang->words['ext_tbl_file']}</th>
			<th width='30%'>{$this->lang->words['ext_tbl_local']}</th>
			<th width='30%'>{$this->lang->words['ext_tbl_db']}</th>
			
		</tr>
HTML;

foreach( $data['files'] as $name => $data )
{
	$mtime  = $this->registry->class_localization->getDate( $data['mtime'], 'long' );
	$dbtime = $this->registry->class_localization->getDate( $data['dbtime'], 'long' );
	$style  = ( $data['mtime'] > $data['dbtime'] ) ? ' class="_amber"' : '';
	$class  = ( $data['mtime'] > $data['dbtime'] ) ? ' selected' : '';
	$jsname = str_replace( '.', '-', $name );
	
$HTML .= <<<HTML
		<tr{$style} id='tr-$jsname'>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/template.png' alt='' /></td>
			<td><input type='checkbox' name='cb[$name]' value='1' id='cb-$jsname' class='cbox{$class}' /></td>
			<td><div>{$name}</div>
			<td>{$mtime}</td>
			<td>{$dbtime}</td>
			
 		</tr>
HTML;
}
$HTML .= <<<HTML
 	</table>
 	<div class='acp-actionbar clearfix'>
 		<input class='button primary right' type='submit' value='{$this->lang->words['ext_tbl_submit']}' />
 	</div>
</div>
</form>
HTML;

return $HTML;
}


/**
 * List installed languages
 *
 * @param	array 		Languages
 * @return	string		HTML
 */
public function languages_list( $rows, $hasTranslate )
{

if ( $hasTranslate )
{
	$this->lang->words['ext_translation_detected'] = sprintf( $this->lang->words['ext_translation_detected'], "{$this->settings['base_url']}&{$this->form_code}&do=translateExtSplash" );
	
$HTML .= <<<HTML
<div class='information-box'>
 <h4><img src='{$this->settings['skin_acp_url']}/images/icons/information.png' alt='' />&nbsp; {$this->lang->words['ext_top_title']}</h4>
 {$this->lang->words['ext_translation_detected']}
</div>
<br />
HTML;
}

$HTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['language_list_page_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['language_list_page_title']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='30%' align=''>{$this->lang->words['language_list_title']}</th>
			<th width='10%' align='center'>{$this->lang->words['language_list_local']}</th>
			<th width='20%' align='center'>{$this->lang->words['language_list_date']}</th>
			<th width='20%' align='center'>{$this->lang->words['language_list_money']}</th>
			<th width='10%' style='text-align: center'>{$this->lang->words['language_list_default']}</th>
			<th width='10%' align='center'>&nbsp;</th>
		</tr>
HTML;

foreach( $rows as $r )
{
	$_default = $r['default'] ? "<img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' alt='{$this->lang->words['yes']}' />" : '';
	
	$HTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td width='30%'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=list_word_packs&amp;id={$r['id']}'>{$r['title']}</a></td>
			<td width='10%'>{$r['local']}</td>
			<td width='20%'>{$r['date']}</td>
			<td width='20%'>{$r['money']}</td>
			<td width='10%' style='text-align: center'>{$_default}</td>			
			<td width='10%' class='col_buttons'>
			 	<ul class='ipsControlStrip'>
			 		<li class='i_edit'> 
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit_lang_info&amp;id={$r['id']}' title='{$this->lang->words['edit']}'>{$this->lang->words['edit']}</a>
					</li>
			 		<li class='i_refresh'> 
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=recache_lang_pack&amp;id={$r['id']}' title='{$this->lang->words['language_list_recache']}'>{$this->lang->words['language_list_recache']}</a>
					</li>
HTML;

$HTML .= <<<HTML
					<li class='ipsControlStrip_more ipbmenu' id='menu_{$r['id']}'><a href='#'>&nbsp;</a></li>
				</ul>
				<ul class='acp-menu' id='menu_{$r['id']}_menucontent'>
HTML;

if( ( IN_DEV OR !$r['protected'] ) AND !$r['default'] )
{
	$HTML .= <<<HTML
			 		<li class='icon delete'>
						<a href='#' onclick="return acp.confirmDelete('{$this->settings['base_url']}{$this->form_code}&amp;do=remove_language&amp;id={$r['id']}');" title='{$this->lang->words['delete']}'>{$this->lang->words['delete']}</a>
					</li>
HTML;
}

				foreach( $r['menu'] as $_menu )
				{
					$cssClass	= $_menu[2] ? $_menu[2] : 'manage';

					if( !$_menu[0] )
					{
						$HTML .= <<<HTML
						<li class='icon {$cssClass}'><em>{$_menu[1]}</em></li>
HTML;
					}
					else
					{
						$HTML .= <<<HTML
						<li class='icon {$cssClass}'><a href='{$_menu[0]}'>{$_menu[1]}</a></li>
HTML;
					}
				}

$HTML .= <<<HTML
				</ul>
			</td>
 		</tr>
HTML;
}
$HTML .= <<<HTML
 	</table>
</div>
<br />
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=language_swap' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['sys_lang_choice_swap']}</h3>
    <table class='ipsTable double_pad'>
        <tr>
            <td class='field_title'><strong class='title'>{$this->lang->words['where_user_lang_choice']}</strong></td>
            <td class='field_field'>
            	<select name='lang_from'>
            		<option value='def'>{$this->lang->words['def_lang_sel']}</option>
HTML;
foreach( $rows as $r )
{
    $HTML .= "<option value='{$r['id']}'>{$r['title']}</option>\n";
}

$HTML .= <<<HTML
        		</select>
            	<br /><span class='desctext'>{$this->lang->words['where_user_lang_choice_desc']}</span>
           	</td>
        </tr>
        <tr>
            <td class='field_title'><strong class='title'>{$this->lang->words['where_touser_lang_choice']}</strong></td>
			<td class='field_field'>
            	<select name='lang_to'>
            		<option value='def'>{$this->lang->words['def_lang_sel']}</option>
HTML;
foreach( $rows as $r )
{
    $HTML .= "<option value='{$r['id']}'>{$r['title']}</option>\n";
}

$HTML .= <<<HTML
        		</select>
				<br /><span class='desctext'>{$this->lang->words['where_touser_lang_choice_desc']}</span>
			</td>
        </tr>
    </table>
    <div class="acp-actionbar">
        <input type='submit' class='button primary' value='{$this->lang->words['update_lang_prefs']}' />
    </div>
</div>
</form>
<br />
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=language_do_import' enctype='multipart/form-data' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['sys_import_language_xml']}</h3>
    <table class='ipsTable'>
        <tr>
            <td class='field_title'><strong class='title'>{$this->lang->words['sys_upload_xml_language_file_from_']}</strong></td>
            <td class='field_field'><input class='textinput' type='file' size='30' name='FILE_UPLOAD' /><br /><span class='desctext'>{$this->lang->words['sys_duplicate_entries_will_not_be_']}</span></td>
        </tr>
        <tr>
            <td class='field_title'><strong class='title'>{$this->lang->words['sys_or_enter_the_filename_of_the_x']}</strong></td>
            <td class='field_field'><input class='textinput' type='text' size='30' name='file_location' /><br /><span class='desctext'>{$this->lang->words['sys_the_file_must_be_uploaded_into']}</span></td>
        </tr>
    </table>
    <div class="acp-actionbar">
        <input type='submit' class='button primary' value='{$this->lang->words['sys_import']}' />
    </div>
</div>
</form>
HTML;

if ( IN_DEV )
{
$HTML .= <<<HTML
<br />
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=language_do_indev_import' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['sys_developers_language_cache_impo']}</h3>
    <table class='ipsTable'>
        <tr>
        	<td class='field_title'>
        		<strong class='title'>{$this->lang->words['sys_indev_export']}</strong>
        	</td>
        	<td class='field_field'><a class='realbutton' href='{$this->settings['base_url']}{$this->form_code}&amp;do=language_do_indev_export'>{$this->lang->words['sys_indev_export_go']}</a></td>
        </tr>
        <tr>
        	<td class='field_title'>
				<strong class='title'>{$this->lang->words['sys_select_the_application_languag']}</strong>
			</td>
			
			<td class='field_field'>
	            <select name='apps[]' multiple='multiple' size=5>
HTML;
foreach( ipsRegistry::$applications as $app => $data )
{
    $HTML .= "<option value='{$app}'>{$data['app_title']}</option>\n";
}

$HTML .= <<<HTML
				</select><br />
				<span class='desctext'>{$this->lang->words['sys_this_will_examine_the_corecach']}</span>
    	</td>
	</tr>
	</table>
	<div class="acp-actionbar">
    	<input type='submit' class='button primary' value='{$this->lang->words['sys_import']}' />
	</div>
</form>
HTML;
}

return $HTML;
}

/**
 * List word packs in a language
 *
 * @param	int			Language id
 * @param	array 		Word packs
 * @param	string		Page title
 * @return	string		HTML
 */
public function languageWordPackList( $id, $packs, $title='' )
{
$HTML = <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sym_manage_languages']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=list_word_packs&amp;id={$this->request['id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='' /> {$this->lang->words['sym_view_word_packs']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=edit_lang_info&amp;id={$this->request['id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' /> {$this->lang->words['sym_edit_language_pack_information']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=add_word_entry&amp;id={$this->request['id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['sym_add_new_language_entry']}</a></li>
	</ul>
</div>

<form class='information-box' style='margin-bottom: 10px;' name='theForm' method='post' action='{$this->settings['base_url']}module=languages&amp;section=manage_languages' id='searchform' enctype='multipart/form-data'>
	<input type='hidden' name='do' value='edit_word_pack' />
	<input type='hidden' name='id' value='{$this->request['id']}' />
	<input type='hidden' name='secure_key' value='{this->registry->adminFunctions->generated_acp_hash}' />

	{$this->lang->words['sym_find']}: <input type='text' name='search' value='{$this->request['search']}' class='inputtext' /> <input type='image' src='{$this->settings['skin_acp_url']}/images/search_icon.gif' value='{$this->lang->words['sym_submit']}' alt='{$this->lang->words['sym_find']}' />
</form>	
	
	<div class='acp-box'>
		<h3>{$this->lang->words['lang_sections_title']}</h3>
		{$packs}
	</div>
HTML;

return $HTML;
}

/**
 * Edit a word pack
 *
 * @param	int			Word pack id
 * @param	array 		Language bits
 * @param	string		Page links
 * @return	string		HTML
 */
public function languageWordPackEdit( $id, $lang, $pages='' )
{
	$title	= $this->request['search'] ? $this->lang->words['lang_search_results'] : $this->lang->words['language_word_pack_edit'] . ': "' . $this->request['word_pack'] . '"';
	
$HTML = <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sym_manage_languages']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=list_word_packs&amp;id={$this->request['id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='' /> {$this->lang->words['sym_view_word_packs']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=edit_lang_info&amp;id={$this->request['id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' /> {$this->lang->words['sym_edit_language_pack_information']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=add_word_entry&amp;id={$this->request['id']}&amp;word_pack={$this->request['word_pack']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['sym_add_new_language_entry']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=edit_word_pack&amp;id={$this->request['id']}&amp;word_pack={$this->request['word_pack']}&amp;filter=outofdate'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['sym_word_out_of_date_check']}</a></li>
	</ul>
</div>
<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform' enctype='multipart/form-data'>
	<input type='hidden' name='do' value='do_edit_word_pack' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='pack' value='{$this->request['word_pack']}'/>
	<input type='hidden' name='st' value='{$this->request['st']}'/>
	<input type='hidden' name='search' value='{$this->request['search']}'/>
	<input type='hidden' name='filter' value='{$this->request['filter']}'/>
	<input type='hidden' name='secure_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	{$pages}
	<br style='clear: both' />
	<div class='acp-box'>		
		<h3>{$title}</h3>
		<table class='ipsTable'>
			<tr>
				<th style='width: 42%'>{$this->lang->words['language_word_pack_current']}</th>
				<th style='width: 42%'>{$this->lang->words['language_word_pack_new']}</th>
				<th style='width: 16%'>&nbsp;</th>
			</tr>		
HTML;

$tabIndex	= 1;

foreach( $lang as $l )
{
	$revert = ( $l['custom'] ) ? "<li class='i_refresh'><a href='{$this->settings['base_url']}{$this->form_code}&do=revert&word_id={$l['id']}&word_pack={$l['pack']}&id={$id}&st={$this->request['st']}' class='dropdown-button'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_rotate_anticlockwise.png' /></a></li>" : '';
	$edit   = IN_DEV ? "<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&do=edit_word_entry&&word_id={$l['id']}&word_pack={$l['pack']}&id={$id}' class='dropdown-button'><img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' /></a></li>" : '';
	$pack	= $this->request['search'] ? 
				"<span class='desctext'>{$this->lang->words['l_wordpack_prefix']}<a href='{$this->settings['base_url']}{$this->form_code}&amp;word_pack={$l['pack']}&amp;do=edit_word_pack&amp;id={$id}'><strong>{$l['pack']}</strong></a></span>" : 
				'';
	
$HTML .= <<<HTML
			<tr class='language_editor ipsControlRow'>
				<td>
					<div class='information-box' style='width:400px; overflow:auto'>
						<h4>{$l['key']}</h4>
						{$l['default']}
					</div>
					{$pack}
				</td>
				<td>
					<br />
					<textarea tabindex='{$tabIndex}' name='lang[{$l['id']}]' cols='50' id='word_{$l['id']}_new' class='new_lang' style='width:95%'>{$l['custom']}</textarea>
				</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
					{$edit}
					{$revert}
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove_word_entry&amp;word_id={$l['id']}&amp;word_pack={$l['pack']}&amp;id={$id}&amp;st={$this->request['st']}");' class='dropdown-button'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' /></a></li>
					</ul>
				</td>
			</tr>
HTML;
	
	$tabIndex++;
}
	 	
$HTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['language_word_pack_go']}' class='button primary' />
		</div>		
	</div>
	<br />
	{$pages}	
</form>
HTML;

return $HTML;
}

/**
 * Language information form
 *
 * @param	string		Action code
 * @param	int			Language pack id
 * @param	string		Form title
 * @param	string		Form header
 * @param	array 		Language pack id
 * @param	string		Button text
 * @return	string		HTML
 */
public function languageInformationForm( $op, $id, $title, $header, $data, $button )
{
$HTML = <<<HTML
<div class='information-box'>
	$header		
</div>
<br />

<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform' enctype='multipart/form-data'>
	<input type='hidden' name='do' value='{$op}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='secure_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['language_form_title']}</strong></td>
				<td class='field_field'><input type='text' name='lang_title' value='{$data['lang_title']}' size='50' class='inputtext' /></td>
		 	</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['language_form_locale']}</strong></td>
				<td class='field_field'><input type='text' name='lang_short' value='{$data['lang_short']}' size='50' class='inputtext' /></td>
		 	</tr
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['language_is_rtl']}</strong></td>
				<td class='field_field'>{$data['lang_isrtl']}</td>
		 	</tr>	
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['language_form_default']}</strong></td>
				<td class='field_field'>{$data['lang_default']}</td>
		 	</tr>		 		
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' />
		</div>
	</div>
</form>
HTML;

return $HTML;
}

/**
 * Show the application languages list
 *
 * @param	string		Application
 * @param	array 		Packs
 * @param	array 		Stats
 * @return	string		HTML
 */
public function languageAppPackList( $app, $packs, $stats )
{
$HTML = <<<HTML

	<table class='ipsTable'>
		<tr>
			<th width='30%' align=''>{$this->lang->words['language_pack_name']}</th>
			<th width='20%' align='center'>{$this->lang->words['language_total_entries']}</th>
			<th width='20%' align='center'>{$this->lang->words['language_customized_entries']}</th>
			<th width='20%' align='center'>{$this->lang->words['language_out_of_date_entries']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if( count( $packs ) )
{
	foreach( $packs as $r )
	{
																				
		$stats[$r]['custom'] = isset( $stats[$r]['custom'] ) ? $stats[$r]['custom'] : '&nbsp;';
$HTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td width='30%' valign='middle'><a href='{$this->settings['base_url']}{$this->form_code}&amp;word_pack={$app}/{$r}&amp;do=edit_word_pack&amp;id={$this->request['id']}' class='larger_text'>{$r}</a></td>
			<td width='20%' valign='middle' align='center'>{$stats[$r]['total']}</td>
			<td width='20%' valign='middle' align='center'>{$stats[$r]['custom']}</td>
			<td width='20%' valign='middle' align='center'><a href='{$this->settings['base_url']}{$this->form_code}&amp;word_pack={$app}/{$r}&amp;do=edit_word_pack&amp;id={$this->request['id']}&amp;filter=1'>{$stats[$r]['outofdate']}</a></td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_add'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;word_pack={$app}/{$r}&amp;do=add_word_entry&amp;id={$this->request['id']}&amp;word_app={$app}' title='{$this->lang->words['l_addnew']}'>{$this->lang->words['l_addnew']}</a>
					</li>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;word_pack={$app}/{$r}&amp;do=edit_word_pack&amp;id={$this->request['id']}' title='{$this->lang->words['edit']}'>{$this->lang->words['edit']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick="return acp.confirmDelete('{$this->settings['base_url']}{$this->form_code}&amp;word_pack={$app}/{$r}&amp;do=remove_word_pack&amp;id={$this->request['id']}&amp;word_app={$app}');" title='{$this->lang->words['l_remove_pack']}'>{$this->lang->words['l_remove_pack']}</a>
					</li>
				</ul>
			</td>
 		</tr>
HTML;
	}
}
else 
{
$HTML .= <<<HTML
	<tr>
		<td colspan='4' class='no_messages'>{$this->lang->words['nolangpacksgroup']}</td>
	</tr>
HTML;
}
$HTML .= <<<HTML
 	</table>
HTML;

return $HTML;
}

/**
 * Add/edit a word in a language pack
 *
 * @param	string		Action
 * @param	int			Word id
 * @param	int			Language pack id
 * @param	string		Form title
 * @param	string		Form header
 * @param	array 		Word data
 * @param	string		Button text
 * @return	string		HTML
 */
public function languageWordEntryForm( $op, $word_id, $lang_id, $title, $header, $data, $button )
{
$HTML = <<<HTML
<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform' enctype='multipart/form-data'>
	<input type='hidden' name='do' value='{$op}' />
	<input type='hidden' name='id' value='{$lang_id}' />
	<input type='hidden' name='word_id' value='{$word_id}' />
	<input type='hidden' name='secure_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td id='language_appselector' class='field_title'><strong class='title'>{$this->lang->words['langpackaddapp']}</strong></td>
				<td class='field_field'><select name='word_app' class='inputtext'>{$data['word_app']}</select></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['language_pack_name']}</strong></td>
				<td class='field_field'><input type='text' name='word_pack_db' id='word_pack_db' value='{$data['word_pack']}' size='50' class='inputtext' /></td>
	 		</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['language_key']}</strong></td>
				<td class='field_field'><input type='text' name='word_key' value='{$data['word_key']}' size='50' class='inputtext' /></td>
	 		</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['language_default']}</strong></td>
				<td class='field_field'><textarea name='word_default' class='inputtext' cols='50'>{$data['word_default']}</textarea></td>
	 		</tr>  			
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' />
		</div>
	</div>	 	
</form>
<script type='text/javascript'>
$('word_pack_db').observe( "blur", checkPack );
document.observe("dom:loaded",function() 
{
	checkPack();
});

function checkPack( e )
{
	if( $('word_pack_db').value == 'admin_js' || $('word_pack_db').value == 'public_js' )
	{
		$('language_appselector').hide();
	}
	else
	{
		$('language_appselector').show();
	}
}

checkPack();
</script>
HTML;

return $HTML;
}


}