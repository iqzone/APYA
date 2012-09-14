<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v4.2.1
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	private $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	@e string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		/* Set time out */
		@set_time_limit( 3600 );
		
		/* Gallery Object */
		require_once( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php' );/*noLibHook*/
		$this->gallery = new ipsGallery( $registry );
		
		$this->albums   = $this->gallery->helper('albums');
		$this->images   = $this->gallery->helper('image');
		$this->moderate = $this->gallery->helper('moderate');
		
		/* Load SQL specific class */
		require_once( IPSLib::getAppDir( 'gallery' ) . '/setup/versions/upg_40000/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . '_version_upgrade.php' );/*noLibHook*/
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			case 'albums':
				$this->convertAlbums();
				break;
			case 'categories':
				$this->convertCategories();
				break;
			case 'albumsPassOne':
				$this->convertAlbumsPassOne();
				break;
			case 'albumsPassTwo':
				$this->convertAlbumsPassTwo();
				break;
			case 'albumsPassThree':
				$this->convertAlbumsPassThree();
				break;
			case 'albumsPassFour':
				$this->convertAlbumsPassFour();
				break;
			case 'albumsNodeTree':
				$this->albumsNodeTree();
				break;
			case 'rebuildAlbums':
				$this->rebuildAlbums();
				break;
			case 'imagePerms':
				$this->imagePerms();
				break;
			case 'rebuildImages':
				$this->rebuildImages();
				break;
			case 'finish':
				$this->finish();
				break;
			
			default:
				$this->convertAlbums();
				break;
		}
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Convert albums $skipPms	= $options['core'][30001]['skipPms'];
	 * 
	 * @return	@e void
	 */
	public function convertAlbums()
	{
		/* Fetch query */
		$query = SQLVC::albumsConvertAlbums();
			
		if ( IPSSetUp::getSavedData('man') )
		{
			$query = trim( $query );
			
			/* Ensure the last character is a semi-colon */
			if ( substr( $query, -1 ) != ';' )
			{
				$query .= ';';
			}
			
			$output .= $query . "\n\n";
		}
		else
		{
			$this->DB->allow_sub_select = 1;
			$this->DB->query( $query );

			if ( $this->DB->error )
			{
				$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
			}
			else
			{
				$this->registry->output->addMessage("Converted albums....");
			}
		}
		
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			/* Create source file */
			if ( $this->registry->dbFunctions()->getDriverType() == 'mysql' )
			{
				$sourceFile = IPSSetUp::createSqlSourceFile( $output, '40000', 'convertAlbums' );
			}
			
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output, $sourceFile );
		}
		
		/* Next Page */
		$this->request['workact'] = 'categories';
	}
	
	/**
	 * Convert albums 
	 * 
	 * @return	@e void
	 */
	public function convertCategories()
	{
		/* Fetch query */
		$query = SQLVC::albumsConvertCats();
			
		if ( IPSSetUp::getSavedData('man') )
		{
			$query = trim( $query );
			
			/* Ensure the last character is a semi-colon */
			if ( substr( $query, -1 ) != ';' )
			{
				$query .= ';';
			}
			
			$output .= $query . "\n\n";
		}
		else
		{
			$this->DB->allow_sub_select = 1;
			$this->DB->query( $query );

			if ( $this->DB->error )
			{
				$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
			}
			else
			{
				$this->registry->output->addMessage("Converted categories....");
			}
		}
		
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			/* Create source file */
			if ( $this->registry->dbFunctions()->getDriverType() == 'mysql' )
			{
				$sourceFile = IPSSetUp::createSqlSourceFile( $output, '40000', 'convertCategories' );
			}
			
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output, $sourceFile );
		}
		
		/* Next Page */
		$this->request['workact'] = 'albumsPassOne';
	}
	
	/**
	 * Process old categories
	 * 
	 * @return	@e void
	 */
	public function convertAlbumsPassOne()
	{
		$this->DB->build( array(  'select' => '*',
							 	  'from'   => 'gallery_albums_main',
							 	  'where'  => 'album_is_global=1 AND album_g_rules LIKE \'parent-cat-%\'' ) );
							 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$oldParent = str_replace( 'parent-cat-', '', $row['album_g_rules'] );
			
			if ( intval( $oldParent ) and $oldParent > 0 )
			{
				$parent = $this->DB->buildAndFetch( array(  'select' => 'album_id',
															'from'   => 'gallery_albums_main',
															'where'  => 'album_cache=\'catid-' . $oldParent . '\'' ) );
															
				/* convert */
				$this->DB->update( 'gallery_albums_main', array( 'album_parent_id' => intval( $parent['album_id'] ) ), 'album_id=' . $row['album_id'] );
			
			}
		}
	
		$this->registry->output->addMessage("Updated parent category associations....");
		
		/* Next Page */
		$this->request['workact'] = 'albumsPassTwo';
	}
	
	/**
	 *  Associate images with new global albums from cats
	 * 
	 * @return	@e void
	 */
	public function convertAlbumsPassTwo()
	{
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'gallery_albums_main',
								 'where'  => 'album_cache LIKE \'catid-%\'' ) );
								 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$oldParent = str_replace( 'catid-', '', $row['album_cache'] );
			
			$this->DB->update( 'gallery_images', array( 'img_album_id' => intval( $row['album_id'] ) ), 'category_id=' . intval( $oldParent ) );
		}

		$this->registry->output->addMessage("Updated image album associations....");
		
		/* Next Page */
		$this->request['workact'] = 'albumsPassThree';
	}
	
	/**
	 *  Associate albums with new global albums from cats
	 * 
	 * @return	@e void
	 */
	public function convertAlbumsPassThree()
	{
		$this->DB->build( array( 'select' => '*',
							 	 'from'   => 'gallery_albums_main',
							 	 'where'  => 'album_cache LIKE \'cat-%\'' ) );
							 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$oldParent = str_replace( 'cat-', '', $row['album_cache'] );
			
			if ( intval( $oldParent ) and $oldParent > 0 )
			{
				$parent = $this->DB->buildAndFetch( array(  'select' => 'album_id',
															'from'   => 'gallery_albums_main',
															'where'  => 'album_cache=\'catid-' . $oldParent . '\'' ) );
															
				/* convert */
				$this->DB->update( 'gallery_albums_main', array( 'album_parent_id' => intval( $parent['album_id'] ) ), 'album_id=' . $row['album_id'] );
				$this->DB->update( 'gallery_images'     , array( 'img_album_id' => intval( $parent['album_id'] ) ), 'category_id=' . intval( $oldParent ) );
			}
		}
				
		/* Trim stuffs */
		$query = SQLVC::albumsTrimPerms();
		$this->DB->query( $query );
		
		$this->registry->output->addMessage("Updated global album associations....");
		
		/* Next Page */
		$this->request['workact'] = 'albumsPassFour';
	}
	
	/**
	 * Fix old "closed" setting
	 * 
	 * @return	@e void
	 */
	public function convertAlbumsPassFour()
	{
		$this->DB->build( array( 'select' => '*',
							 	 'from'   => 'gallery_categories',
							 	 'where'  => 'status=0' ) );
							 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$this->DB->update( 'gallery_albums_main', array( 'album_g_perms_images' => '' ), "album_cache='catid-{$row['id']}'" );
		}
				
		/* Update all node data */
		$this->DB->update( 'gallery_albums_main', array( 'album_cache' => '', 'album_node_level' => 0, 'album_node_left' => 0, 'album_node_right' => 0 ) );
						
		/* Next Page */
		$this->registry->output->addMessage("Fixed closed categories....");
		$this->request['workact'] = 'albumsNodeTree';
	}
	
	/**
	 * Build albums node tree
	 * 
	 * @return	@e void
	 */
	public function albumsNodeTree()
	{
		/* INIT */
		$this->albums->rebuildNodeTree();
		
		$this->registry->output->addMessage("Album node tree built....");
		
		/* Next Page */
		$this->request['workact'] = 'rebuildAlbums';
	}
	
	/**
	 * Rebuild albums
	 * 
	 * @return	@e void
	 */
	public function rebuildAlbums()
	{
		/* INIT */
		$this->registry->output->addMessage("Albums rebuilt in a later step...");
		
		$this->request['workact'] = 'imagePerms';
	}
	
	/**
	 * Reset all image permissions
	 * 
	 * @return	@e void
	 */
	public function imagePerms()
	{
		$options   = IPSSetUp::getSavedData('custom_options');
		$skipImages	= $options['gallery'][40000]['skipImages'];
		
		/* skipping? */
		if ( ! $skipImages )
		{
			$this->moderate->resetPermissions(0);
			$this->registry->output->addMessage("Image permissions rebuilt....");
		}
		else
		{
			$this->registry->output->addMessage("SKIPPING: Image permissions, please run command line tool post upgrade....");
		}
		 
		/* Next Page */
		$this->request['workact'] = 'rebuildImages';
	}
	
	/**
	 * Rebuild images
	 * 
	 * @return	@e void
	 */
	public function rebuildImages()
	{
		$options    = IPSSetUp::getSavedData('custom_options');
		$skipImages	= $options['gallery'][40000]['skipImages'];
		$id         = intval( $this->request['imageId'] );
		$lastId     = 0;
		$done  		= intval( $this->request['done'] );
		$cycleDone  = 0;
		
		/* skipping? */
		if ( ! $skipImages )
		{
			$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
											          'from'   => 'gallery_images' ) );
			
			/* Fetch batch */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'gallery_images',
									 'where'  => 'id > ' . $id,
									 'limit'  => array( 0, 50 ),
									 'order'  => 'id ASC' )  );
									
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				$cycleDone++;
				$lastId = $row['id'];
				
				$this->images->resync( $row );
				$this->images->buildSizedCopies( $row );
			}
			
			/* More? */
			if ( $cycleDone )
			{
				/* Reset */
				$done += $cycleDone;
				
				$this->registry->output->addMessage("Images rebuilt: {$done}/{$total['count']}....");
				
				$this->request['imageId'] = $lastId;
				$this->request['done']    = $done;
				
				/* Reset data and go again */
				$this->request['workact'] = 'rebuildImages';
			}
			else
			{
				$this->registry->output->addMessage("All images rebuilt....");
				$this->request['workact'] = 'finish';
				return;
			}
		}
		else
		{
			$this->registry->output->addMessage("SKIPPING: Image rebuild, please run command line tool post upgrade....");
		}
		 
		/* Next Page */
		$this->request['workact'] = 'finish';
	}
	
	/**
	 * Finish up conversion stuff
	 * 
	 * @return	@e void
	 */
	public function finish()
	{
		$this->registry->output->addMessage( "Upgrade completed");
		
		/* Last function, so unset workact */
		$this->request['workact'] = '';
	}
}