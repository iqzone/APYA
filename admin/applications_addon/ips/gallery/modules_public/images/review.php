<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Rate Image
 * Last Updated: $LastChangedDate: 2011-12-09 15:24:24 -0500 (Fri, 09 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9978 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_review extends ipsCommand
{
	/**
	 * Temporary page output
	 *
	 * @var		$output
	 */
    public $output;

	/**
	 * Page title
	 *
	 * @var		$title
	 */
    public $title;

	/**
	 * Array of navigation bits
	 *
	 * @var		$nav
	 */
    public $nav;

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* Sort out some things to start with... */
		$this->nav[] = array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );
		
		$this->_media = $this->registry->gallery->helper('media');
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}
		
		/* What to do? */
		switch( $this->request['do'] )
		{
			default:
			case 'show':
				$this->_showItems();
			break;
			case 'process':
				$this->_process();
			break;
        }

		/* Output */
		$this->registry->getClass('output')->setTitle( $this->title );
		$this->registry->getClass('output')->addContent( $this->output );
		
		if ( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}		

		$this->registry->getClass('output')->sendOutput();	
	}
	
	/**
	 * Process items
	 * Can be from an upload session or can be from an existing album
	 */
	protected function _process()
	{
		/* Fetch session key or album id */
		$sessionKey = trim( $this->request['sessionKey'] );
		$album_id   = intval( $this->request['album_id'] );
		$type       = trim( $this->request['type'] );
		$album_name = trim( $this->request['album_name'] );
		$album_desc = trim( $this->request['album_description'] );
		$album_mum  = intval( $this->request['album_parent_id'] );
		$album_see  = intval( $this->request['album_is_public'] );
		$album_dd   = intval( $this->request['album_detail_default'] );
		$album_sort = serialize( array( 'key' => $this->request['album_sort_options__key'], 'dir' => $this->request['album_sort_options__dir'] ) );
		$images     = array();
		$toDelete   = array();
		$album      = array();
		
		/* Load album */
		if ( ! empty( $album_id ) )
		{
			$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $album_id );
			
			if ( $sessionKey )
			{
				if ( ! $this->registry->gallery->helper('albums')->isUploadable( $album ) && ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
				{
					$this->registry->output->showError( 'no_permission', '1-gallery-review-process-5', null, null, 403 );
				}
			}
			else
			{
				if ( ! $this->registry->gallery->helper('albums')->isOwner( $album ) && ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
				{
					$this->registry->output->showError( 'no_permission', '1-gallery-review-process-5', null, null, 403 );
				}
			}
		}
		
		/* Fetch items */
		if ( $sessionKey )
		{
			$Db_images = $this->registry->gallery->helper('upload')->fetchSessionUploadsAsImages( $sessionKey );
			$type      = 'uploads';
		}
		elseif ( $album_id )
		{
			/* Check album_parent against isGlobal */
			if ( !$album_mum && !$this->registry->gallery->helper('albums')->isGlobal( $album ) )
			{
				$this->registry->output->showError( 'parent_zero_not_global', '1-gallery-review-process-4' );
			}
			
			/* Sort other options */
			$Db_images  = $this->registry->gallery->helper('image')->fetchAlbumImages( $album_id, array( 'sortOrder' => 'asc' ) );
			$type       = 'album';
			$album_see  = ( $this->registry->gallery->helper('albums')->isGlobal( $album ) !== true ) ? $album_see : $album['album_is_public'];
			$album_wm   = $this->registry->gallery->helper('albums')->canWatermark( $album ) ? intval($this->request['album_watermark']) : $album['album_watermark'];
			$album_name = ( $album_name ) ? $album_name : $this->lang->words['gallery_untitled_album'];
			
			/* Update name and title */
			$this->registry->gallery->helper('albums')->save( array( $album_id => array( 'album_detail_default' => $album_dd, 'album_sort_options' => $album_sort, 'album_name' => $album_name, 'album_description' => $album_desc, 'album_parent_id' => $album_mum, 'album_is_public' => $album_see, 'album_watermark' => $album_wm ) ) );
		}
		else
		{
			$this->registry->output->showError( 'no_permission', '1-gallery-review-process-3', null, null, 403 );
		}	
		
		/* Fetch the known image IDs */
		if ( is_array($this->request['imageIds']) && count($this->request['imageIds']) )
		{
			/* Set up BBcode parsing */
			IPSText::getTextClass('bbcode')->parse_smilies	 = 1;
			IPSText::getTextClass('bbcode')->parse_html		 = 0;
			IPSText::getTextClass('bbcode')->parse_nl2br	 = 1;
			IPSText::getTextClass('bbcode')->parse_bbcode	 = 1;
			IPSText::getTextClass('bbcode')->parsing_section = 'gallery';
			
			$editorClass = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$editor = new $editorClass();
			
			/* Go freaking loopy */
			foreach( $this->request['imageIds'] as $key => $data )
			{
				/* Deleting? */
				if ( $this->request['delete'][ $key ] )
				{
					/* Delete files */
					$toDelete[ $key ] = $Db_images[ $key ];
				}
				else
				{
					$_isCover = ( intval( $this->request['makeCover'] ) == $key ) ? 1 : 0;
					$_caption = trim( IPSText::stripslashes( $this->request['title'][ $key ] ) );
					
					if ( ! $_caption )
					{
						$this->registry->output->showError( $this->lang->words['gerror_no_title'], '1-gallery-review-process-2', null, null, 403 );
					}
										
					/* Parse data */
					$images[ $key ] = array( 'description'    => IPSText::getTextClass('bbcode')->preDbParse( $editor->process( $_POST[ 'description_' . $key ] ) ),
											 'caption'        => $_caption,
											 'caption_seo'    => IPSText::makeSeoTitle( $_caption ),
											 'img_album_id'   => $album_id,
											 '_isCover'       => $_isCover,
											 'image_gps_show' => intval( $this->request['locationAllow'][ $key ] ),
											 'copyright'      => trim( $this->request['copyright'][ $key ] ) );
				}
			}
		}
		elseif ( $sessionKey )
		{
			$this->registry->output->showError( $this->lang->words['gerror_no_items'], '1-gallery-review-process-1', null, null, 403 );
		}
		
		/* Delete? */
		if ( count( $toDelete ) )
		{
			$this->registry->gallery->helper('image')->delete( $toDelete );
		}
		
		/* Now save them back */
		if ( count( $images ) )
		{
			$this->registry->gallery->helper('image')->save( $images );
		}
		
		/* Finish up image uploading? */
		if ( $sessionKey )
		{
			$albums = $this->registry->gallery->helper('upload')->finish( $sessionKey );
			$album  = $this->registry->gallery->helper('albums')->fetchAlbum( array_pop( $albums ) );
			
			/* Moderated? Inform user dudes */
			if ( $album['album_g_approve_img'] AND ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
			{
				$this->registry->output->redirectScreen( $this->lang->words['gal_redirect_mod_album'], $this->settings['base_url'] . 'app=gallery&amp;album=' . $album_id, $album['album_name_seo'], false, 'viewalbum' );
			}
		}
		
		
		/* Redirect to album */
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $album_id, $album['album_name_seo'], false, 'viewalbum' );
	}
	
	/**
	 * Show items to review
	 * Can be from an upload session or can be from an existing album
	 */
	protected function _showItems()
	{
		$sessionKey = trim( $this->request['sessionKey'] );
		$album_id   = intval( $this->request['album_id'] );
		$images     = array();
		$type		= null;
		$firstId    = 0;
		$coverSet   = 0;
		$editors	= array();
		
		/* Init Editor Class */
		$editorClass = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $editorClass();
		
		/* Load album */
		if ( ! empty( $album_id ) )
		{
			$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $album_id );
			
			if ( $sessionKey )
			{
				if ( ! $this->registry->gallery->helper('albums')->isUploadable( $album ) && ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
				{
					$this->registry->output->showError( 'no_permission', '1-gallery-review-show-3a', null, null, 403 );
				}
			}
			else
			{
				if ( ! $this->registry->gallery->helper('albums')->isOwner( $album ) && ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
				{
					$this->registry->output->showError( 'no_permission', '1-gallery-review-show-3b', null, null, 403 );
				}
			}
		}		
						
		/* Fetch items */
		if ( $sessionKey )
		{
			$images = $this->registry->gallery->helper('upload')->fetchSessionUploadsAsImages( $sessionKey );
			$type   = 'uploads';
		}
		else if ( $album_id )
		{
			$images = $this->registry->gallery->helper('image')->fetchAlbumImages( $album_id, array( 'sortOrder' => 'asc' ) );
			$type   = 'album';
			
			if ( $album['album_parent_id'] )
			{
				$album['_parent'] = array( 'album_id' => $album['album_parent_id'], 'parentText' => $this->registry->gallery->helper('albums')->getParentsAsTextString( $album['album_parent_id'] ) );
			}
			else
			{
				$album['_parent'] = array( 'album_id' => 0, 'parentText' => $this->lang->words['as_root'] );
			}
			
			/* Sort out some things */
			$album['album_description']	= IPSText::br2nl($album['album_description']);
			$album['_canWatermark']		= $this->registry->gallery->helper('albums')->canWatermark( $album );
		}
		else
		{
			$this->registry->output->showError( 'no_permission', '1-gallery-review-show-1', null, null, 403 );
		}
	
		/* Loop through and fix up stuffs */
		if ( is_array( $images ) AND count( $images ) )
		{
			foreach( $images as $id => $data )
			{
				if ( ! $firstId )
				{
					/* Fetch album image_view_ */
					$firstId = $id;
					
					if ( empty( $album['album_id'] ) )
					{
						$album   = $this->registry->gallery->helper('albums')->fetchAlbumsById( $images[ $firstId ]['img_album_id'] );
					}
				}
				
				if ( $type == 'uploads' AND IPSLib::isSerialized( $data['metadata'] ) )
				{
					$_data = unserialize( $data['metadata'] );
					
					if ( ! empty( $_data['IPTC.ObjectName'] ) )
					{
						$images[ $id ]['caption'] = $_data['IPTC.ObjectName'];
					}
				}
				
				if ( $type == 'uploads' )
				{
					$where = array( 'fake_meta_id'   => $id,
									'meta_parent_id' => intval( $album['album_id'] ),
									'member_id'		 => $this->memberData['member_id'],
									'existing_tags'	 => explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) ) );
					
					if ( $this->registry->galleryTags->can( 'add', $where ) )
					{
						$images[ $id ]['_tagBox'] = $this->registry->galleryTags->render('entryBox', $where);
					}
				}
				else
				{
					$where = array( 'meta_id'		 => $images[ $id ]['id'],
								    'meta_parent_id' => intval( $album['album_id'] ),
								    'member_id'	     => $this->memberData['member_id'] );
	
					if ( $_REQUEST['ipsTags_' . $id] )
					{
						$where['existing_tags']	= explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags_' . $id] ) );
					}
				
					if ( $this->registry->galleryTags->can( 'edit', $where ) )
					{
						$images[ $id ]['_tagBox'] = $this->registry->galleryTags->render('entryBox', $where);
					}
				}
				
				$images[ $id ]['_title']   = ( $type == 'uploads' ) ? $this->_smrtTitle( $images[ $id ]['caption'] ) : $images[ $id ]['caption'];
				$images[ $id ]['_isMedia'] = $this->_media->isAllowedExtension( $images[ $id ]['masked_file_name'] ) ? 1 : 0;
				
				/* Fix up description */
				$images[ $id ]['editor'] = $editor->show( 'description_' . $id, array( 'type' => 'mini', 'minimize' => TRUE ), $images[ $id ]['description'] );
				
				/* Cover image? */
				if ( $album['album_cover_img_id'] AND $album['album_cover_img_id'] == $id )
				{
					$coverSet = 1;
					$images[ $id ]['_cover'] = 1;
					$album['_hasCoverSet']   = 'inline';
				}
				elseif ( $album['album_cover_img_id'] )
				{
					$coverSet = 1;
					$album['_hasCoverSet'] = 'elsewhere';
				}
			}
			
			/* Ensure we have a cover */
			if ( ! $coverSet )
			{
				$images[ $firstId ]['_cover'] = 1;
				$album['_hasCoverSet']        = 'inline';
			}
		}
	
		/* Ensure we have an album */
		if ( $type == 'album' && empty( $album['album_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', '1-gallery-review-show-2', null, null, 403 );
		}
		
		/* Do we need navigational elements on the page forthwith? */
		if ( $type == 'album' )
		{
			/* Fetch navigation */
			$parents = $this->registry->gallery->helper('albums')->fetchAlbumParents( $album['album_id'] );
			$parents = array_reverse( $parents, true );
			
			foreach( $parents as $id => $data )
			{
				$this->nav[] = array( $data['album_name'], 'app=gallery&amp;album=' . $data['album_id'], $data['album_name_seo'], 'viewalbum' );	
			}
			
			/* add in this album */
			$this->nav[] = array( $album['album_name'], 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], 'viewalbum' );
		}
		/* Get a propoer cover image for uplodas then */
		elseif ( $album['_hasCoverSet'] == 'elsewhere' )
		{
			$album['cover']        = $this->registry->gallery->helper('image')->fetchImage( $album['album_cover_img_id'], FALSE, FALSE );
			$album['cover']['tag'] = $this->registry->gallery->helper('image')->makeImageLink( $album['cover'], array( 'h1image' => TRUE ) );
		}
		
		/* Add review nav */
		$this->nav[] = array( $this->lang->words['review_title_' . $type ] );
		
		/* Send off to skinny skin skin */
		$this->output = $this->registry->output->getTemplate('gallery_imagelisting')->review( $images, $album, $type, $sessionKey );
		$this->title  = $this->lang->words['review_title_' . $type ];
	}
	
	/**
	 * I AM SO SMART ! S M R T!
	 *
	 * @access	protected so there
	 * @param	string
	 * @return	@e string
	 */
	protected function _smrtTitle( $text )
	{
		/* Not an image name? */
		if ( ! preg_match( '#^(.*)\.\S{2,4}$#', $text ) )
		{
			return htmlspecialchars( $text );
		}
		
		/* Knock off file extension */
		$text = preg_replace( '#^(.*)\.\S{2,4}$#', "\\1", $text );
		
		/* Convert some stuff to spaces */
		$text = str_replace( array( '-', '_', '+', '%20' ), ' ', $text );
		
		$_t = explode( ' ', $text );
		$_f = array();
		
		foreach( $_t as $w )
		{
			if ( strlen( $w ) > 3 )
			{
				$_f[] = $w;
			}
			else
			{
				$_f[] = ucfirst( $w );
			}
		}
		
		return implode( ' ', $_f );
	}
}