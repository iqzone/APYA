<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Registration question and answer challenges
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_tools_qanda extends ipsCommand
{
	/**
	 * HTML object
	 *
	 * @var		object
	 */
	protected $html;
	
	/**
	 * Main entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load lang and skin */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_qanda' );
				
		/* URLs */
		$this->form_code    = $this->html->form_code    = 'module=tools&amp;section=qanda';
		$this->form_code_js = $this->html->form_code_js = 'module=tools&section=qanda';
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'qa_manage' );
				$this->showForm( 'edit' );
			break;
			
			case 'new':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'qa_manage' );
				$this->showForm( 'new' );
			break;
			
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'qa_manage' );
				$this->saveForm( 'edit' );
			break;

			case 'donew':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'qa_manage' );
				$this->saveForm( 'new' );
			break;
				
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'qa_manage' );
				$this->remove();
			break;

			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'qanda_view' );
				$this->overview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * Removes a question
	 *
	 * @return	@e void
	 * @author	Brandon
	 */
	public function remove()
	{
		/* Check ID */
		$id = intval( $this->request['id'] );

		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['qa_noid'], 111155 );
		}
		
		/* Delete the record */
		$this->DB->delete( 'question_and_answer', "qa_id={$id}" );
		
		/* Log and bounce */
		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['qa_deleted'] );
		$this->registry->output->silentRedirect( $this->settings['base_url'] . $this->form_code );		
	}	
	
	/**
	 * List current questions
	 *
	 * @return	@e void
	 */
	public function overview()
	{
		/* Query Questions */
		$this->DB->build( array( 'select' => '*', 'from' => 'question_and_answer' ) );
		$this->DB->execute();
		
		/* Do we have questions? */
		$rows = array();
		
		if( $this->DB->getTotalRows() )
		{
			while( $r = $this->DB->fetch() )
			{
				/* Add to output array */
				$rows[] = $r;
			}
		}
		
		/* Output */		
		$this->registry->output->html .= $this->html->overview( $rows );
	}	
	
	/**
	 * Form for adding/editing questions
	 *
	 * @param	string		Type [new|edit]
	 * @return	@e void
	 */
	public function showForm( $type='new' )
	{
        /* Edit Question */
		if( $type != 'new' )
		{
			/* ID */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['qa_noeditid'], 111156 );
			}
		
			/* Query the question */
			$this->DB->build( array( 'select' => '*', 'from' => 'question_and_answer', 'where' => "qa_id=" . $id ) );
			$this->DB->execute();
			
			/* Make sure we found one */	
			if( ! $r = $this->DB->fetch() )
			{
				$this->registry->output->showError( $this->lang->words['qa_404'], 111157 );
			}
		
			/* Text bits */
			$button = $this->lang->words['qa_editbutton'];
			$code   = 'doedit';
		}
		else
		{
			/* Data */
			$r  = array();
			$id = 0;
			
			/* Text Bits */
			$button = $this->lang->words['qa_addbutton'];
			$code   = 'donew';
		}
		
		/* Form Elements */
		$form = array();		
		
		$form['question']		= $this->registry->output->formInput( 'question', htmlspecialchars( $r['qa_question'], ENT_QUOTES ) );
		$form['answers']		= $this->registry->output->formTextarea( 'answers', htmlspecialchars( $r['qa_answers'], ENT_QUOTES ) );

		/* Ouput */	
		$this->registry->output->html           .= $this->html->showForm( $code, $id, $form, $button );
	}
	
	/**
	 * Save the questions from the form
	 *
	 * @param	string		Type [new|edit]
	 * @return	@e void
	 */
	public function saveForm( $type='new' )
	{
		/* Error Checking */
		if( ! $this->request['question'])
		{
			$this->registry->output->showError( $this->lang->words['qa_no_question'], 11150 );
		}
		
		//-----------------------------------------
		// We use strlen so '0' is still supported
		//-----------------------------------------
		
		if( !strlen($this->request['answers']) )
		{
			$this->registry->output->showError( $this->lang->words['qa_no_answer'], 11150 );
		}		

		/* Build DB Array */
		$db_array = array( 
							'qa_question'	=> $_POST['question'],
							'qa_answers'	=> $_POST['answers'],
						);
		
		/* Insert question */
		if( $type == 'new' )
		{
			/* Update the DB */
			$this->DB->insert( 'question_and_answer', $db_array );
			
			$id = $this->DB->getInsertId();
						
			/* Log */
			$this->registry->adminFunctions->saveAdminLog( $this->lang->words['qa_addlog'] );
		}
		/* Update question */
		else
		{
			/* ID */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['qa_noeditid'], 111158 );
			}
			
			/* Update the DB */
			$this->DB->update( 'question_and_answer', $db_array, "qa_id={$id}" );
			
			$this->registry->adminFunctions->saveAdminLog( $this->lang->words['qa_edited']);			
		}

		/* Bounce */
		$this->registry->output->silentRedirect( $this->settings['base_url'] . $this->form_code );
	}
}