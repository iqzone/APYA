<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile Photo
 * Last Updated: $Date: 2012-06-06 06:31:40 -0400 (Wed, 06 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Revision: 10874 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_photo extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load lang file */
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
		
		/* Load library */
		$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
		$this->photo = new $classToLoad( $registry );
		
		switch( $this->request['do'] )
		{
			case 'save':
				$this->_save();
			break;
			default:
			case 'show':
				$output = $this->_show();
			break;
			case 'remove':
				$this->_remove();
			break;
		}
		
		/* Got anything to show? */
		if ( $output )
		{
			$this->registry->output->addContent( $output );
			$this->registry->output->setTitle( $this->lang->words['pe_title'] . ' - ' . ipsRegistry::$settings['board_name'] );
			$this->registry->output->addNavigation( $this->lang->words['pe_title'], '' );
			$this->registry->output->sendOutput();
		}
	}
	
	/**
	 * Removes pics
	 *
	 * @return	@e void [HTML]
	 */
	protected function _remove()
	{
		$this->photo->remove( $this->memberData['member_id'] );
		
		/* redirect to show */
		$this->registry->output->redirectScreen( $this->lang->words['pp_photo_edited'], $this->settings['base_url'] . 'showuser=' . $this->memberData['member_id'], $this->memberData['members_seo_name'] );
	}
	
	/**
	 * Saves data
	 *
	 * @return	@e void [HTML]
	 */
	protected function _save()
	{
		$getJson     = intval( $this->request['getJson'] );
		$photoType   = $this->request['photoType'];
		$gravatar	 = $this->request['gravatar'];
		
		if ( $getJson )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
			$ajax = new $classToLoad();
		}
		
		/* Do it */
		try
		{
			$photo = $this->photo->save( $this->memberData, $photoType, $gravatar );
			
			if ( is_array( $photo ) )
			{
				if ( $getJson )
				{
					$photo['oldThumb'] = $this->memberData['pp_small_photo'];
					return $ajax->returnJsonArray( $photo, false, 'text/html' );
				}
				else
				{
					/* redirect to show */
					$this->registry->output->redirectScreen( $this->lang->words['pp_photo_edited'], $this->settings['base_url'] . 'showuser=' . $this->memberData['member_id'], $this->memberData['members_seo_name'] );
				}
			}
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			if ( $msg == 'upload_to_big' )
			{
				$this->lang->words[ 'pp_' . $msg ] = sprintf( $this->lang->words[ 'pp_' . $msg ], $this->memberData['photoMaxKb'] );
			}
						
			switch ( $msg )
			{
				default:
					if ( $getJson )
					{
						
				
						if ( $this->lang->words[ 'pp_' . $msg ] )
						{
							$msg = $this->lang->words[ 'pp_' . $msg ];
						}
						elseif ( $this->lang->words[ $msg ] )
						{
							$msg = $this->lang->words[ $msg ];
						}
						else
						{
							$msg = $this->lang->words['pp_generic_error'];
						}

						$ajax->returnJsonError( $msg, 'text/html' );
						exit();
					}
					else
					{
						$this->registry->getClass('output')->showError( $this->lang->words[ 'pp_' . $msg ], 1027, null, null, 403 );
					}
				break;
				case 'PROFILE_DISABLED':
					if ( $getJson )
					{
						$ajax->returnJsonError( 'member_profile_disabled', 'text/html' );
						exit();
					}
					else
					{
						$this->registry->getClass('output')->showError( $this->lang->words['member_profile_disabled'], 1027, null, null, 403 );
					}
				break;
			}
		}
	}
	
	/**
	 * Display the photo editor
	 *
	 * @return	@e void [HTML]
	 */
	protected function _show()
	{
		/* Check we're a member */
		if ( !$this->memberData['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['pp_generic_error'], 1027.1, null, null, 403 );
		}
		
		return $this->photo->getEditorHtml( $this->memberData );
	}
}