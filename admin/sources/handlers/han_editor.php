<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Editor Library: Gateway
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class hanEditor
{
	/**
	 * Editor reference
	 *
	 * @var		object
	 */
	public  $class_editor;
	
	/**
	 * Editing method (rte|std)
	 *
	 * @var		string
	 */
	public $method			= '';

	/**
	 * RTE enabled?
	 *
	 * @var		boolean
	 */
	public $rte_on			= false;
		
	/**
	 * Images directory (for acp)
	 *
	 * @var		string
	 */
	public $image_dir		= '';

	/**
	 * Emoticons directory (for acp)
	 *
	 * @var		string
	 */
	public $emo_dir			= '';
	
	/**
	 * Current editor id
	 *
	 * @var		string
	 */
	public $editor_id		= 'ed-0';
	
	/**
	 * Remove emoticons from editor
	 *
	 * @var		boolean
	 */
	public $remove_emoticons	= false;
	
	/**
	 * ACP editor id
	 *
	 * @var		integer
	 */
	public $acp_editor_id	= 0;
	
	/**#@+
	* Registry objects
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
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry   = $registry;
		$this->DB	      = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang	      = $this->registry->getClass('class_localization');
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}	
	
	/**
	 * Init method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function init()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$class = "";
    	
    	if ( ! $this->method )
    	{
    		$this->method = 'rte';
    	}

    	//-----------------------------------------
    	// Make sure we haven't had any messin'
    	//-----------------------------------------
    	
		if ( isset( $_POST['editor_ids'] ) AND is_array( $_POST['editor_ids'] ) )
		{
			foreach( $_POST['editor_ids'] as $k => $v )
			{
				if ( isset($_POST[ $v . '_wysiwyg_used']) AND intval($_POST[ $v . '_wysiwyg_used']) == 1)
				{
					$this->method = 'rte';
				}
				else
				{
					$this->method = 'std';
				}
			}
		}
		
    	if ( isset($_POST['ed-0_wysiwyg_used']) AND intval($_POST['ed-0_wysiwyg_used']) == 1 )
    	{
    		$this->method = 'rte';
    	}
    
    	//-----------------------------------------
    	// Force STD editor.. if needed
    	//-----------------------------------------
    	
    	if ( ( isset($_POST['std_used']) AND intval($_POST['std_used']) ) )
    	{
    		$this->method = 'std';
    	}

    	//-----------------------------------------
    	// Which class
    	//-----------------------------------------
    	
    	switch( $this->method )
    	{
    		case 'rte':
    			$class        = 'class_editor_rte.php';
    			$this->rte_on = 1;
    			break;
    		case 'std':
    			$class 	      = 'class_editor_std.php';
    			$this->rte_on = 0;
    			break;
    		default:
    			$class 		  = 'class_editor_std.php';
    			$this->rte_on = 0;
    	}

		//-----------------------------------------
		// Load classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/editor/class_editor.php' );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/' . $class, 'class_editor_module' );
		
		$this->class_editor						=  new $classToLoad( $this->registry );
		
		$this->class_editor->allow_unicode		=  IPS_ALLOW_UNICODE;
		$this->class_editor->get_magic_quotes	=  IPS_MAGIC_QUOTES;
		
		//-----------------------------------------
		// Load lang file
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_editors' ), 'core' );
		
		//-----------------------------------------
		// Init class
		//-----------------------------------------

        $this->class_editor->editorInit();
        
		//-----------------------------------------
  		// Load skin and language
  		//-----------------------------------------

  		if ( IN_ACP )
  		{
			$image_set = $this->DB->buildAndFetch( array( 'select' => 'set_image_dir, set_emo_dir', 'from' => 'skin_collections', 'where' => 'set_is_default=1' ) );

			$this->image_dir = $image_set['set_image_dir'];
			$this->emo_dir   = $image_set['set_emo_dir'];

			//-----------------------------------------
			// Remove side panel
			//-----------------------------------------

			$this->remove_emoticons  = true;
  		}
  		else
  		{
			$this->emo_dir = ipsRegistry::getClass('output')->skin['set_emo_dir'];
  		}
    }
    
	/**
	 * Show the editor
	 *
	 * @param	string		Raw text with bbcode
	 * @param	string		Form field name
	 * @return	string		Editor HTML
	 */
	public function showEditor( $text, $form_field='post_content' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$smilies      = IPSLib::fetchEmoticonsAsJson( $this->emo_dir );
		$total        = 0;
		$count        = 0;
		$smilie_id    = 0;
		
		//-----------------------------------------
  		// Load skin and language
  		//-----------------------------------------

  		//if ( IN_ACP )
  		//{
			//-----------------------------------------
			// Sort out editor id
			//-----------------------------------------
			
			$this->editor_id = 'ed-'.$this->acp_editor_id;
			
			$this->acp_editor_id++;
  		//}

		//-----------------------------------------
		// Emoticons
		//-----------------------------------------
		
		$this->settings['_remove_emoticons'] =  $this->remove_emoticons;
		
  		//-----------------------------------------
  		// Pre parse...
  		//-----------------------------------------

  		$text = $this->class_editor->processBeforeForm( $text );

		//-----------------------------------------
		// Weird script tag stuff...
		//-----------------------------------------
		
		if( $this->method == 'rte' )
		{
			$text = preg_replace( '#(<|&lt;|&amp;lt;|&\#60;)script#si', "&amp;lt;script", $text );
		}

		//-----------------------------------------
		// Comment
		//-----------------------------------------

  		if ( IN_ACP )
  		{
  			$return_html = $this->registry->getClass('output')->global_template->ips_editor( $form_field, $text, $this->settings['img_url'].'/folder_editor_images/', $this->rte_on, $this->editor_id, $smilies );
  			
			$return_html = preg_replace( '#([^/\.])js/#is', "\\1".$this->settings['board_url'] . '/' . PUBLIC_DIRECTORY . "/js/"                 , $return_html );
			$return_html = str_replace( "<#IMG_DIR#>"        , $this->settings['board_url'] . '/' . PUBLIC_DIRECTORY . "/style_images/{$this->image_dir}", $return_html );
		}
		else
		{
			$return_html = $this->registry->getClass('output')->getTemplate('editors')->ips_editor( $form_field, $text, $this->settings['img_url'].'/folder_editor_images/', $this->rte_on, $this->editor_id, $smilies );
		}

		return $return_html;
  	}
  	
	/**
	 * Retrieve the posted contents from the editor
	 *
	 * @param	string		Form field name OR raw text
	 * @param	boolean		Is definitely full text and not a field name
	 * @return	string		Editor HTML
	 */
  	public function processRawPost( $form_field, $isDefinitelyFullText=false )
  	{
  		return $this->class_editor->processAfterForm( $form_field, $isDefinitelyFullText );
  	}

	/**
	 * Runs through processBeforeForm
	 *
	 * @param	string		Raw text
	 * @return	string		Processed text
	 */
  	public function unProcessRawPost( $text )
  	{
  		return $this->class_editor->processBeforeForm( $text );
  	}
}