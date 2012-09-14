<?php
/**
 * @file		charts.php 	Admin dashboard chart(s)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2011-11-23 14:13:15 -0500 (Wed, 23 Nov 2011) $
 * @version		v3.3.3
 * $Revision: 9873 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_core_system_charts
 * @brief		Admin dashboard chart(s)
 *
 */
class admin_core_system_charts extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Language */
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_system' ), 'core' );

		switch( $this->request['do'] )
		{
			case 'reg':
			default:
				$this->showRegistrationChart();
			break;
		}
	}
	
	/**
	 * Show chart of registrations over x days
	 *
	 * @return	@e void [Outputs to screen]
	 * @link	http://community.invisionpower.com/tracker/issue-32300-dashboard-registration-chart
	 */
	public function showRegistrationChart()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$days	= intval( $this->request['days'] );
		
		if( !$days )
		{
			$days	= 7;
		}
		
		$cutoff			= time() - ( $days * 86400 );
		$_check			= time();
		/* initdata.php sets to GMT, so we need to take into account time offset ourselves */
		$_tzOffset		= $this->settings['time_offset'] * 3600;
		$registrations	= array();
		$labels			= array();
		$_ttl			= 0;
		
		while( $_check > $cutoff )
		{
			$_day	= strftime( '%b %d', $_check + $_tzOffset );
			$_key	= strftime( '%Y-%m-%d', $_check + $_tzOffset );

			$labels[ $_key ]		= $_day;
			$registrations[ $_key ]	= 0;

			$_check	-= 86400;
		}

		//-----------------------------------------
		// Get the data
		//-----------------------------------------

		$this->DB->build( array( 'select' => 'member_id, joined', 'from' => 'members', 'where' => 'joined > ' . $cutoff ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			//$_day	= strftime( '%b %d', $r['joined'] );
			$_key	= strftime( '%Y-%m-%d', $r['joined'] + $_tzOffset );

			if( isset($registrations[ $_key ]) )
			{
				$registrations[ $_key ]	+= 1;
				$_ttl++;
			}
		}
		
		ksort( $labels );
		ksort( $registrations );

		//-----------------------------------------
		// Output chart
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . '/classGraph.php' );/*noLibHook*/
		$graph	= new classGraph();
		$graph->options['title']			= '';
		$graph->options['font']				= DOC_IPS_ROOT_PATH . '/public/style_captcha/captcha_fonts/DejaVuSans.ttf';
		$graph->options['width']			= 1024;
		$graph->options['height']			= 400;
		$graph->options['style3D']			= 1;
		$graph->options['showlegend']		= 0;
		//$graph->options['xaxisskip']		= 5;
		$graph->options['showgridlinesx']	= 0;

		if( $_ttl )
		{
			//ksort($labels);
			//ksort($registrations);
			
			$graph->addLabels( array_values($labels) );
			$graph->addSeries( 'test', array_values($registrations) );
		}
		else
		{
			$graph->options['title']	= sprintf( $this->lang->words['no_reg_x_days'], $days );
			$graph->addLabels( array( 0 ) );
			$graph->addSeries( 'test', array( 0 ) );
		}

		$graph->options['charttype'] = 'Line';
		$graph->display();
		exit;
	}
}