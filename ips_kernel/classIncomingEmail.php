<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.2
 * Parse Incoming Emails
 * Last Updated: $Date: 2012-04-09 11:09:31 -0400 (Mon, 09 Apr 2012) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		25th June 2010
 * @version		$Revision: 10580 $
 */
 
class classIncomingEmail
{
	/**
	 * Debug mode
	 *
	 * @var	boolean
	 */
	const DEBUG_MODE = FALSE;

	/**
	 * Whether to ignore this incoming email or not
	 *
	 * @var	boolean
	 */
	public $ignore = FALSE;

	/**
	 * Recipient Address
	 *
	 * @var	string
	 * @see	__construct()
	 */
	public $to;
	
	/**
	 * Sender Address
	 *
	 * @var	string
	 * @see	__construct()
	 */
	public $from;
	
	/**
	 * Subject
	 *
	 * @var	string
	 * @see	__construct()
	 */
	public $subject;
	
	/**
	 * Message Body
	 *
	 * @var	string
	 * @see	__construct()
	 */
	public $message;
	
	/**
	 * Attachments
	 * This is an associative array of attachment data.
	 * The keys are IDs which are associated with <!--ATTACHMENT:{key}--> taks in $this->body.
	 * The values are an array of data to insert into the attachments database table after attach_member_id and attach_rel_module have been set.
	 *
	 * @var	array
	 * @see	__construct()
	 */
	public $attachments = array();
	
	/**
	 * Attachment Key
	 *
	 * @var	int
	 * @see	_addAttachment()
	 */
	private $akey = 0;

