<?php

/**
 * Show a mini-calendar widget
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9957 $ 
 * @since		1st March 2009
 */

class plugin_mini_calendar implements pluginBlockInterface
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
																		array( '&#36;content', $this->lang->words['block_plugin_mc_content'] ) ,
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
					'key'			=> 'mini_calendar',
					'app'			=> 'calendar',
					'name'			=> $this->lang->words['plugin_name__mini_calendar'],
					'description'	=> $this->lang->words['plugin_description__mini_calendar'],
					'hasConfig'		=> true,
					'templateBit'	=> 'block__mini_calendar',
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
		$options	= array();
		
		foreach( $this->cache->getCache('calendars') as $cal_id => $cal )
		{
			$options[]	= array( $cal['cal_id'], $cal['cal_title'] );
		}

		return array(
					array(
						'label'			=> $this->lang->words['plugin__cal_label1'],
						'description'	=> $this->lang->words['plugin__cal_desc1'],
						'field'			=> $this->registry->output->formDropdown( 'plugin__cal_calendar', $options, $session['config_data']['custom_config']['calendar'] ),
						)
					);
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
		$calId		= 0;
		$default	= 0;

		foreach( $this->cache->getCache('calendars') as $cal_id => $cal )
		{
			if( !$default )
			{
				$default	= $cal_id;
			}
			
			if( $cal_id == $data['plugin__cal_calendar'] )
			{
				$calId	= $cal_id;
			}
		}
		
		return array( true, array( 'calendar' => $calId ? $calId : $default ) );
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
		if ( !IPSLib::appIsInstalled('calendar') OR !is_file( IPSLib::getAppDir( 'calendar' ) . '/modules_public/calendar/view.php' ) )
		{
			return '';
		}

		$config	= unserialize($block['block_config']);

		ipsRegistry::$request['cal_id']	= $config['custom']['calendar'];
		
		//-----------------------------------------
		// Grab calendar class
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadActionOverloader( IPSLib::getAppDir( 'calendar' ) . '/modules_public/calendar/view.php', "public_calendar_calendar_view" );
		$calendar		= new $classToLoad( $this->registry );
		$calendar->makeRegistryShortcuts( $this->registry );

		if( !$calendar->initCalendar(true) )
		{
			return '';
		}

 		//-----------------------------------------
 		// What now?
 		//-----------------------------------------
 		
 		$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + ipsRegistry::getClass( 'class_localization')->getTimeOffset() ) );
		
		$now_date = array(
						  'year'    => $a[0],
						  'mon'     => $a[1],
						  'mday'    => $a[2],
						  'hours'   => $a[3],
						  'minutes' => $a[4],
						  'seconds' => $a[5]
						);
							   
 		$content = $calendar->getMiniCalendar( $now_date['mon'], $now_date['year'] );
 		
		$pluginConfig	= $this->returnPluginInfo();
		$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
		ob_start();
 		$_return	= $this->registry->getClass('output')->getTemplate('ccs')->$templateBit( $content );
 		ob_end_clean();
 		return $_return;
	}
}