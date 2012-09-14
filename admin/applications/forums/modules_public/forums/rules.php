<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Show forum rules
 * Last Updated: $Date: 2012-05-22 10:39:38 -0400 (Tue, 22 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Forums 
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10779 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_rules extends ipsCommand
{
	/**
	 * Array of form data
	 *
	 * @var		array
	 */
	protected $forum	= array();

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'public_forums', 'public_boards' ) );

		//-----------------------------------------
		// Get the forum info based on the forum ID,
		// and get the category name, ID, etc.
		//-----------------------------------------

		$this->forum = $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ]; 

		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------

		if( ! $this->forum['id'] )
		{
			$this->registry->getClass('output')->showError( 'forums_no_id', 10333, null, null, 404 );
		}

		//-----------------------------------------
		// Is it a redirect forum?
		//-----------------------------------------

		if( !empty( $this->forum['redirect_on'] ) )
		{
			$redirect = $this->DB->buildAndFetch( array( 'select' => 'redirect_url', 'from' => 'forums', 'where' => "id=" . $this->forum['id']) );

			if( $redirect['redirect_url'] )
			{
				//-----------------------------------------
				// Update hits:
				//-----------------------------------------
				
				$this->DB->buildAndFetch( array( 'update' => 'forums', 'set' => 'redirect_hits=redirect_hits+1', 'where' => "id=" . $this->forum['id']) );
				
				//-----------------------------------------
				// Boink!
				//-----------------------------------------
				
				$this->registry->getClass('output')->silentRedirect( $redirect['redirect_url'] );
			}
		}

		//-----------------------------------------
		// Check forum access perms
		//-----------------------------------------
		
		if( !$this->request['L'] )
		{
			$this->registry->getClass('class_forums')->forumsCheckAccess( $this->forum['id'], 1 );
		}

		//-----------------------------------------
		// Do we have permission to view these rules?
		//-----------------------------------------
		
		$allow_access = $this->registry->getClass('class_forums')->forumsCheckAccess( $this->forum['id'], 1 );

		if( $allow_access === FALSE )
		{
			$this->registry->getClass('output')->showError( 'forums_no_access', 10334, null, null, 403 );
		}

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'rules_title, rules_text', 'from' => 'forums', 'where' => "id=" . $this->forum['id']) );

        if( $tmp['rules_title'] )
		{
			$rules['title']	= $tmp['rules_title'];
			$rules['body']	= $tmp['rules_text'];
			$rules['fid']	= $this->forum['id'];
			$rules['fseo']	= $this->forum['name_seo'];

			IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section	= 'rules';
			$rules['body']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $rules['body'] );
			
			/* @link http://community.invisionpower.com/tracker/issue-37441-forum-rules/ */
			$rules['body'] = str_replace( "<span rel='lightbox'>", "<span>", $rules['body'] );
			$rules['body'] = str_replace( "class='bbc_img'", "", $rules['body'] );

			$this->output .= $this->registry->getClass('output')->getTemplate('forum')->show_rules($rules);

			$this->registry->output->setTitle( $this->forum['name'] . ' - ' . ipsRegistry::$settings['board_name'] );
			$this->registry->output->addNavigation( $this->forum['name'], "showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
			$this->registry->output->addContent( $this->output );
			$this->registry->output->sendOutput();
		}
		else
		{
			$this->registry->getClass('output')->showError( 'forums_no_rules', 10335, null, null, 404 );
		}
	}
}