	/**
	 * Constructor
	 * Sets $this->to, $this->from, $this->subject, $this->message and $this->attachments
	 *
	 * @param	string	Raw message with headers
	 * @param	array 	Override auto-determined data. Valid keys are 'to', 'from' and 'subject'
	 * @return	@e void
	 */
	public function __construct( $email, $override=array() )
	{
		$this->raw = $email;
		
		//--------------------------------------
		// Get some stuff
		//--------------------------------------
		
		if ( class_exists( 'ipsRegistry' ) )
		{
			$this->DB = ipsRegistry::DB();
			
			// Fetch upload path
			$this->upload_dir = ipsRegistry::$settings['upload_dir'];
			$this->upload_url = ipsRegistry::$settings['upload_url'];
		}
		else
		{
			$this->initDB();
			
			// Fetch upload path
			$this->DB->build( array( 'select' => 'conf_key,conf_value', 'from' => 'core_sys_conf_settings', 'where' => "conf_key='upload_dir' OR conf_key='upload_url'" ) );
			$this->DB->execute();
			while ( $r = $this->DB->fetch() )
			{
				if ( $r['conf_key'] == 'upload_dir' )
				{
					$this->upload_dir = $r['conf_value'];
				}
				elseif ( $r['conf_key'] == 'upload_url' )
				{
					$this->upload_url = $r['conf_value'];
				}
			}

		}
				
		// And allowed attachment types
		$this->types = array();
		$this->DB->build( array( 'select' => '*', 'from' => 'attachments_type' ) );
		$this->DB->execute();
		while ( $r = $this->DB->fetch() )
		{
			$this->types[ $r['atype_mimetype'] ][] = $r;
		}	

		//--------------------------------------
		// Pass to PEAR
		//--------------------------------------
		
		// It raises strict warnings
		if ( !self::DEBUG_MODE )
		{
			@error_reporting( E_NONE );
 			@ini_set( 'display_errors', 'off' );
 		}
 		
		require_once ( IPS_KERNEL_PATH . 'PEAR/Mail/mimeDecode.php' );/*noLibHook*/
		$decoder = new Mail_mimeDecode( $email );
		$mail = $decoder->decode( array(
			'include_bodies'	=> TRUE,
			'decode_bodies'		=> TRUE,
			'decode_headers'	=> TRUE,
			) );
													
		//--------------------------------------
		// Parse Headers
		//--------------------------------------
		
		/* To */
		if ( isset( $override['to'] ) )
		{
			$this->to = $override['to'];
		}
		else
		{
			if ( $mail->headers['delivered-to'] )
			{
				$mail->headers['to'] = $mail->headers['delivered-to'];
			}
			$to = array();
			if ( strpos( $mail->headers['to'], ',' ) === FALSE )
			{
				$mail->headers['to'] = array( $mail->headers['to'] );
			}
			else
			{
				$mail->headers['to'] = explode( ',', $mail->headers['to'] );
			}
			foreach ( $mail->headers['to'] as $_to )
			{
				if ( preg_match( "/.+? <(.+?)>/", $_to, $matches ) )
				{
					$to[] = htmlentities( $matches[1] );
				}
				else
				{
					$to[] = htmlentities( trim( $_to, '<>' ) );
				}
			}
			$this->to = implode( ',', $to );
		}
				
		/* From */
		if ( isset( $override['from'] ) )
		{
			$this->from = $override['from'];
		}
		else
		{
			if ( preg_match( "/.+? <(.+?)>/", $mail->headers['from'], $matches ) )
			{
				$this->from = htmlentities( $matches[1] );
			}
			else
			{
				$this->from = htmlentities( trim( $mail->headers['from'], '<>' ) );
			}
		}
		
		/* Subject */
		if ( isset( $override['subject'] ) )
		{
			$this->subject = $override['subject'];
		}
		else
		{
			$this->subject = ( (bool) trim( $mail->headers['subject'] ) ) ? $mail->headers['subject'] : '(No Subject)';
			$this->subject = htmlentities( $this->subject );
		}
		
		/* CC */
		$mail->headers['cc'] = preg_replace( '/".+?" <(.+?)>/', '$1', $mail->headers['cc'] );
		if ( strpos( $mail->headers['cc'], ',' ) === FALSE )
		{
			$mail->headers['cc'] = array( $mail->headers['cc'] );
		}
		else
		{
			$mail->headers['cc'] = explode( ',', $mail->headers['cc'] );
		}
		foreach ( $mail->headers['cc'] as $_cc )
		{
			if ( preg_match( "/.+? <(.+?)>/", $_cc, $matches ) )
			{
				$cc[] = htmlentities( $matches[1] );
			}
			else
			{
				$cc[] = htmlentities( trim( $_cc, '<> ' ) );
			}
		}
		$this->cc = str_replace( array( '&gt;', '&lt;' ), '', implode( ',', $cc ) );
		
		//-----------------------------------------
		// Ignore?
		//-----------------------------------------
		
		if ( !self::DEBUG_MODE )
		{
			$escapedFrom = $this->DB->addSlashes( $this->from );
			$log = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_incoming_email_log', 'where' => "log_email='{$escapedFrom}'" ) );
			if ( $log['log_id'] )
			{
				$oneMinuteAgo = time() - 60;
				if ( $log['log_time'] > $oneMinuteAgo )
				{
					$this->ignore = TRUE;
				}
				
				$this->DB->update( 'core_incoming_email_log', array( 'log_time' => time() ), "log_id={$log['log_id']}" );
				
				if ( $this->ignore )
				{
					return;
				}
			}
			else
			{
				$this->DB->insert( 'core_incoming_email_log', array( 'log_email' => $this->from, 'log_time' => time() ) );
			}
		}

		//--------------------------------------
		// Parse Body
		//--------------------------------------
		
		$this->message = '';
		$this->attachments = array();
											
		/* Normal message */
		if ( $mail->ctype_primary == 'text' )
		{
			$this->message = str_replace( array( "\n", "\r", "\r\n" ), '<br />', $this->cleanMessage( $mail->body ) );
		}	
		/* Multipart message (may have attachments or just contain a plain and html version) */
		else
		{	
			$havePlainTextMessage = FALSE;
			foreach ( $mail->parts as $part )
			{
				if ( strtolower( $part->ctype_primary ) == 'multipart' AND strtolower( $part->ctype_secondary ) == 'alternative' )
				{
					$add = '';
					foreach ( $part->parts as $subpart )
					{
						$add = str_replace( array( "\n", "\r", "\r\n" ), '<br />', $subpart->body );
						if ( strtolower( $subpart->ctype_secondary ) == 'plain' ) // We prefer plain text
						{
							break;
						}
					}
					$this->message .= $add;
				}
				elseif ( $part->ctype_primary == 'text' )
				{
					if ( !$havePlainTextMessage )
					{
						$this->message .= str_replace( array( "\n", "\r", "\r\n" ), '<br />', $this->cleanMessage( $part->body ) );
						if ( $part->ctype_secondary == 'plain' )
						{
							$havePlainTextMessage = TRUE; // We prefer plain text
						}
					}
				}
				elseif ( $part->ctype_primary == 'multipart' and $part->ctype_secondary == 'related' )
				{
					foreach ( $part->parts as $subPart )
					{
						$this->_addAttachment( $subPart );
					}
				}
				else
				{
					$this->_addAttachment( $part );
				}
			}
		}
	}
	
