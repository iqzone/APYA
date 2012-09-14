<?php

class gallery_attach_forums
{
	/**
	 * Registry Object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	
	
	public function __construct()
	{
		/* Make registry objects */
		$this->registry	= ipsRegistry::instance();
	}
	
	public function getOutput() { }
	/**
	 * Replace output
	 *
	 * @access	public
	 * @param	string		Output
	 * @param	string		Hook key
	 */
	public function replaceOutput( $output, $key )
	{
		$cache = $this->registry->cache()->getCache('gallery_fattach');
		
		if ( ! count( $cache ) )
		{
			return $output;
		}
		
		/* Gallery Object */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . '/applications_addon/ips/gallery/sources/classes/gallery.php', 'ipsGallery' );
		$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		
		/* Load the language File */
		$this->registry->class_localization->loadLanguageFile( array( 'public_gallery', 'public_gallery_four' ), 'gallery' );
			
		if( is_array($this->registry->output->getTemplate('boards')->functionData['boardIndexTemplate'][0]['cat_data']) AND count($this->registry->output->getTemplate('boards')->functionData['boardIndexTemplate'][0]['cat_data']) )
		{
			$tag	= '<!--hook.' . $key . '-->';
			$last	= 0;
		
			foreach( $this->registry->output->getTemplate('boards')->functionData['boardIndexTemplate'][0]['cat_data'] as $cid => $cdata )
			{
				foreach( $cdata['forum_data'] as $id => $data )
				{
					$pos	= strpos( $output, $tag, $last );
					
					if ( $pos )
					{
						$string = '';
						
						if ( isset( $cache[ $id ] ) )
						{
							foreach( $cache[ $id ] as $album_id )
							{
								$album   = $this->registry->gallery->helper('albums')->fetchAlbumsById( $album_id );
								
								if ( $album['album_id'] && $this->registry->gallery->helper('albums')->isViewable( $album ) )
								{
									$string	.= $this->registry->output->getTemplate('gallery_albums')->boardIndexEntry( $album );
								}
							}
							
							$output	= substr_replace( $output, $string . $tag, $pos, strlen( $tag ) ); 
						}
						
						$last	= $pos + strlen( $tag . $string );
					}
				}
			}
		}
		
		return $output;
	}
}