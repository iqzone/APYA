<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Posting Library: Edit Content Block
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

class postFunctions extends blogPost
{
	/**
	 * Array of content block data
	 *
	 * @var array
	 */
	public $org_cblock = array();
	
	/**
	 * Array of content block data
	 *
	 * @var array
	 */
	public $cblock   = array();
	
	/**
	 * Unique MD5 hash for this post
	 *
	 * @var string
	 */
	public $post_key = "";

	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Parent contructor */
		parent::__construct( $registry );
		
		/* Check ID */
		$cbid = intval( $this->request['cbid'] );
		
		if( ! $cbid )
		{
			$this->registry->output->showError( 'missing_files', 106149, null, null, 404 );
		}

		/* Get the original entry */
		$this->org_cblock = $this->DB->buildAndFetch( array( 
															'select'	=>	'ccb.*',
															'from'		=> array( 'blog_custom_cblocks' => 'ccb' ),
															'add_join'	=> array(
																				array( 
																						'select' => 'cb.blog_id',
																						'from'   => array( 'blog_cblocks' => 'cb' ),
																						'where'  => "cb.cblock_ref_id=ccb.cbcus_id and cb.cblock_type='custom'",
																						'type'   => 'inner'
																					)
																				),
															'where'		=> "ccb.cbcus_id = {$cbid}"
												 	)	);

		if( ! $this->org_cblock['cbcus_id'] )
		{
			$this->registry->output->showError( 'missing_files', 106150, null, null, 404 );
		}

		if( $this->org_cblock['blog_id'] != $this->blog['blog_id'] )
		{
			$this->registry->output->showError( 'missing_files', 106151 );
		}
		
		/* Post Key */
		$this->post_key = $this->org_cblock['cbcus_post_key'];

		$this->checkForEditCBlock();

