<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * SQL Toolbox skin file
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
 
class cp_skin_sql
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
 * Result from running an SQL tool
 *
 * @param	string		Page title
 * @param	array 		Columns
 * @param	array 		Rows
 * @return	string		HTML
 */
public function sqlToolResult( $title, $columns, $rows ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$title}</h3>

	<table class='ipsTable'>
		<tr>
HTML;

foreach( $columns as $r )
{
$IPBHTML .= <<<HTML
			<th>{$r}</th>
HTML;
}

$IPBHTML .= <<<HTML
		</tr>
		
		<tr>
HTML;

foreach( $rows as $r )
{
	$num = $num == 1 ? 2 : 1;
$IPBHTML .= <<<HTML
			<td>{$r}</td>
HTML;
}
		
$IPBHTML .= <<<HTML
		</tr>
	</table>	
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the header for an SQL tool result
 *
 * @return	string		HTML
 */
public function sqlToolResultHeader() {
$IPBHTML = "";
//--starthtml--//

$title = sprintf( $this->lang->words['my_title'], $this->true_version );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Splash screen to create an SQL backup
 *
 * @return	string		HTML
 */
public function sqlSafeBackupSplashScreen() {
$IPBHTML = "";
//--starthtml--//

$title	= sprintf( $this->lang->words['my_backuptitle'], $this->true_version );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box with_bg'>
	<h3>{$this->lang->words['my_simple_backup']}</h3>

	<p>{$this->lang->words['my_simple_backup']}</p>
	<p>{$this->lang->words['my_simple_text']}</p>
	<p><b><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=dosafebackup&amp;create_tbl={$this->request['create_tbl']}&amp;addticks={$this->request['addticks']}&amp;skip={$this->request['skip']}&amp;enable_gzip={$this->request['enable_gzip']}'>{$this->lang->words['my_start_backup']}</a></b></p>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the form to create an SQL backup
 *
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function sqlBackupForm( $form ) {
$IPBHTML = "";
//--starthtml--//

$title	= sprintf( $this->lang->words['my_backuptitle'], $this->true_version );

$IPBHTML .= <<<HTML

<div class='warning'> 
	<h4>{$this->lang->words['backup_tool_warning_title']}</h4>
	<p>{$this->lang->words['backup_tool_warning_text']}</p>
</div>
<br />
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='safebackup' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['my_simple_backup']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['my_createtable']}</strong></td>
				<td class='field_field'>{$form['create_tbl']}</td>
			</tr>
			
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['my_backticks']}</strong></td>
				<td class='field_field'><input type='checkbox' name='addticks' value='1' /><br /><span class='desctext'>{$this->lang->words['my_iferror']}</span></td>
			</tr>
			
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['my_skinnoness']}</strong></td>
				<td class='field_field'>{$form['skip']}<br /><span class='desctext'>{$this->lang->words['my_skinnoness_notes']}</span></td>
			</tr>
			
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['my_gzipcontent']}</strong></td>
				<td class='field_field'>{$form['enable_gzip']}<br /><span class='desctext'>{$this->lang->words['my_gzipnotes']}</span></td>
			</tr>
		</table>
		
		<div class="acp-actionbar"><input type='submit' value='{$this->lang->words['my_start_backup']}' class='realbutton' accesskey='s' /></div>		
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View results from executing an SQL query
 *
 * @param	array 		Queries
 * @param	array 		Headers for the queries
 * @param	array 		Rows from the queries
 * @param	string		Page links
 * @return	string		HTML
 */
public function sqlViewResults( $queries, $headers, $rows, $pages, $tableName='', $truncated=array() ) {
$IPBHTML = "";
//--starthtml--//

$query	= implode( ";\n", $queries );

$title	= sprintf( $this->lang->words['my_title'], $this->true_version );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>
HTML;

if ( $tableName )
{
	$this->lang->words['my_results'] .= ' - ' . $tableName;
}

//-----------------------------------------
// When coming from db checker/index checker, won't be set
//-----------------------------------------

if( $this->form_code )
{
	$IPBHTML .= <<<HTML
	<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
		<input type='hidden' name='do' value='runsql' />
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
		
		<div class='acp-box with_bg'>
			<h3>{$this->lang->words['my_run']}</h3>
			<div style='text-align:center;padding:10px'><textarea name='query' id='raw-query' style="width:80%;height:200px;font-family:monospace;">{$query}</textarea></div>
			<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['my_runnew']}' class='realbutton' accesskey='s' /></div>	
		</div>
	</form>
	<br />
HTML;
}

