<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Send email using php mail() or SMTP
 * Last Updated: $Date: 2012-06-08 07:37:57 -0400 (Fri, 08 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10896 $
 *
 *
 * Example usage:
 * <code>
 * $email = new classEmail(
 * 						array(	'debug'			=> 1,
 * 								'method'		=> 'mail',
 * 								'html'			=> 1,
 * 								'debug_path'	=> '/tmp/_mail',
 * 								'charset'		=> 'utf-8',
 * 							)
 * 						);
 * $email->setFrom( "support@invisionpower.com", "This is\r\nan email" );
 * $email->setTo( "me@mydomain.com" );
 * $email->addBCC( "myfriend@mydomain.com" );
 * $email->addBCC( "myotherfriend@mydomain.com" );
 * $email->setSubject( "This is a test!!" );
 * $email->setBody( "<b>We have HTML capability!</b><br /><br /><i>But this is just a test...</i>" );
 * $email->sendMail();
 * </code>
 */
 
/**
 * Email class interface
 *
 */
interface interfaceEmail
{
	/**
	 * Constructor
	 *
	 * @param	array	Initiate class parameters
	 * @return	@e void
	 */
	public function __construct( $opts=array() );

	/**
	 * Clear stored data to prepare a new email. Useful
	 *	to prevent having to close/reopen SMTP connection
	 *	repeatedly.
	 *
	 * @return	@e void
	 */
	public function clearEmail();
	
	/**
	 * Clear stored errors to prepare a new email.
	 *
	 * @return	@e void
	 */
	public function clearError();
	
	/**
	 * Set the from email address
	 *
	 * @param	string		From email address
	 * @param	string		[Optional] From display
	 * @return	@e boolean
	 */
	public function setFrom( $email, $display='' );
	
	/**
	 * Set the 'to' email address
	 *
	 * @param	string		To email address
	 * @return	@e boolean
	 */
	public function setTo( $email );
	
	/**
	 * Add cc's
	 *
	 * @param	string		CC email address
	 * @return	@e boolean
	 */
	public function addCC( $email );
	
	/**
	 * Add bcc's
	 *
	 * @param	string		BCC email address
	 * @return	@e boolean
	 */
	public function addBCC( $email );
	
	/**
	 * Set the email subject
	 *
	 * @param	string		Email subject
	 * @return	@e boolean
	 */
	public function setSubject( $subject );
	
	/**
	 * Set the email body
	 *
	 * @param	string		Email body
	 * @return	@e boolean
	 */
	public function setBody( $body );
	
	/**
	 * Set a header manually
	 *
	 * @param	string		Header key
	 * @param	string		Header value
	 * @return	@e boolean
	 */
	public function setHeader( $key, $value );
	
	/**
	 * Send the mail (All appropriate params must be set by this point)
	 *
	 * @return	@e boolean
	 */
	public function sendMail();
	
	/**
	 * Add an attachment to the current email
	 *
	 * @param	string	File data
	 * @param	string	File name
	 * @param	string	File type (MIME)
	 * @return	@e void
	 */
	public function addAttachment( $data="", $name="", $ctype='application/octet-stream' );
}

/**
 * Email class
 *
 */
class classEmail implements interfaceEmail
{
	/**
	 * From email address 
	 *
	 * @var 	string
	 */
	protected $from			= "";
	
	/**
	 * From email address (displayed)
	 *
	 * @var 	string
	 */
	protected $from_display	= "";
	
	/**
	 * To email address
	 *
	 * @var 	string
	 */
	protected $to				= "";
	
	/**
	 * Email subject
	 *
	 * @var 	string
	 */
	protected $subject		= "";
	
	/**
	 * Email message contents
	 *
	 * @var 	string
	 */
	protected $message		= "";
	
	/**
	 * PHP mail() extra params
	 *
	 * @var 	string
	 */
	protected $extra_opts		= '';
	
	/**
	 * Plain text message contents
	 *
	 * @var 	string
	 */
	protected $pt_message		= "";
	
	/**
	 * Attachments: Parts
	 *
	 * @var 	array
	 */
	protected $parts			= array();
	
	/**
	 * CC Email addresses 
	 *
	 * @var 	array
	 */
	protected $cc			= array();

	/**
	 * BCC Email addresses 
	 *
	 * @var 	array
	 */
	protected $bcc			= array();
	
	/**
	 * Email headers
	 *
	 * @var 	array
	 */
	protected $mail_headers	= array();
	
	/**
	 * Header EOL
	 *  RFC specs state \r\n
	 *  However most servers seem to only support \n
	 *
	 * @var 	string
	 */
	const header_eol		= "\n";
	
	/**
	 * Attachments: Multi-part
	 *
	 * @var 	string
	 */
	protected $multipart		= "";
	
	/**
	 * Attachments: Boundry
	 *
	 * @var 	string
	 */
	protected $boundry		= "----=_NextPart_000_0022_01C1BD6C.D0C0F9F0";
	
	/**
	 * HTML email flag
	 *
	 * @var 	integer
	 */
	protected $html_email		= 0;
	
	/**
	 * Email character set
	 *
	 * @var 	string
	 */
	protected $char_set		= 'utf-8';
	
	/**
	 * SMTP: Resource
	 *
	 * @var 	resource
	 */
	protected $smtp_fp		= null;
	
	/**
	 * SMTP: Message
	 *
	 * @var 	string
	 */
	public $smtp_msg		= "";
	
	/**
	 * SMTP: Port
	 *
	 * @var 	integer
	 */
	protected $smtp_port		= 25;
	
	/**
	 * SMTP: Host
	 *
	 * @var 	string
	 */
	protected $smtp_host		= "localhost";
	
	/**
	 * SMTP: Username 
	 *
	 * @var 	string
	 */
	protected $smtp_user		= "";
	
	/**
	 * SMTP: Password
	 *
	 * @var 	string
	 */
	protected $smtp_pass		= "";
	
	/**
	 * SMTP: HELO or EHLO
	 *
	 * @var 	string
	 */
	public $smtp_helo		= "HELO";
	
	/**
	 * SMTP: Return code
	 *
	 * @var 	string
	 */
	public $smtp_code		= "";
	
	/**
	 * SMTP: Wrap email addresses in brackets flag
	 *
	 * @var 	boolean
	 */
	protected $wrap_brackets	= false;
	
	/**
	 * Default email method (mail or smtp)
	 *
	 * @var 	string
	 */
	protected $mail_method	= 'mail';
	
	/**
	 * Dump email to flat file for testing
	 *
	 * @var 	integer
	 */
	protected $temp_dump		= 0;
	
	/**
	 * Path for email dumps
	 *
	 * @var 	string
	 */
	protected $temp_dump_path	= '';
	
	/**
	 * Error message
	 *
	 * @var 	string
	 */
	public $error_msg		= '';
	
	/**
	 * Error description
	 *
	 * @var 	string
	 */
	public $error_help		= '';
	
	/**
	 * Error flag
	 *
	 * @var 	boolean
	 */
	public $error			= false;
	
	/**
	 * Constructor
	 *
	 * @param	array	Initiate class parameters (method (smtp,mail), debug, debug_path, html, charset, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_helo, wrap_brackets, extra_opts)
	 * @return	@e void
	 */
	public function __construct( $opts=array() )
	{
		$this->mail_method		= ( $opts['method'] AND in_array( strtolower($opts['method']), array( 'smtp', 'mail' ) ) )  ? strtolower($opts['method']) : 'mail';
		$this->temp_dump		= !empty($opts['debug']) ? 1 : 0;
		$this->temp_dump_path	= !empty($opts['debug_path']) ? $opts['debug_path'] : '';
		$this->html_email		= !empty($opts['html']) ? 1 : 0;
		$this->char_set			= !empty($opts['charset']) ? $opts['charset'] : 'utf-8';

		$this->smtp_host		= !empty($opts['smtp_host']) ? $opts['smtp_host'] : '';
		$this->smtp_port		= !empty($opts['smtp_port']) ? intval($opts['smtp_port']) : 25;
		$this->smtp_user		= !empty($opts['smtp_user']) ? $opts['smtp_user'] : '';
		$this->smtp_pass		= !empty($opts['smtp_pass']) ? $opts['smtp_pass'] : '';
		$this->smtp_helo		= !empty($opts['smtp_helo']) ? $opts['smtp_helo'] : 'HELO';
		$this->wrap_brackets	= !empty($opts['wrap_brackets']) ? true : false;
		$this->extra_opts		= !empty($opts['extra_opts']) ? $opts['extra_opts'] : '';
		
		if ( ! defined( 'EMAIL_MULTI_HEADERS_IN_BODY' ) )
		{
			define( 'EMAIL_MULTI_HEADERS_IN_BODY', false );
		}
		
		if ( $this->mail_method == 'smtp' && ! $this->temp_dump )
		{
			$this->_smtpConnect();
		}
	}
	
	/**
	 * Destructor
	 *
	 * @return	@e void
	 */
	public function __destruct()
	{
		if( $this->mail_method == 'smtp' )
		{
			$this->_smtpDisconnect();
		}
	}
	
	/**
	 * Sets the HTML version of the email
	 *
	 * @param	string	$content	HTML content of the email
	 * @return	@e void
	 */
	public function setHtmlContent( $content )
	{
		$this->message = $content;
	}
	
	/**
	 * Sets plain text version of the email
	 *
	 * @param	string	$content	Plain text content of the email
	 * @return	@e void
	 */
	public function setPlainTextContent( $content )
	{
		$this->pt_message = $content;
	}
	
	/**
	 * Sets whether or not this is a HTML email
	 *
	 * @param	boolean	$boolean	True = html email, False = plain text email
	 * @return	@e void
	 */
	public function setHtmlEmail( $boolean=null )
	{
		$this->html_email = ( $boolean ) ? true : false;
	}
	
	/**
	 * Clear stored data to prepare a new email. Useful
	 *	to prevent having to close/reopen SMTP connection
	 *	repeatedly.
	 *
	 * @return	@e void
	 */
	public function clearEmail()
	{
		$this->from			= '';
		$this->from_display	= '';
		$this->to			= '';
		$this->cc			= array();
		$this->bcc			= array();
		$this->subject		= '';
		$this->message		= '';
		$this->pt_message	= '';
		$this->parts		= array();
		$this->mail_headers	= array();
		$this->multipart	= '';
	}
	
	/**
	 * Clear stored errors to prepare a new email.
	 *
	 * @return	@e void
	 */
	public function clearError()
	{
		$this->error_msg	= '';
		$this->error_help	= '';
		$this->error		= false;
	}

	/**
	 * Set the from email address
	 *
	 * @param	string		From email address
	 * @param	string		[Optional] From display
	 * @return	@e boolean
	 */
	public function setFrom( $email, $display='' )
	{
		$this->from			= $this->_cleanEmail( $email );
		
		if ( $display )
		{
			$sfrom	 = $this->_encodeHeaders( array( 'FDisplay' => $display ) );
			$display = $sfrom['FDisplay'];
		}
		
		$this->from_display = $display;
		
		return true;
	}
	
	/**
	 * Set a header manually
	 *
	 * @param	string		Header key
	 * @param	string		Header value
	 * @return	@e boolean
	 */
	public function setHeader( $key, $value )
	{
		$this->mail_headers[ $key ]	= $value;
		
		return true;
	}
	
	/**
	 * Set the 'to' email address
	 *
	 * @param	string		To email address
	 * @return	@e boolean
	 */
	public function setTo( $email )
	{
		$this->to			= $this->_cleanEmail( $email );
		
		return true;
	}
	
	/**
	 * Add cc's
	 *
	 * @param	string		CC email address
	 * @return	@e boolean
	 */
	public function addCC( $email )
	{
		$this->cc[]		= $this->_cleanEmail( $email );
		
		return true;
	}
	
	/**
	 * Add bcc's
	 *
	 * @param	string		BCC email address
	 * @return	boolean
	 */
	public function addBCC( $email )
	{
		$this->bcc[]		= $this->_cleanEmail( $email );
		
		return true;
	}
	
	/**
	 * Set the email subject
	 *
	 * @param	string		Email subject
	 * @return	@e boolean
	 */
	public function setSubject( $subject )
	{
		/* Fix encoded quotes, etc */
		$subject = str_replace( '&quot;', '"', $subject );
		$subject = str_replace( '&#039;', "'", $subject );
		$subject = str_replace( '&#39;' , "'", $subject );
		$subject = str_replace( '&#33;' , "!", $subject );
		$subject = str_replace( '&#36;' , "$", $subject );
		
		if( $this->mail_method != 'smtp' )
		{
			$sheader	= $this->_encodeHeaders( array( 'Subject' => $subject ) );
			$subject	= $sheader['Subject'];
		}
		
		$this->subject		= $subject;
		
		return true;
	}
	
	/**
	 * Set the email body.  This does not need to be called if you have called setHtmlContent() or setPlainTextContent().
	 *
	 * @param	string		Email body
	 * @return	@e boolean
	 */
	public function setBody( $body )
	{
		$this->message		= $body;
		
		return true;
	}
	
	/**
	 * Clean an email address
	 *
	 * @param	string		Email address
	 * @return	@e string
	 */
	protected function _cleanEmail( $email )
	{
		$email		= str_replace( ' '	, '',	$email );
		$email		= str_replace( "\t"	, '',	$email );
		$email		= str_replace( "\r"	, '',	$email );
		$email		= str_replace( "\n"	, '',	$email );
		$email		= str_replace( ',,'	, ',',	$email );
		$email		= preg_replace( '#\#\[\]\'"\(\):;/\$!£%\^&\*\{\}#' , "", $email  );
		
		return $email;
	}

	/**
	 * Send the mail (All appropriate params must be set by this point)
	 *
	 * @return	@e boolean
	 */
	public function sendMail()
	{
		//-----------------------------------------
		// Build headers
		//-----------------------------------------

		$this->_buildHeaders();
				
		//-----------------------------------------
		// Verify params are all set
		//-----------------------------------------
		
		if( !$this->to OR !$this->from OR !$this->subject )
		{
			$this->_fatalError( "From, to, or subject empty" );
			return false;
		}
		
		if( EMAIL_MULTI_HEADERS_IN_BODY && $this->mail_method != 'smtp' && strstr( $this->rfc_headers, $this->boundry ) )
		{
			/* We need to move some stuff around here. PHP Mail likes the message to contain the multiparts but we still need certain headers including boundry*/
			$b = preg_quote( $this->boundry, '#' );
			
			preg_match( "#\n--" . $b . "(.*)$#s", $this->rfc_headers, $matches );
			
			$message = $matches[0];
			$headers = str_replace( $matches[0], '', $this->rfc_headers );
			
			/* strip off 'this is a mime message' */
			preg_match( "#^(.*" . $b . '")#s', $headers, $hMatches );
			
			$headers = $hMatches[0];
			
			$this->message     = $message;
			$this->rfc_headers = $headers;
		}
		
		//-----------------------------------------
		// Debugging
		//-----------------------------------------
		
		if( $this->temp_dump == 1 )
		{
			$debug	= $this->subject . "\n------------\n" . $this->rfc_headers . "\n\n" . $this->message;

			if( !is_dir( $this->temp_dump_path ) )
			{
				@mkdir( $this->temp_dump_path, IPS_FOLDER_PERMISSION );
				@chmod( $this->temp_dump_path, IPS_FOLDER_PERMISSION );
			}
			
			if( !is_dir( $this->temp_dump_path ) )
			{
				$this->_fatalError( "Debugging enabled, but debug path does not exist and cannot be created" );
				return false;
			}
			
			$pathy = $this->temp_dump_path . '/' . date("M-j-Y") . '-' . time() . str_replace( '@', '+', $this->to ) . uniqid( '_' ) . '.php';
			
			$fh = @fopen( $pathy, 'w' );
			@fputs( $fh, $debug, strlen($debug) );
			@fclose( $fh );
		}
		else
		{
			//-----------------------------------------
			// PHP mail()
			//-----------------------------------------
			
			if( $this->mail_method != 'smtp' )
			{
				/* Fifth parameter not supported in safe mode 
			 		@link http://community.invisionpower.com/tracker/issue-30145-php-mail-and-safe-mode */
				if( ( defined('SAFE_MODE_ON') AND SAFE_MODE_ON ) OR $this->settings['safe_mode_skins'] )
				{
					if ( ! @mail( $this->to, $this->subject, $this->message, $this->rfc_headers ) )
					{
						$this->_fatalError( "Could not send the email", "Failed at 'mail' command (SAFE MODE)" );
					}
				}
				else if ( ! @mail( $this->to, $this->subject, $this->message, $this->rfc_headers, $this->extra_opts ) )
				{
					if ( ! @mail( $this->to, $this->subject, $this->message, $this->rfc_headers ) )
					{
						$this->_fatalError( "Could not send the email", "Failed at 'mail' command" );
					}
				}
			}
			
			//-----------------------------------------
			// SMTP
			//-----------------------------------------
			
			else
			{
				$this->_smtpSendMail();
			}
		}
		
		$this->clearEmail();
		
		return $this->error ? false : true;
	}

	/**
	 * Fatal error handler
	 *
	 * @param	string	Error Message
	 * @param	string	Error Help / Description
	 * @return	@e boolean
	 */
	protected function _fatalError( $msg, $help="" )
	{
		$this->error		= true;
		$this->error_msg	= $msg;
		$this->error_help	= $help;
		
		return false;
	}


	/*-------------------------------------------------------------------------*/
	// HEADERS
	/*-------------------------------------------------------------------------*/
	
	/**
	 * Build the multipart headers for the email
	 *
	 * @return	@e string
	 */
	protected function _buildMultipart() 
	{
		$multipart	= '';
		
		for( $i = sizeof( $this->parts ) - 1 ; $i >= 0 ; $i-- )
		{
			$multipart .= self::header_eol . $this->_encodeAttachment( $this->parts[$i] ) . "--" . $this->boundry;
		}
		
		return $multipart . "--\n";
	}

	/**
	 * ENCODE HEADERS - RFC2047
	 *
	 * @param	array 			Array of headers
	 * @return	@e array
	 * @see		http://www.faqs.org/rfcs/rfc822.html
	 * @see		http://www.faqs.org/rfcs/rfc2045
	 * @see		http://www.faqs.org/rfcs/rfc2047
	 * @see		http://us2.php.net/manual/en/function.mail.php#27997
	 */
	protected function _encodeHeaders( $headers = array() )
	{	
		$enc_headers = count($headers) ? $headers : $this->mail_headers;
		
        foreach( $enc_headers as $header => $value) 
        {
        	$orig_value	= $value;
        	
        	//-----------------------------------------
        	// MTAs seem to dislike 'From' encoded
        	//  so we just strip board name and continue
        	//-----------------------------------------
        	
			if( $header == 'From' OR $header == 'Content-Type' OR $header == 'Content-Disposition' OR $header == 'Content-Transfer-Encoding' OR $header == 'v' )
			{
				if ( ! count($headers) )
				{
					$this->mail_headers[ $header ]	= $orig_value;
				}
				
				$enc_headers[ $header ]			= $orig_value;
				
				continue;
			}
			
			//-----------------------------------------
			// We don't want to keep subject in the
			// headers for php mail
			//-----------------------------------------
			
			if( $this->mail_method != 'smtp' AND $header == 'Subject' )
			{
				unset($this->mail_headers[ $header ]);
			}
			
			//-----------------------------------------
			// Don't bother encoding unless we have chars
			//  that need to be encoded
			//-----------------------------------------
			
			if( !preg_match( '/(\w*[\x80-\xFF]+\w*)/', $value ) )
			{
				if( $header != 'Subject' )
				{
					$this->mail_headers[ $header ]	= $orig_value;
				}
				
				$enc_headers[ $header ]			= $orig_value;
				
				continue;
			}

			//-----------------------------------------
			// Base64 encoding from example at php.net
			//-----------------------------------------
			
        	$start		= '=?' . $this->char_set . '?B?';
        	$end		= '?=';
        	$spacer		= $end . self::header_eol . ' ' . $start;
        	$length		= 75 - strlen($start) - strlen($end);
        	$length		= $length - ($length % 4);
        	
        	$value		= base64_encode($value);
        	
        	/* Chunking confuses some email clients with subject */
        	if ( $header != 'Subject' )
        	{
        		$value		= chunk_split( $value, $length, $spacer );
        		$spacer		= preg_quote($spacer);
        		$value		= preg_replace( "/" . $spacer . "$/", "", $value );
        	}

        	$value		= $start . $value . $end;

            if( !count($headers) AND ( $this->mail_method == 'smtp' OR $header != 'Subject' OR $header != 'FDisplay' ) )
            {
            	$this->mail_headers[ $header ]	= $value;
        	}
        	else
        	{
	        	$enc_headers[ $header ]			= $value;
        	}
        }
        
        return $enc_headers;
    }
    
	/**
	 * Build the email headers (MIME, Charset, From, BCC, To, Subject, etc)
	 *
	 * @return	@e void
	 */
	protected function _buildHeaders()
	{
		$extra_headers		= array();
		$extra_headers_rfc	= "";
		
		//-----------------------------------------
		// HTML (hitmuhl)
		// If we're sending HTML messages, then
		// we'll add the plain text message along with
		// it for non HTML browsers
		//-----------------------------------------
		
		if ( ! $this->pt_message )
		{
			$this->pt_message = $this->message;
			$this->pt_message = str_replace( "<br />", "\n", $this->pt_message );
			$this->pt_message = str_replace( "<br>"  , "\n", $this->pt_message );
			$this->pt_message = strip_tags( $this->pt_message );
			
			$this->pt_message = html_entity_decode( $this->pt_message, ENT_QUOTES );
			$this->pt_message = str_replace( '&#092;', '\\', $this->pt_message );
			$this->pt_message = str_replace( '&#036;', '$', $this->pt_message );
		}
		
		//-----------------------------------------
		// Start mail headers
		//-----------------------------------------
		
		$this->mail_headers['MIME-Version']			= "1.0";
		$this->mail_headers['Date'] 				= date( "r" );
		$this->mail_headers['Return-Path']			= $this->from;
		$this->mail_headers['X-Priority']			= "3";
		//$this->mail_headers['X-MSMail-Priority']	= "Normal";
		$this->mail_headers['X-Mailer']				= "IPS PHP Mailer";
		
		//-----------------------------------------
		// Message-ID
		//-----------------------------------------
		
		if( $this->mail_method != 'smtp' )
		{
			$this->mail_headers['Message-ID']		= "<" . md5(microtime(true)) . "@" . $_SERVER['HTTP_HOST'] . ">";
		}
		
		//-----------------------------------------
		// From and to...
		// @link	http://community.invisionpower.com/tracker/issue-22474-mail-delivery-method-character/
		//-----------------------------------------
		
		if( $this->from_display AND !preg_match( '/(\w*[\x80-\xFF]+\w*)/', $this->from_display ) )
		{
			$this->mail_headers['From']		= '"' . $this->from_display . '" <' . $this->from . '>';
		}
		else
		{
			$this->mail_headers['From']		= '<' . $this->from . '>';
		}
				
		if ( $this->mail_method != 'smtp' )
		{
			if( count( $this->cc ) > 0 )
			{
				$this->mail_headers['Cc']	= implode( "," , $this->cc );
			}
			if( count( $this->bcc ) > 0 )
			{
				$this->mail_headers['Bcc']	= implode( "," , $this->bcc );
			}
		}
		else
		{
			if ( $this->to )
			{
				$this->mail_headers['To']	= $this->to;
			}

			$this->mail_headers['Subject']	= $this->subject;
			
			if( count( $this->cc ) > 0 )
			{
				$this->mail_headers['Cc']	= implode( "," , $this->cc );
			}
		}

		//-----------------------------------------
		// Attachments?
		//-----------------------------------------
		
		if ( count($this->parts) > 0 )
		{
			if ( ! $this->html_email )
			{
				$extra_headers[0]['Content-Type']	= "multipart/mixed;\n\tboundary=\"".$this->boundry."\"";
				$extra_headers[0]['notencode']		= "\n\nThis is a MIME encoded message.\n\n--".$this->boundry."\n";
				$extra_headers[1]['Content-Type']	= "text/plain;\n\tcharset=\"".$this->char_set."\"";
				$extra_headers[1]['notencode']		= "\n\n".$this->message."\n\n--".$this->boundry;
			}
			else
			{
				$extra_headers[0]['Content-Type']	= "multipart/mixed;\n\tboundary=\"".$this->boundry."\"";
				$extra_headers[0]['notencode']		= "\n\nThis is a MIME encoded message.\n\n--".$this->boundry."\n";
				$extra_headers[1]['Content-Type']	= "text/html;\n\tcharset=\"".$this->char_set."\"";
				$extra_headers[1]['notencode'] 		= "\n\n".$this->message."\n\n--".$this->boundry;
			}
			
			$extra_headers[2]['notencode'] 			= $this->_buildMultipart();
			
			reset($extra_headers);
			
			foreach( $extra_headers as $subset => $the_header )
			{
				foreach( $the_header as $k => $v )
				{
					if( $k == 'notencode' )
					{
						$extra_headers_rfc .= $v;
					}
					else
					{
						$v = $this->_encodeHeaders( array( 'v' => $v ) );
						
						$extra_headers_rfc .= $k . ': ' . $v['v'] . self::header_eol;
					}
				}
			}
			
			$this->message = "";
		}
		else
		{
			//-----------------------------------------
			// HTML (hitmuhl) ?
			//-----------------------------------------
			
			if ( $this->html_email )
			{
				$extra_headers[0]['Content-Type']	= "multipart/alternative;\n\tboundary=\"".$this->boundry."\"";
				$extra_headers[0]['notencode']		= "\n\nThis is a MIME encoded message.\n\n--".$this->boundry."\n";
				$extra_headers[1]['Content-Type']	= "text/plain;\n\tcharset=\"".$this->char_set."\"";
				$extra_headers[1]['notencode']		= "\n\n".$this->pt_message."\n\n--".$this->boundry."\n";
				$extra_headers[2]['Content-Type']	= "text/html;\n\tcharset=\"".$this->char_set."\"";
				$extra_headers[2]['notencode']		= "\n\n".$this->message."\n\n--".$this->boundry."--";
				
				reset($extra_headers);
				
				foreach( $extra_headers as $subset => $the_header )
				{
					foreach( $the_header as $k => $v )
					{
						if( $k == 'notencode' )
						{
							$extra_headers_rfc .= $v;
						}
						else
						{
							$v = $this->_encodeHeaders( array( $k => $v ) );

							$extra_headers_rfc .= $k . ': ' . $v[ $k ] . self::header_eol;
						}
					}
				}
				
				unset( $this->mail_headers['Content-Type'] );
				
				$this->message = "";
			}
			else
			{
				$this->mail_headers['Content-type']	= 'text/plain; charset="'.$this->char_set.'"';
			}
		}
	
		$this->_encodeHeaders();
		
		foreach( $this->mail_headers as $k => $v )
		{
			$this->rfc_headers .= $k . ": " . $v . self::header_eol;
		}
		
		//-----------------------------------------
		// Attachments extra?
		//-----------------------------------------
		
		if( $extra_headers_rfc )
		{
			$this->rfc_headers .= $extra_headers_rfc;
		}
	}
    
    
	/*-------------------------------------------------------------------------*/
	// SMTP Methods
	/*-------------------------------------------------------------------------*/
    
	/**
	 * SMTP connect
	 *
	 * @return	@e boolean
	 */
    protected function _smtpConnect()
	{
		$this->smtp_fp = @fsockopen( $this->smtp_host, intval($this->smtp_port), $errno, $errstr, 30 );
		
		if ( ! $this->smtp_fp )
		{
			$this->_smtpError( "Could not open a socket to the SMTP server ({$errno}:{$errstr})" );
			return false;
		}
		
		$this->_smtpGetLine();
		
		$this->smtp_code = substr( $this->smtp_msg, 0, 3 );
		
		if ( $this->smtp_code == 220 )
		{
			//-----------------------------------------
			// HELO!, er... HELLO!
			//-----------------------------------------
			
			$this->_smtpSendCmd( "{$this->smtp_helo} " . $this->smtp_host );
			
			if ( $this->smtp_code != 250 )
			{
				$this->_smtpError( "HELO (using: {$this->smtp_helo})" );
				return false;
			}
			
			//-----------------------------------------
			// Do you like my user!
			//-----------------------------------------
			
			if ( $this->smtp_user AND $this->smtp_pass )
			{
				$this->_smtpSendCmd( "AUTH LOGIN" );
				
				if ( $this->smtp_code == 334 )
				{
					$this->_smtpSendCmd( base64_encode($this->smtp_user) );
					
					if ( $this->smtp_code != 334  )
					{
						$this->_smtpError( "Username not accepted from the server" );
						return false;
					}
					
					$this->_smtpSendCmd( base64_encode($this->smtp_pass) );
					
					if ( $this->smtp_code != 235 )
					{
						$this->_smtpError( "Password not accepted from the server" );
						return;
					}
				}
				else
				{
					$this->_smtpError( "This server does not support authorisation" );
					return;
				}
			}
		}
		else
		{
			$this->_smtpError( "Could not connect to the SMTP server" );
			return false;
		}
		
		return true;
	}
	
	/**
	 * SMTP disconnect
	 *
	 * @return	@e boolean
	 */
    protected function _smtpDisconnect()
	{
		$this->_smtpSendCmd( "quit" );

		if ( $this->smtp_code != 221 )
		{
			$this->_smtpError( "Unable to exit SMTP server with 'quit' command" );
			return false;
		}
		
		return @fclose( $this->smtp_fp );
	}
	
	/**
	 * SMTP: Get next line
	 *
	 * @return	@e void
	 */
	protected function _smtpGetLine()
	{
		$this->smtp_msg = "";
		
		while ( $line = @fgets( $this->smtp_fp, 515 ) )
		{
			$this->smtp_msg .= $line;
			
			if ( substr($line, 3, 1) == " " )
			{
				break;
			}
		}
	}
	
	/**
	 * SMTP: Send command
	 *
	 * @param	string		SMTP command
	 * @return	@e boolean
	 */
	protected function _smtpSendCmd( $cmd )
	{
		$this->smtp_msg  = "";
		$this->smtp_code = "";
		
		@fputs( $this->smtp_fp, $cmd . "\r\n" );
		
		$this->_smtpGetLine();
		
		$this->smtp_code = substr( $this->smtp_msg, 0, 3 );
		
		return $this->smtp_code == "" ? false : true;
	}
	
	/**
	 * Encode data and make it safe for SMTP transport
	 *
	 * @param	string	Raw Data
	 * @return	@e string
	 */
	protected function _smtpCrlfEncode( $data )
	{
		$data .= "\n";
		$data  = str_replace( "\n", "\r\n", str_replace( "\r", "", $data ) );
		$data  = str_replace( "\n.\r\n" , "\n. \r\n", $data );
		
		return $data;
	}
	
	/**
	 * SMTP: Error handler
	 *
	 * @param	string		SMTP error
	 * @return	@e boolean
	 */
	protected function _smtpError( $err="" )
	{
		//$this->smtp_msg = $err;
		$this->_fatalError( $err );
		return false;
	}
	
	/**
	 * SMTP: Sends the SMTP email
	 *
	 * @return	@e void
	 */
	protected function _smtpSendMail()
	{
		$data = $this->_smtpCrlfEncode( $this->rfc_headers . "\n\n" . $this->message );

		//-----------------------------------------
		// Wrap in brackets
		//-----------------------------------------
		
		if ( $this->wrap_brackets )
		{
			if ( ! preg_match( "/^</", $this->from ) )
			{
				$this->from = "<" . $this->from . ">";
			}
		}
		
		//-----------------------------------------
		// From:
		//-----------------------------------------
		
		$this->_smtpSendCmd( "MAIL FROM:" . $this->from );
		
		if ( $this->smtp_code != 250 )
		{
			$this->_smtpError( "Mail from command failed" );
			return false;
		}
		
		$to_arry = array( $this->to );
		
		if( count( $this->cc ) > 0 )
		{
			foreach( $this->cc as $cc )
			{
				$to_arry[] = $cc;
			}
		}
		
		if( count( $this->bcc ) > 0 )
		{
			foreach( $this->bcc as $bcc )
			{
				$to_arry[] = $bcc;
			}
		}
		
		//-----------------------------------------
		// To:
		//-----------------------------------------
		
		foreach( $to_arry as $to_email )
		{
			if ( $this->wrap_brackets )
			{
				$this->_smtpSendCmd( "RCPT TO:<" . $to_email . ">" );
			}
			else
			{
				$this->_smtpSendCmd( "RCPT TO:" . $to_email );
			}
			
			if ( $this->smtp_code != 250 )
			{
				$this->_smtpError( "Incorrect email address: $to_email" );
			}
		}
		
		//-----------------------------------------
		// SEND MAIL!
		//-----------------------------------------
		
		$this->_smtpSendCmd( "DATA" );
		
		if ( $this->smtp_code == 354 )
		{
			fputs( $this->smtp_fp, $data . "\r\n" );
		}
		else
		{
			$this->_smtpError( "Error writing email body to SMTP server");
			return false;
		}
		
		//-----------------------------------------
		// GO ON, NAFF OFF!
		//-----------------------------------------
		
		$this->_smtpSendCmd( "." );
		
		if ( $this->smtp_code != 250 )
		{
			$this->_smtpError( "Email was not sent successfully" );
			return false;
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// ATTACHMENTS
	/*-------------------------------------------------------------------------*/
	
	/**
	 * Add an attachment to the current email
	 *
	 * @param	string	File data
	 * @param	string	File name
	 * @param	string	File type (MIME)
	 * @return	@e void
	 */
	public function addAttachment( $data="", $name="", $ctype='application/octet-stream' )
	{
		$this->parts[] = array( 'ctype'  => $ctype,
								'data'   => $data,
								'encode' => 'base64',
								'name'   => $name
							  );
	}
	
	/**
	 * Encode an attachment
	 *
	 * @param	array	Raw data [ctype,encode,name,data]
	 * @return	@e string
	 */
	protected function _encodeAttachment( $part=array() )
	{
		$msg = chunk_split( base64_encode( $part['data'] ) );
		
		$headers 	= array();
		$header_str	= "";

		$headers['Content-Type'] 				= $part['ctype'] . ( $part['name'] ? "; name =\"".$part['name']."\"" : "" );
		$headers['Content-Transfer-Encoding'] 	= $part['encode'];
		$headers['Content-Disposition']			= "attachment; filename=\"".$part['name']."\"";
		
		$headers = $this->_encodeHeaders( $headers );
		
		foreach( $headers as $k => $v )
		{
			$header_str .= $k . ': ' . $v . self::header_eol;
		}
		
		$header_str .= "\n\n" . $msg . "\n";
		
		return $header_str;
	}
}