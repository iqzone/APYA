<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Posting Library: Add Content Block
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
		
		/* Post Key */
		$this->post_key = !empty( $this->request['post_key'] ) ? $this->request['post_key'] : md5(microtime());
		
		/* Permission Check */
		$this->checkForNewCBlock();
		
		/* Navigation */
	    $this->nav[] = array( $this->lang->words['blog_cblock'] );
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

		/* Check to make sure we have a valid content block title */
		$this->request['CBlockTitle'] = str_replace( "<br>", "", $this->request['CBlockTitle'] );
		$this->request['CBlockTitle'] = trim( $this->request['CBlockTitle'] );

		if( ( IPSText::mbstrlen( $this->request['CBlockTitle'] ) < 2 ) or ( ! $this->request['CBlockTitle'] ) )
		{
			$this->obj['post_errors'] = 'no_topic_title';
		}

		/* More unicode.. */
		$temp = IPSText::stripslashes($_POST['CBlockTitle']);
		$temp = preg_replace("/&#([0-9]+);/", "-", $temp );

		if( IPSText::mbstrlen( $temp ) > 64 )
		{
			$this->obj['post_errors'] = 'topic_title_long';
		}
		
		/* Show form with errors */
		if( ( $this->obj['post_errors'] != "" ) or ($this->obj['preview_post'] != "" ) )
		{
			$this->showForm();
		}
		/* Add the block */
		else
		{
			$this->addNewCBlock();
		}

	}
	
	/**
	 * Adds the new ontent block to the database
	 *
	 * @return	@e void
	 */
	public function addNewCBlock()
	{
		/* Insert the block into the database */
		$this->DB->insert( 'blog_custom_cblocks', $this->cblock );
		$cbref_id = $this->DB->getInsertId();

		/* Add the block to the users blog */
		$order = $this->DB->buildAndFetch( array( 
													'select' => "MAX(cblock_order) AS cblock_order",
													'from'   => "blog_cblocks",
													'where'  => "blog_id={$this->blog_id}"
												)	);

		$save['cblock_order']  = intval($order['cblock_order'])+1;
		$save['blog_id']       = $this->blog_id;
		$save['member_id']     = $this->memberData['member_id'];
		$save['cblock_type']   = 'custom';
		$save['cblock_ref_id'] = $cbref_id;
		$save['cblock_show']   = 1;
		$this->DB->insert( 'blog_cblocks', $save );

		/* Make attachments "permanent" */
		$this->pfMakeAttachmentsPermanent( $this->post_key, $cbref_id, 'blogcblock' );
		
		/* Update cache */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$cblock_lib	 = new $classToLoad( $this->registry );
		$cblock_lib->recacheAllBlocks( $this->blog['blog_id'] );

		if( $this->request['inline'] == 1 )
		{
			$this->registry->output->silentRedirect( str_replace( "&amp;", "&", $this->blogFunctions->getBlogUrl( $this->blog['blog_id'], $this->blog['blog_seo_name'] ) ) );
		}
	}

	/**
	 * SHOW FORM
	 *
	 * @return	@e void
	 */
	public function showForm()
	{
		/* Set the default title value */
		$cblock_title = isset( $_POST['CBlockTitle'] ) ? $this->request['CBlockTitle'] : "";

		/* Display Errors */
		if ( !empty( $this->obj['post_errors'] ) )
		{
			$this->output .= "<br />".$this->registry->output->getTemplate('post')->errors( $this->lang->words[ $this->obj['post_errors'] ]);
		}
		
		/* Display Preview */
		if ($this->obj['preview_post'])
		{
			$this->output .= "<br />".$this->registry->output->getTemplate('post')->preview( $this->showPostPreview( $this->cblock['cbcus'] ) );
		}
		
		/* Upload */
		if( $this->can_upload )
		{
			$upload_field = $this->htmlBuildUploads( $this->post_key, 'blogcblock' );
		}
		
		/* Build hte form */
		$this->output .= $this->registry->output->getTemplate( 'blog_post' )->cBlockForm( array( array( 'do'      , 'doaddcblock' ), 
																								 array( 'post_key', $this->post_key ), 
																								 array( 'inline'  , $this->request['inline'] ? 1 : 0 ),
																								 array( 'blogid'  , $this->request['blogid'] )
																								),
																							$this->lang->words['blog_cblocks_add'],
																							$this->lang->words['blog_cblock_submit'],
																							$cblock_title,
																							$this->htmlPostBody( $_POST['Post'] ),
																							$this->htmlCheckBoxes(),
																							$upload_field,
																							$this->blog
																						);

		/* Output */
		$this->registry->output->setTitle( $this->settings['board_name'] . ' -> ' . $this->lang->words['blog_cblock'] );
		
		foreach( $this->nav as $v )
		{
			$this->registry->output->addNavigation( $v[0], $v[1], $v[2], $v[3] );
		}
		$this->registry->output->addContent( $this->output );
		$this->blogFunctions->sendBlogOutput( $this->blog, $this->lang->words['blog_cblocks_add'] );
	}

	/**
	 * perform: Check for new cblock
	 *
	 * @return	@e void
	 */
	public function checkForNewCBlock()
	{
		if( ! $this->blogFunctions->ownsBlog( $this->blog['blog_id'], $this->memberData['member_id'] ) )
		{
            $this->registry->output->showError( 'no_blog_mod_permission', 106146, null, null, 403 );
        }

		if( ! $this->settings['blog_allow_cblockchange'] )
        {
            $this->registry->output->showError( 'no_blog_mod_permission', 106147, null, null, 403 );
        }

		if( ! $this->settings['blog_allow_cblocks'] )
        {
            $this->registry->output->showError( 'no_blog_mod_permission', 106148, null, null, 403 );
        }
	}
}