foreach( $queries as $index => $aQuery )
{
	$IPBHTML .= <<<HTML
<div>{$pages}</div>

<div class='acp-box clear'>
	<h3>{$this->lang->words['my_results']}</h3>

	<table class='ipsTable'>
		<tr>
HTML;

if( count($headers[ $index ]) )
{
	foreach( $headers[ $index ] as $r )
	{
		if ( ! $this->request['notruncate'] AND in_array( $r, $truncated ) )
		{
			$r = '<a href="#" class="right" onclick="return goNoTruncate();"><img src="' .  $this->settings['skin_acp_url'] . '/images/icons/viewmore.png" /></a>' . $r;
		}
		
	$IPBHTML .= <<<HTML
			<th>{$r}</th>
HTML;
	}
}

$IPBHTML .= <<<HTML
		</tr>
HTML;

if( count($rows[ $index ]) )
{
	foreach( $rows[ $index ] as $r )
	{
	$IPBHTML .= <<<HTML
		<tr>
HTML;

	foreach( $r as $_r )
	{
		$num = ( $num == 1 ) ? 2 : 1;
		
		/* Excerpt? */
		if ( $this->request['notruncate'] )
		{
			if ( strlen( $_r ) > 200 )
			{
				$_r = "<textarea style='width:100%;height:150px'>" . $_r . "</textarea>";
			}
		}
		
$IPBHTML .= <<<HTML
			<td>{$_r}</td>
HTML;
	}
	
$IPBHTML .= <<<HTML
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
		</tr>
	</table>
</div>
<div style='margin-top: 2px;'>{$pages}</div>
<br />
<script type='text/javascript'>
function goNoTruncate()
{
	var currentlocation	= window.location.toString();

	if( currentlocation.match( /query=/ ) )
	{
		window.location	= currentlocation + '&notruncate=1';
	}
	else
	{
		window.location	= currentlocation.replace( /#$/, '' ) + '&do=runsql&query=' + escape( $('raw-query').value ) + '&notruncate=1';
	}

	return false;
}
</script>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the database tables (SQL Toolbox homepage)
 *
 * @param	array 		Tables
 * @return	string		HTML
 */
public function sqlListTables( $rows ) {
$IPBHTML = "";
//--starthtml--//

$title	= sprintf( $this->lang->words['my_title'], $this->true_version );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_main_url']}acp.forms.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theForm' id='theForm'>
<input type='hidden' name='do' value='dotool' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['my_tables']}</h3>

		<table class='ipsTable'>
			<tr>
				<th width='1%'>&nbsp;</th>
				<th width='20%'>{$this->lang->words['my_table']}</th>
				<th width='10%'>{$this->lang->words['my_rows']}</th>
				<th width='10%'>{$this->lang->words['my_export']}</th>
				<th width='1%'><input id='checkAll' type="checkbox" title="{$this->lang->words['my_checkall']}" /></th>
			</tr>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
			<tr>
				<td><img src="{$this->settings['skin_acp_url']}/images/icons/table.png" /></td>
				<td>
					<b><span style='font-size:12px'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=viewschematic&table={$r['table']}'>{$r['table']}</a></span></b>
HTML;
	if ( $r['rows'] == '' )
	{
		$IPBHTML .= <<<HTML
			<div class='warning'>
				{$this->lang->words['crashed_table_ohnoes']}
			</div>
HTML;
	}
	$IPBHTML .= <<<HTML
				</td>
				<td><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=runsql&query={$r['query']}'>{$r['rows']}</a></td>
				<td><a href='{$this->settings['base_url']}{$this->form_code}&do=export_tbl&tbl={$r['table']}'>{$this->lang->words['my_export']}</a></b></td>
				<td><input name="tbl_{$r['table']}" value='1' type='checkbox' class='checkAll' /></td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<div class='rightaction'>
				<select name='tool'>
					<option value='optimize'>{$this->lang->words['my_optimize']}</option>
					<option value='repair'>{$this->lang->words['my_repair']}</option>
					<option value='check'>{$this->lang->words['my_check']}</option>
					<option value='analyze'>{$this->lang->words['my_analyze']}</option>
				</select>
				<input type='submit' value='{$this->lang->words['my_go']}' class='realbutton' />
			</div>
		</div>	
	</div>
</form>

<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
<input type='hidden' name='do' value='runsql' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['my_run']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['my_manadvanced']}</strong></td>
				<td class='field_field'><textarea name='query' style="width:80%;height:150px;font-family:monospace;"></textarea></td>
			</tr>
		</table>		
		
		<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['my_run']}' class='realbutton' accesskey='s' /></div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the table schematic (SQL Toolbox homepage)
 *
 * @param	array 		Rows
 * @return	string		HTML
 */
public function sqlViewTable( $table, $rows, $indexes ) {
$IPBHTML = "";
//--starthtml--//

$title	= sprintf( $this->lang->words['view_schematic'], $table );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_main_url']}acp.forms.js'></script>

	<div class='acp-box'>
		<h3>{$this->lang->words['view_structure']}: {$table}</h3>

		<table class='ipsTable'>
			<tr>
HTML;

		foreach( $rows[0] as $k => $v )
		{
			$IPBHTML .= <<<HTML
				<th width='15%'>$k</th>
HTML;
		}

$IPBHTML .= <<<HTML
			</tr>
HTML;

foreach( $rows as $idx )
{
	$IPBHTML .= <<<HTML
			<tr>
HTML;
	foreach( $idx as $k => $v )
	{
$IPBHTML .= <<<HTML
			<td>{$v}</td>
HTML;
	}
	$IPBHTML .= <<<HTML
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<div class='rightaction'>
				&nbsp;
			</div>
		</div>	
	</div>
HTML;

if ( is_array( $indexes ) AND count( $indexes ) )
{
	$IPBHTML .= <<<HTML
	<br />
	<div class='acp-box'>
		<h3>{$this->lang->words['view_indexes']}: {$table}</h3>

		<table class='ipsTable'>
			<tr>
HTML;

		foreach( $indexes[0] as $k => $v )
		{
			$IPBHTML .= <<<HTML
				<th width='12%'>$k</th>
HTML;
		}

$IPBHTML .= <<<HTML
			</tr>
HTML;

foreach( $indexes as $idx )
{
	$IPBHTML .= <<<HTML
			<tr>
HTML;
	foreach( $idx as $k => $v )
	{
$IPBHTML .= <<<HTML
			<td>{$v}</td>
HTML;
	}
	$IPBHTML .= <<<HTML
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<div class='rightaction'>
				&nbsp;
			</div>
		</div>	
	</div>
</form>
HTML;

}

$IPBHTML .= <<<HTML
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
<input type='hidden' name='do' value='runsql' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['my_run']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['my_manadvanced']}</strong></td>
				<td class='field_field'><textarea name='query' style="width:80%;height:150px;font-family:monospace;"></textarea></td>
			</tr>
		</table>		
		
		<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['my_run']}' class='realbutton' accesskey='s' /></div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}