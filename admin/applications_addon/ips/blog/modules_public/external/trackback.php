<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog RSD support
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 4 $
 * @todo		Update class_xml references to classXml
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_external_trackback extends ipsCommand
{
	/**
	* Response library (used for some XML returns)
	*
	* @access	protected
	* @var 		object
	*/
	protected $response;
	
	/**
	* Stored temporary output
	*
	* @access	protected
	* @var 		string 				Page output
	*/
	protected $output				= "";

	/**
	* Current entry
	*
	* @access	protected
	* @var 		array 				Blog entry data
	*/
	protected $entry				= array();
	
	/**
	* Errors from current ping request
	*
	* @access	protected
	* @var 		array 				Stored errors
	*/
	protected $ping_errors			= array();
	
	protected $isAkismetSpam = null;
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Entry id
		//-----------------------------------------
		
		$entry_id = intval( $this->request['eid'] );

		if( !$entry_id )
		{
			$this->registry->output->showError( 'incorrect_use', 10691, null, null, 404 );
		}

		//-----------------------------------------
		// The entry itself
		//-----------------------------------------
		
		$this->entry = $this->DB->buildAndFetch ( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id={$entry_id}" ) );

		if ( !$this->entry['entry_id'] )
		{
			$this->registry->output->showError( 'incorrect_use', 10692, null, null, 404 );
		}

	    $this->entry['entry_sent_trackbacks'] = $this->entry['entry_sent_trackbacks'] ? unserialize($this->entry['entry_sent_trackbacks']) : array();

		//-----------------------------------------
		// Get the Blog info
		//-----------------------------------------
		
		$this->registry->getClass('blogFunctions')->setActiveBlog( $this->entry['blog_id'] );
		$this->blog = $this->registry->getClass('blogFunctions')->getActiveBlog();

		//-------------------------------------------
		// AJAX library (has some useful methods)
		//-------------------------------------------
		
		require_once IPSLib::getAppDir('blog') . '/sources/xmlResponse.php';/*noLibHook*/
		$this->response	= new xmlResponse( $this->registry );
		
		//-------------------------------------------
		// And then
		//-------------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'sendtb':
				$this->sendTrackback();
			break;
			
			case 'dosendtrackback':
				$this->doSendTrackback();
			break;
		}

		//-------------------------------------------
		// Some permission checks
		//-------------------------------------------

		if ( !$this->settings['blog_allow_trackback'] )
		{
			$this->response->returnError( "No trackback allowed" );
		}

		$blog					= $this->DB->buildAndFetch( array ( 'select' => 'blog_settings, blog_disabled', 'from' => 'blog_blogs', 'where' => "blog_id={$this->entry['blog_id']}" ) );
		$blog['blog_settings']	= unserialize( $blog['blog_settings'] );
		
		if ( $blog['blog_disabled'] )
		{
			$this->response->returnError( $this->lang->words['tb_error_send'] );
		}
		
		if ( !$blog['blog_settings']['allowtrackback'] )
		{
			$this->response->returnError( $this->lang->words['tb_error_disabled'] );
		}

		//-------------------------------------------
		// Are they requesting to list the trackbacks?
		//-------------------------------------------
		
		if ( $this->request['__mode'] == 'rss' )
		{
			$this->_trackbackList();
		}

		//-------------------------------------------
		// We have a URL?
		//-------------------------------------------
		
		if ( $_POST['url'] )
		{
			$url = $this->request['url'];
		}
		else
		{
			$this->response->returnError( $this->lang->words['tb_error_nourl'] );
		}

		//-------------------------------------------
		// Attempt to grab charset encoding
		//-------------------------------------------
		
		if ( preg_match ("/;\s*charset=([^\n]+)/is", my_getenv('CONTENT_TYPE'), $reg_charset ) )
		{
			$charset = strtoupper(trim($reg_charset[1]));
		}
		else
		{
			$charset = 'UTF-8';
		}

		//-------------------------------------------
		// Convert the data to our charset
		//-------------------------------------------
		
		if ( strtoupper($charset) != strtoupper($this->settings['gb_char_set']) )
		{
			$_POST['title']		= IPSText::convertCharsets( $_POST['title'], $charset );
			$_POST['excerpt']	= IPSText::convertCharsets( $_POST['excerpt'], $charset );
			$_POST['blog_name']	= IPSText::convertCharsets( $_POST['blog_name'], $charset );
		}

		//-------------------------------------------
		// Clean
		//-------------------------------------------
		
		$title		= $_POST['title']		? IPSText::parseCleanValue( $_POST['title'] )	: "";
		$excerpt	= $_POST['excerpt']		? IPSText::parseCleanValue( $_POST['excerpt'] )	: "";
		$blog_name	= $_POST['blog_name']	? IPSText::parseCleanValue($_POST['blog_name'])	: "";

		//-------------------------------------------
		// Truncate
		//-------------------------------------------
		
		if ( IPSText::mbstrlen( $excerpt ) > 250 )
		{
			$excerpt = IPSText::truncate( $excerpt, 246 );
		}

		//-------------------------------------------
		// Check for duplicates
		//-------------------------------------------
		
		$trackback	= $this->DB->buildAndFetch ( array ( 'select' => '*', 'from' => 'blog_trackback', 'where' => "entry_id={$this->entry['entry_id']} and trackback_url='" . $this->DB->addSlashes( $url ) . "'" ) );

		if ( $trackback['trackback_id'] )
		{
			$this->response->returnError( $this->lang->words['tb_error_dup'] );
		}

		$trackback_queued	= ( $blog['blog_settings']['approve_trackbacks'] ? 1 : 0 );
		
		//-------------------------------------------------
		// Using Akismet?
		//-------------------------------------------------
		
		# Setup some data for later...
		$akismetData = array( 'author'		=> '',
							  'email'		=> '',
							  'body'		=> $excerpt,
							  'permalink'	=> $this->registry->output->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->entry['blog_id']}&amp;showentry={$this->entry['entry_id']}" ),
							  'user_ip'		=> $this->member->ip_address,
							 );
		
		if ( $this->settings['blog_akismet_key'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/lib/akismet.class.php', 'Akismet', 'blog' );
			$akismet = new $classToLoad( $this->settings['board_url'], $this->settings['blog_akismet_key'] );
			
			# Pass data to akismet
			$akismet->setCommentType( 'trackback' );
			$akismet->setCommentAuthor( $akismetData['author'] );
			$akismet->setCommentAuthorEmail( $akismetData['email'] );
			$akismet->setCommentContent( $akismetData['body'] );
			$akismet->setPermalink( $akismetData['permalink'] );
			$akismet->setUserIP( $akismetData['user_ip'] );
			
			try
			{
				/* So that's spam? */
				if( $akismet->isCommentSpam() )
				{
					$this->isAkismetSpam = true;
					
					if( $this->settings['blog_akismet_action'] == 'delete' )
					{
						$this->_akismetLog( $akismetData, 'delete' );

						$this->response->returnError( $this->lang->words['tb_error_spam'] );
					}
					else if( $this->settings['blog_akismet_action'] == 'queue' )
					{
						$trackback_queued = 1;
					}
				}
				else
				{
					$this->isAkismetSpam = false;
				}
			}
			catch( Exception $e )
			{
				// Log error
				$this->_akismetLog( $akismetData, '', $e->getMessage() );
			}
		}

		//-------------------------------------------------
		// Set up insert array
		//-------------------------------------------------
		
		$insert_list = array ( 'blog_id'			=> $this->blog['blog_id'],
							   'entry_id'			=> $this->entry['entry_id'],
							   'ip_address'			=> $this->member->ip_address,
							   'trackback_url'		=> $url,
							   'trackback_title'	=> $title,
							   'trackback_excerpt'	=> $excerpt,
							   'trackback_blog_name'=> $blog_name,
							   'trackback_date'		=> IPS_UNIX_TIME_NOW,
							   'trackback_queued'	=> $trackback_queued
							 );

		//-------------------------------------------------
		// Take a crack at internal spam prevention
		//-------------------------------------------------
		
		if ( $this->settings['blog_prevent_tbspam'] )
		{
			if ( $this->_detectBot() )
			{
				$this->DB->insert( 'blog_trackback_spamlogs', $insert_list );

				$this->response->returnError( $this->lang->words['tb_error_send'] );
			}

			if ( strpos( $_SERVER['REQUEST_URI'], '?s=' ) !== false )
			{
				$this->DB->insert( 'blog_trackback_spamlogs', $insert_list );

				$this->response->returnError( sprintf( $this->lang->words['tb_error_sess'], $this->request['s'] ) );
			}
		}

		//-------------------------------------------------
		// Insert
		//-------------------------------------------------
		
		$this->DB->insert( 'blog_trackback', $insert_list );
		$insert_list['trackback_id'] = $this->DB->getInsertId();
		
		//-------------------------------------------------
		// Using Akismet? (Part 2)
		//-------------------------------------------------
		
		if( !is_null($this->isAkismetSpam) )
		{
			$akismetData['trackback_id'] = $insert_list['trackback_id'];
			
			if( $this->isAkismetSpam )
			{
				if( $this->settings['blog_akismet_action'] == 'queue' )
				{
					$this->_akismetLog( $akismetData, 'queue' );
				}
				else
				{
					$this->_akismetLog( $akismetData, 'allow' );
				}
			}
			else
			{
				$this->_akismetLog( $akismetData, 'fine' );
			}
		}
		
		if ( !$trackback_queued )
		{
			$this->DB->update( 'blog_entries', 'entry_trackbacks=entry_trackbacks+1', "entry_id={$this->entry['entry_id']}", false, true );
		}

		$this->response->returnSuccess();
	}

	/**
	* Return the trackback list
	*
	* @access	protected
	* @return	@e void		[Outputs XML response]
	*/	
	protected function _trackbackList()
	{
		$items = array();

   		$this->DB->build( array( 'select'	=> '*',
   		 								'from'	=> 'blog_trackback',
   		 								'where'	=> "entry_id = {$this->entry['entry_id']}"
   		 					 )		);
   		$this->DB->execute();

   		while ( $trackback = $this->DB->fetch() )
   		{
   			$trackback['trackback_title']	= $trackback['trackback_title'] ? $trackback['trackback_title'] : $trackback['trackback_url'];
   			
   			$items[] = $trackback;
		}

		$entry_desc = IPSText::getTextClass('bbcode')->stripAllTags( $this->entry['entry'] );
		$entry_desc	= IPSText::truncate( $entry_desc, 0, 246 );
		
		$this->response->sendTrackbackList( $items, $this->entry, $entry_desc );
	}

	/**
	* Show form to send a trackback
	*
	* @access	public
	* @return	@e void		[Outputs to screen]
	*/	
	public function sendTrackback()
	{
		$blog = $this->registry->getClass('blogFunctions')->loadBlog( $this->entry['blog_id'] );

		if ( $blog['member_id'] != $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'no_permission', 10693, null, null, 403 );
		}

		if ( !$this->settings['blog_allow_trackbackping'] )
		{
			$this->registry->output->showError( 'no_permission', 10694, null, null, 403 );
		}

	    $this->entry['entry_sent_trackbacks'] = implode ( "<br />", $this->entry['entry_sent_trackbacks'] );

		$this->output = $this->registry->getClass('output')->getTemplate('blog_trackback')->sendTrackbackForm( $this->entry, implode ( "<br />", $this->ping_errors ) );
	
		$this->registry->output->setTitle( $this->lang->words['send_trackback_title'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('blogFunctions')->sendBlogOutput( $this->blog, $this->lang->words['send_trackback_for'] .' "' . $this->entry['entry_name'] . '"' );
	}

	/**
	* Send a trackback
	*
	* @access	public
	* @return	@e void		[Outputs to screen]
	*/	
	public function doSendTrackback()
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_blog' ) );

 		$blog = $this->registry->getClass('blogFunctions')->loadBlog( $this->entry['blog_id'] );
 		
		/* Security Check */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10697, null, null, 403 );
		}

		if ( $blog['member_id'] != $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'no_permission', 10695, null, null, 403 );
		}

		if ( !$this->settings['blog_allow_trackbackping'] )
		{
			$this->registry->output->showError( 'no_permission', 10696, null, null, 403 );
		}

		if ( empty($_POST['trackback_url']) )
		{
			$this->ping_errors[] = $this->lang->words['trackback_error1'];
			$this->sendTrackback();
		}

	    if ( in_array( $this->request['trackback_url'], $this->entry['entry_sent_trackbacks'] ) )
		{
			$this->ping_errors[] = $this->lang->words['trackback_error2'];
			$this->sendTrackback();
		}

		$entry_desc = IPSText::getTextClass('bbcode')->stripAllTags( $this->entry['entry'] );
		$entry_desc	= IPSText::truncate( $entry_desc, 0, 246 );

		if ( $this->_ping(	$_POST['trackback_url'],
							$this->settings['base_url'] . "app=blog&blogid={$this->entry['blog_id']}&showentry={$this->entry['entry_id']}",
							$blog['blog_name'],
							$this->entry['entry_name'],
							$entry_desc
		   )			 )
		{

			$this->entry['entry_sent_trackbacks'][] = $this->request['trackback_url'];
			$entry_sent_trackbacks = serialize( $this->entry['entry_sent_trackbacks'] );
			
			$this->DB->update ( 'blog_entries', array ( 'entry_sent_trackbacks' => $entry_sent_trackbacks ), "entry_id = {$this->entry['entry_id']}" );
			
			$this->output = $this->registry->getClass('output')->getTemplate('blog_trackback')->trackbackSuccess();
			
			$this->registry->output->popUpWindow( $this->lang->words['send_trackback_title'], $this->output );
			exit();
		}
		else
		{
			$this->sendTrackback();
		}
	}

	/**
	* Send the actual trackback response to a server
	*
	* @access	protected
	* @return	boolean			Successful
	*/	
	protected function _ping( $trackback_url, $url, $blog_name="", $title="", $excerpt="" )
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_blog' ) );
		
		//-------------------------------------------------
		// Extract the data
		//-------------------------------------------------

		$tb_url	= parse_url( $trackback_url );
		$host	= isset($tb_url['host']) ? $tb_url['host'] : '';
		$port	= !empty($tb_url['port']) ? $tb_url['port'] : 80;
		$path	= !empty($tb_url['path']) ? $tb_url['path'] : "";
		$query	= !empty($tb_url['query']) ? "?" . $tb_url['query'] : "";
		$result = '';
		
		//-------------------------------------------------
		// Make the connection
		//-------------------------------------------------
		
        if ( $host )
        {
	        $trackback_conn = @fsockopen( $tb_url['host'] == 'https' ? 'ssl://' . $host : $host, $tb_url['host'] == 'https' ? '443' : $port );

	        if ( !$trackback_conn )
	        {
	        	$this->ping_errors[] = sprintf( $this->lang->words['trackback_error3'], $host );
	        	return false;
	        }
	
			//-------------------------------------------------
			// Send the request
			//-------------------------------------------------

			$req = "title=" . rawurlencode($title) . "&url=" . rawurlencode($url) . "&excerpt=" . rawurlencode($excerpt) . "&blog_name=" . rawurlencode($blog_name);

			fputs( $trackback_conn, "POST {$path}{$query} HTTP/1.1\r\n" );
	        fputs( $trackback_conn, "Host: {$host}\r\n");
			fputs( $trackback_conn, "Content-Type: application/x-www-form-urlencoded; charset={$this->settings['gb_char_set']}\r\n" );
	        fputs( $trackback_conn, "Content-length: " . strlen($req) . "\r\n");
	        fputs( $trackback_conn, "Connection: close\r\n\r\n");
	        fputs( $trackback_conn, $req);
	
	   		if ( function_exists( 'stream_set_timeout' ) )
	   		{
	   			stream_set_timeout($trackback_conn, 20);
	   		}
	   		else
	   		{
	   			socket_set_timeout($trackback_conn, 20);
	   		}
	
			//-------------------------------------------------
			// Retrieve the response
			//-------------------------------------------------
			
			$result		= "";
			$timeout	= socket_get_status($trackback_conn);

	   		while( !feof($trackback_conn) && !$timeout['timed_out'] )
	   		{
		       $result	.= fgets( $trackback_conn, 1000 );
		       $timeout	= socket_get_status($trackback_conn);
		   }
	
			//-------------------------------------------------
			// Close the connection
			//-------------------------------------------------

	        fclose($trackback_conn);
		}
		else
		{
			$this->ping_errors[] = $this->lang->words['trackback_error4'];
			return false;
		}
		
		if ( $result == "" )
		{
			$this->ping_errors[] = $this->lang->words['trackback_error5'];
			return false;
		}

		//-------------------------------------------------
		// Get the raw XML
		//-------------------------------------------------

		$result = substr( $result, strpos( $result, "<?xml" ) );

		require_once( IPS_KERNEL_PATH . 'class_xml.php' );/*noLibHook*/
		$xml = new class_xml();
		$xml->xml_parse_document( $result );

		if ( !isset($xml->xml_array['response']['error']['VALUE']) )
		{
			$this->ping_errors[] = $this->lang->words['trackback_error6'];
			return false;
		}

		if ( $xml->xml_array['response']['error']['VALUE'] == "0" )
		{
			return true;
		}
		else
		{
			$this->ping_errors[] = $xml->xml_array['response']['message']['VALUE'];
			return false;
		}
	}

	/**
	* Attempt to detect if the request is coming from a server or not
	*
	* @access	protected
	* @return	boolean			true=Request likely a bot
	*/
	protected function _detectBot()
	{
		//-------------------------------------------------
		// Trackbacks are not sent via a browser
		//-------------------------------------------------
		
		if ( $this->memberData['userAgentType'] == 'browser' )
		{
			return true;
		}
		
		//-------------------------------------------------
		// Spiders don't send trackbacks
		//-------------------------------------------------

		if ( $this->memberData['userAgentType'] == 'search' )
		{
			return true;
		}

		//-------------------------------------------------
		// Trackbacks are not sent through a proxy
		//-------------------------------------------------

		foreach ( $_SERVER as $key => $val )
		{
			if ( substr( $key, 0, 5 ) == 'HTTP_' )
			{
				$headerkey = str_replace( ' ', '-', strtolower( str_replace( '_', ' ', substr($key, 5) ) ) );

				if ( $headerkey == 'via' || $headerkey == 'max-forwards' || $headerkey == 'x-forwarded-for' || $headerkey == 'client-ip' )
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Log a communication from akismet
	 *
	 * @param	array		$comment		Data sent to akismet
	 * @param	string		$action			Action performed
	 * @param	string		$akismetError	Akismet error, if any was returned
	 * @return	@e void
	 */
	protected function _akismetLog( $trackback, $action, $akismetError='' )
	{
		$msg	= '';
		$errors	= '';
		
		if( $akismetError != '' )
		{
			$msg	= $this->lang->words['akismet_error'];
			$errors	= serialize( $akismetError );
		}
		else if( $action == 'fine' )
		{
			$msg = $this->lang->words['akismet_trackback_notspam'];
		}
		else
		{
			$msg = $this->lang->words['akismet_trackback_spam'];
		}
		
		$insert	= array(
						'log_date'			=> IPS_UNIX_TIME_NOW,
						'log_msg'			=> $msg,
						'log_errors'		=> $errors,
						'log_data'			=> serialize($trackback),
						'log_type'			=> 'trackback',
						'log_etbid'			=> intval($trackback['trackback_id']),
						'log_isspam'		=> ( $action != '' AND $action != 'fine' ) ? 1 : 0,
						'log_action'		=> ( $action != '' AND $action != 'fine' ) ? $action : null,
						'log_submitted'		=> 0,
						'log_connect_error'	=> $action ? 0 : 1
						);

		$this->DB->insert( 'blog_akismet_logs', $insert );		
	}

}
