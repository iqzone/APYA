<?php
/*
+--------------------------------------------------------------------------
|   Portal 1.1.0
|   =============================================
|   by Michael John
|   Copyright 2011-2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
|   Based on IP.Board Portal by Invision Power Services
|   Website - http://www.invisionpower.com/
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_portal_blocks_blocks extends ipsCommand
{
	private $html;
	private $form_code;
	private $form_code_js;	

	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blocks' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=blocks&amp;section=blocks';
		$this->form_code_js	= $this->html->form_code_js	= 'module=blocks&section=blocks';
		
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'admin_portal' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->request['do'])
		{				
			case 'block_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );			
				$this->blockForm('new');
				break;
			case 'block_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );			
				$this->blockSave('new');
				break;				
			case 'block_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );			
				$this->blockForm('edit');
				break;				
			case 'block_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );			
				$this->blockSave('edit');
				break;
			case 'block_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_delete' );
				$this->blockDelete();
				break;
			case 'block_move':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );
				$this->blockMove();
				break;                          
			
			case 'blocks':
			default:
            $this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );
				$this->blocks();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
    
	/*-------------------------------------------------------------------------*/
	// Manage Blocks
	/*-------------------------------------------------------------------------*/
	public function blocks()
	{
		$this->registry->output->extra_nav[] = array( 'do=blocks', $this->lang->words['custom_blocks'] );
	
		$this->DB->build( array( 'select' => '*', 'from' => 'portal_blocks', 'order' => 'position ASC' ) );
		$this->DB->execute();
		
		$leftBlocks  = array();	
		$mainBlocks  = array();        
		$rightBlocks = array();
        		 
		while( $r = $this->DB->fetch() )
		{		
            if( $r['align'] == '1' )
            {
                $leftBlocks[] = $r;    
            }
            else if( $r['align'] == '2' )
            {
                $mainBlocks[] = $r;    
            }
            else if( $r['align'] == '3' )
            {
                $rightBlocks[] = $r;    
            }            					 	
		}
		
		$this->registry->output->html .= $this->html->blocksOverview( $leftBlocks, $mainBlocks, $rightBlocks );
	}
	
	/*-------------------------------------------------------------------------*/
	// Block Form
	/*-------------------------------------------------------------------------*/	
	public function blockForm( $type='new' )
	{	
		$id = $this->request['id'] ? intval( $this->request['id'] ) : 0;
				
		if ( $type == 'new' )
		{
			$form['url']    = 'block_add_do';
			$form['title']  = $this->lang->words['add_block'];
			$form['button'] = $this->lang->words['add_block'];
            
            # Default Field
            $data = array( 'template' => 1 );
		}
		else
		{			
			$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'portal_blocks', 'where' => 'block_id='.$id ) );

			if ( !$data['block_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['no_blocks_match_id'];
				$this->blocks();
				return;
			}
			
			$form['url']    = 'block_edit_do';
			$form['title']  = "{$this->lang->words['edit_block']} ".$block['title'];
			$form['button'] = $this->lang->words['edit_block'];
		}

		//-----------------------------------------
		// Setup form and form html
		//-----------------------------------------
        
		# Block Position
		$alignOption[] = array( '1', $this->lang->words['left_blocks'] );
		$alignOption[] = array( '2', $this->lang->words['main_blocks'] ); 
		$alignOption[] = array( '3', $this->lang->words['right_blocks'] );       

		$formFields['title']      = $this->registry->output->formInput( 'title', $data['title'] );
        $formFields['align']      = $this->registry->output->formDropdown( 'align', $alignOption, $data['align'] );
        $formFields['template']   = $this->registry->output->formYesNo( 'template', intval( $data['template'] ) );
		$formFields['block_code'] = $this->registry->output->formTextArea( 'block_code', $data['block_code'], "60", "15" );
		
		$this->registry->output->html .= $this->html->blockForm( $form, $formFields, $data );	
	}	
	
	/*-------------------------------------------------------------------------*/
	// Save Block
	/*-------------------------------------------------------------------------*/	
	public function blockSave( $type='new' )
	{
		$id = intval( $this->request['id'] );        
        
		$save['title']      = trim( IPSText::stripslashes( IPSText::htmlspecialchars( $_POST['title'] ) ) );
        $save['name']       = IPSText::makeSeoTitle( $save['title'] );
        $save['align']      = intval( $this->request['align'] );
        $save['template']   = intval( $this->request['template'] );
		$save['block_code'] = trim( IPSText::stripslashes( $_POST['block_code'] ) );
        
 		if ( !$save['name'] OR !$save['block_code'] )
		{				
			$this->registry->output->showError( $this->lang->words['all_fields_required'] );
		}       
	 
		if ( $type == 'new' )
		{
		  	$save['position'] = 0;
			$this->DB->insert( 'portal_blocks', $save );
			$this->registry->output->global_message = $this->lang->words['block_added'];
		}
		else
		{
			$this->DB->update( 'portal_blocks', $save, 'block_id='.$id );
			$this->registry->output->global_message = $this->lang->words['block_updated'];
		}

		$this->blocks();
	}
    
	/*-------------------------------------------------------------------------*/
	// Block Move
	/*-------------------------------------------------------------------------*/
	private function blockMove()
	{
	    /* Get ajax class */
        $classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
        $ajax   = new $classToLoad();

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		$position = 1;
 		
 		if( is_array($this->request['blocks']) AND count($this->request['blocks']) )
 		{
 			foreach( $this->request['blocks'] as $this_id )
 			{
 				$this->DB->update( 'portal_blocks', array( 'position' => $position ), 'block_id=' . $this_id ); 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	}    
    
	/*-------------------------------------------------------------------------*/
	// Delete Block
	/*-------------------------------------------------------------------------*/
	protected function blockDelete()
	{
		$id = intval($this->request['id']);	
			
		if ( ! $id )
		{
			$this->registry->output->global_message = $this->lang->words['no_blocks_match_id'];
			$this->blocks();
			return;
		}
		
		$this->DB->delete( 'portal_blocks', "block_id=".$id  );
		
		$this->registry->output->global_message = $this->lang->words['block_deleted'];
		$this->blocks();
		return;
	}    
}