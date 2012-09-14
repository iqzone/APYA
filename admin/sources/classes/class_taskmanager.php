<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Task Manager Library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class class_taskmanager
{
	/**
	 * Type of task
	 *
	 * @access	protected
	 * @var		string
	 */
	public $type			= 'internal';

	/**
	 * Current timestamp
	 *
	 * @access	protected
	 * @var		integer
	 */
	protected $time_now		= 0;

	/**
	 * Date pieces
	 *
	 * @access	protected
	 * @var		array
	 */
	public $date_now		= array();

	/**
	 * Cron key
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $cron_key		= "";
	
	/**
	 * Constructer
	 *
	 * @access	public
	 * @param	object 		ipsRegistry $registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Setup Classes */
		$this->registry = $registry;
		$this->lang     = $this->registry->getClass('class_localization');
		$this->DB       = $this->registry->DB();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		/* Setup Timestamps */
		$this->time_now                = time();		
		$this->date_now['minute']      = intval( gmdate( 'i', $this->time_now ) );
		$this->date_now['hour']        = intval( gmdate( 'H', $this->time_now ) );
		$this->date_now['wday']        = intval( gmdate( 'w', $this->time_now ) );
		$this->date_now['mday']        = intval( gmdate( 'd', $this->time_now ) );
		$this->date_now['month']       = intval( gmdate( 'm', $this->time_now ) );
		$this->date_now['year']        = intval( gmdate( 'Y', $this->time_now ) );
		
		/* Attempt to reset timeout */
		if ( @function_exists("set_time_limit") == 1 and SAFE_MODE_ON == 0 )
		{
			@set_time_limit(0);
		}
	}
	
	/**
	 * Run a task
	 *
	 * @return	@e void
	 */
	public function runTask()
	{
		if ( ( ipsRegistry::$request['ck'] ) AND ipsRegistry::$request['ck'] )
		{
			if ( ipsRegistry::$request['ck'] == 'all' )
			{
				if ( ipsRegistry::$settings['task_use_cron'] and ipsRegistry::$request['allpass'] == ipsRegistry::$settings['task_cron_key'] )
				{
					$this->type = 'internal';
				}
				else
				{
					die;
				}
			}
			else
			{			
				$this->type     = 'cron';
				$this->cron_key = substr( trim(stripslashes(IPSText::alphanumericalClean(ipsRegistry::$request['ck']))), 0, 32 );
			}
		}
				
		/* Forcing a task? */
		if ( defined( 'FORCE_TASK_KEY' ) )
		{
			$this_task = $this->DB->buildAndFetch( array(   'select' => '*',
															'from'   => 'task_manager',
															'where'  => "task_key='" . FORCE_TASK_KEY . "'"
												   )      );
												   
			/* Check to make sure the app is enabled and exists */
			$this_task = $this->_checkAppEnabled( $this_task );
		}
		else if ( $this->type == 'internal' )
		{
			//-----------------------------------------
			// Loaded by our image...
			// ... get next job
			//-----------------------------------------
			
			$this_task = $this->DB->buildAndFetch( array(   'select' => '*',
															'from'   => 'task_manager',
															'where'  => 'task_enabled = 1 AND task_next_run <= '.$this->time_now,
															'order'  => 'task_next_run ASC',
															'limit'  => array(0,1)
												   )      );
												   												   
			/* Check to make sure the app is enabled and exists */
			$this_task = $this->_checkAppEnabled( $this_task );
		}
		else if ( $this->type == 'cron' )
		{
			//-----------------------------------------
			// Cron.. load from cron key
			//-----------------------------------------
			
			$this_task = $this->DB->buildAndFetch( array(   'select' => '*',
															'from'   => 'task_manager',
															'where'  => "task_cronkey='".$this->cron_key."'",
												   )      );

			//-----------------------------------------
			// Verify application is enabled
			//-----------------------------------------
			
			if ( ! $this_task['task_application'] OR ! ipsRegistry::$applications[ $this_task['task_application'] ]['app_enabled'] )
			{
				return;
			}
		}

		if ( $this_task['task_id'] )
		{
			//-----------------------------------------
			// Locked?
			//-----------------------------------------
			
			if ( $this_task['task_locked'] > 0 )
			{
				# Yes - now, how long has it been locked for?
				# If longer than 30 mins, unlock as something
				# has gone wrong.
				
				if ( $this_task['task_locked'] < time() - 1800 )
				{
					$newdate = $this->generateNextRun($this_task);
					
					$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate, 'task_locked' => 0 ), "task_id=".$this_task['task_id'] );
					
					$this->saveNextRunStamp();
				}
				
				# Cancel and return if locked
				return;
			}
				
			//-----------------------------------------
			// Got it, now update row, lock and run..
			//-----------------------------------------
			
			$newdate = $this->generateNextRun($this_task);
			
			$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate, 'task_locked' => time() ), "task_id=".$this_task['task_id'] );
			
			$this->saveNextRunStamp();
			
			if ( is_file( IPSLib::getAppDir( $this_task['task_application'] ) . '/tasks/' . $this_task['task_file'] ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( $this_task['task_application'] ) . '/tasks/' . $this_task['task_file'], 'task_item', $this_task['task_application'] );
				$myobj = new $classToLoad( $this->registry, $this, $this_task );
				$myobj->runTask();
				
				//-----------------------------------------
				// Any shutdown queries
				//-----------------------------------------
				
				$this->DB->return_die = 0;
				
				if ( count( $this->DB->obj['shutdown_queries'] ) )
				{
					foreach( $this->DB->obj['shutdown_queries'] as $q )
					{
						$this->DB->query( $q );
					}
				}
				
				$this->DB->return_die = 1;
				
				$this->DB->obj['shutdown_queries'] = array();
			}
		}
	}
	
	/**
	 * Unlock a task
	 *
	 * @access	public
	 * @param	mixed		Task array|Task id
	 * @return	boolean
	 */
	public function unlockTask( $task )
	{
		if( is_array($task) AND count($task) )
		{
			if ( empty($task['task_id']) )
			{
				return false;
			}
			
			$task_id = $task['task_id'];
		}
		else if( intval($task) > 0 )
		{
			$task_id = intval($task);
		}
		else
		{
			return false;
		}
					 
		$this->DB->update( 'task_manager', array( 'task_locked' => 0 ), 'task_id=' . $task_id );
		
		return true;
	}

	/**
	 * Update the task's next run timestamp
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function saveNextRunStamp()
	{
		/* Query the task rund ate */
		$this_task  = $this->DB->buildAndFetch( array( 'select' => 'task_next_run', 'from' => 'task_manager', 'where' => 'task_enabled = 1', 'order' => 'task_next_run ASC', 'limit' => array(0,1) ) );
		
		/* Get the cache */
		$task_cache = $this->caches['systemvars'];
		
		/* Fail safe */
		if ( ! $this_task['task_next_run'] )
		{
			$this_task['task_next_run'] = $this->time_now + 3600;
		}
		
		/* Set new date */
		$task_cache['task_next_run'] = $this_task['task_next_run'];
		
		/* Save Cache */
		ipsRegistry::cache()->setCache( 'systemvars', $task_cache, array( 'array' => 1, 'donow' => 1 ) );
	}
	
	/**
	 * Generate the next run timestamp
	 *
	 * @access	public
	 * @param	array 		Task data
	 * @return	int			Next run timestamp
	 */
	public function generateNextRun($task=array())
	{
		//-----------------------------------------
		// Did we set a day?
		//-----------------------------------------
		
		$day_set       = 1;
		$min_set       = 1;
		$day_increment = 0;
		
		$this->run_day    = $this->date_now['wday'];
		$this->run_minute = $this->date_now['minute'];
		$this->run_hour   = $this->date_now['hour'];
		$this->run_month  = $this->date_now['month'];
		$this->run_year   = $this->date_now['year'];
		
		if ( $task['task_week_day'] == -1 and $task['task_month_day'] == -1 )
		{
			$day_set = 0;
		}
		
		if ( $task['task_minute'] == -1 )
		{
			$min_set = 0;
		}
		
		if ( $task['task_week_day'] == -1 )
		{
			if ( $task['task_month_day'] != -1 )
			{
				$this->run_day = $task['task_month_day'];
				$day_increment = 'month';
			}
			else
			{
				$this->run_day = $this->date_now['mday'];
				$day_increment = 'anyday';
			}
		}
		else
		{
			//-----------------------------------------
			// Calc. next week day from today
			//-----------------------------------------
			
			$this->run_day = $this->date_now['mday'] + ( $task['task_week_day'] - $this->date_now['wday'] );
			
			$day_increment = 'week';
		}
		
		//-----------------------------------------
		// If the date to run next is less
		// than today, best fetch the next
		// time...
		//-----------------------------------------
		
		if ( $this->run_day < $this->date_now['mday'] )
		{
			switch ( $day_increment )
			{
				case 'month':
					$this->_addMonth();
					break;
				case 'week':
					$this->_addDay(7);
					break;
				default:
					$this->_addDay();
					break;
			}
		}
			
		//-----------------------------------------
		// Sort out the hour...
		//-----------------------------------------
		
		if ( $task['task_hour'] == -1)
		{
			/* If week, month are -1 and min =0 then on the hour, otherwise: */
			if ( ! $day_set AND $task['task_minute'] == 0 )
			{
				$this->_addHour( 1 );
			}
			else
			{
				$this->run_hour = $this->date_now['hour'];
			}
		}
		else
		{
			//-----------------------------------------
			// If ! min and ! day then it's
			// every X hour
			//-----------------------------------------
			
			if ( ! $day_set and ! $min_set )
			{
				$this->_addHour( $task['task_hour'] );
			}
			else
			{
				$this->run_hour = $task['task_hour'];
			}
		}
		
		//-----------------------------------------
		// Can we run the minute...
		//-----------------------------------------
		
		if ( $task['task_minute'] == -1 )
		{
			$this->_addMinute();
		}
		else
		{
			if ( $task['task_hour'] == -1 and ! $day_set )
			{
				//-----------------------------------------
				// Runs every X minute..
				//-----------------------------------------
				
				$this->_addMinute($task['task_minute']);
			}
			else
			{
				//-----------------------------------------
				// runs at hh:mm
				//-----------------------------------------
				
				$this->run_minute = $task['task_minute'];
			}
		}
		
		if ( $this->run_hour <= $this->date_now['hour'] and $this->run_day == $this->date_now['mday'] )
		{
			if ( $task['task_hour'] == -1 )
			{
				//-----------------------------------------
				// Every hour...
				//-----------------------------------------
				
				if ( $this->run_hour == $this->date_now['hour'] and $this->run_minute <= $this->date_now['min'] )
				{
 					$this->_addHour();
 				}
 			}
 			else
 			{
 				//-----------------------------------------
 				// Every X hour, try again in x hours
 				//-----------------------------------------
 				
 				if ( ! $day_set and ! $min_set )
 				{
 					$this->_addHour($task['task_hour'] );
 				}
 				
 				//-----------------------------------------
 				// Specific hour, try tomorrow
 				//-----------------------------------------
 				
 				else if ( ! $day_set )
 				{
 					$this->_addDay();
 				}
 				else
 				{
 					//-----------------------------------------
 					// Oops, specific day...
 					//-----------------------------------------
 					
 					switch ( $day_increment )
					{
						case 'month':
							$this->_addMonth();
							break;
						case 'week':
							$this->_addDay(7);
							break;
						default:
							$this->_addDay();
							break;
					}
 				}
 			}
		}
		
		//-----------------------------------------
		// Return stamp...
		//-----------------------------------------
		
		$next_run = gmmktime( $this->run_hour, $this->run_minute, 0, $this->run_month, $this->run_day, $this->run_year );
		 
		return $next_run;
	
	}
	
	/**
	 * Add to the log file
	 *
	 * @access	public
	 * @param	array 		Task data
	 * @param 	string		Description to add to the log file
	 * @return	@e void
	 */
	public function appendTaskLog( $task, $desc )
	{
		if ( ! $task['task_log'] )
		{
			return;
		}
	
		$save = array( 'log_title' => $task['task_title'],
					   'log_date'  => time(),
					   'log_ip'    => my_getenv('REMOTE_ADDR'),
					   'log_desc'  => $desc
					  );
					 
		$this->DB->insert( 'task_logs', $save );
	}
	
	/**
	 * Add a month to the next run timestamp
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _addMonth()
	{
		if ($this->date_now['month'] == 12)
		{
			$this->run_month = 1;
			$this->run_year++;
		}
		else
		{
			$this->run_month++;
		}
	}
	
	/**
	 * Add a day to the next run timestamp
	 *
	 * @access	protected
	 * @param	integer		Number of days to add
	 * @return	@e void
	 */
	protected function _addDay($days=1)
	{
		if ( $this->date_now['mday'] >= ( gmdate( 't', $this->time_now ) - $days ) )
		{
			$this->run_day = ($this->date_now['mday'] + $days) - date( 't', $this->time_now );
			$this->_addMonth();
		}
		else
		{
			$this->run_day += $days;
		}
	}
	
	/**
	 * Add an hour to the next run timestamp
	 *
	 * @access	protected
	 * @param	integer		Number of hours to add
	 * @return	@e void
	 */
	protected function _addHour($hour=1)
	{
		if ($this->date_now['hour'] >= (24 - $hour ) )
		{
			$this->run_hour = ($this->date_now['hour'] + $hour) - 24;
			$this->_addDay();
		}
		else
		{
			$this->run_hour += $hour;
		}
	}
	
	/**
	 * Add a minute to the next run timestamp
	 *
	 * @access	protected
	 * @param	integer		Number of minutes to add
	 * @return	@e void
	 */
	protected function _addMinute($mins=1)
	{
		if ( $this->date_now['minute'] >= (60 - $mins) )
		{
			$this->run_minute = ( $this->date_now['minute'] + $mins ) - 60;
			$this->_addHour();
		}
		else
		{
			$this->run_minute += $mins;
		}
	}
	
	/**
	 * Ensure app exists and is enabled
	 *
	 * @param		array	Task Data
	 * @return		array	Task Data
	 */
	protected function _checkAppEnabled( $task )
	{
		if ( ! $task['task_application'] OR ! ipsRegistry::$applications[ $task['task_application'] ]['app_enabled'] )
		{
			/* Best update them all */
			$_apps = array();
			
			foreach( IPSLib::getEnabledApplications() as $appDir => $appData )
			{
				$_apps[] = "'" . $appDir . "'";
			}
			
			$this->DB->update( 'task_manager', array( 'task_enabled' => 0 ), 'task_application NOT IN (' . implode( ',', $_apps ) . ')' );
			
			/* Fetch task again */
			$task = $this->DB->buildAndFetch( array(	'select'	=> '*',
														'from'		=> 'task_manager',
														'where'		=> 'task_enabled = 1 AND task_next_run <= '.$this->time_now,
														'order'		=> 'task_next_run ASC',
														'limit'		=> array(0,1) ) );
											   
		}
		
		return $task;	
	}
}