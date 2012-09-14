<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Like Ajax
 * Last Updated: $LastChangedDate: 2011-05-05 12:03:47 +0100 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8644 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_skingen extends ipsAjaxCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinGenerator.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinGenerator( $registry );
		
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'save':
				$this->_save();
			break;
        }
    }
    
	/**
     * Find tags
     *
     * @return	@e void
     */
    protected function _save()
    {  
    	/* Init */
    	$storedClasses  = json_decode( $_POST['storedClasses'], true );
    	$storedSettings = json_decode( $_POST['storedSettings'], true );
    	$css			= $_POST['css'];
    	
    	/* Build it, ship it, wiggle your hips a bit */
    	try
    	{
    		/* Apologies for the previous comment */
    		$this->skinFunctions->resetMemberAndSwitchSkin( $this->memberData['member_id'] );
    	
    		$this->skinFunctions->save( array( 'css' => $css, 'storedSettings' => $storedSettings, 'storedClasses' => $storedClasses ), $this->memberData['member_id'] );
    	}
    	catch( Exception $e )
    	{
    		return $this->returnJsonArray( array('status' => 'error', 'msg' => $e->getMessage() ) );
    	}
    	
    	/* Done */
		return $this->returnJsonArray( array('status' => 'ok' ) );
    }
   
}
