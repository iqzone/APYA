<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP ban filters skin file
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
 
class cp_skin_banfilters
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
 * Ban filter overview screen
 *
 * @param	array 		IPs
 * @param	array 		Emails
 * @param	array 		Usernames
 * @return	string		HTML
 */
public function banOverview( $ips, $emails, $names ) {
$IPBHTML = "";
//--starthtml--//

$canRemove = $this->registry->class_permissions->checkPermission( 'ban_remove' );

/* Ban types */
$ban				= array();
$ban['bantype']		= $this->registry->output->formDropdown( 'bantype', array( array( 'ip', $this->lang->words['ban_ip'] ), array( 'email', $this->lang->words['ban_email'] ), array( 'name', $this->lang->words['ban_name'] ) ) );
$ban['bantext']		= $this->registry->output->formSimpleInput( 'bantext', '', 50 );
$ban['banreason']	= $this->registry->output->formSimpleInput( 'banreason', '', 50 );

/* Remove new lines from the type dropdown or JS won't be happy.. */
$ban['bantype']		= IPSText::br2nl($ban['bantype']);


$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ban_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='#' id='add_banfilter' title='{$this->lang->words['ban_addnew']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['ban_addnew']}</a>
			</li>
		</ul>
	</div>
</div>

<script tyle='text/javascript' src='{$this->settings['js_app_url']}acp.banfilters.js'></script>
<form method='post' id='ban-delete' action='{$this->settings['base_url']}{$this->form_code}'>
	<input type='hidden' name='do' value='ban_delete' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />

	<div class='acp-box'>
		<h3>{$this->lang->words['ban_bancontrol']}</h3>
		
		<div id='tabstrip_banForm' class='ipsTabBar with_left with_right'>
			<span class='tab_left'>&laquo;</span>
			<span class='tab_right'>&raquo;</span>
			<ul>
				<li id='tab_BanIps'>{$this->lang->words['ban_ips']}</li>
				<li id='tab_BanEmails'>{$this->lang->words['ban_emails']}</li>
				<li id='tab_BanNames'>{$this->lang->words['ban_names']}</li>
			</ul>
		</div>
		
		<div id='tabstrip_banForm_content' class='ipsTabBar_content'>
			
			<!-- IP ADDRESSES -->
			<div id='tab_BanIps_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<th width='1%'>&nbsp;</th>
						<th width='15%'>{$this->lang->words['ban_ip']}</th>
						<th width='60%'>{$this->lang->words['ban_form_reason']}</th>
						<th width='24%'>{$this->lang->words['ban_added_on']}</th>
					</tr>
HTML;

if( is_array( $ips ) && count( $ips ) )
{
	foreach( $ips as $r )
	{
			$checkbox = $canRemove ? "<input type='checkbox' name='banid_{$r['ban_id']}' value='1' />" : '';
			
			$IPBHTML .= <<<HTML
					<tr>
						<td>{$checkbox}</td>
						<td>{$r['ban_content']}</td>
						<td>{$r['ban_reason']}</td>
						<td>{$r['_date']}</td>
					</tr>
HTML;
	}
}
else
{
			$IPBHTML .= <<<HTML
					<tr>
						<td colspan='4'>{$this->lang->words['ban_ips_none']}</td>
					</tr>
HTML;
}

$IPBHTML .= <<<HTML
		 		</table>
			</div>
			
			<!-- EMAIL ADDRESSES -->
			<div id='tab_BanEmails_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<th width='1%'>&nbsp;</th>
						<th width='20%'>{$this->lang->words['ban_email']}</th>
						<th width='55%'>{$this->lang->words['ban_form_reason']}</th>
						<th width='24%'>{$this->lang->words['ban_added_on']}</th>
					</tr>
HTML;

if( is_array( $emails ) && count( $emails ) )
{
	foreach( $emails as $r )
	{
			$checkbox = $canRemove ? "<input type='checkbox' name='banid_{$r['ban_id']}' value='1' />" : '';
			
			$IPBHTML .= <<<HTML
					<tr>
						<td>{$checkbox}</td>
						<td>{$r['ban_content']}</td>
						<td>{$r['ban_reason']}</td>
						<td>{$r['_date']}</td>
					</tr>
HTML;
	}
}
else
{
			$IPBHTML .= <<<HTML
					<tr>
						<td colspan='4'>{$this->lang->words['ban_emails_none']}</td>
					</tr>
HTML;
}

$IPBHTML .= <<<HTML
		 		</table>
			</div>
			
			<!-- NAMES -->
			<div id='tab_BanNames_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<th width='1%'>&nbsp;</th>
						<th width='15%'>{$this->lang->words['ban_name']}</th>
						<th width='60%'>{$this->lang->words['ban_form_reason']}</th>
						<th width='24%'>{$this->lang->words['ban_added_on']}</th>
					</tr>
HTML;

if( is_array( $names ) && count( $names ) )
{
	foreach( $names as $r )
	{
			$checkbox = $canRemove ? "<input type='checkbox' name='banid_{$r['ban_id']}' value='1' />" : '';
			
			$IPBHTML .= <<<HTML
					<tr>
						<td>{$checkbox}</td>
						<td>{$r['ban_content']}</td>
						<td>{$r['ban_reason']}</td>
						<td>{$r['_date']}</td>
					</tr>
HTML;
	}
}
else
{
			$IPBHTML .= <<<HTML
					<tr>
						<td colspan='4'>{$this->lang->words['ban_names_none']}</td>
					</tr>
HTML;
}

$IPBHTML .= <<<HTML
		 		</table>
			</div>
			
			<script type='text/javascript'>
				jQ("#tabstrip_banForm").ipsTabBar({ tabWrap: "#tabstrip_banForm_content" });
			</script>
		</div>
HTML;
		
if ( $canRemove )
{
	$IPBHTML .= <<<HTML
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['ban_deletebutton']}' class='realbutton redbutton' />
		</div>
HTML;
}

$IPBHTML .= <<<HTML
	</div>
</form>

<script type='text/javascript'>
	ipb.templates['add_banfilter'] = "<div class='acp-box'><h3>{$this->lang->words['ban_addnew']}</h3><form action='{$this->settings['base_url']}{$this->form_code}do=ban_add' method='post'><table class='ipsTable double_pad'><tr><td class='field_title' style='min-width:100px;'><strong class='title'>{$this->lang->words['ban_form_type']}</strong></td><td class='field_field'>{$ban['bantype']}</td></tr><tr><td class='field_title' style='min-width:100px;'><strong class='title'>{$this->lang->words['ban_form_filter']}</strong></td><td class='field_field'>{$ban['bantext']}<br /><span class='desctext'>{$this->lang->words['ban_form_filter_desc']}</span></td></tr><tr><td class='field_title' style='min-width:100px;'><strong class='title'>{$this->lang->words['ban_form_reason']}</strong></td><td class='field_field'>{$ban['banreason']}<br /><span class='desctext'>{$this->lang->words['ban_form_reason_desc']}</span></td></tr></table><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['ban_addnew']}' class='button primary' /></div></form></div>";
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

}