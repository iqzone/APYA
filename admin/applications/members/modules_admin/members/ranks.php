<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Member management
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_members_members_ranks extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
	/**
	 * Shortcut for url
	 *
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate('cp_skin_ranks');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=members&amp;section=ranks';
		$this->form_code_js	= $this->html->form_code_js	= 'module=members&section=ranks';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member' ) );

		///-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'title':
				$this->_titlesStart();
			break;

			case 'rank_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_edit' );
				$this->_titlesForm( 'edit' );
			break;

			case 'rank_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_add' );
				$this->_titlesForm( 'add' );
			break;

			case 'do_add_rank':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_add' );
				$this->_titlesSave( 'add' );
			break;

			case 'do_rank_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_edit' );
				$this->_titlesSave( 'edit' );
			break;

			case 'rank_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_delete' );
				$this->_titlesDelete();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
		
	}
	
	/**
	 * Recache ranks
	 *
	 * @return	@e void
	 */
	public function titlesRecache()
	{
		$ranks = array();
        	
		$this->DB->build( array( 'select'	=> 'id, title, pips, posts',
										'from'	=> 'titles',
										'order'	=> 'posts DESC',
							)      );
		$this->DB->execute();
					
		while ( $i = $this->DB->fetch() )
		{
			$ranks[ $i['id'] ] = array(
										'TITLE'	=> $i['title'],
										'PIPS'	=> $i['pips'],
										'POSTS'	=> $i['posts'],
									);
		}

		$this->cache->setCache( 'ranks', $ranks, array( 'array' => 1 ) );
	}
	
	
	/**
	 * Overview page
	 *
	 * @return	@e void			[Outputs to screen]
	 */
	protected function _titlesStart()
	{
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['member_rank_nav'] );
		
		$titles		= array();

		//-----------------------------------------
		// Parse macro
		//-----------------------------------------

		$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'skin_replacements', 'where' => "replacement_set_id=0 AND replacement_key='pip_pip'" ) );

    	$row['A_STAR'] = str_replace( "{style_image_url}", $this->settings['img_url'], $row['replacement_content'] );

		//-----------------------------------------
		// Lets get on with it...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'titles', 'order' => "posts" ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$r['A_STAR']	= $row['A_STAR'];
			$titles[]		= $r;
		}
										 
		$this->registry->output->html .= $this->html->titlesOverview( $titles );
	}
	
	/**
	 * Save rank [add/edit]
	 *
	 * @param	string			'add' or 'edit'
	 * @return	@e void			[Outputs to screen]
	 */
	protected function _titlesSave( $type = 'add' )
	{
		//-----------------------------------------
		// check for input
		//-----------------------------------------
		
		foreach( array( 'title', 'pips' ) as $field )
		{
			if ( ! isset( $this->request[ $field ] ) )
			{
				$this->registry->output->showError( $this->lang->words['rnk_completeform'], 11239 );
			}
		}
		
		if ( $this->request['pips'] > 100 )
		{
			$this->registry->output->showError( $this->lang->words['rnk_max100'], 11240 );
		}
		
		if( $type == 'add' )
		{
			$this->DB->insert( 'titles', array(
											 'posts'  => intval( trim( $this->request['posts'] ) ),
											 'title'  => trim($this->request['title']),
											 'pips'   => trim($this->request['pips']),
								  )       );

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['rnk_added'] );
		}
		else
		{
			if ( !$this->request['id'] )
			{
				$this->registry->output->showError( $this->lang->words['rnk_notfound'], 11241 );
			}

			$this->DB->update( 'titles', array ( 'posts'  => trim($this->request['posts']),
															  'title'  => trim($this->request['title']),
															  'pips'   => trim($this->request['pips']),
													        ) , "id=" . intval( $this->request['id'] )  );

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['rnk_edit'] );
		}
		
		$this->titlesRecache();

		if( $type == 'add' )
		{
			$this->registry->output->global_message	= $this->lang->words['rnk_added2'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=title' );
		}
		else
		{
			$this->registry->output->global_message	= $this->lang->words['rnk_edited'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=title' );
		}
	}
	
	/**
	 * Delete a rank
	 *
	 * @return	@e void			[Outputs to screen]
	 */
	protected function _titlesDelete()
	{
		//-----------------------------------------
		// check for input
		//-----------------------------------------
		
		if ( !$this->request['id'] )
		{
			$this->registry->output->showError( $this->lang->words['rnk_notfounddel'], 11242 );
		}
		
		$this->DB->delete( 'titles', "id=" . intval($this->request['id']) );
		
		$this->titlesRecache();
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['rnk_removed'] );

		$this->registry->output->global_message	= $this->lang->words['rnk_removed2'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=title' );
	}
	

	/**
	 * Show the form to add/edit a rank
	 *
	 * @param	string			Type of form (add|edit)
	 * @return	@e void			[Outputs to screen]
	 */
	protected function _titlesForm( $mode='edit' )
	{
		$this->registry->output->extra_nav[]	= array( '', $this->lang->words['rnk_setup'] );
		
		if ( $mode == 'edit' )
		{
			$form_code = 'do_rank_edit';
			
			if ( !$this->request['id'] )
			{
				$this->registry->output->showError( $this->lang->words['rnk_notfound'], 11243 );
			}
			
			$rank = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'titles', 'where' => "id=" . intval($this->request['id']) ) );

			$button = $this->lang->words['rnk_editrank'];
		}
		else
		{
			$form_code = 'do_add_rank';
			
			$rank = array( 'posts' => '', 'title' => "", 'pips' => "");

			$button = $this->lang->words['rnk_addrank'];
		}
		
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->titlesForm( $rank, $form_code, $button );
	}
}