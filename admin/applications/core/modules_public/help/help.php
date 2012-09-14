<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Help File System
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
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

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_help_help extends ipsCommand
{
	/**
	 * HTML to output
	 *
	 * @var		string			HTML
	 */
	public $output		= "";

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_help' ) );

		/* What to do? */
		switch( $this->request['do'] )
		{
			case '01':
				$this->helpShowSection();
			break;

			default:
				$this->helpShowTitles();
			break;
		}
				
		/* Output */
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}

	/**
	 * Show help topics
	 *
	 * @return	@e void
	 */
 	public function helpShowTitles()
 	{
		/* INI */
		$seen = array();
		
		
		/* Query the help topics */
		$this->DB->build( array( 'select' => 'id, title, description, app', 'from' => 'faq', 'order'  => 'position ASC' ) );
		$this->DB->execute();
		
		/* Loop through topics */		
		$rows = array();		

		while( $row = $this->DB->fetch() )
		{
			if( !IPSLib::appIsInstalled( $row['app'] ) )
			{
				continue;
			}

			if( isset( $seen[ $row['title'] ] ) )
			{
				continue;
			}
			else
			{
				$seen[ $row['title'] ] = 1;
			}

			$rows[] = $row;
			
		}
		
		/* Output */
		$this->output .= $this->registry->output->getTemplate('help')->helpShowTopics( 
																						$this->lang->words['page_title'], 
																						$this->lang->words['help_txt'], 
																						$this->lang->words['choose_file'], 
																						$rows 
																					);
																					
		/* Navigation */
		$this->registry->output->setTitle( $this->lang->words['page_title'] . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->output->addNavigation( $this->lang->words['page_title'], '' );
	}
	 
	/**
	 * Displays a help file
	 *
	 * @return	@e void
	 */
 	public function helpShowSection()
 	{
 		/* Check ID */
 		$id = $this->request['HID'] ? intval( $this->request['HID'] ) : 0;
 		
 		if ( ! $id )
 		{
 			$this->helpShowTitles();
 			return;
 		}
 		
 		/* Query the hel topic */
 		$topic = $this->DB->buildAndFetch( array( 'select' => 'id, title, text', 'from' => 'faq', 'where' => 'id=' . $id ) );

		if ( ! $topic['id'] )
		{
			$this->registry->output->showError( 'help_no_id', 10128 );
		}

		/* Parse out board URL */
		$topic['text'] = str_replace( '{board_url}', $this->settings['base_url'], $topic['text'] );

		IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'global';
		
		$topic['text']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $topic['text'] );
		
		if ( $this->request['hl'] )
		{
			$topic['text'] = IPSText::searchHighlight( $topic['text'], $this->request['hl'] );
			$topic['title'] = IPSText::searchHighlight( $topic['title'], $this->request['hl'] );
		}
		
		/* Output */
		$this->output .= $this->registry->output->getTemplate( 'help' )->helpShowSection( 
																							$this->lang->words['help_topic'], 
																							$this->lang->words['topic_text'], 
																							$topic['title'], 
																							$topic['text']
																						);
		
		/* Navigation */
		$this->registry->output->setTitle( $this->lang->words['help_topic'] . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->output->addNavigation( $this->lang->words['help_topics'], "app=core&amp;module=help" );
		$this->registry->output->addNavigation( $this->lang->words['help_topic'], '' );	
		
		if( $this->request['xml'] == 1 )	
		{
			$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
			$classAjax   = new $classToLoad();
			$classAjax->returnHtml( $this->output );
		}
 	} 
}