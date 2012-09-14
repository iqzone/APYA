<?php

/*
+--------------------------------------------------------------------------
|   IP.Blog Component v<#VERSION#>
|   =============================================
|   by Remco Wilting
|   (c) 2001 - 2005 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionblog.com
+--------------------------------------------------------------------------
| > $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
| > $Revision: 4 $
| > $Author: ips_terabyte $
+--------------------------------------------------------------------------
|
|   > COMMUNITY BLOG SETUP INSTALLATION MODULES
|   > Script written by Matt Mecham
|   > Community Blog version by Remco Wilting
|   > Date started: 23rd April 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

// Number of Blogs updated per run
// Lower this amount if you get timeout errors during the blog upgrade and rebuild processes
define( 'UPDATE_PER_RUN'  , 50 );

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	* fetchs output
	* 
	* @access	public
	* @return	string
	*/
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();

		//--------------------------------
		// What are we doing?
		//--------------------------------
		switch( $this->request['workact'] )
		{
			case 'rebuildblogs':
				$this->rebuild_blogs();
			break;
			case 'blogsettings':
				$this->upgrade_blogsettings();
			break;
			default:
				$this->upgrade_blogsettings();
			break;
		}
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/*-------------------------------------------------------------------------*/
	// Rebuild Blogs
	/*-------------------------------------------------------------------------*/

	public function rebuild_blogs()
	{
		$this->DB->loadCacheFile( ROOT_PATH . 'sources/sql/'.strtolower($this->settings['sql_driver']).'_blog_queries.php', 'sql_blog_queries' );

		$start = $this->request['st'] ? intval($this->request['st']) : 0;
		$cnt = 0;
		$end = 0;

		$this->DB->build( array( 
								'select' =>	'blog_id',
								'from'	 =>	'blog_blogs',
								'where'	 =>	"blog_type = 'local'",
								'order'	 =>	"blog_id",
								'limit'  =>	array($start, UPDATE_PER_RUN)
						)	);
		$qid = $this->DB->execute();
		while ( $row = $this->DB->fetch( $qid ) )
		{
			$this->rebuildBlog( $row['blog_id'] );
			$cnt++;
		}

		//--------------------------------
		// Next page...
		//--------------------------------
		if ( $cnt < UPDATE_PER_RUN )
		{
			 unset( $this->request['workact'] );
			 unset( $this->request['st'] );
			 $this->_output = "$cnt Blogs rebuild....";
		}
		else
		{
			$st = $cnt + $start;
			$this->_output = "$cnt Blogs rebuild....";
			$this->request['workact'] = 'rebuildblog';
			$this->request['st'] = $st;
		}

	}

	/*-------------------------------------------------------------------------*/
	// Blog Settings
	/*-------------------------------------------------------------------------*/

	public function upgrade_blogsettings()
	{
		$start = $this->request['st'] ? intval($this->request['st']) : 0;
		$cnt = 0;
		$end = 0;
		
		$this->DB->build( array(
			 						'select'	=>	'blog_id, blog_settings',
									'from'	=>	'blog_blogs',
									'where'	=>	"blog_type = 'local'",
									'order'	=>	"blog_id",
									'limit'	=>	array($start, UPDATE_PER_RUN)
						)	);
		$qid = $this->DB->execute();
		while ( $row = $this->DB->fetch( $qid ) )
		{
			$settings = unserialize( $row['blog_settings'] );
			if ( is_array( $settings['categories'] ) && count( $settings['categories'] ) > 0 )
			{
				foreach( $settings['categories'] as $id => $category )
				{
					$this->DB->insert( 'blog_categories', array(
																'blog_id'		=> $row['blog_id'],
																'category_name'	=> $category,
																'category_type'	=> 'public'
																)
									);
					$cat_id = $this->DB->getInsertId();
					$this->DB->update( 'blog_entries', array('category_id' => $cat_id ), "blog_id = {$row['blog_id']} and entry_category = '{$category}'" );
				}
			}
			$cnt++;
		}

		//--------------------------------
		// Next page...
		//--------------------------------
		if ( $cnt < UPDATE_PER_RUN )
		{
			 $this->request['workact'] = 'rebuildblogs';
			 unset( $this->request['st'] );
			 $this->_output = "$cnt Blogs updated....";
		}
		else
		{
			$st = $cnt + $start;
			$this->_output = "$cnt Blogs updated....";
			$this->request['workact'] = 'blogsettings';
			$this->request['st'] = $st;
		}

	}

	/*-------------------------------------------------------------------------*/
	// Rebuild Blog Helper functions
	/*-------------------------------------------------------------------------*/

	public function rebuildBlog( $blog_id="" )
	{
		$this->rebuild_bloglevel( $blog_id );
	}

	function rebuild_bloglevel( $blog_id="", $level=0, $excludelevels=array() )
	{
		// Private level
		$levelrow = $this->DB->buildAndFetch( array( 'select' => 'blog_id', 'from' => 'blog_lastinfo', 'where' => "blog_id = {$blog_id}" ) );

		$exclude_cats = array();
		
		if( count($excludelevels) )
		{
			$this->DB->build( array( 
										'select' => 'category_id',
										'from' => 'blog_categories',
										'where' => "blog_id = {$blog_id} AND category_type IN('".implode("','", $excludelevels)."')" 
							)	);
			$this->DB->execute();
			while ( $row = $this->DB->fetch() )
			{
				$exclude_cats[] = $row['category_id'];
			}
		}
		$extra = "";
		if ( count( $exclude_cats ) > 0 )
		{
			$extra = " AND category_id NOT IN(".implode( ',', $exclude_cats ).")";
		}

		$this->DB->buildFromCache('blog_rebuild_getcounts', array ( 'blog_id' => $blog_id, 'extra' => $extra ), 'sql_blog_queries' );
		$this->DB->execute();
		$update = $this->DB->fetch();

		$this->DB->build( array( 'select'	=> "COUNT(*) as comment_count",
							          'from'	=> array('blog_entries' => 'e'),
						              'add_join'=> array( 0 => array( 'from'   => array( 'blog_comments' => 'c' ),
													                  'where'  => "e.entry_id=c.entry_id",
													                  'type'   => 'left'
														)			),
            						  'where'	=> "e.blog_id = {$blog_id} AND e.entry_status='published' AND c.comment_queued=0".$extra,
						     )		);
		$this->DB->execute();
		$row = $this->DB->fetch();
		$update['blog_num_comments'] = $row['comment_count'];

		$this->DB->build( array ( 'select' => 'entry_id, entry_date, entry_name',
									   'from'	=> 'blog_entries',
									   'where'	=> "blog_id={$blog_id} AND entry_status='published'".$extra,
									   'order'	=> 'entry_date DESC',
									   'limit'	=> array(0,1)
							 )       );
		$this->DB->execute();
		$row = $this->DB->fetch();
		$update['blog_last_entry'] = $row['entry_id'];
		$update['blog_last_date'] = $row['entry_date'];
		$update['blog_last_entryname'] = $row['entry_name'];
		$update['blog_last_update'] = $row['entry_date'];
		
		/**
		 * @link	http://community.invisionpower.com/tracker/issue-36543-error-in-120-upgrade-script/
		 */
		$memberIdField = $this->DB->checkForField( 'member_id', 'members' ) ? 'member_id' : 'id';
		
		$this->DB->build( array( 'select'	=> "e.entry_id, e.entry_name",
							          'from'	=> array('blog_entries' => 'e'),
						              'add_join'=> array( 0 => array( 'select' => 'c.comment_id, c.comment_date, c.member_id',
						              								  'from'   => array( 'blog_comments' => 'c' ),
													                  'where'  => "e.entry_id=c.entry_id",
													                  'type'   => 'inner'
																	),
						              					  1 => array( 'select' => 'CASE WHEN c.member_id>0 THEN m.members_display_name ELSE c.member_name END as member_name',
						              								  'from'   => array( 'members' => 'm' ),
													                  'where'  => "c.member_id=m." . $memberIdField,
													                  'type'   => 'left'
														)			),
            						  'where'	=> "e.blog_id = {$blog_id} AND e.entry_status='published' AND c.comment_queued=0".$extra,
            						  'order'	=> 'c.comment_id DESC',
            						  'limit'	=> array(0,1)
						     )		);
		$this->DB->execute();
		$row = $this->DB->fetch();
		$update['blog_last_comment']			= $row['comment_id'];
		$update['blog_last_comment_date']		= $row['comment_date'];
		$update['blog_last_comment_entry']		= $row['entry_id'];
		$update['blog_last_comment_entryname']	= $row['entry_name'];
		$update['blog_last_comment_name']		= $row['member_name'];
		$update['blog_last_comment_mid']		= $row['member_id'];
		$update['blog_last_update'] = ($row['comment_date'] > $update['blog_last_update'] ? $row['comment_date'] : $update['blog_last_update']);

		if ( $levelrow['blog_id'] )
		{
			$this->DB->update("blog_lastinfo", $update, "blog_id={$blog_id} and level='{$level}'");
		}
		else
		{
			$update['blog_id'] = $blog_id;
			$update['level'] = $level;
			$this->DB->insert("blog_lastinfo", $update);
		}
	}

}