<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP permissions skin file
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
 
class cp_skin_permissions
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
 * Confirmation page that perm set was removed
 *
 * @param	string	Name
 * @return	string	HTML
 */
public function permissionSetRemoveDone( $name ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['per_result']}</h3>
	<table class='ipsTable'>
		<tr>
			<td valign='middle'>{$this->lang->words['per_removecustom']}<b>{$name}</b>.</td>
		</tr>
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show users assigned to the perm mask
 *
 * @param	integer	Perm mask id
 * @param	string	Permission set name
 * @param	array 	Users with this mask
 * @return	string	HTML
 */
public function permissionSetUsers( $perm_id, $set, $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['per_membersusing']}{$set}</h3>

	<table class='ipsTable'>
		<tr>
			<th width='50%'>{$this->lang->words['per_userdetail']}</th>
			<th width='50%'>{$this->lang->words['per_action']}</th>
		</tr>
HTML;

foreach( $rows as $r )
{
	$also_using = '';
	
	if ( $r['_extra'] )
	{
		$also_using = "<br />\n&#149;&nbsp;{$this->lang->words['per_alsousing']}{$r['_extra']}\n";
	}
	
$IPBHTML .= <<<HTML
		<tr>
			<td width='50%' style='vertical-align: top;'>
				<div style='font-weight:bold;font-size:11px;padding-bottom:6px;margin-bottom:3px;border-bottom:1px solid #000'>{$r['members_display_name']}</div>
				&#149;&nbsp;{$this->lang->words['per_posts']}{$r['posts']}<br />
				&#149;&nbsp;{$this->lang->words['per_email']}{$r['email']}
				{$also_using}
			</td>

			<td width='50%' style='vertical-align: top;'>
				&#149;&nbsp;<a href='{$this->settings['base_url']}{$this->form_code}do=remove_mask&amp;id={$r['member_id']}&amp;pid={$perm_id}' title='{$this->lang->words['per_removethis']}'>{$this->lang->words['per_removethisset']}</a><br />
				&#149;&nbsp;<a href='{$this->settings['base_url']}{$this->form_code}do=remove_mask&amp;id={$r['member_id']}&amp;pid=all' title='{$this->lang->words['per_removeall']}'>{$this->lang->words['per_removeall']}</a>
			</td>
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
 * Permissions matrix
 *
 * @param	array 		Permission names
 * @param	array		Compiled grids
 * @param	array 		Permissions checked
 * @param	array 		Colors
 * @param	string		Type
 * @param	boolean		$addOutsideBox		Add or not the outside acp-box
 * @return	string		HTML
 */
public function permissionMatrix( $perm_names, $perm_matrix, $perm_checked, $colors, $type, $addOutsideBox=true )
{
$IPBHTML = "";
//--starthtml--//

$cols = count( $perm_names ) + 1;
$width = ceil( 83 / count( $perm_names ) );

$IPBHTML .= $addOutsideBox ? "<div class='acp-box'>" : '';

$IPBHTML .= <<<HTML
<!--<h3>{$this->lang->words['frm_enterthematrix']}</h3>-->
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.permissions2.js'></script>

<table class='ipsTable permission_table' id='perm_matrix'>
	<tr>
		<th style='width: 13%'>&nbsp;</th>
HTML;

foreach( $perm_names as $k => $v )
{
	$IPBHTML .= <<<HTML
		<th style='width: {$width}%; text-align: center;'>{$v}</td>
HTML;
}

$IPBHTML .= <<<HTML
	</tr>
	<tr>
		<td class='section' colspan='{$cols}'>{$this->lang->words['frm_global']}</td>
	</tr>
	<tr>
		<td class='off'>&nbsp;</td>
HTML;

$col_num = 0;

foreach( $perm_names as $k => $v )
{
	$col_num++;
	$IPBHTML .= <<<HTML
		<!-- Check an entire column -->
		<td style='background-color:{$colors[$k]}' class='perm column' id='column_{$col_num}'>
			{$v}<br />
			<input type='checkbox' name='perms[{$type}{$k}][*]' id='col_{$col_num}' value='1'{$perm_checked['*'][$k]}>
		</td>
HTML;
}

$IPBHTML .= <<<HTML
	</tr>
HTML;

$row_num = 0;

foreach( $perm_matrix as $set => $row )
{
	$set = explode( '%', $set );
	$row_num++;
	$col_num = 0;
	$IPBHTML .= <<<HTML
	<tr>	
		<td class='section' colspan='{$cols}'><strong>{$set[1]}</strong></td>
	</tr>
	<tr id='forum{$type}_row_{$row_num}'>
		<td class='off'>
			<input type='button' id='forum_select_row_1_{$row_num}' value='+' class='select_row' />&nbsp;
			<input type='button' id='forum_select_row_0_{$row_num}' value='&ndash;' class='select_row' />
		</td>
HTML;

	foreach( $row as $key => $perm )
	{
		$col_num++;
		$IPBHTML .= <<<HTML
		<td class='perm' id='clickable_{$col_num}' style='background-color:{$colors[$key]}'>
			{$perm}<br />
			<input type='checkbox' name='perms[{$type}{$key}][{$set[0]}]' id='perm_{$row_num}_{$col_num}' value='1'{$perm_checked[$set[0]][$key]}>
		</td>
HTML;
	}
	
	$IPBHTML .= <<<HTML
	</tr>
HTML;
}
		
	$IPBHTML .= <<<HTML
</table>

<script type='text/javascript'>
	var permissions = new acp.permissions( { 'form': 'adminform', 'table': 'perm_matrix', 'app': 'forum', 'typekey': '{$type}' } );
	var noShowAlert	= false;
</script>
HTML;

$IPBHTML .= $addOutsideBox ? '</div>' : '';

//--endhtml--//
return $IPBHTML;	
}

/**
 * Permission set matrix
 *
 * @param	array 		Perm names
 * @param	array 		Perm grids
 * @param	array 		Boxes to check
 * @param	array 		Colors
 * @param	string		Application
 * @param	string		Type
 * @return	string		HTML
 */
public function permissionSetMatrix( $perm_names, $perm_matrix, $perm_checked, $colors, $app, $type )
{
$IPBHTML = "";
//--starthtml--//

if( !is_array( $perm_matrix ) || !count( $perm_matrix ) ){ return ''; }

$IPBHTML .= <<<HTML
		<table class='permission_table' id='perm_matrix_{$app}{$type}' style='width: 100%'>
HTML;

if( is_array( $perm_names ) && count( $perm_names ) )
{
	$col_num = 0;
	$col_width = floor( 87 / count( $perm_names ) );
	$IPBHTML .= <<<HTML
		<tr>
			<td style='padding: 0px;'>
				<table width='100%'>
					<tr>
						<td style='width: 13%'>&nbsp;</td>						
HTML;

	foreach( $perm_names as $key => $text )
	{
		$col_num++;
		$IPBHTML .= <<<HTML
						<td class='perm' style='background-color: {$colors[$key]}; width: {$col_width}%'>
							
							<input type='button' id='{$app}{$type}_select_col_1_{$col_num}' value='+' class='select_col' />&nbsp;
							<input type='button' id='{$app}{$type}_select_col_0_{$col_num}' value='&ndash;' class='select_col' />
						</td>
HTML;
	}
	
	$IPBHTML .= <<<HTML
					</tr>
				</table>
			</td>
		</tr>
HTML;
}

$row_num = 0;
foreach( $perm_matrix as $set => $row )
{
	$set = explode( '%', $set );
	$row_num++;

	if( !empty($row['_noconfig']) )
	{
$IPBHTML .= <<<HTML
			<tr>
				<td class='section'><strong>{$set[1]}</strong></td>
			</tr>
			<tr>
				<td class='no_messages ipsForm_center'>{$row['_noconfig']}</td>
			<tr>
HTML;
	}
	else
	{
$IPBHTML .= <<<HTML
			<tr>
				<td class='section'><strong>{$set[1]}</strong></td>
			</tr>
			<tr>
				<td style='padding: 0px;'>	
						<table width='100%'>
							<tr id='{$app}{$type}_row_{$row_num}'>
								<td class='off' style='width: 13%; text-align: right;'>
									<input type='button' id='{$app}{$type}_select_row_1_{$row_num}' value='+' class='select_row' />&nbsp;
									<input type='button' id='{$app}{$type}_select_row_0_{$row_num}' value='&ndash;' class='select_row' />
								</td>
HTML;

$col_num = 0;
//$col_width = floor( 87 / count( $row ) );

foreach( $perm_names as $key => $perm )
{
	$col_num++;
	
	if( isset( $row[ $key ] ) )
	{
		$IPBHTML .= <<<HTML
										<td class='perm' style='background-color:{$colors[$key]}; width: {$col_width}%'>
											{$perm}<br />
											<input type='checkbox' id='{$app}{$type}_{$col_num}_{$row_num}' name='perms[{$app}][{$type}][{$key}][{$set[0]}]' value='1'{$perm_checked[$set[0]][$key]}>
										</td>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
										<td class='perm' style='background-color:{$colors[$key]}; width: {$col_width}%'>
											<em class='desctext'>{$this->lang->words['per_notused']}</em>
										</td>
HTML;
	
	}
}

$IPBHTML .= <<<HTML
							</tr>
						</table>				
				</td>
			</tr>
HTML;
}

}

$IPBHTML .= <<<HTML
		</table>
		<script type='text/javascript'>
			perms["{$app}{$type}"] = new acp.permissions( { 'form': 'adminform', 'table': 'perm_matrix_{$app}{$type}', 'app': '{$app}', 'typekey': '{$type}' } );
		</script>
HTML;

//--endhtml--//
return $IPBHTML;	
}

/**
 * Show dialog to advise user to apply group restrictions
 *
 * @return	string		HTML
 */
public function groupAdminConfirm( $group_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
 <h3>{$this->lang->words['sm_configrest']}</h3>
 <table class='ipsTable double_pad'>
	<tr>
		<td>
			<strong class='title'>{$this->lang->words['sm_detectacp']}</strong>
 			<br /><br />
 			{$this->lang->words['sm_setrestrict']}
		</td>
	</tr>
  	</table>
	<div class='acp-actionbar'>
		<a class='button' href='{$this->settings['base_url']}&amp;{$this->form_code}'>{$this->lang->words['sm_nothanks']}</a>&nbsp;&nbsp;
		<a class='button' href='{$this->settings['base_url']}&amp;module=restrictions&amp;section=restrictions&amp;do=acpperms-group-add-complete&amp;entered_group={$group_id}'>{$this->lang->words['sm_yesplease']}</a>
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Edit permissions mask
 *
 * @param	string		Set name
 * @param	array 		Perm grids
 * @param	integer		Id
 * @param	array 		Applications
 * @return	string		HTML
 */
public function permissionEditor( $set_name, $perm_matricies, $id, $apps ) {
$IPBHTML = "";
//--starthtml--//

$i			= 1;

$IPBHTML .= <<<HTML
<!--<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tab_perms');
});
 //]]>
</script>-->

<div class='section_title'>
	<h2>{$set_name}</h2>
</div>
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.permissions2.js'></script>
<script type='text/javascript'>
	var perms = {};
</script>

<form action='{$this->settings['base_url']}{$this->form_code}do=do_edit_set&amp;id={$this->request['id']}' method='post' id='adminform'>
	<input type='hidden' name='_from' value='{$this->request['_from']}' />
	
	<div class='acp-box'>		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['per_setname']}</strong></td>
				<td class='field_field'><input type='text' class='input_text' size='30' name='perm_name' value='{$set_name}' /></td>
			</tr>
		</table>
	</div>
	<br />

