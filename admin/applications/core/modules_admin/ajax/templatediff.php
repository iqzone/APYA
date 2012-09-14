<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * AJAX Functions For applications/core/js/ipb3Templates.js file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * Author: Matt Mecham
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10721 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_templatediff extends ipsAjaxCommand 
{
	/**
	 * Skin functions object handle
	 *
	 * @var		object
	 */
	protected $skinFunctions;
	
    /**
	 * Main executable
	 *
	 * @param	object	registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
    {
    	$registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ), 'core' );
    	
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinDifferences.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinDifferences( $registry );

		/* Check... */
		if ( !$registry->getClass('class_permissions')->checkPermission( 'skindiff_reports', ipsRegistry::$current_application, 'templates' ) )
		{
			$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
	    	exit();
		}
				
    	//-----------------------------------------
    	// What shall we do?
    	//-----------------------------------------
    	
    	switch( $this->request['do'] )
    	{
			default:
    		case 'process':
    			$this->_process();
    		break;
    		case 'merge':
    			$this->_merge();
    		break;
			case 'viewDiff':
				$this->_viewDiff();
			break;
			case 'editDiff':
				$this->_editDiff();
			break;
			case 'saveEdit':
				$this->_saveEdit();
			break;
			case 'viewVersion':
				$this->_viewVersion();
			break;
			case 'resolveAllSingle':
				$this->_resolveAllSingle();
			break;
    	}
    }
    
    /**
	 * Grab a version to show
	 *
	 * @return	@e void
	 */
	protected function _viewVersion()
	{
		$change_id = trim( $this->request['change_id'] );
		$type      = trim( $this->request['type'] );

		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$diff_row = $this->DB->buildAndFetch( array( 'select' => '*',
													 'from'   => 'skin_merge_changes',
													 'where'  => "change_id=" . intval( $change_id )  ) );
		
		
		if ( ! $diff_row['change_key'] )
		{
			$this->returnJsonError( $this->lang->words['ajax_nokeyfound'] );
    		exit();
		}
		
		/* Fetch session */
		$session = $this->skinFunctions->fetchSession( $diff_row['change_session_id'] );
		
		/* Start things off? */
		$return = array( 'data_title'      => $diff_row['change_data_title'],
						 'data_group'      => $diff_row['change_data_group'],
						 'data_type'       => $diff_row['change_data_type'],
						 'data_master_key' => $session['merge_master_key'],
						 'data_content'    => '' );
						 
		/* What to get? */
		switch( $type )
		{
			default:
			case 'original':
				$return['data_content'] = $this->skinFunctions->fetchOriginalItem( $return );
			break;
			case 'custom':
				$return['data_content'] = $this->skinFunctions->fetchCustomItem( $return );
			break;
			case 'new':
				$return['data_content'] = $this->skinFunctions->fetchNewItem( $return );
			break;
		}
		
		/* Formatting */
		$return['data_content'] = htmlspecialchars( $return['data_content'] );
		$return['data_content'] = str_replace( "\n", "<br>", $return['data_content']);
		$return['data_content'] = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$return['data_content']);
		$return['data_content'] = str_replace( "\t", "&nbsp; &nbsp; ", $return['data_content'] );
		
		$this->returnJsonArray( $return );
	}
	
	/**
	 * Save diff
	 *
	 * @return	@e void
	 */
	protected function _saveEdit()
	{
		$change_id = trim( $this->request['change_id'] );
		$content   = $_POST['content'];
		
		/* Re-format */
		$content = str_replace( '\\"', '\\\"', $content );
		$content = IPSText::formToText( $content );
		
		/* Load item from DB */
		$item = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'skin_merge_changes',
												 'where'  => 'change_id=' . intval( $change_id ) ) );
		
		if ( ! $item )
		{
			/* Oops */
			$this->returnJsonError( $this->lang->words['ajax_nosuchitemid'] );
		}
		
					
		/* Save to db */
		$this->DB->update( 'skin_merge_changes', array( 'change_final_content' => $content ), 'change_id=' . intval( $change_id ) );
		
		
		/* Done */
		$this->returnJsonArray( array( 'ok' => true, 'desc' => $this->_buildDescString( $item ) ) );
	}
	
	/**
	 * Save diff
	 *
	 * @return	@e void
	 */
	protected function _resolveAllSingle()
	{
		$change_id = trim( $this->request['change_id'] );
		$type      = trim( $this->request['type'] );
		
		/* Load item from DB */
		$item = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'skin_merge_changes',
												 'where'  => 'change_id=' . intval( $change_id ) ) );
		
		if ( ! $item )
		{
			/* Oops */
			$this->returnJsonError( $this->lang->words['ajax_nosuchitemid'] );
		}
		
		/* Resolve it! */
		$this->skinFunctions->resolveConflict( array( $change_id ), $type );
					
		/* Done */
		$this->returnJsonArray( array( 'ok' => true, 'desc' => $this->_buildDescString( $item ) ) );
	}

	
	/**
	 * Build a desc string
	 *
	 * @param	array		Item data
	 * @return	string		String
	 */
	public function _buildDescString( $item )
	{
		/* INIT */
		$_desc     = '';
		
		/* Fetch basic stats */
		if ( $item['change_data_content'] )
		{
			$item['_diffs'] = substr_count( $item['change_data_content'], '-ips-match:1' );
		}
		
		if ( $item['change_merge_content'] AND stristr( $item['change_merge_content'], '<ips:conflict' ) )
		{
			$item['_conflicts'] = substr_count( $item['change_merge_content'], '<ips:conflict' );
		}
		
		/* Build text string */
		if ( $item['change_is_new'] == 'new' )
		{
			$_desc = $this->lang->words['ajax_newitem4thisv'];
		}
		else
		{
			$_desc = $item['_diffs'] . ' differences';
			
			if ( intval( $item['_conflicts'] ) > 0 )
			{
				if ( $item['change_final_content'] )
				{
					$_desc .= ', <strong>' . $item['_conflicts'] . '</strong>' . ' ' . $this->lang->words['ajax_resolvedconflicts'];
				}
				else
				{
					$_desc .= ', <strong>' . $item['_conflicts'] . '</strong>' . ' ' . $this->lang->words['ajax_mergeconflicts'];
				}
			}
			else
			{
				$_desc .= ',' . ' ' . $this->lang->words['ajax_noconflicts'];
			}
			
			if ( $item['change_changes_applied'] )
			{
				$_desc .= $this->lang->words['ajax_changescommitted'];
			}
		}
		
		return $_desc;
	}
	
	/**
	 * Grab a diff to edit
	 *
	 * @return	@e void
	 */
	protected function _editDiff()
	{
		$change_id = trim( $this->request['change_id'] );
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$diff_row = $this->DB->buildAndFetch( array( 'select' => '*',
													 'from'   => 'skin_merge_changes',
													 'where'  => "change_id=" . intval( $change_id )  ) );
		
		
		if ( ! $diff_row['change_key'] )
		{
			$this->returnJsonError( $this->lang->words['ajax_nokeyfound'] );
    		exit();
		}
		
		/* Prevent two lots of data going through */
		$diff_row['change_data_content']  = '';
		
		/* Convert inline mark-up */
		$diff_row['change_merge_content'] = $this->skinFunctions->formatMergeForEdit( $diff_row['change_merge_content'] );
		
		$this->returnJsonArray( $diff_row );
	}
    
	/**
	 * Grab a diff to show
	 *
	 * @return	@e void
	 */
	protected function _viewDiff()
	{
		$change_id = trim( $this->request['change_id'] );
		$type      = trim( $this->request['type'] );
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$diff_row = $this->DB->buildAndFetch( array( 'select' => '*',
													 'from'   => 'skin_merge_changes',
													 'where'  => "change_id=" . intval( $change_id )  ) );
		
		
		if ( ! $diff_row['change_key'] )
		{
			$this->returnJsonError( $this->lang->words['ajax_nokeyfound'] );
    		exit();
		}
		
		/* Diff */
		if ( $type != 'merge' )
		{
			$diff_row['change_data_content'] = str_replace( "\n", "<br>", $diff_row['change_data_content']);
			$diff_row['change_data_content'] = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$diff_row['change_data_content']);
			$diff_row['change_data_content'] = preg_replace( "#(?<!(\<del|\<ins)) {1}(?!:style)#i", "&nbsp;" ,$diff_row['change_data_content']);
			$diff_row['change_data_content'] = str_replace( "\t", "&nbsp; &nbsp; ", $diff_row['change_data_content'] );
		}
		else
		{
			/* Convert inline mark-up */
			$diff_row['change_data_content'] = $this->skinFunctions->formatMergeForPreview( $diff_row['change_merge_content'] );
		}
		
		$this->returnJsonArray( $diff_row );
	}
	
	/**
	 * Process
	 *
	 * @return	@e void
	 */
    protected function _process()
    { 
    	/* INIT */
		$pergo         = intval( $this->request['perGo'] ) ? intval( $this->request['perGo'] ) : 10;
		$diffSessionID = intval( $this->request['sessionID'] );
		$completed     = 0;
		$type          = 'template';
		$items         = array();
		$completed     = 0;
		
		/* Fetch current session */
		$session = $this->skinFunctions->fetchSession( $diffSessionID );
		
		if ( $session === FALSE )
		{
			$this->returnJsonError( $this->lang->words['ajax_invalidsession'] );
    		exit();
		}
		
		/* Are we doing templates or CSS? */
		if ( $session['merge_templates_togo'] )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_templates',
									 'where'  => 'template_set_id=0 AND template_master_key=\'' . $session['merge_master_key'] . '\'',
									 'order'  => 'template_id ASC',
									 'limit'  => array( intval( $session['merge_templates_done'] ), intval( $pergo ) ) ) );
													 
			$this->DB->execute();
		
			$type = 'template';
			
			while( $row = $this->DB->fetch() )
			{
				$items[] = array( 'data_group'      => $row['template_group'],
								  'data_title'      => $row['template_name'],
								  'data_content'    => $row['template_content'],
								  'data_master_key' => $session['merge_master_key'],
								  'data_set_id'     => $session['merge_set_id'],
								  'data_type'       => 'template' );
			}
			
		}
		
		/* If we're here, set to templates but no templates, check CSS */
		if ( ! count( $items ) AND $type == 'template' )
		{
			$type  = 'css';
			$pergo = 1;
			
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_css',
									 'where'  => 'css_set_id=0 AND css_master_key=\'' . $session['merge_master_key'] . '\'',
									 'order'  => 'css_id ASC',
									 'limit'  => array( intval( $session['merge_css_done'] ), intval( $pergo ) ) ) );
													 
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$items[] = array( 'data_group'      => $row['css_app'],
								  'data_title'      => $row['css_group'],
								  'data_content'    => $row['css_content'],
								  'data_master_key' => $session['merge_master_key'],
								  'data_set_id'     => $session['merge_set_id'],
								  'data_type'       => 'css' );
			}
		}
	
		/* If we don't have items, we're probably done */
		if ( ! count( $items ) )
		{
			/* Fetch some counts templates */
			$templates = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
														  'from'   => 'skin_merge_changes',
														  'where'  => 'change_is_diff=1 AND change_is_new=0 AND change_data_type=\'template\' AND change_session_id=\'' . $diffSessionID . '\'' ) );
														  
			/* Fetch some counts CSS */
			$css       = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
														  'from'   => 'skin_merge_changes',
														  'where'  => 'change_is_diff=1 AND change_is_new=0 AND change_data_type=\'css\' AND change_session_id=\'' . $diffSessionID . '\'' ) );
			
			
			$this->DB->update( 'skin_merge_session', array( 'merge_m_templates_togo' => $templates['count'],
															'merge_m_css_togo'       => $css['count'],
															'merge_diff_done'		 => 1 ), 'merge_id=' . intval( $diffSessionID ) );
			/* Flag as completed */
			$completed = 1;
		}
		else
		{
			foreach( $items as $item )
			{
				/* Fetch templates to compare */
				$new      = $item['data_content'];
				$original = $this->skinFunctions->fetchOriginalItem( $item );
				$custom   = $this->skinFunctions->fetchCustomItem( $item );
				
				/* Other data */
				$key      = $diffSessionID.':'.$item['data_group'].':'.$item['data_title'];
				
				/* Got an original bit? */
				if ( $original !== FALSE )
				{
					/* Check difference */
					$difference = $this->skinFunctions->fetchDifferences( $original, $new );
					
					if ( $difference !== FALSE )
					{
						/* Prevent dupes */
						$diffCheck = $this->DB->buildAndFetch( array( 'select' => 'change_id, change_key',
																	  'from'   => 'skin_merge_changes',
																	  'where'  => "change_key='" . $key . "'" ) );

						if ( $diffCheck['change_id'] )
						{
							$this->DB->update( 'skin_merge_changes', array( 'change_key'				=> $key,
																			'change_session_id'		 	=> $diffSessionID,
																			'change_updated'			=> time(),
																			'change_data_group'		 	=> $item['data_group'],
																			'change_data_title'			=> $item['data_title'],
																			'change_data_content'		=> $difference,
																			'change_data_type'			=> $item['data_type'],
																			'change_is_diff'			=> 1,
																			'change_is_new'				=> 0,
																			'change_can_merge'			=> ( $custom ) ? 1 : 0 ), 'change_id=' . intval( $diffCheck['change_id'] ) );
						}
						else
						{
							$this->DB->insert( 'skin_merge_changes', array( 'change_key'				=> $key,
																			'change_session_id'		 	=> $diffSessionID,
																			'change_updated'			=> time(),
																			'change_data_group'		 	=> $item['data_group'],
																			'change_data_title'			=> $item['data_title'],
																			'change_data_content'		=> $difference,
																			'change_data_type'			=> $item['data_type'],
																			'change_is_diff'			=> 1,
																			'change_is_new'				=> 0,
																			'change_can_merge'			=> ( $custom ) ? 1 : 0 ) );
						}
					}
				}
				else
				{
					
					$this->DB->insert( 'skin_merge_changes', array( 'change_key'				=> $key,
																	'change_session_id'		 	=> $diffSessionID,
																	'change_updated'			=> time(),
																	'change_data_group'		 	=> $item['data_group'],
																	'change_data_title'			=> $item['data_title'],
																	'change_data_content'		=> htmlspecialchars( $item['data_content'] ),
																	'change_data_type'			=> $item['data_type'],
																	'change_is_diff'			=> 0,
																	'change_is_new'				=> 1,
																	'change_can_merge'			=> 0 ) );
																			
				}
				
				/* Increment */
				if ( $type == 'template' )
				{
					$session['merge_templates_done']++;
				}
				else
				{
					$session['merge_css_done']++;
				}
			}
		}
		
		/* Update current session */
		$this->DB->update( 'skin_merge_session', array( 'merge_templates_done' => intval( $session['merge_templates_done'] ), 'merge_css_done' => intval( $session['merge_css_done'] ) ), 'merge_id='.$diffSessionID );
		
		$done  = intval( $session['merge_templates_done'] ) + intval( $session['merge_css_done'] );
		$total = $session['merge_templates_togo'] + $session['merge_css_togo'];
		
		/* Messages */
		if ( $type == 'template' )
		{
			$title		= $this->lang->words['ajax_tbdlll'];
			$message	= sprintf( $this->lang->words['ajax_tbdprocess'], $session['merge_templates_done'], $session['merge_templates_togo'] );
		}
		else
		{
			$title		= $this->lang->words['ajax_cssdlll'];
			$message	= sprintf( $this->lang->words['ajax_cssdprocess'], $session['merge_css_done'], $session['merge_css_togo'] );
		}

		/* Give Jason back (L or H?) */
		$this->returnJsonArray( array( 'processed' => $done, 'completed' => $completed, 'title' => $title, 'message' => $message, 'perGo' => $pergo  ) );
    }
    
    /**
	 * Process merge
	 *
	 * @return	@e void
	 */
    protected function _merge()
    { 
    	/* INIT */
		$pergo         = intval( $this->request['perGo'] ) ? intval( $this->request['perGo'] ) : 10;
		$diffSessionID = intval( $this->request['sessionID'] );
		$completed     = 0;
		$type          = 'template';
		$items         = array();
		$completed     = 0;
	
		/* Fetch current session */
		$session = $this->skinFunctions->fetchSession( $diffSessionID );
		
		if ( $session === FALSE )
		{
			$this->returnJsonError( $this->lang->words['ajax_invalidsession'] );
		}
		
		/* Load merge class */
		require_once( IPS_KERNEL_PATH . 'classMerge.php' );/*noLibHook*/

		/* Are we doing templates or CSS? */
		if ( $session['merge_m_templates_togo'] )
		{
			$this->DB->build( array( 'select'   => 'c.*',
									 'from'     => array( 'skin_merge_changes' => 'c' ),
									 'where'    => 'change_session_id=' . intval( $diffSessionID ) . ' AND change_data_type=\'template\' AND change_is_diff=1 AND change_is_new=0',
									 'order'    => 'change_id ASC',
									 'limit'    => array( intval( $session['merge_m_templates_done'] ), intval( $pergo ) ),
									 'add_join' => array( array(  'select' => 's.*',
									 						      'from'   => array( 'skin_templates' => 's' ),
									 					    	  'where'  => 's.template_set_id=0 AND s.template_master_key=\'' . $session['merge_master_key'] . '\' AND s.template_group=c.change_data_group AND s.template_name=c.change_data_title',
									 					    	  'type'   => 'left' ) ) ) );
			$this->DB->execute();
		
			$type = 'template';
			
			while( $row = $this->DB->fetch() )
			{
				$items[] = array( 'data_group'      => $row['template_group'],
								  'data_title'      => $row['template_name'],
								  'data_content'    => $row['template_content'],
								  'data_master_key' => $session['merge_master_key'],
								  'data_set_id'     => $session['merge_set_id'],
								  'data_type'       => 'template' );
			}
			
		}
		
		/* If we're here, set to templates but no templates, check CSS */
		if ( ! count( $items ) AND $type == 'template' )
		{
			$type  = 'css';
			$pergo = 1;
			
			$this->DB->build( array( 'select'   => 'c.*',
									 'from'     => array( 'skin_merge_changes' => 'c' ),
									 'where'    => 'change_session_id=' . intval( $diffSessionID ) . ' AND change_data_type=\'css\' AND change_is_diff=1 AND change_is_new=0',
									 'order'    => 'change_id ASC',
									 'limit'    => array( intval( $session['merge_m_css_done'] ), intval( $pergo ) ),
									 'add_join' => array( array(  'select' => 's.*',
									 						      'from'   => array( 'skin_css' => 's' ),
									 					    	  'where'  => 's.css_set_id=0 AND s.css_master_key=\'' . $session['merge_master_key'] . '\' AND s.css_app=c.change_data_group AND s.css_group=c.change_data_title',
									 					    	  'type'   => 'left' ) ) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$items[] = array( 'data_group'      => $row['css_app'],
								  'data_title'      => $row['css_group'],
								  'data_content'    => $row['css_content'],
								  'data_master_key' => $session['merge_master_key'],
								  'data_set_id'     => $session['merge_set_id'],
								  'data_type'       => 'css' );
			}
		}
	
		/* If we don't have items, we're probably done */
		if ( ! count( $items ) )
		{
			/* Flag as completed */
			$completed = 1;
		}
		else
		{
			foreach( $items as $item )
			{
				/* Fetch templates to compare */
				$new      = $item['data_content'];
				$original = $this->skinFunctions->fetchOriginalItem( $item );
				$custom   = $this->skinFunctions->fetchCustomItem( $item );
				
				/* Other data */
				$key      = $diffSessionID.':'.$item['data_group'].':'.$item['data_title'];
				
				/* Got all the required data? */
				if ( $new AND $original AND $custom )
				{
					/* Run the merge */
					$merge = new ThreeWayMerge( $original, $new, $custom, ( $type == 'template' ) ? 'lite' : 'full' );
							
					$result = $merge->merge();
					
					if ( $result !== FALSE )
					{
						/* Check for conflicts */
						$conflicts = ( stristr( $result, '<ips:conflict' ) ) ? 1 : 0;
						
						$this->DB->update( 'skin_merge_changes', array( 'change_updated'			=> time(),
																		'change_merge_content'		=> $result,
																		'change_is_conflict'		=> $conflicts,
																		'change_can_merge'			=> 1 ), 'change_key=\'' . $key . '\'' );
						
						
					}
				}
				
				/* Increment */
				if ( $type == 'template' )
				{
					$session['merge_m_templates_done']++;
				}
				else
				{
					$session['merge_m_css_done']++;
				}
			}
		}
		
		/* Update current session */
		$this->DB->update( 'skin_merge_session', array( 'merge_m_templates_done' => intval( $session['merge_m_templates_done'] ), 'merge_m_css_done' => intval( $session['merge_m_css_done'] ) ), 'merge_id=' . $diffSessionID );
		
		$done  = intval( $session['merge_m_templates_done'] ) + intval( $session['merge_m_css_done'] );
		$total = $session['merge_m_templates_togo'] + $session['merge_m_css_togo'];
		
		/* Messages */
		if ( $type == 'template' )
		{
			$title		= $this->lang->words['ajax_tbmlll'];
			$message	= sprintf( $this->lang->words['ajax_tbdprocess'], $session['merge_m_templates_done'], $session['merge_m_templates_togo'] );
		}
		else
		{
			$title		= $this->lang->words['ajax_cssmlll'];
			$message	= sprintf( $this->lang->words['ajax_cssdprocess'], $session['merge_m_css_done'], $session['merge_m_css_togo'] );
		}
		
		/* Give Jason back (L or H?) */
		$this->returnJsonArray( array( 'processed' => $done, 'completed' => $completed, 'title' => $title, 'message' => $message, 'perGo' => $pergo, 'totalBits' => $total ) );
    }

}