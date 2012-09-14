<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Formats forum search results
 * Last Updated: $Date: 2012-05-25 13:17:47 -0400 (Fri, 25 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10798 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format_members extends search_format
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_profile' ), 'members' );
	}
	
	/**
	 * Parse search results
	 *
	 * @param	array 	$r				Search result
	 * @return	array 	$html			Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		if ( IPSSearchRegistry::get('members.searchInKey') == 'comments' )
		{
			$this->registry->getClass('output')->addJSModule( 'status', 0 );
		}
		
		return parent::parseAndFetchHtmlBlocks( $rows );
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @param	array   $search_row		Array of data
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 */
	public function formatContent( $data )
	{
		$data['misc']	= unserialize( $data['misc'] );		
		$template		= IPSSearchRegistry::get('members.searchInKey') == 'comments' ? 'memberCommentsSearchResult' : 'memberSearchResult';
		
		if ( $data['status_last_ids'] )
		{
			foreach ( unserialize( $data['status_last_ids'] ) as $_data )
			{
				$data['replies'][ $_data['reply_id'] ] = array_merge( $_data, IPSMember::buildDisplayData( $_data['reply_member_id'] ) );
			}
		}
		
		return array( ipsRegistry::getClass('output')->getTemplate('search')->$template( $data, IPSSearchRegistry::get('opt.searchType') == 'titles' ? true : false ), 0 );
	}

	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @return array
	 */
	public function processResults( $ids )
	{
		if ( IPSSearchRegistry::get('members.searchInKey') == 'comments' )
		{
			return $this->processResultsComments( $ids );
		}
		else
		{
			$rows = array();
			
			foreach( $ids as $i => $d )
			{
				$rows[ $i ] = $this->genericizeResults( $d );
			}
			
			return $rows;
		}
	}
	
		/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @param	array 	$ids			Ids
	 * @return array
	 */
	public function processResultsComments( $ids )
	{
		/* INIT */
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchType') == 'content' ? false : true;
		$members			= array();
		$results			= array();
		$statusIds			= array();
		$replyIds			= IPSSearchRegistry::get('_internal.replyIds');
		$replyData			= IPSSearchRegistry::get('_internal.replyData');
		$replies			= array();
		$sortKey			= '';

		/* Got some? */
		if ( count( $ids ) )
		{
			/* Set vars */
			IPSSearch::$ask = 'status_date';
			IPSSearch::$aso = strtolower( $sort_order );
			IPSSearch::$ast = 'numerical';
			
			/* Get the status updates */
			$this->DB->build( array('select'   => "s.*",
									'from'	   => array( 'member_status_updates' => 's' ),
		 							'where'	   => 's.status_id IN( ' . implode( ',', $ids ) . ')',
									'add_join' => array_merge( array( array( 'select'	=> 'm.member_id as owner_id, m.members_display_name as owner_display_name, m.members_seo_name as owner_seo_name',
																			 'from'		=> array( 'members' => 'm' ),
															 				 'where'	=> 'm.member_id=s.status_member_id',
															 				 'type'		=> 'left' ),
																	  array( 'select'	=> 'mem.member_id as author_id, mem.members_display_name as author_display_name, mem.members_seo_name as author_seo_name',
																			 'from'		=> array( 'members' => 'mem' ),
															 				 'where'	=> 'mem.member_id=s.status_author_id',
															 				 'type'		=> 'left' ) ) ) ) );

			/* Grab data */
			$this->DB->execute();
			
			/* Grab the results */
			while( $row = $this->DB->fetch() )
			{
				$statusIds[ $row['status_id'] ] = $row;
			}
		
			/* Sort */
			if ( count( $statusIds ) )
			{
				usort( $statusIds, array("IPSSearch", "usort") );
		
				foreach( $statusIds as $id => $row )
				{
					/* Do we have any reply data? */
					if ( ! empty( $replyData[ $id ] ) )
					{
						$row = array_merge( $row, $replyData[ $id ] );
					}
					
					/* Get author data? */
					if ( ! empty( $row['status_author_id'] ) )
					{
						$members[ $row['status_author_id'] ] = $row['status_author_id'];
					}
				
					if ( ! empty( $row['status_member_id'] ) )
					{
						$members[ $row['status_member_id'] ] = $row['status_member_id'];
					}
					
					if ( ! empty( $row['reply_member_id'] ) )
					{
						$members[ $row['reply_member_id'] ] = $row['reply_member_id'];
					}
					
					$results[ $row['status_id'] ] = $row;
				}
			}
			
			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $results as $id => $r )
				{
					$_status_member = IPSMember::buildDisplayData( $mems[ $r['status_member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
					$_status_author = IPSMember::buildDisplayData( $mems[ $r['status_author_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
					
					$results[ $id ]['status_member'] = $_status_member;
					$results[ $id ]['status_author'] = $_status_author;
					
					if ( ! empty( $r['reply_member_id'] ) )
					{
						$results[ $id ]['reply_author'] = IPSMember::buildDisplayData( $mems[ $r['reply_member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
					}
				}
			}
		}
				
		return $results;
	}
	
	/**
	 * Reassigns fields in a generic way for results output
	 *
	 * @param  array  $r
	 * @return array
	 */
	public function genericizeResults( $r )
	{
		if ( IPSSearchRegistry::get('members.searchInKey') == 'comments' )
		{
			$r['app']				= 'members';
			$r['content']			= $r['comment_content'];
			$r['content_title']		= $r['member_display_name_owner'];
			$r['updated']			= $r['comment_date'];
			$r['type_2']			= 'comment_id';
			$r['type_id_2']			= $r['comment_id'];
			$r['misc']				= serialize( array(
														'members_display_name' => $r['members_display_name'],
														'pp_thumb_photo'	   => $r['pp_thumb_photo'],
														'pp_thumb_width'	   => $r['pp_thumb_width'],
														'pp_thumb_height'	   => $r['pp_thumb_height']
												)		);
		}
		else
		{
			$r['app']				= 'members';
			$r['content']			= $r['signature'] . ' ' . $r['pp_about_me'];
			$r['content_title']		= $r['members_display_name'];
			$r['updated']			= time();
			$r['type_2']			= 'profile_view';
			$r['type_id_2']			= $r['member_id'];
			$r['misc']				= serialize( array( 
														'pp_thumb_photo'	=> $r['pp_thumb_photo'],
														'pp_thumb_width'	=> $r['pp_thumb_width'],
														'pp_thumb_height'	=> $r['pp_thumb_height']
												)		);
		}

		return $r;
	}

}