<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Static Classes for IP.Board 3
 *
 * These classes are not required as objects. We have grouped
 * together several singletons to prevent multiple file loads
 * Last Updated: $Date: 2012-06-08 12:17:17 -0400 (Fri, 08 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		12th March 2002
 * @version		$Revision: 10899 $
 *
 * @author	Matt
 */


/* Non setting settings defaults.
 * Do not edit here, edit them in your conf_global.php */
$_ipsPowerSettings = array( 'status_sidebar_show_x'			=> 5,
							'ipb_disable_group_psformat'	=> 0,
							'tags_max_truncated_len'		=> 35,
							'max_bbcodes_per_post'			=> 500,
							'postpage_contents'				=> '5,10,15,20,25,30,35,40',
							'topicpage_contents'			=> '5,10,15,20,25,30,35,40',
							'member_photo_crop'				=> 100,
							'posting_allow_rte'				=> 1, # Will look to redirect old editor methods to new and will remove this (legacy compatibility)
							'like_notifications_limit'		=> 1000,
							'actidx_override'				=> 0,
							'signature_line_length'			=> 200,
							'show_x_page_link'				=> 2,
							'post_order_column'				=> 'pid', // Other valid values: 'post_date'
							);

class IPSAdCodeDefault
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $memberData;
	public $cache;
	public $caches;
	
	/**
	 * Ad code to overwrite the global header code
	 *
	 * @var		string
	 */
	public $headerCode = '';
	
	/**
	 * Ad code to overwrite the global footer code
	 *
	 * @var		string
	 */
	public $footerCode = '';
	
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Basic functionality
	 */
	public function userCanViewAds()
	{
		return false;
	}
}

/**
 * Deletion Log Class
 */
class IPSDeleteLog
{
	/**
	 * Add entry to the delete log
	 *
	 * @param	int			Object ID
	 * @param	string		Object Type
	 * @param	string		Reason for addition
	 * @param	array		Array of member data of user adding log entry
	 */
	public static function addEntry( $id, $type, $reason, $memberData )
	{
		if ( $id AND $type AND is_array( $memberData ) AND $memberData['member_id'] )
		{
			ipsRegistry::DB()->replace( 'core_soft_delete_log', array( 'sdl_obj_id'        => $id,
																	   'sdl_obj_key'       => $type,
																	   'sdl_obj_reason'    => $reason,
																	   'sdl_obj_member_id' => $memberData['member_id'],
																	   'sdl_obj_date'      => time(),
																	   'sdl_locked'		   => 0 ), array( 'sdl_obj_id', 'sdl_obj_key' ) );
																	   
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Remove entres to the delete log
	 *
	 * @param	array		Object IDs
	 * @param	string		Object Type
	 * @param	boolean		Force deletion (used when deleting topics/posts/etc)
	 */
	public static function removeEntries( $ids, $type, $forceDelete=false )
	{
		if ( is_array( $ids ) AND count( $ids ) AND $type )
		{
			$ids   = IPSLib::cleanIntArray( $ids );
			
			/* if we're not a global mod, then lock these not remove unless we're deleting stuff */
			if ( ! ipsRegistry::member()->getProperty('g_is_supmod') AND $forceDelete === false )
			{
				ipsRegistry::DB()->update( 'core_soft_delete_log', array( 'sdl_locked' => 1 ), 'sdl_obj_id IN (' . implode( ',', $ids ) . ') AND sdl_obj_key=\'' . $type . '\'' );
			}
			else
			{
				ipsRegistry::DB()->delete( 'core_soft_delete_log', 'sdl_obj_id IN (' . implode( ',', $ids ) . ') AND sdl_obj_key=\'' . $type . '\'' );
			}
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Fetch entries from the delete log
	 *
	 * @param	array		Object IDs
	 * @param	string		Object Type
	 * @param	boolean		Parse Member Data
	 */
	public static function fetchEntries( $ids, $type, $parseMember=true )
	{
		$return = array();
		
		if ( is_array( $ids ) AND count( $ids ) AND $type )
		{
			$ids = IPSLib::cleanIntArray( $ids );
			
			ipsRegistry::DB()->build( array( 'select'   => 'l.*',
											 'from'     => array( 'core_soft_delete_log' => 'l' ),
											 'where'    => 'sdl_obj_id IN (' . implode( ',', $ids ) . ') AND sdl_obj_key=\'' . $type . '\'',
											 'add_join' => array( array( 'select' => 'm.*',
											 							 'from'   => array( 'members' => 'm' ),
											 							 'where'  => 'l.sdl_obj_member_id=m.member_id' ),
											 					  array( 'select' => 'p.*',
											 					  		 'from'	  => array( 'profile_portal' => 'p' ),
											 					  		 'where'  => 'l.sdl_obj_member_id=p.pp_member_id' ) ) ) );
											 					  		 
			$i = ipsRegistry::DB()->execute();
											 					  		 
			while( $row = ipsRegistry::DB()->fetch( $i ) )
			{
				if ( $parseMember )
				{
					$row['member'] = IPSMember::buildDisplayData( $row );
				}
				
				$return[ $row['sdl_obj_id'] ] = $row;
			}
			
			return $return;
		}
		
		return array();
	}
}

class IPSContentCache
{
	/**
	 * Keep track of what tables are linked to which key
	 *
	 * @var		array
	 */
	protected static $_tables = array( 'post' => 'content_cache_posts',
							  		 'sig'  => 'content_cache_sigs' );

	/**
	 * Keep track of what settings are linked to which key
	 *
	 * @var		array
	 */
	protected static $_settings = array( 'post' => 'cc_posts',
							    	   'sig'  => 'cc_sigs' );
							
	/**
	 * Check to see whether content caching is enabled
	 *
	 * @return	boolean
	 */
	static public function isEnabled()
	{
		return ( ipsRegistry::$settings['cc_on'] AND ( ipsRegistry::$settings['cc_posts'] OR ipsRegistry::$settings['cc_sigs'] ) ) ? TRUE : FALSE;
	}

	/**
	 * Check to see whether we have a valid type
	 *
	 * @param	string		Content Type (post/sig/etc)
	 * @return	boolean
	 */
	static public function isValidType( $type )
	{
		return isset( self::$_tables[ $type ] ) ? TRUE : FALSE;
	}
	
	/**
	 * Fetch correct table name based on type
	 * Assumes isValidType has been run
	 *
	 * @param	string		Content Type (post/sig/etc)
	 * @return	boolean
	 */
	static public function fetchTableName( $type )
	{
		return self::$_tables[ $type ];
	}
	
	/**
	 * Fetch correct setting value based on type
	 * Assumes isValidType has been run
	 *
	 * @param	string		Content Type (post/sig/etc)
	 * @return	boolean
	 */
	static public function fetchSettingValue( $type )
	{
		return ipsRegistry::$settings[ self::$_settings[ $type ] ];
	}

	/**
	 * Add data to the cache
	 *
	 * @param	int			Content ID
	 * @param	string		Content Type (post/sig/etc)
	 * @param	string		Content
	 * @param	boolean		Already had preDb/preDisplay run. It FALSE, assumed preDb has been run and no HTML will be parsed but smilies and bbcode will be
	 * @return	bool
	 */
	static public function update( $id, $type, $content, $parsed=TRUE )
	{
		/* Enabled?? */
		if ( ! self::isEnabled() )
		{
			return FALSE;
		}
		
		/* Valid type?? */
		if ( ! self::isValidType( $type ) )
		{
			return FALSE;
		}
		
		/* Search engine? */
		if ( ipsRegistry::member()->is_not_human === TRUE )
		{
			return FALSE;
		}
		
		/* Init */
		$parsingSection = 'topics';
		
		if ( $content AND $parsed !== TRUE )
		{
			/* What are we parsing? */
			switch( $type )
			{
				case 'post':
					$parsingSection = 'topics';
				break;
				case 'sig':
					$parsingSection = 'signatures';
				break;
			}
					
			/* Set up parser */
			IPSText::getTextClass( 'bbcode' )->parse_smilies         = 1;
			IPSText::getTextClass( 'bbcode' )->parse_html    	     = 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br		     = 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode    	     = 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section	     = $parsingSection;
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup		 = ipsRegistry::member()->getProperty( 'member_group_id' );
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others = ipsRegistry::member()->getProperty( 'mgroup_others' );

			/* Format */
			$content = IPSText::getTextClass( 'bbcode' )->preDisplayParse( IPSText::getTextClass( 'bbcode' )->preDbParse( $content ) );
		}
		
		if ( $content )
		{
			ipsRegistry::DB()->setDataType( 'cache_content', 'string' );
												
			ipsRegistry::DB()->replace( self::fetchTableName( $type ), array( 'cache_content_id' => $id,
																			  'cache_content'    => $content,
																			  'cache_updated'    => time() ), array( 'cache_content_id' ) );
		}
		else
		{
			/* No content, then drop it */
			self::drop( $type, $id );
		}
		
		return TRUE;
	}
	
	/**
	 * Drop data from the cache
	 * If no ID is passed, it'll drop all caches for the supplied 'type'
	 *
	 * @param	string		Content Type (post/sig/etc)
	 * @param	int/array	[Content ID]
	 * @return	bool
	 */
	static public function drop( $type, $id=0 )
	{
		if ( ! self::isEnabled() )
		{
			return FALSE;
		}
		
		/* Valid type?? */
		if ( ! self::isValidType( $type ) )
		{
			return FALSE;
		}
		
		if ( $id )
		{
			if ( is_array( $id ) )
			{
				$id = implode( ',', $id );
			}
			
			ipsRegistry::DB()->delete( self::fetchTableName( $type ), "cache_content_id IN (" . $id . ")" );
		}
		else
		{
			ipsRegistry::DB()->delete( self::fetchTableName( $type ) );
		}
		
		return TRUE;
	}
	
	/**
	 * Remove all "type" data from the cache
	 *
	 * @param	string		[Content Type (post/sig/etc)]
	 * @return	int			Number of rows affected
	 */
	static public function truncate( $type='' )
	{
		if ( ! self::isEnabled() )
		{
			return 0;
		}
		
		/* Valid type?? */
		if ( $type AND ! self::isValidType( $type ) )
		{
			return 0;
		}
		
		$affected = 0;
		
		if ( $type )
		{
			$count = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => self::fetchTableName( $type ) ) );
			
			ipsRegistry::DB()->delete( self::fetchTableName( $type ) );
			$affected = $count['total']; // ipsRegistry::DB()->getAffectedRows(); - With no where clause, mysql_affected_rows always returns 0
		}
		else
		{
			foreach( self::$_tables as $type => $name )
			{
				$count = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => $name ) );
				ipsRegistry::DB()->delete( $name );
				$affected += $count['total']; // $affected += ipsRegistry::DB()->getAffectedRows(); - With no where clause, mysql_affected_rows always returns 0
			}
		}
		
		return intval( $affected );
	}
	
	/**
	 * Count the number of cached items
	 *
	 * @param	string		[Content Type (post/sig/etc)]
	 * @return	int			Combined number of items
	 */
	static public function count( $type='' )
	{
		if ( ! self::isEnabled() )
		{
			return FALSE;
		}
		
		/* Valid type?? */
		if ( $type AND ! self::isValidType( $type ) )
		{
			return FALSE;
		}
		
		$count = 0;
		
		if ( $type )
		{
			$row   = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'COUNT(*) as c', 'from' => self::fetchTableName( $type ) ) );
			$count = intval( $row['c'] );
		}
		else
		{
			foreach( self::$_tables as $type => $name )
			{
				$row    = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'COUNT(*) as c', 'from' => $name ) );
				$count += intval( $row['c'] );
			}
		}
		
		return intval( $count );
	}
	
	/**
	 * Prune items back to X days
	 *
	 * If no type is supplied, all types are pruned
	 *
	 * @param	string		[Content Type (post/sig/etc)]
	 * @return	int			Number of rows affected
	 */
	static public function prune( $type='' )
	{
		if ( ! self::isEnabled() )
		{
			return FALSE;
		}
		
		/* Valid type?? */
		if ( $type AND ! self::isValidType( $type ) )
		{
			return FALSE;
		}
		
		$affected = 0;
		
		if ( $type )
		{
			$time = time() - ( self::fetchSettingValue( $type ) * 86400 );
			
			ipsRegistry::DB()->delete( self::fetchTableName( $type ), "cache_updated <" . $time );
			$affected = ipsRegistry::DB()->getAffectedRows();
		}
		else
		{
			foreach( self::$_tables as $type => $name )
			{
				$time = time() - ( self::fetchSettingValue( $type ) * 86400 );
				
				ipsRegistry::DB()->delete( self::fetchTableName( $type ), "cache_updated <" . $time );
				$affected += ipsRegistry::DB()->getAffectedRows();
			}
		}
		
		return intval( ipsRegistry::DB()->getAffectedRows() );
	}
	
	/**
	 * Fetch table join
	 *
	 * Cheap way of grabbing the join on the cache table so that your code
	 * doesn't have to check for whether we're using the cache or not, etc
	 *
	 * @param	string	Content Type (post/sig/etc)
	 * @param	string	Join field (eg 'p.pid')
	 * @param	string	[Table alias - default 'cca']
	 * @param	string	[Table join type - default 'left']
	 * @param	string	[Custom select so that fields can be aliased, etc]
	 * @return	bool
	 */
	static public function join( $type, $joinField, $alias='cca', $joinType='left', $customSelect='' )
	{
		if ( ! self::isEnabled() )
		{
			return FALSE;
		}
		
		/* Valid type?? */
		if ( ! self::isValidType( $type ) )
		{
			return FALSE;
		}
		
		return array( 'select' => ( $customSelect ) ? $customSelect : $alias.'.*',
					  'from'   => array( self::fetchTableName( $type ) => $alias ),
					  'where'  => $alias . '.cache_content_id=' . $joinField,
					  'type'   => $joinType );
	}
}
	
	
/**
 * Experimental class for storing options as bitwise
 *
 * @author	Matt
 */
class IPSBWOptions
{
	/**
	 * Convert a bit field into an array of options
	 *
	 * @param	int		Bitwise option
	 * @param	string	Type of options to decipher (user / groups / etc)
	 * @param	string	App
	 * @return	array
	 * <code>$options = IPSBWOptions::thawOptions( 18, 'user', 'forums' );</code>
	 */
	static public function thaw( $bitfield, $type, $app='global' )
	{
		/* INIT */
		$bitfield = intval($bitfield);
		$array    = array();
		
		/* Generate bitwise array */
		$bitArray = self::_getBitWiseArray( $type, $app );
		
		if ( ! $bitArray OR ! count( $bitArray ) )
		{
			return array();
		}
		
		/* Build options */
		foreach( $bitArray as $key => $bitvalue )
		{
			if ( $bitfield & intval( $bitvalue ) )
			{
				$array[ $key ] = 1;
			}
			else
			{
				$array[ $key ] = 0;
			}
		}
		
		return $array;
	}
	
