<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog poll voting
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

class public_blog_actions_dovote extends ipsCommand
{
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

		if ( !$this->blog_id )
        {
			$this->registry->output->showError( 'incorrect_use', 10626, null, null, 404 );
		}
		
		/* Security Check */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10642, null, null, 403 );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$entry_id	= intval($this->request['eid']);
		$vote_cast	= array();

		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );

		//-----------------------------------------
		// Permissions check
		//-----------------------------------------

		if ( ! intval( $this->memberData['g_vote_polls'] ) )
		{
			$this->registry->output->showError( 'no_reply_polls', 10627, null, null, 403 );
		}

		//-----------------------------------------
		// Make sure we have a valid poll id
		//-----------------------------------------

		if ( ! $entry_id )
		{
			$this->registry->output->showError( 'missing_files', 10628, null, null, 404 );
		}

   		//-----------------------------------------
   		// Load the entry and poll
   		//-----------------------------------------

   		$entry = $this->DB->buildAndFetch( array( 
													'select'	=> "p.*",
													'from'		=> array('blog_polls' => 'p'),
													'add_join'	=> array(
																			array( 
																					'select' => 'e.*',
																					'from'   => array( 'blog_entries' => 'e' ),
																					'where'  => "p.entry_id=e.entry_id",
																					'type'   => 'inner'
																				)
																		),
													'where'		=> "p.entry_id = {$entry_id}"
										)	);

   		//-----------------------------------------
   		// No entry?
   		//-----------------------------------------

   		if ( ! $entry['entry_id'] )
   		{
   			$this->registry->output->showError( 'poll_none_found', 10629, null, null, 404 );
   		}

		//-----------------------------------------
		// Locked entry?
		//-----------------------------------------

   		if ( $entry['entry_locked'] )
   		{
   			$this->registry->output->showError( 'locked_topic', 10630, null, null, 403 );
   		}

   		//-----------------------------------------
   		// Have we voted before?
		//-----------------------------------------

		$this->DB->build( array( 'select' => 'member_id', 'from' => 'blog_voters', 'where' => "entry_id={$entry['entry_id']} and member_id={$this->memberData['member_id']}" ) );
		$this->DB->execute();

		if ( $this->DB->getTotalRows() )
		{
			$this->registry->output->showError( 'poll_you_voted', 10631 );
		}

		//-----------------------------------------
		// Sort out the new array
		//-----------------------------------------

		if ( ! $this->request['nullvote'] )
		{
			//-----------------------------------------
			// First, which choices and ID did we choose?
			//-----------------------------------------

			if ( is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $question_id => $choice_id )
				{
					if ( ! $question_id or ! $choice_id )
					{
						continue;
					}

					$vote_cast[ $question_id ][] = $choice_id;
				}
			}
			
			//-----------------------------------------
			// Multi vote poll
			//-----------------------------------------
			
			foreach( $_POST as $k => $v )
			{
				if( preg_match( "#^choice_(\d+)_(\d+)$#", $k, $matches ) )
				{
					if( $v == 1 )
					{
						$vote_cast[ $matches[1] ][] = $matches[2];
					}
				}
			}

			//-----------------------------------------
			// Unparse the choices
			//-----------------------------------------

			$poll_answers = unserialize( $entry['choices'] );
        	reset($poll_answers);

			//-----------------------------------------
			// Got enough votes?
			//-----------------------------------------

			if ( count( $vote_cast ) < count( $poll_answers ) )
			{
				$this->registry->output->showError( 'no_vote', 10632 );
			}

			//-----------------------------------------
			// Add voter
			//-----------------------------------------

			$this->DB->insert( 'blog_voters', array( 
														'member_id'		=> $this->memberData['member_id'],
														'ip_address'	=> $this->member->ip_address,
														'entry_id'		=> $entry['entry_id'],
														'vote_date'		=> time(),
													)
							);

			//-----------------------------------------
			// Loop
			//-----------------------------------------

			foreach ( $vote_cast as $question_id => $choice_array )
			{
				foreach( $choice_array as $choice_id )
				{
					$poll_answers[ $question_id ]['votes'][ $choice_id ]++;
					
					if ( $poll_answers[ $question_id ]['votes'][ $choice_id ] < 1 )
					{
						$poll_answers[ $question_id ]['votes'][ $choice_id ] = 1;
					}
				}
			}

			//-----------------------------------------
			// Save...
			//-----------------------------------------

			$entry['choices'] = serialize( $poll_answers );

			$this->DB->update( 'blog_polls', "votes=votes+1,choices='" . $this->DB->addSlashes( $entry['choices'] ) . "'", "poll_id={$entry['poll_id']}", false, true );

			//-----------------------------------------
			// Go bump in the night?
			//-----------------------------------------

			$entry['entry_last_vote'] = time();
			$this->DB->update( 'blog_entries', array( 'entry_last_vote' => $entry['entry_last_vote'] ), 'entry_id=' . $entry['entry_id'] );
		}
		else
		{
			//-----------------------------------------
			// Add null vote
			//-----------------------------------------

			$this->DB->insert( 'blog_voters', array( 
													'member_id'		=> $this->memberData['member_id'],
													'ip_address'	=> $this->ip_address,
													'entry_id'		=> $entry['entry_id'],
													'vote_date'		=> time(),
													)
							);
		}

        $lang = $this->request['nullvote'] ? $this->lang->words['poll_viewing_results'] : $this->lang->words['poll_vote_added'];

		$this->registry->output->redirectScreen( $lang , $this->settings['base_url'] . "app=blog&blogid={$this->blog_id}&showentry={$entry['entry_id']}&st={$this->request['st']}" );
	}

}
