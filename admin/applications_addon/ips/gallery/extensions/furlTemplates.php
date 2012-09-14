<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Sets up SEO templates
 * Last Updated: $Date: 2011-12-19 06:56:46 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10022 $
 *
 */
$_SEOTEMPLATES = array(
						
						'viewsizes' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))image=(.+?)(?:(?:&|&amp;))size=(.+?)(&|$)/i', 'gallery/sizes/$1-#{__title__}/$2/$3' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery/sizes/(\d+?)-(.+?)/(?:(.+?)(/|$))?#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'images' ),
																								array( 'section'	, 'sizes' ),
																								array( 'image'		, '$1' ),
																								array( 'size'		, '$3' ),
																							)
																	) 
										),
						'viewimage' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))image=(.+?)(&|$)/i', 'gallery/image/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery/image/(\d+?)-#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'image'		, '$1' )
																							)
																	) 
										),
										
						'editalbum' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))albumedit=(.+?)(&|$)/i', 'gallery/album/$1-#{__title__}/edit/$2' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery/album/(\d+?)-(.+?)/edit(/|$)#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'images' ),
																								array( 'section'	, 'review' ),
																								array( 'album_id'	, '$1' )
																							)
																	) 
										),
														
						'viewalbum' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))(?:module=user(?:&|&amp;)user=\d+?(?:&|&amp;)do=view_album(?:&|&amp;))?album=(.+?)(&|$)/i', 'gallery/album/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery/album/(\d+?)-#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'album'		, '$1' )
																							)
																	) 
										),
						'browsealbumroot' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 0,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))browseAlbum=0(&|$)/i', 'gallery/browse/$1' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery/browse/([^0-9]+?|$)#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'albums' ),
																								array( 'section'	, 'browse' ),
																								array( 'browseAlbum', '$1' )
																							)
																	) 
										),
						'browsealbum' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))browseAlbum=(\d+?)(&|$)/i', 'gallery/browse/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery/browse/(\d+?)-#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'albums' ),
																								array( 'section'	, 'browse' ),
																								array( 'browseAlbum', '$1' )
																							)
																	) 
										),
						'galleryportal' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 0,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))home=portal(&|$)/i', 'gallery/portal/$1' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery/portal/#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'albums' ),
																								array( 'section'	, 'home' )
																							)
																	) 
										),


						'rssalbum' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))module=albums(?:(?:&|&amp;))section=rss(?:(?:&|&amp;))album=(.+?)(&|$)/i', 'gallery/rss/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery/rss/(\d+?)-#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'albums' ),
																								array( 'section'	, 'rss' ),
																								array( 'album'		, '$1' )
																							)
																	) 
										),
										
						'useralbum' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))user=(.+?)(&|$)/i', 'gallery/member/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery/member/(\d+?)-#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'albums' ),
																								array( 'section'	, 'user' ),
																								array( 'member_id'	, '$1' )
																							)
																	) 
										),
						
						'app=gallery'		=> array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery/i', 'gallery/' ),
											'in'			=> array( 
																		'regex'		=> '#/gallery(/|$|\?)#i',
																		'matches'	=> array( array( 'app', 'gallery' ) )
																	) 
														),
					);