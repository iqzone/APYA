<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Define data hook locations (Gallery)
 * Last Updated: $Date: 2011-06-16 17:16:07 -0400 (Thu, 16 Jun 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9056 $
 */

$dataHookLocations = array(

	/* POSTING DATA LOCATIONS */
	array( 'galleryPreAddImage', 'Add Image (Pre DB)' ),
	array( 'galleryPostAddImage', 'Add Image (Post DB)' ),
	array( 'galleryEditImage', 'Edit Image' ),
	array( 'galleryRebuildStatsCache', 'Rebuild Gallery Statistics Cache' ),
	array( 'galleryAddImageComment', 'Add Image Comment' ),
	array( 'galleryEditImageComment', 'Edit Image Comment' ),
	array( 'galleryCommentAddPostSave', 'Add Image Comment (post save)' ),
	array( 'galleryCommentEditPostSave', 'Edit Image Comment (post save)' ),
	array( 'galleryCommentPostDelete', 'Delete Image Comment (post delete)' ),
	array( 'galleryCommentToggleVisibility', 'Toggle Image Comment Visibility (post delete)' ),
	
);