	/**
	 * Add Attachment
	 *
	 * @param	string	Part of message
	 * @return	@e void
	 */
	private function _addAttachment( $part )
	{
		$mime = "{$part->ctype_primary}/{$part->ctype_secondary}";
		if ( isset( $this->types[ $mime ] ) )
		{
			foreach ( $this->types[ $mime ] as $data )
			{
				$name_parts = explode( '.', $part->ctype_parameters['name'] );
				$ext = array_pop( $name_parts );
				if ( strtolower( $data['atype_extension'] ) == strtolower( $ext ) and $data['atype_post'] )
				{
					/* Create the file */
					$masked_name = md5( uniqid( 'email' ) ) . "-{$part->ctype_parameters['name']}";
					while ( is_file( $this->upload_dir . "/{$masked_name}" ) )
					{
						$masked_name = md5( uniqid( 'email' ) . microtime() ) . "-{$part->ctype_parameters['name']}";
					}
					file_put_contents( $this->upload_dir . "/{$masked_name}", $part->body );
					
					/* Store attachment data */
					$this->akey++;
					$this->attachments[ $this->akey ] = array(
						'attach_ext'		=> $ext,
						'attach_file'		=> $part->ctype_parameters['name'],
						'attach_location'	=> $masked_name,
						'attach_is_image'	=> ( $part->ctype_primary == 'image' ) ? 1 : 0,
						'attach_date'		=> time(),
						'attach_filesize'	=> $part->d_parameters['size'],
						);
					$this->message .= "<!--ATTACHMENT:{$this->akey}-->";
					break;
				}
			}
		}
		else
		{
			die( "Invalid mine {$mime}" );
		}
	}
	
	/**
	 * Route
	 * Uses incoming Email rules to route Email
	 *
	 * @return	@e void
	 */
	public function route()
	{
		$unrouted = TRUE;
		$this->DB->build( array( 'select' => '*', 'from' => 'core_incoming_emails' ) );
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			switch ( $row['rule_criteria_field'] )
			{
				case 'to':
					$analyse = $this->to;
					break;
					
				case 'from':
					$analyse = $this->from;
					break;
					
				case 'sbjt':
					$analyse = $this->subject;
					break;
					
				case 'body':
					$analyse = $this->message;
					break;
			}
									
			$match = false;
			switch ( $row['rule_criteria_type'] )
			{
				case 'ctns':
					$match = (bool) ( strpos( $analyse, $row['rule_criteria_value'] ) !== FALSE );
					break;
					
				case 'eqls':
					if ( strpos( $analyse, ',' ) !== FALSE )
					{
						$match = (bool) in_array( $analyse, explode( ',', $analyse ) );
					}
					else
					{
						$match = (bool) ( $analyse == $row['rule_criteria_value'] );
					}
					break;
					
				case 'regx':
					$match = (bool) ( preg_match( "/{$row['rule_criteria_value']}/", $analyse ) == 1 );
					break;
			}
									
			if ( $match )
			{
				$unrouted = FALSE;
				if ( $row['rule_app'] != '--' )
				{
					$appdir = IPS_ROOT_PATH . 'applications_addon/ips/' . $row['rule_app'];
					if ( !is_dir( $appdir ) )
					{
						$appdir = IPS_ROOT_PATH . 'applications_addon/other/' . $row['rule_app'];
					}
					if ( !is_dir( $appdir ) )
					{
						$appdir = IPS_ROOT_PATH . 'applications/' . $row['rule_app'];
					}
					
					if ( is_file( $appdir . '/extensions/incomingEmails.php' ) )
					{
						$class = 'incomingEmails_' . $row['rule_app'];
						require_once( $appdir . '/extensions/incomingEmails.php' );/*noLibHook*/
						$class = new $class;
						$class->process( $this->DB, $this->to, $this->from, $this->subject, $this->message, $this->attachments, $this->raw, $this->cc );
					}
				}
				break;
			}
		}
				
