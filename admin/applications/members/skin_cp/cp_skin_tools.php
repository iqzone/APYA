<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP tools skin file
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
 
class cp_skin_tools
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
 * Merge start form
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function mergeStart( $member ){
$IPBHTML = "";

$desc	= sprintf( $this->lang->words['merge_explanation'], $member['members_display_name'], $member['members_display_name'] );

$form['name'] = ipsRegistry::getClass('output')->formInput( 'name', ipsRegistry::$request['name'], 'member' );
$form['email'] = ipsRegistry::getClass('output')->formInput( 'email', ipsRegistry::$request['email'] );
$form['target_id'] = ipsRegistry::getClass('output')->formSimpleInput( 'target_id', ipsRegistry::$request['target_id'] );

$IPBHTML .= <<<HTML
<div class='information-box'>
{$desc}
</div>
<br />
<form action='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=doMerge' method='post'>
	<input type='hidden' name='member_id' value='{$member['member_id']}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['merge_any_details']}</h3>
 
 		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['merge_find_name']}</strong></td>
				<td class='field_field'>{$form['name']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['merge_find_email']}</strong></td>
				<td class='field_field'>{$form['email']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['merge_find_id']}</strong></td>
				<td class='field_field'>{$form['target_id']}</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			 <input type='submit' class='button primary' value='{$this->lang->words['merge_continue_button']}' />
		</div>
	</div>
</form>
<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var autoComplete = new ipb.Autocomplete( $('member'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Confirm merge of two members
 *
 * @param	array 		Member data
 * @param	array 		Member data
 * @return	string		HTML
 */
public function mergeConfirm( $member, $member2 ){
$IPBHTML = "";

$desc	= sprintf( $this->lang->words['merge_confirmation'], $member2['members_display_name'], $member['members_display_name'], $member2['members_display_name'] );

$IPBHTML .= <<<HTML
<div class='information-box'>
{$desc}
</div>
<br />
<form action='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=doMerge' method='post'>
	<input type='hidden' name='member_id' value='{$member['member_id']}' />
	<input type='hidden' name='member_id2' value='{$member2['member_id']}' />
	<input type='hidden' name='confirm' value='1' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['merge_confirm_title']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['merge_remove']}</strong></td>
				<td class='field_field'><a href='{$this->settings['_original_base_url']}/index.php?showuser={$member2['member_id']}'>{$member2['members_display_name']}</a></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['merge_keep']}</strong></td>
				<td class='field_field'><a href='{$this->settings['_original_base_url']}/index.php?showuser={$member['member_id']}'>{$member['members_display_name']}</a></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			 <input type='submit' class='button primary' value='{$this->lang->words['merge_submit']}' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member tools splash page
 *
 * @param	string		Message
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function toolsIndex( $message, $form=array() ) {

$IPBHTML = "";
//--starthtml--//

if( $message )
{
	$IPBHTML .= <<<HTML
<div class='information-box'>
	<p>{$message}</p>
</div>
<br />
HTML;
}

$form_name	= ipsRegistry::getClass('output')->formInput( "name", isset($_POST['name']) ? IPSText::stripslashes($_POST['name']) : '' );
$form_ip	= ipsRegistry::getClass('output')->formInput( "ip", isset($_POST['ip']) ? IPSText::stripslashes($_POST['ip']) : '' );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['t_iptoolstitle']}</h2>
</div>
<form action='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=show_all_ips' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['t_showallip']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$form['text']}</strong></td>
				<td class='field_field'>{$form['form']}</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$this->lang->words['t_showallbutton']}' />
		</div>
	</div>
</form>
<br />

<form action='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=learn_ip' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['t_ipmulti']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
	 			<td class='field_title'><strong class='title'>{$this->lang->words['t_showme']}</strong></td>
				<td class='field_field'>{$form_ip}</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
				<input type='submit' class='button primary' value='{$this->lang->words['t_showmebutton']}' />
		</div>
	</div>
</form>
<br />
<br />

<div class='section_title'>
	<h2>{$this->lang->words['t_guesttools']}</h2>
