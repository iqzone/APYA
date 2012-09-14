<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Upgrade Class
 *
 * Class to add options and notices for IP.Board upgrade
 * Last Updated: $Date: 2011-05-24 20:40:20 +0100 (Tue, 24 May 2011) $
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

class version_class_gallery_42000
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
		$html    = '';
		
		if ( $this->DB->checkForTable( 'gallery_albums_main') )
		{
			$options = '';
			$this->DB->build( array( 'select' => '*', 'from' => 'gallery_albums_main', 'where' => 'album_is_global=1 AND album_node_level=0' ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$options .= '<option value="' . $row['album_id'] . '">' . $row['album_name'] . '</option>';
			}
			
			$html = <<<EOF
				<li>
					<select name='membersAlbum'>{$options}</select>
					<strong>Select</strong> the "Member's Album" - this is the album that contains your member's albums that don't have other parents.
				</li>
				<li>
					<input type='checkbox' name='membersAlbumNew' value='1' />
					<strong>Or create</strong> a new member's album.
				</li>
		
EOF;
		}
		else
		{
			$html = "<input type='hidden' name='membersAlbumNew' value='1' />";
		}
		
		return <<<EOF
	<ul>
		{$html}
		<li>
			<input type='checkbox' name='skipAlbums' value='1' />
			<strong>Skip</strong> album rebuilding. If checked, you *must* run the command line script (rebuildGallery4.php) as soon as the upgrade is complete and run the album rebuild options.
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
		return array( 'skipAlbums'      => intval( $_REQUEST['skipAlbums'] ),
					  'membersAlbumNew' => intval( $_REQUEST['membersAlbumNew'] ),
					  'membersAlbum'    => intval( $_REQUEST['membersAlbum'] ) );
		
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
		$skipAlbums   = $options['gallery'][42000]['skipAlbums'];
				
		$notices   = array();
		
		if ( $skipAlbums )
		{
			$notices[] = "Album rebuilding skipped, please use the provided shell tool 'rebuildGallery4.php' found in your 'tools' directory of the download";
		}
		
		/* Hackery! */
		$memberAlbum = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'gallery_albums_main', 'where' => 'album_g_perms_thumbs=\'member\'' ) );
		
		IPSLib::updateSettings( array( 'gallery_members_album' => $memberAlbum['album_id'] ) );
		
		$this->DB->update( 'gallery_albums_main', array( 'album_g_perms_thumbs' => '' ), 'album_id=' . intval( $memberAlbum['album_id'] ) );
		
		return $notices;
	}
}