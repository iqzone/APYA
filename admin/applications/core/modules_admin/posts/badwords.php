<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Bad Word Filters
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_posts_badwords extends ipsCommand 
{
	/**
	 * HTML skin object
	 *
	 * @var		object
	 */
	public $html;
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_badwords' );
		$this->html->form_code    = '&amp;module=posts&amp;section=badwords';
		$this->html->form_code_js = '&module=posts&section=badwords';
		
		$this->lang->loadLanguageFile( array( 'admin_posts' ) );

		/* What to do */
		switch( $this->request['do'] )
		{				
			case 'badword_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordAdd();
			break;
				
			case 'badword_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_delete' );
				$this->badwordRemove();
			break;
				
			case 'badword_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordEditForm();
			break;
				
			case 'badword_doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->handleBadwordEdit();
			break;
				
			case 'badword_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordsExport();
			break;
				
			case 'badword_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordsImport();
			break;
			
			default:
			case 'overview':
			case 'badword':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordsOvervew();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Remove a badword filter
	 *
	 * @return	@e void
	 */
	public function badwordRemove()
	{
		/* Check ID */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['bwl_nofiter'], 11138 );
		}
		
		/* Delete */
		$this->DB->delete( 'badwords', "wid={$id}" );
		
		/* Rebuild cache and bounce */
		$this->badwordsRebuildCache();		
		$this->registry->output->global_message = $this->lang->words['bwl_filter_removed'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );
	}	
	
	/**
	 * Handles the badword edit form
	 *
	 * @return	@e void
	 */
	public function handleBadwordEdit()
	{
		/* Check for before */
		if( ! $this->request['before'] )
		{
			$this->registry->output->showError( $this->lang->words['bwl_noword'], 11139 );
		}
		
		/* Check ID */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['bwl_nofilter'], 11140 );
		}
		
		/* Match */
		$this->request['match'] = $this->request['match'] ? 1 : 0;
		
		/* Swap Text */
		$this->DB->setDataType( array( 'type', 'swop' ), 'string' );
			
		$this->DB->update( 'badwords', array( 
												'type'    => $this->request['before'], // do not trim - see bug report 34433
												'swop'    => $this->request['after'], // do not trim - see bug report 34433
												'm_exact' => $this->request['match']
												), "wid=" . $id );
			  
		/* Recache and bounce */
		$this->badwordsRebuildCache();		
		$this->registry->output->global_message = $this->lang->words['bwl_filter_edited'];		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );
	}	
	
	/**
	 * Edit Badword Form
	 *
	 * @return	@e void
	 * @author	Josh
	 */
	public function badwordEditForm()
	{
		/* Check ID */
		$id = intval( $this->request['id'] );
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['bwl_nofilter'], 11141 );
		}
		
		/* Get the field */		
		$this->DB->build( array( 'select' => '*', 'from' => 'badwords', 'where' => "wid='{$id}'" ) );
		$this->DB->execute();
		
		if ( ! $r = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['bwl_filter_404'], 11142 );
		}
		
		/* Form Fields */
		$form           = array();
		$form['before'] = $this->registry->output->formInput('before', stripslashes( $r['type'] ) );
		$form['after']  = $this->registry->output->formInput('after' , stripslashes( $r['swop'] ) );
		$form['match']  = $this->registry->output->formDropdown( 'match', array( 0 => array( 1, $this->lang->words['bwl_exact'] ), 1 => array( 0, $this->lang->words['bwl_loose'] ) ), $r['m_exact'] );
		
		/* Output */
		$this->registry->output->html           .= $this->html->badwordEditForm( $id, $form );		
	}
	
	/**
	 * Handle add bad word request
	 *
	 * @return	@e void
	 */
	public function badwordAdd()
	{
		/* Check for before text */
		if( ! $this->request['before'] )
		{
			$this->registry->output->showError( $this->lang->words['bwl_noword'], 11143 );
		}
		
		/* Match */		
		$this->request['match'] = $this->request['match'] ? 1 : 0;

		/* Insert filter */
		$this->DB->setDataType( array( 'type', 'swop' ), 'string' );
		
		$this->DB->insert( 'badwords', array( 
												'type'    => $this->request['before'], // do not trim - see bug report 34433
												'swop'    => $this->request['after'], // do not trim - see bug report 34433
												'm_exact' => $this->request['match']
												)
							);
		
		/* Rebuild the cache */
		$this->badwordsRebuildCache();
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['bwl_filter_new'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );
	}	
	
	/**
	 * Badword Overview Screen
	 *
	 * @return	@e void
	 */
	public function badwordsOvervew()
	{
		/* Query the bad words */
		$this->DB->build( array( 'select' => '*', 'from' => 'badwords', 'order' => 'type' ) );
		$this->DB->execute();
		
		/* Loop through the results */
		$rows = array();
		
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				$words[] = $r;
			}
			
			foreach( $words as $r )
			{
				$r['replace'] = $r['swop']    ? stripslashes( $r['swop'] ) : '######';
				$r['method']  = $r['m_exact'] ? $this->lang->words['bwl_exact'] : $this->lang->words['bwl_loose'];
				$r['type'] 	  = stripslashes( $r['type'] );
				
				$rows[] = $r;
			}
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->badwordsWrapper( $rows );
	}	
	

	/**
	 * Import badwords from an xml file
	 *
	 * @return	@e void
	 */
	public function badwordsImport()
	{
		/* Get Badwords XML */
		try
		{
			$content = $this->registry->adminFunctions->importXml( 'ipb_badwords.xml' );
		}
		catch ( Exception $e )
		{
			$this->registry->output->showError( $e->getMessage() );
		}
			
		/* Check for content */
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['bwl_upload_failed'];
			$this->badwordsOvervew();
			return;
		}
		
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		if( !count( $xml->fetchElements('badword') ) )
		{
			$this->registry->output->global_message = $this->lang->words['bwl_upload_wrong'];
			$this->badwordsOvervew();
			return;
		}
		
		/* Get a list of current badwords */
		$words = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'badwords', 'order' => 'type' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$words[ $r['type'] ] = 1;
		}
		
		/* Loop through the xml document and insert new bad words */
		foreach( $xml->fetchElements('badword') as $badword )
		{
			$entry  = $xml->fetchElementsFromRecord( $badword );

			/* Get the filter settings */
			$type    = $entry['type'];
			$swop    = $entry['swop'];
			$m_exact = $entry['m_exact'];
			
			/* Skip if it's already in the db */
			if ( $words[ $type ] )
			{
				continue;
			}
			
			/* Add to the db */
			if ( $type )
			{
				$this->DB->insert( 'badwords', array( 'type' => $type, 'swop' => $swop, 'm_exact' => $m_exact ) );
			}
		}
		
		/* Rebuild cache and bounce */
		$this->badwordsRebuildCache();                    
		$this->registry->output->global_message = $this->lang->words['bwl_upload_good'];	
		$this->badwordsOvervew();	
	}
	
	/**
	 * Exports badwords to an xml file
	 *
	 * @return	@e void
	 */
	public function badwordsExport()
	{
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'badwordexport' );
		$xml->addElement( 'badwordgroup', 'badwordexport' );

		/* Query the badwords */
		$this->DB->build( array( 'select' => 'type, swop, m_exact', 'from' => 'badwords', 'order' => 'type' ) );
		$this->DB->execute();
		
		/* Add the bad word entries to the xml file */
		while( $r = $this->DB->fetch() )
		{
			$xml->addElementAsRecord( 'badwordgroup', 'badword', $r );
		}

		/* Create the xml document and send to the browser */
		$xmlData = $xml->fetchDocument();
		$this->registry->output->showDownload( $xmlData, 'ipb_badwords.xml' );
	}
	
	/**
	 * Rebuild badword cache
	 *
	 * @return	@e void
	 */
	public function badwordsRebuildCache()
	{
		$cache = array();
			
		$this->DB->build( array( 'select' => 'type,swop,m_exact', 'from' => 'badwords' ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$cache[] = $r;
		}
		
		usort( $cache, array( $this, '_thisUsort' ) );
				
		$this->cache->setCache( 'badwords', $cache, array( 'array' => 1 ) );
	}
	
	/**
	 * Custom sort operation
	 *
	 * @param	string	A
	 * @param	string	B
	 * @return	integer
	 */
	protected function _thisUsort($a, $b)
	{
		if ( IPSText::mbstrlen($a['type']) == IPSText::mbstrlen($b['type']) )
		{
			return 0;
		}
		return ( IPSText::mbstrlen($a['type']) > IPSText::mbstrlen($b['type']) ) ? -1 : 1;
	}
}