<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Quick help - shows popups with help information
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_help_quickhelp extends ipsCommand
{
	/**
	 * Array of help text
	 *
	 * @var		array			Help texts
	 */
	protected $help_text			= array();
	
	/**
	 * Initialize the help text array
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _initText()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_system' ) );

		return array(	'mg_dohtml' => array( 'title' => $this->lang->words['q_html'],
											  'body'  => $this->lang->words['q_html_info'],
											 ),
											 
						'mod_mmod' =>  array( 'title' => $this->lang->words['q_multi'],
											  'body'  => $this->lang->words['q_multi_info'],
											 ),
											 
						'set_spider' => array( 'title' => $this->lang->words['q_bots'],
											  'body'  => $this->lang->words['q_bots_info'],
											 ),
		
						'mg_upload' => array( 'title' => $this->lang->words['q_upload'],
											  'body'  => $this->lang->words['q_upload_info'],
											 ),
		
		
						'mg_promote' => array( 'title' => $this->lang->words['q_promote'],
											   'body'  => $this->lang->words['q_promote_info'],
											 ),
						's_reg_antispam' => array ( 'title' => $this->lang->words['q_captcha'],
													'body'  => $this->lang->words['q_captcha_info'],
											 ),
											 
						'm_bulkemail'    => array ( 'title' => $this->lang->words['q_bulk'],
												    'body' => $this->lang->words['q_bulk_info'],
												),
						'comp_menu' => array ( 'title' => $this->lang->words['q_components'],
											   'body'  => $this->lang->words['q_components_info'],
											 ),
					);
	
	}

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$id = $this->request['id'];
		
		if( $this->request['do'] == 'redirect' )
		{
			$this->_redirect( $id );
		}
		
		$this->help_text = $this->_initText();
		
		if ($this->help_text[ $id ]['title'] == "")
		{
			$this->registry->output->showError( $this->lang->words['q_nohelp'], 11148 );
		}
		
		$this->registry->output->html .= $this->registry->output->global_template->quickHelp( $this->help_text[$id]['title'], $this->help_text[$id]['body'] );
		
		$this->registry->output->printPopupWindow();
	}
	
	/**
	 * Redirect offsite for more help/options
	 *
	 * @param	string		'key' to redirect to
	 * @return	@e void		[Outputs to screen]
	 */
	private function _redirect( $id )
	{
		switch( $id )
		{
			case 'docs':
				$url = "http://external.ipslink.com/ipboard30/landing/?p=docs-ipb";
			break;

			case 'resources':
				$url = "http://external.ipslink.com/ipboard30/landing/?p=resources";
			break;
			
			case 'contact':
				$url = "http://external.ipslink.com/ipboard30/landing/?p=contact";
			break;
			
			case 'features':
				$url = "http://external.ipslink.com/ipboard30/landing/?p=suggestfeatures";
			break;
			
			case 'bugs':
				$url = "http://external.ipslink.com/ipboard30/landing/?p=bugs";
			break;
			
			default:
			case 'support':
				$url = "http://external.ipslink.com/ipboard30/landing/?p=support";
			break;
		}
			
		$this->registry->output->silentRedirect( $url );
	}	
}