</div>
<form action='{$this->settings['base_url']}&amp;app=forums&amp;module=tools&amp;section=tools&amp;do=deleteposts' method='post'>
	<input type='hidden' name='member_id' value='0' />
	<div class='acp-box'>
		<h3>{$this->lang->words['t_deleteallguestsposts']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
	 			<td class='field_title'><strong class='title'>{$this->lang->words['t_guestname']}</strong></td>
				<td class='field_field'>
					{$form_name}<br />
					<span class='desctext'>{$this->lang->words['t_guestname_desc']}</span>
				</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
				<input type='submit' class='button primary' value='{$this->lang->words['t_deleteposts']}' />
		</div>
	</div>
</form>

<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Delete PM wrapper
 *
 * @param	array		Member
 * @param	int 		Total topics
 * @param	int			Total replies
 * @return	string		HTML
 */
public function deleteMessagesWrapper( $member, $countTopics, $countReplies )
{
$IPBHTML = "";
//--starthtml--//

$countTopics['total'] = intval($countTopics['total']);
$countReplies['total'] = intval($countReplies['total']);

$this->lang->words['total_pms_topics']	= sprintf( $this->lang->words['total_pms_topics'], $countTopics['total'] );
$this->lang->words['total_pms_replies']	= sprintf( $this->lang->words['total_pms_replies'], $countReplies['total'] );

$topicsYesNo	= $this->registry->output->formYesNo( 'topics', 0 );
$repliesYesNo	= $this->registry->output->formYesNo( 'replies', 0 );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['member_management_h2']}</h2>
</div>

<form action='{$this->settings['base_url']}module=members&amp;section=tools&amp;do=deleteMessages&amp;member_id={$member['member_id']}&amp;confirm=1' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['tools_delete_all_pms']} {$member['members_display_name']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['total_pms_topics']}</strong>
				</td>
				<td class='field_field'>
					{$topicsYesNo}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['total_pms_replies']}</strong>
				</td>
				<td class='field_field'>
					{$repliesYesNo}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['confirm_pm_button']}' class='realbutton' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show all of a member's IP addresses
 *
 * @param	array 		Member data
 * @param	array 		All IPs
 * @param	string		Page links
 * @param	array 		Member's registering with IP
 * @return	string		HTML
 */
public function showAllIPs( $member, $allips, $links, $reg=array() ) {

$IPBHTML = "";
//--starthtml--//

$count = count($allips);
$counttxt = sprintf( $this->lang->words['t_counttxt'], $member['members_display_name'], $count );
$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$counttxt}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='20%'>{$this->lang->words['t_ipaddy']}</th>
			<th width='10%'>{$this->lang->words['t_timesused']}</th>
			<th width='25%'>{$this->lang->words['t_lastused']}</th>
			<th width='20%'>{$this->lang->words['t_usedotherreg']}</th>
			<th width='25%'>{$this->lang->words['t_iptool']}</th>
	 	</tr>
HTML;

