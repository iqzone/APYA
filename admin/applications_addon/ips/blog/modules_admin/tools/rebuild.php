<?php
/**
 * @file		rebuild.php 	Rebuild tools
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		27th January 2004
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_blog_tools_rebuild
 * @brief		Rebuild tools
 */
class admin_blog_tools_rebuild extends ipsCommand
{
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
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blog' );
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_blog' ) );
		
		/* SQL */
		$registry->DB()->loadCacheFile( IPSLib::getAppDir('blog') . '/sql/' . ips_DBRegistry::getDriverType() . '_blog_queries.php', 'sql_blog_queries' );
		
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=tools&amp;section=rebuild&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=tools&section=rebuild&';
		
		switch( $this->request['do'] )
		{
			case 'doresyncblogs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_tool_resync' );
				$this->reSyncBlogs();
			break;
			
			case 'doresyncentries':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_tool_resync' );
				$this->reSyncEntries();
			break;

			case 'dorebuildstats':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_stats' );
				$this->rebuildStats();
			break;

			case 'dorefreshrss':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_stats' );
				$this->rebuildRss();
			break;
			
			case 'dorebuildthumbs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_thumbs' );
				$this->rebuildThumbnails();
			break;

			case 'doconvertattach':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_attach' );
				$this->convertAttach();
			break;

			case 'tools':			
			default:
				$this->toolsIndex();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * Tools index page
	 *
	 * @return	@e void
	 */
	public function toolsIndex()
	{
		/* Output */
		$this->registry->output->html .= $this->html->toolsOverview();
	}

	/**
	 * Resynchronize blogs
	 *
	 * @return	@e void
	 */
	public function reSyncBlogs()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval( $this->request['st'] );
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$end   += $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'blog_blogs', 'where' => "blog_id > $end" ) );
		$max = intval( $tmp['count'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => 'blog_id, blog_name, blog_view_level, member_id', 'from' => 'blog_blogs', 'where' => "blog_id >= $start and blog_id < $end", 'order' => 'blog_id ASC' ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			/* Rebuild last info */
			$this->registry->getClass('blogFunctions')->rebuildBlog( $r['blog_id'] );
			
			if( ! $r['blog_view_level'] )
			{
				$r['blog_view_level'] = 'public';
				$this->DB->update( 'blog_blogs', array( 'blog_view_level' => 'public' ), "blog_id={$r['blog_id']}" );
			}
			
			if( $r['blog_view_level'] == 'private' AND ! $r['blog_owner_only'] )
			{
				$this->DB->update( 'blog_blogs', array( 'blog_owner_only' => 1 ), "blog_id={$r['blog_id']}" );
			}
			
			/* Done */
			$output[] = $this->lang->words['tr_processedblog'] .$r['blog_name'];
			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = "<b>{$this->lang->words['tr_completed']}</b><br />".implode( "<br />", $output );
			$url  = $this->form_code;
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------

			$text = "<b>$end " . $this->lang->words['tr_processedsofar'] . "</b><br />".implode( "<br />", $output );
			$url  = "{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$end}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->html	.= $this->registry->output->global_template->temporaryRedirect( $this->settings['base_url'] . $url, $text );
	}

	/**
	 * Resynchronize entries
	 *
	 * @return	@e void
	 */
	public function reSyncEntries()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval( $this->request['st'] );
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$end   += $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'blog_entries', 'where' => "entry_id > $end" ) );
		$max = intval( $tmp['count'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => 'entry_id, entry_name', 'from' => 'blog_entries', 'where' => "entry_id >= $start and entry_id < $end", 'order' => 'entry_id ASC' ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			$this->registry->getClass('blogFunctions')->rebuildEntry( $r['entry_id'] );
			$output[] = $this->lang->words['tr_processedentry'].$r['entry_name'];
			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = "<b>{$this->lang->words['tr_completed']}</b><br />".implode( "<br />", $output );
			$url  = $this->form_code;
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------

			$text = "<b>$end " . $this->lang->words['tr_processedsofar'] . "</b><br />".implode( "<br />", $output );
			$url  = "{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$end}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->html	.= $this->registry->output->global_template->temporaryRedirect( $this->settings['base_url'] . $url, $text );
	}

	/**
	 * Force RSS feeds to refresh
	 *
	 * @return	@e void
	 * @link	http://community.invisionpower.com/tracker/issue-31527-blog-rss-issue-obsolete-links
	 */
	public function rebuildRss()
	{
		$this->DB->update( 'blog_rsscache', array( 'rsscache_refresh' => 1 ) );
		
        $this->registry->adminFunctions->saveAdminLog( $this->lang->words['tr_rebuildrss'] );
        $this->registry->output->global_message = $this->lang->words['tr_rebuildrssdone'];
        $this->toolsIndex();
	}

	/**
	 * Rebuild stastistics
	 *
	 * @return	@e void
	 */
	public function rebuildStats()
	{
		$this->registry->getClass('blogFunctions')->rebuildStats();
		
        $this->registry->adminFunctions->saveAdminLog( $this->lang->words['tr_rebuildstats'] );
        $this->registry->output->global_message = $this->lang->words['tr_rebuildstatsdone'];
        $this->toolsIndex();
	}

	/**
	 * Fix old attachments bbcodes for entries
	 *
	 * @return	@e void
	 */
	public function convertAttach()
	{
		$done   = 0;
		$start  = intval( $this->request['st'] );
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$end   += $start;
		$output = array();
		$cblocks = intval( $this->request['cblocks'] ) ? intval( $this->request['cblocks'] ) : 0;

		if ( ! $cblocks )
		{
			//-----------------------------------------
			// Got any more?
			//-----------------------------------------

			$tmp = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'blog_entries', 'where' => "entry_id > {$end}" ) );
			$max = intval( $tmp['count'] );

			//-----------------------------------------
			// Avoid limit...
			//-----------------------------------------

			$this->DB->build( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id >= {$start} and entry_id < {$end}", 'order' => 'entry_id ASC' ) );
			$outer = $this->DB->execute();

			//-----------------------------------------
			// Process...
			//-----------------------------------------

			while( $entry = $this->DB->fetch( $outer ) )
			{
				if ( $entry['entry_has_attach'] )
				{
					preg_match_all( "#\[attachmentid=(\d+?)\]#is", $entry['entry'], $match );
					if ( is_array( $match[0] ) and count( $match[0] ) )
					{
						for ( $i = 0 ; $i < count( $match[0] ) ; $i++ )
						{
							if ( intval($match[1][$i]) == $match[1][$i] )
							{
								$attach_ids[ $match[1][$i] ] = $match[1][$i];
							}
						}
						ksort( $attach_ids );

						$replaced = 0;
						$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module='blogentry' AND attach_rel_id=".intval($entry['entry_id']), 'order' => 'attach_id ASC' ) );
						$qid = $this->DB->execute();
						while ( $attach = $this->DB->fetch( $qid ) )
						{
							$attach_id = array_shift( $attach_ids );
							$entry['entry'] = str_replace( '[attachmentid='.$attach_id.']', '[attachment='.$attach['attach_id'].':'.$attach['attach_file'].']', $entry['entry'] );
							$replaced = 1;
						}

						if ( $replaced )
						{
							$replace['entry'] = $entry['entry'];
							$this->DB->update( 'blog_entries', $replace, 'entry_id='.intval($entry['entry_id']) );
						}
					}
				}

				$output[] = $this->lang->words['tr_processedentry'].$entry['entry_name'];
				$done++;
			}

			//-----------------------------------------
			// Finish - or more?...
			//-----------------------------------------

			if ( ! $done and ! $max )
			{
			 	//-----------------------------------------
				// Done..
				//-----------------------------------------

				$text = "<b>{$this->lang->words['tr_entryrebuild']}</b><br />".implode( "<br />", $output );
				$url  = "{$this->form_code}&do=".$this->request['req'].'&pergo='.$this->request['pergo'].'&cblocks=1';
				$time = 0;
			}
			else
			{
				//-----------------------------------------
				// More..
				//-----------------------------------------

				$text = "<b>$end " . $this->lang->words['tr_processedsofar'] . "</b><br />".implode( "<br />", $output );
				$url  = "{$this->form_code}&do=".$this->request['req'].'&pergo='.$this->request['pergo'].'&st='.$end;
				$time = 0;
			}
		}
		else
		{
			//-----------------------------------------
			// Got any more?
			//-----------------------------------------

			$tmp = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'blog_custom_cblocks', 'where' => "cbcus_id > $end" ) );
			$max = intval( $tmp['count'] );

			//-----------------------------------------
			// Avoid limit...
			//-----------------------------------------

			$this->DB->build( array( 'select' => '*', 'from' => 'blog_custom_cblocks', 'where' => "cbcus_id >= $start and cbcus_id < $end", 'order' => 'cbcus_id ASC' ) );
			$outer = $this->DB->execute();

			//-----------------------------------------
			// Process...
			//-----------------------------------------

			while( $cblock = $this->DB->fetch( $outer ) )
			{
				if ( $cblock['cbcus_has_attach'] )
				{
					preg_match_all( "#\[attachmentid=(\d+?)\]#is", $cblock['cbcus'], $match );
					if ( is_array( $match[0] ) and count( $match[0] ) )
					{
						for ( $i = 0 ; $i < count( $match[0] ) ; $i++ )
						{
							if ( intval($match[1][$i]) == $match[1][$i] )
							{
								$attach_ids[ $match[1][$i] ] = $match[1][$i];
							}
						}
						ksort( $attach_ids );

						$replaced = 0;
						$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module='blogcblock' AND attach_rel_id=".intval($cblock['cbcus_id']), 'order' => 'attach_id ASC' ) );
						$qid = $this->DB->execute();
						while ( $attach = $this->DB->fetch( $qid ) )
						{
							$attach_id = array_shift( $attach_ids );
							$cblock['cbcus'] = str_replace( '[attachmentid='.$attach_id.']', '[attachment='.$attach['attach_id'].':'.$attach['attach_file'].']', $cblock['cbcus'] );
							$replaced = 1;
						}

						if ( $replaced )
						{
							$replace['cbcus'] = $cblock['cbcus'];
							$this->DB->update( 'blog_custom_cblocks', $replace, 'cbcus_id='.intval($cblock['cbcus_id']) );
						}
					}
				}

				$output[] = $this->lang->words['tr_contentblock'].$cblock['cbcus_name'];
				$done++;
			}

			//-----------------------------------------
			// Finish - or more?...
			//-----------------------------------------

			if ( ! $done and ! $max )
			{
			 	//-----------------------------------------
				// Done..
				//-----------------------------------------

				$text = "<b>{$this->lang->words['tr_completed']}</b><br />".implode( "<br />", $output );
				$url  = $this->form_code;
				$time = 2;
			}
			else
			{
				//-----------------------------------------
				// More..
				//-----------------------------------------

				$text = "<b>$end " . $this->lang->words['tr_processedsofar'] . "</b><br />".implode( "<br />", $output );
				$url  = "{$this->form_code}&do=".$this->request['req'].'&pergo='.$this->request['pergo'].'&cblocks=1&st='.$end;
				$time = 0;
			}
		}

		/* Redirect */
		$this->registry->output->html	.= $this->registry->output->global_template->temporaryRedirect( $this->settings['base_url'] . $url, $text );
	}

	/**
	 * Rebuild attachment thumbnails
	 *
	 * @return	@e void
	 */
	public function rebuildThumbnails()
	{
		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		require_once( IPS_KERNEL_PATH . 'classImageGd.php' );/*noLibHook*/
		
		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();
		$url	= '';
		
		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'attach_id', 'from' => 'attachments', 'where' => "attach_rel_module IN('blogentry','blogcblock')", 'limit' => array($dis,1)  ) );
		$max = intval( $tmp['attach_id'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module IN('blogentry','blogcblock')", 'order' => 'attach_id ASC', 'limit' => array($start,$end) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			if ( $r['attach_is_image'] )
			{
				if ( $r['attach_thumb_location'] and ( $r['attach_thumb_location'] != $r['attach_location'] ) )
				{
					if ( is_file( $this->settings['blog_upload_dir'].'/'.$r['attach_thumb_location'] ) )
					{
						if ( ! @unlink( $this->settings['blog_upload_dir'].'/'.$r['attach_thumb_location'] ) )
						{
							$output[] = sprintf($this->lang->words['blog_could_not_remove'], $r['attach_thumb_location']);
							continue;
						}
					}
				}

				$attach_data	= array();
				$thumbnail		= preg_replace( "/^(.*)\.(.+?)$/", "\\1_thumb.\\2", $r['attach_location'] );

				$image = new classImageGd();

				$image->init( array(
				                         'image_path'     => $this->settings['blog_upload_dir'],
				                         'image_file'     => $r['attach_location'],
				               )          );

				$image->force_resize	= false;

				$return = $image->resizeImage( $this->settings['blog_thumb_width'], $this->settings['blog_thumb_height'] );
				
				if( !$return['noResize'] )
				{
					$image->writeImage( $this->settings['blog_upload_dir'] . '/' . $thumbnail );
					
					$attach_data['attach_thumb_location'] = $thumbnail;
				}

				$attach_data['attach_thumb_width']    = intval($return['newWidth'] ? $return['newWidth'] : $image->cur_dimensions['width']);
				$attach_data['attach_thumb_height']   = intval($return['newHeight'] ? $return['newHeight'] : $image->cur_dimensions['height']);

				if ( count( $attach_data ) )
				{
					$this->DB->update( 'attachments', $attach_data, 'attach_id='.$r['attach_id'] );
					
					$output[] = sprintf($this->lang->words['blog_resized'], $r['attach_location']);
				}

				unset($image);
			}

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------
		
		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['tr_completed'] . '<br />' . implode( "<br />", $output );
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------

			$text = sprintf( $this->lang->words['tr_uptodis'], $dis ) . '<br />' . implode( "<br />", $output );
			$url  = "&amp;do={$this->request['do']}&amp;pergo={$this->request['pergo']}&amp;st={$dis}";
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------
		
		$this->registry->output->html .= $this->registry->output->global_template->temporaryRedirect( $this->settings['base_url'] . $this->form_code . $url, $text );
	}
}