<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP Manage Warnings Reasons Skin
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $ (Original: Mark)
 * @copyright	© 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		8th November 2011
 * @version		$Rev: 10721 $
 *
 */
 
class cp_skin_warnings_reasons
{

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

//===========================================================================
// Manage
//===========================================================================
function manage( $reasons ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['warn_reasons']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['warn_reasons_add']}</a></li>
		</ul>
	</div>
</div>

<div class='acp-box'>
 	<h3>{$this->lang->words['warn_reasons']}</h3>
	<table class='ipsTable' id='warnReason_list'>
		<tr>
			<th width='3%'>&nbsp;</th>
			<th width='69%'>{$this->lang->words['warn_reasons_name']}</th>
			<th width='20%'>{$this->lang->words['warn_reasons_points']}</th>
			<th width='8%' class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( !empty( $reasons ) )
{
	
	foreach ( $reasons as $reasonID => $data )
	{
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow isDraggable' id='reasons_{$reasonID}'>
			<td class='col_drag'><span class='draghandle'>&nbsp;</span></td>
			<td><span class='larger_text'><a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$reasonID}'>{$data['wr_name']}</a></span></td>
			<td>{$data['wr_points']}</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$reasonID}' title='{$this->lang->words['edit']}'>{$this->lang->words['edit']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=delete&amp;id={$reasonID}");' title='{$this->lang->words['delete']}'>{$this->lang->words['delete']}</a>
					</li>
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
			<td colspan='3' class='no_messages'>
				{$this->lang->words['warn_reasons_empty']} <a href='{$this->settings['base_url']}{$this->form_code}do=add' class='mini_button'>{$this->lang->words['warn_reasons_add']}</a>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>

<script type='text/javascript'>
	jQ("#warnReason_list").ipsSortable( 'table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>

HTML;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Form
//===========================================================================
function form( $current ) {

if ( empty( $current ) )
{
	$title = $this->lang->words['warn_reasons_add'];
	$id = 0;
	$icon = 'add';
}
else
{
	$title = $this->lang->words['warn_reasons_add'];
	$id = $current['wr_id'];
	$icon = 'edit';
}

$form['name'] = ipsRegistry::getClass('output')->formInput( 'name', ( empty( $current ) ? '' : $current['wr_name'] ) );
$form['points'] = ipsRegistry::getClass('output')->formSimpleInput( 'points', ( empty( $current ) ? '' : $current['wr_points'] ) );
$form['points_override'] = ipsRegistry::getClass('output')->formYesNo( 'points_override', ( empty( $current ) ? '' : $current['wr_points_override'] ) );
$form['remove'] = ipsRegistry::getClass('output')->formSimpleInput( 'remove', ( empty( $current ) ? '' : $current['wr_remove'] ) );
$form['remove_unit'] = ipsRegistry::getClass('output')->formDropdown( 'remove_unit', array( array( 'h', $this->lang->words['warnings_hours'] ), array( 'd', $this->lang->words['warnings_days'] ) ), ( empty( $current ) ? '' : $current['wr_remove_unit'] ) );
$form['remove_override'] = ipsRegistry::getClass('output')->formYesNo( 'remove_override', ( empty( $current ) ? '' : $current['wr_remove_override'] ) );


$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML

<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box'>
	<h3>{$title}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}do=save' method='post'>
	<input type='hidden' name='id' value='{$id}' />
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_reasons_name']}</strong></td>
			<td class='field_field'>
				{$form['name']}
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_reasons_points']}</strong></td>
			<td class='field_field'>
				{$form['points']}<br />
				<span class='desctext'>{$this->lang->words['warn_reasons_points_desc']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_reasons_points_override']}</strong></td>
			<td class='field_field'>
				{$form['points_override']}
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_reasons_remove']}</strong></td>
			<td class='field_field'>
				{$form['remove']} {$form['remove_unit']}
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_reasons_remove_override']}</strong></td>
			<td class='field_field'>
				{$form['remove_override']}
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['warnings_save']}' class='realbutton'>
	</div>
	</form>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

}