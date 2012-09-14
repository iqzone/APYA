<?php

/**
 * Status Updates widget
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8644 $ 
 * @since		1st March 2009
 */

class plugin_status_updates implements pluginBlockInterface
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	protected $registry;
	protected $request;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->memberData	=& $registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @return	array
	 */
	public function getTags()
	{
		return array(
					$this->lang->words['block_plugin__generic'] => array( 
																		array( '&#36;title', $this->lang->words['block_custom__title'] ) ,
																		array( '&#36;output', $this->lang->words['block_plugin_su_content'] ) ,
																		),
					);
	}
	
	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (name, description, hasConfig)
	 */
	public function returnPluginInfo()
	{
		return array(
					'key'			=> 'status_updates',
					'name'			=> $this->lang->words['plugin_name__status_updates'],
					'description'	=> $this->lang->words['plugin_description__status_updates'],
					'hasConfig'		=> false,
					'templateBit'	=> 'block__status_updates',
					);
	}
	
	/**
	 * Get plugin configuration data.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnPluginConfig( $session )
	{
		return array();
	}

	/**
	 * Check the plugin config data
	 *
	 * @access	public
	 * @param	array 			Submitted plugin data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Plugin data to use )
	 */
	public function validatePluginConfig( $data )
	{
		return array( true, $data );
	}
	
	/**
	 * Execute the plugin and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	string				Block HTML to display or cache
	 */
	public function executePlugin( $block )
	{
		$this->lang->loadLanguageFile( array( 'public_boards' ), 'forums' );

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/hooks/gateway.php', "members_hookGateway", 'members' );
		$hookGateway	= new $classToLoad( $this->registry );
		$output			= $hookGateway->statusUpdates();

		$pluginConfig	= $this->returnPluginInfo();
		$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
		ob_start();
 		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $output );
 		ob_end_clean();
 		return $_return;
	}
}