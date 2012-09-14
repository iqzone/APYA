<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Like Ajax
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_tags extends ipsAjaxCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'find':
				$this->_find();
			break;
			case 'getTagsAsPopUp':
				$this->_getTagsAsPopUp();
			break;
        }
    }
    
    /**
     * Get tags as pop-up window
     *
     * @return	@e void
     */
    protected function _getTagsAsPopUp()
    {  
    	/* init */
    	$tag_aai_lookup = IPSText::md5Clean( $this->request['key'] );
    	
    	/* Init tags */	
    	require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$tagClass = classes_tags_bootstrap::run( $tag_aai_lookup );
		
		$formatted = $tagClass->getTagsByCacheKey( $tag_aai_lookup );
		
		return $this->returnHtml( $this->registry->output->getTemplate('global_other')->tagsAsPopUp( $formatted ) );
    }
    
	/**
     * Find tags
     *
     * @return	@e void
     */
    protected function _find()
    {  
    	/* init */
    	$app 	  = $this->request['meta_app'];
    	$area 	  = $this->request['meta_area'];
    	$parentId = intval( $this->request['meta_parent_id'] );
    	$metaId   = intval( $this->request['meta_id'] );
    	$find     = trim( $this->request['find'] );
		$tags     = array();
		$sort	  = array();
		
    	/* Checks */
    	if ( empty( $app ) OR empty( $area ) )
    	{
    		return $this->returnJsonArray( array() );
    	}
    	
    	/* Init tags */	
    	require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$tagClass = classes_tags_bootstrap::run( $app, $area );
		
		/* Get tags by whut */
		if ( ! empty( $metaId ) )
		{
			$tags = $tagClass->getRawTagsByMetaId( $metaId, $find );
		}
		else if ( ! empty( $parentId ) )
		{
			$tags = $tagClass->getRawTagsByParentId( $parentId, $find );
		}
 		
		/* format */
		if ( is_array( $tags ) && count( $tags ) )
		{
			foreach( $tags as $id => $t )
			{
				$sort[ strlen( $t['tag_text'] ) . '.' . md5( $t['tag_text'] ) ] = $t['tag_text'];
			}
		}
		
		ksort( $sort );
		
		return $this->returnJsonArray( array_values( $sort ) );
		
    }
   
}
