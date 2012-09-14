<?php
/**
 * @file		photo.php 	Returns a cropped photo for an ajax request
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		7th Feb 2011
 * $LastChangedDate: 2012-05-11 11:17:52 -0400 (Fri, 11 May 2012) $
 * @version		v3.3.3
 * $Revision: 10727 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_members_ajax_photo
 * @brief		Returns a cropped photo for an ajax request
 *
 */
class public_members_ajax_photo extends ipsAjaxCommand 
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
		
		/* Load library */
		$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
		$this->photo = new $classToLoad( $registry );
			
		switch( $this->request[ 'do' ] )
		{
			case 'cropPhoto':
				$this->_cropPhoto();
			break;
			case 'show':
				$this->_show();
			break;
			case 'remove':
				$this->_remove();
			break;
			case 'save':
				$this->_save();
			break;
			case 'importUrl':
				$this->_importUrl();
			break;
		}
	}
	
	/**
	 * Import a photo or image from URL
	 * 
	 */
	protected function _importUrl()
	{
		$url = trim( $this->request['url'] );
		
		/* Do it */
		try
		{
			/* Check for valid URL */
			$photo = $this->photo->save( $this->memberData, 'url', '', $url );
			
			if ( is_array( $photo ) )
			{
				$photo = IPSMember::buildDisplayData( IPSMember::load( $this->memberData['member_id'], 'all' ) );
				$photo['oldThumb'] = $this->memberData['pp_small_photo'];
				$photo['status']   = 'ok';
				$photo['thumb']    = $photo['pp_thumb_photo'];
				$this->returnJsonArray( $photo );
			}
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			switch ( $msg )
			{
				default:
					$this->returnJsonError( $this->lang->words[ 'pp_' . $msg ] );
				break;
				case 'upload_to_big':
					$this->returnJsonError( sprintf( $this->lang->words[ 'pp_' . $msg ], $this->memberData['photoMaxKb'] ) );
				break;
				case 'PROFILE_DISABLED':
					$this->returnJsonError( 'member_profile_disabled' );
				break;
			}
		}
	}
	
	/**
	 * Saves data
	 *
	 * @return	@e void [HTML]
	 */
	protected function _save()
	{
		$photoType   = $this->request['photoType'];
		$gravatar	 = $this->request['gravatar'];
		
		/* Do it */
		try
		{
			$photo = $this->photo->save( $this->memberData, $photoType, $gravatar );
			
			if ( is_array( $photo ) )
			{
				$photo = IPSMember::buildDisplayData( IPSMember::load( $this->memberData['member_id'], 'all' ) );
				$photo['oldThumb'] = $this->memberData['pp_small_photo'];
				$photo['status']   = 'ok';
				$photo['thumb']    = $photo['pp_thumb_photo'];
				$this->returnJsonArray( $photo );
			}
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			switch ( $msg )
			{
				case 'upload_to_big':
					$this->returnJsonError( sprintf( $this->lang->words[ 'pp_' . $msg ], $this->memberData['photoMaxKb'] ) );
				break;
				default:
					$this->returnJsonError( $this->lang->words[ 'pp_' . $msg ] );
				break;
				case 'PROFILE_DISABLED':
					$this->returnJsonError( 'member_profile_disabled' );
				break;
			}
		}
	}
	
	/**
	 * Remove the photo
	 *
	 * @return	@e void [HTML]
	 */
	protected function _remove()
	{
		$this->photo->remove( $this->memberData['member_id'] );
		
		$return = IPSMember::buildDisplayData( IPSMember::load( $this->memberData['member_id'], 'all' ) );
		$return['oldThumb'] = $this->memberData['pp_small_photo'];
		$return['status'] = 'deleted';
		$return['thumb']  = $return['pp_thumb_photo'];
		
		$this->returnJsonArray( $return );
	}
	
	/**
	 * Display the photo editor
	 *
	 * @return	@e void [HTML]
	 */
	protected function _show()
	{
		$this->returnHtml( $this->photo->getEditorHtml( $this->memberData ) );
	}
	
	/**
	 * Crop the chosen photo
	 *
	 * @return	@e void [JSON array]
	 */
	protected function _cropPhoto()
	{
		$x1   = intval( $this->request['x1'] );
		$x2   = intval( $this->request['x2'] );
		$y1   = intval( $this->request['y1'] );
		$y2   = intval( $this->request['y2'] );
		$dims = array( 'x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2 );
		
		/* Crop and fetch return data */
		$return = $this->photo->cropPhoto( $this->memberData['member_id'], $dims );
		$member = IPSMember::buildDisplayData( IPSMember::load( $this->memberData['member_id'], 'all' ) );
		
		if ( $return['status'] == 'ok' )
		{
			$this->returnJsonArray( array_merge( $member, $return ) );
		}
		else
		{
			$this->returnJsonError('fail');
		}
	}
}