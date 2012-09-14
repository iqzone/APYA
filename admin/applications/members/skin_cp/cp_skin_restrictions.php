<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP restrictions skin file
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
 * @version		$Rev: 10721 $
 *
 */
 
class cp_skin_restrictions
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
 * Form to add a new restricted member
 *
 * @return	string		HTML
 */
public function restrictionsMemberForm() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['r_title']}</h2>
</div>

<form id='postingform' action="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-member-add-complete" method="post" name="REPLIER">
<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
<div class='acp-box'>
 <h3>{$this->lang->words['r_findadmin']}</h3>
  <table class='ipsTable'>
  	<tr>
    	<td class='field_title'><strong class='title'>{$this->lang->words['r_displayname']}</strong></td>
    	<td class='field_field'><input type="text" id='entered_name' name="entered_name" size="30" autocomplete='off' style='width:210px' value="" tabindex="1" class='input_text' /><br /><span class='desctext'>{$this->lang->words['r_displayname_info']}</span></td>
  	</tr>
  </table>
  <div class='acp-actionbar'>
  	<input type='submit' value='{$this->lang->words['r_proceed']}' class='button primary' accesskey='s' />
  </div>
<script type="text/javascript">
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('entered_name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add a new restricted group
 *
 * @return	string		HTML
 */
public function restrictionsGroupForm() {

$IPBHTML = "";
//--starthtml--//

$all_groups 		= array();

foreach( $this->cache->getCache('group_cache') as $group_data )
{
	if( $group_data['g_access_cp'] )
	{
		$all_groups[]	= array( $group_data['g_id'], $group_data['g_title'] );
	}
}

$dropDown	= $this->registry->output->formDropdown( "entered_group", $all_groups );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['r_title']}</h2>
</div>

<form id='postingform' action="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-group-add-complete" method="post" name="REPLIER">
<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
<div class='acp-box'>
 <h3>{$this->lang->words['r_selectgroup']}</h3>
  <table class='ipsTable'>
  	<tr>
    	<td class='field_title'><strong class='title'>{$this->lang->words['r_whatgroup']}</strong></td>
    	<td class='field_field'>{$dropDown}</td>
  	</tr>
  </table>
	<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['r_proceed']}' class='realbutton' accesskey='s' /></div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * ACP restrictions overview
 *
 * @param	array 		Members
 * @param	array 		Groups
 * @return	string		HTML
 */
