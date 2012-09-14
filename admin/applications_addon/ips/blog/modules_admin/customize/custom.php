<?php
/**
 * @file		custom.php 	IP.Blog Custom themes management
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		27th January 2004
 * $LastChangedDate: 2011-12-30 12:18:03 -0500 (Fri, 30 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 11 $
 * @todo		Update class_xml references to classXml
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_blog_customize_custom
 * @brief		IP.Blog Custom themes management
 */
class admin_blog_customize_custom extends ipsCommand
{
	/**
	 * Array of allowed file types
	 *
	 * @var		$allowed_files
	 */
	protected $allowed_files = array( 'png', 'jpeg', 'jpg', 'gif' );
	
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin Template */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blog', 'blog' );
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_blog' ), 'blog' );
		
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=customize&amp;section=custom&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=customize&section=custom&';
		
		switch( $this->request['do'] )
		{
			//-----------------------------------------
			// Themes
			//-----------------------------------------
			case 'themes':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_manage' );
				$this->themesOverview();
			break;
			
			case 'themeAdd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_manage' );
				$this->themesForm();
			break;
			
			case 'themeEdit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_manage' );
				$this->themesForm( $this->request['id'] );
			break;
			
			case 'themeDoAdd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_manage' );
				$this->themesSave();
			break;
			
			case 'themeDoEdit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_manage' );
				$this->themesSave( $this->request['id'] );
			break;
			
			case 'themeDelete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_delete' );
				$this->themesDelete();
			break;
			
			case 'themeExport':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_manage' );
				$this->themesExport();
			break;
			
			case 'themeImport':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_manage' );
				$this->themesImport();
			break;
			
			case 'themeToggle':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_manage' );
				$this->themesToggle();
			break;
							
			//-----------------------------------------
			// Member customized themes
			//-----------------------------------------
			
			case 'custom':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_approve' );
				$this->customThemes();
			break;
			
			case 'customView':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_approve' );
				$this->customView();
			break;
			
			case 'customHandle':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_approve' );
				$this->customHandle();
			break;
			
			case 'customEdit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_approve' );
				$this->customEditForm();
			break;
			
			case 'customSave':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_theme_approve' );
				$this->customEditSave();
			break;
			
			//-----------------------------------------
			// Default / Home Page
			//-----------------------------------------
			case 'home':
			default:
				$this->themesOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
		
	/**
	 * Customization Full View
	 *
	 * @return	@e void
	 */
	public function customThemes()
	{
		/* Pagination */
		$start = $this->request['st'] ? intval( $this->request['st'] ) : 0;
		$limit = 20;								  
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as cnt', 'from' => 'blog_blogs', 'where' => "(blog_theme_custom IS NOT NULL AND blog_theme_custom != '') OR (blog_theme_final IS NOT NULL AND blog_theme_final != '')" ) );
		
		$links = $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $count['cnt'],
																	'itemsPerPage'		=> $limit,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> "{$this->settings['base_url']}{$this->form_code}&do=custom",
															)	);
		
		$this->DB->build( array(
								'select'	=> 'b.blog_id, b.member_id, b.blog_name, b.blog_theme_custom, b.blog_theme_final, b.blog_theme_approved',
								'from'		=> array( 'blog_blogs' => 'b' ),
								'where'		=> "(b.blog_theme_custom IS NOT NULL AND b.blog_theme_custom != '' ) OR (b.blog_theme_final IS NOT NULL AND b.blog_theme_final != '')",
								'order'		=> 'b.blog_name asc',
								'add_join'	=> array(
													array(
															'select'	=> 'm.members_display_name',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=b.member_id',
															'type'		=> 'left'
															)
														)
						)	);
		$this->DB->execute();
		
		/* Build Output Rows */
		$rows = array();
		
		while( $r = $this->DB->fetch() )
		{
			$r['_suspicious'] = $this->_checkSuspect( $r['blog_theme_custom'] ) ? true : false;
			
			$r['_approved'] = $r['blog_theme_approved'] ? 1 : ( ( $r['blog_theme_final'] AND ! $r['blog_theme_custom'] ) ? 1 : 0 );
			
			$rows[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->memberCustomThemesList( $rows );
	}
	
	/**
	 * Customization Edit Form
	 *
	 * @return	@e void
	 */
	public function customEditForm()
	{
		$id = intval( $this->request['id'] );
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['cc_determineid'], 11613 );
		}
		
		$blog = $this->DB->buildAndFetch( array( 'select' => 'blog_id, blog_name, blog_theme_custom, blog_theme_final', 'from' => 'blog_blogs', 'where' => 'blog_id=' . $id ) );
		
		if( !$blog['blog_id'] )
		{
			$this->registry->output->showError( $this->lang->words['cc_determineid'], 11614 );
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->memberCustomThemeEdit( $blog['blog_id'], $blog['blog_name'], htmlspecialchars( $blog['blog_theme_custom'] ? $blog['blog_theme_custom'] : $blog['blog_theme_final'], ENT_QUOTES ) );
	}
	
	/**
	 * Customization Edit Saving
	 *
	 * @return	@e void
	 */
	public function customEditSave()
	{
		$id = intval( $this->request['id'] );

		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['cc_determineid'], 11615 );
		}
		
		$blog = $this->DB->buildAndFetch( array( 'select' => 'blog_id, blog_name, blog_theme_custom, blog_theme_final', 'from' => 'blog_blogs', 'where' => 'blog_id=' . $id ) );
		
		$css = IPSText::stripslashes( strip_tags( $_REQUEST['css'] ) );

		$update = $blog['blog_theme_custom'] ? array( 'blog_theme_custom' => $css ) : array( 'blog_theme_final' => $css );

		$this->DB->update( 'blog_blogs', $update , 'blog_id=' . $id );
		
		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['cc_themeedited'];
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}&do=custom" );
	}
	
	/**
	 * Customization Handling
	 *
	 * @return	@e void
	 */
	public function customHandle()
	{
		$ids = array();
		
		if( !empty( $this->request['id'] ) AND is_int( $this->request['id'] ) )
		{
			$ids[] = intval( $this->request['id'] );
		}
		
		foreach( $this->request as $k => $v )
		{
			if ( preg_match( "/^id_(\d+)$/", $k, $match ) )
			{
				if ($this->request[ $match[0] ])
				{
					$ids[] = intval($match[1]);
				}
			}
		}
		
		if( !count($ids) )
		{
			$this->registry->output->showError( $this->lang->words['cc_noselmod'], 11616 );
		}
		
		$actions 	= array( 'approve', 'clear' );
		$action		= ( isset($this->request['action']) AND in_array( $this->request['action'], $actions ) ) ? $this->request['action'] : '';
		
		if( !$action )
		{
			$this->registry->output->showError( $this->lang->words['cc_noaction'], 11617 );
		}
		
		$ids = array_unique($ids);
		$id_str = implode( ',', $ids );
		
		$this->DB->build( array(
								'select'	=> 'blog_id, blog_theme_approved,blog_theme_id,blog_theme_final,blog_theme_custom',
								'from'		=> 'blog_blogs',
								'where'		=> 'blog_id IN(' . $id_str . ')',
						)	);
		$qr = $this->DB->execute();
		
		while( $r = $this->DB->fetch($qr) )
		{
			if( $action == 'approve' )
			{
				if( $r['blog_theme_custom'] )
				{
					$this->DB->update( 'blog_blogs', "blog_theme_approved=1, blog_theme_id=0, blog_theme_final=blog_theme_custom, blog_theme_custom=''", 'blog_id=' . $r['blog_id'], false, true );
				}
			}
			else
			{
				if( $r['blog_theme_custom'] )
				{
					$this->DB->update( 'blog_blogs', array( 'blog_theme_custom' => '' ), 'blog_id=' . $r['blog_id'] );
				}
				else
				{
					$this->DB->update( 'blog_blogs', array( 'blog_theme_final' => '' ), 'blog_id=' . $r['blog_id'] );
				}
			}
		}
		
		$append = $this->request['full'] ? "&code=custom" : '';
		
		$text	= $action == 'approve' ? $this->lang->words['cc_approved'] : $this->lang->words['cc_cleared'];
		
		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['cc_thisorthat'];
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}&do=custom" );
	}
		
	/**
	 * Customization View
	 *
	 * @return	@e void
	 */
	public function customView()
	{
		/* Check ID */
		$blogid = intval( $this->request['id'] );
		
		if( ! $blogid )
		{
			$this->registry->output->showError( $this->lang->words['cc_nolocblog'], 11618 );
		}
		
		/* Query the style */
		$blog = $this->DB->buildAndFetch( array( 'select' => 'blog_id, blog_theme_custom, blog_theme_final, blog_name', 'from' => 'blog_blogs', 'where' => 'blog_id=' . $blogid ) );
		
		if( ! $blog['blog_id'] )
		{
			$this->registry->output->showError( $this->lang->words['cc_nolocblog'], 11619 );
		}
			
		$custom_css = htmlentities( $blog['blog_theme_custom'] ? $blog['blog_theme_custom'] : $blog['blog_theme_final'], ENT_QUOTES );
		
		$highlight	= "<span style='background-color: red; color: white;'>%s</span>";
		
		$custom_css = str_replace( "expression", sprintf( $highlight, "expression" ), $custom_css );
		$custom_css = str_replace( "@import", sprintf( $highlight, "@import" ), $custom_css );
		$custom_css = str_replace( "behavior", sprintf( $highlight, "behavior" ), $custom_css );
		$custom_css = str_replace( "moz-binding", sprintf( $highlight, "moz-binding" ), $custom_css );
		
		/* Output */
		$this->registry->output->html .= $this->html->memberCustomThemeViewPopup( $blog['blog_name'], $custom_css, $blog['blog_id'] );
		$this->registry->output->printPopupWindow();
	}
	
	/**
	 * Check CSS for suspicious stuff...
	 *
	 * @param  string  $css
	 * @return bool
	 */
	protected function _checkSuspect( $css='' )
	{
		if( !$css )
		{
			return false;
		}

		$check_against = IPSText::getTextClass('bbcode')->xssHtmlClean( $css );

		if( $css != $check_against )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Themes Overview Page
	 *
	 * @return	@e void
	 */
	public function themesOverview()
	{
		/* Init vars */
		$themes = array();
		
		/* Query the themes */
		$this->DB->build( array( 'select'	=> '*', 'from' => 'blog_themes', 'order' => 'theme_name ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$themes[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->themes_overview( $themes );
	}
	
	/**
	 * Themes Form (add/edit)
	 *
	 * @param	integer		$id		Theme ID
	 * @return	@e void
	 */
	public function themesForm( $id=0 )
	{
		/* Edit Theme */
		if( intval( $id ) > 0 )
		{
			/* Form Do */
			$do = 'themeDoEdit';
			
			/* Get Current Values */
			$theme = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_themes', 'where' => 'theme_id=' . intval( $id ) ) );

			if( ! count( $theme ) )
			{
				$this->registry->output->showError( $this->lang->words['cc_noloctheme'], 11620 );
			}
		}
		/* Create theme */
		else
		{
			/* Form Do */
			$do = 'themeDoAdd';
						
			/* Default Values */
			$theme = array(
							'theme_on'			=> 1,
							'theme_css'			=> '',
							'theme_images'		=> '',
							'theme_opts'		=> array(),
							'theme_author'		=> $this->memberData['members_display_name'],
							'theme_name'		=> $this->lang->words['cc_newtheme'],
							'theme_homepage'	=> $this->settings['board_url'],
							'theme_email'		=> $this->memberData['email'],
							'theme_desc'		=> $this->lang->words['cc_desctheme'],
							);
		}
		
		/* Form Elements */
		$form = array();
		
		$form['theme_name']				= $this->registry->output->formInput( 'theme_name', $this->request['theme_name'] ? stripslashes($this->request['theme_name']) : $theme['theme_name'] );
		$form['theme_desc']				= $this->registry->output->formTextarea( 'theme_desc', $_POST['theme_desc'] ? htmlspecialchars( $_POST['theme_desc'], ENT_QUOTES ) : htmlspecialchars( $theme['theme_desc'], ENT_QUOTES ) );
		$form['theme_on']				= $this->registry->output->formYesNo( 'theme_on', $this->request['theme_on'] ? $this->request['theme_on'] : $theme['theme_on'] );
		$form['theme_author']			= $this->registry->output->formInput( 'theme_author', $this->request['theme_author'] ? stripslashes($this->request['theme_author']) : $theme['theme_author'] );
		$form['theme_homepage']			= $this->registry->output->formInput( 'theme_homepage', $this->request['theme_homepage'] ? stripslashes($this->request['theme_homepage']) : $theme['theme_homepage'] );
		$form['theme_email']			= $this->registry->output->formInput( 'theme_email', $this->request['theme_email'] ? stripslashes($this->request['theme_email']) : $theme['theme_email'] );
		$form['theme_images']			= $this->registry->output->formInput( 'theme_images', $this->request['theme_images'] ? stripslashes($this->request['theme_images']) : $theme['theme_images'] );
		$form['theme_css']				= $this->registry->output->formTextarea( 'theme_css', $_POST['theme_css'] ? htmlspecialchars( $_POST['theme_css'], ENT_QUOTES ) : htmlspecialchars( $theme['theme_css'], ENT_QUOTES ), 60, 40 );
		$form['theme_css_overwrite']	= $this->registry->output->formYesNo( 'theme_css_overwrite', $this->request['theme_css_overwrite'] ? $this->request['theme_css_overwrite'] : $theme['theme_css_overwrite'] );
		
		/* Output */
		$this->registry->output->html .= $this->html->themeForm( $id, $do, $form );
	}
	
	/**
	 * Themes Save (add/edit)
	 *
	 * @param	integer		$id		Theme ID
	 * @return	@e void
	 */
	public function themesSave( $id=0 )
	{
		/* Edit */
		if( intval( $id ) > 0 )
		{
			/* Check for valid theme */
			$theme = $this->DB->buildAndFetch( array( 'select' => 'theme_id', 'from' => 'blog_themes', 'where' => 'theme_id=' . intval( $id ) ) );

			if( ! $theme['theme_id'] )
			{
				$this->registry->output->showError( $this->lang->words['cc_noloctheme'], 11621 );
			}
		}
		
		/* DB Array */
		$theme = array(
						'theme_on'				=> intval( $this->request['theme_on'] ),
						'theme_css'				=> IPSText::stripslashes( $_POST['theme_css'] ),
						'theme_css_overwrite'	=> intval( $this->request['theme_css_overwrite'] ),
						'theme_images'			=> $this->request['theme_images'],
						'theme_name'			=> $this->request['theme_name'],
						'theme_author'			=> $this->request['theme_author'],
						'theme_homepage'		=> $this->request['theme_homepage'],
						'theme_email'			=> $this->request['theme_email'],
						'theme_desc'			=> $this->request['theme_desc'],
					);
		
		/* Error Checking */
		if( ! $theme['theme_name'] OR ! $theme['theme_css'] )
		{
			$this->registry->output->showError( $this->lang->words['cc_fillform'], 11622 );
		}
		
		if( $id )
		{
			$this->DB->update( 'blog_themes', $theme, 'theme_id=' . $id );
		}
		else
		{
			$this->DB->insert( 'blog_themes', $theme );
		}
		
		/* Recache and Bounce */
		$this->reCacheThemes();
		
		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['cc_themesaved'];
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}&do=themes" );
	}
	
	/**
	 * Themes delete
	 *
	 * @return	@e void
	 */
	public function themesDelete()
	{
		/* Check ID */
		$id = intval( $this->request['id'] );
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['cc_nolocthemedel'], 11623 );
		}
		
		/* Delete */
		$this->DB->delete( 'blog_themes', 'theme_id=' . $id );
		
		/* Update blogs */
		$this->DB->update( 'blog_blogs', array( 'blog_theme_id' => 0 ), 'blog_theme_id=' . $id );
		
		$this->reCacheThemes();
		
		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['cc_themdeleted'];
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}&do=themes" );
	}
	
	/**
	 * Themes toggle (enabled/disabled)
	 *
	 * @return	@e void
	 */
	public function themesToggle()
	{
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['cc_nolocthemetog'], 11624 );
		}
		
		$theme = $this->DB->buildAndFetch( array( 'select' => 'theme_on, theme_id', 'from' => 'blog_themes', 'where' => 'theme_id=' . $id ) );
		
		if( ! $theme['theme_id'] )
		{
			$this->registry->output->showError( $this->lang->words['cc_nolocthemetog'], 11625 );
		}
		
		$enabled = $theme['theme_on'] ? 0 : 1;
		
		$this->DB->update( 'blog_themes', array( 'theme_on' => $enabled ), 'theme_id=' . $id );
		
		if( ! $enabled )
		{
			$this->DB->update( 'blog_blogs', array( 'blog_theme_id' => 0 ), 'blog_theme_id=' . $id );
		}
		
		$this->reCacheThemes();
		
		/* Redirect */
		$this->registry->output->global_message = $enabled ? $this->lang->words['cc_theme_enabled'] : $this->lang->words['cc_theme_disabled'];
		$this->registry->output->silentRedirectWithMessage( "{$this->settings['base_url']}{$this->html->form_code}do=themes" );
	}
	
	/**
	 * Themes export
	 *
	 * @return	@e void
	 */
	public function themesExport()
	{
		/* Check ID */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['cc_nolocthemeexp'], 11626 );
		}
		
		$theme = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_themes', 'where' => 'theme_id=' . $id ) );
		
		if( ! $theme['theme_id'] )
		{
			$this->registry->output->showError( $this->lang->words['cc_nolocthemeexp'], 11627 );
		}
		
		//-------------------------------
		// Get XML class
		//-------------------------------

		require_once( IPS_KERNEL_PATH . 'class_xml.php' );/*noLibHook*/

		$xml = new class_xml();

		$xml->xml_set_root( 'blogthemeexport', array( 'exported' => time() ) );

		//-------------------------------
		// Add component
		//-------------------------------

		$xml->xml_add_group( 'blogtheme' );

		$entry		= array();
		$content	= array();

		foreach( $theme as $k => $v )
		{
			if( $k == 'theme_id' )
			{
				continue;
			}
			
			$content[] = $xml->xml_build_simple_tag( $k, $v );
		}

		$entry[] = $xml->xml_build_entry( 'theme', $content );

		$xml->xml_add_entry_to_group( 'blogtheme', $entry );

		$xml->xml_format_document();

		//-------------------------------
		// Print to browser
		//-------------------------------

		$this->registry->output->showDownload( $xml->xml_document, 'blog_theme.xml', '', 0 );		
	}
	
	/**
	 * Themes import
	 *
	 * @return	@e void
	 */
	public function themesImport()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$updated        = 0;
		$inserted       = 0;

		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// check and load from server
			//-----------------------------------------

			if ( ! $this->request['file_location'] )
			{
				$this->registry->output->global_message = $this->lang->words['cc_nofileimp'];
				$this->themesOverview();
				return;
			}

			if ( ! is_file( DOC_IPS_ROOT_PATH . $this->request['file_location'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['cc_fileat'] . DOC_IPS_ROOT_PATH . $this->request['file_location'];
				$this->themesOverview();
				return;
			}

			if ( preg_match( "#\.gz$#", $this->request['file_location'] ) )
			{
				if ( $FH = @gzopen( DOC_IPS_ROOT_PATH.$this->request['file_location'], 'rb' ) )
				{
					while ( ! @gzeof( $FH ) )
					{
						$content .= @gzread( $FH, 1024 );
					}

					@gzclose( $FH );
				}
			}
			else
			{
				if ( $FH = @fopen( DOC_IPS_ROOT_PATH.$this->request['file_location'], 'rb' ) )
				{
					$content = @fread( $FH, filesize(DOC_IPS_ROOT_PATH.$this->request['file_location']) );
					@fclose( $FH );
				}
			}
		}
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------

			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );

			try
			{
				$content  = $this->registry->adminFunctions->importXml( $tmp_name );
			}
			catch ( Exception $e )
			{
				$this->registry->output->showError( $e->getMessage() ); 
			}

			if ( ! $content )
			{
				$this->registry->output->global_message = $this->lang->words['cc_nofileleftbehind'];
				$this->themesOverview();
				return;
			}
		}

		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'class_xml.php' );/*noLibHook*/

		$xml = new class_xml();

		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------

		$xml->xml_parse_document( $content );

		//-----------------------------------------
		// pArse
		//-----------------------------------------

		$fields = array( 'theme_on', 'theme_css', 'theme_opts', 'theme_name', 'theme_author',
						 'theme_homepage' , 'theme_email'   , 'theme_desc' );

		if ( ! is_array( $xml->xml_array['blogthemeexport']['blogtheme']['theme'][0]  ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------

			$xml->xml_array['blogthemeexport']['blogtheme']['theme'] = array( 0 => $xml->xml_array['blogthemeexport']['blogtheme']['theme'] );
		}

		foreach( $xml->xml_array['blogthemeexport']['blogtheme']['theme'] as $entry )
		{
			$newrow = array();

			foreach( $fields as $f )
			{
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}

			$this->DB->insert( 'blog_themes', $newrow );
		}

		//-----------------------------------------
		// Done...
		//-----------------------------------------

		$this->reCacheThemes();
		
		$this->registry->output->global_message = sprintf( $this->lang->words['cc_insertedsuc'], $newrow['theme_name'] );

		$this->themesOverview();
	}
	
	/**
	 * Recache themes
	 *
	 * @return	@e void
	 */
 	public function reCacheThemes()
 	{
 		$cache = array();
 		
 		$this->DB->build( array( 'select'	=> '*', 'from' => 'blog_themes', 'where' => 'theme_on=1' ) );
 		$this->DB->execute();
 		
 		while( $r = $this->DB->fetch() )
 		{
 			$cache[ $r['theme_id'] ] = $r;
 		}
 		
 		$this->cache->setCache( 'blog_themes', $cache, array( 'array' => 1 ) );
 	}
	
}