		if ( $unrouted )
		{
			$apps = array();
			foreach ( glob( IPS_ROOT_PATH . 'applications/*' ) as $f )
			{
				if ( is_file( $f . '/extensions/incomingEmails.php' ) )
				{
					$bits = explode( '/', $f );
					$_appdir = array_pop( $bits );
					$apps[ $_appdir ] = $f;
				}
			}
			foreach ( glob( IPS_ROOT_PATH . 'applications_addon/ips/*' ) as $f )
			{
				if ( is_file( $f . '/extensions/incomingEmails.php' ) )
				{
					$bits = explode( '/', $f );
					$_appdir = array_pop( $bits );
					$apps[ $_appdir ] = $f;
				}
			}
			foreach ( glob( IPS_ROOT_PATH . 'applications_addon/other/*' ) as $f )
			{
				if ( is_file( $f . '/extensions/incomingEmails.php' ) )
				{
					$bits = explode( '/', $f );
					$_appdir = array_pop( $bits );
					$apps[ $_appdir ] = $f;
				}
			}
			
			$routed = FALSE;
			foreach ( $apps as $_appdir => $appdir )
			{
				require_once( $appdir . '/extensions/incomingEmails.php' );/*noLibHook*/
				$class = 'incomingEmails_' . $_appdir;
				$i = new $class;
				if ( $routed = $i->handleUnrouted( $this->DB, $this->to, $this->from, $this->subject, $this->message, $this->attachments, $this->raw, $this->cc ) )
				{
					break;
				}
			}
			
			if ( !$routed and $this->to != $this->from )
			{
				$unroutedMessage = @file_get_contents( DOC_IPS_ROOT_PATH . 'interface/email/unrouted.txt' );
				if ( $unroutedMessage )
				{
					$unroutedMessage = nl2br( $unroutedMessage ) . '<br /><br />';
					foreach ( explode( "<br />", $this->message ) as $line )
					{
						$unroutedMessage .= "> {$line}<br />";
					}
				
					$this->DB->insert( 'mail_queue', array(
						'mail_date'		=> time(),
						'mail_to'		=> $this->from,
						'mail_from'		=> $this->to,
						'mail_subject'	=> "Re: {$this->subject}",
						'mail_content'	=> $unroutedMessage,
						'mail_html_on'	=> TRUE
						) );
				}
			}
		}
	}
	
	/**
	 * Init DB
	 * Since this class may be being called when ipsRegistry
	 * is not set up, we may need to create a DB object
	 * 
	 * @return	@e void
	 * @see		route()
	 */
	protected function initDB()
	{
		/* Init */
		$INFO = array();
		@require( DOC_IPS_ROOT_PATH . 'conf_global.php' );/*noLibHook*/
		
		$this->DB_driver        = strtolower( $INFO['sql_driver'] );
		require_once( IPS_KERNEL_PATH . 'classDb' . ucwords( $this->DB_driver ) . '.php' );/*noLibHook*/
		$classname = "db_driver_" . $this->DB_driver;
			
		$this->DB = new $classname;
		$this->DB->obj['sql_database']			= $INFO['sql_database'];
		$this->DB->obj['sql_user']				= $INFO['sql_user'];
		$this->DB->obj['sql_pass']				= $INFO['sql_pass'];
		$this->DB->obj['sql_host']				= $INFO['sql_host'];
		$this->DB->obj['sql_charset']			= $INFO['sql_charset'];
		$this->DB->obj['sql_tbl_prefix']		= $INFO['sql_tbl_prefix'] ? $INFO['sql_tbl_prefix'] : '';
		
		$this->DB->connect();
	}
	
	/**
	 * Clean message
	 * Parses message and removes any nasty stuff
	 *
	 * @param	string		Message
	 * @return	@e string
	 */
	protected function cleanMessage( $txt )
	{
		require_once( IPS_KERNEL_PATH . 'HTMLPurifier/HTMLPurifier.auto.php' );/*noLibHook*/
		
		$purifier = new HTMLPurifier();
		return $purifier->purify( $txt );
	}
}