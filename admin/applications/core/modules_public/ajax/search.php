<?php
/**
 * @file		search.php 	AJAX configure VNC filters
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		2/14/2011
 * $LastChangedDate: 2011-05-25 10:30:28 -0400 (Wed, 25 May 2011) $
 * @version		v3.3.3
 * $Revision: 8887 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		public_core_ajax_search
 * @brief		Search VNC configurator
 * 
 */
class public_core_ajax_search extends ipsAjaxCommand
{	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Get forums class
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('class_forums' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
			$this->registry->getClass('class_forums')->strip_invisible = 1;
			$this->registry->getClass('class_forums')->forumsInit();
		}
		
		$this->lang->loadLanguageFile( array( 'public_search' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'showForumsVncFilter':
				$this->showForm();
			break;
			
			case 'saveForumsVncFilter':
				$this->saveForm();
			break;
		}
	}

	/**
	 * Save the form to configure VNC forum filters
	 *
	 * @return	@e void
	 */
	public function saveForm()
	{
		$vncPrefs	= IPSMember::getFromMemberCache( $this->memberData, 'vncPrefs' );

		/* Filter forums for VNC */
		if( !empty($this->request['saveVncFilters']) )
		{
			$this->request['saveVncFilters']	= rtrim( $this->request['saveVncFilters'], ',' );
			
			if( $this->request['saveVncFilters'] == 'all' )
			{
				unset($vncPrefs['forums']['vnc_forum_filter']);
			}
			else if( strpos( $this->request['saveVncFilters'], ',' ) !== false )
			{
				$vncPrefs['forums']['vnc_forum_filter']	= explode( ',', $this->request['saveVncFilters'] );
			}
			else if( !empty($this->request['saveVncFilters']) )
			{
				$vncPrefs['forums']['vnc_forum_filter']	= array( $this->request['saveVncFilters'] );
			}
		}
		
		IPSMember::setToMemberCache( $this->memberData, array( 'vncPrefs' => $vncPrefs ) );
		
		$this->returnJsonArray( array( 'ok' => true ) );
	}
	
	/**
	 * Show the form to configure VNC forum filters
	 *
	 * @return	@e void
	 */
	public function showForm()
	{
		$_data		= $this->_getData();
		$vncPrefs	= IPSMember::getFromMemberCache( $this->memberData, 'vncPrefs' );
		$fFP		= $vncPrefs == null ? null : ( empty($vncPrefs['forums']['vnc_forum_filter']) ? null : $vncPrefs['forums']['vnc_forum_filter'] );
		
		$this->returnHtml( $this->registry->output->getTemplate('search')->forumsVncFilters( $_data, $fFP ) );
	}

	/**
	 * Fetches forum jump data
	 *
	 * @return	string
	 */
	private function _getData()
	{
		$depth_guide = 0;
		$links		 = array();
		
		if( is_array($this->registry->class_forums->forum_cache['root'] ) AND count($this->registry->class_forums->forum_cache['root'] ) )
		{
			foreach($this->registry->class_forums->forum_cache['root'] as $forum_data )
			{
				if ( $forum_data['sub_can_post'] or ( isset($this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) AND is_array($this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) AND count($this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) ) )
				{
					$forum_data['redirect_on'] = isset( $forum_data['redirect_on'] ) ? $forum_data['redirect_on'] : 0;
					
					if ( $forum_data['redirect_on'] == 1 )
					{
						continue;
					}
					
					$links[] = array( 'important' => true, 'depth' => $depth_guide, 'title' => $forum_data['name'], 'id' => $forum_data['id'] );
					
					if ( isset($this->registry->class_forums->forum_cache[ $forum_data['id'] ]) AND is_array($this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) )
					{
						$depth_guide++;
						
						foreach($this->registry->class_forums->forum_cache[ $forum_data['id'] ] as $forum_data )
						{
							if ( $forum_data['redirect_on'] == 1 )
							{
								continue;
							}						
						
							$links[] = array( 'depth' => $depth_guide, 'title' => $forum_data['name'], 'id' => $forum_data['id'] );
					
							$links = $this->_getDataRecursively( $forum_data['id'], $links, $depth_guide );			
						}
						
						$depth_guide--;
					}
				}
			}
		}
		
		return $links;
	}
	
	/**
	 * Internal helper function for forumsForumJump
	 *
	 * @param	integer	$root_id
	 * @param	array	$links
	 * @param	string	$depth_guide
	 * @return	string
	 */
	private function _getDataRecursively( $root_id, $links=array(), $depth_guide=0 )
	{
		if ( isset( $this->registry->class_forums->forum_cache[ $root_id ] ) AND is_array($this->registry->class_forums->forum_cache[ $root_id ] ) )
		{
			$depth_guide++;
			
			foreach($this->registry->class_forums->forum_cache[ $root_id ] as $forum_data )
			{
				if ( $forum_data['redirect_on'] == 1 )
				{
					continue;
				}
				
				$links[] = array( 'depth' => $depth_guide, 'title' => $forum_data['name'], 'id' => $forum_data['id'] );
				
				$links = $this->_getDataRecursively( $forum_data['id'], $links, $depth_guide );
			}
		}

		return $links;
	}
}