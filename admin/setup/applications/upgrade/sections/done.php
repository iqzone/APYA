<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Installer: DONE file
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
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


class upgrade_done extends ipsCommand
{	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Remove the FURL cache */
		@unlink( IPS_CACHE_PATH . 'cache/furlCache.php' );
		
		/* Got anything to show? */
		$apps    = explode( ',', IPSSetUp::getSavedData('install_apps') );
		$vNums   = IPSSetUp::getSavedData('version_numbers');
		$output = array();
		
		if ( is_array( $apps ) and count( $apps ) )
		{
			foreach( $apps as $app )
			{
				/* Grab version numbers */
				$numbers = IPSSetUp::fetchAppVersionNumbers( $app );
				
				/* Grab all numbers */
				$nums[ $app ] = IPSSetUp::fetchXmlAppVersions( $app );
				
				/* Grab app data */
				$appData[ $app ] = IPSSetUp::fetchXmlAppInformation( $app, $this->settings['gb_char_set'] );
				
				$appClasses[ $app ] = IPSSetUp::fetchVersionClasses( $app, $vNums[ $app ], $numbers['latest'][0] );
			}
			
			/* Got anything? */
			if ( count( $appClasses ) )
			{
				foreach( $appClasses as $app => $data )
				{
					foreach( $data as $num )
					{
						if ( is_file( IPSLib::getAppDir( $app ) . '/setup/versions/upg_' . $num . '/version_class.php' ) )
						{
							$_class = 'version_class_' . $app . '_' . $num;
							require_once( IPSLib::getAppDir( $app ) . '/setup/versions/upg_' . $num . '/version_class.php' );/*noLibHook*/
							
							$_tmp = new $_class( $this->registry );
							
							if ( method_exists( $_tmp, 'postInstallNotices' ) )
							{
								$_t = $_tmp->postInstallNotices();
								
								if ( is_array( $_t ) AND count( $_t ) )
								{
									$output[ $app ][ $num ] = array( 'long' => $nums[ $app ][ $num ],
																	 'app'  => $appData[ $app ],
																	 'out'  => implode( "<br />", $_t ) );
								}
							}
						}
					}
				}
			}
		}
		
		/* Remove any SQL source files */
		IPSSetUp::removeSqlSourceFiles();
		
		/* Simply return the Done page */
		$this->registry->output->setTitle( "Complete!" );
		$this->registry->output->setHideButton( TRUE );
		$this->registry->output->addContent( $this->registry->output->template()->upgrade_complete( $output ) );
		$this->registry->output->sendOutput();
	}
}