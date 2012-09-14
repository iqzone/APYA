<?php

class galleryProfilePhotoAlbum
{
	public $registry;
	
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
		$this->settings =& $this->registry->fetchSettings();
		$this->DB       = ipsRegistry::DB();
		$this->memberData =& $this->registry->member()->fetchMemberData();
	}
	
	public function getOutput()
	{
		if( ! $this->memberData['g_gallery_use'] )
		{
			return '';
		}

		/* Setup Gallery Environment */
		require_once( IPS_ROOT_PATH . '/applications_addon/ips/gallery/sources/classes/gallery.php' );
		$this->registry->setClass( 'gallery', new ipsGallery( $this->registry ) );
		
		$this->albums = $this->registry->gallery->helper('albums');
		$this->images = $this->registry->gallery->helper('image');
		
		/* Find the users profile photo album */
		$photoAlbum = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'gallery_albums_main', 'where' => "album_is_profile=1 AND album_owner_id={$this->memberData['member_id']}" ) );
		
		if( ! $photoAlbum['album_id'] )
		{
			return '';
		}
		
		/* Query images */
		$this->DB->build( array( 
									'select'	=> '*', 
									'from'		=> 'gallery_images', 
									'where'		=> "img_album_id={$photoAlbum['album_id']} AND approved=1",
									'order'		=> "id DESC",
									'limit'		=> array( 0, 5 )
						)	);
		$q = $this->DB->execute();
		
		$profileImages = array();
		while( $image = $this->DB->fetch( $q ) )
		{
			$image['_image'] = $this->images->makeImageTag( $image, array( 'type' => 'thumb', 'link-type' => 'src' ) );
			$profileImages[] = $image;
		}
		
		return $this->registry->output->getTemplate('gallery_albums')->galleryPhotoAlbum( $profileImages, $photoAlbum['id'] );
	}
}