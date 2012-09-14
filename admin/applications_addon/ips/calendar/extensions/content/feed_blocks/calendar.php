<?php
/**
 * Calendar feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10065 $ 
 * @since		1st March 2009
 */

class feed_calendar implements feedBlockInterface
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
	protected $registry;
	protected $caches;
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
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @param	string		Additional info (database id;type)
	 * @return	array
	 */
	public function getTags( $info='' )
	{
		//-----------------------------------------
		// Calendar columns
		//-----------------------------------------
		
		$_finalColumns		= array(
									'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__calurl'] ),
									'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__caldate'] ),
									'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__caltitle'] ),
									'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__calcontent'] ),
									);
		$_noinfoColumns		= array();
		
		foreach( $this->DB->getFieldNames( 'cal_events' ) as $_column )
		{
			if( $this->lang->words['col__cal_events_' . $_column ] )
			{
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__cal_events_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}
		
		foreach( $this->DB->getFieldNames( 'cal_calendars' ) as $_column )
		{
			if( $this->lang->words['col__cal_calendars_' . $_column ] )
			{
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__cal_calendars_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}

		return array(
					$this->lang->words['block_feed__generic']	=> array( 
																		array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																		),	
						
					$this->lang->words['block_feed_calendar']	=> array(
																		array( '&#36;records', $this->lang->words['block_feed__cal'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																		),
					);
	}
	
	/**
	 * Provides the ability to modify the feed type or content type values
	 * before they are passed into the gallery template search query
	 *
	 * @access 	public
	 * @param 	string 		Current feed type 
	 * @param 	string 		Current content type
	 * @return 	array 		Array with two keys: feed_type and content_type
	 */
	public function returnTemplateGalleryKeys( $feed_type, $content_type )
	{		
		return array( 'feed_type' => $feed_type, 'content_type' => $content_type );
	}

	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (key (folder name), associated app, name, description, hasFilters, templateBit)
	 */
	public function returnFeedInfo()
	{
		return array(
					'key'			=> 'calendar',
					'app'			=> 'calendar',
					'name'			=> $this->lang->words['feed_name__calendar'],
					'description'	=> $this->lang->words['feed_description__calendar'],
					'hasFilters'	=> true,
					'templateBit'	=> 'feed__generic',
					'inactiveSteps'	=> array( ),
					);
	}
	
	/**
	 * Get the feed's available content types.  Returns form elements and data
	 *
	 * @param	array 			Session data
	 * @param	array 			true: Return an HTML radio list; false: return an array of types
	 * @return	array 			Form data
	 */
	public function returnContentTypes( $session = array(), $asHTML = true )
	{
		$_types		= array(
							array( 'events', $this->lang->words['ct_events'] ),
							);
		$_html		= array();
		
		if( !$asHTML )
		{
			return $_types;
		}
		
		foreach( $_types as $_type )
		{
			$_html[]	= "<input type='radio' name='content_type' id='content_type_{$_type[0]}' value='{$_type[0]}'" . ( $session['config_data']['content_type'] == $_type[0] ? " checked='checked'" : '' ) . " /> <label for='content_type_{$_type[0]}'>{$_type[1]}</label>"; 
		}
		
		return array(
					array(
						'label'			=> $this->lang->words['generic__select_contenttype'],
						'description'	=> '',
						'field'			=> '<ul style="line-height: 1.6"><li>' . implode( '</li><li>', $_html ) . '</ul>',
						)
					);
	}
	
	/**
	 * Check the feed content type selection
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedContentTypes( $data )
	{
		if( !in_array( $data['content_type'], array( 'events' ) ) )
		{
			$data['content_type']	= 'events';
		}

		return array( true, $data['content_type'] );
	}
	
	/**
	 * Get the feed's available filter options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnFilters( $session )
	{
		$filters	= array();

		$calendars	= array();
		
		foreach( $this->cache->getCache('calendars') as $calendar )
		{
			$calendars[]	= array( $calendar['cal_id'], $calendar['cal_title'] );
		}

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_calendar__calendars'],
							'description'	=> $this->lang->words['feed_calendar__calendars_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_calendars[]', $calendars, explode( ',', $session['config_data']['filters']['filter_calendars'] ), 8 ),
							);

		$session['config_data']['filters']['filter_start']			= $session['config_data']['filters']['filter_start'] ? $session['config_data']['filters']['filter_start'] : 0;
		$session['config_data']['filters']['filter_end']			= $session['config_data']['filters']['filter_end'] ? $session['config_data']['filters']['filter_end'] : 0;
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_calendar__start'],
							'description'	=> $this->lang->words['feed_calendar__start_desc'],
							'field'			=> $this->registry->output->formInput( 'filter_start', $session['config_data']['filters']['filter_start'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_calendar__end'],
							'description'	=> $this->lang->words['feed_calendar__end_desc'],
							'field'			=> $this->registry->output->formInput( 'filter_end', $session['config_data']['filters']['filter_end'] ),
							);

		return $filters;
	}
	
	/**
	 * Check the feed filters selection
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedFilters( $session, $data )
	{
		$filters	= array();
		$return		= true;
		
		$filters['filter_calendars']	= is_array($data['filter_calendars']) ? implode( ',', $data['filter_calendars'] ) : '';
		$filters['filter_start']		= $data['filter_start'];
		$filters['filter_end']			= $data['filter_end'];

		//-----------------------------------------
		// Verify we can create a timestamp out of the dates
		//-----------------------------------------
		
		if( ( $filters['filter_start'] AND !@strtotime($filters['filter_start']) ) OR ($filters['filter_end'] AND !@strtotime($filters['filter_end']) ) )
		{
			$return	= false;
		}

		return array( $return, $filters );
	}
	
	/**
	 * Get the feed's available ordering options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnOrdering( $session )
	{
		$session['config_data']['sortorder']	= $session['config_data']['sortorder'] ? $session['config_data']['sortorder'] : 'desc';
		$session['config_data']['offset_start']	= $session['config_data']['offset_start'] ? $session['config_data']['offset_start'] : 0;
		$session['config_data']['offset_end']	= $session['config_data']['offset_end'] ? $session['config_data']['offset_end'] : 10;
		$session['config_data']['sortby']		= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'start';

		$filters	= array();

		$sortby	= array( 
						array( 'title', $this->lang->words['sort_calendar__title'] ), 
						array( 'start', $this->lang->words['sort_calendar__start'] ), 
						array( 'end', $this->lang->words['sort_calendar__end'] ),
						array( 'rand', $this->lang->words['sort_generic__rand'] )
						);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_sort_by'],
							'description'	=> $this->lang->words['feed_sort_by_desc'],
							'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
							);
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_order_direction'],
							'description'	=> $this->lang->words['feed_order_direction_desc'],
							'field'			=> $this->registry->output->formDropdown( 'sortorder', array( array( 'desc', 'DESC' ), array( 'asc', 'ASC' ) ), $session['config_data']['sortorder'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_start'],
							'description'	=> $this->lang->words['feed_limit_offset_start_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_start', $session['config_data']['offset_start'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_end'],
							'description'	=> $this->lang->words['feed_limit_offset_end_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_end', $session['config_data']['offset_end'] ),
							);
		
		return $filters;
	}
	
	/**
	 * Check the feed ordering options
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Ordering data to use )
	 */
	public function checkFeedOrdering( $data, $session )
	{
		$limits						= array();
		$sortby						= array( 'title', 'start', 'end', 'rand' );
		
		$limits['sortby']			= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'title';
		$limits['sortorder']		= in_array( $data['sortorder'], array( 'desc', 'asc' ) ) ? $data['sortorder'] : 'desc';
		$limits['offset_start']		= intval($data['offset_start']);
		$limits['offset_end']		= intval($data['offset_end']);

		return array( true, $limits );
	}
	
	/**
	 * Execute the feed and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 		Block data
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function executeFeed( $block, $previewMode=false )
	{
		$this->lang->loadLanguageFile( array( 'public_ccs' ), 'ccs' );

		$config	= unserialize( $block['block_config'] );
		$where	= array();
		
		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------
		
		$where		= array();	// array( 'e.event_approved=1', 'e.event_private=0', "e.event_perms='*'" );
		
		if( !$this->memberData['g_is_supmod'] )
		{
			$where[]	= 'e.event_approved=1';
		}
		else
		{
			$where[]	= 'e.event_approved IN (0,1)';
		}
		
		$where[]	= "((e.event_private=1 AND e.event_member_id=" . $this->memberData['member_id'] . ") OR (e.event_private=0 AND " . $this->DB->buildRegexp( "e.event_perms", $this->member->perm_id_array ) . "))";
		
		if( $config['filters']['filter_calendars'] )
		{
			$where[]	= "e.event_calendar_id IN(" . $config['filters']['filter_calendars'] . ")";
		}
		else
		{
			//-----------------------------------------
			// Get calendars
			//-----------------------------------------

			$this->DB->build( array( 'select' => 'perm_type_id as calendar_id', 'from' => 'permission_index', 'where' => "app='calendar' AND " . $this->DB->buildRegexp( "perm_view", $this->member->perm_id_array ) ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$_calendars[]	= $r['calendar_id'];
			}
			
			$where[]	= "e.event_calendar_id IN(" . ( count($_calendars) ? implode( ',', $_calendars ) : 0 ) . ")";
		}
		
		if( $config['filters']['filter_start'] OR $config['filters']['filter_end'] )
		{
			$timenow	= $config['filters']['filter_start'] ? @strtotime( $config['filters']['filter_start'] ) : 0;
			$timethen	= $config['filters']['filter_end'] ? @strtotime( $config['filters']['filter_end'] ) : 0;

			if( $timenow )
			{
				if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
				{
					$timenow	= $timenow - ( $this->memberData['time_offset'] * 3600 );
				}
				else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
				{
					$timenow	= $timenow - ( $this->settings['time_offset'] * 3600 );
				}
			}

			if( $timethen )
			{
				if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
				{
					$timethen	= $timethen - ( $this->memberData['time_offset'] * 3600 );
				}
				else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
				{
					$timethen	= $timethen - ( $this->settings['time_offset'] * 3600 );
				}
			}

			$start_date	= $timenow ? gmstrftime( "%Y-%m-%d %H:00:00", $timenow ) : 0;
			$end_date	= $timethen ? gmstrftime( "%Y-%m-%d %H:00:00", $timethen ) : 0;

			if( $start_date AND $end_date )
			{
				$where[]	= "( ( e.event_start_date <= '{$end_date}' AND e.event_end_date >= '{$start_date}' ) OR
								( ( e.event_end_date " . $this->DB->buildIsNull(true) . " OR e.event_end_date='0000-00-00 00:00:00' ) AND e.event_start_date >= '{$start_date}' AND e.event_start_date <= '{$end_date}' ) OR 
								( e.event_recurring=3 AND e.event_end_date <= '{$end_date}' AND e.event_end_date >= '{$start_date}' ) )";
			}
			else if( $start_date )
			{
				$where[]	= "( e.event_end_date >= '{$start_date}' OR
								( ( e.event_end_date " . $this->DB->buildIsNull(true) . " OR e.event_end_date='0000-00-00 00:00:00' ) AND e.event_start_date >= '{$start_date}' ) OR 
								( e.event_recurring=3 AND e.event_end_date >= '{$start_date}' ) )";
			}
			else if( $end_date )
			{
				$where[]	= "( e.event_end_date <= '{$end_date}' OR
								( ( e.event_end_date " . $this->DB->buildIsNull(true) . " OR e.event_end_date='0000-00-00 00:00:00' ) AND e.event_start_date <= '{$end_date}' ) OR 
								( e.event_recurring=3 AND e.event_end_date <= '{$end_date}' ) )";
			}
		}

		$order	= '';
		
		switch( $config['sortby'] )
		{
			case 'title':
				$order	.=	"e.event_title ";
			break;

			default:
			case 'start':
				$order	.=	"e.event_start_date ";
			break;
			
			case 'end':
				$order	.=	"e.event_end_date ";
			break;
			
			case 'rand':
				$order	.=	$this->DB->buildRandomOrder() . ' ';
			break;
		}

		$order	.= $config['sortorder'];
		
		$_key	= '';
		
		switch( $config['sortby'] )
		{
			case 'start':
				$_key	=	"start";
			break;
			
			case 'end':
				$_key	=	"end";
			break;
		}
		
		//-----------------------------------------
		// Attachment parser
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$attachments		=  new $classToLoad( $this->registry );
		$attachments->type	= 'event';
		$attachments->init();
				
		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------

		$events			= array();
		$_eventHit		= 0;
		
		$this->DB->build( array(
								'select'	=> 'e.*',
								'from'		=> array( 'cal_events' => 'e' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'c.*',
														'from'		=> array( 'cal_calendars' => 'c' ),
														'where'		=> 'c.cal_id=e.event_calendar_id',
														'type'		=> 'left',
														),
													array(
														'select'	=> 'm.member_group_id, m.mgroup_others',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'e.event_member_id=m.member_id',
														'type'		=> 'left',
														)
													)
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			//-----------------------------------------
			// Fix cal dates
			//-----------------------------------------

			$_startTime	= strtotime( $r['event_start_date'] );
			$_endTime	= ( $r['event_end_date'] AND $r['event_end_date'] != '0000-00-00 00:00:00' ) ? strtotime( $r['event_end_date'] ) : 0;

			list( $_year, $_month, $_day )	= explode( '-', preg_replace( "/(.+?)\s.+$/", "$1", $r['event_start_date'] ) );
			
			if( !$r['event_all_day'] )
			{
				if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
				{
					$_startTime	= $_startTime + ( $this->memberData['time_offset'] * 3600 );
				}
				else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
				{
					$_startTime	= $_startTime + ( $this->settings['time_offset'] * 3600 );
				}

				if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
				{
					$_endTime		= $_endTime ? ( $_endTime + ( $this->memberData['time_offset'] * 3600 ) ) : 0;
				}
				else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
				{
					$_endTime		= $_endTime ? ( $_endTime + ( $this->settings['time_offset'] * 3600 ) ) : 0;
				}
			}
			
			//-----------------------------------------
			// Recurring events
			//-----------------------------------------
			
			if( $r['event_recurring'] )
			{
				$_incrementor	= 0;
				
				switch( $r['event_recurring'] )
				{
					case 1:
						$_incrementor	= 604800;
					break;
					
					case 2:
						$_incrementor	= 2592000;
					break;
					
					case 3:
						$_incrementor	= 31536000;
					break;
				}
				
				if( $_startTime < gmmktime( 0 ) )
				{
					while( $_startTime < gmmktime( 0 ) )
					{
						$_startTime += $_incrementor;
					}
				}
			}
			
			//-----------------------------------------
			// Ranged events
			//-----------------------------------------
			
			else if( $_endTime )
			{
				if( $_startTime < gmmktime( 0 ) AND $_endTime > gmmktime( 0 ) )
				{
					while( $_startTime < gmmktime( 0 ) )
					{
						$_startTime += 86400;
					}
				}
			}

			//-----------------------------------------
			// Normalization
			//-----------------------------------------
			
			$r				= IPSMember::buildDisplayData( $r );
			$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=' . $r['event_id'], 'none', $r['event_title_seo'], 'cal_event' );
			$r['title']		= $r['event_title'];
			$r['date']		= $_startTime;
			$r['content']	= $r['event_content'];

			IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= intval( $r['event_smilies'] );
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'calendar';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
			
			$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
			
			if( $r['event_attachments'] )
			{
				$attachHTML	= $attachments->renderAttachments( array( $r['event_id'] => $r['content'] ), array( $r['event_id'] ) );
				
				if( is_array($attachHTML) )
				{
					$r['content']			= $attachHTML[ $r['event_id'] ]['html'];
					$r['content']			.= $attachHTML[ $r['event_id'] ]['attachmentHtml'];
				}
			}
			
			if( $_key )
			{
				$_thisKey	= $_key == 'start' ? $_startTime : $_endTime;
				$events[ $_thisKey . '.' . $_eventHit ]		= $r;
				$_eventHit++;
			}
			else
			{
				$events[]		= $r;
			}
		}
		
		if( $_key )
		{
			if( strtolower($config['sortorder']) == 'asc' )
			{
				ksort($events);
			}
			else
			{
				krsort($events);
			}
		}
		
		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();
		
		// Using a gallery template, or custom?
		if( ( $block['block_template'] && $block['tpb_name'] ) || $previewMode == true )
		{
			$templateBit = $block['tpb_name'];
		}
		else
		{
			$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];
		}

		if( $config['hide_empty'] AND !count($events) )
		{
			return '';
		}
		
		$backup										= $this->memberData['time_offset'];
		$backup1									= $this->memberData['dst_in_use'];
		$backup2									= $this->registry->class_localization->offset;
		$this->memberData['time_offset']			= 0;
		$this->memberData['dst_in_use']				= 0;
		$this->registry->class_localization->offset	= 0;
		
		ob_start();
		$return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $events );
		ob_end_clean();
		
		$this->memberData['time_offset']			= $backup;
		$this->memberData['dst_in_use']				= $backup1;
		$this->registry->class_localization->offset	= $backup2;
		
		return $return;
	}
}