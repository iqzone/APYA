<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forum Statistics
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_statistics_stats extends ipsCommand
{
	/**
	* Array of Month Names
	*
	* @var		array
	*/		
	protected $month_names;
	
	/**
	* Skin object
	*
	* @var		object			Skin templates
	*/	
	protected $html;	
	
	/**
	* Main class entry point
	*
	* @param	object		ipsRegistry reference
	* @return	@e void		[Outputs to screen]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		/* Load HTML and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_stats' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_stats' ) );

		/* URLs */
		$this->form_code	= $this->html->form_code	= 'module=statistics&amp;section=stats';
		$this->form_code_js	= $this->html->form_code_js	= 'module=statistics&section=stats';		
		
		/* Setup the month name array */
		$this->month_names = array( 1	=> $this->lang->words['stats_jan'], 
									2	=> $this->lang->words['stats_feb'], 
									3	=> $this->lang->words['stats_mar'], 
									4	=> $this->lang->words['stats_apr'],
									5	=> $this->lang->words['stats_may'], 
									6	=> $this->lang->words['stats_jun'], 
									7	=> $this->lang->words['stats_jul'], 
									8	=> $this->lang->words['stats_aug'], 
									9	=> $this->lang->words['stats_sep'], 
									10	=> $this->lang->words['stats_oct'], 
									11	=> $this->lang->words['stats_nov'], 
									12	=> $this->lang->words['stats_dec']
								  );
		
		/* Fix up navigation bar */
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=statistics', $this->lang->words['stats_title'] );
		
		/* Silly timezones */
		$tz = floor( $this->memberData['time_offset'] );
		$this->DB->setTimeZone( $tz );
		if ( $tz ==  0 )
		{
			date_default_timezone_set( 'GMT' );
		}
		elseif ( $tz > 0 )
		{				
			date_default_timezone_set( 'Etc/GMT-' . $tz );
		}
		elseif ( $tz < 0 )
		{				
			date_default_timezone_set( 'Etc/GMT+' . abs( $tz ) );
		}
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'spam':
				$this->statsMainScreen( 'spam' );
			break;
			
			case 'show_spam':
				$this->spamServiceResultScreen();
			break;
			
			case 'spamGraph':
				$this->spamServiceGraph();
			break;
			
			case 'show_reg':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_registration' );
				$this->result_screen( 'reg' );
			break;
				
			case 'show_topic':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_topics' );
				$this->result_screen( 'topic' );
			break;
			
			case 'topic':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_topics' );
				$this->statsMainScreen( 'topic' );
			break;
			
			//-----------------------------------------
			
			case 'show_post':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_posts' );
				$this->result_screen( 'post' );
			break;
					
			case 'post':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_posts' );
				$this->statsMainScreen( 'post' );
			break;
			
			//-----------------------------------------
			
			case 'show_msg':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_msg' );
				$this->result_screen( 'msg' );
			break;
					
			case 'msg':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_msg' );
				$this->statsMainScreen( 'msg' );
			break;
				
			//-----------------------------------------
			
			case 'statsShowTopicViews':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_views' );
				$this->statsShowTopicViews();
			break;
					
			case 'views':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_views' );
				$this->statsMainScreen( 'views' );
			break;
			
			//-----------------------------------------
			
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_registration' );
				$this->statsMainScreen( 'reg' );
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Print a 1x1 transparent gif and safely exist
	 *
	 * @return	@e void
	 */
	protected function _safeExit()
	{
		$content	= base64_decode( "R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" );
		header( "Content-type: image/gif" );
		header( "Connection: Close" );
		header( "Cache-Control:  public, max-age=86400" );
		header( "Expires: " . gmdate( "D, d M Y, H:i:s", time() + 86400 ) . " GMT" );
		print $content;
		flush();
		exit;
	}
	
	/**
	 * Display a graph of spam service stats
	 *
	 * @return	@e void
	 */
	public function spamServiceGraph()
	{
		/* Check the to fields */
		if ( ! checkdate( $this->request['to_month'], $this->request['to_day'], $this->request['to_year'] ) )
		{
			$this->_safeExit();
		}
		
		/* Check the from fields */
		if ( ! checkdate( $this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year'] ) )
		{
			$this->_safeExit();
		}
		
		/* Create time stamps */
		$to_time   = mktime(12 ,0 ,0 ,$this->request['to_month']   ,$this->request['to_day']   ,$this->request['to_year']  );
		$from_time = mktime(12 ,0 ,0 ,$this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year']);
		
		/* Get Human Dates */
		$human_to_date   = getdate( $to_time );
		$human_from_date = getdate( $from_time );

		/* Setup Timescale */
	  	switch( $this->request['timescale'] )
	  	{
	  		case 'daily':
		  		$php_date = "F jS - Y";
		  		break;
		  		
		  	case 'monthly':
		  	    $php_date = "F Y";
		  	    break;
		  	    
		  	default:
		  		$php_date = "W [F Y]";
		  		break;
		}
		
		$order = $this->request['sortby'] == 'desc' ? 'desc' : 'asc';
		
		/* Query Spam Logs */
		$this->DB->build( array( 
								'select'	=> 'log_code, log_date', 
								'from'		=> 'spam_service_log', 
								'where'		=> 'log_date > ' . $from_time . ' AND log_date < ' . $to_time,
								'order'		=> 'log_date ' . $order
						)	);
		$this->DB->execute();
		
		/* Make sure we have logs */
		if( ! $this->DB->getTotalRows() )
		{
			$this->_safeExit();
		}
		
		/* Loop through and build data for two graph lines */
		$notSpamData	= array();
		$isSpamData		= array();
		$labels			= array();
		
		while( $r = $this->DB->fetch() )
		{
			/* Ignore errors */
			if( $r['log_code'] == 0 )
			{
				continue;
			}
			
			/* Date */
			$logDate = date( $php_date, $r['log_date'] );
			
			if( ! in_array( $logDate, $labels ) )
			{
				$labels[] = $logDate;
			}
			
			if( ! isset( $notSpamData[ $logDate ] ) )
			{
				$notSpamData[ $logDate ] = 0;
			}
			
			if( ! isset( $isSpamData[ $logDate ] ) )
			{
				$isSpamData[ $logDate ] = 0;
			}
			
			if( $r['log_code'] == 1 )
			{
				$notSpamData[ $logDate ]++;
			}
			else if( in_array( $r['log_code'], array( 2, 3, 4 ) ) )
			{
				$isSpamData[ $logDate ]++;
			}
		}
		
		if( !count($notSpamData) AND !count($isSpamData) )
		{
			$this->_safeExit();
		}

		/* Table Title */
		$title = $this->lang->words[ 'timescale_' . $this->request['timescale'] ] ." {$this->lang->words['stats_spam']} ({$human_from_date['mday']} {$this->month_names[$human_from_date['mon']]} {$human_from_date['year']} {$this->lang->words['stats_to']} {$human_to_date['mday']} {$this->month_names[$human_to_date['mon']]} {$human_to_date['year']})";

		/* Get the Graph Class */
		require_once( IPS_KERNEL_PATH . 'classGraph.php' );/*noLibHook*/
		
		/* Graph Ooptions */
		$graph = new classGraph( false );
		$graph->options['title']			= $title;
		$graph->options['width']			= 800;
		$graph->options['height']			= 480;
		$graph->options['style3D']			= 0;
		$graph->options['charttype']		= 'Line';
		$graph->options['font']				= IPS_PUBLIC_PATH . 'style_captcha/captcha_fonts/Sathu.ttf';

		/* Build Graph */
		$graph->addLabels( $labels );
		$graph->addSeries( $this->lang->words['statisnotspam'], array_values( $notSpamData ) );
		$graph->addSeries( $this->lang->words['statisspam'], array_values( $isSpamData ) );

		$graph->display();

		exit();
	}
	
	/**
	 * Display spam service stats
	 *
	 * @return	@e void
	 */
	public function spamServiceResultScreen()
	{
		/* Check the to fields */
		if ( ! checkdate( $this->request['to_month'], $this->request['to_day'], $this->request['to_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_toincorrect'], 11352 );
		}
		
		/* Check the from fields */
		if ( ! checkdate( $this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_fromincorrect'], 11353 );
		}
		
		/* Create time stamps */
		$to_time   = mktime(12 ,0 ,0 ,$this->request['to_month']   ,$this->request['to_day']   ,$this->request['to_year']  );
		$from_time = mktime(12 ,0 ,0 ,$this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year']);
		
		/* Get Human Dates */
		$human_to_date   = getdate( $to_time );
		$human_from_date = getdate( $from_time );
		
		/* Setup Timescale */
	  	switch( $this->request['timescale'] )
	  	{
	  		case 'daily':
		  		$php_date = "F jS - Y";
		  		break;
		  		
		  	case 'monthly':
		  	    $php_date = "F Y";
		  	    break;
		  	    
		  	default:
		  		$php_date = "W [F Y]";
		  		break;
		}
		
		$order = $this->request['sortby'] == 'desc' ? 'desc' : 'asc';
		
		/* Query Spam Logs */
		$this->DB->build( array( 
								'select'	=> 'log_code, log_date', 
								'from'		=> 'spam_service_log', 
								'where'		=> 'log_date > ' . $from_time . ' AND log_date < ' . $to_time,
								'order'		=> 'log_date ' . $order
						)	);
		$this->DB->execute();
		
		$results	= array();
		$total		= 0;
		
		while( $r = $this->DB->fetch() )
		{
			/* Skip errors */
			if( $r['log_code'] == 0 )
			{
				continue;
			}
			
			/* Key */
			if( $r['log_code'] == 1 )
			{
				$arrayKey = 'notspam';
			}
			else if( in_array( $r['log_code'], array( 2, 3, 4 ) ) )
			{
				$arrayKey = 'spam';
			}

			$results[ date( $php_date, $r['log_date'] ) ][ $arrayKey ]++;
			$total++;
		}
		
		/* Figure out max result */
		$max_result = 0;
		
		foreach( $results as $k => $v )
		{
			$_max = ( $v['spam'] > $v['notspam'] ) ? $v['spam'] : $v['notspam'];

			if( $_max > $max_result )
			{
				$max_result = $_max;
			}
		}

		/* Build the output rows */
		$rows = array();
			
		foreach( $results as $dateVal => $stats )
		{
			/* INIT */
			$newStats = array();
			
			/* Width of the bars */
			$newStats['spam']['_width']		= intval( ( $stats['spam'] / $max_result ) * 100 - 8 );
			$newStats['notspam']['_width']	= intval( ( $stats['notspam'] / $max_result ) * 100 - 8 );
			
			$newStats['spam']['_width'] 	= $newStats['spam']['_width'] < 0 ? 1 : $newStats['spam']['_width'];
			$newStats['notspam']['_width'] 	= $newStats['notspam']['_width'] < 0 ? 1 : $newStats['notspam']['_width'];
			
			
			/* Save Count */
			$newStats['spam']['count']		= $stats['spam'] ? $stats['spam'] : 0;
			$newStats['notspam']['count']	= $stats['notspam'] ? $stats['notspam'] : 0;
			
			$rows[$dateVal] = $newStats;
		}

		/* Table Title */
		$title = $this->lang->words[ 'timescale_' . $this->request['timescale'] ] ." {$this->lang->words['stats_spam']} ({$human_from_date['mday']} {$this->month_names[$human_from_date['mon']]} {$human_from_date['year']} {$this->lang->words['stats_to']} {$human_to_date['mday']} {$this->month_names[$human_to_date['mon']]} {$human_to_date['year']})";
		
		/* Navigation */
		$this->registry->output->extra_nav[]     = array( '', $this->lang->words['stats_spam'] );
		return $this->registry->output->html .= $this->html->spamServiceStats( $title, $rows, $total );
	}
	
	/**
	 * Display statistics for the selected mode
	 *
	 * @param	string	Type of stat screen reg, topic, post, msg	 
	 * @return	@e void
	 */
	public function result_screen($mode='reg')
	{
		/* Check the to fields */
		if ( ! checkdate( $this->request['to_month'], $this->request['to_day'], $this->request['to_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_toincorrect'], 11352 );
		}
		
		/* Check the from fields */
		if ( ! checkdate( $this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_fromincorrect'], 11353 );
		}
		
		/* Create time stamps */
		$to_time   = mktime(12 ,0 ,0 ,$this->request['to_month']   ,$this->request['to_day']   ,$this->request['to_year']  );
		$from_time = mktime(12 ,0 ,0 ,$this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year']);
		
		/* Get Human Dates */
		$human_to_date   = getdate( $to_time );
		$human_from_date = getdate( $from_time );
		
		/* Setup based on mode */
		switch( $mode )
		{
			case 'reg':
				$table     = $this->lang->words['stats_reg'];
				
				$sql_table = 'members';
				$sql_field = 'joined';
				
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=statistics&amp;section=stats&amp;do=reg', $table );
				$this->registry->output->extra_nav[] = array( '', $this->lang->words['stats_reg_nav'] );			
			break;
			
			case 'topic':
				$table     = $this->lang->words['stats_topic'];
				
				$sql_table = 'topics';
				$sql_field = 'start_date';
				
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=statistics&amp;section=stats&amp;do=topic', $table );
				$this->registry->output->extra_nav[] = array( '', $this->lang->words['stats_topic_nav'] );			
			break;
			
			case 'post':
				$table     = $this->lang->words['stats_post'];
				
				$sql_table = 'posts';
				$sql_field = 'post_date';
				
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=statistics&amp;section=stats&amp;do=post', $table );
				$this->registry->output->extra_nav[] = array( '', $this->lang->words['stats_post_nav'] );			
			break;
			
			case 'msg':
				$table     = $this->lang->words['stats_msg'];
				
				$sql_table = 'message_topics';
				$sql_field = 'mt_date';
				
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=statistics&amp;section=stats&amp;do=msg', $table );
				$this->registry->output->extra_nav[] = array( '', $this->lang->words['stats_msg_nav'] );			
			break;
		}

		/* Setup Timescale */
	  	switch( $this->request['timescale'] )
	  	{
	  		case 'daily':
	  			$sql_date = "%j %Y";
		  		$php_date = "F jS - Y";
		  		break;
		  		
		  	case 'monthly':
		  		$sql_date = "%m %Y";
		  	    $php_date = "F Y";
		  	    break;
		  	    
		  	default:
		  		// weekly
		  		$sql_date = "%U %Y";
		  		$php_date = "W [F Y]";
		  		break;
		}
		
		/* Table Title */
		$title       = $this->lang->words[ 'timescale_' . $this->request['timescale'] ] ." {$table} ({$human_from_date['mday']} {$this->month_names[$human_from_date['mon']]} {$human_from_date['year']} {$this->lang->words['stats_to']} {$human_to_date['mday']} {$this->month_names[$human_to_date['mon']]} {$human_to_date['year']})";
		
		/* Query the stats */
		$this->DB->build( array(
										'select' => "MAX( {$sql_field} ) as result_maxdate, COUNT(*) as result_count, {$this->DB->buildDateFormat( $this->DB->buildFromUnixtime( $sql_field ), $sql_date )} as result_time",
										'from'	 => $sql_table,
										'where'	 => $sql_field . ' > ' . $from_time . ' AND ' . $sql_field . ' < ' . $to_time,
										'group'	 => $this->DB->buildDateFormat( $this->DB->buildFromUnixtime( $sql_field ), $sql_date ),
										//'order'	 => $sql_field . ' ' . $this->request['sortby'],
							)		);
							
						
		$this->DB->execute();
		
		/* Loop through the results */
		$running_total = 0;
		$max_result    = 0;
		$results       = array();
	
		while( $row = $this->DB->fetch() )
		{	
			if( $row['result_count'] >  $max_result )
			{
				$max_result = $row['result_count'];
			}
				
			$running_total += $row['result_count'];
			
			$results[ $row['result_maxdate'] ] = array(
								 						'result_maxdate'	=> $row['result_maxdate'],
														'result_count'		=> $row['result_count'],
														'result_time'		=> $row['result_time'],
														'formatted_date'	=> date( $php_date, $row['result_maxdate'] ),
													);
								  
		}
		
		if( strtolower($this->request['sortby']) == 'desc' )
		{
			krsort( $results );
		}
		else
		{
			ksort( $results );
		}
		
		/* Build the output rows */
		$rows = array();
			
		foreach( $results as $data )
		{
			/* Width of the bar */
    		$data['_width'] = intval( ( $data['result_count'] / $max_result ) * 100 - 8 );
    			
    		if( $data['_width'] < 1 )
    		{
    			$data['_width'] = 1;
    		}
    			
    		$data['_width'] .= '%';
    		
			/* Format Date */
    		if( $this->request['timescale'] == 'weekly' )
    		{
    			$data['_name'] = $this->lang->words['stats_weekno'] . date( $php_date, $data['result_maxdate'] );
    		}
    		else
    		{
    			$data['_name'] = date( $php_date, $data['result_maxdate'] );
    		}
    		
    		$rows[] = $data;
		}
		
		/* Output */
		$this->registry->output->html	.= $this->html->statResultsScreen( $title, $rows, $running_total );
	}	
	
	/**
	 * Date Selection Screen
	 *
	 * @param	string	Type of stat screen reg, topic, post, msg, views
	 * @return	@e void
	 */
	public function statsMainScreen( $mode='reg' )
	{
		/* Setup this mode */
		switch( $mode )
		{
			case 'spam':
				$form_code	= 'show_spam';
				$table		= $this->lang->words['stats_spam'];
			break;
			
			case 'reg':
				$form_code = 'show_reg';
				$table     = $this->lang->words['stats_reg'];
			break;
			
			case 'topic':
				$form_code = 'show_topic';
				$table     = $this->lang->words['stats_topic'];
			break;
			
			case 'post':
				$form_code = 'show_post';
				$table     = $this->lang->words['stats_post'];
			break;
			
			case 'msg':
				$form_code = 'show_msg';
				$table     = $this->lang->words['stats_msg'];
			break;
			
			case 'views':
			default:
				$form_code = 'statsShowTopicViews';
				$table     = $this->lang->words['stats_views'];
				
				$this->registry->output->setMessage( $this->lang->words['topic_view_warning'], 1 );
			break;			
		}

		/* Setup Dates */
		$old_date = getdate( time() - ( 3600 * 24 * 90 ) );
		$new_date = getdate( time() + ( 3600 * 24 ) );

		/* Form Elements */
		$form = array();

		$form['from_month'] = $this->registry->output->formDropdown( "from_month", $this->statsMakeMonth(), $old_date['mon']  );
		$form['from_day']   = $this->registry->output->formDropdown( "from_day"  , $this->statsMakeDay()  , $old_date['mday'] );
		$form['from_year']  = $this->registry->output->formDropdown( "from_year" , $this->statsMakeYear() , $old_date['year'] );
		$form['to_month']   = $this->registry->output->formDropdown( "to_month"  , $this->statsMakeMonth(), $new_date['mon']  );
		$form['to_day']     = $this->registry->output->formDropdown( "to_day"    , $this->statsMakeDay()  , $new_date['mday'] );
		$form['to_year']    = $this->registry->output->formDropdown( "to_year"   , $this->statsMakeYear() , $new_date['year'] );
		$form['timescale']  = $this->registry->output->formDropdown( "timescale" , array( 0 => array( 'daily', $this->lang->words['stats_daily']), 1 => array( 'weekly', $this->lang->words['stats_weekly'] ), 2 => array( 'monthly', $this->lang->words['stats_monthly'] ) ) );
		$form['sortby']     = $this->registry->output->formDropdown( "sortby"    , array( 0 => array( 'asc', $this->lang->words['stats_asc']), 1 => array( 'desc', $this->lang->words['stats_desc'] ) ), 'desc' );
									     									     
		/* Output */
		$this->registry->output->html           .= $this->html->statMainScreeen( $form_code, $table, $form );
		
		/* Navigation */
		$this->registry->output->extra_nav[]     = array( '', $table );
	}	

	/**
	 * Show topic view stats
	 *
	 * @return	@e void
	 */
	public function statsShowTopicViews()
	{
		/* Check the to fields */
		if ( ! checkdate( $this->request['to_month'], $this->request['to_day'], $this->request['to_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_toincorrect'], 11354 );
		}
		
		/* Check the from fields */
		if ( ! checkdate( $this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_fromincorrect'], 11355 );
		}
		
		/* Create time stamps */
		$to_time   = mktime(0 ,0 ,0 ,$this->request['to_month']   ,$this->request['to_day']   ,$this->request['to_year']  );
		$from_time = mktime(0 ,0 ,0 ,$this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year']);
		
		/* Get Human Dates */
		$human_to_date   = getdate( $to_time );
		$human_from_date = getdate( $from_time );
		
		/* Title */
		$title = "{$this->lang->words['stats_views_nav']} ({$human_from_date['mday']} {$this->month_names[$human_from_date['mon']]} {$human_from_date['year']} {$this->lang->words['stats_to']} {$human_to_date['mday']} {$this->month_names[$human_to_date['mon']]} {$human_to_date['year']})";
		
		/* Query the topic stats */
		$this->DB->build( array( 
										'select'    => 'SUM(t.views) as result_count, t.forum_id',
										'from'	    => array( 'topics' => 't' ),
										'where'	    => "t.start_date > {$from_time} AND t.start_date < {$to_time}",
										'group'	    => 't.forum_id',
										'order'	    => 'result_count ' . $this->request['sortby'],
										'add_join'	=> array(
															array( 
																	'select' => 'f.name as result_name',
																	'from'	 => array( 'forums' => 'f' ),
																	'where'	 => 'f.id=t.forum_id',
																	'type'	 => 'left'
																)
															)
							)		);
		$this->DB->execute();
		
		/* Loop through the results */
		$running_total	= 0;
		$max_result		= 0;
		$results		= array();
		$running_total	= 0;
	
		while( $row = $this->DB->fetch() )
		{	
			if( $row['result_count'] >  $max_result )
			{
				$max_result = $row['result_count'];
			}
			
			$running_total	+= $row['result_count'];
						
			$results[] = array(
								 'result_maxdate'  => $row['result_maxdate'],
								 'result_count'    => $row['result_count'],
								 'result_time'     => $row['result_time'],
								 'result_name'     => $row['result_name'],								 
							  );
								  
		}
		
		/* Build the output rows */
		$rows = array();
			
		foreach( $results as $data )
		{
			$running_total += $row['result_count'];
		
			/* Width of the bar */				
    		$data['_width'] = intval( ( $data['result_count'] / $max_result ) * 100 - 8 );
    			
    		if( $data['_width'] < 1 )
    		{
    			$data['_width'] = 1;
    		}
    			
    		$data['_width'] .= '%';
    		
    		/* Title */
    		$data['_name'] = $data['result_name'];
    		
    		$rows[] = $data;
		}
		
		/* Output */
		$this->registry->output->extra_nav[] 	 = array( $this->settings['base_url'] . 'module=statistics&amp;section=stats&amp;do=views', $this->lang->words['stats_views'] );
		$this->registry->output->extra_nav[]     = array( '', $this->lang->words['stats_views_nav'] );
		$this->registry->output->html           .= $this->html->statResultsScreen( $title, $rows, $running_total );
	}
	
	/**
	 * Create the drop down options for the year select
	 *
	 * @return	array
	 */
	public function statsMakeYear()
	{
		$time_now = getdate();
		
		$return = array();
		
		$start_year = 2002;
		
		$latest_year = intval($time_now['year'])+1;
		
		if ($latest_year == $start_year)
		{
			$start_year -= 1;
		}
		
		for ( $y = $start_year; $y <= $latest_year; $y++ )
		{
			$return[] = array( $y, $y);
		}
		
		return $return;
	}
	
	/**
	 * Create the drop down options for the month select
	 *
	 * @return	array
	 */
	public function statsMakeMonth()
	{
		$return = array();
		
		for ( $m = 1 ; $m <= 12; $m++ )
		{
			$return[] = array( $m, $this->month_names[$m] );
		}
		
		return $return;
	}
	
	/**
	 * Create the drop down options for the day select
	 *
	 * @return	array
	 */
	public function statsMakeDay()
	{
		$return = array();
		
		for ( $d = 1 ; $d <= 31; $d++ )
		{
			$return[] = array( $d, $d );
		}
		
		return $return;
	}
	
	
		
}