<?php
/**
 * @file		templates.php 	Retrieve a template bit
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		Friday 19th May 2006 17:33
 * $LastChangedDate: 2011-05-03 04:53:04 -0400 (Tue, 03 May 2011) $
 * @version		v3.3.3
 * $Revision: 8588 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_core_ajax_templates
 * @brief		Retrieve a template bit
 *
 */
class public_core_ajax_templates extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Fetch template bit
		//-----------------------------------------
		
		$templateBit	= trim($this->request['template_bit']);
		$group			= trim($this->request['template_group']);
		$_lang 			= trim($this->request['lang_module']);
		$_app			= trim($this->request['lang_app']);
		
		if( !$templateBit OR !$group )
		{
			$this->returnJsonError( $this->lang->words['missing_template_bit'] );
		}
		
		$templateBit	= 'ajax__' . $templateBit;
		
		if( !is_object( $this->registry->output->getTemplate( $group ) ) OR !method_exists( $this->registry->output->getTemplate( $group ), $templateBit ) )
		{
			$this->returnJsonError( $this->lang->words['missing_template_bit'] );
		}
		
		if( $_lang && $_app )
		{
			$this->registry->class_localization->loadLanguageFile( array( 'public_' . $_lang ), $_app );
		}
		
		$this->returnJsonArray( array( 'html' => $this->registry->output->getTemplate( $group )->$templateBit() ), true );
    }
}