if( is_array($allips) AND count($allips) )
{
	foreach( $allips as $ip_address => $use_info )
	{
		$date = $use_info[1] ? ipsRegistry::getClass( 'class_localization')->getDate( $use_info[1], 'SHORT' ) : 'No Info';
		$others	= intval( count( $reg[ $ip_address ] ) );

		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$ip_address}</strong></td>
			<td>{$use_info[0]}</td>
			<td>{$date}</td>
			<td>{$others}</td>
			<td><a href='{$this->settings['base_url']}module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$ip_address}'>{$this->lang->words['t_learnmore']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<th colspan='5' align='center'>{$this->lang->words['t_noips']}</th>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar' style='text-align: left;'>
		{$links}
	</div>
</div>
<br />

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Learn about an IP
 *
 * @param	string		Host address
 * @param	array 		All registering members
 * @param	array 		All posting members
 * @param	array 		All voting members
 * @param	array 		All emailing members
 * @param	array 		All validating members
 * @param	array 		All other instances of IP
 * @return	string		HTML
 */
public function learnIPResults( $hostAddr, $registered=array(), $posted=array(), $voted=array(), $emailed=array(), $validating=array(), $results=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['hostaddress_for']} {$this->request['ip']}</h3>
 	
	<table class='ipsTable'>
		<tr>
			<td width='40%'>{$this->lang->words['t_ipresolves']}</td>
			<td width='60%'>{$hostAddr}</td>
		</tr>
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipreg']}</h3>
	<table class='ipsTable'>
	<tr>
		<th width='30%'>{$this->lang->words['t_ipname']}</th>
		<th width='20%'>{$this->lang->words['t_ipemail']}</th>
		<th width='10%'>{$this->lang->words['t_ipposts']}</th>
		<th width='20%'>{$this->lang->words['t_ipip']}</th>
		<th width='20%'>{$this->lang->words['t_ipregistered']}</th>
	 </tr>
HTML;

if( is_array($registered) AND count($registered) )
{
	foreach( $registered as $member )
	{
		$IPBHTML .= <<<HTML
	 <tr>
		<td><strong>{$member['members_display_name']}</strong></td>
		<td>{$member['email']}</td>
		<td>{$member['posts']}</td>
		<td>{$member['ip_address']}</td>
		<td>{$member['_joined']}</td>
	 </tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipposting']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='20%'>{$this->lang->words['t_ipemail']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipposted']}</th>
			<th width='10%'>{$this->lang->words['t_ipview']}</th>
		</tr>
HTML;

if( is_array($posted) AND count($posted) )
{
	foreach( $posted as $member )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$member['members_display_name']}</strong></td>
			<td>{$member['email']}</td>
			<td>{$member['ip_address']}</td>
			<td>{$member['_post_date']}</td>
			<td align='center'><a href='{$this->settings['board_url']}/index.php?app=forums&amp;module=forums&amp;section=findpost&amp;pid={$member['pid']}' target='_blank'>{$this->lang->words['t_ipviewpost']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipvoting']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='20%'>{$this->lang->words['t_ipemail']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipfirstused']}</th>
			<th width='10%'>{$this->lang->words['t_ipview']}</th>
		</tr>
HTML;

if( is_array($voted) AND count($voted) )
{
	foreach( $voted as $member )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$member['members_display_name']}</strong></td>
			<td>{$member['email']}</td>
			<td>{$member['ip_address']}</td>
			<td>{$member['_vote_date']}</td>
			<td align='center'><a href='{$this->settings['board_url']}/index.php?showtopic={$member['tid']}' target='_blank'>{$this->lang->words['t_ipviewpoll']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipemailing']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='30%'>{$this->lang->words['t_ipemail']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipfirstused']}</th>
		</tr>
HTML;

if( is_array($emailed) AND count($emailed) )
{
	foreach( $emailed as $member )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$member['members_display_name']}</strong></td>
			<td>{$member['email']}</td>
			<td>{$member['from_ip_address']}</td>
			<td>{$member['_email_date']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipvalidating']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='30%'>{$this->lang->words['t_ipemail']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipfirstused']}</th>
		</tr>
HTML;

if( is_array($validating) AND count($validating) )
{
	foreach( $validating as $member )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$member['members_display_name']}</strong></td>
			<td>{$member['email']}</td>
			<td>{$member['ip_address']}</td>
			<td>{$member['_entry_date']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipalsofound']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='30%'>{$this->lang->words['t_ip_table']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipfirstused']}</th>
		</tr>
HTML;

if( is_array($results) AND count($results) )
{
	foreach( $results as $table => $result )
	{
		if( count($result) )
		{
			foreach( $result as $member )
			{
				$date	 	= $member['date'] ? ipsRegistry::getClass( 'class_localization')->getDate( $member['date'], 'SHORT' ) : '';
				$member['members_display_name']	= $member['members_display_name'] ? $member['members_display_name'] : $this->lang->words['t_guest'];
				
				$IPBHTML .= <<<HTML
				<tr>
					<td><strong>{$member['members_display_name']}</strong></td>
					<td>{$table}</td>
					<td>{$member['ip_address']}</td>
					<td>{$date}</td>
				</tr>
HTML;
			}
		}
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

}