	/**
	 * Build an SQL query bit
	 *
	 * @param	string		Field (field name as assigned by thaw)
	 * @param	string		SQL field
	 * @param	string		Type (members, groups, etc )
	 * @param	string		App (global, forums, etc)
	 * @param	string		Type of SQL query (add/remove/has)
	 * @return	string		Formatted SQL field
	 */
	static public function sql( $bitField, $sqlField, $type, $app='global', $sql='has' )
	{
		/* Generate int sign */
		switch( $sql )
		{
			default:
			case 'has':
				$_sign = '&';
			break;
			case 'remove':
				$_sign = '-';
			break;
			case 'add':
				$_sign = '+';
			break;
		}
		
		/* Generate bitwise array */
		$bitArray = self::_getBitWiseArray( $type, $app );

		/* Do it.. .*/
		if ( in_array( $bitField, array_keys( $bitArray ) ) )
		{
			return '( ' . $sqlField . ' ' . $_sign . ' ' . $bitArray[ $bitField ] . ' ) != 0';
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Freeze options
	 * Converts an array of options array( 'key' => 0 ... ) into an int for saving in a DB field
	 *
	 * @param	array 		Array of key => values to save
	 * @param	string		Type of options to save
	 * @param	string		App
	 * @return	int
	 */
	static public function freeze( $toSave, $type, $app='global' )
	{
		/* INIT */
		$int = 0;
		
		/* Generate bitwise array */
		$bitArray = self::_getBitWiseArray( $type, $app );
		
		if ( ! $bitArray OR ! count( $bitArray ) )
		{
			return 0;
		}
		
		foreach( $bitArray as $key => $value )
		{
			if ( isset( $toSave[ $key ] ) )
			{
				if ( $toSave[ $key ] == 1 )
				{
					$int += $value;
				}
			}
		}
		
		return intval( $int );
	}

	/**
	 * Fetch and build the bitwise array
	 *
	 * @param	string		Array key to return
	 * @return	array
	 */
	static protected function _getBitWiseArray( $type, $app )
	{
		$bitArray   = array();
		$allOptions = ipsRegistry::fetchBitWiseOptions( $app );
		
		if ( is_array( $allOptions ) )
		{
			if ( isset( $allOptions[ $type ] ) AND is_array( $allOptions[ $type ] ) )
			{
				$n = 1;
				
				foreach( $allOptions[ $type ] as $key )
				{
					$bitArray[ $key ] = $n;
					
					$n *= 2;
				}
			}
		}
		
		return $bitArray;
	}
}

/**
 * Time Class
 *
 * Class for handling timestamps
 *
 */
class IPSTime
{
   /**
    * Current timestamp
    *
    * @var		integer
    */
	protected static $timestamp	= IPS_UNIX_TIME_NOW;

   /**
    * Number of seconds in a minute
    *
    * @var		integer
    */
	protected static $minute		= 60;

   /**
    * Number of seconds in a hour
    *
    * @var		integer
    */
	protected static $hour		= 3600;

   /**
    * Number of seconds in a day
    *
    * @var		integer
    */
	protected static $day			= 86400;

   /**
    * Number of seconds in a week
    *
    * @var		integer
    */
	protected static $week		= 604800;

   /**
    * Number of seconds in a year
    *
    * @var		integer
    */
	protected static $year		= 220752000;

   /**
    * Months with 31 days
    *
    * @var		array
    */
	protected static $months_31 = array( 01, 03, 05, 07, 08, 10, 12 );
	
   /**
    * Months with 30 days
    *
    * @var		array
    */
	protected static $months_30 = array( 04, 06, 09, 11 );

	/**
	 * time_class::dmy_format()
	 * Generates a time stamp for the day/month/year
	 *
	 * @param	integer	[$ts]	Timestamp to format, self::$timestamp used if none specified
	 * @return	@e void
	 */
	static public function dmy_format( $ts=0 )
	{
		/* Set the timestamp */
		$_ts = ( $ts ) ? $ts : self::$timestamp;

		/* Break it into dmy format */
		$_ts = date( "m,d,Y", $_ts );
		$_ts = explode( ",", $_ts );

		/* Return the timestamp */
		return mktime( 0, 0, 0, $_ts[0], $_ts[1], $_ts[2] );
	}

	/**
	 * time_class::time_ago()
	 * Returns how long ago the specified time stamp was
	 *
	 * @param	integer	$ts	Timestamp to format
	 * @return	@e void
	 */
	static public function time_ago( $ts )
	{
		if( $ts == time() )
		{
			return '--';
		}
		if( $ts < 60 )
		{
			$plural = ( $ts == 1 ) ? '' : 's';
			return sprintf( "%0d", $ts ) . " second$plural";
		}
		else if( $ts < self::$hour )
		{
			$plural = ( sprintf("%0d", ( $ts / self::$minute ) ) == 1 ) ? '' : 's';
			return sprintf("%0d", ( $ts / self::$minute ) ) . " minute$plural";
		}
		else if( $ts < self::$day )
		{
			$plural = ( sprintf("%0d", ( $ts / self::$hour ) ) == 1 ) ? '' : 's';
			return sprintf("%0d", ( $ts / self::$hour ) ) . " hour$plural";
		}
		else
		{
			$plural = ( sprintf("%0d", ( $ts / self::$day ) ) == 1 ) ? '' : 's';
			return sprintf("%0d", ( $ts / self::$day ) ) . " day$plural";
		}
	}

	/**
	 * Set the timestamp
	 *
	 * @param	int		New timestamp
	 * @return	@e void
	 */
	static public function setTimestamp( $time )
	{
		self::$timestamp = $time;
	}
	
	/**
	 * Get the timestamp
	 *
	 * @return	int		Timestamp
	 */
	static public function getTimestamp()
	{
		return self::$timestamp;
	}
	
	/**
	 * time_class::add_minutes()
	 * Adds the specified number of minutes to the timestamp
	 *
	 * @param	integer	[$num]	Number of minutes to add, 1 by default
	 * @return	@e void
	 */
	static public function add_minutes( $num=1 )
	{
		self::$timestamp += self::$minute * $num;
	}

	/**
	 * time_class::add_hours()
	 * Adds the specified number of hours to the timestamp
	 *
	 * @param	integer	[$num]	Number of hours to add, 1 by default
	 * @return	@e void
	 */
	static public function add_hours( $num=1 )
	{
		self::$timestamp += self::$hour * $num;
	}

	/**
	 * time_class::add_days()
	 * Adds the specified number of days to the timestamp
	 *
	 * @param	integer	[$num]	Number of days to add, 1 by default
	 * @return	@e void
	 */
	static public function add_days( $num=1 )
	{
		self::$timestamp += self::$day * $num;
	}

	/**
	 * time_class::add_weeks()
	 * Adds the specified number of weeks to the timestamp
	 *
	 * @param	integer	[$num]	Number of weeks to add, 1 by default
	 * @return	@e void
	 */
	static public function add_weeks( $num=1 )
	{
		self::$timestamp += self::$week * $num;
	}

	/**
	 * time_class::add_month()
	 * Adds a single month to the current timestamp, takes into account leap years
	 *
	 * @return	@e void
	 */
	static public function add_month()
	{
		$daysInMonth = date( 't', self::$timestamp );
		
		self::$timestamp += self::add_days( intval( $daysInMonth ) );
	}

	/**
	 * time_class::add_months()
	 * Adds the specified number of months to the timestamp
	 *
	 * @param	integer	$num	Number of months to add, 1 by default
	 * @return	@e void
	 */
	static public function add_months( $num=1 )
	{
		for( $i = 0; $i < $num; $i++ )
		{
			self::add_month();
		}
	}

	/**
	 * time_class::add_years()
	 * Adds the specified number of years to the timestamp
	 *
	 * @param	integer	$num	Number of years to add, 1 by default
	 * @return	@e void
	 */
	static public function add_years( $num=1 )
	{
		for( $i = 0; $i < $num; $i++ )
		{
			self::add_months( 12 );
			self::remove_days( 1 );
		}
	}

	/**
	 * time_class::remove_days()
	 * Removes the specified number of days to the timestamp
	 *
	 * @param	integer	[$num]	Number of days to remove, 1 by default
	 * @return	@e void
	 */
	static public function remove_days( $num=1 )
	{
		self::$timestamp -= self::$day * $num;
	}

	/**
	 * Convert unix timestamp into: (no leading zeros)
	 * array( 'day' => x, 'month' => x, 'year' => x, 'hour' => x, 'minute' => x );
	 * Written into separate static public function to allow for timezone to be used easily
	 *
	 * @param	integer	[$unix]	Timestamp
	 * @return	array 	Date parts
	 */
    static public function unixstamp_to_human( $unix=0 )
    {
    	$tmp = gmdate( 'j,n,Y,G,i', $unix );

    	list( $day, $month, $year, $hour, $min ) = explode( ',', $tmp );

    	return array( 'day'    => $day,
    				  'month'  => $month,
    				  'year'   => $year,
    				  'hour'   => $hour,
    				  'minute' => $min );
    }

	/**
	 * Convert unix timestamp into mmddyyyy
	 *
	 * @param	integer	[$unix]	Timestamp
	 * @param	string	[$sep]	Separator
	 * @return	string	mm/dd/yyyy
	 */
    static public function unixstamp_to_mmddyyyy( $unix=0, $sep='/' )
    {
    	if ( ! $unix )
    	{
    		return "";
    	}

    	$date = self::unixstamp_to_human( $unix );

    	return sprintf("%02d{$sep}%02d{$sep}%04d", $date['month'], $date['day'], $date['year'] );
    }

	/**
	 * Convert mmddyyyy into unix timestamp
	 *
	 * @param	string	[$date]			Date
	 * @param	string	[$sep]			Separator
	 * @param	bool	[$checkdate]	Whether to validate date or not
	 * @return	integer	Timestamp
	 */
    static public function mmddyyyy_to_unixstamp( $date='', $sep='/', $checkdate=true )
    {
    	if ( ! $date )
    	{
    		return "";
    	}

    	list( $month, $day, $year ) = explode( $sep, $date );

    	if ( $checkdate )
    	{
			if ( ! checkdate( $month, $day, $year ) )
			{
				return "";
			}
    	}

    	return self::human_to_unixstamp( $day, $month, $year, 0, 0 );
    }

	/**
	 * Wrapper for gmmktime (separated for timezone management)
	 *
	 * @param	integer	$day	Day
	 * @param	integer	$month	Month
	 * @param	integer $year	Year
	 * @param	integer	$hour	Hour
	 * @param	integer	$minute	Minute
	 * @return	integer	Timestamp
	 */
    static public function human_to_unixstamp( $day, $month, $year, $hour, $minute )
    {
    	return gmmktime( intval($hour), intval($minute), 0, intval($month), intval($day), intval($year) );
    }

    /**
	 * My gmmktime() - PHP func seems buggy
	 *
	 * @param	integer	$hour	Hour
	 * @param	integer	$min	Minute
	 * @param	integer $sec	Second
	 * @param	integer $month	Month
	 * @param	integer $day	Day
	 * @param	integer $year	Year
	 * @return	integer	Timestamp
	 * @since	2.0
	 */
	static public function date_gmmktime( $hour=0, $min=0, $sec=0, $month=0, $day=0, $year=0 )
	{
		// Calculate UTC time offset
		$offset = date( 'Z' );

		// Generate server based timestamp
		$time   = mktime( $hour, $min, $sec, $month, $day, $year );

		// Calculate DST on / off
		$dst    = intval( date( 'I', $time ) - date( 'I' ) );

		return $offset + ($dst * 3600) + $time;
	}

    /**
	 * Hand rolled GETDATE method
	 *
	 * getdate doesn't work apparently as it doesn't take into account
	 * the offset, even when fed a GMT timestamp.
	 *
	 * @param	integer	Unix date
	 * @return	array	0, seconds, minutes, hours, mday, wday, mon, year, yday, weekday, month
	 * @since	2.0
	 */
    static public function date_getgmdate( $gmt_stamp )
    {
    	//$tmp = gmdate( 'j,n,Y,G,i,s,w,z,l,F,W,M', $gmt_stamp );
    	$format	= '%e,%m,%Y,%H,%M,%S,%u,%j,%A,%B,%W,%b';
    	
    	//-----------------------------------------
    	// Some flags not available on Windows
    	// @see http://www.php.net/manual/en/function.strftime.php#53340
    	//-----------------------------------------
    	
    	if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
    	{
    		$mapping = array(
    						'%e'	=> sprintf("%' 2d", date("j", $gmt_stamp)),
    						'%u'	=> ($w = date("w", $gmt_stamp)) ? $w : 7,
    						);

			$format = str_replace( array_keys($mapping), array_values($mapping), $format );
    	}
    	
		$tmp = gmstrftime( $format, $gmt_stamp );

    	list( $day, $month, $year, $hour, $min, $seconds, $wday, $yday, $weekday, $fmon, $week, $smon ) = explode( ',', $tmp );

    	return array(  0         => $gmt_stamp,
    				   "seconds" => $seconds, //	Numeric representation of seconds	0 to 59
					   "minutes" => $min,     //	Numeric representation of minutes	0 to 59
					   "hours"	 => $hour,	  //	Numeric representation of hours	0 to 23
					   "mday"	 => trim($day),     //	Numeric representation of the day of the month	1 to 31
					   "wday"	 => $wday,    //    Numeric representation of the day of the week	0 (for Sunday) through 6 (for Saturday)
					   "mon"	 => $month,   //    Numeric representation of a month	1 through 12
					   "year"	 => $year,    //    A full numeric representation of a year, 4 digits	Examples: 1999 or 2003
					   "yday"	 => $yday,    //    Numeric representation of the day of the year	0 through 365
					   "weekday" => $weekday, //	A full textual representation of the day of the week	Sunday through Saturday
					   "month"	 => $fmon,    //    A full textual representation of a month, such as January or Mar
					   "week"    => $week,    //    Week of the year
					   "smonth"  => $smon,
					   "smon"    => $smon
					);
    }
}

/**
* IPSLib
*
* Dumping ground for functions that don't fit anywhere else
*
*/
class IPSLib
{
	/**
	 * FURL Templates
	 *
	 * @param	array
	 */
	static protected $_furlTemplates = array();
	
	/**
	 * Search configs
	 *
	 * @param	array
	 */
	static protected $_searchConfigs = array();
	
	/**
	 * Log in methods
	 *
	 * @param	array
	 */
	static protected $_lims 		   = array();
	
	/**
	 * Returns the class name to be instantiated, the class file will already be included 
	 *
	 * @param	string 	$filePath		File location of the class (leave an empty string if you've already loaded the main file)
	 * @param	string	$className		Name of the class
	 * @param	string	$app			Application (defaults to 'core')
	 * @return	string	Class Name
	 */
	static public function loadLibrary( $filePath, $className, $app='core' )
	{
		/* Get the class */
		if ( $filePath != '' )
		{
			require_once( $filePath );/*noLibHook*/
		}
		
		/* Check for hooks */
		$hooksCache	= ipsRegistry::cache()->getCache('hooks');

		if( isset( $hooksCache['libraryHooks'][ $app ][ $className ] ) AND is_array( $hooksCache['libraryHooks'][ $app ][ $className ] ) AND count( $hooksCache['libraryHooks'][ $app ][ $className ] ) )
		{
			foreach( $hooksCache['libraryHooks'][ $app ][ $className ] as $classOverloader )
			{
				/* Hooks: Do we have the hook file? */
				if( is_file( IPS_HOOKS_PATH . $classOverloader['filename'] ) )
				{
					require_once( IPS_HOOKS_PATH . $classOverloader['filename'] );/*noLibHook*/
	            
					if( class_exists( $classOverloader['className'] ) )
					{
						/* Hooks: We have the hook file and the class exists - reset the classname to load */
						$className = $classOverloader['className'];
					}
				}
			}
		}
		
		/* Return Class Name */
		return $className;
	}
	
	/**
	 * Returns the class name to be instantiated, the class file will already be included 
	 *
	 * @param	string 	$filePath		File location of the class
	 * @param	string	$className		Name of the class
	 * @return	string	Class Name
	 */
	static public function loadActionOverloader( $filePath, $className )
	{
		/* Get the class */
		require_once( $filePath );/*noLibHook*/
		
		/* Hooks: Are we overloading this class? */
		$hooksCache	= ipsRegistry::cache()->getCache('hooks');

		if( isset( $hooksCache['commandHooks'][ $className ] ) AND is_array( $hooksCache['commandHooks'][ $className ] ) AND count( $hooksCache['commandHooks'][ $className ] ) )
		{
			foreach( $hooksCache['commandHooks'][ $className ] as $classOverloader )
			{
				/* Hooks: Do we have the hook file? */
				if( is_file( IPS_HOOKS_PATH . $classOverloader['filename'] ) )
				{
					require_once( IPS_HOOKS_PATH . $classOverloader['filename'] );/*noLibHook*/
	            
					if( class_exists( $classOverloader['className'] ) )
					{
						/* Hooks: We have the hook file and the class exists - reset the classname to load */
						$className = $classOverloader['className'];
					}
				}
			}
		}
		
		/* Return Class Name */
		return $className;
	}
	
	/**
	 * Checks if there are any data hooks to run
	 *
	 * @param	array 	$dataArray		Data to be passed into the hooks
	 * @param	string	$hookLocation	Location the data was sent from
	 * @return	@e void
	 */
	static public function doDataHooks( &$dataArray, $hookLocation )
	{
    	/* Loop through the cache */
    	$hooksCache = ipsRegistry::cache()->getCache( 'hooks' );

		if( isset($hooksCache['dataHooks'][ $hookLocation ]) AND is_array( $hooksCache['dataHooks'][ $hookLocation ] ) AND count( $hooksCache['dataHooks'][ $hookLocation ] ) )
		{
			foreach( $hooksCache['dataHooks'][ $hookLocation ] as $r )
			{
				/* Check for hook file */
				if( is_file( IPS_HOOKS_PATH . $r['filename'] ) )
				{
					/* Check for hook class */
					require_once( IPS_HOOKS_PATH . $r['filename'] );/*noLibHook*/
					
					if( class_exists( $r['className'] ) )
					{
						/* Create and run the hook */
						$_hook		= new $r['className'];
						$newArray	= $_hook->handleData( $dataArray );
						
						/* Make sure the array isn't wiped out */
						if( is_array( $newArray ) && count( $newArray ) )
						{
							$dataArray = $newArray;
						}
					}
				}
			}
		}
	}
	
	/**
	 * Returns an array of data hook locations
	 *
	 * @return	array
	 */
	static public function getDataHookLocations()
	{
		$_locations = array();
		
		/* Loop all apps and get back our locations! */
		foreach( ipsRegistry::$applications as $app_dir => $application )
		{
			$dataHookLocations = array();

			if( is_file( IPSLib::getAppDir( $app_dir ) . '/extensions/dataHookLocations.php' ) )
			{
				require_once( IPSLib::getAppDir( $app_dir ) . '/extensions/dataHookLocations.php' );/*noLibHook*/
				
				if ( is_array($dataHookLocations) && count($dataHookLocations) )
				{
					$_locations = array_merge( $_locations, $dataHookLocations );
				}
			}
		}
		
		return $_locations;
	}
	
	/**
	 * Checks to see if there is a template hook installed at the specified location
	 *
	 *
	 * @param	string	$group
	 * @param	array	$id
	 * @return	bool
	 */
	static public function locationHasHooks( $group, $ids )
	{
		/* Return right away if we don't have an ids to check */
		if( ! is_array( $ids ) || ! count( $ids ) )
		{
			return false;
		}

		/* Reformat the cache on the first call, to save processing later */
		static $formattedCache	= array();

		if( !isset($formattedCache[ $group ]) )
		{
			$formattedCache[ $group ] = array();
									
			$hookCache = ipsRegistry::cache()->getCache( 'hooks' );

			if ( isset( $hookCache['templateHooks'][ $group ] ) AND is_array($hookCache['templateHooks'][ $group ]) AND count($hookCache['templateHooks'][ $group ]) )
			{
				foreach( $hookCache['templateHooks'][ $group ] as $_hook )
				{
					$formattedCache[ $group ][] = $_hook['id'];
				}
			}
		}

		/* Use formatted cache to check */
		if( count( $formattedCache[ $group ] ) )
		{
			foreach( $ids as $id )
			{
				if( in_array( $id, $formattedCache[ $group ] ) )
				{
					return true;
				}
			}
		}

		return false;
	}
	
	/**
	 * Central setlocale method so we can adjust as needed
	 *
	 * @param	string		Locale to set
	 * @return	@e void
	 * @link	http://community.invisionpower.com/tracker/issue-16386-language-locale-gives-error/
	 * @link	http://community.invisionpower.com/tracker/issue-18424-change-lang-locale/
	 */
	static public function setlocale( $locale='' )
	{
		if( !$locale )
		{
			return;
		}
		
		if ( stripos( $locale, 'tr_' ) !== FALSE )
		{
			setlocale( LC_COLLATE, $locale );
			setlocale( LC_MONETARY, $locale );
			setlocale( LC_NUMERIC, $locale );
			setlocale( LC_TIME, $locale );
			setlocale( LC_MESSAGES, $locale );
		}
		else
		{
			setlocale( LC_ALL, $locale );
		}
	}
	
	/**
	 * Quickly determines if we've got FB enabled and set up
	 *
	 * @return	boolean
	 */
	static public function fbc_enabled()
	{
		return ( ipsRegistry::$settings['fbc_enable'] AND ipsRegistry::$settings['fbc_appid'] AND ipsRegistry::$settings['fbc_secret'] ) ? TRUE : FALSE;
	}
	
	/**
	 * Quickly determines if we've got twitter enabled and set up
	 *
	 * @return	boolean
	 */
	static public function twitter_enabled()
	{
		return ( ipsRegistry::$settings['tc_enabled'] AND ipsRegistry::$settings['tc_token'] AND ipsRegistry::$settings['tc_secret'] ) ? TRUE : FALSE;
	}
	
	/**
	 * Quickly determines if we've got other log in enabled and set up
	 *
	 * @return	boolean
	 */
	static public function loginMethod_enabled( $method )
	{
		if ( ! count( self::$_lims ) )
		{
			if ( is_array( ipsRegistry::cache()->getCache('login_methods') ) )
			{
				$cache = ipsRegistry::cache()->getCache('login_methods');
				
				foreach( $cache as $lim )
				{
					self::$_lims[ $lim['login_folder_name'] ] = $lim['login_folder_name'];
				}
			}
		}
		
		switch( $method )
		{
			case 'facebook':
				return self::fbc_enabled();
			break;
			case 'twitter':
				return self::twitter_enabled();
			break;
			default:
					return in_array( $method, self::$_lims ) ? true : false;
			break;
		}
	}
	
	/**
	 * Loop through the input request and create an array of ids based on a string prefix
	 *
	 * @param	string		Prefix
	 * @return	array
	 */
	static public function fetchInputAsArray( $prefix='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$ids = array();

		if( !$prefix )
		{
			return $ids;
		}
		
		//-----------------------------------------
		// GET from checkboxes
		//-----------------------------------------

		foreach ( ipsRegistry::$request as $k => $v )
		{
			if ( $v && preg_match( "/^" . $prefix . '(\d+)$/', $k, $match ) )
			{
				$ids[] = $match[1];
			}
		}

		$ids = self::cleanIntArray( $ids );
		
		return $ids;
	}
	
	/**
	 * Little function to return the version number data
	 *
	 * Handy to use when dealing with IN_DEV, etc
	 * Uses the constant where available
	 *
	 * @param	string  App ( Default 'core')
	 * @return	array  array( 'long' => x, 'human' => x )
	 */
	static public function fetchVersionNumber( $app='core' )
	{
		if ( ! defined( IPB_VERSION ) OR ! defined( IPB_LONG_VERSION ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sources/base/setup.php' );/*noLibHook*/
			
			$XMLVersions = IPSSetUp::fetchXmlAppVersions( $app );
			$tmp         = $XMLVersions;
			krsort( $tmp );

			foreach( $tmp as $long => $human )
			{
				$return = array( 'long' => $long, 'human' => $human );
				break;
			}
		}
		else
		{
			$return = array( 'long' => IPB_LONG_VERSION, 'human' => IPB_VERSION );
		}
		
		return $return;
	}
	
	/**
	 * Cheeky little function to locate group table fields from other apps
	 *
	 * @return array 	Array of fields from different apps
	 */
	static function fetchNonDefaultGroupFields()
	{
		$fields = array();
		
		foreach( array( 'calendar', 'gallery', 'blog', 'downloads', 'ccs', 'ipchat', 'nexus', 'ipseo' ) as $app )
		{
			$_file = IPSLib::getAppDir( $app ) . '/setup/versions/install/sql/' . $app . '_mysql_tables.php';
			
			if ( is_file( $_file ) )
			{
				$TABLE = array();
				
				require( $_file );/*noLibHook*/
				
				foreach( $TABLE as $t )
				{
					if ( preg_match( '#^ALTER TABLE\s+?groups\s+?ADD\s+?(\S+?)\s#i', $t, $match ) )
					{
						$fields[] = $match[1];
					}
				}
			}
		}
		
		return $fields;
	}
	
	/**
	 * Update settings
	 *
	 * @param	array		array('conf_key' => 'new value')
	 * @return	true/false
	 */
	static public function updateSettings( $update=array(), $parseEditorValues=FALSE )
	{	
		//-----------------------------------------
		// Load the settings we need to update
		//-----------------------------------------
	
		$fields = array_keys( $update );
		ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => "conf_key IN ('" . implode( "','", $fields ) . "')" ) );
		ipsRegistry::DB()->execute();
		$db_fields = array();
		while ( $r = ipsRegistry::DB()->fetch() )
		{
			$db_fields[ $r['conf_key']  ] = $r;
		}
		
		if ( empty( $db_fields ) )
		{
			return false;
		}

		/* We have to examine $_POST as the custom PHP for some settings
		still sets that rather than $value */			
		$oldPostData = $_POST;
		
		//-----------------------------------------
		// Update values
		//-----------------------------------------
		
		foreach( $db_fields as $key => $data )
		{
			$value = null;
			
			/* Init */
			if ( !is_array( $update[ $key ] ) )
			{
				if ( $update[ $key ] != $data['conf_default'] )
				{
					$value = str_replace( "&#39;", "'", IPSText::stripslashes( $update[ $key ] ) );
					$value = $value == '' ? "{blank}" : $value;
				}
				else
				{
					$value = $data['conf_default'];
				}
			}
						
			/* Evaluate PHP */
			if ( $data['conf_evalphp'] )
			{
				$data['conf_evalphp']	= str_replace( '&#092;', '\\', $data['conf_evalphp'] );
				$save				= 1;

				$data['conf_evalphp'] = str_replace( '$this->registry', 'ipsRegistry::instance()', $data['conf_evalphp'] );
				$data['conf_evalphp'] = str_replace( '$this->cache', 'ipsRegistry::cache()', $data['conf_evalphp'] );
				$data['conf_evalphp'] = str_replace( '$this->DB', 'ipsRegistry::DB()', $data['conf_evalphp'] );
				$data['conf_evalphp'] = str_replace( '$this->settings', 'ipsRegistry::$settings', $data['conf_evalphp'] );
				$data['conf_evalphp'] = str_replace( '$this->lang', 'ipsRegistry::getClass(\'class_localization\')', $data['conf_evalphp'] );
				
				eval( $data['conf_evalphp'] );
				
				if ( $_POST[ $key ] !== $oldPostData[ $key ] )
				{
					$value = $_POST[ $key ];
				}
				
				/* Was value set? */
				if ( $value === null )
				{
					$value = str_replace( "&#39;", "'", IPSText::stripslashes( $update[ $key ] ) );
					$value = $value == '' ? "{blank}" : $value;
				}
			}
			
			/* Parse */
			if ( $parseEditorValues and $data['conf_type'] == 'editor' )
			{
				IPSText::getTextClass('bbcode')->bypass_badwords	= 1;
				IPSText::getTextClass('bbcode')->parse_smilies		= 1;
				IPSText::getTextClass('bbcode')->parse_html			= 1;
				IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
				IPSText::getTextClass('bbcode')->parse_nl2br        = 1;
				 		        
		        if( trim($value) == '<br>' OR trim($value) == '<br />' )
		        {
		        	$value	= '';
		        }
				else
				{
					$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
					$editor = new $classToLoad();
					$value = $editor->process( $value );
				}
			}
			
			/* Strip new lines */
			$value	= str_replace( "\r", "", $value );
			
			/* Save */
			if ( $value != $data['conf_default'] )
			{
				ipsRegistry::DB()->update( 'core_sys_conf_settings', array( 'conf_value' => $value ), 'conf_id=' . $data['conf_id'] );
			}
			else if( ( isset( $update[ $key ] ) && $update[ $key ] == '' ) || ( $update[ $key ] == $data['conf_default'] ) || $data['conf_value'] != '' )
			{	
				ipsRegistry::DB()->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), 'conf_id=' . $data['conf_id'] );
			}
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => 'conf_add_cache=1' ) );
		$info = ipsRegistry::DB()->execute();
	
		while ( $r = ipsRegistry::DB()->fetch($info) )
		{	
			$value = $r['conf_value'] != "" ?  $r['conf_value'] : $r['conf_default'];
			
			if ( $value == '{blank}' )
			{
				$value = '';
			}

			$settings[ $r['conf_key'] ] = $value;
		}
		
		ipsRegistry::cache()->setCache( 'settings', $settings, array( 'array' => 1 ) );
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return true;
	}
	
	/**
	 * Retrieve the default language
	 *
	 * @return	string		Default language id (most likely a number)
	 */
	static public function getDefaultLanguage()
	{
		$cache	= ipsRegistry::cache()->getCache('lang_data');
		
		if( !count($cache) OR !is_array($cache) )
		{
			ipsRegistry::getClass('class_localization')->rebuildLanguagesCache();
			
			$cache	= ipsRegistry::cache()->getCache('lang_data');
		}
		
		$_default	= 1;
		
		foreach( $cache as $_lang )
		{
			if( $_lang['lang_default'] )
			{
				$_default	= $_lang['lang_id'];
				break;
			}
		}
		
		return $_default;
	}
	
	/**
	 * Build a global caches array from the database
	 *
	 * @return  @e array Array of global caches
	 */
	static public function buildGlobalCachesArray()
	{
		/* INIT */
		$globalCaches = array();
		
		/* Get apps with global caches */
		ipsRegistry::DB()->build( array( 'select' => 'app_global_caches',
										 'from'   => 'core_applications',
										 'where'  => "app_enabled=1 AND app_global_caches != '' AND app_global_caches " . ipsRegistry::DB()->buildIsNull( false )
								 )		);
		ipsRegistry::DB()->execute();
		
		while( $gc = ipsRegistry::DB()->fetch() )
		{
			$caches = explode( ',', $gc['app_global_caches'] );
			
			foreach( $caches as $key )
			{
				if ( $key )
				{
					$globalCaches[] = $key;
				}
			}
		}
		
		/* Get hooks with global caches */
		ipsRegistry::DB()->build( array( 'select' => 'hook_global_caches',
										 'from'   => 'core_hooks',
										 'where'  => "hook_enabled=1 AND hook_global_caches != '' AND hook_global_caches " . ipsRegistry::DB()->buildIsNull( false )
								 )		);
		ipsRegistry::DB()->execute();
		
		while( $gc = ipsRegistry::DB()->fetch() )
		{
			$caches = explode( ',', $gc['hook_global_caches'] );
			
			foreach( $caches as $key )
			{
				if ( $key )
				{
					$globalCaches[] = $key;
				}
			}
		}
		
		/* Let's have only unique values and return */
		$globalCaches = array_unique( $globalCaches );
		
		return $globalCaches;
	}
	
	/**
	 * Cache global caches
	 *
	 * @return	@e boolean
	 * 
	 * @exceptions
	 * CANNOT_WRITE			Cannot write to cache file
	 * NO_DATA_TO_WRITE		No data to write
	 */
	static public function cacheGlobalCaches()
	{
		/* Init vars */
		$data			= '';
		$globalCaches	= self::buildGlobalCachesArray();
		
		/* Got any caches? */
		if ( count( $globalCaches ) )
		{
			$_date = gmdate( 'r', time() );
			$_var  = var_export( $globalCaches, TRUE );
			$data  = <<<EOF
<?php
/**
 * Global Caches cache. Do not attempt to modify this file.
 * Please modify the relevant setting for each application or hook
 *
 * Written: {$_date}
 *
 * Why? Because Tera says so this time :P
 */

\$GLOBAL_CACHES = {$_var};

EOF;
		}
		
		/* Got data to write? */
		if ( $data )
		{
			if ( @file_put_contents( DOC_IPS_ROOT_PATH . 'cache/globalCaches.php', $data ) )
			{
				return TRUE;
			}
			else
			{
				throw new Exception( 'CANNOT_WRITE' );
			}
		}
		else
		{
			/* No data? Delete any current file as well */
			@unlink( DOC_IPS_ROOT_PATH . 'cache/globalCaches.php' );
			throw new Exception( 'NO_DATA_TO_WRITE' );
		}
	}
	
	/**
	 * Build furl templates from FURL extensions
	 *
	 * @return  array
	 */
	static public function buildFurlTemplates()
	{
		/* INIT */
		$apps			= array();
		$_SEOTEMPLATES	= array();
		static $_apps	= array();
		
		/* Done this already? */
		if ( self::$_furlTemplates )
		{
			return self::$_furlTemplates;
		}
		
		/**
		 * Get app data and cache - 1 query is better than 1 per app
		 */
		ipsRegistry::DB()->build( array( 'select' => 'app_directory, app_public_title, app_enabled', 'from' => 'core_applications' ) );
		ipsRegistry::DB()->execute();
		
		while( $_r = ipsRegistry::DB()->fetch() )
		{
			$_apps[ $_r['app_directory'] ]	= $_r;
		}

		/* Because this is called before the cache is unpacked, we need to expensively grab all app dirs */
		foreach( array( 'applications', 'applications_addon/ips', 'applications_addon/other' ) as $folder )
		{
			try
			{
				foreach( new DirectoryIterator( IPS_ROOT_PATH . $folder ) as $file )
				{
					if ( ! $file->isDot() AND $file->isDir() )
					{
						$_name = $file->getFileName();
						
						if ( substr( $_name, 0, 1 ) != '.' )
						{
							/* Check if this app is enabled before including the templates.. */
							$_check = isset($_apps[ $_name ]) ? $_apps[ $_name ] : array( 'app_public_title' => '', 'app_enabled' => 0 );

							if ( $_check['app_public_title'] && $_check['app_enabled'] )
							{
								$apps[ $folder . '/' . $_name ] = $_name;
							}
						}
					}
				}
			} catch ( Exception $e ) {}
		}
		
		/* First, add in core stuffs */
		ipsRegistry::_loadCoreVariables();
		$templates = ipsRegistry::_fetchCoreVariables('templates');
		
		if ( is_array( $templates ) )
		{
			foreach( $templates as $key => $data )
			{
				self::$_furlTemplates[ $key ] = $data;
			}
		}
		
		/* Loop over the applications and build */
		foreach( $apps as $path => $app_dir )
		{
			if ( is_file( IPS_ROOT_PATH . $path . '/extensions/furlTemplates.php' ) )
			{
				$_SEOTEMPLATES = array();
				
				require( IPS_ROOT_PATH . $path . '/extensions/furlTemplates.php' );/*noLibHook*/
				
				if ( is_array( $_SEOTEMPLATES ) && count( $_SEOTEMPLATES ) )
				{
					foreach( $_SEOTEMPLATES as $key => $data )
					{
						self::$_furlTemplates[ $key ] = $data;
					}
				}
			}
		}
		
		/* Return for anyone else */
		return self::$_furlTemplates;
	}
	
	/**
	 * Cache templates from FURL extensions
	 *
	 * @return  boolean
	 * @exceptions
	 * CANNOT_WRITE		Cannot write to cache file
	 * NO_DATA_TO_WRITE	No data to write
	 */
	static public function cacheFurlTemplates()
	{
		if ( ! count( self::$_furlTemplates ) )
		{
			self::buildFurlTemplates();
		}

		if ( count( self::$_furlTemplates ) )
		{
			$_date = gmdate( 'r', time() );
			$_var  = var_export( self::$_furlTemplates, TRUE );
			$data  = <<<EOF
<?php
/**
 * FURL Templates cache. Do not attempt to modify this file.
 * Please modify the relevant 'furlTemplates.php' file in /{app}/extensions/furlTemplates.php
 * and rebuild from the Admin CP
 *
 * Written: {$_date}
 *
 * Why? Because Matt says so.
 */
 \$templates = {$_var};

?>
EOF;
		
			if ( ! @file_put_contents( DOC_IPS_ROOT_PATH . 'cache/furlCache.php', $data ) )
			{
				throw new Exception( 'CANNOT_WRITE' );
			}
		}
		else
		{
			throw new Exception( 'NO_DATA_TO_WRITE' );
		}
		
		return TRUE;
	}
	
	/**
	 * Recursively cleans keys and values and
	 * inserts them into the input array
	 *
	 * @param	mixed		Input data
	 * @param	array		Storage array for cleaned data
	 * @param	integer		Current iteration
	 * @return	array 		Cleaned data
	 */
	static public function parseIncomingRecursively( &$data, $input=array(), $iteration = 0 )
	{
		// Crafty hacker could send something like &foo[][][][][][]....to kill Apache process
		// We should never have an input array deeper than 20..

		if ( $iteration >= 20 )
		{
			return $input;
		}

		foreach( $data as $k => $v )
		{
			if ( is_array( $v ) )
			{
				$input[ $k ] = self::parseIncomingRecursively( $data[ $k ], array(), $iteration + 1 );
			}
			else
			{
				$k = IPSText::parseCleanKey( $k );
				$v = IPSText::parseCleanValue( $v, false );

				$input[ $k ] = $v;
			}
		}

		return $input;
	}

	/**
	 * Recursively cleans values after settings have been loaded.
	 * Necessary for certain functions (such as whether to strip space chars or not)
	 *
	 * @param	mixed		Input data
	 * @param	integer		Current iteration
	 * @return	array 		Cleaned data
	 */
	static public function postParseIncomingRecursively( $request, $iteration = 0 )
	{
		// Crafty hacker could send something like &foo[][][][][][]....to kill Apache process
		// We should never have an input array deeper than 20..

		if ( $iteration >= 20 OR !is_array($request) )
		{
			return $request;
		}

		foreach( $request as $k => $v )
		{
			if ( is_array( $v ) )
			{
				$request[ $k ] = self::postParseIncomingRecursively( $v, ++$iteration );
			}
			else
			{
				$v = IPSText::postParseCleanValue( $v );

				$request[ $k ] = $v;
			}
		}

		return $request;
	}
	
	/**
	 * Performs basic cleaning, Null characters, etc
	 *
	 * @param	array 	Input data
	 * @return	array 	Cleaned data
	 */
	static public function cleanGlobals( &$data, $iteration = 0 )
	{
		// Crafty hacker could send something like &foo[][][][][][]....to kill Apache process
		// We should never have an input array deeper than 10..

		if ( $iteration >= 10 )
		{
			return;
		}
				
		foreach( $data as $k => $v )
		{
			if ( is_array( $v ) )
			{
				self::cleanGlobals( $data[ $k ], ++$iteration );
			}
			else
			{
				# Null byte characters
				$v = str_replace( chr('0') , '', $v );
				$v = str_replace( "\0"    , '', $v );
				$v = str_replace( "\x00"  , '', $v );

				// @link	http://community.invisionpower.com/tracker/issue-21188-post-processor-eating-characters/
				//$v = str_replace( '%00'   , '', $v );

				# File traversal
				$v = str_replace( "../", "&#46;&#46;/", $v );
				
				/* RTL override */
				$v = str_replace( '&#8238;', '', $v );
				
				$data[ $k ] = $v;
			}
		}
	}
	
	/**
	 * Fetch emoticons as JSON for editors, etc
	 *
	 * @param	string		Directory for emos [optional]
	 * @param	bool		Include emoticons not marked clickable
	 * @return	string		JSON
	 */
	static public function fetchEmoticonsAsJson( $emoDir='', $nonClickable=false )
	{
		$emoDir    = ( $emoDir ) ? $emoDir : ipsRegistry::getClass('output')->skin['set_emo_dir'];
		$emoArray  = array();
		$emoString = '';
		$smilie_id = 0;

		foreach( ipsRegistry::cache()->getCache('emoticons') as $elmo )
		{
			if ( $elmo['emo_set'] != $emoDir )
			{
				continue;
			}
			
			if ( ! $elmo['clickable'] AND !$nonClickable )
			{
				continue;
			}

			$smilie_id++;
			
			//-----------------------------------------
			// Make single quotes as URL's with html entites in them
			// are parsed by the browser, so ' causes JS error :o
			//-----------------------------------------
			
			if ( strstr( $elmo['typed'], "&#39;" ) )
			{
				$in_delim  = '"';
			}
			else
			{
				$in_delim  = "'";
			}
			
			$emoArray[] = $in_delim . addslashes($elmo['typed']) . $in_delim . ' : "' . $smilie_id . ','.$elmo['image'].'"';
		
		}
		
		//-----------------------------------------
		// Finish up smilies...
		//-----------------------------------------
		
		if ( count( $emoArray ) )
		{
			$emoString = implode( ",\n", $emoArray );
		}
		
		return $emoString;
	}
	
	/**
	 * Fetch bbcode as JSON for editors, etc
	 *
	 * @return	string		JSON
	 */
	static public function fetchBbcodeAsJson( $filter=array() )
	{
		$bbcodes			= array();
		$currentBbcodes		= ipsRegistry::cache()->getCache('bbcode');
		$protectedBbcodes	= array('right', 'left', 'center', 'b', 'i', 'u', 'url', 'img', 'quote', 'indent', 'snapback',
									'list', 'strike', 'sub', 'sup', 'email', 'color', 'size', 'font'
									);
		
		/* Remove protected bbcodes */
		foreach( $protectedBbcodes as $_key )
		{
			unset( $currentBbcodes[ $_key ] );
		}
		 
		/* Get all others */
		foreach( $currentBbcodes as $bbcode )
		{
			if ( $bbcode['bbcode_groups'] != 'all' )
			{
				$pass		= false;
				$groups		= array_diff( explode( ',', $bbcode['bbcode_groups'] ), array('') );
				$mygroups	= array( ipsRegistry::member()->getProperty('member_group_id') );
				$mygroups	= array_diff( array_merge( $mygroups, explode( ',', IPSText::cleanPermString( ipsRegistry::member()->getProperty('mgroup_others') ) ) ), array('') );
				
				foreach( $groups as $g_id )
				{
					if( in_array( $g_id, $mygroups ) )
					{
						$pass = true;
						break;
					}
				}
				
				if ( ! $pass )
				{
					continue;
				}
			}
			
			if ( ! empty( $filter['skip'] ) && is_array( $filter['skip'] ) )
			{ 
				if ( in_array( $bbcode['bbcode_tag'], $filter['skip'] ) )
				{
					continue;
				}
			}

			$bbcodes[ $bbcode['bbcode_tag'] ]	= array(
														'id'				=> $bbcode['bbcode_id'],
														'title'				=> $bbcode['bbcode_title'],
														'desc'				=> $bbcode['bbcode_desc'],
														'tag'				=> $bbcode['bbcode_tag'],
														'useoption'			=> $bbcode['bbcode_useoption'],
														'example'			=> $bbcode['bbcode_example'],
														'switch_option'		=> $bbcode['bbcode_switch_option'],
														'menu_option_text'	=> $bbcode['bbcode_menu_option_text'],
														'menu_content_text'	=> $bbcode['bbcode_menu_content_text'],
														'single_tag'		=> $bbcode['bbcode_single_tag'],
														'optional_option'	=> $bbcode['bbcode_optional_option'],
														'image'				=> $bbcode['bbcode_image'],
														);
		}
		
		return IPSText::simpleJsonEncode($bbcodes);
	}

	/**
	 * Runs the specified member sync module, takes a variable number of arguments.
	 *
	 * @param	string	$module		The module to run, ex: onCreateAccount, onRegisterForm, etc
	 * @param	mixed	...			Remaining params should match the module being called. ex: array of member data for onCreateAccount,
     *								or an id and email for onEmailChange
	 * @return	@e void
	 */
	static public function runMemberSync( $module )
	{
		/* ipsRegistry::$applications only contains apps with a public title #15785 */
		$app_cache = ipsRegistry::cache()->getCache('app_cache');
		
		/* Params */
		$params = func_get_args();
		array_shift( $params );

		/* Loop through applications */
		foreach( $app_cache as $app_dir => $app )
		{
			/* Only if app enabled... */
			if ( $app['app_enabled'] )
			{
				/* Setup */
				$_file  = self::getAppDir( $app['app_directory'] ) . '/extensions/memberSync.php';
				
				/* Check for the file */
				if ( is_file( $_file ) )
				{
					/* Get the file */
					$_class = self::loadLibrary( $_file, $app['app_directory'] . 'MemberSync', $app['app_directory'] );
					
					/* Check for the class */
					if ( class_exists( $_class ) )
					{
						/* Create an object */
						$_obj = new $_class();

						/* Check for the module */
						if ( method_exists( $_obj, $module ) )
						{
							/* Call it */
							call_user_func_array( array( $_obj, $module ), $params );
							IPSDebug::addLogMessage( $app_dir . '-' . $module, 'mem' );
						}
					}
				}
			}
		}
	}

	/**
	 * Pick the highest number from an array
	 * Used in classItemMarking.. figured it might be useful elsewhere...
	 *
	 * @param	array 		Array of numbers
	 * @return	integer		Highest number in the array
	 */
	static public function fetchHighestNumber( $array )
	{
		if ( is_array( $array ) )
		{
			$_array = array();

			foreach( $array as $number )
			{
				$_array[] = intval( $number );
			}

			sort( $_array );

			return intval( array_pop( $_array ) );
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Hand-rolled 'is_writable' function to overcome
	 * annoyances with the PHP in built version
	 * Based on user notes at php.net (is_writable function comments)
	 *
	 * @param	string		Path to check
	 * @return	boolean
	 */
	static public function isWritable( $path )
	{
		if ( substr( $path, -1 ) == '/' )
		{
	        return self::isWritable( $path . uniqid( mt_rand() ) . '.tmp');
		}
	    else if ( is_dir( $path ) )
		{
			return self::isWritable( $path.'/'.uniqid( mt_rand() ) . '.tmp');
	    }

		$e = file_exists( $path );
	    $f = @fopen( $path, 'a' );

	    if ( $f === FALSE )
		{
	        return FALSE;
		}

	    fclose ($f );

	    if ( $e === FALSE )
		{
	        unlink($path);
		}

	    return TRUE;
	}

	/**
	 * Acts like PHPs next() but if the pointer is at the end of the array or it finds a false
	 * value, then it rewinds the array and starts over
	 *
	 * @param	array 		Reference to an array
	 * @return	mixed		Next value in the array
	 */
	static public function next( &$array )
	{
		if ( ! is_array( $array ) )
		{
			return FALSE;
		}

		$next = next( $array );

		if ( ! $next || $next === FALSE )
		{
			reset( $array );
			$next = next( $array );
		}

		return $next;
	}
	
	/**
	 * Function to naturally sort an array by keys
	 *
	 * @param	array 		Array to sort
	 * @return	array 		Sorted array
	 */
	static public function knatsort( $array )
	{
		$_a = array_keys( $array );
		$_b = array();
		
		natsort( $_a );
		
		foreach( $_a as $__a )
		{
			$_b[ $__a ] = $array[ $__a ];
		}
		
		return $_b;
	}

	/**
	 * Merges arrays like array_merge_recursive but replaces indentical keys
	 *
	 * @param	array 		Array to merge
	 * @param	array 		Array to merge
	 * @param	array 		...
	 * @return	array 		Merged array
	 */
	static public function arrayMergeRecursive()
	{
	    $arrays = func_get_args();
  		$base   = array_shift( $arrays );
  		
  		if ( ! is_array($base) )
  		{
  			$base = empty($base) ? array() : array($base);
  		}
  		
  		foreach( $arrays as $append )
  		{
    		if ( ! is_array( $append ) )
    		{
    			$append = array( $append );
    		}
    		
    		foreach( $append as $key => $value)
    		{
      			if ( ! array_key_exists( $key, $base ) and ! is_numeric( $key ) )
      			{
        			$base[$key] = $append[$key];
        			continue;
      			}
      			
      			if ( is_array($value) or is_array( $base[$key] ) )
      			{
        			$base[$key] = self::arrayMergeRecursive( $base[$key], $append[$key] );
      			}
      			else if ( is_numeric( $key ) )
      			{
        			if ( ! in_array( $value, $base ) )
        			{
        				$base[] = $value;
        			}
      			}
      			else
      			{
       				$base[$key] = $value;
      			}
    		}
  		}
  		
 		return $base;
	}
	
	/**
	 * Merge two arrays.  Unlike array_merge, however, duplicate keys are ignored rather than overwritten.
	 *
	 * @param	array
	 * @param	array
	 * @return	array
	 */
	static public function mergeArrays( $array1, $array2 )
	{
		$returnArray	= $array1;
		
		foreach( $array2 as $_key => $_value )
		{
			if( !array_key_exists( $_key, $returnArray ) )
			{
				$returnArray[ $_key ]	= $_value;
			}
		}
		
		return $returnArray;
	}
	
	/**
	 * arraySearchLoose
	 *
	 * @param	string		"Needle"
	 * @param	array 		Array of text to search
	 * @return	mixed		Key of array, or false on failure
	 */
	static public function arraySearchLoose( $needle, $haystack )
	{
		if( !is_array( $haystack ) OR !count($haystack) OR ! $needle )
		{
			return false;
		}
		
		foreach( $haystack as $k => $v )
		{
			if( $v AND stripos( $v, $needle ) !== false )
			{
				return $k;
			}
		}
		
		return false;
	}
	
	/**
	 * Get the application title.  Uses lang file keys if present.
	 *
	 * @param	string		application
	 * @param	bool		Force public title
	 * @return	string		Text to show for application title
	 */
	static public function getAppTitle( $app, $forcePublic=false )
	{
		if ( ! $app )
		{
			return '';
		}

		return isset( ipsRegistry::getClass('class_localization')->words[ $app . '_display_title' ] ) ? 
				ipsRegistry::getClass('class_localization')->words[ $app . '_display_title' ] :
				( ( IN_ACP AND !$forcePublic ) ? ipsRegistry::$applications[ $app ]['app_title'] : ipsRegistry::$applications[ $app ]['app_public_title'] );
	}

	/**
	 * Generates the app [ -> module ] path. The module is optional, if module is not
	 * specified then just the app dir is returned. If this is called from the ACP and module
	 * is present, then it'll return modules_admin, otherwise modules_public
	 *
	 * @param	string		application
	 * @param	string		module (optional)
	 * @return	mixed		Directory to app or module (or false if error)
	 */
	static public function getAppDir( $app, $module='' )
	{
		$location = '';

		if ( ! $app OR !is_string($app) )
		{
			return FALSE;
		}

		/* Ok, chicken and egg scenario. Applications has not been set up - most likely because
		   we're using this function before the caches have been loaded and unpacked.
		   So we guess based on folder names.... */
		if ( ! is_array( ipsRegistry::$applications ) OR ! count( ipsRegistry::$applications ) OR ! isset( ipsRegistry::$applications[ $app ] ) )
		{
			$location = self::extractAppLocationKey( $app );
		}
		else
		{
			$location = ipsRegistry::$applications[ $app ]['app_location'];
		}

		$pathBit = IPS_ROOT_PATH . 'applications';

		switch ( $location )
		{
			default:
			case 'root':
				$pathBit .= '/' . $app;
			break;
			case 'ips':
				$pathBit .= '_addon/ips/' . $app;
			break;
			case 'other':
				$pathBit .= '_addon/other/' . $app;
			break;
		}

		if ( $module )
		{
			$modulesFolder = ( IPS_AREA != 'admin' ) ? 'modules_public' : 'modules_admin';
			
			return $pathBit . "/" . $modulesFolder . "/" . $module;
		}
		else
		{
			return $pathBit;
		}
	}
	
	/**
	 * Extracts app_location from app key
	 *
	 * @param	string		File path
	 * @return	string		root, ips, other
	 */
	static public function extractAppLocationKey( $app )
	{
		/* Test core apps first... */
		if ( is_dir( IPS_ROOT_PATH . 'applications/' . $app ) )
		{
			$location = 'root';
		}
		else if ( is_dir( IPS_ROOT_PATH . 'applications_addon/ips/' . $app ) )
		{
			$location = 'ips';
		}
		else
		{
			$location = 'other';
		}
		
		return $location;
	}

	/**
	 * Generates the app folder name, either "applications" or "applications_addon"
	 *
	 * @param	string		application
	 * @return	mixed		Directory to app or module (or false if error)
	 */
	static public function getAppFolder( $app )
	{
		if ( ! $app OR ! isset(ipsRegistry::$applications[ $app ]) )
		{
			return FALSE;
		}

		switch ( ipsRegistry::$applications[ $app ]['app_location'] )
		{
			default:
			case 'root':
				$pathBit = 'applications';
			break;
			case 'ips':
				$pathBit = 'applications_addon/ips';
			break;
			case 'other':
				$pathBit = 'applications_addon/other';
			break;
		}

		return $pathBit;
	}
	
	/**
	 * Determines if the application can be searched
	 *
	 * @param	string	$app	Application key
	 * @return	bool
	 */
	static public function appIsSearchable( $app, $type='search' )
	{
		/* Init */
		$_ck   = '';
		
		/* map config */
		switch( strtolower( $type ) )
		{
			default:
			case 'search':
				$_ck = 'can_search';
			break;
			case 'vnc':
			case 'newcontent':
			case 'viewnewcontent':
				$_ck = 'can_viewNewContent';
			break;
			case 'active':
			case 'activecontent':
				$_ck = 'can_activeContent';
			break;
			case 'usercontent':
			case 'users':
			case 'user':
				$_ck = 'can_userContent';
			break;
			case 'vncwithfollowfilter':
				$_ck = 'can_vnc_filter_by_followed';
			break;
			case 'vncwithunreadcontent':
				$_ck = 'can_vnc_unread_content';
			break;
			case 'tags':
				$_ck = 'can_searchTags';
			break;
		}

		/* got anything? */
		if ( ! is_array( self::$_searchConfigs ) OR ! count( self::$_searchConfigs ) )
		{
			$_needRebuild	= false;

			foreach( ipsRegistry::$applications as $_app => $data )
			{
				/* use the cache if we can */
				if ( ! IN_DEV AND isset( ipsRegistry::$applications[$_app]['search'] ) AND is_array( ipsRegistry::$applications[$_app]['search'] ) AND count( ipsRegistry::$applications[$_app]['search'] ) )
				{
					if( IPSLib::appIsInstalled( $_app ) )
					{
						self::$_searchConfigs[ $_app ] = ipsRegistry::$applications[$_app]['search'];
					}
				}
				else
				{
					$_file = IPSLib::getAppDir( $_app ) . '/extensions/search/config.php';
					
					if ( IPSLib::appIsInstalled( $_app ) AND is_file( $_file ) )
					{
						$CONFIG = array();
						require( $_file );/*noLibHook*/
						
						if ( is_array( $CONFIG ) AND count( $CONFIG ) )
						{
							self::$_searchConfigs[ $_app ] = $CONFIG;
						
							unset( $CONFIG );
							
							$_needRebuild	= true;
						}
					}
				}
			}
		}
		
		/* Do we need to rebuild application cache because we checked for search configs manually? */
		if( $_needRebuild )
		{
			ipsRegistry::cache()->rebuildCache( 'app_cache', 'global' );
		}
			
		/* return */
		if ( isset( self::$_searchConfigs[ $app ] ) AND is_array( self::$_searchConfigs[ $app ] ) AND count( self::$_searchConfigs[ $app ] ) )
		{
			return ( self::$_searchConfigs[ $app ][ $_ck ] ) ? true : false;
		}
		
		return false;
	}

	/**
     * Checks to see if the given application is currently installed and enabled
     *
     * @param	string	$app
     * @return	bool
     */
    static public function appIsInstalled( $app, $checkEnabled=true )
    {
    	if ( isset( ipsRegistry::$applications[$app] ) )
    	{
    		if( $checkEnabled )
    		{
    			if( ipsRegistry::$applications[$app]['app_enabled'] )
    			{
    				return TRUE;
    			}
    		}
    		else
    		{
    			return TRUE;
			}
    	}

    	return FALSE;
    }

	/**
	 * Verify an app supports an extension
	 *
	 * @param	string	Application
	 * @param	array	Array of extensions to check
	 * @return  bool
	 */
	static public function appSupportsExtension( $app, $extensions=array() )
	{
		if( !$app )
		{
			return false;
		}
		
		if( !count($extensions) )
		{
			return true;
		}
		
		$application	= ipsRegistry::$applications[ $app ];
		
		if( $application['app_directory'] )
		{
			$c = 0;
			
			foreach( $extensions as $e )
			{
				if ( ! empty( $application['extensions'][ $e ] ) )
				{
					$c++;
				}
			}
			
			if ( $c == count( $extensions ) )
			{
				return true;
			}
		}
		
		return false;
	}
    
    /**
     * Get all enabled applications
     * 
     * @param	array	Array of extensions an application must have to be returned
     * @return  Array
     */
    static public function getEnabledApplications( $extensions=array(), $forceNotFromCache=FALSE )
    {
    	$apps 		= array();
    	$extensions = is_string( $extensions ) ? array( $extensions ) : ( is_array( $extensions ) ? $extensions : array() );
    	
		static $cache	= array();
		$_key			= md5( implode( ',', $extensions ) );
		
		if( $cache[ $_key ] and !$forceNotFromCache )
		{
			return $cache[ $_key ];
		}
    	
    	if ( ! is_array( ipsRegistry::$applications ) OR ! count( ipsRegistry::$applications ) )
    	{
    		return array();
    	}
    	
    	foreach( ipsRegistry::$applications as $appDir => $appData )
    	{
    		if ( self::appIsInstalled( $appDir, true ) )
    		{
    			if ( count($extensions) )
    			{
    				if ( is_array( $appData['extensions'] ) )
    				{
    					$c = 0;
    					
    					foreach( $extensions as $e )
    					{
    						if ( ! empty( $appData['extensions'][ $e ] ) )
    						{
    							$c++;
    						}
    					}
    					
    					if ( $c == count( $extensions ) )
    					{
    						$apps[ $appDir ] = $appData;
    					}
    				}
    			}
    			else
    			{
    				$apps[ $appDir ] = $appData;
    			}
    		}
    	}
    	
    	$cache[ $_key ]	= $apps;
    	
    	return $cache[ $_key ];
    }
    
    /**
     * Check to see if the givem module is currently installed and enabled
     *
     * @param	string	$module	module_key
     * @param	string	[$app]	app_key, current application by default
     * @return	bool
     */
    static public function moduleIsEnabled( $module, $app='' )
    {
    	$app = $app ? $app : ipsRegistry::$current_application;
    	
    	foreach( ipsRegistry::$modules[$app] as $_m )
    	{
    		if ( $_m['sys_module_key'] == $module )
    		{
    			return $_m['sys_module_visible'] == 1;
    		}
    	}
    	
    	// Undefined, retun true
    	return TRUE;
    }

	/**
	 * Grab max post upload
	 *
	 * @return	integer	Max post size
	 */
	static public function getMaxPostSize()
	{
		$max_file_size = 16777216;
		$tmp           = 0;

		$_post   = @ini_get('post_max_size');
		$_upload = @ini_get('upload_max_filesize');

		if ( $_upload > $_post )
		{
			$tmp = $_post;
		}
		else
		{
			$tmp = $_upload;
		}

		if ( $tmp )
		{
			$max_file_size = $tmp;
			unset($tmp);

			preg_match( '#^(\d+)(\w+)$#', strtolower($max_file_size), $match );
			
			if( $match[2] == 'g' )
			{
				$max_file_size = intval( $max_file_size ) * 1024 * 1024 * 1024;
			}
			else if ( $match[2] == 'm' )
			{
				$max_file_size = intval( $max_file_size ) * 1024 * 1024;
			}
			else if ( $match[2] == 'k' )
			{
				$max_file_size = intval( $max_file_size ) * 1024;
			}
			else
			{
				$max_file_size = intval( $max_file_size );
			}
		}

		return $max_file_size;
	}

    /**
	 * Convert strlen to bytes
	 *
	 * @param	integer		string length (no chars)
	 * @return	integer		Bytes
	 * @since	2.0
	 */
	static public function strlenToBytes( $strlen=0 )
    {
		$dh = pow(10, 0);

        return round( $strlen / ( pow(1024, 0) / $dh ) ) / $dh;
    }

	/**
	 * Takes a number of bytes and formats in k or MB as required
	 *
	 * @param	string 		Size, in bytes
	 * @param	boolean		TRUE = no language class avaiable (during start up, debug, etc)
	 * @return	string		Size, in MB, KB or bytes, whichever is closest
	 */
	static public function sizeFormat($bytes="", $noLang=FALSE)
	{
		$retval = "";
		
		if ( $noLang === FALSE )
		{
			$lang['sf_gb']    = ipsRegistry::getClass('class_localization')->words['sf_gb']    ? ipsRegistry::getClass('class_localization')->words['sf_gb']    : 'gb';
			$lang['sf_mb']    = ipsRegistry::getClass('class_localization')->words['sf_mb']    ? ipsRegistry::getClass('class_localization')->words['sf_mb']    : 'mb';
			$lang['sf_k']     = ipsRegistry::getClass('class_localization')->words['sf_k']     ? ipsRegistry::getClass('class_localization')->words['sf_k']     : 'kb';
			$lang['sf_bytes'] = ipsRegistry::getClass('class_localization')->words['sf_bytes'] ? ipsRegistry::getClass('class_localization')->words['sf_bytes'] : 'b';
		}
		else
		{
			$lang['sf_gb']    = 'gb';
			$lang['sf_mb']    = 'mb';
			$lang['sf_k']     = 'kb';
			$lang['sf_bytes'] = 'b';
		}
		
		if ( $bytes >= 1073741824 )
		{
			$retval = round($bytes / 1073741824 * 100 ) / 100 . $lang['sf_gb'];
		}
		else if ($bytes >= 1048576)
		{
			$retval = round($bytes / 1048576 * 100 ) / 100 . $lang['sf_mb'];
		}
		else if ($bytes  >= 1024)
		{
			$retval = round($bytes / 1024 * 100 ) / 100 . $lang['sf_k'];
		}
		else
		{
			$retval = $bytes . $lang['sf_bytes'];
		}

		return $retval;
	}

    /**
	 * Makes int based arrays safe
	 * XSS Fix: Ticket: 24360 (Problem with cookies allowing SQL code in keys)
	 *
	 * @param	array		Array
	 * @return	array		Array (Cleaned)
	 * @since	2.1.4(A)
	 */
    static public function cleanIntArray( $array=array() )
    {
		$return = array();

		if ( is_array( $array ) and count( $array ) )
		{
			foreach( $array as $k => $v )
			{
				$return[ intval($k) ] = intval($v);
			}
		}

		return $return;
	}

	/**
	 * Loads an interface. Abstracted incase we change location / method
	 * of loading an interface
	 *
	 * @param	string		File name
	 * @return	@e void
	 * @since	3.0.0
	 */
	static public function loadInterface( $filename )
	{
		//-----------------------------------------
		// Very simple, currently.
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/interfaces/' . $filename );/*noLibHook*/
	}
	
	/**
	 * Scale a remote image
	 *
	 * @param	string		URL
	 * @param	int			Max width
	 * @param	int			Max height
	 * @return	string		width='#' height='#' string
	 */
	static public function getTemplateDimensions( $image, $width, $height )
	{
		if( empty( $width ) AND empty( $height ) )
		{
			return;
		}
		
		if( !$image )
		{
			return;
		}

		//-----------------------------------------
		// Checking image dimensions via disk instead
		// of http is faster...can we try that..?
		//-----------------------------------------

		if( strpos( $image, ipsRegistry::$settings['board_url'] ) === 0 )
		{
			$image = DOC_IPS_ROOT_PATH . str_replace( ipsRegistry::$settings['board_url'], '', $image );
		}

		//-----------------------------------------
		// Dimensions
		// If set maxwidth and no maxheight, then we want the script to
		//	reduce based on width only.  And vice-versa.
		//-----------------------------------------
		
		$maxWidth	= ( $width ) ? intval($width) : 1000000000;
		$maxHeight	= ( $height ) ? intval($height) : 1000000000;
		
		//-----------------------------------------
		// Existing dims
		//-----------------------------------------
		
		$_dims		= @getimagesize( $image );

		if( !$_dims[0] )
		{
			return;
		}
		
		$_newDims	= IPSLib::scaleImage( array( 
												'cur_width'		=> $_dims[0],
												'cur_height'	=> $_dims[1],
												'max_width'		=> $maxWidth,
												'max_height'	=> $maxHeight,
										)		);

		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		return " width='{$_newDims['img_width']}' height='{$_newDims['img_height']}'";
	}

	/**
	 * Given current dimensions + max dimensions, return scaled image dimensions constrained to maximums
	 *
	 * @param	array	Current dimensions + max dimensions
	 * @return	array	New image dimensions
	 * @since	2.0
	 */
	public static function scaleImage($arg)
	{
		// max_width, max_height, cur_width, cur_height

		$ret = array(
					  'img_width'  => $arg['cur_width'],
					  'img_height' => $arg['cur_height']
					);

		if ( $arg['cur_width'] > $arg['max_width'] )
		{
			$ret['img_width']  = $arg['max_width'];
			$ret['img_height'] = ceil( ( $arg['cur_height'] * ( ( $arg['max_width'] * 100 ) / $arg['cur_width'] ) ) / 100 );
			$arg['cur_height'] = $ret['img_height'];
			$arg['cur_width']  = $ret['img_width'];
		}

		if ( $arg['cur_height'] > $arg['max_height'] )
		{
			$ret['img_height']  = $arg['max_height'];
			$ret['img_width']   = ceil( ( $arg['cur_width'] * ( ( $arg['max_height'] * 100 ) / $arg['cur_height'] ) ) / 100 );
		}

		return $ret;
	}
	
	/**
	 * Retrieve all IP addresses a user (or multiple users) have used
	 *
	 * @param 	string		Where clause for ip address
	 * @param	string		Defaults to 'All', otherwise specify which tables to check (comma separated)
	 * @return	array		Multi-dimensional array of found IP addresses in which sections
	 */
	static public function findIPAddresses( $ip_where, $tables_to_check='all' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$ip_addresses 	= array();
		$tables			= array(
							'admin_logs'			=> array( 'member_id', 'ip_address', 'ctime' ),
							'dnames_change'			=> array( 'dname_member_id', 'dname_ip_address', 'dname_date' ),
							'members'				=> array( 'member_id', 'ip_address', 'joined' ),
							'message_posts'			=> array( 'msg_author_id', 'msg_ip_address', 'msg_date' ),
							'moderator_logs'		=> array( 'member_id', 'ip_address', 'ctime' ),
							'posts'					=> array( 'author_id', 'ip_address', 'post_date', array( 'pid' ) ),
							'member_status_updates'	=> array( 'status_author_id', 'status_author_ip', 'status_date' ),
							'profile_ratings'		=> array( 'rating_by_member_id', 'rating_ip_address', '' ),
							//'sessions'				=> array( 'member_id', 'ip_address', 'running_time' ),
							'topic_ratings'			=> array( 'rating_member_id', 'rating_ip_address', '' ),
							'validating'			=> array( 'member_id', 'ip_address', 'entry_date' ),
							'voters'				=> array( 'member_id', 'ip_address', 'vote_date', array( 'tid' ) ),
							'error_logs'			=> array( 'log_member', 'log_ip_address', 'log_date' ),
							);

		//-----------------------------------------
		// Check apps
		// @see http://forums.invisionpower.com/tracker/issue-16966-members-download-manag/
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $app_dir => $data )
		{
			if( is_file( IPSLib::getAppDir( $app_dir ) . "/extensions/coreExtensions.php") )
			{
				$classX = IPSLib::loadLibrary( IPSLib::getAppDir( $app_dir ) . "/extensions/coreExtensions.php", $app_dir . '_findIpAddress', $app_dir );
				
				if( class_exists( $classX ) )
				{
					$ipLookup	= new $classX( ipsRegistry::instance() );
					
					if( method_exists( $ipLookup, 'getTables' ) )
					{
						$tables		= array_merge( $tables, $ipLookup->getTables() );
					}
				}
			}
		}

		//-----------------------------------------
		// Got tables?
		//-----------------------------------------

		$_tables = explode( ',', $tables_to_check );

		if( !is_array($_tables) OR !count($_tables) )
		{
			return array();
		}

		//-----------------------------------------
		// Loop through them and grab the IPs
		//-----------------------------------------

		foreach( $tables as $tablename => $fields )
		{
			if( $tables_to_check == 'all' OR in_array( $tablename, $_tables ) )
			{
				$extra = '';
				$ids   = array();
				
				if( $tablename == 'members' )
				{
					if( $fields[2] )
					{
						$extra	= ', ' . $fields[2] . ' as date';
					}
					
					if( $fields[3] AND is_array($fields[3]) )
					{
						$extra	.= ', ' . implode( ', ', $fields[3] );
					}

					ipsRegistry::DB()->build( array(
													'select'	=> $fields[1] . $extra . ', member_id', 
													'from'		=> $tablename, 
													'where'		=> $fields[1] . $ip_where,
													'group'		=> 'member_id, ip_address, joined',
													'order'		=> 'joined DESC',
													'limit'		=> array( 250 ),
											)		);
				}
				else
				{
					if( $fields[2] )
					{
						$extra	= ', c.' . $fields[2] . ' as date';
					}
					
					if( $fields[3] AND is_array($fields[3]) )
					{
						$extra	.= ', c.' . implode( ', c.', $fields[3] );
					}
					
					$extra .= ', c.' . $fields[1] . ' as ip_address, c.' . $fields[0];
				
					ipsRegistry::DB()->build( array(
													'select'	=> 'c.' . $fields[1] . $extra, 
													'from'		=> array( $tablename => 'c' ), 
													'where'		=> 'c.' . $fields[1] . $ip_where,
													'order'		=> $fields[2] ? 'c.' . $fields[2] . ' DESC' : 'c.' . $fields[0] . ' DESC',
													'group'		=> 'c.' . $fields[0] . ', c.' . $fields[1],
													'limit'		=> array( 250 ),
													'add_join'	=> array(
																		array(
																			'select'	=> 'm.member_id, m.members_display_name, m.email, m.posts, m.joined',
																			'from'		=> array( 'members' => 'm' ),
																			'where'		=> 'm.member_id=c.' . $fields[0],
																			'type'		=> 'left',
																			)
																		)
											)		);
				}

				ipsRegistry::DB()->execute();
				
				$i = 0;
				
				while( $r = ipsRegistry::DB()->fetch() )
				{
					if ( $r[ $fields[0] ] )
					{
						$ids[] = $r[ $fields[0] ];
					}
					
					if( $r[ $fields[1] ] )
					{
						$rawData[ ++$i ]	= $r;
					}
				}
				
				/* Get members */
				$members = IPSMember::load( $ids, 'core' );
				
				if ( is_array( $rawData ) and count ( $rawData ) )
				{
					foreach( $rawData as $idx => $data )
					{
						if ( $data[ $fields[0] ] && is_array( $members[ $data[ $fields[0] ] ] ) )
						{
							$ip_addresses[ $tablename ][ $idx ] = array_merge( $data, $members[ $data[ $fields[0] ] ] );
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Here are your IPs kind sir.  kthxbai
		//-----------------------------------------

		return $ip_addresses;
	}
	
	/**
	 * Display a strip of share links
	 *
	 * @param	string		Document title (can be left blank and it will attempt to self-discover)
	 * @param	array		Addition params: url, cssClass, group [string template group], bit [string template bit], skip [array of share_keys to skip]
	 * @return	string		HAITHTEEEMEL
	 */
	static public function shareLinks( $title='', $params=array() )
	{
		$url      = isset( $params['url'] )         ? $params['url']         : '';
		$cssClass = isset( $params['cssClass'] )    ? $params['cssClass']    : 'topic_share left';
		$group    = isset( $params['group'] )       ? $params['group']       : 'global';
		$bit      = isset( $params['bit'] )         ? $params['bit']         : 'shareLinks';
		$skip     = isset( $params['skip'] )        ? $params['skip']        : array();
		$override = isset( $params['overrideApp'] ) ? $params['overrideApp'] : '';
		
		/* Disabled? */
		if ( ! ipsRegistry::$settings['sl_enable'] )
		{
			return '';
		}
		
		/* Disable for bots */
		if( ipsRegistry::member()->is_not_human )
		{
			return '';
		}
		
		$canon  = ipsRegistry::getClass('output')->fetchRootDocUrl();
		$url    = ( $url ) ? $url : ipsRegistry::$settings['this_url'];
		$raw    = $url;
		$canon  = IPSText::base64_encode_urlSafe( ( $canon ) ? $canon : $url );
		$title  = IPSText::base64_encode_urlSafe( $title );
		$url    = IPSText::base64_encode_urlSafe( $url );
		
		$cache = ipsRegistry::cache()->getCache('sharelinks');
	
		if ( ! $cache OR ! is_array( $cache ) )
		{
			ipsRegistry::cache()->rebuildCache('sharelinks', 'global' );
			$cache = ipsRegistry::cache()->getCache('sharelinks');
		}
		
		/* Check for required canonical urls or not */
		foreach( $cache as $key => $data )
		{
			if ( is_array( $skip ) AND in_array( $key, $skip ) )
			{
				unset( $cache[ $key ] );
			}
			else
			{
				$cache[ $key ]['_rawUrl']     = $raw;
				$cache[ $key ]['overrideApp'] = $override;
				$cache[ $key ]['_url']        = $data['share_canonical'] ? $canon : $url;
			}
		}
		
		return ipsRegistry::getClass('output')->getTemplate( $group )->$bit( $cache, $title, $canon, $cssClass );
	}
	
	/**
	 * Quick function to see if a string is serialized
	 * End up using something similar throughout the code
	 *
	 * @param	string	String to test
	 * @return	boolean
	 */
	static public function isSerialized( $string )
	{
    	if ( ! is_string( $string ) OR ! trim( $string ) )
    	{
    		return false;
    	} 
    	
    	if ( preg_match( "#^(i|s|a|o|d):(.*)#si", $string ) !== false )
    	{
    		return true;
    	}
    	
    	return false; 
	}
	
	/**
	 * Check for an IPv4 address
	 * 
	 * @param	string	IP address
	 * @return	boolean
	 */
	static public function validateIPv4( $IP ) 
	{
		preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $IP, $match );
		
	    return ( $match[0] ) ? true : false; 
	} 
	
	/**
	 * Check an IPv6 address
	 *
	 * @link	http://crisp.tweakblogs.net/blog/3049/ipv6-validation-more-caveats.html
	 * @param	string	IP address
	 * @return	boolean
	 */
	static public function validateIPv6( $IP ) 
	{ 
		if ( strlen($IP) < 3 )
	    {
	        return $IP == '::';
	    }
	
	    if ( strpos( $IP, '.' ) ) 
	    { 
	        $lastcolon = strrpos($IP, ':');
	         
	        if ( ! ( $lastcolon && self::validateIPv4( substr( $IP, $lastcolon + 1) ) ) )
	        {
	            return false; 
			}
			
	        $IP = substr( $IP, 0, $lastcolon ) . ':0:0'; 
	    } 
	
	    if ( strpos( $IP, '::' ) === false ) 
	    { 
	        return preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $IP); 
	    } 
	
	    $colonCount = substr_count($IP, ':');
	    
	    if ( $colonCount < 8 ) 
	    { 
	        return preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $IP); 
	    } 
	
	    // special case with ending or starting double colon 
	    if ( $colonCount == 8 ) 
	    { 
	        return preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $IP); 
	    } 
	
	    return false; 
	}
	
	/**
	 * Has an active license or not
	 * @param	boolean	Allow perpetual license to count.
	 * @return boolean
	 */
	static public function hasActiveLicense( $allowPerpetual=true )
	{
		//return true;
		$licenseData = ipsRegistry::cache()->getCache( 'licenseData' );

		if ( ( ! $licenseData OR ! $licenseData['key']['_expires'] OR $licenseData['key']['_expires'] < IPS_UNIX_TIME_NOW OR $licenseData['key']['_expires'] == 9999999999 ) )
		{
			return false;
		}
		
		if ( $allowPerpetual !== true )
		{
			if ( stristr( $licenseData['name'], 'perpetual' ) )
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Is the archive DB on another server?
	 * @return boolean
	 */
	static public function isUsingRemoteArchiveDB()
	{
		return ( ipsRegistry::$settings['archive_remote_sql_database'] && ipsRegistry::$settings['archive_remote_sql_user'] ) ? true : false;
	}
}

/**
* IPSDebug
*
* Only useful when developing
*/
class IPSDebug
{
	/**
	 * Memory debug array
	 *
	 * @var		array 		Memory debug info
	 */
	static public $memory_debug = array();

	/**
	 * Messages
	 *
	 * @var		array 		Messages
	 */
	static protected $_messages = array();

	/**
	 * Turn off constructor
	 *
	 * @return	@e void
	 */
	private function __construct() {}

	/**
	 * Start time
	 *
	 * @var		integer		Start time
	 */
	static protected $_starttime;

	/**
	 * Add message
	 *
	 * @param	string
	 * @return	@e void
	 */
	static public function addMessage( $message )
	{
		self::$_messages[] = $message;
	}
	
	/**
	 * Send a FirePHP message
	 *
	 * @param	string	$method		Method to call
	 * @param	string	$vars		Parameters to pass
	 * @return	@e void
	 * @link	http://www.firephp.org/HQ/
	 */
	static public function fireBug( $method, $parameters=array() )
	{
		if ( IN_DEV )
		{
			try
			{
				if( !class_exists( 'FB' ) )
				{
					require_once( IPS_KERNEL_PATH . '/FirePHPCore/fb.php' );/*noLibHook*/
				}
				
				if( $method == 'registerExceptionHandler' )
				{
					$firephp = FirePHP::getInstance(true);
					$firephp->registerExceptionHandler();
				}
				
				if( $method == 'registerErrorHandler' )
				{
					$firephp = FirePHP::getInstance(true);
					$firephp->registerErrorHandler();
				}
	
				if( method_exists( 'FB', $method ) )
				{
					$function	= 'FB::' . $method;
	
					call_user_func_array( $function, $parameters );
				}
			}
			catch( Exception $e ) { }
		}
	}
	
	/**
	 * Prettify a debug backtrace
	 *
	 * @param	array		Data from backtrace
	 * @return	string
	 */
	static public function prettifyBackTrace( $debug )
	{
		$_dString = '';

		if ( is_array( $debug ) and count( $debug ) )
		{
			foreach( $debug as $idx => $data )
			{
				/* Remove non-essential items */
				if ( $data['class'] == 'dbMain' OR $data['class'] == 'ips_DBRegistry' OR $data['class'] == 'ipsRegistry' OR $data['class'] == 'ipsController' OR $data['class'] == 'ipsCommand' OR $data['class'] == 'db_driver_mysql' )
				{
					continue;
				}
				
				$_dbString[ $idx ] = array( 'file'     => $data['file'],
											'line'     => $data['line'],
											'function' => $data['function'],
											'class'    => $data['class'] );
			}
		}
		
		if ( count( $_dbString ) )
		{
			$_error_string .= "\n .--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------.";
			$_error_string .= "\n | File                                                                       | Function                                                                      | Line No.          |";
			$_error_string .= "\n |----------------------------------------------------------------------------+-------------------------------------------------------------------------------+-------------------|";
			
			foreach( $_dbString as $i => $data )
			{
				if ( defined('DOC_IPS_ROOT_PATH') )
				{
					$data['file'] = str_replace( DOC_IPS_ROOT_PATH, '', $data['file'] );
				}
				
				/* Reset */
				$data['func'] = "[" . $data['class'] . '].' . $data['function'];
				
				/* Pad right */
				$data['file'] = str_pad( $data['file'], 75 );
				$data['func'] = str_pad( $data['func'], 78 );
				$data['line'] = str_pad( $data['line'], 18 );
				
				$_error_string .= "\n | " . $data['file'] . "| " . $data['func'] . '| ' . $data['line'] . '|';
				$_error_string .= "\n '----------------------------------------------------------------------------+-------------------------------------------------------------------------------+-------------------'";
			}
		}
		
		return $_error_string;
	}
	
	/**
	 * Custom error handler
	 *
	 * @param	integer	Error number
	 * @param	string	Error string
	 * @param	string	Error file
	 * @param	string	Error line number
	 * @return	@e void
	 */
	static public function errorHandler( $errno, $errstr, $errfile, $errline )
	{
		/* Did we turn off errors with @? */
		if ( ! error_reporting() )
		{
			return;
		}
		
		/* Are we truly debugging? */
		if ( IPS_ERROR_CAPTURE === FALSE )
		{
			return;
		}

		$errfile = str_replace( @getcwd(), "", $errfile );
		$log	 = false;
		$message = "> [$errno] $errstr\n> > Line: $errline\n> > File: $errfile";
		
		/* What do we have? */
		switch ($errno)
		{
	  		case E_ERROR:
				$log = true;
	   			echo "<b>IPB ERROR</b> [$errno] $errstr (Line: $errline of $errfile)<br />\n";
	   			exit(1);
	   		break;
	  		case E_WARNING:
				$log = true;
	   			echo "<b>IPB WARNING</b> [$errno] $errstr (Line: $errline of $errfile)<br />\n";
	   		break;
			case E_NOTICE:
	   			$log = true;
	   		break;
	 		default:
				return FALSE;
	   			//Do nothing
	   		break;
		}
		
		/* Logging? */
		if ( $log )
		{
			if ( IPS_ERROR_CAPTURE === TRUE )
			{
				self::addLogMessage( $message, "phpNotices", false, true );
			}
			else
			{
				foreach( explode( ',', IPS_ERROR_CAPTURE ) as $class )
				{
					if ( preg_match( '#/' . preg_quote( $class, '#' ) . '\.#', $errfile ) )
					{
						self::addLogMessage( $message, "phpNotices", false, true );
						break;
					}
				}
			}
		}
	}
	
	/**
	 * Add a message to the log file
	 * Handy for __destruct stuff, etc
	 *
	 * @param	string	Message to add
	 * @param	string	Which file to add it to
	 * @param	mixed	False, or an array of vars to include in log
	 * @param	bool	Force log even if IPS_LOG_ALL is off - handy for on-the-fly debugging
	 * @param	bool	Unlink file before writing
	 * @return	@e void
	 */
	static public function addLogMessage( $message, $file='debugLog', $array=FALSE, $force=FALSE, $unlink=FALSE )
	{
		/* Make sure IN_DEV is on to prevent logs filling up where people forget to turn it off... */
		if ( ( defined( 'IPS_LOG_ALL' ) AND IPS_LOG_ALL === TRUE ) OR $force === TRUE )
		{
			if ( $unlink === TRUE )
			{
				@unlink( DOC_IPS_ROOT_PATH . 'cache/' . $file . '.cgi' );
			}
			
			/* Array to dump? */
			if ( is_array( $array ) )
			{
				$message .= "\n" . var_export( $array, TRUE );
			}
			
			$message = "\n" . str_repeat( '-', 80 ) . "\n> Time: " . time() . ' / ' . gmdate( 'r' ) . "\n> URL: " . $_SERVER['REQUEST_URI'] . "\n> " . $message;
			@file_put_contents( DOC_IPS_ROOT_PATH . 'cache/' . $file . '.cgi', $message, FILE_APPEND );
		}
	}

	/**
	 * Return messages
	 *
	 * @return 	array 		Stored messages
	 */
	static public function getMessages()
	{
		return self::$_messages;
	}

	/**
	 * Displays a templating error
	 * Only used when IN_DEV is on
	 *
	 * @param	string		Complete PHP error string
	 * @param	string		Text evaluated by PHP
	 * @return	@e void
	 */
	static public function showTemplateError( $errorText, $evalCode )
	{
		$output     = array();
		$count      = 0;
		$openDiv    = '<div style="width:95%;text-align:left; margin-auto; padding:10px; white-space:pre;border:1px solid black; background:#eee;font-family:\'Courier New\', Courier, Geneva;font-size:0.8em">';
		$lineNumber = 0;

		/* Convert text into lines */
		$evalCode = preg_replace( "#\r#", "\n", $evalCode );
		$lines    = explode( "\n", $evalCode );

		if ( count( $lines ) )
		{
			foreach( $lines as $l )
			{
				$count++;
				$output[ $count ] = htmlspecialchars($l);
			}
		}

		/* Anything we can deal with? */
		if ( strstr( $errorText, "eval()'d code" ) )
		{
			preg_match( '#eval\(\)\'d code</b> on line <b>(\d+?)</b>#', $errorText, $matches );

			if ( $matches[1] )
			{
				$lineNumber = $matches[1];
				$output[ $lineNumber ] = "<span style='background:yellow;color:red;font-weight:bold'>" . $output[ $lineNumber ] . "</span>";

				if ( $lineNumber > 20 )
				{
					$_lineNumber = $lineNumber - 20;
					$output[ $_lineNumber ] = "<a name='line{$lineNumber}'></a>" . $output[ $_lineNumber ];
				}
			}
		}

		if ( count( $output ) )
		{
			if ( $lineNumber )
			{
				print "<h4>Parse Error on line: <a href='#line{$lineNumber}'>" . $lineNumber . "</a></h4>";
			}
			else
			{
				print "<h4>" . $errorText . "</h4>";
			}

			print $openDiv;

			foreach( $output as $number => $data )
			{
				print "<span style='color:#BBB'>".$number."</span>" . ' : ' . $data . "<br />";
			}

			print "</div>";

			exit();
		}

		/* Still here? */
		print "<h4>" . $errorText . "</h4>";
		print htmlspecialchars( $evalCode );
		exit();
	}

	/**
	 * Get current memory usage
	 *
	 * @return	integer		Current memory usage
	 */
	static public function getMemoryDebugFlag()
	{
		if ( IPS_MEMORY_START AND function_exists( 'memory_get_usage' ) )
		{
			return memory_get_usage();
		}
	}

	/**
	 * Set a memory debug flag
	 *
	 * @param 	string		Comment to set
	 * @param	integer		Memory usage to compare against
	 * @return	int			Memory used
	 */
	static public function setMemoryDebugFlag( $comment, $init_usage=0 )
	{
		if ( IPS_MEMORY_START AND function_exists( 'memory_get_usage' ) )
		{
			$_END  = memory_get_usage();
			$_USED = $_END - $init_usage;
			self::$memory_debug[] = array( $comment, $_USED );
			return $_USED;
		}
	}

	/**
	 * Start a timer
	 *
	 * @return	@e void
	 */
	static public function startTimer()
    {
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        self::$_starttime = $mtime;
    }

	/**
	 * Stop the timer
	 *
	 * @return	integer		Length of time
	 */
    static public function endTimer()
    {
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = round (($endtime - self::$_starttime), 5);
        return $totaltime;
    }

	/**
	 * Start a timer (return value instead of storing locally)
	 *
	 * @return	integer		Time
	 */
	static public function startTimerInstance()
    {
        $mtime = microtime ( true );
        $mtime = explode (' ', $mtime);
        $mtime = isset( $mtime[1] ) ? $mtime[1] + $mtime[0] : $mtime[0];
        return $mtime;
    }

	/**
	 * Stop the timer (compare against provided time instead of stored time)
	 *
	 * @param	integer		Start time
	 * @return	integer		Length of time
	 */
    static public function endTimerInstance( $startTime=0 )
    {
        $mtime = microtime ( true );
        $mtime = explode (' ', $mtime);
        $mtime = isset( $mtime[1] ) ? $mtime[1] + $mtime[0] : $mtime[0];
        $endtime = $mtime;

        $totaltime = round (($endtime - $startTime), 5);
        return $totaltime;
    }

	/**
	 * Retrieve server load and update cache if appropriate
	 *
	 * @return	string	Server load
	 */
	static public function getServerLoad()
	{
		$load_limit			= "--";
		
		//-----------------------------------------
		// Check cache first...
		//-----------------------------------------
        
        $cache	= ipsRegistry::instance()->cache()->getCache('systemvars');

        if( $cache['loadlimit'] )
        {
	        $loadinfo	= explode( "-", $cache['loadlimit'] );
	        
	        if ( intval($loadinfo[1]) > (time() - 30) )
	        {
				//-----------------------------------------
				// Cache is less than 30 secs old, use it
				//-----------------------------------------

		        $server_load_found	= 1;
		        $load_limit			= $loadinfo[0];
			}
		}
	        
		//-----------------------------------------
		// No cache or it's old, check real time
		//-----------------------------------------
		
		if( !$server_load_found )
		{
	        # @ supressor stops warning in > 4.3.2 with open_basedir restrictions
	        
        	if ( @file_exists('/proc/loadavg') )
        	{
        		if ( $fh = @fopen( '/proc/loadavg', 'r' ) )
        		{
        			$data = @fread( $fh, 6 );

        			@fclose( $fh );
        			
        			$load_avg	= explode( " ", $data );
        			$load_limit	= trim($load_avg[0]);
        		}
        	}
        	else if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
        	{
		        /*---------------------------------------------------------------
		        | typeperf is an exe program that is included with Win NT,
		        |	XP Pro, and 2K3 Server.  It can be installed on 2K from the
		        |	2K Resource kit.  It will return the real time processor
		        |	Percentage, but will take 1 second processing time to do so.
		        |	This is why we shall cache it, and check only every 2 mins.
		        |
		        |	Can also be obtained from COM, but it's extremely slow...
		        ---------------------------------------------------------------*/
	        	
	        	$serverstats = @shell_exec('typeperf "Processor(_Total)\% Processor Time" -sc 1');
	        	
	        	if( $serverstats )
	        	{
					$server_reply	= explode( "\n", str_replace( "\r", "", $serverstats ) );
					$serverstats	= array_slice( $server_reply, 2, 1 );
					$statline		= explode( ",", str_replace( '"', '', $serverstats[0] ) );
					$load_limit		= round( $statline[1], 4 );
				}
			}
        	else
        	{
				if ( $serverstats = @exec("uptime") )
				{
					preg_match( '/(?:averages)?\: ([0-9\.]+)(,|)[\s]+([0-9\.]+)(,|)[\s]+([0-9\.]+)/', $serverstats, $load );

					$load_limit = $load[1];
				}
			}
			
			$cache['loadlimit']	= $load_limit . "-" . time();
			
			if( $load_limit )
			{
				ipsRegistry::instance()->cache()->setCache( 'systemvars', $cache, array( 'array' => 1 ) );
			}
		}
		
		return $load_limit;
	}
}

/**
* IPSCookie
*
* This deals with saving and writing cookies
*/
class IPSCookie
{
	/**
	 * Sensitive cookies
	 *
	 * @var		array 		Sensitive cookies
	 */
	static public $sensitive_cookies = array();
	
	/**
	 * Handle cookies internally
	 * so that when you SET one it is available to GET in the same process
	 *
	 * @var		array
	 */
	static protected $_cookiesSet = array();

    /**
	 * Set a cookie.
	 *
	 * Abstract layer allows us to do some checking, etc
	 *
	 * @param	string		Cookie name
	 * @param	string		Cookie value
	 * @param	integer		Is sticky flag
	 * @param	integer		Number of days to expire cookie in
	 * @return	@e void
	 * @since	2.0
	 */
    static public function set( $name, $value="", $sticky=1, $expires_x_days=0 )
    {
		//-----------------------------------------
		// Check
		//-----------------------------------------

        if ( !empty( ipsRegistry::$settings['no_print_header'] ) )
        {
        	return;
        }
		
		/* Update internal array */
		self::$_cookiesSet[ $name ] = $value;
		
		//-----------------------------------------
		// Auto serialize arrays
		//-----------------------------------------

		if ( is_array( $value ) )
		{
			$value = serialize( $value );
		}

		//-----------------------------------------
		// Set vars
		//-----------------------------------------

        if ( $sticky == 1 )
        {
        	$expires = time() + 60*60*24*365;
        }
		else if ( $expires_x_days )
		{
			$expires = time() + ( $expires_x_days * 86400 );
		}
		else
		{
			$expires = FALSE;
		}

		//-----------------------------------------
		// Finish up...
		//-----------------------------------------

        ipsRegistry::$settings['cookie_domain'] =  ipsRegistry::$settings['cookie_domain'] == "" ? ""  : ipsRegistry::$settings['cookie_domain'] ;
        ipsRegistry::$settings['cookie_path'] =  ipsRegistry::$settings['cookie_path']   == "" ? "/" : ipsRegistry::$settings['cookie_path'] ;

		//-----------------------------------------
		// Set the cookie
		//-----------------------------------------

		if ( in_array( $name, self::$sensitive_cookies ) )
		{
			if ( PHP_VERSION < 5.2 )
			{
				if ( ipsRegistry::$settings['cookie_domain'] )
				{
					@setcookie( ipsRegistry::$settings['cookie_id'].$name, $value, $expires, ipsRegistry::$settings['cookie_path'], ipsRegistry::$settings['cookie_domain'] . '; HttpOnly' );
				}
				else
				{
					@setcookie( ipsRegistry::$settings['cookie_id'].$name, $value, $expires, ipsRegistry::$settings['cookie_path'] );
				}
			}
			else
			{
				@setcookie( ipsRegistry::$settings['cookie_id'].$name, $value, $expires, ipsRegistry::$settings['cookie_path'], ipsRegistry::$settings['cookie_domain'], NULL, TRUE );
			}
		}
		else
		{
			@setcookie( ipsRegistry::$settings['cookie_id'].$name, $value, $expires, ipsRegistry::$settings['cookie_path'], ipsRegistry::$settings['cookie_domain']);
		}
    }

    /**
	 * Get a cookie.
	 * Abstract layer allows us to do some checking, etc
	 *
	 * @param	string		Cookie name
	 * @return	mixed
	 * @since	2.0
	 */
    static public function get($name)
    {
		/* Check internal data first */
		if ( isset( self::$_cookiesSet[ $name ] ) )
		{
			return self::$_cookiesSet[ $name ];
		}
    	else if ( isset( $_COOKIE[ipsRegistry::$settings['cookie_id'].$name] ) )
    	{
			$_value = $_COOKIE[ ipsRegistry::$settings['cookie_id'].$name ];

    		if ( substr( $_value, 0, 2 ) == 'a:' )
    		{
				return unserialize( stripslashes( urldecode( $_value ) ) );
    		}
    		else
    		{
				return IPSText::parseCleanValue( urldecode( $_value ) );
    		}
    	}
    	else
    	{
    		return FALSE;
    	}
    }
}

/**
* IPSText
*
* This deals with cleaning and parsing text items.
*/
class IPSText
{
	/**
	 * Class Convert Object
	 *
	 * @var		object
	 */
	static protected $classConvertCharset;

	/**
	 * Default document character set
	 *
	 * @var		string		Character set
	 */
	static public $gb_char_set = 'UTF-8';

	/**
	 * Remove dodgy control characters?
	 *
	 * @var		boolean		Remove emulated spaces (e.g. alt+160)
	 */
	static public $strip_space_chr = true;

	/**
	 * Classes
	 *
	 * @var		array
	 */
	static protected $_internalClasses = array();

	/**
	 * Ensure no one can create this as an object
	 *
	 * @return	@e void
	 */
	private function __construct() {}

	/**
	 * Cleans/gets a file extension
	 *
	 * @param string $file
	 * @return string
	 */
	static public function getFileExtension( $string )
	{
		return ( strstr( $string, '.' ) ) ? strtolower( str_replace( ".", "", substr( $string, strrpos( $string, '.' ) ) ) ) : strtolower( $string );
	}

	/**
	 * Unconvert smilies
	 *
	 * @param	string		Raw text
	 * @return	string		Converted text
	 */
	public static function unconvertSmilies( $txt )
	{
		//-----------------------------------------
		// Unconvert smilies
		//-----------------------------------------

		$txt = str_replace( "<#EMO_DIR#>", "&lt;#EMO_DIR&gt;", $txt );

		preg_match_all( "#(<img(?:[^>]+?)class=['\"]bbc_emoticon[\"'](?:[^>]+?)alt=['\"](.+?)[\"'](?:[^>]+?)?>)#is", $txt, $matches );

		if( is_array($matches[1]) AND count($matches[1]) )
		{
			foreach( $matches[1] as $index => $value )
			{				
				if ( count( ipsRegistry::cache()->getCache('emoticons') ) > 0 )
				{
					foreach( ipsRegistry::cache()->getCache('emoticons') as $row )
					{
						$_emoCode = str_replace( '<', '&lt;', str_replace( '>', '&gt;', $row['typed'] ) );
						
						if( $matches[2][ $index ] == $_emoCode )
						{
							/* We need to make sure emoticons are wrapped in spaces so they are parsed properly */
							//$txt = str_replace( $value, ' ' . $_emoCode . ' ', $txt );
							/* We are no longer matching opening/closing "space" so no need to add it */
							$txt = str_replace( $value, $_emoCode, $txt );
							continue 2;
						}
					}
				}
			}
		}

		$txt = str_replace( "&lt;#EMO_DIR&gt;", "<#EMO_DIR#>", $txt );
		
		return $txt;
	}
	
	/**
	 * Simple JSON encode for when its not possible to convert data
	 * into UTF-8 (for example polls that display the contents, etc)
	 * This should only used for light lifting.
	 *
	 * @param	array   Simple array
	 * @return	object
	 */
	static public function simpleJsonEncode( $array )
	{
		$final = array();

		if ( is_array( $array ) )
		{
			foreach( $array as $k => $v )
			{
				$k = str_replace( '"', '\"', $k );
				
				if ( is_array( $v ) )
				{
					$v = self::simpleJsonEncode( $v );
				}
				else
				{
					$v = str_replace( '"', '\"', $v );
					$v = str_replace( "\n", '\n', str_replace( "\r", '', $v ) );
					$v = '"' . $v . '"';
				}
				
				$final[] = '"' . $k . '":' . $v . '';
			}
			
			return '{' . implode( ",", $final ) . '}';
		}
	}
	
	static public function jsonEncodeForTemplate( $data )
	{
		/* Using UTF-8 - it's an easy thing */
		if ( IPS_DOC_CHAR_SET == 'UTF-8' )
		{
			return json_encode( $data );
		}
		
		/* convert */
		array_walk_recursive( $data, array( 'IPSText', 'arrayWalkCallbackConvert' ) );
		$jsonEncoded = json_encode( $data );
		
		return IPSText::convertCharsets( $jsonEncoded, "UTF-8", IPS_DOC_CHAR_SET );
	}
	
	/**
	 * Get helper classes
	 * Used here to allow classes to be loaded and used as-and-when they're needed
	 *
	 * @param	mixed		Name of item requested
	 * @return	object
	 */
	static public function getTextClass( $name )
	{
		if ( isset( self::$_internalClasses[ $name ] ) && is_object( self::$_internalClasses[ $name ] ) )
		{
			return self::$_internalClasses[ $name ];
		}
		else
		{
			switch( $name )
			{
				default:
				case 'bbcode':
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/handlers/han_parse_bbcode.php", 'parseBbcode' );
			        $_class                      =  new $classToLoad( ipsRegistry::instance() );
			        $_class->allow_update_caches = 1;
			        $_class->bypass_badwords     = ipsRegistry::instance()->member() ? intval( ipsRegistry::instance()->member()->getProperty('g_bypass_badwords') ) : 0;
				break;
				case 'editor':
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/handlers/han_editor.php", 'hanEditor' );
					$_class = new $classToLoad( ipsRegistry::instance() );
			        $_class->init();
				break;
				case 'email':
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/handlers/han_email.php", 'hanEmail' );
					$_class = new $classToLoad( ipsRegistry::instance() );
			        $_class->init();
				break;
			}

			if ( is_object( $_class ) )
			{
				self::$_internalClasses[ $name ] = $_class;

				return self::$_internalClasses[ $name ];
			}
		}
	}
	
	/**
	 * Encode for saving data into the DB that will be exported to XML
	 *
	 * Mostly used to ensure that data designed for UTF-8 XML files is actually stored as UTF-8 from
	 * 'flat' files that may not be saved as UTF-8.
	 *
	 * @param	string		Data in
	 * @return	string		Data out
	 */
	static public function encodeForXml( $string )
	{
		if ( function_exists( 'mb_detect_encoding' ) )
		{
			$encoding = mb_detect_encoding( $string );
			if ( $encoding != 'UTF-8' )
			{
				$string = IPSText::convertCharsets( $string, $encoding );
			}
		}
		elseif ( strtolower( IPS_DOC_CHAR_SET ) == 'utf-8' )
		{
			$string = utf8_encode( $string );
		}
		
		return $string;
	}

	/**
	 * Make an SEO title for use in the URL
	 * We parse them even if friendly urls are off so that the data is there when you do switch it on
	 *
	 * @param	string		Raw SEO title or text
	 * @return	string		Cleaned up SEO title
	 */
	static public function makeSeoTitle( $text )
	{
		if ( ! $text )
		{
			return '';
		}

		/* Strip all HTML tags first */
		$text = strip_tags($text);
			
		/* Preserve %data */
		$text = preg_replace('#%([a-fA-F0-9][a-fA-F0-9])#', '-xx-$1-xx-', $text);
		$text = str_replace( array( '%', '`' ), '', $text);
		$text = preg_replace('#-xx-([a-fA-F0-9][a-fA-F0-9])-xx-#', '%$1', $text);

		/* Convert accented chars */
		$text = self::convertAccents($text);
		
		/* Convert it */
		if ( self::isUTF8( $text )  )
		{
			if ( function_exists('mb_strtolower') )
			{
				$text = mb_strtolower($text, 'UTF-8');
			}

			$text = self::utf8Encode( $text, 250 );
		}

		/* Finish off */
		$text = strtolower($text);
		
		if ( strtolower( IPS_DOC_CHAR_SET ) == 'utf-8' )
		{
			$text = preg_replace( '#&.+?;#'        , '', $text );
			$text = preg_replace( '#[^%a-z0-9 _-]#', '', $text );
		}
		else
		{
			/* Remove &#xx; and &#xxx; but keep &#xxxx; */
			$text = preg_replace( '/&#(\d){2,3};/', '', $text );
			$text = preg_replace( '#[^%&\#;a-z0-9 _-]#', '', $text );
			$text = str_replace( array( '&quot;', '&amp;'), '', $text );
		}
		
		$text = str_replace( array( '`', ' ', '+', '.', '?', '_', '#' ), '-', $text );
		$text = preg_replace( "#-{2,}#", '-', $text );
		$text = trim($text, '-');

		IPSDebug::addMessage( "<span style='color:red'>makeSeoTitle ($text) called</span>" );
		
		return ( $text ) ? $text : '-';
	}

	/**
	 * Seems like UTF-8?
	 * hmdker at gmail dot com {@link php.net/utf8_encode}
	 *
	 * @param	string		Raw text
	 * @return	boolean
	 */
	static public function isUTF8($str) {
	    $c=0; $b=0;
	    $bits=0;
	    $len=strlen($str);
	    for($i=0; $i<$len; $i++)
	    {
	        $c=ord($str[$i]);

	        if($c > 128)
	        {
	            if(($c >= 254)) return false;
	            elseif($c >= 252) $bits=6;
	            elseif($c >= 248) $bits=5;
	            elseif($c >= 240) $bits=4;
	            elseif($c >= 224) $bits=3;
	            elseif($c >= 192) $bits=2;
	            else return false;

	            if(($i+$bits) > $len) return false;

	            while( $bits > 1 )
	            {
	                $i++;
	                $b = ord($str[$i]);
	                if($b < 128 || $b > 191) return false;
	                $bits--;
	            }
	        }
	    }

	    return true;
	}

	/**
	 * Converts accented characters into their plain alphabetic counterparts
	 *
	 * @param	string		Raw text
	 * @return	string		Cleaned text
	 */
	static public function convertAccents($string)
	{
		if ( ! preg_match('/[\x80-\xff]/', $string) )
		{
			return $string;
		}

		if ( self::isUTF8( $string) )
		{
			$_chr = array(
							/* Latin-1 Supplement */
							chr(195).chr(128) => 'Ae', chr(195).chr(129) => 'A',
							chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
							chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
							chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
							chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
							chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
							chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
							chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
							chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
							chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
							chr(195).chr(150) => 'Oe', chr(195).chr(153) => 'U',
							chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
							chr(195).chr(156) => 'Ue', chr(195).chr(157) => 'Y',
							chr(195).chr(159) => 'ss', chr(195).chr(160) => 'a',
							chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
							chr(195).chr(163) => 'a', chr(195).chr(164) => 'ae',
							chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
							chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
							chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
							chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
							chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
							chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
							chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
							chr(195).chr(181) => 'o', chr(195).chr(182) => 'oe',
							chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
							chr(195).chr(187) => 'u', chr(195).chr(188) => 'ue',
							chr(195).chr(189) => 'y', chr(195).chr(191) => 'y',
							/* Latin Extended-A */
							chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
							chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
							chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
							chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
							chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
							chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
							chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
							chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
							chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
							chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
							chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
							chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
							chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
							chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
							chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
							chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
							chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
							chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
							chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
							chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
							chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
							chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
							chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
							chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
							chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
							chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
							chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
							chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
							chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
							chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
							chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
							chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
							chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
							chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
							chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
							chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
							chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
							chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
							chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
							chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
							chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
							chr(197).chr(146) => 'OE', chr(197).chr(147) => 'oe',
							chr(197).chr(148) => 'R', chr(197).chr(149) => 'r',
							chr(197).chr(150) => 'R', chr(197).chr(151) => 'r',
							chr(197).chr(152) => 'R', chr(197).chr(153) => 'r',
							chr(197).chr(154) => 'S', chr(197).chr(155) => 's',
							chr(197).chr(156) => 'S', chr(197).chr(157) => 's',
							chr(197).chr(158) => 'S', chr(197).chr(159) => 's',
							chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
							chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
							chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
							chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
							chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
							chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
							chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
							chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
							chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
							chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
							chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
							chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
							chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
							chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
							chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
							chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
							/* Euro Sign */
							chr(226).chr(130).chr(172) => 'E',
							/* GBP (Pound) Sign */
							chr(194).chr(163) => '' );

			$string = strtr($string, $_chr);
		}
		else
		{
			$_chr      = array();
			$_dblChars = array();
			
			/* We assume ISO-8859-1 if not UTF-8 */
			$_chr['in'] =   chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
							.chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
							.chr(195).chr(199).chr(200).chr(201).chr(202)
							.chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
							.chr(211).chr(212).chr(213).chr(217).chr(218)
							.chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
							.chr(231).chr(232).chr(233).chr(234).chr(235)
							.chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
							.chr(244).chr(245).chr(249).chr(250).chr(251)
							.chr(252).chr(253).chr(255).chr(191).chr(182).chr(179).chr(166)
							.chr(230).chr(198).chr(175).chr(172).chr(188)
							.chr(163).chr(161).chr(177);

			$_chr['out'] = "EfSZszYcYuAAAACEEEEIIIINOOOOUUUUYaaaaceeeeiiiinoooouuuuyyzslScCZZzLAa";

			$string           = strtr( $string, $_chr['in'], $_chr['out'] );
			$_dblChars['in']  = array( chr(140), chr(156), chr(196), chr(197), chr(198), chr(208), chr(214), chr(216), chr(222), chr(223), chr(228), chr(229), chr(230), chr(240), chr(246), chr(248), chr(254));
			$_dblChars['out'] = array('Oe', 'oe', 'Ae', 'Aa', 'Ae', 'DH', 'Oe', 'Oe', 'TH', 'ss', 'ae', 'aa', 'ae', 'dh', 'oe', 'oe', 'th');
			$string           = str_replace($_dblChars['in'], $_dblChars['out'], $string);
		}
				
		return $string;
	}

	/**
	 * Manually utf8 encode to a specific length
	 * Based on notes found at php.net
	 *
	 * @param	string		Raw text
	 * @param	int			Length
	 * @return	string
	 */
	static public function utf8Encode( $string, $len=0 )
	{
		$_unicode       = '';
		$_values        = array();
		$_nOctets       = 1;
		$_unicodeLength = 0;
 		$stringLength   = strlen( $string );

		for ( $i = 0 ; $i < $stringLength ; $i++ )
		{
			$value = ord( $string[ $i ] );

			if ( $value < 128 )
			{
				if ( $len && ( $_unicodeLength >= $len ) )
				{
					break;
				}

				$_unicode .= chr($value);
				$_unicodeLength++;
			}
			else
			{
				if ( count( $_values ) == 0 )
				{
					$_nOctets = ( $value < 224 ) ? 2 : 3;
				}

				$_values[] = $value;

				if ( $len && ( $_unicodeLength + ($_nOctets * 3) ) > $len )
				{
					break;
				}

				if ( count( $_values ) == $_nOctets )
				{
					if ( $_nOctets == 3 )
					{
						$_unicode .= '%' . dechex($_values[0]) . '%' . dechex($_values[1]) . '%' . dechex($_values[2]);
						$_unicodeLength += 9;
					}
					else
					{
						$_unicode .= '%' . dechex($_values[0]) . '%' . dechex($_values[1]);
						$_unicodeLength += 6;
					}

					$_values  = array();
					$_nOctets = 1;
				}
			}
		}

		return $_unicode;
	}
	
	/**
	 * Converts UTF-8 into HTML entities (&#1xxx;) for correct display in browsers
	 *
	 * @param	 string 		UTF8 Encoded string
	 * @return	 string 		..converted into HTML entities (similar to what a browser does with POST)
	 */
	public static function utf8ToEntities($string)
	{ 
		/*
 		 * @see http://en.wikipedia.org/wiki/UTF-8#Description
 		 * @link http://community.invisionpower.com/tracker/issue-23681-possible-addition/
 		 */
		# Four-byte chars
		$string = preg_replace( "/([\360-\364])([\200-\277])([\200-\277])([\200-\277])/e",  "'&#' . ( ( ord('\\1') - 240 ) * 262144 + ( ord('\\2') - 128 ) * 4096 + ( ord('\\3') - 128 ) * 64 + ( ord('\\4') - 128 ) ) . ';'", $string );
        
    	/* Three byte chars */
		$string = preg_replace( "/([\340-\357])([\200-\277])([\200-\277])/e", "'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'", $string ); 

    	/* Two byte chars */
		$string = preg_replace("/([\300-\337])([\200-\277])/e", "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'", $string); 

    	return $string; 
	}
	
	/**
	 * Strips out all non UTF-8 characters from a string
	 * This is best used when you have already converted / got UTF-8 data
	 * @param	string	In
	 * @return	string	Cleaned
	 */
	public static function stripNonUtf8( $string )
	{
		$string = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]|(?<=^|[\x00-\x7F])[\x80-\xBF]+|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
							   '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/', '?', $string );
		
		
		$string = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]|\xED[\xA0-\xBF][\x80-\xBF]/S','?', $string );
		
		return $string;
	}
	
	/**
	 * Decodes long named HTML entities in a string &eacute; without affecting other HTML entities (&lt; etc) etc
	 * @param string 	Raw string
	 * @param string	Converted string
	 */
	public static function decodeNamedHtmlEntities( $string )
	{
		/* Some manual conversions */
		$manual = array( 'Alpha' => 'A', 'Beta' => 'B', 'alpha' => 'a', 'beta' => 'b' );
		
		foreach( $manual as $o => $r )
		{
			$string = str_replace( '&' . $o . ';', $r, $string );
		}
		
		preg_match_all( '#&([^;\s]+?);#', $string, $matches );
		$entities = array();
		$notList  = array( 'amp', 'para', 'middot', 'gt', 'lt' );
		
		foreach( $matches[0] as $word )
		{
			if ( preg_match( '#^&([a-zA-Z]{2,8});$#', $word, $match ) )
			{
				if ( ! in_array( $match[1], $notList ) )
				{
					$entities[] = $word;
				}
			}
		}

		/* -15 is the same as -1 but with Euro plus French and Finnish */
		$charSet    = ( IPS_DOC_CHAR_SET == 'ISO-8859-1' ) ? 'ISO-8859-15' : IPS_DOC_CHAR_SET;
		$translated = @explode( ' ', @html_entity_decode( @implode( ' ', $entities ), ENT_NOQUOTES, $charSet ) );

		if ( is_array( $translated ) AND count( $translated ) AND count( $translated ) == count( $entities ) )
		{
			return str_replace( $entities, $translated, $string );
		}
		
		return $string;
	}
	
	
	/**
	 * Returns an MD5 hash of content which has whitespace stripped.
	 * This is used in some classes to determine if content has changed without
	 * whitespace changes triggering it.
	 *
	 * @param	string 		Incoming text
	 * @return	string		MD5 hash of whitespace stripped content
	 */
	public static function contentToMd5( $t )
	{
		return md5( trim( preg_replace( '#[\s\t\n\r]#', "", $t ) ) );
	}

	/**
	 * Replace Recursively
	 *
	 * @param	string		Text to search in
	 * @param	string		Opening text to search for. (Example: <a href=)
	 * @param	string		Closing text to search for. (Example: >)
	 * @param	mixed		Call back function that handles the replacement. If using a class, pass array( $classname, $function ) THIS MUST BE A STATIC FUNCTION
	 * @return	string		Replaced text
	 * <code>
	 * # We want to replace all instances of <a href="http://www.domain.com"> with <a href="javascript:goLoad('domain.com')">
	 * $text = IPSText::replaceRecursively( $text, "<a href=", ">", array( 'myClass', 'replaceIt' ) );
	 * class myClass {
	 *	static function replaceIt( $text, $openText, $closeText )
	 *	{
	 *		# $text contains the matched text between the tags, eg: "http://www.domain.com"
	 *		# $openText contains the searched for opening, eg: <a href
	 *		# $closeText contains the searched for closing, eg: >
	 *		# Remove http...
	 *		$text = str_replace( 'http://', '', $text )
	 *		# Remove quotes
	 * 		$text = str_replace( array( '"', "'" ), '', $text );
	 *		return '"javascript:goLoad(\'' . $text . '\')"';
	 *	}
	 * }
	 * </code>
	 */
	public static function replaceRecursively( $text, $textOpen, $textClose, $callBackFunction )
	{
		//----------------------------------------
		// INIT
		//----------------------------------------

		# Tag specifics
		$foundOpenText_pointer  = 0;
		$foundCloseText_pointer = 0;
		$foundOpenTextRecurse_pointer = 0;

		//----------------------------------------
		// Keep the server busy for a while
		//----------------------------------------

		while ( 1 == 1 )
		{
			# Reset pointer
			$startOfTextAfterOpenText_pointer = 0;

			# See if we have any 'textOpen' at all
			$foundOpenText_pointer = strpos( $text, $textOpen, $foundCloseText_pointer );

			# No?
			if ( $foundOpenText_pointer === FALSE )
			{
				break;
			}

			# Do we have any close text?
			$foundCloseText_pointer = strpos( $text, $textClose, $foundOpenText_pointer );

			# No?
			if ( $foundCloseText_pointer === FALSE )
			{
				return $text;
			}

			# Reset pointer for text between the open and close text
			$startOfTextAfterOpenText_pointer = $foundOpenText_pointer + strlen( $textOpen );

			# Check recursively
			$foundOpenTextRecurse_pointer = $startOfTextAfterOpenText_pointer;

			while ( 1 == 1 )
			{
				# Got any open text again?
				$foundOpenTextRecurse_pointer = strpos( $text, $textOpen, $foundOpenTextRecurse_pointer );

				# No?
				if ( $foundOpenTextRecurse_pointer === FALSE OR $foundOpenTextRecurse_pointer >= $foundCloseText_pointer )
				{
					break;
				}

				# Yes! Reset recursive pointer
				$foundCloseTextRecurse_pointer = $foundCloseText_pointer + strlen( $textClose );

				# Yes! Reset close normal pointer to next close tag FROM the last found close point
				$foundCloseText_pointer = strpos( $text, $textClose, $foundCloseTextRecurse_pointer );

				# Make sure we have a closing text
				if ( $foundCloseText_pointer === FALSE )
				{
					return $text;
				}

				$foundOpenTextRecurse_pointer += strlen( $textOpen );
			}

			# This is the text between the open text and close text
			$foundText  = substr( $text, $startOfTextAfterOpenText_pointer, $foundCloseText_pointer - $startOfTextAfterOpenText_pointer );

			# Recurse
			if ( strpos( $foundText, $textOpen ) !== FALSE )
			{
				$foundText = IPSText::replaceRecursively( $foundText, $textOpen, $textClose, $callBackFunction );
			}

			# Run the call back...
			$_newText  = call_user_func( $callBackFunction, $foundText, $textOpen, $textClose );

			# Run the replacement
			$text = substr_replace( $text, $_newText, $foundOpenText_pointer, ( $foundCloseText_pointer - $foundOpenText_pointer ) + strlen( $textClose )  );

			# Reset pointer
			$foundCloseText_pointer = $foundOpenText_pointer + strlen($_newText);
		}

		return $text;
	}

	/**
	 * Reset Text Classes
	 *
	 * @param	string		Classname to search for
	 * @return	boolean		True if successful, false if not
	 */
	static public function resetTextClass( $name )
	{
		if ( ! is_object( self::$_internalClasses[ $name ] ) )
		{
			return false;
		}

		switch( $name )
		{
			default:
			case 'bbcode':
				self::$_internalClasses[ $name ]->allow_cache_updates	= 1;
				self::$_internalClasses[ $name ]->bypass_badwords		= intval( ipsRegistry::instance()->member()->getProperty('g_bypass_badwords') );
				self::$_internalClasses[ $name ]->parse_smilies			= 1;
				self::$_internalClasses[ $name ]->parse_nl2br			= 1;
				self::$_internalClasses[ $name ]->parse_html			= 0;
				self::$_internalClasses[ $name ]->parse_bbcode			= 1;
				self::$_internalClasses[ $name ]->parsing_section		= 'post';
				self::$_internalClasses[ $name ]->error					= '';
				self::$_internalClasses[ $name ]->parsing_mgroup		= '';
				self::$_internalClasses[ $name ]->parsing_mgroup_others	= '';
			break;
			case 'editor':
				self::$_internalClasses[ $name ]->error = '';
			break;
		}

		return true;
	}

	/**
	 * Clean _GET _POST key
	 *
	 * @param	string		Key name
	 * @return	string		Cleaned key name
	 * @since	2.1
	 */
    static public function parseCleanKey($key)
    {
    	if ( $key == "" )
    	{
    		return "";
    	}

    	$key = htmlspecialchars( urldecode($key) );
    	$key = str_replace( ".."           , ""  , $key );
    	$key = preg_replace( '/\_\_(.+?)\_\_/'  , ""  , $key );
    	$key = preg_replace( '/^([\w\.\-\_]+)$/', "$1", $key );

    	return $key;
    }

    /**
	 * Clean _GET _POST value
	 *
	 * @param	string		Input
	 * @param	bool		Also run postParseCleanValue
	 * @return	string		Cleaned Input
	 * @since	2.1
	 */
    static public function parseCleanValue( $val, $postParse=true )
    {
    	if ( $val == "" )
    	{
    		return "";
    	}

    	$val = str_replace( "&#032;", " ", IPSText::stripslashes($val) );

		# Convert all carriage return combos
		$val = str_replace( array( "\r\n", "\n\r", "\r" ), "\n", $val );

    	$val = str_replace( "&"				, "&amp;"         , $val );
    	$val = str_replace( "<!--"			, "&#60;&#33;--"  , $val );
    	$val = str_replace( "-->"			, "--&#62;"       , $val );
    	$val = str_ireplace( "<script"	    , "&#60;script"   , $val );
    	$val = str_replace( ">"				, "&gt;"          , $val );
    	$val = str_replace( "<"				, "&lt;"          , $val );
    	$val = str_replace( '"'				, "&quot;"        , $val );
    	$val = str_replace( "\n"			, "<br />"        , $val ); // Convert literal newlines
    	$val = str_replace( "$"				, "&#036;"        , $val );
    	$val = str_replace( "!"				, "&#33;"         , $val );
    	$val = str_replace( "'"				, "&#39;"         , $val ); // IMPORTANT: It helps to increase sql query safety.

    	if ( IPS_ALLOW_UNICODE )
		{
			$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val );

			//-----------------------------------------
			// Try and fix up HTML entities with missing ;
			//-----------------------------------------

			$val = preg_replace( '/&#(\d+?)([^\d;])/i', "&#\\1;\\2", $val );
		}
		
		//-----------------------------------------
		// Shortcut to auto run other cleaning
		//-----------------------------------------
		
		if( $postParse )
		{
			$val	= IPSText::postParseCleanValue( $val );
		}

    	return $val;
    }
    
    /**
	 * Clean _GET _POST value after settings loaded
	 *
	 * @param	string		Input
	 * @return	string		Cleaned Input
	 * @since	2.1
	 */
    static public function postParseCleanValue($val)
    {
    	if ( $val == "" )
    	{
    		return "";
    	}

		/* This looks wrong but it's correct. During FURL set up in registry this function is called before settings are loaded
		 * and we want to strip hidden chars in this instance, so.. */
    	if ( ! isset( ipsRegistry::$settings['strip_space_chr'] ) OR ipsRegistry::$settings['strip_space_chr'] )
    	{
			$val = IPSText::removeControlCharacters( $val );
    	}

    	return $val;
    }

	/**
	 * Check email address to see if it seems valid
	 *
	 * @param	string		Email address
	 * @return	boolean
	 * @since	2.0
	 */
	static public function checkEmailAddress( $email = "" )
	{
		$email = trim($email);

		$email = str_replace( " ", "", $email );

		//-----------------------------------------
		// Check for more than 1 @ symbol
		//-----------------------------------------

		if ( substr_count( $email, '@' ) > 1 )
		{
			return FALSE;
		}

    	if ( preg_match( '#[\;\#\n\r\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s\,]#', $email ) )
		{
			return FALSE;
		}
    	else if ( preg_match( '/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/', $email) )
    	{
    		return TRUE;
    	}
    	else
    	{
    		return FALSE;
    	}
	}
	
	/**
	 * Function to trim text around a word or phrase
	 *
	 * @param	string	$haystack	Text
	 * @param	string	$needle		Phrase
	 * @return	string
	 */
	static public function truncateTextAroundPhrase( $haystack, $needle )
	{
		/* Base on words */
		$haystack = explode( " ", $haystack );

		if( count( $haystack ) > 21 )
		{
			$_term_at = IPSLib::arraySearchLoose( $needle, $haystack );

			if( $_term_at - 11 > 0 )
			{
				$begin = array_splice( $haystack, 0, $_term_at - 11 );
				
				/* The term position will have changed now */
				$_term_at = IPSLib::arraySearchLoose( $needle, $haystack );
			}

			if( $_term_at + 11 < count( $haystack ) )
			{
				$end   = array_splice( $haystack, $_term_at + 11, count( $haystack ) );
			}
		}
		else
		{
			$begin = array();
			$end   = array();
		}

		$haystack = implode( " ", $haystack );
		
		if( is_array( $begin ) && count( $begin ) )
		{
			$haystack = '...' . $haystack;
		}
		
		if( is_array( $end ) && count( $end ) )
		{
			$haystack = $haystack . '...';
		}
		
		return $haystack;
	}
	
	/**
	 * Replaces text with highlighted blocks
	 *
	 * @param	string		Incoming Content
	 * @param	string		HL attribute
	 * @return	string		Formatted text
	 * @since	2.2.0
	 */
	static public function searchHighlight( $text, $highlight )
	{
		/* No highlight to do (1)? No point in wasting time then.. */
		if ( $highlight == '' )
		{
			return $text;
		}
		
		$highlight  = self::parseCleanValue( urldecode( $highlight ) );
		
		/* No highlight to do (2)? No point in wasting time then.. */
		if ( $highlight == '' )
		{
			return $text;
		}
		
		/* Init some more vars */
		$loosematch = 1;//strstr( $highlight, '*' ) ? 1 : 0;
		$isPhrase   = preg_match( '#("|&quot;)#', $highlight );
		$keywords   = str_replace( '*', '', str_replace( "+", " ", str_replace( "++", "+", str_replace( '-', '', trim($highlight) ) ) ) );
		$keywords	= str_replace( '&quot;', '', str_replace( '\\', '&#092;', str_replace( '&amp;quot;', '', $keywords ) ) );
		$word_array = array();
		$endmatch   = "(.)?";
		$beginmatch = "(.)?";

		//-----------------------------------------
		// Get rid of links first...
		//-----------------------------------------
		
		$_storedUrls = array();
		
		preg_match_all( "/<a href=['\"](.+?)[\"']([^>]*?)>/is", $text, $_urls );

		for ( $i = 0; $i < count($_urls[0]); $i++ )
		{
			$_bleh	= md5( uniqid( microtime(), true ) );
			
			$text	= str_replace( $_urls[0][$i], "--URL::{$_bleh}-- ", $text );
			
			$_storedUrls[ $_bleh ]	= $_urls[0][$i];
		}

		//-----------------------------------------
		// Go!
		//-----------------------------------------

		if ( $keywords )
		{
			if ( preg_match("/,(and|or),/i", $keywords) )
			{
				while ( preg_match('/\s+(and|or)\s+/i', $keywords, $match) )
				{
					$word_array = explode( " ".$match[1]." ",	$keywords );
					$keywords   = str_replace( $match[0], '',	$keywords );
				}
			}
			else if ( ! $isPhrase && strstr( $keywords, ' ' ) )
			{
				$word_array = explode( ' ', str_replace( '  ', ' ', $keywords ) );
			}
			else
			{
				$word_array[] = $keywords;
			}

			if ( ! $loosematch )
			{
				$beginmatch = '(^|\s|\>|;|\])';
				$endmatch   = '(\s|,|\.|!|<br|&|$)';
			}

			if ( is_array($word_array) )
			{
				/* We'll use this to match against, so we don't break images with the term in the image name */
				$textForMatch = strip_tags( IPSText::getTextClass( 'bbcode' )->stripAllTags( $text ) );
				
				foreach ( $word_array as $keywords )
				{
					/* We don't want to highlight small words, they're usually noise and it can produce memory errors with single chars being highlighted 
					 * Correction: We don't want to highlight them unless user used double quotes in search term */
					if( strpos( $highlight, '&amp;quot;' ) === false AND strlen( $keywords ) < ipsRegistry::$settings['min_search_word'] )
					{
						continue;
					}
					
					/* Make sure we're not trying to process an empty keyword */
					if( ! $keywords )
					{
						continue;
					}

					preg_match_all( "/{$beginmatch}(".preg_quote($keywords, '/')."){$endmatch}/is", $textForMatch, $matches );

					for ( $i = 0; $i < count($matches[0]); $i++ )
					{
						$text = str_ireplace( $matches[0][$i], $matches[1][$i] . "<span class='searchlite'>" . $matches[2][$i] . "</span>" . $matches[3][$i], $text );
					}
				}
			}
		}

		//-----------------------------------------
		// Fix links
		//-----------------------------------------
		
		if( count($_storedUrls) )
		{
			foreach( $_storedUrls as $k => $v )
			{
				$text	= str_replace( "--URL::{$k}-- ", $v, $text );
			}
		}

		return $text;
	}

	/**
	 * Check a URL to make sure it's not all hacky
	 *
	 * @param	string		Input String
	 * @return	boolean
	 * @since	2.1.0
	 */
	static public function xssCheckUrl( $url )
	{
		// This causes problems if people submit bbcode with urlencoded items that are valid
		// e.g.: http://www.google.com/search?q=site%3Aipb3preview.ipslink.com+-%22Viewing+Profile%22
		// %22 gets changed into " and then this fails, even though this is a valid url
		// $url = trim( urldecode( $url ) );
		$url	= trim( $url );

		/* Test for http://%XX */
		if ( stristr( $url, 'http://%' ) )
		{
			return FALSE;
		}
		
		/* Test for http://&XX */
		if ( stristr( $url, 'http://&' ) )
		{
			return FALSE;
		}

		if ( ! preg_match( '#^(http|https|news|ftp)://(?:[^<>\"]+|[a-z0-9/\._\- !&\#;,%\+\?:=]+)$#iU', $url ) )
		{
			return FALSE;
		}

		return TRUE;
	}
	
	/**
	 * Here we can do some generic checking for XSS
	 * This should not be considered fool proof, though can provide
	 * a centralized point for maintenance and checking
	 * @param string $txt
	 * @return string
	 */
	static public function xssMakeJavascriptSafe( $txt )
	{
		$txt = preg_replace( "/(j)avascript/i" , "\\1&#097;v&#097;script", $txt );
		//$txt = str_ireplace( "alert"      , "&#097;lert"          , $txt );
		//$txt = preg_replace( "/(b)(e)(h)(a)(v)(i)(o)(r)/is"   , "\\1\\2\\3<b></b>\\4\\5\\6\\7\\8"    	  , $txt );
		$txt = preg_replace( '/(e)((\/\*.*?\*\/)*)x((\/\*.*?\*\/)*)p((\/\*.*?\*\/)*)r((\/\*.*?\*\/)*)e((\/\*.*?\*\/)*)s((\/\*.*?\*\/)*)s((\/\*.*?\*\/)*)i((\/\*.*?\*\/)*)o((\/\*.*?\*\/)*)n/is' , "\\1xp<b></b>ressi&#111;n"     , $txt );
		$txt = preg_replace( '/(e)((\\\|&#092;)*)x((\\\|&#092;)*)p((\\\|&#092;)*)r((\\\|&#092;)*)e((\\\|&#092;)*)s((\\\|&#092;)*)s((\\\|&#092;)*)i((\\\|&#092;)*)o((\\\|&#092;)*)n/is' 	  , "\\1xp<b></b>ressi&#111;n"     	  , $txt );
		$txt = preg_replace( '/m((\\\|&#092;)*)o((\\\|&#092;)*)z((\\\|&#092;)*)\-((\\\|&#092;)*)b((\\\|&#092;)*)i((\\\|&#092;)*)n((\\\|&#092;)*)d((\\\|&#092;)*)i((\\\|&#092;)*)n((\\\|&#092;)*)g/is' 	  , "moz-<b></b>b&#105;nding"     	  , $txt );
		$txt = str_ireplace( "about:"     , "&#097;bout:"         , $txt );
		$txt = str_ireplace( "<body"      , "&lt;body"            , $txt );
		$txt = str_ireplace( "<html"      , "&lt;html"            , $txt );
		$txt = str_ireplace( "document." , "&#100;ocument."      , $txt );
		$txt = str_ireplace( "window."   , "wind&#111;w."      , $txt );
		
		$event_handlers	= array( 'mouseover', 'mouseout', 'mouseup', 'mousemove', 'mousedown', 'mouseenter', 'mouseleave', 'mousewheel',
								 'contextmenu', 'click', 'dblclick', 'load', 'unload', 'submit', 'blur', 'focus', 'resize', 'scroll',
								 'change', 'reset', 'select', 'selectionchange', 'selectstart', 'start', 'stop', 'keydown', 'keyup',
								 'keypress', 'abort', 'error', 'dragdrop', 'move', 'moveend', 'movestart', 'activate', 'afterprint',
								 'afterupdate', 'beforeactivate', 'beforecopy', 'beforecut', 'beforedeactivate', 'beforeeditfocus',
								 'beforepaste', 'beforeprint', 'beforeunload', 'begin', 'bounce', 'cellchange', 'controlselect',
								 'copy', 'cut', 'paste', 'dataavailable', 'datasetchanged', 'datasetcomplete', 'deactivate', 'drag',
								 'dragend', 'dragleave', 'dragenter', 'dragover', 'drop', 'end', 'errorupdate', 'filterchange', 'finish',
								 'focusin', 'focusout', 'help', 'layoutcomplete', 'losecapture', 'mediacomplete', 'mediaerror', 'outofsync',
								 'pause', 'propertychange', 'progress', 'readystatechange', 'repeat', 'resizeend', 'resizestart', 'resume',
								 'reverse', 'rowsenter', 'rowexit', 'rowdelete', 'rowinserted', 'seek', 'syncrestored', 'timeerror',
								 'trackchange', 'urlflip',
								);
		
		foreach( $event_handlers as $handler )
		{
			$txt = str_ireplace( 'on' . $handler, '&#111;n' . $handler, $txt );
		}
		
		return $txt;
	}
	
	/**
	 * Strip URLs from stuff
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	static public function stripUrls( $txt )
	{
		/* Start off by attempting to strip <a href=""></a> */
		$txt = preg_replace( '#<a(?:[^\"\']+?)href\s{0,}=\s{0,}(\"|\'|&quot;|&\#34;|&\#39;|&\#034;|&\#039;)([^<]+?)</a>#i', "", $txt );
		
		/* Now grab any non linked items */
		$txt = preg_replace( '#(http|https|news|ftp)://(?:[^<>\[\"\s]+|[a-z0-9/\._\-!&\#;,%\+\?:=]+)#i', "", $txt );
		
		return $txt;
	}
	
	/**
	 * Returns a cleaned MD5 hash
	 *
	 * @param	string		Input String
	 * @return	string		Parsed string
	 * @since	2.1
	 */
	static public function md5Clean( $text )
	{
		return preg_replace( "/[^a-zA-Z0-9]/", "" , substr( $text, 0, 32 ) );
    }
	
	/**
	 * Convert unicode entities
	 *
	 * @param	string		Input to convert (in the form of %u00E9 (example))
	 * @param	bool		Force to utf-8 (useful if you want to run convertCharsets() on it after)
	 * @return	string		UTF-8 (or html entity) encoded content
	 */
	static public function convertUnicode( $t, $forceUtf8=false )
	{
		$t = rawurldecode( $t );
		
		/* Need this function? */
		if ( ! strstr( $t, '%u' ) )
		{
			return $t;
		}

		if ( strtolower(IPS_DOC_CHAR_SET) == 'utf-8' || $forceUtf8 )
		{
			return preg_replace_callback( '#%u([0-9A-F]{1,4})#i', array( self, '_convertHexToUtf8' ), utf8_encode($t) );
		}
		else
		{
			return preg_replace_callback( '#%u([0-9A-F]{1,4})#i', create_function( '$matches', "return '&#' . hexdec(\$matches[1]) . ';';" ), $t );
		}
	}
	
	/**
	 * Convert decimal character code to utf-8
	 *
	 * @param	integer		Character code
	 * @return	string		Character
	 */
	static protected function _convertToUtf8( $int=0 )
	{
		$return = '';

		if ( $int < 0 )
		{
			return chr(0);
		}
		else if ( $int <= 0x007f )
		{
			$return .= chr($int);
		}
		else if ( $int <= 0x07ff )
		{
			$return .= chr(0xc0 | ($int >> 6));
			$return .= chr(0x80 | ($int & 0x003f));
		}
		else if ( $int <= 0xffff )
		{
			$return .= chr(0xe0 | ($int  >> 12));
			$return .= chr(0x80 | (($int >> 6) & 0x003f));
			$return .= chr(0x80 | ($int  & 0x003f));
		}
		else if ( $int <= 0x10ffff )
		{
			$return .= chr(0xf0 | ($int  >> 18));
			$return .= chr(0x80 | (($int >> 12) & 0x3f));
			$return .= chr(0x80 | (($int >> 6) & 0x3f));
			$return .= chr(0x80 | ($int  &  0x3f));
		}
		else
		{ 
			return chr(0);
		}
		
		return $return;
	}

	/**
	 * Wrapper for dec_char_ref_to_utf8
	 *
	 * @param	array		Hex character code
	 * @return	string		Character
	 */
	static protected function _convertHexToUtf8( $matches )
	{
		return self::_convertToUtf8( hexdec( $matches[1] ) );
	}
	
	/**
	 * Callback function for array_walk_recursive to convert each entry
	 *
	 * @param	mixed		Value
	 * @param	string		Array key
	 * @return	void
	 */
	static public function arrayWalkCallbackConvert( &$value, $key )
	{
		if( is_string($value) )
		{
			$value = IPSText::convertCharsets( $value, IPS_DOC_CHAR_SET, "UTF-8" );
		}
	}

	/**
	 * Convert a string between charsets
	 *
	 * @param	string		Input String
	 * @param	string		Current char set
	 * @param	string		Destination char set
	 * @return	string		Parsed string
	 * @since	2.1.0
	 * @todo 	[Future] If an error is set in classConvertCharset, show it or log it somehow
	 */
	static public function convertCharsets( $text, $original_cset, $destination_cset="UTF-8" )
	{
		define( 'CONVERT_JSU_TO_ENTITY', true );
		
		$original_cset    = strtolower($original_cset);
		$destination_cset = strtolower( $destination_cset );
		$t                = $text;

		//-----------------------------------------
		// Not the same?
		//-----------------------------------------

		if ( $destination_cset == $original_cset )
		{
			return $t;
		}
		
		if ( ! is_object( self::$classConvertCharset ) )
		{
			require_once( IPS_KERNEL_PATH.'/classConvertCharset.php' );/*noLibHook*/
			self::$classConvertCharset = new classConvertCharset();
			
			if ( ipsRegistry::$settings['charset_conv_method'] == 'mb' AND function_exists( 'mb_convert_encoding' ) )
			{
				self::$classConvertCharset->method = 'mb';
			}
			else if ( ipsRegistry::$settings['charset_conv_method'] == 'iconv' AND function_exists( 'iconv' ) )
			{
				self::$classConvertCharset->method = 'iconv';
			}
			else if ( ipsRegistry::$settings['charset_conv_method'] == 'recode' AND function_exists( 'recode_string' ) )
			{
				self::$classConvertCharset->method = 'recode';
			}
			else
			{
				self::$classConvertCharset->method = 'internal';
			}
		}
		
		$text = self::$classConvertCharset->convertEncoding( $text, $original_cset, $destination_cset );
		
		/* Experimental - convert \u#### to html entity */
		if( $destination_cset != 'utf-8' AND $text AND CONVERT_JSU_TO_ENTITY )
		{
			preg_match_all( '#\\\u([0-9]{4})#ims', $text, $matches );
			
			if( is_array($matches) AND count($matches) )
			{
				foreach( $matches[1] as $k => $v )
				{
					$v	= "&#" . hexdec( ltrim( $v, '0' ) )  . ";";
					
					$text	= str_replace( $matches[0][$k], $v, $text );
				}
			}
		}
		
		return $text ? $text : $t;
	}

	/**
	 * Truncate a HTML string without breaking HTML entites
	 *
	 * @param	string		Input String
	 * @param	integer		Desired min. length
	 * @return	string		Parsed string
	 * @since	2.0
	 */
	static public function truncate($text, $limit=30)
	{
		$orig = $text;
		$text = str_replace( '&amp;' , '&#38;', $text );
		$text = str_replace( '&quot;', '&#34;', $text );
		$text = str_replace( '&gt;', '&#62;', $text );
		$text = str_replace( '&lt;', '&#60;', $text );

		$string_length = self::mbstrlen( $text );

		if ( $string_length > $limit)
		{
			$text = trim( self::mbsubstr( $text, 0, $limit - 3 ) ). '...';
		}
		else
		{
			$text = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', $text );
		}

		// Let's just use the original string if the truncated one is longer or same length
		return ( self::mbstrlen( $text ) >= $string_length ) ? $orig : $text;
	}
	
	/**
	 * MB strtolower
	 *
	 * @param	string	Input String
	 * @return	string	Parsed string
	 */
	static public function mbstrtolower( $text )
	{
		if ( function_exists('mb_list_encodings') AND function_exists('mb_strtolower') )
		{
			$valid_encodings = array();
			$valid_encodings = mb_list_encodings();

			if ( count($valid_encodings) )
			{
				if ( in_array( strtoupper(IPS_DOC_CHAR_SET), $valid_encodings ) )
				{
					$text = mb_strtolower( $text, strtoupper(IPS_DOC_CHAR_SET) );
				}
			}
			else
			{
				$text = strtolower( $text );
			}
		}
		else
		{
			$text = strtolower( $text );
		}
		
		return $text;
	}
	
	/**
	 * Substr support for this without mb_substr
	 *
	 * @param	string	Input String
	 * @param	integer	Desired min. length
	 * @return	string	Parsed string
	 * @since	2.0
	 */
	static public function mbsubstr( $text, $start=0, $limit=30 )
	{
		$text = str_replace( '&amp;' , '&#38;', $text );
		$text = str_replace( '&quot;', '&#34;', $text );
		$text = str_replace( '&gt;', '&#62;', $text );
		$text = str_replace( '&lt;', '&#60;', $text );

		//-----------------------------------------
		// Got multibyte?
		//-----------------------------------------

		if( function_exists('mb_list_encodings') )
		{
			$valid_encodings = array();
			$valid_encodings = mb_list_encodings();

			if( count($valid_encodings) )
			{
				if( in_array( strtoupper(IPS_DOC_CHAR_SET), $valid_encodings ) )
				{
					$text	= mb_substr( $text, $start, $limit, strtoupper(IPS_DOC_CHAR_SET) );
					$text	= preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', $text );
					
					return $text;
				}
			}
		}

		//-----------------------------------------
		// Handrolled method
		//-----------------------------------------
		
		$string_length = self::mbstrlen( $text );
		
		//-----------------------------------------
		// Negative start?
		//-----------------------------------------

		if( $start < 0 )
		{
			$start	= $string_length + $start;
		}

		//-----------------------------------------
		// Do it!
		//-----------------------------------------
		
		if ( $string_length > $limit )
		{
			if( strtoupper(IPS_DOC_CHAR_SET) == 'UTF-8' )
			{
				// Multi-byte support
				//$text = preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,0}'.
	            //           '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){'.intval($start).','.intval($limit).'}).*#s',
	            //           '$1',$text);
	            
	            /**
	             * @link	http://www.php.net/manual/en/function.substr.php#55107
	             */
	            preg_match_all( "/./su", $text, $ar );
	            $text	= implode( "", array_slice( $ar[0], $start, $limit ) );
            }
            else
            {
            	$text = substr( $text, $start, $limit );
            }

			$text = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', $text );
		}
		else
		{
			$text = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', $text );
		}

		return $text;
	}
	
	/**
	 * mb_stripos - uses mb functions if available
	 *
	 * @param	string	Input String
	 * @param	integer	Desired min. length
	 * @return	string	Parsed string
	 * @since	2.0
	 */
	static public function mbstripos( $text, $string, $start=0 )
	{
		// Do we have multi-byte functions?

		if( function_exists('mb_list_encodings') AND function_exists('mb_stripos') )
		{
			$valid_encodings = array();
			$valid_encodings = mb_list_encodings();

			if( count($valid_encodings) )
			{
				if( in_array( strtoupper(IPS_DOC_CHAR_SET), $valid_encodings ) )
				{
					return @mb_stripos( $text, $string, $start, strtoupper(IPS_DOC_CHAR_SET) );
				}
			}
		}

		// No?  Fallback to normal stripos
		return stripos( $text, $string, $start );
	}

	/**
	 * Clean a string to remove all non alphanumeric characters
	 *
	 * @param	string		Input String
	 * @param	string		Additional tags
	 * @return	string		Parsed string
	 * @since	2.1
	 */
	static public function alphanumericalClean( $text, $additional_tags="" )
	{
		if ( $additional_tags )
		{
			$additional_tags = preg_quote( $additional_tags, "/" );
		}

		return preg_replace( '/[^a-zA-Z0-9\-\_' . $additional_tags . "]/", "" , $text );
    }

	/**
	 * Get the true length of a multi-byte character string
	 *
	 * @param	string		Input String
	 * @return	integer		String length
	 * @since	2.1
	 */
	static public function mbstrlen( $t )
	{
		if( function_exists( 'mb_list_encodings' ) )
		{
			$encodings	= mb_list_encodings();

			if( in_array( strtoupper(IPS_DOC_CHAR_SET), array_map( 'strtoupper', $encodings ) ) )
			{
				return mb_strlen( $t, IPS_DOC_CHAR_SET );
			}
		}

		return strlen( preg_replace("/&#([0-9]+);/", "-", self::stripslashes( $t ) ) );
    }

	/**
	 * Convert text for use in a textarea
	 *
	 * @param	string		Input String
	 * @return	string		Parsed string
	 * @since	2.0
	 */
	static public function textToForm( $t="" )
	{
		// Use forward look up to only convert & not &#123;
		//$t = preg_replace("/&(?!#[0-9]+;)/s", '&#38;', $t );

		$t = str_replace( "&" , "&#38;"  , $t );
		$t = str_replace( "<" , "&#60;"  , $t );
		$t = str_replace( ">" , "&#62;"  , $t );
		$t = str_replace( '"' , "&#34;"  , $t );
		$t = str_replace( "'" , '&#039;' , $t );

		if ( IN_ACP )
		{
			$t = str_replace( "\\", "&#092;" , $t );
		}

		return $t; // A nice cup of?
	}

	/**
	 * Cleaned form data back to text
	 *
	 * @param	string	Input String
	 * @return	string	Parsed string
	 * @since	2.0
	 */
	static public function formToText($t="")
	{
		$t = str_replace( "&#38;"  , "&", $t );
		$t = str_replace( "&#60;"  , "<", $t );
		$t = str_replace( "&#62;"  , ">", $t );
		$t = str_replace( "&#34;"  , '"', $t );
		$t = str_replace( "&#039;" , "'", $t );
		$t = str_replace( "&#46;&#46;/" , "../", $t );

		if ( IN_ACP )
		{
			//$t = str_replace( '\\'     , '\\\\', $t );
			$t = str_replace( '&#092;' ,'\\', $t );
		}

		return $t;
	}

	/**
	 * Attempt to make slashes safe for us in DB (not really needed now?)
	 *
	 * @param	string	Input String
	 * @return	string	Parsed string
	 * @since	2.0
	 */
	static public function safeslashes($t="")
	{
		return str_replace( '\\', "\\\\", self::stripslashes($t) );
	}

	/**
	 * Remove slashes if magic_quotes enabled
	 *
	 * @param	string		Input String
	 * @return	string		Parsed string
	 * @since	2.0
	 */
	static public function stripslashes($t)
	{
		if( is_array($t) )
		{
			return $t;
		}

		if ( IPS_MAGIC_QUOTES )
		{
    		$t = stripslashes($t);
    	}
    	
    	$t = preg_replace( '/\\\(?!&amp;#|\?#)/', "&#092;", $t );

    	return $t;
    }

	/**
	 * Strip the attachment tag from data
	 *
	 * @param	string		Incoming text
	 * @return	string		Text with any attach tags removed
	 */
	static public function stripAttachTag( $text )
	{
		return preg_replace( '#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is', '', $text );
	}

	/**
	 * Convert text for use in a textarea
	 *
	 * @param	string		Input String
	 * @return	string		Parsed string
	 * @since	2.0
	 */
	static public function raw2form($t="")
	{
		$t = str_replace( '$', "&#036;", $t);

		if ( IPS_MAGIC_QUOTES )
		{
			$t = stripslashes($t);
		}

		$t = preg_replace( '/\\\(?!&amp;#|\?#)/', "&#092;", $t );

		//---------------------------------------
		// Make sure macros aren't converted
		//---------------------------------------

		$t = preg_replace( "/<{(.+?)}>/", "&lt;{\\1}&gt;", $t );

		return $t;
	}

	/**
	 * htmlspecialchars including entities
	 *
	 * @param	string		Input String
	 * @return	string		Parsed string
	 * @since	2.0
	 */
	static public function htmlspecialchars($t="")
	{
		// Use forward look up to only convert & not &#123;
		$t = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $t );
		$t = str_replace( "<", "&lt;"  , $t );
		$t = str_replace( ">", "&gt;"  , $t );
		$t = str_replace( '"', "&quot;", $t );
		$t = str_replace( "'", '&#039;', $t );

		return $t; // A nice cup of?
	}

	/**
	 * unhtmlspecialchars including multi-byte characters
	 *
	 * @param	string		Input String
	 * @return	string		Parsed string
	 * @since	2.0
	 */
	static public function UNhtmlspecialchars($t="")
	{
		$t = str_replace( "&amp;" , "&", $t );
		$t = str_replace( "&lt;"  , "<", $t );
		$t = str_replace( "&gt;"  , ">", $t );
		$t = str_replace( "&quot;", '"', $t );
		$t = str_replace( "&#039;", "'", $t );
		$t = str_replace( "&#39;" , "'", $t );
		$t = str_replace( "&#33;" , "!", $t );
		$t = str_replace( "&#34;" , '"', $t );
		$t = str_replace( "&#036;", '$', $t );
		
		return $t;
	}

	/**
	 * Remove leading comma from comma delim string
	 *
	 * @param	string	Input String
	 * @return	string	Parsed string
	 * @since	2.0
	 */
	static public function trimLeadingComma($t)
	{
		return ltrim( $t, ',' );
	}

	/**
	 * Remove trailing comma from comma delim string
	 *
	 * @param	string	Input String
	 * @return	string	Parsed string
	 * @since	2.0
	 */
	static public function trimTrailingComma($t)
	{
		return rtrim( $t, ',' );
	}

	/**
	 * Remove dupe commas from comma delim string
	 *
	 * @param	string	Input String
	 * @return	string	Parsed string
	 * @since	2.0
	 */
	static public function cleanComma($t)
	{
		return preg_replace( "/,{2,}/", ",", $t );
	}

	/**
	 * Clean perm string (wrapper for comma cleaners)
	 *
	 * @param	string	Input String
	 * @return	string	Parsed string
	 * @since	2.0
	 */
	static public function cleanPermString($t)
	{
		$t = self::cleanComma($t);
		$t = self::trimLeadingComma($t);
		$t = self::trimTrailingComma($t);

		return $t;
	}

	/**
	 * Convert HTML line break tags to \n
	 *
	 * @param	string	Input text
	 * @return	string	Parsed text
	 * @since	2.0
	 */
	static public function br2nl($t="")
	{
		//print nl2br(htmlspecialchars($t)).'<br>--------------------------------<br>';
		$t	= str_replace( array( "\r", "\n" ), '', $t );
		$t	= str_ireplace( array( "<br />", "<br>" ), "\n", $t );
		//$t = preg_replace( "#(?:\n|\r)?<br />(?:\n|\r)?#", "\n", $t );
		//$t = preg_replace( "#(?:\n|\r)?<br>(?:\n|\r)?#"  , "\n", $t );
		//print nl2br(htmlspecialchars($t)).'<br>--------------------------------<br>';
		return $t;
	}

	/**
	 * Removes control characters (hidden spaces)
	 *
	 * @param	string	Input String
	 * @return	intger	String length
	 * @since	2.1
	 */
	static public function removeControlCharacters( $t )
	{
		/* This looks wrong but it's correct. During FURL set up in registry this function is called before settings are loaded
		 * and we want to strip hidden chars in this instance, so.. */
    	if ( ! isset( ipsRegistry::$settings['strip_space_chr'] ) OR ipsRegistry::$settings['strip_space_chr'] )
    	{
			/**
    		 * @see	http://en.wikipedia.org/wiki/Space_(punctuation)
    		 * @see http://www.ascii.cl/htmlcodes.htm
    		 */
			$t = str_replace( chr(160), ' ', $t );
			$t = str_replace( chr(173), ' ', $t );
			
			//$t = str_replace( chr(240), ' ', $t );	-> latin small letter eth

    		//$t = str_replace( chr(0xA0), "", $t );  //Remove sneaky spaces	Same as chr 160
    		//$t = str_replace( chr(0x2004), "", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x2005), "", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x2006), "", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x2009), "", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x200A), "", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x200B), "", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x200C), "", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x200D), " ", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x202F), " ", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x205F), " ", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0x2060), "", $t );  //Remove sneaky spaces
    		//$t = str_replace( chr(0xFEFF), "", $t );  //Remove sneaky spaces
		}

		return $t;
    }

    /**
     * URL encode safe with IPB FURLS
     * @param string $data
     * @return	string		Data
     */
    static public function urlencode_furlSafe( $data )
    {
    	$data = str_replace( '/', '%2F', $data );
    	
    	return urlencode( $data );
    }
    
    /**
    * URL encode safe with IPB FURLS
    * @param string $data
    * @return	string		Data
    */
    static public function urldecode_furlSafe( $data )
    {
    	$data = str_replace( '%25', '%', $data );
    	 
    	return urldecode( $data );
    }
    
	/**
	 * Base64 encode for URLs
	 *
	 * @param	string		Data
	 * @return	string		Data
	 */
	static public function base64_encode_urlSafe( $data )
	{
		return strtr( base64_encode( $data ), '+/=', '-_,' );
	}
	
	/**
	 * Base64 decode for URLs
	 *
	 * @param	string		Data
	 * @return	string		Data
	 */
	static public function base64_decode_urlSafe( $data )
	{
		return base64_decode( strtr( $data, '-_,', '+/=' ) );
	}
	
	/**
	 * Wrapper for PHPs strip_tags. Makes < and > not part of tags safe
	 * Based on @link http://www.php.net/manual/en/function.strip-tags.php#89309
	 * @param string $text
	 * @param string $allowed
	 */
	static public function stripTags( $text, $allowed='' )
	{
		$strs = explode( '<', $text ); 
    	$res  = $strs[0]; 
    	
    	for( $i=1 ; $i < count( $strs ) ; $i++ ) 
   		{ 
        	if ( ! strpos( $strs[$i], '>' ) )
        	{
            	$res = $res . '&#0000001;' . $strs[$i];
        	}
        	else
        	{
            	$res = $res . '<' . $strs[$i];
        	}
   	 	}
		
   	 	$text = strip_tags( $res, $allowed );
   	 	
		return str_replace( '&#0000001;', '<', $text );
	}
}