	    $this->nav[] = array( $this->lang->words['blog_editcblock'] );
	}

	/**
	 * MAIN PROCESS FUNCTION
	 *
	 * @return	@e void
	 */
	public function process()
	{
		/* Parse the post, and check for any errors. */
		$this->cblock = $this->compileCBlock();

		/* Check the block title */
		$this->request['CBlockTitle'] = str_replace( "<br>", "", $this->request['CBlockTitle'] );
		$this->request['CBlockTitle'] = trim( $this->request['CBlockTitle'] );

		if( ( IPSText::mbstrlen( $this->request['CBlockTitle'] ) < 2 ) or ( ! $this->request['CBlockTitle' ] ) )
		{
			$this->obj['post_errors'] = 'no_topic_title';
		}

		/* More unicode.. */
		$temp = IPSText::stripslashes( $_POST['CBlockTitle'] );
		$temp = preg_replace("/&#([0-9]+);/", "-", $temp );

		if( IPSText::mbstrlen( $temp ) > 64 )
		{
			$this->obj['post_errors'] = 'topic_title_long';
		}

		if( ($this->obj['post_errors'] != "") or ( $this->obj['preview_post'] != "" ) )
		{
			$this->showForm();
		}
		else
		{
			$this->completeEdit();
		}
	}

	/**
	 * Do the edit
	 *
	 * @return	@e void
	 */
	public function completeEdit()
	{
		/* Update the custom block */
		$this->DB->update( 'blog_custom_cblocks', $this->cblock, 'cbcus_id='.$this->org_cblock['cbcus_id'] );

		/* Make attachments "permanent" */
		$this->pfMakeAttachmentsPermanent( $this->post_key, $this->org_cblock['cbcus_id'], 'blogcblock' );
		
		/* Update cache */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$cblock_lib	 = new $classToLoad( $this->registry );
		$cblock_lib->recacheAllBlocks( $this->blog['blog_id'] );
		
		/* Redirect the user */
		if( $this->request['inline'] == 1 )
		{
			$this->registry->output->silentRedirect( str_replace( "&amp;", "&", $this->blogFunctions->getBlogUrl( $this->blog['blog_id'], $this->blog['blog_seo_name'] ) ) );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['cblock_edited'], $this->settings['base_url'] . "app=blog", 'false', 'app=blog' );
		}
	}

	/**
	 * SHOW FORM
	 *
	 * @return	@e void
	 */
	public function showForm()
	{
		/* Get saved/edited post and setup HTML */
		$this->editor->setAllowSmilies( $this->org_entry['entry_use_emo'] ? 1 : 0 );
		$this->editor->setAllowHtml( (intval($this->org_entry['entry_htmlstatus']) AND $this->memberData['g_blog_do_html']) ? 1 : 0 );
		
		$raw_post = empty($_POST['Post']) ? $this->org_cblock['cbcus'] : $_POST['Post'];
		
		/* Set the default title */
		$cblock_title = isset( $_POST['CBlockTitle'] ) ? $this->request['CBlockTitle'] : $this->org_cblock['cbcus_name'];

		/* Show the errors */
		if( !empty( $this->obj['post_errors'] ) )
		{
			$this->output .= "<br />".$this->registry->output->getTemplate( 'post' )->errors( $this->lang->words[ $this->obj['post_errors'] ]);
		}
		
		/* Show the preview */
		if( $this->obj['preview_post'] )
		{
			$this->output .= "<br />".$this->registry->output->getTemplate( 'post' )->preview( $this->showPostPreview( $this->cblock['cbcus'] ) );
		}
		
		/* Upload */
		if( $this->can_upload )
		{
			$upload_field = $this->htmlBuildUploads( $this->post_key, 'blogcblock' );
		}
		
		/* HTML Status */
		$this->request['post_htmlstatus'] = $this->org_cblock['cbcus_html_state'];

		/* Build hte form */
		$this->output .= $this->registry->output->getTemplate( 'blog_post' )->cBlockForm(
																							array( 
																									array( 'do'      , 'doeditcblock' ), 
																									array( 'cbid'    , $this->org_cblock['cbcus_id'] ), 
																									array( 'post_key', $this->post_key ), 
																									array( 'inline'  , $this->request['inline'] ? 1 : 0 ),
																									array( 'blogid'  , $this->request['blogid'] )
																							),
																							$this->lang->words['blog_editcblock'],
																							$this->lang->words['blog_cblock_editsubmit'],
																							$cblock_title,
																							$this->htmlPostBody( $raw_post ),
																							$this->htmlCheckBoxes(),
																							$upload_field,
																							$this->blog
																						);

		/* Output */
		$this->registry->output->setTitle( $this->settings['board_name'] . ' -> ' . $this->lang->words['blog_editcblock'] );
		
		foreach( $this->nav as $v )
		{
			$this->registry->output->addNavigation( $v[0], $v[1], $v[2], $v[3] );
		}
		$this->registry->output->addContent( $this->output );
		$this->blogFunctions->sendBlogOutput( $this->blog, $this->lang->words['blog_editcblock'] );
	}

	/**
	 * perform: Check for edit cblock
	 *
	 * @return	@e void
	 */
	public function checkForEditCBlock()
	{
		if( $this->memberData['member_id'] == $this->blog['member_id'] )
		{
			if( ! $this->memberData['g_blog_allowlocal'] )
			{
	            $this->registry->output->showError( 'no_blog_mod_permission', 106152, null, null, 403 );
	        }

			if( ! $this->settings['blog_allow_cblockchange'] )
	        {
	            $this->registry->output->showError( 'no_blog_mod_permission', 106153, null, null, 403 );
	        }

			if( ! $this->settings['blog_allow_cblocks'] )
	        {
	            $this->registry->output->showError( 'no_blog_mod_permission', 106154, null, null, 403 );
	        }
	    }
	    else
	    {
	    	if( ! $this->memberData['g_is_supmod'] && ! $this->memberData['_blogmod']['moderate_allow_editcblocks'] )
	    	{
	            $this->registry->output->showError( 'no_blog_mod_permission', 106155, null, null, 403 );
	    	}
	    }
	}
}