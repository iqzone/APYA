<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Upgrade Class
 *
 * Class to add options and notices for IP.Board upgrade
 * Last Updated: $Date: 2011-05-24 15:40:20 -0400 (Tue, 24 May 2011) $
 * </pre>
 * 
 * @author		Matt Mecham <matt@invisionpower.com>
 * @version		$Rev: 8884 $
 * @since		3.0
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @link		http://www.invisionpower.com
 * @package		IP.Board
 */ 

class version_class_gallery_40000
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Add pre-upgrade options: Form
	 * 
	 * @access	public
	 * @return	@e string	 HTML block
	 */
	public function preInstallOptionsForm()
	{
		return <<<EOF
	<ul>
		<li>
			<input type='checkbox' name='skipAlbums' value='1' />
			<strong>Skip</strong> album rebuilding. If checked, you *must* run the command line script (rebuildGallery4.php) as soon as the upgrade is complete and run the album rebuild options.
		</li>
		<li>
			<input type='checkbox' name='skipImages' value='1' />
			<strong>Skip</strong> image rebuilding. If checked, you *must* run the command line script (rebuildGallery4.php) as soon as the upgrade is complete and run the image rebuild options.
		</li>
	</ul>
EOF;
		
	}
	
	/**
	 * Add pre-upgrade options: Save
	 *
	 * Data will be saved in saved data array as: appOptions[ app ][ versionLong ] = ( key => value );
	 * 
	 * @access	public
	 * @return	@e array	 Key / value pairs to save
	 */
	public function preInstallOptionsSave()
	{
		/* Return */
		return array( 'skipAlbums' => intval( $_REQUEST['skipAlbums'] ),
					  'skipImages' => intval( $_REQUEST['skipImages'] )
					);
		
	}
	
	/**
	 * Return any post-installation notices
	 * 
	 * @access	public
	 * @return	@e array	 Array of notices
	 */
	public function postInstallNotices()
	{
		$options      = IPSSetUp::getSavedData('custom_options');
		$skipAlbums   = $options['gallery'][40000]['skipAlbums'];
		$skipImages   = $options['gallery'][40000]['skipImages'];
		
		$notices   = array();
		
		if ( $skipAlbums )
		{
			$notices[] = "Album rebuilding skipped, please use the provided shell tool 'rebuildGallery4.php' found in your 'tools' directory of the download";
		}
		
		if ( $skipImages )
		{
			$notices[] = "Image rebuilding skipped, please use the provided shell tool 'rebuildGallery4.php' found in your 'tools' directory of the download";
		}
		
		return $notices;
	}
}