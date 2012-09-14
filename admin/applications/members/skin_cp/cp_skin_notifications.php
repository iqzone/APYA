<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP notification config skin file
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
 
class cp_skin_notifications
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
 * Show the form to configure notification defaults
 *
 * @param	array 		Application notification options
 * @param	array 		Current configuration
 * @return	string		HTML
 */
public function showConfigurationOptions( $options, $config ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['notifications_config']}</h2>
</div>

<div class='acp-box' style='margin-top: 4px;'>
	<h3>{$this->lang->words['notifications_config_title']}</h3>
	<form method='post' action='{$this->settings['base_url']}{$this->form_code}&amp;do=save'>
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
		<table class='ipsTable double_pad'>
		<tr>
			<th>{$this->lang->words['notificationtype_h']}</th>
			<th>{$this->lang->words['notificationdfs_h']}</th>
			<th>{$this->lang->words['notificationdo_h']}</th>
			<th>{$this->lang->words['notificationdms_h']}</th>
		</tr>
HTML;

$_options = array(
					array( 'email', $this->lang->words['notopt__email'] ), 
					array( 'inline', $this->lang->words['notopt__inline'] ), 
					array( 'mobile', $this->lang->words['notopt__mobile'] ) 
				);

foreach( $options as $option )
{
	$_thisConfig	= $config[ $option['key'] ];
	
	if( !is_array($_thisConfig) OR !count($_thisConfig) )
	{
		$_thisConfig['selected']	= $option['default'];
		$_thisConfig['disabled']	= $option['disabled'];
	}
	
	$_defaultOpt	= $this->registry->output->formMultiDropdown( 'default_' . $option['key'] . '[]', $_options, $_thisConfig['selected'], 3, 'default_' . $option['key'] );
	$_disabledOpt	= $this->registry->output->formMultiDropdown( 'disabled_' . $option['key'] . '[]', $_options, $_thisConfig['disabled'], 3,'disabled_' . $option['key'] );
	$_disableOver	= $this->registry->output->formYesNo( 'disable_override_' . $option['key'], intval($_thisConfig['disable_override']) );
	
	$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$this->lang->words['notify__' . $option['key'] ]}</strong><div class='desctext' style='font-style: italic;'>{$this->lang->words['notify_key_pre']}{$option['key']}</div></td>
			<td>{$_defaultOpt}</td>
			<td>{$_disabledOpt}</td>
			<td>{$_disableOver}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['save_notify_config']}' class='realbutton' />
		</div>
	</form>
</div>	
HTML;

//--endhtml--//
return $IPBHTML;
}

}