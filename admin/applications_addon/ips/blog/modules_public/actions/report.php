<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog index listing
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_actions_report extends ipsCommand
{
	/**
	* Stored temporary output
	*
	* @access	protected
	* @var 		string 				Page output
	*/
	protected $output				= "";

	/**
	* Stored temporary page title
	*
	* @access	protected
	* @var 		string 				Page title
	*/
	protected $page_title			= "";

	/**
	* Blog id
	*
	* @access	protected
	* @var 		integer
	*/
	protected $blog_id				= 0;
	
	/**
	* Blog data
	*
	* @access	protected
	* @var 		array
	*/
	protected $blog					= array();
	
	/**
	* Entry data
	*
	* @access	protected
	* @var 		array
	*/
	protected $entry				= array();

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-------------------------------------------
		// Get blog
		//-------------------------------------------
		
		$this->blog    = $this->registry->getClass('blogFunctions')->getActiveBlog();
		$this->blog_id = intval( $this->blog['blog_id'] );

		if ( ! $this->request['cid'] && ! $this->blog_id )
        {
			$this->registry->output->showError( 'incorrect_use', 10644, null, null, 404 );
		}

		//-------------------------------------------
		// Set urls
		//-------------------------------------------
		
		$this->settings[ 'blog_url'] =  $this->registry->getClass('blogFunctions')->getBlogUrl( $this->blog_id );

		//-------------------------------------------
		// And then
		//-------------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'report':
				$this->_reportForm();
			break;
			
			case 'sendreport':
				$this->_sendReport();
			break;
			
			case 'submitham':
				$this->_sendHam();
			break;

			case 'submitspam':
				$this->_sendSpam();
			break;
			
			default:
				$this->registry->output->showError( 'incorrect_use', 10645 );
			break;
		}

		$this->registry->output->setTitle( $this->page_title );
		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('blogFunctions')->sendBlogOutput( $this->blog );
	}

	/**
	* Send spam to Akismet
	*
	* @access	protected
	* @return	@e void
	*/	
    protected function _sendSpam()
	{
		if( !$this->request['cid'] )
		{
			$this->registry->output->showError( 'spam_no_cid', 10646, null, null, 404 );
		}
		
		if( !$this->settings['blog_akismet_key'] )
		{
			$this->registry->output->showError( 'spam_no_akismet', 10647 );
		}
		
		/* Load AKI */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/lib/akismet.class.php', 'Akismet', 'blog' );
		$akismet = new $classToLoad( $this->settings['board_url'], $this->settings['blog_akismet_key'] );
		
		if ( ! $akismet->isKeyValid() )
		{
			$this->registry->output->showError( 'spam_wrongkey_akismet', 10647.1 );
		}
		
		$cid = intval( $this->request['cid'] );
		
		if( $this->request['trackback'] == 1 )
		{
			$tb = $this->DB->buildAndFetch( array(  'select'	=> 'tb.*',
													'from'		=> array( 'blog_trackback' => 'tb' ),
													'where'		=> 'tb.trackback_id=' . $cid,
													'add_join'	=> array( array( 'select' => 'e.blog_id, e.entry_name_seo',
																				 'from'   => array( 'blog_entries' => 'e' ),
																				 'where'  => 'e.entry_id=tb.entry_id',
																				 'type'   => 'left' ) )
											)		);

			if ( empty($tb['trackback_id']) )
			{
				$this->registry->output->showError( 'spam_no_tb', 10648, null, null, 404 );
			}
			
			/* Setup data */
			$akismet->setCommentType( 'trackback' );
			$akismet->setCommentAuthor( '' );
			$akismet->setCommentAuthorEmail( '' );
			$akismet->setCommentContent( $tb['trackback_excerpt'] );
			$akismet->setPermalink( $this->registry->output->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$tb['blog_id']}&amp;showentry={$tb['entry_id']}" ) );
			$akismet->setUserIP( $tb['ip_address'] );
			
			try
			{
				$akismet->submitSpam();
			}
			catch( Exception $e ) {}
			
			$this->registry->output->redirectScreen( $this->lang->words['spam_reported'], $this->settings['base_url'] . "app=blog&amp;module=display&amp;section=blog&amp;blogid={$tb['blog_id']}&amp;showentry={$tb['entry_id']}&amp;st={$this->request['st']}", $tb['entry_name_seo'], 'showentry' );
		}
		else
		{
			$thecomment = $this->DB->buildAndFetch( array(
													'select'	=> 'c.*',
													'from'		=> array( 'blog_comments' => 'c' ),
													'where'		=> 'c.comment_id=' . $cid,
													'add_join'	=> array( array( 'select' => 'e.blog_id, e.entry_name_seo',
																				 'from'   => array( 'blog_entries' => 'e' ),
																				 'where'  => 'e.entry_id=c.entry_id',
																				 'type'   => 'left' ),
																		  array( 'select' => 'm.email',
																				 'from'   => array( 'members' => 'm' ),
																				 'where'  => 'm.member_id=c.member_id',
																				 'type'   => 'left' ) )
											)		);

			if ( empty($thecomment['comment_id']) )
			{
				$this->registry->output->showError( 'spam_no_comment', 10649, null, null, 404 );
			}
			
			/* Setup data */
			$akismet->setCommentType( 'comment' );
			$akismet->setCommentAuthor( $thecomment['member_name'] );
			$akismet->setCommentAuthorEmail( $thecomment['email'] );
			$akismet->setCommentContent( $thecomment['comment_text'] );
			$akismet->setPermalink( $this->registry->output->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$tb['blog_id']}&amp;showentry={$tb['entry_id']}" ) );
			$akismet->setUserIP( $thecomment['ip_address'] );
			
			try
			{
				$akismet->submitSpam();
			}
			catch( Exception $e ) {}
			
			$this->registry->output->redirectScreen( $this->lang->words['spam_reported'], $this->settings['base_url'] . "app=blog&amp;module=display&amp;section=blog&amp;blogid={$thecomment['blog_id']}&amp;showentry={$thecomment['entry_id']}&amp;st={$this->request['st']}#comment{$thecomment['comment_id']}", $tb['entry_name_seo'], 'showentry' );
		}
	}
	
	/**
	* Send "ham" to Akismet
	*
	* @access	protected
	* @return	@e void
	*/	
    protected function _sendHam()
	{
		if( !$this->request['cid'] )
		{
			$this->registry->output->showError( 'spam_no_cid', 10650, null, null, 404 );
		}
		
		if( !$this->settings['blog_akismet_key'] )
		{
			$this->registry->output->showError( 'spam_no_akismet', 10651 );
		}
		
		/* Load AKI */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/lib/akismet.class.php', 'Akismet', 'blog' );
		$akismet = new $classToLoad( $this->settings['board_url'], $this->settings['blog_akismet_key'] );
		
		if ( ! $akismet->isKeyValid() )
		{
			$this->registry->output->showError( 'spam_wrongkey_akismet', 10651.1 );
		}
		
		$cid = intval( $this->request['cid'] );
		
		if( $this->request['trackback'] == 1 )
		{
			$tb = $this->DB->buildAndFetch( array(  'select'	=> 'tb.*',
													'from'		=> array( 'blog_trackback' => 'tb' ),
													'where'		=> 'tb.trackback_id=' . $cid,
													'add_join'	=> array( array( 'select' => 'e.blog_id, e.entry_name_seo',
																				 'from'   => array( 'blog_entries' => 'e' ),
																				 'where'  => 'e.entry_id=tb.entry_id',
																				 'type'   => 'left' ) )
											)		);
			
			if ( empty($tb['trackback_id']) )
			{
				$this->registry->output->showError( 'spam_no_tb', 10652, null, null, 404 );
			}
			
			/* Setup data */
			$akismet->setCommentType( 'trackback' );
			$akismet->setCommentAuthor( '' );
			$akismet->setCommentAuthorEmail( '' );
			$akismet->setCommentContent( $tb['trackback_excerpt'] );
			$akismet->setPermalink( $this->registry->output->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$tb['blog_id']}&amp;showentry={$tb['entry_id']}" ) );
			$akismet->setUserIP( $tb['ip_address'] );
			
			try
			{
				$akismet->submitHam();
			}
			catch( Exception $e ) {}
			
			$this->registry->output->redirectScreen( $this->lang->words['ham_reported'], $this->settings['base_url'] . "app=blog&amp;module=display&amp;section=blog&amp;blogid={$tb['blog_id']}&amp;showentry={$tb['entry_id']}", $tb['entry_name_seo'], 'showentry' );	
		}
		else
		{
			$thecomment = $this->DB->buildAndFetch( array(
													'select'	=> 'c.*',
													'from'		=> array( 'blog_comments' => 'c' ),
													'where'		=> 'c.comment_id=' . $cid,
													'add_join'	=> array( array( 'select' => 'e.blog_id, e.entry_name_seo',
																				 'from'   => array( 'blog_entries' => 'e' ),
																				 'where'  => 'e.entry_id=c.entry_id',
																				 'type'   => 'left' ),
																		  array( 'select' => 'm.email',
																				 'from'   => array( 'members' => 'm' ),
																				 'where'  => 'm.member_id=c.member_id',
																				 'type'   => 'left' ) )
											)		);

			if ( empty($thecomment['comment_id']) )
			{
				$this->registry->output->showError( 'spam_no_comment', 10653, null, null, 404 );
			}
			
			/* Setup data */
			$akismet->setCommentType( 'comment' );
			$akismet->setCommentAuthor( $thecomment['member_name'] );
			$akismet->setCommentAuthorEmail( $thecomment['email'] );
			$akismet->setCommentContent( $thecomment['comment_text'] );
			$akismet->setPermalink( $this->registry->output->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$tb['blog_id']}&amp;showentry={$tb['entry_id']}" ) );
			$akismet->setUserIP( $thecomment['ip_address'] );
			
			try
			{
				$akismet->submitHam();
			}
			catch( Exception $e ) {}
			
			$this->registry->output->redirectScreen( $this->lang->words['ham_reported'], $this->settings['base_url'] . "app=blog&amp;module=display&amp;section=blog&amp;blogid={$thecomment['blog_id']}&amp;showentry={$thecomment['entry_id']}&amp;st={$this->request['st']}#comment{$thecomment['comment_id']}", $tb['entry_name_seo'], 'showentry' );
		}
	}
	
	/**
	* Show the report form
	*
	* @access	protected
	* @return	@e void
	* @deprecated	Just redirects to report center now
	*/	
    protected function _reportForm()
	{
		if ( !$this->request['cid'] && !$this->request['eid'] )
		{
			$this->registry->output->showError( 'missing_files', 10654, null, null, 404 );
		}
		
		if( $this->request['cid'] )
		{
			$comment = $this->DB->buildAndFetch( array( 'select' => '*',
														'from'   => 'blog_comments',
			 											'where'  => 'comment_id=' . intval( $this->request['cid'] ) ) );


			$entry  = $this->DB->buildAndFetch( array( 'select' => '*',
													   'from'   => 'blog_entries',
													   'where'  => 'entry_id=' . intval( $comment['entry_id'] ) ) );
		}
		elseif($this->request['eid'])
		{
			$entry  = $this->DB->buildAndFetch( array( 'select' => '*',
													   'from'   => 'blog_entries',
													   'where'  => 'entry_id=' . intval( $this->request['eid'] ) ) );
		}
		
		
		$url = $this->settings['base_url'] . "app=core&module=reports&rcom=blog&blog_id={$entry['blog_id']}&entry_id={$entry['entry_id']}";
		
		if($this->request['cid'])
		{
			$url .= "&comment_id={$this->request['cid']}";
		}
		
		if($this->request['st'])
		{
			$url .= "&st={$this->request['st']}";
		}
		
		$this->registry->output->silentRedirect($url);
	}

	/**
	* Actually send the report
	*
	* @access	protected
	* @return	@e void
	* @deprecated	Just redirects to report center now
	*/	
    protected function _sendReport()
	{
		$eid = intval($this->request['eid']);
		$cid = intval($this->request['cid']);
		$st  = intval($this->request['st']);

		if ( (!$eid) and (!$cid) )
		{
			$this->registry->output->showError( 'missing_files', 10655, null, null, 404 );
		}
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=core&module=reports&rcom=blog&blog_id={$this->request['blogid']}&entry_id={$this->request['eid']}&comment_id={$this->request['cid']}&st={$this->request['st']}" );
	}

	/**
	* Check access to an entry
	*
	* @access	protected
	* @param	integer		Entry id
	* @return	@e void		[Outputs error if no access]
	*/	
    protected function _checkAccess($eid)
    {
		$this->entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id=" . $eid ) );
		
		if( !$this->registry->getClass('blogFunctions')->checkAccess( $this->entry, $this->blog ) )
		{
			$this->registry->output->showError( 'no_permission', 10660, null, null, 403 );
		}
	}
}