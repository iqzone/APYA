<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Login handler abstraction : AJAX Find Names functions
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4 $
 *
 */

class public_blog_ajax_findblogs extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
    	switch( $this->request['do'] )
    	{
			case 'get-by-member-name':
				$this->_getByMemberName();
			break;
			
			case 'get-by-display-name':
    			$this->_getByDisplayName();
    		break;

			case 'get-by-blog-name':
    			$this->_getByBlogrName();
    		break;
    	}
	}
	
	/**
	 * Returns possible matches for the string input
	 *
	 * @access	protected
	 * @return	@e void		Outputs to screen
	 */
	protected function _getByDisplayName()
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------

    	$name = IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['name'], 0 ), true );
		$name = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );

    	//-----------------------------------------
    	// Check length
    	//-----------------------------------------

    	if ( IPSText::mbstrlen( $name ) < 3 )
    	{
    		$this->returnJsonError( 'requestTooShort' );
    	}

    	//-----------------------------------------
    	// Try query...
    	//-----------------------------------------

    	$this->DB->build( array( 'select'	=> 'm.members_display_name, m.name, m.member_id',
    							 'from'	    => array( 'members' => 'm' ),
    							 'where'	=> "m.members_l_display_name LIKE '" . $this->DB->addSlashes( $name ) . "%'",
    							 'order'	=> $this->DB->buildLength( 'm.members_display_name' ) . ' ASC',
    							 'limit'	=> array( 0, 15 ),
 								 'add_join' => array( array( 'select' => 'p.*',
														     'from'   => array( 'profile_portal' => 'p' ),
														     'where'  => 'p.pp_member_id=m.member_id',
														     'type'   => 'left' ) ) ) );
		$this->DB->execute();

    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------

    	if ( ! $this->DB->getTotalRows() )
 		{
    		$this->returnJsonArray( array( ) );
    	}

    	$return = array();

		while( $r = $this->DB->fetch() )
		{
			$photo = IPSMember::buildProfilePhoto( $r );			
			$return[ $r['member_id'] ] = array( 'name' 	=> $r['members_display_name'],
												'img'	=> $photo['pp_mini_photo'],
												'img_w'	=> $photo['pp_mini_width'],
												'img_h'	=> $photo['pp_mini_height']
											);
		}

		$this->returnJsonArray( $return );
	}
	
	/**
	 * Returns possible matches for the string input
	 *
	 * @access	protected
	 * @return	@e void		Outputs to screen
	 */
	protected function _getByMemberName()
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------

    	$name = IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['name'], 0 ), true );
		$name = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );

    	//-----------------------------------------
    	// Check length
    	//-----------------------------------------

    	if ( IPSText::mbstrlen( $name ) < 3 )
    	{
    		$this->returnJsonError( 'requestTooShort' );
    	}

    	//-----------------------------------------
    	// Try query...
    	//-----------------------------------------

    	$this->DB->build( array( 'select'	=> 'm.members_display_name, m.name, m.member_id',
    							 'from'	    => array( 'members' => 'm' ),
    							 'where'	=> "m.members_l_display_name LIKE '" . $this->DB->addSlashes( $name ) . "%'",
    							 'order'	=> $this->DB->buildLength( 'm.members_display_name' ) . ' ASC',
    							 'limit'	=> array( 0, 15 ),
 								 'add_join' => array( array( 'select' => 'p.*',
														     'from'   => array( 'profile_portal' => 'p' ),
														     'where'  => 'p.pp_member_id=m.member_id',
														     'type'   => 'left' ) ) ) );
		$this->DB->execute();

    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------

    	if ( ! $this->DB->getTotalRows() )
 		{
    		$this->returnJsonArray( array( ) );
    	}

    	$return = array();

		while( $r = $this->DB->fetch() )
		{
			$photo = IPSMember::buildProfilePhoto( $r );			
			$return[ $r['member_id'] ] = array( 'name' 	=> $r['name'],
												'img'	=> $photo['pp_mini_photo'],
												'img_w'	=> $photo['pp_mini_width'],
												'img_h'	=> $photo['pp_mini_height']
											);
		}

		$this->returnJsonArray( $return );
	}
	
	/**
	 * Returns possible matches for the string input
	 *
	 * @access	protected
	 * @return	@e void		Outputs to screen
	 */
	protected function _getByBlogrName()
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------

    	$name = IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['name'], 0 ), true );
		$name = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );

    	//-----------------------------------------
    	// Check length
    	//-----------------------------------------

    	if ( IPSText::mbstrlen( $name ) < 3 )
    	{
    		$this->returnJsonError( 'requestTooShort' );
    	}

    	//-----------------------------------------
    	// Try query...
    	//-----------------------------------------

    	$this->DB->build( array( 'select'	=> 'b.member_id,b.blog_name',
    							 'from'	    => array( 'blog_blogs' => 'b' ),
    							 'where'	=> $this->DB->buildLower('b.blog_name') . " LIKE '" . $this->DB->addSlashes( $name ) . "%'",
    							 'order'	=> $this->DB->buildLength( 'b.blog_name' ) . ' ASC',
    							 'limit'	=> array( 0, 15 ),
 								 'add_join' => array( array( 'select' => 'p.*',
														     'from'   => array( 'profile_portal' => 'p' ),
														     'where'  => 'p.pp_member_id=b.member_id',
														     'type'   => 'left' ) ) ) );
		$this->DB->execute();

    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------

    	if ( ! $this->DB->getTotalRows() )
 		{
    		$this->returnJsonArray( array() );
    	}

    	$return = array();

		while( $r = $this->DB->fetch() )
		{
			$photo = IPSMember::buildProfilePhoto( $r );			
			$return[ $r['member_id'] ] = array( 'name' 	=> $r['blog_name'],
												'img'	=> $photo['pp_mini_photo'],
												'img_w'	=> $photo['pp_mini_width'],
												'img_h'	=> $photo['pp_mini_height']
											);
		}
		
		$this->returnJsonArray( $return );
	}	
}