<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Postinb Library
 * Last Updated: $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 4 $
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class blogPost
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Contains the generated output
	 *
	 * @var string
	 */
	protected $output = "";
	
	/**
	 * Array of navigation breadcrumbs
	 *
	 * @var array
	 */
	protected $nav = array();
	
	/**
	 * Title of the current page
	 *
	 * @var string
	 */
	protected $page_title = "";
	
	/**
	 * Array of general data for the posting library
	 *
	 * @var array
	 */
	protected $obj = array();
	
	/**
	 * Determines if the logged in user can add polls
	 *
	 * @var bool
	 */
	protected $can_add_poll = 0;
	
	/**
	 * Max number of questsions the logged in user is allowed for polls
	 *
	 * @var integer
	 */
	protected $max_poll_questions = 0;
	
	/**
	 * Max number of choices the logged in user is allower per question for a poll
	 *
	 * @var integer
	 */
	protected $max_poll_choices_per_question = 0;
	
	/**
	 * Determines if the logged in user is allowed to upload
	 *
	 * @var bool
	 */
	protected $can_upload = 0;
	
	/**
	 * Determines if the logged in user is allowed to edit polls
	 *
	 * @var bool
	 */
    protected $can_edit_poll = 0;
    
    /**
     * Total number of votes in the poll
     *
     * @var integer
     */
 	protected $poll_total_votes = 0;
 	
 	/**
 	 * Type of data being posted, ex: comment, entry, cblock
 	 *
 	 * @var string
 	 */
	protected $post_type = "";
	
	/**
	 * ID of the blog being posted to
	 *
	 * @var integer
	 */
	protected $blog_id = 0;
	
	/**
	 * Array of blog data
	 *
	 * @var array
	 */
	protected $blog = array();
	
	/**
     * Show the 'whch blog' drop down
     *
     * @var	bool
     */
    protected $_needsBlogDropDown = false;
    
    /**
     * Are we actively using the 'blog this' system?
     *
     * @var	bool
     */
    protected $_blogThis = false;
	
	/**
	 * Setup registry classes
	 *
	 * @param  ipsRegistry  $registry
	 * @return	@e void
	 * @todo [Future]		Allow user to set a default blog?
	 */
	public function __construct( ipsRegistry $registry, $noSetUp=false )
	{
        /* Make registry objects */
		$this->registry		=  $registry;
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		if ( ! $this->registry->isClassLoaded('blogFunctions') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass('blogFunctions', new $classToLoad($this->registry));
		}

		$this->blogFunctions = $this->registry->getClass('blogFunctions');
		
		/* No set up? Just use methods within */
		if ( ! $noSetUp )
		{
			/* If we don't have a blog id, load one (@note	See todo for this method) */
			if ( empty( $this->request['blogid'] ) )
			{
				$blogs = $this->fetchPostableBlogs();
				
				if ( is_array( $blogs ) AND count( $blogs ) )
				{
					$_blog = array_shift( $blogs );
					
					$this->request['blogid'] = intval( $_blog['blog_id'] );
					$this->blogFunctions->setActiveBlog( intval( $this->request['blogid'] ) );
					
					$this->_needsBlogDropDown = true;
				}
			}
			
			$this->blog    = $this->blogFunctions->getActiveBlog();
			$this->blog_id = intval( $this->blog['blog_id'] );
	    	
	    	if ( empty($this->blog_id) )
	    	{
	    		if ( empty($this->request['btapp']) )
	    		{
	    			$this->registry->output->showError( 'incorrect_use', 106186.1, null, null, 404 );
	    		}
	    		else
	    		{
	    			$this->registry->output->showError( 'bt_must_have_blog', 106186.2, null, null, 403 );
	    		}
	    	}
	    	
	    	/* Get the blog URL */
			$this->settings['blog_url'] = $this->blogFunctions->getBlogUrl( $this->blog_id, $this->blog['blog_seo_name'] );
	
			/* Set the navigation */
			$this->nav[] = array( $this->lang->words['blog_title'], 'app=blog', 'false', 'app=blog' );
		    $this->nav[] = array( $this->blog['blog_name'], "app=blog&module=display&section=blog&blogid={$this->blog['blog_id']}", $this->blog['blog_seo_name'], 'showblog' );
	
			/* Load language files */
			$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
	        $this->lang->loadLanguageFile( array( 'public_post' ), 'forums' );
	    }
		
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
				        
        /* Blog this flag */
        if ( $this->settings['blog_allow_bthis'] AND isset( $this->request['btapp'] ) )
        {
        	$this->_blogThis = true;
        }
    }

	/**
	 * Run the posting function
	 *
	 * @param  string  $action
	 * @return	@e void
	 */
	public function execute( $action )
	{
		//-----------------------------------------
		// Attach?
		//-----------------------------------------
		$this->obj['form_extra']   = '';
		$this->obj['hidden_field'] = '';
		
		if( $this->memberData['g_blog_attach_max'] != -1 )
		{
			$this->can_upload = 1;
			$this->obj['form_extra']   = " enctype='multipart/form-data'";
			$this->obj['hidden_field'] = "<input type='hidden' name='MAX_FILE_SIZE' value='" . ( $this->memberData['g_blog_attach_max'] * 1024 ) . "' />";
		}
		
		//-----------------------------------------
		// Allowed poll?
		//-----------------------------------------
		$this->can_add_poll                  = intval($this->memberData['g_blog_allowpoll']);
		$this->max_poll_choices_per_question = intval($this->settings['max_poll_choices']);
		$this->max_poll_questions            = intval($this->settings['max_poll_questions']);
		$this->can_edit_poll                 = $this->blogFunctions->allowEditEntry( $this->blog );
		
		if ( ! $this->max_poll_questions )
		{
			$this->can_add_poll = 0;
		}
		
		//-----------------------------------------
		// Did the user press the "preview" button?
		//-----------------------------------------
		$this->obj['preview_post'] = isset( $this->request['preview'] ) ? $this->request['preview'] : 0;
		
		//-----------------------------------------
		// Make a action lookup table
		//-----------------------------------------
		$this->obj['action_codes'] = array(  'showform'	 	 => array( '0'  , 'new_entry'	, 'entry'	),
											 'dopost' 	 	 => array( '1'  , 'new_entry'	, 'entry'	),
											 'editentry'	 => array( '0'  , 'edit_entry'  , 'entry'	),
											 'doeditentry'	 => array( '1'  , 'edit_entry'	, 'entry'	),
											 'addcblock'	 => array( '0'	, 'add_cblock'	, 'cblock'	),
											 'doaddcblock'	 => array( '1'	, 'add_cblock'	, 'cblock'	),
											 'editcblock'	 => array( '0'	, 'edit_cblock'	, 'cblock'	),
											 'doeditcblock'	 => array( '1'	, 'edit_cblock'	, 'cblock'	),
										   );
		
		//-----------------------------------------
		// Make sure our input CODE element is legal.
		//-----------------------------------------
		
		if(! isset( $this->obj['action_codes'][ $action ] ) )
		{
			$this->registry->output->showError( 'missing_files', 106187 );
		}
		
		$this->post_type = $this->obj['action_codes'][ $action ][2];
		
		//-----------------------------------------
		// Are we allowed to post at all?
		//-----------------------------------------
		
		if( $this->memberData['member_id'] )
		{
			if( $this->memberData['restrict_post'] )
			{
				if( $this->memberData['restrict_post'] == 1 )
				{
					$this->registry->output->showError( 'posting_off', 106188, null, null, 403 );
				}
		
				$post_arr = IPSMember::processBanEntry( $this->memberData['restrict_post'] );
		
				if( time() >= $post_arr['date_end'] )
				{
					//-----------------------------------------
					// Update this member's profile
					//-----------------------------------------
		
					$this->DB->update( 'members', array( 'restrict_post' => 0 ), 'member_id=' . intval( $this->memberData['member_id'] ) );
				}
				else
				{
					$this->registry->output->showError( array( 'posting_off_susp', $this->registry->class_localization->getDate($post_arr['date_end'], 'LONG' ) ), 106189, null, null, 403 );
				}
			}
		
			//-----------------------------------------
			// Flood check..
			//-----------------------------------------
		
			if( $action == "showreplyform" || $action == "docomment" )
			{
				if( $this->settings['flood_control'] > 0 )
				{
					if( $this->memberData['g_avoid_flood'] != 1 )
					{
						if( time() - $this->memberData['last_post'] < $this->settings['flood_control'] )
						{
							$this->registry->output->showError( array( 'flood_control', $this->settings['flood_control'] ), 106190, null, null, 403 );
						}
					}
				}
			}
		}
		elseif( $this->member->is_not_human )
		{
			$this->registry->output->showError( 'posting_off', 106191, null, null, 403 );
		}
		
		//-----------------------------------------
		// If the first CODE array bit is set to "0" - show the relevant form.
		// If it's set to "1" process the input.
		//
		// We pass a reference to this classes object so we can manipulate this classes
		// data from our sub class.
		//-----------------------------------------
		
		if( $this->obj['action_codes'][ $action ][0] )
		{
			//-----------------------------------------
			// Make sure we have a valid auth key
			//-----------------------------------------
		
			if( $this->request['auth_key'] != $this->member->form_hash )
			{
				$this->registry->output->showError( 'del_post', 20610, null, null, 403 );
			}
		
			//-----------------------------------------
			// Make sure we have a "Guest" Name..
			//-----------------------------------------
		
			if( ! $this->memberData['member_id'] )
			{
		
				$this->request['UserName'] = trim( $this->request['UserName'] );
				$this->request['UserName'] = str_replace( "<br>", "", $this->request['UserName'] );
				$this->request['UserName'] = $this->request['UserName'] ? $this->request['UserName'] : $this->lang->words['global_guestname'];
		
				if( $this->request['UserName'] != $this->lang->words['global_guestname'] )
				{
					$this->DB->build( array( 
													'select' => 'member_id, name, members_display_name, members_created_remote, email, member_group_id, member_login_key, ip_address, login_anonymous',
													'from'   => 'members',
													'where'  => "members_l_username='" . trim( strtolower ($this->request['UserName'] ) ) . "'"
											)	);
					$this->DB->execute();
		
					if( $this->DB->getTotalRows() )
					{
						$this->request['UserName'] = $this->settings['guest_name_pre'] . $this->request['UserName'] . $this->settings['guest_name_suf'];
					}
				}
			}
		
			$this->process();
		}
		else
		{
			$this->showForm();
		}
	}

	/**
	 * Process the post, placeholder, is overwritten in posttype libs
	 *
	 * @return	@e void
	 */
	public function process()
	{
	}

	/**
	 * Show the post form, placeholder, is overwritten in posttype libs
	 *
	 * @return	@e void
	 */
	public function showForm()
	{
	}
	
	/**
	 * Set blog this database
	 *
	 * @param	int			Entry ID
	 * @param	string		App
	 * @param	int			ID 1
	 * @param	int			ID 2
	 * @return	int			blog_this.bt_id
	 */
	public function setBlogThis( $entry_id, $app, $id1, $id2 )
	{
		$entry_id = intval($entry_id);
		$app      = trim($app);
		$id1      = intval($id1);
		$id2      = intval($id2);
		
		if ( $entry_id && $app && $id1 )
		{
			$this->DB->insert( 'blog_this', array( 'bt_entry_id' => $entry_id,
												   'bt_app'      => $app,
												   'bt_id1'      => $id1,
												   'bt_id2'		 => $id2
							  )					  );
			
			return $this->DB->getInsertId();
		}
		
		return false;
	}
	
	/**
	 * Fetch blogs one is allowed to start a new entry in
	 *
	 * @param	array	MemberData (will use $this->memberData) if null
	 * @return  array	Basic blog data by ID => data
	 */
	public function fetchPostableBlogs( $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : ( ( $member === null ) ? $this->memberData : IPSMember::load( intval( $member ), 'all' ) );
		
		$_blogs  = $this->blogFunctions->fetchMyBlogs( $memberData );
		$_return = array();
		
		if ( is_array( $_blogs ) )
		{
			foreach( $_blogs as $id => $data )
			{
				/* Blog this? */
				if ( $this->_blogThis )
				{
					if ( stristr( $data['blog_view_level'], 'private' ) )
					{
						continue;
					}
				}
				
				if ( $data['_canPostIn'] )
				{
					$_return[ $id ] = $data;
				}
			}
		} 
		
		return $_return;
	}
	
	/**
	 * Sort categories from post and store in the DB
	 *
	 * @param	int		Blog ID
	 * @param	int		Entry ID
	 * @param	bool	Rebuild blog cache?
	 * @return	array	[Array of id => cat name]
	 */
	public function processCategories( $blogId, $entryId, $status='published', $rebuild=true )
	{
		/* INIT */
		$categories = array();
		$catsToAdd	= array();
		$isDraft    = ( $status == 'published' ) ? 0 : 1;
		
		/* Grab what ya can */
		if ( is_array($_POST) AND count($_POST) )
		{
			/* Remove old mappings */
			$this->DB->delete( 'blog_category_mapping', 'map_blog_id=' . intval( $blogId ) . ' AND map_entry_id=' . intval( $entryId ) );
			
			/* OK */
			if ( is_array( $this->request['catCheckBoxes'] ) and count( $this->request['catCheckBoxes'] ) )
			{
				foreach( $this->request['catCheckBoxes'] as $id => $value )
				{
					if ( $value )
					{
						/* This a new one? */
						if ( strstr( $id, 'catNew-' ) )
						{
							$name = IPSText::truncate( trim( $this->request['catNames'][ $id ] ), 32 );
							
							if ( $name )
							{
								$this->DB->insert( 'blog_categories', array( 'category_blog_id'   => $blogId,
																			 'category_title'     => $name,
																			 'category_title_seo' => IPSText::makeSeoTitle( $name ) ) );
																			 
								$catId = intval( $this->DB->getInsertId() );
																			 
								$this->DB->insert( 'blog_category_mapping', array( 'map_category_id' => $catId,
																				   'map_entry_id'    => $entryId,
																				   'map_is_draft'    => $isDraft,
																				   'map_blog_id'     => $blogId ) );
								
								$categories[ $catId ] = $name;
							}
						}
						else
						{	
							$id = intval($id);
							
							if ( empty($id) )
							{
								$categories[0] = $name;
								
								$this->DB->insert( 'blog_category_mapping', array( 'map_category_id' => 0,
																				   'map_entry_id'    => $entryId,
																				   'map_is_draft'    => $isDraft,
																				   'map_blog_id'     => $blogId ) );
							}
							else
							{
								$categories[ $id ] = $name;
								
								$this->DB->insert( 'blog_category_mapping', array( 'map_category_id' => $id,
																				   'map_entry_id'    => $entryId,
																				   'map_is_draft'    => $isDraft,
																				   'map_blog_id'     => $blogId ) );
							}
						}
					}
				}
			}
			
			/* Added nothing? Best add uncategorized, then */
			if ( ! count( $categories ) )
			{
				$this->DB->insert( 'blog_category_mapping', array( 'map_category_id' => 0,
																   'map_entry_id'    => $entryId,
																   'map_is_draft'    => $isDraft,
																   'map_blog_id'     => $blogId ) );
				
				$categories[0] = $name;
			}
		}
		
		if ( $rebuild )
		{
			$this->blogFunctions->categoriesRecacheForBlog( $blogId );
		}
		
		$this->blogFunctions->categoriesRecacheForEntry( $blogId, $entryId );
		
		return $categories;
	}
		
	/**
	 * Show post preview
	 *
	 * @param  string  $t
	 * @return string
	 */
    public function showPostPreview( $t="" )
    {
    	IPSText::getTextClass('bbcode')->parse_html				= (intval($this->request['post_htmlstatus']) AND $this->memberData['g_blog_do_html']) ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br			= $this->request['post_htmlstatus'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_smilies			= $this->request['enableemo'] ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'blog_' . $this->post_type;
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
    	
		return IPSText::getTextClass('bbcode')->preDisplayParse( $t );
    }

	/**
	 * HTML: name fields
	 * Returns the HTML for either text inputs or membername
	 * depending if the member is a guest.
	 *
	 * @return	string
	 */
	public function htmlNameField() 
	{
		if ( ! $this->memberData['member_id'] AND $this->settings['guest_captcha'] AND $this->settings['bot_antispam_type'] != 'none' )
		{
			$captchaHTML = $this->registry->getClass('class_captcha')->getTemplate();

			return $captchaHTML;
		}
	}
	
	/**
	 * HTML: Date Fields
	 * Returns the HTML for editing the date of an entry
	 *
	 * @param  timestamp  $curr_date
	 * @return string
	 */
	public function htmlDateField( $curr_date='' )
	{
		/* INI */
		$curr_date = $curr_date ? $curr_date : time();
		$_days     = '';
		$_months   = '';
		$_years    = '';
		$_year     = gmdate('Y');
		
		/* Defaults */
		$_defaults = explode( '/', gmstrftime( "%m/%d/%Y/%H/%M", $curr_date + $this->registry->class_localization->getTimeOffset() ) );		
		
		/* GOT POST? */
		if ( isset( $_POST['entry_day'] ) )
		{
			$_defaults[0] = $_POST['entry_month'];
			$_defaults[1] = $_POST['entry_day'];
			$_defaults[2] = $_POST['entry_year'];
			
			list($_defaults[3], $_defaults[4]) = explode( ':', $_POST['entry_time'] );
		}
		
		/* Build day dropdown */ 
		for( $i = 1; $i < 32; $i++ )
		{
			$sel    = $_defaults[1] == $i ? ' selected=\'selected\'' : '';
			$_days .= "<option value='{$i}'{$sel}>{$i}</option>";
		}
		
		/* Build month dropdown */
		for( $i = 1; $i < 13; $i++ )
		{
			$sel      = $_defaults[0] == $i ? ' selected=\'selected\'' : '';
			$_months .= "<option value='{$i}'{$sel}>{$this->lang->words['M_'.$i]}</option>";		
		}
		
		/* Build year dropdown */
		for( $i = 2004; $i <= $_year + 1; $i++ )
		{
			$sel     = $_defaults[2] == $i ? ' selected=\'selected\'' : '';
			$_years .= "<option value='{$i}'{$sel}>{$i}</option>";					
		}
		
		/* Return HTML */
		return array( 'days' => $_days, 'months' => $_months, 'years' => $_years, 'hour' => $_defaults[3], 'minute' => $_defaults[4] );
	}

	/**
	 * HTML: Album Fields
	 * Returns the HTML for adding an album to the entry
	 * depending if the gallery is installed and there are albums.
	 *
	 * @param  integer  $album_id
	 * @return string
	 * 
	 * @todo	Remove the gallery 3 code for the IPB 3.2 release of gallery
	 */
	public function htmlAlbumField( $album_id = 0 ) 
	{
		/* Init vars */
		$return = array();
		
		/* Gallery enabled? */
		if( IPSLib::appIsInstalled('gallery') )
		{
			/* Get main library */
			if ( !ipsRegistry::isClassLoaded('gallery') )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
				$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
			}
			
			$return['canCreate'] = $this->registry->gallery->helper('albums')->canCreate();
			
			$albums = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'album_owner_id' => $this->blog['member_id'], 'isViewable' => 1, 'album_is_global' => 0 ) );
			
			/* Got any albums? */
			if( is_array($albums) && count($albums) )
			{
				$return['dropdown']  = "<select id='entry_gallery_album' name='entry_gallery_album' class='input_select' tabindex='3'>\n";
				$return['dropdown'] .= "<option value='0'>{$this->lang->words['entry_cat_none']}</option>\n";
				
				foreach( $albums as $_id => $_data )
				{
					$return['dropdown'] .= "<option value='{$_id}' ";
					$return['dropdown'] .= ( $_id == $album_id ) ? "selected='selected'" : '';
					$return['dropdown'] .= ">{$_data['album_name']}</option>\n";
				}
				
				$return['dropdown'] .= "</select>";
			}
		}
		
		return $return;
	}

	/**
	 * HTML: Post Body
	 * Returns the HTML for post area, code buttons and post icons
	 *
	 * @param  string  $raw_post
	 * @return string
	 */
	public function htmlPostBody( $raw_post="", $autoSaveKey='' ) 
	{	
		$editorSection = 'blog_entry';
		
		switch( $this->post_type )
		{
			case 'entry':
				$this->lang->words['the_max_length'] = $this->settings['blog_max_entry_length'] * 1024;
			break;
			
			case 'cblock':
				$editorSection = 'blog_cblock';
				$this->lang->words['the_max_length'] = $this->settings['blog_max_cblock_length'] * 1024;
			break;
			
			default:
	   			$this->registry->output->showError( 'incorrect_use', 106192 );
	   		break;
		}
		
		$this->editor->setContent( $raw_post, $editorSection );
				
		return $this->editor->show( 'Post', array( 'autoSaveKey' => $autoSaveKey )  );
	}

	/**
	 * HTML: mod_options
	 * Returns the HTML for hte mod options drop down box
	 *
	 * @param  string  $entry_status
	 * @return string
	 */
	public function modOptions( $entry_status="" )
	{
		if( ! $this->blogFunctions->allowPublish( $this->blog ) )
		{
			$entry_status = 'draft';
		}
		elseif( $entry_status == "" )
		{
			if( !empty( $this->blog['blog_settings']['defaultstatus'] ) )
			{
				$entry_status = $this->blog['blog_settings']['defaultstatus'] == 'published' ? 'published' : 'draft';
			}
			else
			{
				$entry_status = ( isset( $this->settings['blog_entry_defaultstatus'] ) && $this->settings['blog_entry_defaultstatus'] == 'published' ) ? 'published' : 'draft';
			}
		}
		
		if( $this->blogFunctions->allowPublish( $this->blog ) )
		{
			if( $entry_status == "published" )
			{
				$pubsel   = " selected='selected'";
				$draftsel = "";
			}
			else
			{
				$draftsel = " selected='selected'";
				$pubsel   = "";
			}

			$html  = "<select name='mod_options' id='bfs_modOptions' class='forminput'>\n";
			$html .= "<option value='published' id='bfs_pub' {$pubsel}>{$this->lang->words['published']}</option>\n";
			$html .= "<option value='draft' id='bfs_draft' {$draftsel}>{$this->lang->words['draft']}</option>\n";

			return $html;
		}
		else
		{
			return "{$this->lang->words['draft']} <input type='hidden' name='mod_options' value='{$entry_status}'>\n";
		}
	}

	/**
	 * HTML: checkboxes
	 * Returns the HTML for sign/emo/track boxes
	 *
	 * @return string
	 */
	public function htmlCheckBoxes()
	{
		$html_status_array = array();
		
		if( $this->memberData['g_blog_do_html'] )
		{
			$this->request['post_htmlstatus'] = isset( $this->request['post_htmlstatus'] ) ? $this->request['post_htmlstatus'] : 0;
			$html_status_array = array( 0 => ( $this->request['post_htmlstatus'] == 0 ? ' selected="selected"' : '' ),
										1 => ( $this->request['post_htmlstatus'] == 1 ? ' selected="selected"' : '' ),
										2 => ( $this->request['post_htmlstatus'] == 2 ? ' selected="selected"' : '' )
										);
		}
		
		return $html_status_array;
	}

	/**
	 * HTML: Build Upload Area - yay
	 *
	 * @param  string   $post_key
	 * @param  string   $type
	 * @param  integer  $pid
	 * @return string
	 */
	public function htmlBuildUploads( $post_key="", $type="blogentry", $pid=0)
	{
		/* Upload Stats */
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type				= $type;
		$class_attach->attach_post_key	= $post_key;
		$class_attach->init();
		$class_attach->getUploadFormSettings();

		$upload_field = $this->registry->output->getTemplate( 'post' )->uploadForm( $post_key, $type, $class_attach->attach_stats, $pid, $this->blog['blog_id'] );
		
		return $upload_field;
	}

	/**
	 * Compile poll
	 *
	 * @return array
	 */
    public function compilePoll()
    {
    	//-----------------------------------------
		// Check poll
		//-----------------------------------------

		$questions		= array();
		$choices_count	= 0;
		$is_mod        = $this->memberData['g_is_supmod'] ? $this->memberData['g_is_supmod'] : intval( $this->memberData['_blogmod']['moderate_can_edit_entry'] );
				
		if ( $this->can_add_poll )
		{
			if ( isset($_POST['question']) AND is_array( $_POST['question'] ) and count( $_POST['question'] ) )
			{
				foreach( $_POST['question'] as $id => $q )
				{
					if ( ! $q OR ! $id )
					{
						continue;
					}
					
					$questions[ $id ]['question'] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::parseCleanValue( IPSText::stripAttachTag( $q ) ) ), 255 );
				}
			}
			
			if ( isset($_POST['multi']) AND is_array( $_POST['multi'] ) and count( $_POST['multi'] ) )
			{
				foreach( $_POST['multi'] as $id => $q )
				{
					if ( ! $q OR ! $id )
					{
						continue;
					}
					
					$questions[ $id ]['multi'] = intval($q);
				}
			}			
			
			//-----------------------------------------
			// Choices...
			//-----------------------------------------
			
			if ( isset($_POST['choice']) AND is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $mainid => $choice )
				{
					if ( trim( $choice ) == '' )
					{
						continue;
					}

					list( $question_id, $choice_id ) = explode( "_", $mainid );
					
					$question_id = intval( $question_id );
					$choice_id   = intval( $choice_id );
					
					if ( ! $question_id OR ! isset($choice_id) )
					{
						continue;
					}
					
					if ( ! $questions[ $question_id ]['question'] )
					{
						continue;
					}
					
					$questions[ $question_id ]['choice'][ $choice_id ] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::parseCleanValue( IPSText::stripAttachTag( $choice ) ) ), 255 );
					
					if( ! $is_mod )
					{
						$questions[ $question_id ]['votes'][ $choice_id ]  = intval($this->poll_answers[ $question_id ]['votes'][ $choice_id ]);
					}
					else
					{
						$_POST['votes'] = isset($_POST['votes']) ? $_POST['votes'] : 0;
						
						$questions[ $question_id ]['votes'][ $choice_id ]  = intval( $_POST['votes'][ $question_id.'_'.$choice_id ] );
					}
					
					$this->poll_total_votes += $questions[ $question_id ]['votes'][ $choice_id ];
				}
			}
			
			//-----------------------------------------
			// Make sure we have choices for each
			//-----------------------------------------
			
			foreach( $questions as $id => $data )
			{
				if ( ! is_array( $data['choice'] ) OR ! count( $data['choice'] ) )
				{
					unset( $questions[ $id ] );
				}
				else
				{
					$choices_count += intval( count( $data['choice'] ) );
				}
			}
			
			//-----------------------------------------
			// Error check...
			//-----------------------------------------
			
			if ( count( $questions ) > $this->max_poll_questions )
			{
				$this->obj['post_errors'] = 'poll_to_many';
			}
			
			if ( count( $choices_count ) > ( $this->max_poll_questions * $this->max_poll_choices_per_question ) )
			{
				$this->obj['post_errors'] = 'poll_to_many';
			}
		}

		return $questions;
    }
    
	/**
	 * Compile Post
	 * Compiles all the incoming information into an array
	 * which is returned to the accessor
	 *
	 * @return array
	 */
	public function compilePost()
	{
		/* Max Length */
		$this->settings['blog_max_entry_length'] = $this->settings['blog_max_entry_length'] ? $this->settings['blog_max_entry_length'] : 2140000;

		/* Enable EMoticons? */
		$this->request['enableemo'] = $this->request['enableemo'] == 'yes' ? 1 : 0;
		
		/* Check the entry */
		if( IPSText::mbstrlen( trim( IPSText::br2nl( $_POST['Post'] ) ) ) < 1 )
		{
			if( ! $_POST['preview'] )
			{
				$this->obj['post_errors'] = 'no_post';
				return ;
			}
		}

		if( IPSText::mbstrlen( $_POST['Post'] ) > ( $this->settings['blog_max_entry_length'] * 1024 ) )
		{
			$this->obj['post_errors'] = 'post_too_long';
			return;
		}
		
		if( IPSText::mbstrlen( $this->request['EntryTitle'] ) > ( $this->settings['blog_max_entrytitle'] ) )
		{
			$this->obj['post_errors'] = 'entry_title_too_long';
			return ;
		}
		
		/* Check the title */
		$this->request['EntryTitle'] = $this->pfCleanTopicTitle( $_POST['EntryTitle'] );
		$this->request['EntryTitle'] = IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->request['EntryTitle'] );

		/* Process the entry */
		$this->editor->setAllowHtml( ( $this->memberData['g_blog_do_html'] and $this->request['post_htmlstatus'] ) ? 1 : 0 );
		
		$this->request['Post'] = $this->editor->process( $_POST['Post'] );

		IPSText::getTextClass('bbcode')->parse_smilies			= $this->request['enableemo'];
		IPSText::getTextClass('bbcode')->parse_html				= ( $this->memberData['g_blog_do_html'] and $this->request['post_htmlstatus'] ) ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br			= $this->request['post_htmlstatus'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'blog_entry';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];		

		/* Entry Date */
		$entry_time = explode( ':', $this->request['entry_time'] );
		$dte        = $this->request['entry_day'] ? ( gmmktime( 
								intval( $entry_time[0] ),
								intval( $entry_time[1] ),
								0,
								intval( $this->request['entry_month'] ),
								intval( $this->request['entry_day'] ),
								intval( $this->request['entry_year'] )
							) - $this->registry->class_localization->getTimeOffset() ) : time();

		if ( $dte > time() )
		{
			/* Enforce a draft */
			$this->request['mod_options'] = 'draft';
		}

		/* Build the entry array */
		$post = array(  'blog_id'			  => $this->blog_id,
						'entry_author_id'	  => $this->memberData['member_id'],
						'entry_author_name'	  => $this->memberData['members_display_name'],
						'entry_date'		  => $dte,
						'entry_name'		  => $this->request['EntryTitle'],
						'entry_name_seo'	  => IPSText::makeSeoTitle($this->request['EntryTitle']),
						'entry'     		  => IPSText::getTextClass('bbcode')->preDbParse( $this->request['Post'] ),
						'entry_short'		  => '', # Leave blank to rebuild properly later once blog/entry is viewed
						'entry_status'		  => ( $this->request['mod_options'] == 'published' ? 'published' : 'draft' ),
						'entry_post_key'	  => $this->request['post_key'],
						'entry_html_state'	  => intval( $this->request['post_htmlstatus'] ),
						'entry_use_emo'		  => $this->request['enableemo'],
						'entry_last_update'	  => $dte,
						'entry_gallery_album' => intval( $this->request['entry_gallery_album'] ),
						'entry_future_date'   => ( $dte > time() ) ? 1 : 0,
						'entry_poll_state'    => ( count( $this->poll_questions ) AND $this->can_add_poll ) ? 1 : 0,
					 );
		
		$testParse	= IPSText::getTextClass('bbcode')->preDisplayParse( $this->request['Post'] );
		
		/* Assign Errors */
		if( IPSText::getTextClass('bbcode')->error )
		{
	    	$this->obj['post_errors'] = IPSText::getTextClass('bbcode')->error;
		}
		if ( IPSText::getTextClass('bbcode')->warning )
		{
			$this->obj['post_errors'] = IPSText::getTextClass('bbcode')->warning;
		}

		return $post;
	}
	
	/**
	 * Compile Post
	 * Compiles all the incoming information into an array
	 * which is returned to the accessor
	 *
	 * @return array
	 */
	public function compileCBlock()
	{
		/* Max Length */
		$this->settings['blog_max_cblock_length'] = $this->settings['blog_max_cblock_length'] ? $this->settings['blog_max_cblock_length'] : 2140000;

		/* Enable Sig & Emoticons? */
		$this->request['enablesig'] = $this->request['enablesig'] == 'yes' ? 1 : 0;
		$this->request['enableemo'] = $this->request['enableemo'] == 'yes' ? 1 : 0;

		/* Check the post */
		if( IPSText::mbstrlen( trim( $_POST['Post'] ) ) < 1)
		{
			if( ! $_POST['preview'] )
			{
				$this->registry->output->showError( 'no_post', 106199 );
			}
		}

		if( IPSText::mbstrlen( $_POST['Post'] ) > ( $this->settings['blog_max_cblock_length'] * 1024 ) )
		{
			$this->registry->output->showError( 'post_too_long', 106200 );
		}
		
		/* Check the title */
		$this->request['CBlockTitle'] = $this->pfCleanTopicTitle( $_POST['CBlockTitle'] );
		$this->request['CBlockTitle'] = IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->request['CBlockTitle'] );

		/* Process the posted data */
		$this->editor->setAllowHtml( intval( $this->memberData['g_blog_do_html'] ) );
		
		$this->request['Post'] = $this->editor->process( $_POST['Post'] );

		IPSText::getTextClass( 'parser' )->parse_html		= intval( $this->memberData['g_blog_do_html'] );
		IPSText::getTextClass( 'parser' )->parse_smilies	= 1;//$this->request['enableemo'];
		IPSText::getTextClass( 'parser' )->parse_bbcode		= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'blog_cblock';
		
		/* Build the cblock entry array */
		$post = array(
						'cbcus_name'		=> $this->request['CBlockTitle'],
						'cbcus'     		=> IPSText::getTextClass( 'bbcode' )->preDbParse( $this->request['Post'] ),
						'cbcus_post_key'	=> $this->request['post_key'],
						'cbcus_html_state'	=> intval( $this->request['post_htmlstatus'] )
					 );
					 
		/* Assign errors */
	    $this->obj['post_errors'] = IPSText::getTextClass( 'bbcode' )->error;

		return $post;
	}

	/**
	 * Clean topic title
	 *
	 * @param  string  $title
	 * @return string
	 */
	public function pfCleanTopicTitle($title="")
	{
		if( $this->settings['etfilter_shout'] )
		{
			if( function_exists('mb_convert_case') )
			{
				if( in_array( strtolower( $this->settings['gb_char_set'] ), array_map( 'strtolower', mb_list_encodings() ) ) )
				{
					$title = mb_convert_case( $title, MB_CASE_TITLE, $this->settings['gb_char_set'] );
				}
				else
				{
					$title = ucwords( strtolower($title) );
				}
			}
			else
			{
				$title = ucwords( strtolower($title) );
			}
		}
		
		$title = IPSText::parseCleanValue( $title );
		
		if( $this->settings['etfilter_punct'] )
		{
			$title	= preg_replace( "/\?{1,}/"      , "?"    , $title );		
			$title	= preg_replace( "/(&#33;){1,}/" , "&#33;", $title );
		}

		//-----------------------------------------
		// The DB column is 250 chars, so we need to do true mb_strcut, then fix broken HTML entities
		// This should be fine, as DB would do it regardless (cept we can fix the entities)
		//-----------------------------------------

		$title = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', IPSText::mbsubstr( $title, 0, 250 ) );
		
		$title = IPSText::stripAttachTag( $title );
		$title = str_replace( "<br />", "", $title  );
		$title = trim( $title );
		
		//$title = IPSText::getTextClass( 'bbcode' )->stripBadWords( $title );

		return $title;
	}

	/**
	 * Convert temp uploads into permanent ones! YAY
	 *
	 * @param  string   $post_key
	 * @param  string   $rel_id
	 * @param  string   $rel_module
	 * @param  araray   $args
	 * @return integer
	 */
	public function pfMakeAttachmentsPermanent( $post_key="", $rel_id="", $rel_module="", $args=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return = array( 'count' => 0 );
		
		//-----------------------------------------
		// Attachments: Re-affirm...
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach                  = new $classToLoad( $this->registry );
		$class_attach->type            = $rel_module;
		$class_attach->attach_post_key = $post_key;
		$class_attach->attach_rel_id   = $rel_id;
		$class_attach->init();
		
		$return = $class_attach->postProcessUpload( $args );

		return intval( $return['count'] );
	}
	
	/**
	 * Check for queued comments
	 *
	 * @return bool
	 */
	public function checkCommentQueued()
	{
		// Do we need to queue this comment?
		if( $this->blog['blog_settings']['approvemode'] == 'all' )
		{
			if( $this->blogFunctions->allowApprove( $this->blog ) )
			{
				$queued = 0;
			}
			else
			{
				$queued = 1;
			}
		}
		elseif( $this->blog['blog_settings']['approvemode'] == 'guests' && $this->memberData['member_id'] == 0 )
		{
			$queued = 1;
		}
		else
		{
			$queued = 0;
		}

		return $queued;
	}
	
	/**
	 * Generates the poll box
	 *
	 * @author	Terabyte
	 * @param	string		$formType		Form type (new/edit/reply)
	 * @return	@e string	HTML
	 */
	protected function _generatePollBox( $formType )
	{
		if ( $this->can_add_poll )
		{
			//-----------------------------------------
			// Did someone hit preview / do we have
			// post info?
			//-----------------------------------------
			
			$poll_questions = array();
			$poll_question	= "";
			$poll_choices   = array();
			$show_open      = 0;
			$is_mod         = 0;
			$poll_votes		= array();
			$poll_multi		= array();			
			
			if ( isset($_POST['question']) AND is_array( $_POST['question'] ) and count( $_POST['question'] ) )
			{
				foreach( $_POST['question'] as $id => $question )
				{
					$poll_questions[$id] = IPSText::parseCleanValue( $question );
				}
				
				$poll_question = ipsRegistry::$request['poll_question'];
				$show_open     = 1;
			}
			
			if ( isset($_POST['multi']) AND is_array( $_POST['multi'] ) and count( $_POST['multi'] ) )
			{
				foreach( $_POST['multi'] as $id => $checked )
				{
					$poll_multi[ $id ] = $checked;
				}
			}			
			
			if ( isset($_POST['choice']) AND is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $id => $choice )
				{
					$poll_choices[ $id ] = IPSText::parseCleanValue( $choice );
				}
			}
			
			if ( $formType == 'edit' )
			{
				if ( isset( $_POST['votes'] ) && is_array( $_POST['votes'] ) and count( $_POST['votes'] ) )
				{
					foreach( $_POST['votes'] as $id => $vote )
					{
						$poll_votes[ $id ] = $vote;
					}
				}
			}
			
			if ( $formType == 'edit' AND ( ! isset($_POST['question']) OR ! is_array( $_POST['question'] ) OR ! count( $_POST['question'] ) ) )
			{
				//-----------------------------------------
				// Load the poll from the DB
				//-----------------------------------------
				
				$this->poll_data    = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_polls', 'where' => 'entry_id=' . $this->org_entry['entry_id'] ) );
				
				$this->poll_answers = unserialize(stripslashes($this->poll_data['choices']));
				
				if( !is_array($this->poll_answers) OR !count($this->poll_answers) )
				{
					$this->poll_answers = unserialize( preg_replace( '!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", stripslashes( $this->poll_data['choices'] ) ) );
				}
				if ( !is_array($this->poll_answers) OR !count($this->poll_answers) )
				{
					$this->poll_answers = array();
				}

        		//-----------------------------------------
        		// Lezz go
        		//-----------------------------------------
        		
        		foreach( $this->poll_answers as $question_id => $data )
        		{
        			if( !$data['question'] OR !is_array($data['choice']) )
        			{
        				continue;
        			}
        			
        			$poll_questions[ $question_id ] = $data['question'];
        			$poll_multi[ $question_id ]     = isset($data['multi']) ? intval($data['multi']) : 0;
        			
        			foreach( $data['choice'] as $choice_id => $text )
					{
						$poll_choices[ $question_id . '_' . $choice_id ] = stripslashes( $text );
						$poll_votes[ $question_id . '_' . $choice_id ]   = intval($data['votes'][ $choice_id ]);
					}
				}
				
				$poll_question = $this->poll_data['poll_question'];
				$show_open     = $this->poll_data['choices'] ? 1 : 0;
				$is_mod        = $this->can_edit_poll;
			}
			
			/* Overwrite link */
			$this->lang->words['poll_manage_link'] = $this->lang->words['blog_poll_manage_link'];
			
			/* Poll only and public are NOT supported in blog yet.. */
			$this->settings['poll_allow_public'] = $this->settings['ipb_poll_only'] = 0;
			
			return $this->registry->getClass('output')->getTemplate('post')->pollBox( array('max_poll_questions'	=> $this->max_poll_questions, 
																							'max_poll_choices'		=> $this->max_poll_choices_per_question, 
																							'poll_questions'		=> IPSText::simpleJsonEncode( $poll_questions ), 
																							'poll_choices'			=> IPSText::simpleJsonEncode( $poll_choices ), 
																							'poll_votes'			=> IPSText::simpleJsonEncode( $poll_votes ), 
																							'show_open'				=> $show_open, 
																							'poll_question'			=> $poll_question, 
																							'is_mod'				=> $is_mod, 
																							'poll_multi'			=> json_encode( $poll_multi ), 
																							'poll_only'				=> 0, 
																							'poll_view_voters'		=> 0, 
																							'poll_votes'			=> intval( $this->poll_data['votes'] ),
																							'poll_data'				=> $this->poll_data,
																							'poll_answers'			=> $this->poll_answers 
																					)		);
		}
		
		return '';
	}
}
