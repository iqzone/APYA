<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Handles ajax functions for IP.Board Text Editor
 * Author: Matt "Matt Mecham" Mecham
 * Last Updated: $LastChangedDate: 2012-06-07 06:21:09 -0400 (Thu, 07 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10885 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_media extends ipsAjaxCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->lang->loadLanguageFile( array( 'public_editors' ), 'core' );

		//-----------------------------------------
		// Route action
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'loadtab':
				$this->_showTab();
			break;

			case 'show':
			default:
				$this->_showPane();
			break;
		}
	}

	/**
	 * Show a tab
	 *
	 * @return	@e void
	 */
	protected function _showTab()
	{
		$app	= trim($this->request['tabapp']);
		$plugin	= trim($this->request['tabplugin']);
		$output	= '';
		$string	= $this->convertAndMakeSafe( $this->request['search'], TRUE );
		
		if( $app AND is_file( IPSLib::getAppDir( $app ) . '/extensions/sharedmedia/plugin_' . $plugin . '.php' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/extensions/sharedmedia/plugin_' . $plugin . '.php', 'plugin_' . $app . '_' . $plugin, $app );
			$_plugin		= new $classToLoad( $this->registry );
			$output			= $_plugin->showTab( $string );
		}
		else
		{
			$output			= $this->registry->output->getTemplate('editors')->sharedMediaDefault();
		}
		
		return $this->returnHtml( $output );
	}

	/**
	 * Show the panel to select your media
	 *
	 * @return	@e void
	 */
	protected function _showPane()
	{
		//-----------------------------------------
		// Loop through apps and collect tabs
		//-----------------------------------------
		
		$_plugins	= array();
		$_tabs		= array();
		
		foreach( IPSLib::getEnabledApplications() as $application )
		{
			if( is_dir( IPSLib::getAppDir( $application['app_directory'] ) . '/extensions/sharedmedia' ) )
			{
				try
				{
					foreach( new DirectoryIterator( IPSLib::getAppDir( $application['app_directory'] ) . '/extensions/sharedmedia' ) as $file )
					{
						if( ! $file->isDot() && $file->isFile() )
						{
							if( preg_match( '/^plugin_(.+?)\.php$/', $file->getFileName(), $matches ) )
							{
								$classToLoad	= IPSLib::loadLibrary( $file->getPathName(), 'plugin_' . $application['app_directory'] . '_' . $matches[1], $application['app_directory'] );
								$_plugins[ $application['app_directory'] ][ $matches[1] ]	= new $classToLoad( $this->registry );

								if( $_plugins[ $application['app_directory'] ][ $matches[1] ]->getTab() )
								{
									$_tabs[]	= array( 'app' => $application['app_directory'], 'plugin' => $matches[1], 'title' => $_plugins[ $application['app_directory'] ][ $matches[1] ]->getTab() );
								}
							}
						}
					}
				} catch ( Exception $e ) {}
			}
		}

		return $this->returnJsonArray( array( 'html' => $this->registry->output->getTemplate('editors')->sharedMedia( $_tabs ) ) );
	}

}
