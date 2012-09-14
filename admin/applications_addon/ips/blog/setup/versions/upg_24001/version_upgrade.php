<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog version upgrader
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		1st Dec 2009
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		$_output
	 */
	private $_output = '';
	
	/**
	 * Fetchs output
	 * 
	 * @return	@e string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
		
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->request  =& $this->registry->fetchRequest();
		
		/* Set time out */
		@set_time_limit( 3600 );
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			case 'faves':
			default:
				$this->convertFaves();
				break;
		}
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		return empty($this->request['workact']) ? true : false;
	}
	
	/**
	 * Convert old image favorites/subscriptions
	 * 
	 * @return	@e void
	 */
	public function convertFaves()
	{
		$st			       = trim( $this->request['st'] );
		list( $id, $done ) = explode( '-', $st );
		$id				   = intval( $id );
		$cycleDone		   = 0;

		/* Got the tables? */
		if( $this->DB->checkForTable('blog_read') )
		{
			$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
											          'from'   => 'blog_read',
											          'where'  => "blog_id=0 AND member_id > 0 AND entries_read != '' AND entries_read != 'a:0:{}'"
											  )		 );
											          
			/* Init vars */
			$favesToConvert	= array();
			$blogIds        = array();
			$realBlogs      = array();
			$memberIds		= array();
			$realMembers	= array();
			
			/* Convert favorites */
			$this->DB->build( array( 'select' => 'member_id, entries_read',
									 'from'   => 'blog_read',
									 'where'  => "blog_id=0 AND member_id > {$id} AND entries_read != '' AND entries_read != 'a:0:{}'",
									 'limit'  => array( 0, 500 ),
									 'order'  => 'member_id asc'
							 )		);
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$cycleDone++;
				$lastId = $r['member_id'];
				
				/* Got any favs? [entries_read.. what a misleading name :()] */
				if( $r['entries_read'] && IPSLib::isSerialized($r['entries_read']) )
				{
					$favourites	= unserialize($r['entries_read']);
					
					/* Got them, add member id and loop blogs */
					if ( is_array($favourites) && count($favourites) )
					{
						foreach( $favourites as $blogID )
						{
							$blogID = intval($blogID);
							
							$blogIds[ $blogID ] = $blogID;
							
							$favesToConvert[ $blogID ][ $r['member_id'] ]  = array( 'like_rel_id'		=> $blogID,
																					'like_member_id'	=> $r['member_id']
																					);
						}
					}
				}
			}
			
			/* Found something? */
			if( count($favesToConvert) )
			{
				/* Check if all those blogs exist and unset blog 0 (if any) */
				$this->DB->build( array( 'select' => 'blog_id', 'from' => 'blog_blogs', 'where' => 'blog_id IN (' . implode( ',', $blogIds ) .')' ) );
				$bg = $this->DB->execute();
				
				while( $blog = $this->DB->fetch($bg) )
				{
					$blog['blog_id'] = intval($blog['blog_id']);
					
					$realBlogs[ $blog['blog_id'] ] = $blog['blog_id'];
				}
				
				unset($realBlogs[0]);
				
				/* Now let's retrieve some member IDs... */
				$_tmp = $favesToConvert;
				foreach( $_tmp as $blog => $_members )
				{
					if ( isset($realBlogs[ $blog ]) )
					{
						foreach( $_members as $mid => $member )
						{
							$memberIds[ $mid ] = $mid;
						}
					}
					else
					{
						unset($favesToConvert[ $blog ]);
					}
				}
				
				unset($_tmp);
				
				/* Create new like records */
				if( count($memberIds) )
				{
					$realMembers = IPSMember::load( $memberIds, 'core', 'id' );
					
					require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
					$_like = classes_like::bootstrap( 'blog', 'blog' );
					
					if( count($favesToConvert) )
					{
						foreach( $favesToConvert as $blog => $_members )
						{
							foreach( $_members as $member )
							{
								if( !$member['like_member_id'] OR !isset($realMembers[ $member['like_member_id'] ]) )
								{
									continue;
								}
								
								$_like->add( $member['like_rel_id'], $member['like_member_id'], array( 'like_notify_do'	=> 0, 'like_notify_freq' => 'immediate' ), false );
							}
						}
					}
					else
					{
						$this->registry->output->addMessage("No member favorite blogs found to convert....");
					}
				}
				else
				{
					$this->registry->output->addMessage("No member favorite blogs found to convert....");
				}
			}
			else
			{
				$this->registry->output->addMessage("No member favorite blogs found to convert....");
			}
			
			/* More? */
			if ( $cycleDone )
			{
				/* Reset */
				$done += $cycleDone;
				
				$this->registry->output->addMessage("Member favorite blogs converted {$done}/{$total['count']}....");
				
				$this->request['st'] 	  = $lastId . '-' . $done;
				
				/* Reset data and go again */
				$this->request['workact'] = 'faves';
				return;
			}
			else
			{
				$this->registry->output->addMessage("Member favorite blogs converted....");
				
				/* Drop table, no longer used */
				$this->DB->dropTable('blog_read');
				
				unset($this->request['workact']);
				$this->request['st'] = '';
				return;
			}
		}
		else
		{
			$this->registry->output->addMessage("Member favorite blogs conversion skipped, no old tables found....");
		}
	}
}