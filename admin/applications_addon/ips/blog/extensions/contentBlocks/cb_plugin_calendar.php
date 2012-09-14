<?php
/**
* Calendar Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_calendar extends contentBlocks implements iContentBlock
{
	protected $data;
	protected $now_date;
	protected $sel_date;
	protected $month_words;
	protected $day_words;
	protected $show_draft;
	protected $configable;
	public $js_block;
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
	 * CONSTRUCTOR
	 *
	 * @param  array   $blog      Array of data from the current blog
	 * @param  object  $registry
	 * @return	@e void
	 */
	public function __construct( $blog, ipsRegistry $registry )
	{
		/* Save Classes */
		$this->blog         = $blog;
		$this->data         = array();
		$this->cblock_cache = $this->cblock_cache['minicalendar'];
		$this->configable   = 0;
		$this->js_block     = 0;
		
		$this->member       = $registry->member();
		$this->memberData 	=& $registry->member()->fetchMemberData();
		$this->request      = $registry->request();
		$this->settings		= $registry->settings();
		$this->lang         = $registry->getClass( 'class_localization' );
		$this->DB           = $registry->DB();
		$this->registry     = $registry;
		
		/* Show Draft */
		if( $this->blog['allow_entry'] )
		{
			$this->show_draft = true;
		}
		elseif( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_view_draft'] )
		{
			$this->show_draft = true;
		}
		else
		{
			$this->show_draft = false;
		}		
		
		/* Date Setup */
		$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->registry->class_localization->getTimeOffset() ) );

		$this->now_date = array(
								 'year'    => $a[0],
								 'mon'     => $a[1],
								 'mday'    => $a[2],
								 'hours'   => $a[3],
								 'minutes' => $a[4],
								 'seconds' => $a[5]
							   );

		if( !empty( $this->request['m'] ) && !empty( $this->request['y'] ) )
		{
			$datestamp = gmmktime( 0, 0, 0, $this->request['m'], !empty( $this->request['d'] ) ? $this->request['d'] : 1, $this->request['y'] );
			$a         = explode( ',', gmdate( 'Y,n,j,G,i,s', $datestamp ) );

			$this->sel_date = array(
									 'year'    => $a[0],
									 'mon'     => $a[1],
									 'mday'    => $a[2],
									 'hours'   => $a[3],
									 'minutes' => $a[4],
									 'seconds' => $a[5]
								   );
		}
		else
		{
			$this->sel_date = $this->now_date;
		}

		/* Lang Entries */
		$this->month_words = array( $this->lang->words['M_1'] , $this->lang->words['M_2'] , $this->lang->words['M_3'] ,
									$this->lang->words['M_4'] , $this->lang->words['M_5'] , $this->lang->words['M_6'] ,
									$this->lang->words['M_7'] , $this->lang->words['M_8'] , $this->lang->words['M_9'] ,
									$this->lang->words['M_10'], $this->lang->words['M_11'], $this->lang->words['M_12'] );
		
		if( !$this->settings['ipb_calendar_mon'] )
		{
        	$this->day_words   = array( $this->lang->words['D_0'], $this->lang->words['D_1'], $this->lang->words['D_2'],
        								$this->lang->words['D_3'], $this->lang->words['D_4'], $this->lang->words['D_5'],
        								$this->lang->words['D_6'] );
    	}
    	else
    	{
        	$this->day_words   = array( $this->lang->words['D_1'], $this->lang->words['D_2'], $this->lang->words['D_3'],
        								$this->lang->words['D_4'], $this->lang->words['D_5'], $this->lang->words['D_6'],
        								$this->lang->words['D_0'] );
		}		
	}
	
	/**
	 * Returns the html for the calendar block
	 *
	 * @param  array  $cblock  Array of content block data
	 * @return string
	 */	
	public function getBlock( $cblock )
	{
		/* INIT */
        $month           = $this->sel_date['mon'];
        $year            = $this->sel_date['year'];
        $our_datestamp   = mktime( 12,0,0, $month, 1, $year);
        $first_day_array = getdate($our_datestamp);
		$days = array();
        
		if( $this->settings['ipb_calendar_mon'] )
		{
		    $first_day_array['wday'] = $first_day_array['wday'] == 0 ? 7 : $first_day_array['wday'];
		}

		/* Get blog_entries */
        $entries = array();

       	$end_datestamp = ( $month + 1 > 12 ? mktime( 12, 0, 0, 1, 1, $year + 1 ) : mktime( 12, 0, 0, $month + 1, 1, $year ) );

		/* Can we use the cache */
		if( $this->use_cache && isset( $this->cblock_cache ) && ! $this->show_draft &&
			 $this->sel_date['mon'] == $this->now_date['mon'] && $this->sel_date['year'] == $this->now_date['year'] &&
			 $this->cblock_cache['cbcache_lastupdate'] >= $our_datestamp && $this->cblock_cache['cbcache_lastupdate'] < $end_datestamp &&
			 ! $this->cblock_cache['cbcache_refresh'] )
		{
			$entries = unserialize( $this->cblock_cache['cbcache_content'] );
		}
		else
		{
			/*  Do we show the drafts? */
			$extra = "";
			if( ! $this->show_draft )
			{
				$extra = " AND entry_status='published'";
			}
			
	        $this->DB->build( array( 
	        						'select' => 'entry_date', 
	        						'from'	 => 'blog_entries',
	        						'where'  => "blog_id={$this->blog['blog_id']} and entry_date >= " . ( $our_datestamp - 86400 ) . " and entry_date < " . ( $end_datestamp + 86400 ) . $extra
	        				)	);
			$this->DB->execute();
			
			while ( $entry = $this->DB->fetch() )
			{
				$entries[] = $entry;
			}

			/* Do we update the cache */
			if( $this->use_cache && ! $this->show_draft && $this->sel_date['mon'] == $this->now_date['mon'] && $this->sel_date['year'] == $this->now_date['year'] )
			{
				if( isset( $this->cblock_cache['minicalendar'] ) )
				{
					$update['cbcache_content'] = serialize( $entries );
					$update['cbcache_refresh'] = 0;
					$update['cbcache_lastupdate'] = time();
					$this->DB->update( 'blog_cblock_cache', $update, "blog_id={$this->blog['blog_id']} AND cbcache_key='minicalendar'", true );
				}
				else
				{
					$insert['cbcache_content'] = serialize( $entries );
					$insert['cbcache_refresh'] = 0;
					$insert['cbcache_lastupdate'] = time();
					$insert['blog_id'] = $this->blog['blog_id'];
					$insert['cbcache_key'] = 'minicalendar';
					$this->DB->insert( 'blog_cblock_cache', $insert, true );
				}
			}
		}

		$offset = $this->registry->class_localization->getTimeOffset();
		
		$days_with_entries = array();
		foreach( $entries as $entry )
		{
			$yearday = gmstrftime("%j", $entry['entry_date'] );
			$days_with_entries[ $yearday -1 ] = 1;
		}

		$cal_output = "";
        foreach( $this->day_words as $day )
        {
			$days[] = IPSText::mbsubstr( $day, 0, 1 );
        	//$cal_output .= $this->registry->output->getTemplate( 'blog_cblocks' )->mini_cal_day_bit( $day );
        }

        for( $c = 0 ; $c < 42; $c++ )
        {
        	$day_array = getdate( $our_datestamp );

			$check_against = $c;
			
			if( $this->settings['ipb_calendar_mon'] )
			{
		    	$check_against = $c+1;
			}

        	if( ( ( $c ) % 7 ) == 0 )
        	{
				/* Kill the loop if we are no longer on our month */
        		if( $day_array['mon'] != $month )
        		{
        			break;
        		}

       			$cal_output .= $this->registry->output->getTemplate( 'blog_cblocks' )->mini_cal_new_row( $our_datestamp );
        	}

        	//-----------------------------------------
        	// Run out of legal days for this month?
        	// Or have we yet to get to the first day?
        	//-----------------------------------------

        	if( ( $check_against < $first_day_array['wday'] ) or ($day_array['mon'] != $month ) )
        	{
        		$cal_output .= $this->registry->output->getTemplate( 'blog_cblocks' )->mini_cal_blank_cell();
        	}
        	else
        	{
				/* Do we have an entry on this date? */
				if ( isset( $days_with_entries[ $day_array['yday'] ] ) )
				{
        			$cal_date = "<a href=\"{$this->settings['blog_url']}&amp;view=showday&amp;d={$day_array['mday']}&amp;m={$day_array['mon']}&amp;y={$day_array['year']}\">{$day_array['mday']}</a>";
        		}
        		else
        		{
        			$cal_date = $day_array['mday'];
        		}

        		if( ( $day_array['mday'] == $this->now_date['mday'] ) and ( $this->now_date['mon'] == $day_array['mon'] ) and ( $this->now_date['year'] == $day_array['year'] ) )
        		{
        			$cal_output .= $this->registry->output->getTemplate( 'blog_cblocks' )->mini_cal_date_cell_today( $cal_date );
        		}
        		else
        		{
        			$cal_output .= $this->registry->output->getTemplate( 'blog_cblocks' )->mini_cal_date_cell( $cal_date );
        		}

	       		$our_datestamp += 86400;

	        }
		}

       	$prev = ( $month - 1 < 1  ? array( 'm' => 12, 'y' => $year - 1) : array( 'm' => $month - 1, 'y' => $year ) );
       	$next = ( $month + 1 > 12 ? array( 'm' => 1 , 'y' => $year + 1) : array( 'm' => $month + 1, 'y' => $year ) );

		$title  = "<a href=\"{$this->settings['blog_url']}&amp;m={$prev['m']}&amp;y={$prev['y']}\">{$this->lang->words['_larr']}</a> ";
		$title .= "<a href=\"{$this->settings['blog_url']}&amp;m={$month}&amp;y={$year}\">{$this->month_words[$month - 1]} {$year}</a> ";
		$title .= "<a href=\"{$this->settings['blog_url']}&amp;m={$next['m']}&amp;y={$next['y']}\">{$this->lang->words['_rarr']}</a>";

		$return_html  = $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $title, 0, true );
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->mini_cal_mini_wrap( $cal_output, $days );
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );

		return $return_html;	
	}
	
	/**
	 * Returns the html for the content block configuration form
	 *
	 * @param  array   $cblock  Array of content block data
	 * @return string
	 */	
	public function getConfigForm( $cblock )
	{
		return '';
	}
	
	/**
	 * Handles any extra processing needed on config data
	 *
	 * @param  array  $data  array of config data
	 * @return array
	 */	
	public function saveConfig( $data )
	{
		return $data;
	}	
}