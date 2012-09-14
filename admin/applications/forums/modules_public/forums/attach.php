<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * View Topic Attachments
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Forums 
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_attach extends ipsCommand
{
	/**
	* Class entry point
	*
	* @param	object		Registry reference
	* @return	@e void		[Outputs to screen/redirects]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		/* INIT */
		$topic_id = intval( $this->request['tid'] );
				
		/* Make sure we have a topic */
		if ( ! $topic_id )
        {
        	$this->registry->getClass('output')->showError( 'attach_missing_tid', 10329, null, null, 404 );
        }
        
		/* Query the topic */
        $topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $topic_id ) );
        
        if ( ! $topic['topic_hasattach'] )
        {
        	$this->registry->getClass('output')->showError( 'attach_no_attachments', 10330, null, null, 404 );
        }
        
		/* Check the forum */
        if ( ! $this->registry->getClass('class_forums')->forum_by_id[ $topic['forum_id'] ] )
		{
			$this->registry->getClass('output')->showError( 'attach_no_forum_perm', 2037, true, null, 403 );
		}
		
		/* Build the attachment display */
		$this->registry->getClass('output')->addContent( $this->getAttachments( $topic ) );
		
		/* Set the title */
		$this->registry->getClass('output')->setTitle($topic['title'] .' -> ' . $this->lang->words['attach_page_title']  . ' - ' . ipsRegistry::$settings['board_name']);
		
		/* Set the navigation */
		$navigation   = $this->registry->getClass('class_forums')->forumsBreadcrumbNav( $topic['forum_id'] );
		$navigation[] = array( $topic['title'], 'showtopic=' . $topic_id, $topic['title_seo'], 'showtopic' );
		if( is_array( $navigation ) AND count( $navigation ) )
		{
			foreach( $navigation as $_id => $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}		
		
		/* Send the output */
        $this->registry->getClass('output')->sendOutput();
	}

	/**
	* Get the actual output.
	* This is abstracted so that the AJAX routine can load and execute this function
	*
	* @param	array 		Topic and attachment data
	* @return	string		HTML output
	*/
	public function getAttachments( $topic )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$attach_cache = ipsRegistry::cache()->getCache('attachtypes');
		
		//-----------------------------------------
		// Get forum skin and lang
		//-----------------------------------------
		
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'public_forums', 'public_topic' ), 'forums' );
		
		//-----------------------------------------
		// aight.....
		//-----------------------------------------
		
		$_queued	= ( ! $this->registry->getClass('class_forums')->canQueuePosts( $topic['forum_id'] ) ) ? ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), 'p.' ) : '';
		$_st		= $this->request['st'] > 0 ? intval($this->request['st']) : 0;
		$_limit		= 50;
		$_pages		= '';
		$_count		= $this->DB->buildAndFetch( array(
													'select'	=> 'COUNT(*) as attachments', 
													'from'		=> array( 'posts' => 'p' ),
													'where'		=> 'a.attach_id IS NOT NULL AND p.topic_id='.$topic['tid'] . $_queued,
													'add_join'	=> array( array(
																				'from'   => array( 'attachments' => 'a' ),
																				'where'  => "a.attach_rel_id=p.pid AND a.attach_rel_module='post'",
																				'type'   => 'left' ) 
																				)
											)		);

		if( $_count['attachments'] > $_limit )
		{
			$_pages	= $this->registry->getClass('output')->generatePagination( array( 
																					'totalItems'		=> $_count['attachments'],
																					'itemsPerPage'		=> $_limit,
																					'currentStartValue'	=> $_st,
																					'baseUrl'			=> "app=forums&amp;module=forums&amp;section=attach&amp;tid={$topic['tid']}",
																			)	);
		}

		$this->DB->build( array( 
										'select'	=> 'p.pid, p.topic_id',
										'from'		=> array( 'posts' => 'p' ),
										'where'		=> 'a.attach_id IS NOT NULL AND p.topic_id='.$topic['tid'] . $_queued,
										'order'		=> 'p.pid ASC, a.attach_id ASC',
										'limit'		=> array( $_st, $_limit ),
										'add_join'	=> array( array(
																	'select' => 'a.*',
																	'from'   => array( 'attachments' => 'a' ),
																	'where'  => "a.attach_rel_id=p.pid AND a.attach_rel_module='post'",
																	'type'   => 'left' ) 
																	)												
							)	);
										
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			if ( IPSMember::checkPermissions('read', $topic['forum_id']) != TRUE )
			{
				continue;
			}
			
			if ( ! $row['attach_id'] )
			{
				continue;
			}
			
			$row['image']		= str_replace( 'folder_mime_types', 'mime_types', $attach_cache[ $row['attach_ext'] ]['atype_img'] );
			$row['short_name']	= IPSText::truncate( $row['attach_file'], 30 );
			$row['attach_date']	= $this->registry->getClass( 'class_localization')->getDate( $row['attach_date'], 'SHORT' );
			$row['real_size']	= IPSLib::sizeFormat( $row['attach_filesize'] );
			
			$rows[]	= $row;
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('forum')->forumAttachments( $topic['title'], $rows, $_pages );
		
		return $this->output;
	}
}