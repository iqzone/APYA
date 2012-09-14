<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Reports AJAX options
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_reports extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_reports' ) );

		$this->DB->loadCacheFile( IPSLib::getAppDir('core') . '/sql/' . ips_DBRegistry::getDriverType() . '_report_queries.php', 'report_sql_queries' );

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') .'/sources/classes/reportLibrary.php', 'reportLibrary' );
		$this->registry->setClass( 'reportLibrary', new $classToLoad( $this->registry ) );
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'change_status':
				$this->output = $this->_changeStatus();
			break;
		}
	}
	
	/**
	 * Adds a rating to the index
	 *
	 * @return	@e void
	 */
	protected function _changeStatus()
	{
		//-----------------------------------------
		// Get status's and flags
		//-----------------------------------------
		
		$this->registry->getClass('reportLibrary')->buildStatuses( true );
			
		$COM_PERM = $this->registry->getClass('reportLibrary')->buildQueryPermissions();
		
		//-----------------------------------------
		// Process changes if info is correct
		//-----------------------------------------
		
		if( is_numeric($this->request['id']) && $this->request['id'] > 0 )
		{
			$the_id = intval( $this->request['id'] );
			$return = array('id' => $the_id);
			
			$this->DB->buildFromCache( 'grab_report', array( 'COM' => $COM_PERM, 'rid' => $the_id ), 'report_sql_queries' );
			$res = $this->DB->execute();

			if( $this->DB->getTotalRows() > 0 )
			{
				$report = $this->DB->fetch();

				$old_status = $report['status'];
				$new_status = $this->request['status'];

				if( $old_status != $new_status )
				{
					$build_update = array(
										'status'		=> $new_status,
										'date_updated'	=> time(),
										'updated_by'	=> $this->memberData['member_id'],
									);

					$this->DB->update( 'rc_reports_index', $build_update, "id=".$the_id );
				}
				
				$this->registry->getClass('reportLibrary')->updateCacheTime();
				$this->registry->getClass('reportLibrary')->rebuildMemberCacheArray();
				
				//-----------------------------------------
				// Need to reload data to get right "points" :(
				//-----------------------------------------
				
				$this->DB->buildFromCache( 'grab_report', array( 'COM' => $COM_PERM, 'rid' => $the_id ), 'report_sql_queries' );
				$res = $this->DB->execute();
				$report = $this->DB->fetch();

				//-----------------------------------------
				// Pick the right flag.. or else!
				//-----------------------------------------

				$return['img']		= str_replace( '<#IMG_DIR#>', $this->registry->output->skin['set_image_dir'], $this->registry->getClass('reportLibrary')->flag_cache[ $report['status'] ][ $report['points'] ]['img'] );
				$return['width']	= $this->registry->getClass('reportLibrary')->flag_cache[ $report['status'] ][ $report['points'] ]['width'];
				$return['height']	= $this->registry->getClass('reportLibrary')->flag_cache[ $report['status'] ][ $report['points'] ]['height'];
				$return['is_png']	= $this->registry->getClass('reportLibrary')->flag_cache[ $report['status'] ][ $report['points'] ]['is_png'];

				//-----------------------------------------
				// Image? PNG? Using 'Is-Evil' machine?
				//-----------------------------------------
				
				if( $return['img'] != '' )
				{
					$this->returnJsonArray( array( 'img' => $this->registry->getClass('output')->getTemplate('reports')->statusIcon( $return['img'], $return['width'], $return['height'] ) ) );
				}
				else
				{
					$this->returnString( '&nbsp;' );
				}
			}
		}
		else
		{
			$this->returnString( 'error' );
		}
	}
}