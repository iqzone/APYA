<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Facilitates reputation plugins
 * Last Updated: $Date: 2012-06-01 04:38:59 -0400 (Fri, 01 Jun 2012) $
 * </pre>
 *
 * @author		Joshua Williams <josh@invisionpower.com>
 * @package		IP.Board
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @link		http://www.invisionpower.com
 * @since		Wednesday 14th May 2008 14:00
 */

class classReputationCache
{
	/**
	 * Variable that determines if the reputation system is activated
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $rep_system_on;
	
	/**
	 * Error string
	 *
	 * @access	public
	 * @var		string
	 */
	public $error_message;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		$this->rep_system_on = ipsRegistry::$settings['reputation_enabled'];
		
		/* re-assign some language strings */
		if ( $this->settings['reputation_point_types'] == 'like' )
		{
			foreach( array( 'rep_description', 'member_has_x_rep' ) as $lang )
			{
				if ( ! empty( $this->lang->words[ $lang ] ) && ! empty( $this->lang->words[ $lang . '_like' ] ) )
				{
					$this->lang->words[ $lang ] = $this->lang->words[ $lang . '_like' ];
				}
			}
		}
	}
	
	/**
	 * Retuns an array for use in a join statement
	 *
	 * @access	public
	 * @param  	string		$type		Type of content, ex; Post
	 * @param	integer		$type_id	ID of the type, ex: pid
	 * @param  	string		[$app]		App for this content, by default the current application
	 * @return	array
	 */
	public function getTotalRatingJoin( $type, $type_id, $app='' )
	{
		/* Online? */
		if( ! $this->rep_system_on )
		{
			return array();
		}
		
		/* INIT */
		$app = ( $app ) ? $app : ipsRegistry::$current_application;
		
		/* Return the join array */
		return array( 'select' => 'rep_cache.rep_points, rep_cache.rep_like_cache',
					  'from'   => array( 'reputation_cache' => 'rep_cache' ),
					  'where'  => "rep_cache.app='{$app}' AND rep_cache.type='{$type}' AND rep_cache.type_id={$type_id}",
					  'type'   => 'left' );
	}
	
	/**
	 * Has this member rated this item?
	 * @param 	array $data (app, id, type, memberId )
	 * @return	boolean
	 */
	public function getCurrentMemberRating( $data )
	{
		return $this->DB->buildAndFetch( array( 'select' => '*, rep_rating as has_given_rep', 
										 		'from'   => 'reputation_index', 
										 	    'where'  => "app='" . $data['app'] . "' AND type='" . $data['type'] . "' AND type_id=" . intval( $data['id'] ) . " AND member_id=" . intval( $data['memberId'] ) ) );	
	}
	
	/**
	 * Has this member rated this item?
	 * @param 	array $data (app, id, type, memberId )
	 * @return	int
	 */
	public function getCurrentRating( $data )
	{
		$rating = $this->DB->buildAndFetch( array( 'select' => 'rep_points', 
										 		   'from'   => 'reputation_cache', 
										 	       'where'  => "app='" . $data['app'] . "' AND type='" . $data['type'] . "' AND type_id=" . intval( $data['id'] ) ) );
		
		return intval( $rating['rep_points'] );
	}
	
	/**
	 * Retuns an array for use in a join statement
	 *
	 * @access	public
	 * @param	string		$type		Type of content, ex; Post
	 * @param	integer		$type_id	ID of the type, ex: pid
	 * @param	string		[$app]		App for this content, by default the current application
	 * @return	array
	 */	
	public function getUserHasRatedJoin( $type, $type_id, $app='' )
	{
		/* Online? */
		if( ! $this->rep_system_on )
		{
			return array();
		}
		
		/* INIT */
		$app = ( $app ) ? $app : ipsRegistry::$current_application;
		
		/* Return the join array */
		return array(
						'select' => 'rep_index.rep_rating as has_given_rep',
						'from'   => array( 'reputation_index' => 'rep_index' ),
						'where'  => "rep_index.app='{$app}' AND 
						             rep_index.type='{$type}' AND 
						             rep_index.type_id={$type_id} AND 
						             rep_index.member_id=" . $this->memberData['member_id'],
						'type'   => 'left',
					);
	}
	
	/**
	 * Adds a rating to the index and updates caches
	 *
	 * @access	public
	 * @param	string		$type		Type of content, ex; Post
	 * @param	integer		$type_id	ID of the type, ex: pid
	 * @param	integer		$rating		Either 1 or -1
	 * @param	string		$message	Message associated with this rating
	 * @param	integer		$member_id	Id of the owner of the content being rated
	 * @param	string		[$app]		App for this content, by default the current application
	 * @todo 	[Future] Move forum notifications to an onRep memberSync callback
	 * @return	bool
	 */
	public function addRate( $type, $type_id, $rating, $message='', $member_id=0, $app='' )
	{
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		/* Online? */
		if ( ! $this->rep_system_on )
		{
			$this->error_message = $this->lang->words['reputation_offline'];
			return false;
		}
		
		/* INIT */
		$app       = ( $app ) ? $app : ipsRegistry::$current_application;
		$rating    = intval( $rating );
		
		if ( ! $this->memberData['member_id'] )
		{
			$this->error_message = $this->lang->words['reputation_guest'];
			return false;
		}
		
		if ( $rating != -1 && $rating != 1 )
		{
			$this->error_message = $this->lang->words['reputation_invalid'];
			return false;
		}
		
		/* Check for existing rating */
		$currentRating = $this->getCurrentMemberRating( array( 'app' => $app, 'type' => $type, 'id' => $type_id, 'memberId' => $this->memberData['member_id'] ) );
		
		/* Check the point types */
		if ( $rating == -1 && IPSMember::canRepDown( $currentRating, $this->memberData ) === false )
		{
			$this->error_message = $this->lang->words['reputation_invalid'];
			return false;
		}
		
		if ( $rating == 1 && IPSMember::canRepUp( $currentRating, $this->memberData ) === false )
		{
			$this->error_message = $this->lang->words['reputation_invalid'];
			return false;
		}
		
		/* Day Cutoff */
		$day_cutoff = time() - 86400;

		/* Check Max Positive Votes */
		if( $rating == 1 )
		{
			if ( intval( $this->memberData['g_rep_max_positive'] ) === 0 )
			{
				$this->error_message = $this->lang->words['reputation_quota_pos'];
				return false;				
			}
			
			$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as votes', 
													  'from'   => 'reputation_index', 
													  'where'  => 'member_id=' . $this->memberData['member_id'] . ' AND rep_rating=1 AND rep_date > ' . $day_cutoff )	);
					
			if ( $total['votes'] >= $this->memberData['g_rep_max_positive'] )
			{
				$this->error_message = $this->lang->words['reputation_quota_pos'];
				return false;				
			}
		}
		
		/* Check Max Negative Votes if not like mode */
		if ( $rating == -1 AND ! $this->isLikeMode() )
		{
			if ( intval( $this->memberData['g_rep_max_negative'] ) === 0 )
			{
				$this->error_message = $this->lang->words['reputation_quota_neg'];
				return false;				
			}
			
			$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as votes', 
													  'from'   => 'reputation_index', 
													  'where'  => 'member_id=' . $this->memberData['member_id'] . ' AND rep_rating=-1 AND rep_date > ' . $day_cutoff )	);
													
			if( $total['votes'] >= $this->memberData['g_rep_max_negative'] )
			{
				$this->error_message = $this->lang->words['reputation_quota_neg'];
				return false;				
			}
		}		
		
		/* If no member id was passed in, we have to query it using the config file */
		if( ! $member_id )
		{
			/* Reputation Config */
			if( is_file( IPSLib::getAppDir( $app ) . '/extensions/reputation.php' ) )
			{
				$rep_author_config = array();
				require( IPSLib::getAppDir( $app ) . '/extensions/reputation.php' );/*maybeLibHook*/
			}
			else
			{
				$this->error_message = $this->lang->words['reputation_config'];
				return false;
			}
			
			if( ! $rep_author_config[$type]['column'] || ! $rep_author_config[$type]['table'] )
			{
				$this->error_message = $this->lang->words['reputation_config'];
				return false;
			}
			
			$_col	= $rep_author_config[$type]['id_field'] ? $rep_author_config[$type]['id_field'] : $type;
			
			/* Query the content author */
			$content_author = $this->DB->buildAndFetch( array( 'select' => "{$rep_author_config[$type]['column']} as id",
															   'from'   => $rep_author_config[$type]['table'],
															   'where'  => "{$_col}={$type_id}" )	);
			
			$member_id = $content_author['id'];
		}
		
		if( ! ipsRegistry::$settings['reputation_can_self_vote'] && $member_id == $this->memberData['member_id'] )
		{
			$this->error_message = $this->lang->words['reputation_yourown'];
			return false;
		}
		
		/* Query the member group */
		if( ipsRegistry::$settings['reputation_protected_groups'] )
		{
			$member_group = $this->DB->buildAndFetch( array( 'select' => 'member_group_id', 'from' => 'members', 'where' => "member_id={$member_id}" ) );
			
			if( in_array( $member_group['member_group_id'], explode( ',', ipsRegistry::$settings['reputation_protected_groups'] ) ) )
			{
				$this->error_message = $this->lang->words['reputation_protected'];
				return false;			
			}
		}
		
		/* Build the insert array */
		$db_insert = array( 'member_id'  => $this->memberData['member_id'],
							'app'        => $app,
							'type'       => $type,
							'type_id'    => $type_id,
							'rep_date'   => time(),
							'rep_msg'    => $message,
							'rep_rating' => $rating );								
		
		/* Insert */
		if ( $currentRating )
		{
			if ( $rating == -1 && $this->isLikeMode() )
			{
				$this->DB->delete( 'reputation_index', "app='{$app}' AND type='{$type}' AND type_id={$type_id} AND member_id=".$this->memberData['member_id'] );
			}
		}
		else
		{
			$this->DB->replace( 'reputation_index', $db_insert, array( 'app', 'type', 'type_id', 'member_id' ) );
		}
		
		/* Update cache */
		$this->updateCache( $app, $type, $type_id );

		/* Get authors current rep */
		$author_points = $this->DB->buildAndFetch( array( 'select' => 'pp_reputation_points', 
														  'from'   => 'profile_portal',
														  'where'  => "pp_member_id={$member_id}" )	 );
		
		/* Figure out new rep */
		if( $currentRating['rep_rating'] == -1 )
		{
			$author_points['pp_reputation_points'] += 1;
		}
		else if ( $currentRating['rep_rating'] == 1 )
		{
			$author_points['pp_reputation_points'] -= 1;
		}
		
		/* now add on new rating if we're not like mode-ing */
		if ( ( ! $this->isLikeMode() ) || ( empty( $currentRating['rep_rating'] ) && $this->isLikeMode() ) )
		{
			$author_points['pp_reputation_points'] += $rating;
		}

		$this->DB->update( 'profile_portal', array( 'pp_reputation_points' => $author_points['pp_reputation_points'] ), "pp_member_id={$member_id}" );
		
		/* Notification */
		if ( $rating == 1 && $this->isLikeMode() && $app == 'forums' && $type == 'pid' )
		{
			/* Check for class_forums */
			if ( ! $this->registry->isClassLoaded( 'class_forums' ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
				$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
				$this->registry->strip_invisible = 0;
				$this->registry->class_forums->forumsInit();
			}
		
			$classToLoad   = IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
			$notifyLibrary = new $classToLoad( $this->registry );
			
			if ( ! $this->registry->isClassLoaded('topics') )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
				$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
			}
			
			$post        = $this->registry->getClass('topics')->getPostById( $type_id );
			
			/* Set topic data */
			$this->registry->getClass('topics')->setTopicData( $post );
			
			/* Quick check */
			if ( ! $post['author_id'] OR $post['author_id'] == $this->memberData['member_id'] )
			{
				return true;
			}
			
			$_toMember	 = IPSMember::load( $post['author_id'] );
			
			/* Set language */
			$_toMember['language'] = $_toMember['language'] == "" ? IPSLib::getDefaultLanguage() : $_toMember['language'];
			
			/* Quick permission check */
			if ( $this->registry->getClass('topics')->canView() !== true )
			{
				return true;
			}
			
			$url = $this->registry->output->buildSEOUrl( "showtopic={$post['topic_id']}&amp;view=findpost&amp;p={$post['pid']}", "publicNoSession", $post['title_seo'], 'showtopic' );
			
			IPSText::getTextClass('email')->getTemplate( "new_likes", $_toMember['language'] );
		
			IPSText::getTextClass('email')->buildMessage( array('MEMBER_NAME'	=> $this->memberData['members_display_name'],
																'SHORT_POST'	=> IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripAllTags( $post['post'] ), 300 ),
																'URL'		    => $url ) );
	
			IPSText::getTextClass('email')->subject	= sprintf(  IPSText::getTextClass('email')->subject, 
																$this->registry->output->buildSEOUrl( 'showuser=' . $this->memberData['member_id'], 'publicNoSession', $this->memberData['members_seo_name'], 'showuser' ), 
																$this->memberData['members_display_name'],
																$url,
																$this->registry->output->buildSEOUrl( "showtopic={$post['topic_id']}", "publicNoSession", $post['title_seo'], 'showtopic' ),
																IPSText::truncate( $post['topic_title'], 30 ) );
	
			$notifyLibrary->setMember( $_toMember );
			$notifyLibrary->setFrom( $this->memberData );
			$notifyLibrary->setNotificationKey( 'new_likes' );
			$notifyLibrary->setNotificationUrl( $url );
			$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
			$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
			
			try
			{
				$notifyLibrary->sendNotification();
			}
			catch( Exception $e ){}
		}
		
		return true;		
	}
	
	/**
	 * Returns an array of reputation information based on the points passed in
	 *
	 * @access	public
	 * @param	integer		$points		Number of points to base the repuation information on
	 * @return	array 					'text' and 'image'
	 */
	public function getReputation( $points )
	{
		/* INIT */
		$cache  = ipsRegistry::cache()->getCache( 'reputation_levels' );
		$cache  = is_array( $cache ) && count( $cache ) ? $cache : array();
		$points = intval( $points );

		if( count($cache) AND is_array($cache) )
		{
			foreach( $cache as $k => $r )
			{
				if( $r['level_points'] == 0 )
				{
					if( $points >= 0 && $points < intval( $cache[ $k -1 ]['level_points'] ) )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
					}
					else if( $points <= 0 && $points > intval( $cache[ $k + 1 ]['level_points'] ) )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
					}
					else if( $points == 0 )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
					}
				}
				else if( $r['level_points'] > 0 )
				{
					if( $points >= intval( $r['level_points'] ) )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
					}
				}
				else
				{
					if( $points <= intval( $r['level_points'] ) && $points > intval( $cache[ $k + 1 ]['level_points'] ) )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );	
					}
				}
			}
		}
		
		/* Return the lowest rep, if we're still here */
		$r = array_pop( $cache );
		return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
	}
	
	/**
	 * Get 'like' formatted for this item
	 * 
	 * @param	array	Data array 	( id, type, app )
	 */
	public function getLikeFormatted( $item )
	{
		$data   = array( 'names' => array(), 'count' => 0, 'formatted' => '', 'iLike' => false );
		$max	= 0;
		
		/* Like system enabled? */
		if ( $this->settings['reputation_point_types'] != 'like' )
		{
			return $data;
		}
		
		$cache  = $this->getLikeData( $item );
		
		/* We had the total count set from cache.get */
		if ( isset($cache['count']) AND $cache['count'] )
		{
			/* fetch data */
			if ( $this->memberData['member_id'] AND in_array( $this->memberData['member_id'], $cache['memberIds'] ) )
			{
				/* Now mix up the data */
				$data['names'][] = array( 'name' => $this->lang->words['post_like_moi'], 'seo' => $this->memberData['members_seo_name'], 'id' => $this->memberData['member_id'] );
				
				/* Flag as me */
				$data['iLike'] = true;
			}
			
			if ( is_array( $cache['memberNames'] ) )
			{
				$i   = 1;
				$max = 3;
				
				foreach( $cache['memberNames'] as $mid => $mdata )
				{
					$_last = ( $i == count( $cache['memberNames'] ) || $i == $max ) ? 1 : 0;
					
					/* Is this you? */
					if ( $mid == $this->memberData['member_id'] )
					{
						continue;
					}
					
					/* Push it on */
					$data['names'][] = array( 'name' => $mdata['n'], 'seo' => $mdata['s'], 'id' => $mid, 'last' => $_last );
					
					$i++;
					
					if ( $i > $max )
					{
						/* Done thanks */
						break;
					}
				}
			}
		}
		
		/* Finish off */
		$data['totalCount']  = isset($cache['count']) ? $cache['count'] : 0;
		$data['othersCount'] = isset($cache['count']) ? ( ( $cache['count'] > $max ) ? $cache['count'] - $max : 0 ) : 0;
		$data['formatted']   = $this->formatLikeNameString( $data, $item );
		
		return $data;
	}
	
	/**
	 * Gets 'like' data for this item
	 *
	 * @param	array	( id, type, app )
	 * @return	array
	 *
	 */
	public function getRepPoints( $data )
	{
		if ( ! isset( $data['rep_points'] ) || $data['rep_points'] == '' )
		{
			$data['rep_points'] = $this->updateCache( $data['app'], $data['type'], $data['type_id'] );
		}
	
		return intval( $data['rep_points'] );
	}
	
	/**
	 * Gets 'like' data for this item
	 * 
	 * @param	array	( id, type, app )
	 * @return	array
	 * 
	 */
	public function getLikeData( $data )
	{
		$store   = array( 'cache_data' => array() );
		$expired = false;
		
		if ( IPSLib::isSerialized( $data['rep_like_cache'] ) )
		{ 
			$store = unserialize( $data['rep_like_cache'] );
			
			if ( empty( $store['cache_expire'] ) OR time() >= $store['cache_expire'] )
			{
				$expired = true;
				$store   = array();
			}
		}
		
		if ( ! isset( $data['rep_like_cache'] ) OR $expired === true )
		{
			unset( $data['rep_like_cache'] );
			
			$store = $this->getLikeRawData( $data );
		
			$this->DB->replace( 'reputation_cache', array( 'app'	        => $data['app'],
														   'type'           => $data['type'],
														   'type_id'        => $data['id'],
														   'rep_points'     => intval( $store['cache_data']['count'] ),
														   'cache_date'     => time(),
														   'rep_like_cache' => serialize( $store ) ), array( 'app', 'type', 'type_id' ) );
		}
		
		return $store['cache_data'];
	}
	
	/**
	 * Get the like data from the DB (no cache)
	 * 
	 * @param	array		$data		Like data ( id, type, app )
	 * @return	@e array	Cache data
	 */
	public function getLikeRawData( $data )
	{
		/* Get data */
		$store = array( 'cache_data' => array(), 'cache_expire' => time() + 86400 );

		$this->DB->build( array( 'select'	=> 'r.member_id, r.rep_rating',
								 'from'		=> array( 'reputation_index' => 'r' ),
								 'where'	=> "r.app='" . $data['app'] . "' AND r.type='" . $data['type'] . "' AND r.type_id='" . $data['id'] . "'",
								 //'order'	=> 'r.rep_date',
								 'add_join' => array( array( 'select' => 'm.members_display_name, m.members_seo_name',
															 'from'   => array( 'members' => 'm' ),
															 'where'  => 'm.member_id=r.member_id',
															 'type'   => 'left' ) ) ) );
				
		$q = $this->DB->execute();
		
		while ( $row = $this->DB->fetch( $q ) )
		{
			if ( $row['rep_rating'] == 1 )
			{
				$data['count']++;
				
				if ( $data['count'] <= 5 )
				{
					$data['memberNames'][ $row['member_id'] ] = array( 'n' => $row['members_display_name'], 's' => $row['members_seo_name'] );
				}
				
				if ( $data['count'] <= 500 )
				{
					$data['memberIds'][] = $row['member_id'];
				}
			}
		}

		/* Fetch and store */
		if ( $data['count'] > 0 )
		{
			$store = array( 'cache_data'   => $data,
						    'cache_expire' => time() + 86400 );
		}
		
		return $store;
	}
	
	/**
	 * Formats the Bob, Bill, Joe and 2038 Others Hate You
	 * 
	 * @param	array	$data
	 * @param	array	$item	Data (id, type, app)
	 * @return	string
	 */
	public function formatLikeNameString( array $data, array $item )
	{
		$langString  = '';
		$seeMoreLink = '#';
	
		if ( ! is_array( $data['names'] ) OR ! count( $data['names'] ) )
		{
			return false;
		}
		/* Format up the names */
		$i      = 0;
		$_names = array();
		
		foreach( $data['names'] as $name )
		{
			if ( $this->memberData['member_id'] AND ( $this->memberData['member_id'] == $name['id'] ) )
			{
				$_names[$i] = $name['name'];
			}
			else
			{
				$_names[$i] = IPSMember::makeProfileLink($name['name'], $name['id'], $name['seo'] );
			}
			
			$i++;
		}

		/* More than one? */
		if ( $data['totalCount'] > 1 )
		{
			/* Joe and Matt love you */
			if ( $data['totalCount'] == 2 )
			{
				$_n = $_names[0] . ' ' . $this->lang->words['post_like_and'] . ' ' . $_names[1];
				
				$langString = sprintf( $this->lang->words['post_like_formatted_many'], $_n );
			}
			/* Joe, Matt and Mike love you more */
			else if ( $data['totalCount'] == 3 )
			{
				$_n = $_names[0] . ', ' . $_names[1] . ' ' . $this->lang->words['fave_and'] . ' ' . $_names[2];
				
				$langString = sprintf( $this->lang->words['post_like_formatted_many'], $_n );
			}
			/* Joe, Matt, Mike and 1 more love you */
			else if ( $data['totalCount'] == 4 )
			{
				$_n = $_names[0] . ', ' . $_names[1] . ', ' . $_names[2];
				
				$langString = sprintf( $this->lang->words['post_like_formatted_one_more'], $_n, $seeMoreLink, $item['id'], $item['type'], $item['app'] );
			}
			/* Joe, Matt, Mike and 5 more are indifferent to your redonkulous comments */
			else
			{
				$_n = $_names[0] . ', ' . $_names[1] . ', ' . $_names[2];
				
				$langString = sprintf( $this->lang->words['post_like_formatted_more'], $_n, $seeMoreLink, $item['id'], $item['type'], $item['app'], $data['othersCount'] );
			}
		}
		else
		{
			/* Just the one and it might be you! */	
			if ( $data['names'][0]['id'] == $this->memberData['member_id'] )
			{
				$langString = $this->lang->words['post_like_formatted_me'];
			}
			else
			{
				$langString = sprintf( $this->lang->words['post_like_formatted_one'], $_names[0] );
			}
		}

		return $langString;
	}
	
	/**
	 * Get data based on a relationship ID
	 *
	 * @param	array 	$data (id, type, app)
	 * @return	mixed	Array of like data OR null
	 */
	public function getDataByRelationshipId( $data )
	{
		/* Init */
		$mids	 = array();
		$members = array();
		$rows    = array();
		$where   = ( ! empty( $data['rating'] ) ) ? " AND rep_rating=" . $data['rating'] : '';
		
		/* Fetch data */	
		$this->DB->build( array( 'select' => '*',
					   			 'from'   => 'reputation_index',
								 'where'  => 'app=\'' . $data['app'] . "' AND type='" . $data['type'] . "' AND type_id=" . intval( $data['id'] ) . $where,
								 'order'  => 'rep_date DESC',
								 'limit'  => array( 0, 250 ) ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$return[ $row['member_id'] ] = $row;
			$mids[ $row['member_id'] ]   = intval( $row['member_id'] );
		}
		
		/* Just the one? */
		if ( count( $mids ) )
		{
			$members = IPSMember::load( $mids, 'all' );
		
			foreach( $members as $i => $d )
			{
				$_m = IPSMember::buildProfilePhoto( $d );
				$return[ $i ] = array_merge( (array) $_m, (array) $return[ $i ] );
			}
		}
		
		return ( is_array( $return ) ) ? $return : null;
	}
	
	/**
	 * Handles updating and creating new caches
	 *
	 * @access	private
	 * @param	string	$app		App for this content
	 * @param	string	$type		Type of content, ex; Post
	 * @param	integer	$type_id	ID of the type, ex: pid
	 * @return	@e void
	 */
	public function updateCache( $app, $type, $type_id )
	{		
		/* Update type cache */
		$data = $this->DB->buildAndFetch( array( 'select' => 'SUM(rep_rating) as sum, COUNT(*) as count', 
												 'from'   => 'reputation_index', 
												 'where'  => "app='{$app}' AND type='$type' AND type_id='$type_id'" )	);
													
		/* Fetch LIKE data */
		$store = $this->getLikeRawData( array( 'id' => $type_id, 'type' => $type, 'app' => $app ) );
		
		/* Update cache */
		$this->DB->replace( 'reputation_cache', array( 'app'	        => $app,
													   'type'           => $type,
													   'type_id'        => $type_id,
													   'rep_points'		=> intval($data['sum']),
													   'cache_date'     => time(),
													   'rep_like_cache' => serialize( $store ) ), array( 'app', 'type', 'type_id' ) );
		
		/* Update totals */
		$idKey   = md5( $app . ';' . $type . ';' . $type_id );
		$typeKey = md5( $app . ';' . $type );
		
		$this->DB->replace( 'reputation_totals', array( 'rt_key'      => $idKey,
														'rt_app_type' => $typeKey,
														'rt_type_id'  => $type_id,
														'rt_total'    => intval( $data['sum'] ) ), array( 'rt_key' ) );
		return $data['sum'];
	}
	
	/**
	 * Is this in like mode?
	 */
	public function isLikeMode()
	{
		return ( $this->settings['reputation_point_types'] == 'like' ) ? true : false;
	}
}