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

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_portal_ajax_view extends ipsAjaxCommand 
{
	public function doExecute( ipsRegistry $registry )
	{		
		$this->lang	=  $registry->getClass('class_localization');
		$this->lang->loadLanguageFile( array( 'admin_portal' ) );	
		
    	switch( $this->request['do'] )
    	{
 			case 'view_quicktags':
				$this->view_quicktags();
			break;   		
    		
			default:
			case 'show_block':
				$this->show_block();
			break;
    	}
	}
	
	/*-------------------------------------------------------------------------*/
	// View Quick Tags
	/*-------------------------------------------------------------------------*/
	protected function view_quicktags()
	{
		$html = $this->registry->output->loadTemplate('cp_skin_gms');	
		
		IPSText::getTextClass('bbcode')->parse_html    = 1;
        IPSText::getTextClass('bbcode')->parse_nl2br   = 1;
        IPSText::getTextClass('bbcode')->parse_smilies = 1;
        IPSText::getTextClass('bbcode')->parse_bbcode  = 1;
        IPSText::getTextClass( 'bbcode' )->parsing_section	= 'gms';		
		$this->lang->words['gms_form_quick_tags'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $this->lang->words['gms_form_quick_tags'] );
				
		$this->returnHTML( $html->quicktags() );		
	}	
	
	/*-------------------------------------------------------------------------*/
	// View Block
	/*-------------------------------------------------------------------------*/
	protected function show_block()
	{
		$html = $this->registry->output->loadTemplate('cp_skin_blocks');
		
		$id = intval( $this->request['id'] );
		$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'portal_blocks', 'where' => 'block_id='.$id ) );														
	
		if ( !$data['block_id'] )
		{
			$this->returnJsonError( "No matches for id provided." );
			exit();
		}
        
        ob_start();
        eval("?>".$data['block_code']."<?php\n");
        $data['block_code'] = ob_get_contents();
        ob_end_clean();		
			
		$this->returnHTML( $html->viewBlock( $data ) );		
	}	
}