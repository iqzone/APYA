<?php
/**
 * @file		version_upgrade.php		2.5 Upgrade Logic
 *
 * $Copyright: $
 * $License: $
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * $Revision: 4 $
 * @since 		20th October 2011
 */

/**
 *
 * @class	version_upgrade
 * @brief	2.5 Upgrade Logic
 *
 */
class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	private $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->request  =& $registry->fetchRequest();
		
		/* Init */
		$pergo        = 100;
		if ( !$this->request['st'] )
		{
			$start = array( 0, 0 );
		}
		else
		{
			$start = explode( '-', $this->request['st'] );
		}
		$latestId     = 0;
		$latestParent = 0;
		$latestTime   = 0;
		$tagCache     = array();
												
		/* Get them */
		ipsRegistry::DB()->build( array(
			'select'	=> '*',
			'from'		=> 'tags_index',
			'where'		=> "app='blog'",
			'group'		=> 'type_id',
			'order'		=> 'type_id ASC',
			'limit'		=> array( $start[0], 50 )
			) );
						
		$e = ipsRegistry::DB()->execute();
		
		/* Are you leeeeeeeeaving? */
		if ( !ipsRegistry::DB()->getTotalRows( $e ) )
		{
			return true;
		}
		
		/* Nope, let's do some converting! */
		while ( $row = ipsRegistry::DB()->fetch( $e ) )
		{
			$tagsCache = array();
			
			ipsRegistry::DB()->build( array(
				'select'	=> '*',
				'from'		=> 'tags_index',
				'where'		=> "app='blog' AND type_id={$row['type_id']}",
				'order'		=> 'id ASC',
				'limit'		=> array( $start[1], $pergo )
				) );
								
			$f = ipsRegistry::DB()->execute();
			
			if ( !ipsRegistry::DB()->getTotalRows( $f ) )
			{
				$this->request['st'] = ( $start[0] + 50 ) . '-0';
				continue;
			}
			else
			{
				$this->request['st'] = $start[0] . '-' . ( $start[1] + $pergo );
			}
			
			while ( $t = ipsRegistry::DB()->fetch( $f ) )
			{
				ipsRegistry::DB()->insert( 'core_tags', array(
					'tag_aai_lookup'		=> md5( 'blog;entries;' . $t['type_id'] ),
					'tag_aap_lookup'		=> md5( 'blog:entries:' . $t['type_id_2'] ),
					'tag_meta_app'			=> 'blog',
					'tag_meta_area'			=> 'entries',
					'tag_meta_id'			=> $t['type_id'],
					'tag_meta_parent_id'	=> $t['type_id_2'],
					'tag_member_id'			=> $t['member_id'],
					'tag_added'				=> $t['updated'],
					'tag_prefix'			=> 0,
					'tag_text'				=> $t['tag'],
					) );
					
				$tagsCache[] = $t['tag'];
			}
							
			ipsRegistry::DB()->insert( 'core_tags_cache', array(
				'tag_cache_key'		=> md5( 'blog;entries;' . $row['type_id'] ),
				'tag_cache_text'	=> serialize( array( 'tags' => $tagsCache, 'prefix' => '' ) ),
				'tag_cache_date'	=> $row['updated'],
				) );
				
			ipsRegistry::DB()->insert( 'core_tags_perms', array(
				'tag_perm_aai_lookup'		=> md5( 'blog;entries;' . $row['type_id'] ),
				'tag_perm_aap_lookup'		=> md5( 'blog:entries:' . $row['type_id_2'] ),
				'tag_perm_text'				=> '*',
				'tag_perm_visible'			=> 1,
				) );
		}
		
		/* Next! */
		$registry->output->addMessage( "Converting Tags..." );
		return false;
	}
}