public function acpPermsOverview( $members, $groups ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['r_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['r_memberrestrict']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='35%'>{$this->lang->words['r_member']}</th>
			<th width='20%'>{$this->lang->words['r_primary']}</th>
			<th width='20%'>{$this->lang->words['r_secondary']}</th>
			<th width='20%'>{$this->lang->words['r_updated']}</th>
			<th width='5%'>&nbsp;</th>
		</tr>
		{$members}
	</table>
	<div class='acp-actionbar'>
		<div class='rightaction'><input type='button' value='{$this->lang->words['r_findadmin']}' onclick="window.location='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-member-add'" class='button primary' accesskey='s'></div>
	</div>	
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['r_grouprestrict']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='55%'>{$this->lang->words['r_group']}</th>
			<th width='20%' align='center'>{$this->lang->words['r_totalmem']}</th>
			<th width='20%' align='center'>{$this->lang->words['r_updated']}</th>
			<th width='5%'>&nbsp;</th>
		</tr>
		{$groups}
	</table>
	<div class='acp-actionbar'>
		<div class='rightaction'><input type='button' value='{$this->lang->words['r_findgroup']}' onclick="window.location='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-group-add'" class='button primary' accesskey='s'></div>
	</div>
</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Restricted member
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function acpMemberRow( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr class='ipsControlRow'>
	<td>
		<img src='{$this->settings['skin_acp_url']}/images/lock_close.gif' alt='@' style='vertical-align:top' />
		<strong>{$data['members_display_name']}</strong>
	</td>
	<td>{$data['_group_name']}</td>
	<td>{$data['_other_groups']}</td>
	<td>{$data['_date']}</td>
	<td class='col_buttons'>
		<ul class='ipsControlStrip'>
			<li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=accperms-member-edit&amp;mid={$data['member_id']}'>{$this->lang->words['r_managerestrict']}</a></li>
			<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=accperms-member-remove&amp;mid={$data['member_id']}");'>{$this->lang->words['r_removeall']}</a></li>
		</ul>
	</td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Restricted group
 *
 * @param	array 		Group data
 * @return	string		HTML
 */
public function acpGroupRow( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr class='ipsControlRow'>
 <td>
   <img src='{$this->settings['skin_acp_url']}/images/lock_close.gif' alt='@' style='vertical-align:top' />
   <strong>{$data['_group_name']}</strong>
 </td>
 <td align='center'>{$data['_total']}</td>
 <td align='center'>{$data['_date']}</td>
 <td class='col_buttons'>
	<ul class='ipsControlStrip'>
		<li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=accperms-group-edit&amp;gid={$data['row_id']}' title='{$this->lang->words['r_managerestrictg']}'>{$this->lang->words['r_managerestrictg']}</a></li>
		<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=accperms-group-remove&amp;gid={$data['row_id']}");' title='{$this->lang->words['r_removeallg']}'>{$this->lang->words['r_removeallg']}</a></li>
	</ul>
 </td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add new restrictions form
 *
 * @param	int			Role id
 * @param	string		Role type
 * @param	array 		Permissions
 * @param	array 		Access capabilities
 * @return	string		HTML
 */
public function restrictionsForm( $role_id=0, $role_type='member', $permissions=array(), $access=array() ) {

$itemsByModules = array();

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['r_title']}</h2>
</div>

<script type="text/javascript" src='{$this->settings['js_main_url']}acp.permissions.js'></script>
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-save&amp;secure_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}' method='post' id='adform' name='adform'>
<input type='hidden' name='id' value='{$role_id}' />
<input type='hidden' name='type' value='{$role_type}' />
<div class='acp-box'>
	<h3>Set Restrictions</h3>
<div class='ipsTabBar' id='tab_restrictions'>
	<span class='tab_left'>&laquo;</span>
	<span class='tab_right'>&raquo;</span>
	<ul>
HTML;

foreach( ipsRegistry::$applications as $app_dir => $application )
{
	if( is_array(ipsRegistry::$modules[ $app_dir ]) AND count(ipsRegistry::$modules[ $app_dir ]) )
	{
		$IPBHTML .= "<li id='tab_{$application['app_id']}'>{$application['app_title']}</li>\n";
	}
}

$IPBHTML .= <<<HTML
	</ul>
</div>
<div id='tabContent'>
HTML;

foreach( ipsRegistry::$applications as $app_dir => $application )
{
	$checked	= in_array( $application['app_id'], $access['applications'] ) ? 1 : 0;
	$form		= $this->registry->output->formCheckbox( 'app_' . $application['app_id'], $checked, 1, 'app_' . $application['app_id'], "onclick='checkApp( {$application['app_id']} );'" );
	
	if( is_array(ipsRegistry::$modules[ $app_dir ]) AND count(ipsRegistry::$modules[ $app_dir ]) )
	{
		$IPBHTML .= <<<HTML
	
	<div id='tab_{$application['app_id']}_content'>
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'><label for='app_{$application['app_id']}'>{$form} {$this->lang->words['r_grantto']} {$application['app_title']} {$this->lang->words['application_bit']}?</label></th>
			</tr>
		</table>
HTML;
	
		foreach( ipsRegistry::$modules[ $app_dir ] as $module )
		{
			$checked	= in_array( $module['sys_module_id'], $access['modules'] ) ? 1 : 0;
			$form		= $this->registry->output->formCheckbox( 'module_' . $module['sys_module_id'], $checked, 1, 'module_' . $module['sys_module_id'], "onclick='checkModule( {$module['sys_module_id']} );'" );
			
			if( ! $module['sys_module_admin'] )
			{
				continue;	
			}

			$IPBHTML .= <<<HTML
			<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for='module_{$module['sys_module_id']}'>{$form} {$this->lang->words['r_grantto']} {$module['sys_module_title']} {$this->lang->words['module_bit']}?</label></th>
			</tr>
			</table>
			
HTML;

			if( is_array( $permissions ) AND is_array($permissions[ $application['app_id'] ][ $module['sys_module_id'] ]) AND count($permissions[ $application['app_id'] ][ $module['sys_module_id'] ]) )
			{
				// Call me lazy if you wish :P
				$shorten = $permissions[ $application['app_id'] ][ $module['sys_module_id'] ];
				
				foreach( $shorten as $group => $shorter )
				{
					if( !is_array($shorter) OR !count($shorter) )
					{
						continue;
					}

					$IPBHTML .= <<<HTML
				<table class='ipsTable double_pad'>
				<tr>
					<th colspan='2' class='subhead'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$shorter['title']}</th>
				</tr>
HTML;
					if( is_array($shorter['items']) AND count($shorter['items']) )
					{
						foreach( $shorter['items'] as $item_key => $item_text )
						{
							$itemsByModules[ $module['sys_module_id'] ][] = $item_key;
							$checked	= (is_array($access['items'][ $module['sys_module_id'] ]) AND in_array($item_key, $access['items'][ $module['sys_module_id'] ] )) ? 1 : 0;
							$form		= $this->registry->output->formCheckbox( 'item_' . $module['sys_module_id'] . '_' . $item_key, $checked, 1, 'item_' . $module['sys_module_id'] . '_' . $item_key, "onclick='checkItem( {$module['sys_module_id']} );'" );
							
							$IPBHTML .= <<<HTML
							<tr>
								<td colspan='2'>
									<strong class='title'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for='item_{$module['sys_module_id']}_{$item_key}'>{$form} {$item_text}</label></strong>
								</td>
							</tr>
HTML;
						}
					}
					
					$IPBHTML .= <<<HTML
				</table>
HTML;
				}
			}
			
		}

		$IPBHTML .= <<<HTML
	</div>

HTML;
	}
}

$IPBHTML .= <<<HTML
</div>
<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['r_savebutton']}' class='button primary' /></div>
</div>
</form>
<script type='text/javascript'>
	jQ("#tab_restrictions").ipsTabBar();
</script>
HTML;



$IPBHTML .= <<<HTML
<script type='text/javascript'>
	var modulesByApp = [];
	var appsByModule = [];
	var itemsByModule = [];
HTML;

foreach( ipsRegistry::$applications as $app_dir => $application )
{
	$modules = array();
	foreach( ipsRegistry::$modules[ $app_dir ] as $module )
	{
		if( ! $module['sys_module_admin'] )
		{
			continue;	
		}
		$modules[] = $module['sys_module_id'];
		
		$IPBHTML .= <<<HTML
		appsByModule[ {$module['sys_module_id']} ] = {$application['app_id']};
HTML;
	}
	
	$modules = implode( ',', $modules );

	$IPBHTML .= <<<HTML
		modulesByApp[ {$application['app_id']} ] = Array( {$modules} );

HTML;
}
foreach ( $itemsByModules as $module => $items )
{
	$items = implode( ',', array_map( create_function( '$v', 'return "\'$v\'";' ), $items ) );
	$IPBHTML .= <<<HTML
		itemsByModule[ {$module} ] = Array( {$items} );

HTML;
}

$IPBHTML .= <<<HTML

	function checkApp( app )
	{	
		for ( var i in modulesByApp[ app ] )
		{
			if (  $( 'module_' + modulesByApp[ app ][ i ] ) )
			{
			    $( 'module_' + modulesByApp[ app ][ i ] ).checked = $( 'app_' + app ).checked;
			    checkModule( modulesByApp[ app ][ i ] );
			}
		}
	}
	
	function checkModule( module )
	{
		if ( $( 'module_' + module ).checked )
		{
			$( 'app_' + appsByModule[ module ] ).checked = true;
		}
		
		for ( var i in itemsByModule[ module ] )
		{
			if ( $( 'item_' + module + '_' + itemsByModule[ module ][ i ] ) )
			{
		    	$( 'item_' + module + '_' + itemsByModule[ module ][ i ] ).checked = $( 'module_' + module ).checked;
			}
		}
	}
	
	function checkItem( module )
	{
		$( 'module_' + module ).checked = true;
		$( 'app_' + appsByModule[ module ] ).checked = true;
	}

</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

}