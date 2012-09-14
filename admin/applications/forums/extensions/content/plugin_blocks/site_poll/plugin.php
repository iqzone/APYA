<?php

/**
 * Show a poll widget
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9350 $ 
 * @since		1st March 2009
 */

class plugin_site_poll implements pluginBlockInterface
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
																		array( '&#36;content', $this->lang->words['block_plugin_sp_content'] ) ,
																		array( '&#36;tid', $this->lang->words['block_plugin_sp_tid'] ) ,
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
					'key'			=> 'site_poll',
					'name'			=> $this->lang->words['plugin_name__site_poll'],
					'description'	=> $this->lang->words['plugin_description__site_poll'],
					'hasConfig'		=> true,
					'templateBit'	=> 'block__site_poll',
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
		return array(
					array(
						'label'			=> $this->lang->words['plugin__poll_label1'],
						'description'	=> $this->lang->words['plugin__poll_desc1'],
						'field'			=> $this->registry->output->formInput( 'plugin__poll', $session['config_data']['custom_config']['poll'] ),
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
		return array( $data['plugin__poll'] ? true : false, array( 'poll' => $data['plugin__poll'] ) );
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
		$config	= unserialize($block['block_config']);
		
		if( !$config['custom']['poll'] )
		{
			return '';
		}
		
		/* Friendly URL */
		if( $this->settings['use_friendly_urls'] )
		{
			preg_match( "#/topic/(\d+)(.*?)/#", $config['custom']['poll'], $match );
			$tid = intval( trim( $match[1] ) );
		}
		/* Normal URL */
		else
		{
			preg_match( "/(\?|&amp;)(t|showtopic)=(\d+)($|&amp;)/", $config['custom']['poll'], $match );
			$tid = intval( trim( $match[3] ) );
		}

		if( !$tid )
		{
			return '';
		}

		$poll	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'polls', 'where' => 'tid=' . $tid ) );
		
		if( !$poll['pid'] )
		{
			return '';
		}

		$this->lang->loadLanguageFile( array( 'public_boards', 'public_topic' ), 'forums' );
		$this->lang->loadLanguageFile( array( 'public_editors' ), 'core' );

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", "class_forums", 'forums' );
		$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
		$this->registry->class_forums->forumsInit();

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/modules_public/forums/topics.php', "public_forums_forums_topics", 'forums' );
		$topic			= new $classToLoad( $this->registry );
		$topic->makeRegistryShortcuts( $this->registry );

		ipsRegistry::$request['t'] = $poll['tid'];
		
		/* Init */
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}

		try
		{
			/* Load up the data dudes */
			$this->registry->getClass('topics')->autoPopulate();
		}
		catch( Exception $crowdCheers )
		{}
		
		$topic->forumClass = $this->registry->getClass('class_forums');

		$topic->topicSetUp( $this->registry->getClass('topics')->getTopicData() );

		if ( $this->registry->getClass('topics')->getTopicData('poll_state') )
		{
			$pluginConfig	= $this->returnPluginInfo();
			$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
			$pollOutput		= $topic->_generatePollOutput();
		
			ob_start();
	 		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $pollOutput['html'], $tid );
	 		ob_end_clean();
	 		return $_return;
 		}
 		else
 		{
 			return;
 		}
	}
}