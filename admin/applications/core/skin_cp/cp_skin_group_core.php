<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Group plugin skin functions
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 10721 $
 */
 
class cp_skin_group_core
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
 * Show forums group form
 *
 * @param	array 	Group data
 * @param	string	Tab ID
 * @param	array 	Plugins
 * @param	array 	Plugins we can view
 * @param	array 	Plugins we can send
 * @return	string	HTML
 */
public function acp_group_form_main( $group, $tabId, $plugins, $canView, $canSend ) {


$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_GROUPS_{$tabId}_content'>
	<div>
		<table class='ipsTable double_pad'>
EOF;

foreach( $plugins as $id => $data )
{
	$formSend	= $this->registry->output->formYesNo( "rc_send_" . $id, in_array( $id, $canSend ) );
	$formView	= $this->registry->output->formYesNo( "rc_view_" . $id, in_array( $id, $canView ) );
	
	$IPBHTML .= <<<EOF
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_rc_perms_for']} {$data['class_title']}</strong></th>
			</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_can_send_report']}</strong>
				</td>
				<td class='field_field'>
		 			{$formSend}
				</td>
		 	</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_can_view_report']}</strong>
				</td>
				<td class='field_field'>
		 			{$formView}
				</td>
		 	</tr>
EOF;
}

$IPBHTML .= <<<EOF
		</table>
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Display forum group form tabs
 *
 * @param	array 	Group data
 * @param	string	Tab id
 * @return	string	HTML
 */
public function acp_group_form_tabs( $group, $tabId ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
	<li id='tab_GROUPS_{$tabId}'>{$this->lang->words['tab_groupform_rc']}</li>
EOF;

return $IPBHTML;
}

}