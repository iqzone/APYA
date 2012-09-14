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
 
class cp_skin_warnings_actions
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
function manage( $actions ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML

<div class='section_title'>
	<h2>{$this->lang->words['warn_actions']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['warn_actions_add']}</a></li>
		</ul>
	</div>
</div>

<div class='acp-box'>
 	<h3>{$this->lang->words['warn_actions']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='3%'>&nbsp;</th>
			<th>{$this->lang->words['warn_actions_points']}</th>
			<th>{$this->lang->words['warn_actions_mq_short']}</th>
			<th>{$this->lang->words['warn_actions_rpa_short']}</th>
			<th>{$this->lang->words['warn_actions_suspend_short']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( !empty( $actions ) )
{
	
	foreach ( $actions as $actionID => $data )
	{
		foreach ( array( 'mq', 'rpa', 'suspend' ) as $k )
		{
			$dbField = "wa_{$k}";
			$kWithUnit = "{$k}_unit";
			if ( $data[$dbField] == -1 )
			{
				$$kWithUnit = $this->lang->words['warnings_permanently'];
			}
			elseif ( $data[$dbField] == 0 )
			{
				$$kWithUnit = '--';
			}
			else
			{
				$$kWithUnit = $data[$dbField] . ' '  . ( ( $data[ $dbField . '_unit' ] == 'd' ) ? $this->lang->words['warnings_days'] : $this->lang->words['warnings_hours'] );
			}
		}
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>&nbsp;</td>
			<td><span class='larger_text'><a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$actionID}'>{$data['wa_points']}</a></span></td>
			<td>{$mq_unit}</td>
			<td>{$rpa_unit}</td>
			<td>{$suspend_unit}</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$actionID}' title='{$this->lang->words['edit']}'>{$this->lang->words['edit']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=delete&amp;id={$actionID}");' title='{$this->lang->words['delete']}'>{$this->lang->words['delete']}</a>
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
			<td colspan='6' class='no_messages'>
				{$this->lang->words['warn_actions_empty']} <a href='{$this->settings['base_url']}{$this->form_code}do=add' class='mini_button'>{$this->lang->words['warn_actions_add']}</a>
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

//===========================================================================
// Form
//===========================================================================
function form( $current ) {

if ( empty( $current ) )
{
	$title = $this->lang->words['warn_actions_add'];
	$id = 0;
	$icon = 'add';
}
else
{
	$title = $this->lang->words['warn_actions_add'];
	$id = $current['wa_id'];
	$icon = 'edit';
}

$form['points'] = ipsRegistry::getClass('output')->formSimpleInput( 'points', ( empty( $current ) ? '' : $current['wa_points'] ) );
$form['mq'] = ipsRegistry::getClass('output')->formSimpleInput( 'mq', ( empty( $current ) ? '' : $current['wa_mq'] ) );
$form['mq_unit'] = ipsRegistry::getClass('output')->formDropdown( 'mq_unit', array( array( 'h', $this->lang->words['warnings_hours'] ), array( 'd', $this->lang->words['warnings_days'] ) ), ( empty( $current ) ? '' : $current['wa_mq_unit'] ) );
$form['mq_perm'] = ipsRegistry::getClass('output')->formCheckbox( 'mq_perm', ( !empty( $current ) and $current['wa_mq'] == -1 ? 1 : 0 ) );
$form['rpa'] = ipsRegistry::getClass('output')->formSimpleInput( 'rpa', ( empty( $current ) ? '' : $current['wa_rpa'] ) );
$form['rpa_unit'] = ipsRegistry::getClass('output')->formDropdown( 'rpa_unit', array( array( 'h', $this->lang->words['warnings_hours'] ), array( 'd', $this->lang->words['warnings_days'] ) ), ( empty( $current ) ? '' : $current['wa_rpa_unit'] ) );
$form['rpa_perm'] = ipsRegistry::getClass('output')->formCheckbox( 'rpa_perm', ( !empty( $current ) and $current['wa_rpa'] == -1 ? 1 : 0 ) );
$form['suspend'] = ipsRegistry::getClass('output')->formSimpleInput( 'suspend', ( empty( $current ) ? '' : $current['wa_suspend'] ) );
$form['suspend_unit'] = ipsRegistry::getClass('output')->formDropdown( 'suspend_unit', array( array( 'h', $this->lang->words['warnings_hours'] ), array( 'd', $this->lang->words['warnings_days'] ) ), ( empty( $current ) ? '' : $current['wa_suspend_unit'] ) );
$form['suspend_perm'] = ipsRegistry::getClass('output')->formCheckbox( 'suspend_perm', ( !empty( $current ) and $current['wa_suspend'] == -1 ? 1 : 0 ) );
$form['ban_group'] = ipsRegistry::getClass('output')->formCheckbox( 'ban_group', ( empty( $current ) ? '' : ( $current['wa_ban_group'] > 0 ? 1 : 0 ) ) );
$form['override'] = ipsRegistry::getClass('output')->formYesNo( 'override', ( empty( $current ) ? '' : $current['wa_override'] ) );

$mq_extra_style = ( !empty( $current ) and $current['wa_mq'] == -1 ) ? 'display:none' : '';
$rpa_extra_style = ( !empty( $current ) and $current['wa_rpa'] == -1 ) ? 'display:none' : '';
$suspend_extra_style = ( !empty( $current ) and $current['wa_suspend'] == -1 ) ? 'display:none' : '';

$form['ban_group_id'] = ipsRegistry::getClass('output')->formDropdown( 'ban_group_id', '--groups--', ( empty( $current ) ? '' : $current['wa_ban_group'] ) );


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
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_actions_points']}</strong></td>
			<td class='field_field'>
				{$form['points']}<br />
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_actions_mq']}</strong></td>
			<td class='field_field'>
				<label for='mq_perm'>{$form['mq_perm']} {$this->lang->words['warnings_permanently']}</label>
				<span id='mq_extra' style='{$mq_extra_style}'>{$this->lang->words['warnings_or_for']}<br /><br />{$form['mq']} {$form['mq_unit']}<br /></span>
				<br /><span class='desctext'>{$this->lang->words['warnings_content_desc']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_actions_rpa']}</strong></td>
			<td class='field_field'>
				<label for='rpa_perm'>{$form['rpa_perm']} {$this->lang->words['warnings_permanently']}</label>
				<span id='rpa_extra' style='{$rpa_extra_style}'>{$this->lang->words['warnings_or_for']}<br /><br />{$form['rpa']} {$form['rpa_unit']}<br /></span>
				<br /><span class='desctext'>{$this->lang->words['warnings_content_desc']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_actions_suspend']}</strong></td>
			<td class='field_field'>
				<label for='suspend_perm'>{$form['suspend_perm']} {$this->lang->words['warnings_permanently']}</label>
				<span id='suspend_extra' style='{$suspend_extra_style}'>{$this->lang->words['warnings_or_for']}<br /><br />{$form['suspend']} {$form['suspend_unit']}<br /></span>
				<br /><label for='ban_group'>{$form['ban_group']} {$this->lang->words['warn_actions_ban_group']} </label>{$form['ban_group_id']}
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['warn_actions_override']}</strong></td>
			<td class='field_field'>
				{$form['override']}
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['warnings_save']}' class='realbutton'>
	</div>
	</form>
</div>

<script type='text/javascript'>
	$('mq_perm').observe('click', change.bindAsEventListener( this, 'mq' ) );
	$('rpa_perm').observe('click', change.bindAsEventListener( this, 'rpa' ) );
	$('suspend_perm').observe('click', change.bindAsEventListener( this, 'suspend' ) );
	
	function change( e, thing )
	{
		if( $( thing + '_perm' ).checked )
		{
			new Effect.Fade( $( thing + '_extra' ), { duration: 0.2 } );
		}
		else
		{
			new Effect.Appear( $( thing + '_extra' ), { duration: 0.2 } );
		}
	}
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

}