HTML;

foreach( $apps as $app => $types )
{
	foreach( $types as $type )
	{
		$IPBHTML .= <<<HTML
		<input type='hidden' name='apps[{$app}][{$type}]' value='1' />
HTML;
	}
}

$IPBHTML .= <<<HTML
	<div class='acp-box'>
		<h3>{$this->lang->words['per_editset']}</h3>
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
			<div id='tab_perms' class='ipsTabBar with_left with_right'> 
				<span class='tab_left'>&laquo;</span>
				<span class='tab_right'>&raquo;</span>
				<ul>
HTML;
	foreach( $perm_matricies as $type => $matrix )
	{
		if( !$matrix ){ continue; }
		$IPBHTML .= "				<li id='tab_members" . $i ."'>" . $type . "</li>";
		$i++;
	}
	
$IPBHTML .= <<<HTML
				</ul>
			</div>
			<div id='tab_perms_content'>
HTML;

$i = 1;
	
foreach( $perm_matricies as $type => $matrix )
{
	if( !$matrix ){ continue; }
$IPBHTML .= <<<HTML
				<div id='tab_members{$i}_content' class='ipsTabBar_content'>
					{$matrix}
				</div>
HTML;
	$i++;
}

$IPBHTML .= <<<HTML
			</div>
			<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['frm_savechanges']}' class='realbutton' /></div>
		</div>
	<br style='clear: both' />
