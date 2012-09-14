<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Tagging: Abstract class
 * Matt Mecham
 * Last Updated: $Date: 2012-05-14 07:38:45 -0400 (Mon, 14 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		24 Feb 2011
 * @version		$Revision: 10742 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

abstract class classes_tag_abstract
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	protected $app  = '';
	protected $area = '';
	protected $errorMsg = '';
	
	/**
	 * Init
	 *
	 * @return	@e void
	 */
	public function init()
	{
		
	}

	/**
	 * @return the $errorMsg
	 */
	public function getErrorMsg()
	{
		return $this->errorMsg;
	}

	/**
	 * @return the $errorMsg
	 */
	public function getFormattedError()
	{
		if( $this->errorMsg )
		{
			switch( $this->errorMsg )
			{
				case 'too_few_tags':
					return sprintf( $this->lang->words['too_few_tags'], $this->_getMinTags() );
				break;
				
				case 'too_many_tags':
					return sprintf( $this->lang->words['too_many_tags'], $this->_getMaxTags() );
				break;
				
				default:
					return $this->lang->words[ $this->errorMsg ] ? $this->lang->words[ $this->errorMsg ] : $this->errorMsg;
				break;
			}
		}
		
		return '';
	}

	/**
	 * @param field_type $errorMsg
	 */
	public function setErrorMsg( $errorMsg )
	{
		$this->errorMsg = $errorMsg;
	}

	/**
	 * @return the $app
	 */
	public function getApp()
	{
		return $this->app;
	}

	/**
	 * @return the $area
	 */
	public function getArea()
	{
		return $this->area;
	}

	/**
	 * @return the search section
	 */
	public function getSearchSection()
	{
		return '';
	}

	/**
	 * @param field_type $app
	 */
	public function setApp( $app )
	{
		$this->app = $app;
	}

	/**
	 * @param field_type $area
	 */
	public function setArea( $area )
	{
		$this->area = $area;
	}
		
	/**
	 * @return boolean
	 */
	public function isEnabled()
	{
		return $this->_isEnabled();
	}
	
	/**
	 * Check to make sure our tags are ok
	 * @param	mixed	Array of tag text or string (tag, tag, tag)
	 * @param  array $where array (contains any of these: meta_id, meta_visible, meta_parent_id, member_id, permission_string)
	 * @param	string	mask to check (add / replace)
	 * 
	 */
	public function checkAdd( $tags, $where, $mask='add' )
	{
		$where = $this->_cleanWhere( $where );
		
		/* Quick check */
		if ( $this->can( $mask, $where ) !== true )
		{
			$this->setErrorMsg( 'no_permission' );
			return false;
		}
		
		/* Clean my tags please */
		$tags = $this->_cleanTags( $tags );
		
		if ( $tags === false )
		{
			/* Error message is populated at this point */
			return false;
		}

		return true;
	}
	
	/**
	 * Add tags to the dee bee
	 * @param	mixed	Array of tag text or string (tag, tag, tag)
	 * @param  array $where array (contains any of these: meta_id, meta_visible, meta_parent_id, member_id, permission_string)
	 * @param	string	mask to check (add / replace)
	 * 
	 */
	public function add( $tags, $where, $mask='add' )
	{
		$where       = $this->_cleanWhere( $where );
		$memberId    = ( isset( $where['member_id'] ) ) ? intval( $where['member_id'] ) : $this->memberData['member_id'];
		$trackPrefix = false;
		$c           = 0;

		/* Quick check */
		if ( $this->can( $mask, $where ) !== true )
		{
			$this->setErrorMsg( 'no_permission' );
			return false;
		}
		
		/* If we're adding, check that some don't already exist (see bug report 34329) */
		if ( $mask == 'add' )
		{
			$this->deleteByMetaId( $where['meta_id'] );
		}
		
		/* Clean my tags please */
		$tags = $this->_cleanTags( $tags );

		if ( $tags === false )
		{
			/* Error message is populated at this point */
			return false;
		}
		
		/* Ok, we need some data please */
		$where['meta_parent_id']    = isset( $where['meta_parent_id'] )    ? $where['meta_parent_id']    : $this->getParentId( $where );
		$where['permission_string'] = isset( $where['permission_string'] ) ? $where['permission_string'] : $this->getPermissionData( $where );
		$where['meta_visible']      = isset( $where['meta_visible'] )      ? $where['meta_visible']      : $this->getIsVisible( $where );
				
		/* Prefixes */
		if ( ! empty( $_REQUEST[ $this->_getFieldId( $where ) . '_prefix' ] ) )
		{
			if ( $this->_prefixesEnabled( $where ) )
			{
				$trackPrefix = true;
			}
		}
		
		/* Update permission row */
		$this->_setStoredPermissionData( array( 'meta_id'           => $where['meta_id'],
												'meta_parent_id'	=> $where['meta_parent_id'],
												'permission_string' => $where['permission_string'],
												'meta_visible'		=> $where['meta_visible'] ) );
		
		/* Add tags */
		foreach( $tags as $tag )
		{
			$this->DB->insert( 'core_tags', array( 'tag_aai_lookup'     => $this->_getKey( array( 'meta_id'        => $where['meta_id'] ) ),
												   'tag_aap_lookup'     => $this->_getKey( array( 'meta_parent_id' => $where['meta_parent_id'] ) ),
							   					   'tag_meta_app'       => $this->getApp(),
												   'tag_meta_area'      => $this->getArea(),
												   'tag_meta_id'        => intval( $where['meta_id'] ),
												   'tag_meta_parent_id' => $where['meta_parent_id'],
												   'tag_member_id'      => $memberId,
												   'tag_added'			=> IPS_UNIX_TIME_NOW,
												   'tag_prefix'			=> ( ! $c && $trackPrefix ) ? 1 : 0,
												   'tag_text'			=> $this->DB->addSlashes( $tag ) ) );
			$c++;
		}
		
		/* Rebuild cache */
		$this->_addCache( $where );
	}
	
	/**
	 * Add tags to the dee bee
	 * @param	mixed	Array of tag text or string (tag, tag, tag)
	 * @param  array $where array (contains any of these: meta_id, meta_visible, meta_parent_id, member_id, permission_string)
	 * 
	 */
	public function replace( $tags, $where )
	{
		$where = $this->_cleanWhere( $where );
		
		/* Can we edit first of all? */
		$can = $this->can( 'edit', $where );
		
		if ( $can )
		{
			/* Remove */
			if ( ! empty( $where['meta_id'] ) )
			{
				$this->deleteByMetaId( $where['meta_id'] );
			}
			
			return $this->add( $tags, $where, 'edit' );
		}
		else
		{
			return $can;
		}
	}
	
	/**
	 * Deletes tags dot dot dot, sorry, I mean...
	 * 
	 * @param	array		$metaIds	Array of meta ids to delete
	 * @return	@e void
	 */
	public function deleteByMetaId( $metaIds )
	{
		if ( empty( $metaIds ) )
		{
			return false;
		}
		
		/* Basic */
		if ( ! is_array( $metaIds ) )
		{
			$metaIds = array( $metaIds );
		}
		
		/* Fetch keys */
		foreach( $metaIds as $id )
		{
			$keys[] = $this->_getKey( array( 'meta_id' => $id ) );
		}
		
		/* Delete tags */
		$this->DB->delete( 'core_tags', 'tag_aai_lookup IN (\'' . implode( "','", $keys ) . '\')' );
		
		/* Delete perms */
		$this->DB->delete( 'core_tags_perms', 'tag_perm_aai_lookup IN (\'' . implode( "','", $keys ) . '\')' );
		
		/* Delete cache */
		$this->DB->delete( 'core_tags_cache', 'tag_cache_key IN (\'' . implode( "','", $keys ) . '\')' );
	}
	
	/**
	 * Basic permission check
	 * @param	string	$what (add/remove/edit/create) [ add = add new tags to items, create = create unique tags ]
	 * @param	array	$where data
	 */
	public function can( $what, $where )
	{
		/* Tagging disabled? */
		if ( ! $this->settings['tags_enabled'] )
		{
			return false;
		}
		
		/* Admins are god */
		if ( $this->memberData['g_is_supmod'] )
		{
			return true;
		}
		
		/* Member disabled */
		if ( $this->memberData['bw_disable_tagging'] )
		{
			return false;
		}
		
		/* Group disabled */
		if ( $this->memberData['gbw_disable_tagging'] )
		{
			return false;
		}
		
		if ( $what == 'prefix' )
		{
			/* Member disabled */
			if ( $this->memberData['bw_disable_prefixes'] )
			{
				return false;
			}
			
			/* Group disabled */
			if ( $this->memberData['gbw_disable_prefixes'] )
			{
				return false;
			}
		}
		
		return null;
	}
	
	/**
	 * Fetches tags from the debbie, I mean db. Avoiding the cache
	 * @param int 		meta ID
	 * @param string	Tag to match (foo matches foo, foobar, foobaz, etc)
	 */
	public function getRawTagsByMetaId( $metaId, $text='' )
	{
		$tags  = array();
		$where = ( ! empty( $text ) ) ? ' AND tag_text LIKE \'' . $this->DB->addSlashes( $text ) . '%\'' : '';
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_tags',
								 'where'  => 'tag_aai_lookup=\'' . $this->_getKey( array( 'meta_id' => $metaId ) ) . '\'' . $where,
								 'order'  => 'tag_id' ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$tags[ $row['tag_id'] ] = $row;
		}
		
		return $tags;
	}
	
	/**
	 * Fetches tags from the debbie, I mean db. Avoiding the cache
	 * @param int meta ID
	 * @param string	Tag to match (foo matches foo, foobar, foobaz, etc)
	 */
	public function getRawTagsByParentId( $parentId, $text='' )
	{
		$tags  = array();
		$where = ( ! empty( $text ) ) ? ' AND tag_text LIKE \'' . $this->DB->addSlashes( $text ) . '%\'' : '';
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_tags',
								 'where'  => 'tag_aap_lookup=\'' . $this->_getKey( array( 'meta_parent_id' => $parentId ) ) . '\'' . $where ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$tags[ $row['tag_id'] ] = $row;
		}
		
		return $tags;
	}
	
	/**
	 * Get tags main interface
	 * 
	 * @param		array	$where
	 * @return		array	Formatted data
	 */
	public function getTagsByMetaId( $where )
	{
		/* get from cache */
		$tags = $this->_getCache( $where );

		return $this->_formatCachedData( $tags );
	}
	
	/**
	 * Get tags by cache key interface
	 *
	 * @param		array	$where
	 * @return		array	Formatted data
	 */
	public function getTagsByCacheKey( $key )
	{
		/* get from cache */
		$tags = $this->_getCache( array( 'tag_cache_key' => $key ) );
	
		return $this->_formatCachedData( $tags );
	}
	
	/**
	 * Format tags for display
	 * @param array $tagData array( 'tag', 'tag', 'tag' )
	 * @param string $tag_aai_lookup (tag_cache_key)
	 * @return string
	 */
	public function formatTagsForDisplay( $tagData, $tag_aai_lookup='' )
	{
		$array		= array();
		$string		= array();
		$truncated	= array();
		$linked		= array();
		$diff		= 0;
		$text		= '';
		$len		= 0;
		
		if ( ! is_array( $tagData ) )
		{
			return null;
		}
		
		/* System enabled? */
		if ( ! $this->_isEnabled() )
		{
			return null;
		}
		
		foreach( $tagData as $t )
		{
			$array[]	= $this->registry->output->getTemplate( $this->skin() )->tagEntry( $t, false, $this->getApp(), $this->getSearchSection() );
			$string[]	= $t;
			
			$len += ( IPSText::mbstrlen( $t ) ) + 2;
			
			if ( $len <= $this->settings['tags_max_truncated_len'] )
			{
				$truncated[]	= $t;
				$linked[]		= $this->registry->output->getTemplate( $this->skin() )->tagEntry( $t, true, $this->getApp(), $this->getSearchSection() );
			}
			
			if ( count( $array ) > count( $truncated ) )
			{
				$diff = count( $array ) - count( $truncated );
				$text = ' <span hovercard-ref=\'tagsPopUp\' hovercard-id=\'' . $tag_aai_lookup . '\' class=\'_hovertrigger clickable\'>' . sprintf( $this->lang->words['tag_trunc_has_more'], $diff ) . '</span>';
			}
		}
		
		return array( 'array'				=> $array,
					  'parsed'				=> implode( ', ', $array ),
					  'parsedWithoutComma'	=> implode( ' ' , $array ),
					  'truncatedNoText'		=> implode( ', ', $truncated ),
					  'truncated'			=> implode( ', ', $truncated ) . $text,
					  'truncatedCount'		=> count( $truncated ),
					  'truncatedWithLinks'	=> implode( ', ', $linked ) . $text,
					  'totalCount'			=> count( $array ),
					  'string'				=> implode( ', ', $string ) );
	}
	
	/**
	 * DEFAULT: returns nothing and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	int		Id of parent if one exists or 0
	 */
	public function getParentId( $where )
	{
		return 0;
	}
	
	/**
	 * DEFAULT: returns nothing and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	string	Comma delimiter or *
	 */
	public function getPermissionData( $where )
	{
		return '*';
	}
	
	/**
	 * DEFAULT: returns true and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	int		If meta item is visible (not unapproved, etc)
	 */
	public function getIsVisible( $where )
	{
		return 1;
	}
	
	/**
	 * Enter description here ...
	 * @param array $array (meta_id_field => x.field_id );
	 */
	public function getCacheJoin( $array )
	{
		/* Tags disabled? */
		if ( ! $this->_isEnabled() )
		{
			return null;
		}
		
		$p = ( empty( $array['tag_table_prefix'] ) ) ? 'xxx' : $array['tag_table_prefix'];
		
		return array( 'select' => $p.'.*',
					  'from'   => array( 'core_tags_cache' => $p ),
					  'type'   => 'left',
					  'where'  => $p.'.tag_cache_key=' . $this->DB->buildMd5Statement( $this->DB->buildConcat( array( array( $this->getApp(), 'string' ),  array( ';', 'string'), array( $this->getArea(), 'string' ), array( ';',  'string' ), array( $array['meta_id_field'] ) ) ) ) );
	}
	
	/**
	 * Format joined data for display
	 * 
	 * @param		array	$tags
	 * @return		array	Formatted data
	 */
	public function formatCacheJoinData( $tags )
	{
		return $this->_formatCachedData( $tags );
	}
	
	/**
	 * Moves tags from one meta ID to another (merging, etc) ...
	 * @param array $oldMetaId array
	 * @param int   $newMetaId
	 */
	public function moveTagsByMetaId( $oldMetaIds, $newMetaId )
	{
		$keys   = array();
		$newKey = $this->_getKey( array( 'meta_id' => $newMetaId ) );
		
		/* Basic */
		if ( ! is_array( $oldMetaIds ) )
		{
			$oldMetaIds = array( $oldMetaIds );
		}
		
		/* Fetch keys */
		foreach( $oldMetaIds as $id )
		{
			$keys[] = $this->_getKey( array( 'meta_id' => $id ) );
			$this->_deleteStoredPermissionDataByMetaId( $id );
			$this->_deleteCache( array( 'meta_id' => $id ) );
		}
		
		/* Update tags */
		$this->DB->update( 'core_tags', array( 'tag_meta_id'    => $newMetaId,
											   'tag_aai_lookup' => $newKey ), 'tag_aai_lookup IN (\'' . implode( "','", $keys ) . '\')' );
		
		/* Update permissions */
		$parent      = $this->getParentId( array( 'meta_id' => $newMetaId ) );
		$permissions = $this->getPermissionData( array( 'meta_id' => $newMetaId ) );
		$isVisible   = $this->getIsVisible( array( 'meta_id' => $newMetaId ) );
		
		/* Update permission row */
		$this->_setStoredPermissionData( array( 'meta_id'           => $newMetaId,
												'meta_parent_id'	=> $parent,
												'permission_string' => $permissions,
												'meta_visible'		=> $isVisible ) );
		
		/* Rebuild cache */
		$this->_addCache( array( 'meta_id' => $newMetaId, 'meta_parent_id' => $parent ) );
	}
	
	/**
	 * Moves tags from one parent to another ...
	 * @param array $where
	 * @param int   $newParentId
	 */
	public function moveTagsByParentId( $oldParentId, $newParentId )
	{
		/* Update parent */
		$permissions  = $this->getPermissionData( array( 'meta_parent_id' => $newParentId ) );
		$parentKeyOld = $this->_getKey( array( 'meta_parent_id' => $oldParentId ) );
		$parentKeyNew = $this->_getKey( array( 'meta_parent_id' => $newParentId ) );
		
		/* Update perms  */
		$this->DB->update( 'core_tags_perms', array( 'tag_perm_aap_lookup' => $parentKeyNew, 'tag_perm_text' => $permissions ), 'tag_perm_aap_lookup=\'' . $parentKeyOld . '\'' );
		
		/* Update tags */
		$this->DB->update( 'core_tags', array( 'tag_meta_parent_id' => $newParentId ), 'tag_meta_parent_id=' . intval( $oldParentId ) );
	}
	
	/**
	 * Moves tags to another parent, by meta id
	 * @param array $oldMetaId array
	 * @param int   $newParentId
	 */
	public function moveTagsToParentId( $oldMetaIds, $newParentId )
	{
		$keys   = array();
		
		/* Basic */
		if ( ! is_array( $oldMetaIds ) )
		{
			$oldMetaIds = array( $oldMetaIds );
		}
		
		/* Fetch keys */
		foreach( $oldMetaIds as $id )
		{
			$keys[] = $this->_getKey( array( 'meta_id' => $id ) );
			$this->_deleteCache( array( 'meta_id' => $id ) );
		}
				
		/* Update tags */
		$this->DB->update( 'core_tags', array( 'tag_meta_parent_id' => $newParentId ), 'tag_aai_lookup IN (\'' . implode( "','", $keys ) . '\')' );
		
		/* Update parent */
		$permissions  = $this->getPermissionData( array( 'meta_parent_id' => $newParentId ) );
		$parentKeyNew = $this->_getKey( array( 'meta_parent_id' => $newParentId ) );
		
		/* Update perms  */
		$this->DB->update( 'core_tags_perms', array( 'tag_perm_aap_lookup' => $parentKeyNew, 'tag_perm_text' => $permissions ), 'tag_perm_aai_lookup IN (\'' . implode( "','", $keys ) . '\')' );

		/* Update caches */
		foreach( $oldMetaIds as $id )
		{
			$this->_addCache( array( 'meta_id' => $id, 'meta_parent_id' => $newParentId ) );
		}
	}
	
	/**
	 * Updates all permissions by parent Id
	 * @param int $parentId
	 */
	public function updatePermssionsByParentId( $parentId )
	{
		$parentKey    = $this->_getKey( array( 'meta_parent_id' => $parentId ) );
		$permissions  = $this->getPermissionData( array( 'meta_parent_id' => $parentId ) );
		
		/* Update perms  */
		$this->DB->update( 'core_tags_perms', array( 'tag_perm_text' => $permissions ), 'tag_perm_aap_lookup=\'' . $parentKey . '\'' );
	}
	
	/**
	 * Updates visibility by parent ID
	 * @param int $parentId
	 * @param int	
	 */
	public function updateVisibilityByParentId( $parentId, $visible=1 )
	{
		$parentKey = $this->_getKey( array( 'meta_parent_id' => $parentId ) );
		
		/* Update perms  */
		$this->DB->update( 'core_tags_perms', array( 'tag_perm_visible' => intval($visible) ), 'tag_perm_aap_lookup=\'' . $parentKey . '\'' );
	}
	
	/**
	 * Updates visibility by meta ID
	 * @param array $metaIds
	 * @param int	
	 */
	public function updateVisibilityByMetaId( $metaIds, $visible=1 )
	{
		$keys = array();
		
		/* Basic */
		if ( ! is_array( $metaIds ) )
		{
			$metaIds = array( $metaIds );
		}
		
		/* Fetch keys */
		foreach( $metaIds as $id )
		{
			$keys[] = $this->_getKey( array( 'meta_id' => $id ) );
		}
		
		/* Update perms  */
		$this->DB->update( 'core_tags_perms', array( 'tag_perm_visible' => intval($visible) ), "tag_perm_aai_lookup IN ('" . implode( "','", $keys ) . "')" );
	}
	
	/**
	 * Render something
	 * 
	 * @param	string	view to show
	 * @param	array	Where data to show
	 */
	public function render( $what, $where )
	{
		/* System enabled? */
		if ( ! $this->_isEnabled() )
		{
			return '';
		}
		
		$where   = $this->_cleanWhere( $where );
		
		$tags    = array( 'array' => array(), 'string' => '' );
		$options = array( 'isOpenSystem'    => $this->_isOpenSystem(),
						  'isEnabled'	    => $this->_isEnabled(),
						  'minLen' 		    => $this->_getMinLen(),
						  'maxLen'		    => $this->_getMaxLen(),
						  'minTags'		    => $this->_getMinTags(),
						  'maxTags'		    => $this->_getMaxTags(),
						  'predefinedTags'  => $this->_getPreDefinedTags( $where ),
						  'fieldId'			=> $this->_getFieldId( $where ),
						  'prefixesEnabled' => $this->_prefixesEnabled( $where ),
						  'meta_app'		=> $this->getApp(),
						  'meta_area'		=> $this->getArea(),
						  'meta_parent_id'	=> intval( $where['meta_parent_id'] ) );
		switch( $what )
		{
			case 'entryBox':
				/* set up some basic data */
				if ( ! empty( $where['meta_id'] ) AND empty( $where['existing_tags'] ) )
				{
					/* fetch current tags if available */
					$tags = $this->getTagsByMetaId( $where );
				}
				else if ( ! empty( $where['existing_tags'] ) )
				{
					$where['existing_tags'] = $this->_cleanTags( implode( ',', $where['existing_tags'] ), false );
					
					$tags	= array( 'tags'		 => $where['existing_tags'],
									 'prefix'	 => '',
									 'formatted' => $this->formatTagsForDisplay( $where['existing_tags'], $this->_getKey( array( 'meta_id' => $where['meta_id'] ) ) ) );
				}

				return $this->registry->output->getTemplate( $this->skin() )->tagTextEntryBox( $tags, $options, $where );
			break;
		}
	}
	
	/**
	 * Search for tags
	 * @param mixed $tags	Array or string
	 * @param array $options	Array( 'meta_id' (array), 'meta_parent_id' (array), 'olderThan' (int), 'youngerThan' (int), 'limit' (int), 'sortKey' (string) 'sortOrder' (string) )
	 */
	public function search( $tags, $options )
	{
		/* Fetch search class */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/search/bootstrap.php');/*noLibHook*/
		$search = classes_tags_search_bootstrap::init( $this->getApp(), $this->getArea() );
		
		/* fix up tags */
		$tags = $this->_cleanTags( $tags, FALSE );
		
		if ( $tags === false )
		{
			/* Error message is populated at this point */
			return false;
		}
		
		$metaIds = $search->run( $tags, $options );
		
		return $metaIds;
	}
	
	/**
	 * DEFAULT: Should define this in your own class if you want it differenly displayed
	 * @return	string	Template group
	 */
	protected function skin()
	{
		return 'global_other';
	}
	
	/**
	 * Formats cached data
	 * @param 	array $tags
	 * @return	array
	 */
	protected function _formatCachedData( $tags )
	{
		if ( ! is_array( $tags ) OR ! count( $tags ) OR empty( $tags['tag_cache_text'] ) )
		{
			return null;
		}
		
		/* Unserialise */
		if ( ! IPSLib::isSerialized( $tags['tag_cache_text'] ) )
		{
			return null;
		}
		
		$tagData = unserialize( $tags['tag_cache_text'] );
		
		$tagData['formatted']           = array();
		
		if ( is_array($tagData['tags']) AND count($tagData['tags']) )
		{
			$tagData['formatted']           = $this->formatTagsForDisplay( $tagData['tags'], $tags['tag_cache_key'] );
			$tagData['formatted']['prefix']	= false;
		}
		else
		{
			return null;
		}
		
		if( ! empty( $tagData['prefix'] ) )
		{
			$tagData['formatted']['prefix'] = $this->registry->output->getTemplate( $this->skin() )->tagPrefix( $tagData['prefix'], $this->getApp(), $this->getSearchSection() );
		}
		
		return $tagData;
	}
	
	/**
	 *  Rebuild cache
	 *  @param		array	$where
	 *  @return		void
	 */
	protected function _addCache( $where )
	{
		$where = $this->_cleanWhere( $where );
		
		if ( empty( $where['meta_id'] ) )
		{
			trigger_error( "Meta ID missing", E_USER_ERROR );
		}
		
		$key  = $this->_getKey( $where );
		$tags = $this->getRawTagsByMetaId( $where['meta_id'] );
		$text = array( 'tags' => array(), 'prefix' => '' );
		
		foreach( $tags as $id => $tag )
		{
			$text['tags'][] = $tag['tag_text'];
			
			if ( $tag['tag_prefix'] )
			{
				$text['prefix'] = $tag['tag_text'];
			}
		}
		
		$update = array( 'tag_cache_key'  => $key,
						 'tag_cache_text' => serialize( $text ),
						 'tag_cache_date' => IPS_UNIX_TIME_NOW
						);
		
		$this->DB->replace( 'core_tags_cache', $update, array( 'tag_cache_key' ) );
		
		return $update;
	}
	
	/**
	 *  Rebuild cache
	 *  @param		array	$where
	 *  @return		void
	 */
	protected function _deleteCache( $where )
	{
		$where = $this->_cleanWhere( $where );
		
		if ( empty( $where['meta_id'] ) )
		{
			trigger_error( "Meta ID missing", E_USER_ERROR );
		}
		
		$key  = $this->_getKey( $where );
		
		$this->DB->delete( 'core_tags_cache', 'tag_cache_key=\'' . $key . '\'' );
	}	
	
	/**
	 *  Rebuild cache
	 *  @param		array	$where
	 *  @return		void
	 */
	protected function _getCache( $where )
	{
		$where = $this->_cleanWhere( $where );
		
		if ( ! empty( $where['cache_key'] ) )
		{
			$key = $where['cache_key'];
		}
		else
		{
			if ( empty( $where['meta_id'] ) )
			{
				trigger_error( "Meta ID missing", E_USER_ERROR );
			}
			
			$key	= $this->_getKey( $where );
		}
		
		$cache	= $this->DB->buildAndFetch( array( 'select' => '*',
												   'from'   => 'core_tags_cache',
												   'where'  => 'tag_cache_key=\'' . $key . '\'' ) );
												   
		if ( ! $cache['tag_cache_key'] )
		{
			$cache = $this->_addCache( $where );
		}
		
		return $cache;
	}	
	
	/**
	 * Sets a permission row
	 * @param array $data
	 */
	protected function _setStoredPermissionData( $data )
	{
		$this->DB->replace( 'core_tags_perms', array( 'tag_perm_aai_lookup' => $this->_getKey( array( 'meta_id' => $data['meta_id'] ) ),
													  'tag_perm_aap_lookup' => $this->_getKey( array( 'meta_parent_id' => $data['meta_parent_id'] ) ),
													  'tag_perm_text'		=> trim( IPSText::cleanPermString( $data['permission_string'] ) ),
													  'tag_perm_visible'    => intval( $data['meta_visible'] ) ), array( 'tag_perm_aai_lookup' ) );
		
	}
																 
	/**
	 * Return a permission row for a TID
	 * @param int $metaId
	 * @return array
	 */
	protected function _getStoredPermissionDataByMetaId( $metaId )
	{
		$permRow = $this->DB->build( array( 'select'   => '*',
								 			'from'     => 'core_tags_perms',
							     			'where'    => 'tag_perm_aai_lookup=\'' . $this->_getKey( array( 'meta_id' => $metaId ) ) . '\'' ) );
		return $permRow;
	}
	
	/**
	 * Delete a permission row for a TID
	 * @param int $metaId
	 * @return array
	 */
	protected function _deleteStoredPermissionDataByMetaId( $metaId )
	{
		$this->DB->delete( 'core_tags_perms', 'tag_perm_aai_lookup=\'' . $this->_getKey( array( 'meta_id' => $metaId ) ) . '\'' );
	}
	
	/**
	 * Get text field name (future expansion)
	 * @param 	array	Where Data
	 * @return 	Booyaleean
	 */
	protected function _getFieldId( $where )
	{
		return 'ipsTags';	
	}
	
	/**
	 * Get maximum tags allowed
	 * 
	 * @return 	Booyaleean
	 */
	protected function _getMaxTags()
	{
		return intval( $this->settings['tags_max'] );	
	}
	
	/**
	 * Get minimum tags allowed
	 * 
	 * @return 	Booyaleean
	 */
	protected function _getMinTags()
	{
		return intval( $this->settings['tags_min'] );	
	}
	
	/**
	 * Get maximum tag length allowed
	 * 
	 * @return 	Booyaleean
	 */
	protected function _getMaxLen()
	{
		return intval( $this->settings['tags_len_max'] );	
	}
	
	/**
	 * Get minimum tag length allowed
	 * 
	 * @return 	Booyaleean
	 */
	protected function _getMinLen()
	{
		return intval( $this->settings['tags_len_min'] );	
	}
	
	/**
	 * Must clean words before they are added
	 * 
	 * @return 	Booyaleean
	 */
	protected function _mustCleanWords()
	{
		return ( $this->settings['tags_clean'] ) ? true : false;	
	}
	
	/**
	 * Can set an item as a topic prefix
	 * @param 	array	Where Data
	 * @return 	Booyaleean
	 */
	protected function _prefixesEnabled( $where )
	{
		switch( $this->settings['tags_can_prefix'] )
		{
			case 0:
				return false;
			break;
			case 1:
				return ( $this->memberData['g_is_supmod'] ) ? true : false;
			break;
			case 2:
				return $this->can( 'prefix', $where );
			break;
		}	
	}
	
	/**
	 * Is this an open system
	 * Open is where anyone can create tags, closed is where they are pre-defined
	 * 
	 * @return 	Booyaleean
	 */
	protected function _isOpenSystem()
	{
		return ( $this->settings['tags_open_system'] ) ? true : false;	
	}
	
	/**
	 * Force all tags to lowercase?
	 * 
	 * @return 	Booyaleean
	 */
	protected function _forceLower()
	{
		return ( $this->settings['tags_force_lower'] ) ? true : false;	
	}
	
	/**
	 * Is tagging enabled?
	 * 
	 * @return 	Booyaleean
	 */
	protected function _isEnabled()
	{
		return ( $this->settings['tags_enabled'] ) ? true : false;	
	}
	
	/**
	 * Fetch a list of pre-defined tags
	 * 
	 * @param 	array	Where Data
	 * @return	Array of pre-defined tags or null
	 */
	protected function _getPreDefinedTags( $where=array() )
	{
		if ( $this->settings['tags_predefined'] )
		{
			$this->settings['tags_predefined'] = str_replace( array( "<br>", "<br />", "\r", "," ), "\n", $this->settings['tags_predefined'] );
			$array = explode( "\n", $this->settings['tags_predefined'] );
			$final = array();
			
			foreach( $array as $a )
			{
				$final[] = trim( $a );
			}
			
			return $final;
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Strip/remove HTML ...
	 * @param string $tag
	 */
	private function _stripHtml( $tag )
	{
		return str_replace( array( '&#62;', '&#60;', '&amp;', '&quot;', '&#39;', '<', '>', '"', "'" ), '', $tag );
	}
	
	/**
	 * Build a key
	 */
	private function _getKey( $where )
	{
		$where = $this->_cleanWhere( $where );
		
		if ( isset( $where['meta_id'] ) )
		{
			return md5( $this->getApp() . ';' . $this->getArea() . ';' . intval( $where['meta_id'] ) );
		}
		else if ( isset( $where['meta_parent_id'] ) )
		{
			return md5( $this->getApp() . ':' . $this->getArea() . ':' . intval( $where['meta_parent_id'] ) );
		}
	}
	
	/**
	 * Cleans incoming tags
	 * @param	String or Array		Comma delim string or array of tags
	 * @param	Bool				If TRUE, will check minimum and maximum amounts of tags - not necessary for searching
	 * @return	Array				Array of cleaned tags
	 */
	private function _cleanTags( $tags, $checkForMinimumAndMaximum=TRUE )
	{
		/* Sort out tags */
		if ( ! is_array( $tags ) )
		{
			if ( strstr( $tags, ',' ) )
			{
				$_tags = explode( ',', IPSText::cleanPermString( $tags ) );
				$tags  = array();
				
				foreach( $_tags as $t )
				{
					if ( $t )
					{
						$tags[] = $this->_stripHtml( trim( ( $this->_forceLower() ? IPSText::mbstrtolower( $t ) : $t ) ) );
					}
				}
			}
			else
			{
				if ( ! strlen( $tags ) )
				{
					return false;
				}
				
				$tags = array( $this->_stripHtml( ( $this->_forceLower() ? IPSText::mbstrtolower( $tags ) : $tags ) ) );
			}
		}
		
		/* So.. got tags to parse? */
		if ( count( $tags ) )
		{
			/* Make sure they are all unique */
			$tags = array_unique( $tags );
			
			/* Check for min/max string length */
			if ( $checkForMinimumAndMaximum and ( $this->_getMaxLen() OR $this->_getMinLen() ) )
			{
				$_tags = $tags;
				$tags  = array();
				
				foreach( $_tags as $tag )
				{
					if ( $this->_getMaxLen() )
					{
						if ( IPSText::mbstrlen( $tag ) > $this->_getMaxLen() )
						{
							continue;
						}
					}
					
					if ( $this->_getMinLen() )
					{
						if ( IPSText::mbstrlen( $tag ) < $this->_getMinLen() )
						{
							continue;
						}
					}
					
					$tags[] = $tag;
				}
			}
			
			/* removes any bad words */
			$badwords = $this->cache->getCache('badwords');
	
			if ( $this->_mustCleanWords() AND ( is_array( $badwords ) && count( $badwords ) ) )
			{
				$_tags = $tags;
				$tags  = array();
				
				foreach( $_tags as $tag )
				{
					$_bad	= false;
					
					foreach( $badwords as $badword )
					{
						if( strtolower($tag) == strtolower($badword['type']) )
						{
							$_bad	= true;
							break;
						}
					}
					
					if( !$_bad )
					{
						$tags[] = $tag;
					}
				}
			}
		}
				
		/* Now, do we have a sufficient number of tags? */
		if ( $checkForMinimumAndMaximum && $this->_getMaxTags() && count( $tags ) > $this->_getMaxTags() )
		{
			$this->setErrorMsg('too_many_tags');
			return false;
		}
		
		/* Perhaps not enough? */
		if ( $checkForMinimumAndMaximum && $this->_getMinTags() && count( $tags ) < $this->_getMinTags() )
		{
			$this->setErrorMsg('too_few_tags');
			return false;
		}
		
		/* Generic catch all in case min/max tags aren't set up. */
		if ( ! count( $tags ) )
		{
			$this->setErrorMsg('too_few_tags');
			return false;
		}
		
		/* Phew. */
		return $tags;
	}
	
	/**
	 * Clean where stuffs
	 * Ensures that DB naming conventions aren't used
	 * @param	Array	Dirty where
	 * @return	Array	Clean where
	 */
	private function _cleanWhere( $where )
	{
		$clean = array( 'tag_meta_app', 'tag_meta_area', 'tag_meta_id', 'tag_meta_parent_id', 'tag_member_id', 'tag_aai_lookup', 'tag_cache_key' );
		
		if ( is_numeric( $where ) )
		{
			$where = array( 'meta_id' => $where );
		}
		
		if ( is_array( $where ) )
		{
			foreach( $where as $k => $v )
			{
				if ( in_array( $k, $clean ) )
				{
					unset( $where[ $k ] );
					$where[ str_replace( 'tag_', '', $k ) ] = $v;
				}
			}
		}
		
		if ( empty( $where['meta_app'] ) )
		{
			$where['meta_app'] = $this->getApp();
		}
		
		if ( empty( $where['meta_area'] ) )
		{
			$where['meta_area'] = $this->getArea();
		}
		
		return $where;
	}
	
}