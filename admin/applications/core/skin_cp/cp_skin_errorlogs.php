<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Error log skin file
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
 
class cp_skin_errorlogs
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
 * Error log wrapper
 *
 * @param	array 		Rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function errorlogsWrapper( $rows, $links ) {

$form_array 		= array(
							0 => array( 'log_error'      , $this->lang->words['error_log_error']),
							2 => array( 'log_error_code' , $this->lang->words['error_log_code'] ),
							3 => array( 'log_request_uri', $this->lang->words['error_log_uri'] ),
							4 => array( 'members_display_name', $this->lang->words['error_log_member'] ),
							);
$type_array 		= array(
							0 => array( 'exact'	, $this->lang->words['erlog_exact'] ),
							1 => array( 'loose'	, $this->lang->words['erlog_loose'] ),
						 	);
$form				= array();

$form['type']		= $this->registry->output->formDropdown( "type" , $form_array, $this->request['type'] );
$form['match']		= $this->registry->output->formDropdown( "match", $type_array, $this->request['match'] );
$form['string']		= $this->registry->output->formInput( "string", $this->request['string'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.forms.js'></script>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
<input type='hidden' name='do' value='remove' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
<div class="acp-box">
	<h3>{$this->lang->words['error_log_thelogs']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='10%'>{$this->lang->words['error_log_code']}</th>
			<th width='30%'>{$this->lang->words['error_log_error']}</th>
			<th width='15%'>{$this->lang->words['error_log_ip']}</th>
			<th width='22%'>{$this->lang->words['error_log_uri']}</th>
			<th width='10%'>{$this->lang->words['error_log_member']}</th>
			<th width='10%'>{$this->lang->words['error_log_date']}</th>
			<th width='3%'><input type='checkbox' title="{$this->lang->words['my_checkall']}" id='checkAll' /></th>
		</tr>
HTML;

if( count( $rows ) AND is_array( $rows ) )
{
	foreach( $rows as $row )
	{
		$row['log_request_uri']	= htmlentities( $row['log_request_uri'], ENT_QUOTES );

		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['log_error_code']}</td>
			<td>{$row['log_error']}</td>
			<td>{$row['log_ip_address']}</td>
			<td><textarea style='width: 100%'>{$row['log_request_uri']}</textarea></td>
			<td>{$row['members_display_name']}</td>
			<td>{$row['_date']}</td>
			<td><input type='checkbox' name='id_{$row['log_id']}' value='1' class='checkAll' /></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='7' align='center'>{$this->lang->words['error_log_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
        <div class='right'>
			<input type="checkbox" id="checkbox" name="type" value="all" />&nbsp;{$this->lang->words['erlog_removeall']}&nbsp;<input type="submit" value="{$this->lang->words['erlog_removechecked']}" class="button primary" />
        </div>
        <div class='left'>{$links}</div>
        <br class='clear' />
	</div>
</div>
</form>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='list' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['error_log_search']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['erlog_searchwhere']}</td>
			    <td class='field_field'>{$form['type']} {$form['match']} {$form['string']}</td>
			</tr>
		</table>

		<div class='acp-actionbar'>
			<input value="{$this->lang->words['erlog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

}