</form>
<br />

<script type='text/javascript'>
	jQ("#tab_perms").ipsTabBar({ tabWrap: "#tab_perms_content" });
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Mask splash page
 *
 * @param	array 		Masks
 * @param	string		Dropdown lsit
 * @return	string		HTML
 */
public function permissionsSplash( $rows, $dlist ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['per_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['per_setname']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='20%'>{$this->lang->words['per_name']}</th>
			<th width='15%'>{$this->lang->words['per_usedgroups']}</th>
			<th width='20%'>{$this->lang->words['per_usedmembers']}</th>
			<th width='1%'>&nbsp;</th>
		</tr>
HTML;

/* ROW */
foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
  			<td><strong>{$r['name']}</strong></td>
  			<td>{$r['groups']}</td>
  			<td>
HTML;
if ( $r['mems'] > 0 )
{
$IPBHTML .= <<<HTML
{$r['mems']} (<a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}{$this->form_code_js}do=view_perm_users&amp;id={$r['id']}", "{$this->lang->words['per_user']}", "500","350");' title='{$this->lang->words['per_viewnames']}'>{$this->lang->words['per_view']}</a>)
HTML;
}
else
{
$IPBHTML .= <<<HTML
{$this->lang->words['per_nomember']}
HTML;
}
$IPBHTML .= <<<HTML
  </td>
  <td class='col_buttons'>
  	  <ul class='ipsControlStrip'>
         <li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=edit_set_form&amp;id={$r['id']}' title='{$this->lang->words['per_editset']}'>{$this->lang->words['per_editset']}</a></li>
HTML;

	if ( ! $r['isactive'] )
	{
$IPBHTML .= <<<HTML
		<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete_set&amp;id={$r['id']}");' title='{$this->lang->words['per_deleteset']}'>{$this->lang->words['per_deleteset']}</a></li>
HTML;
	}
	else
	{
$IPBHTML .= <<<HTML
		<li class='i_delete disabled'><a href='#' onclick='return false;' title='{$this->lang->words['per_inuse']}'>{$this->lang->words['per_inuse']}</a></li>
HTML;
	}
$IPBHTML .= <<<HTML
      </ul>
  </td>
</tr>
HTML;
}
/* / ROW */

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='do_create_set' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['per_createnew']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['per_setname']}</strong></td>
				<td class='field_field'><input type='text' class='input' size='30' name='new_perm_name' /></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['per_baseon']}</strong></td>
				<td class='field_field'><select name='new_perm_copy' class='dropdown'>{$dlist}</select></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['per_createbutton']}' class='button primary' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


}