<?php
/**
 * @file		tabs.php 	AJAX storage of tab order preference
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		8th Feb 2011
 * $LastChangedDate: 2011-03-10 16:00:38 -0500 (Thu, 10 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 8021 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_core_ajax_tabs
 * @brief		AJAX storage of tab order preference
 *
 */
class admin_core_ajax_tabs extends ipsAjaxCommand 
{
	/**
	 * Array of valid tab keys
	 *
	 * @var		$tabKeys
	 */
	protected $tabKeys	= array( 'core', 'forums', 'members', 'lookfeel', 'support', 'reports', 'other' );
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$registry->getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ), 'core' );

		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'save':
				$this->_saveTabs();
			break;
		}
	}
	
	/**
	 * Save tab preference order
	 *
	 * @return	@e void
	 */
	protected function _saveTabs()
	{
		//-----------------------------------------
		// Store order
		//-----------------------------------------
		
		$order	= array();
		$append	= array();
		
		foreach( $this->tabKeys as $pos => $key )
		{
			if( $this->request['pos_' . $key ] )
			{
				$order[ $this->request['pos_' . $key ] ] = $key;
			}
			else
			{
				$append[] = $key;
			}
		}
		
		if( count($append) )
		{
			$order = array_merge( $order, $append );
		}
		
		ksort($order);
		
		//-----------------------------------------
		// Save preference
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'tabOrder', $order );
		
		//-----------------------------------------
		// Return new order
		//-----------------------------------------
		
		$this->returnJsonArray( array( 'order' => $order ) );
	}
}