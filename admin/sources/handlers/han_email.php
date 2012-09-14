<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * API: Core
 * Last Updated: $Date: 2012-06-11 11:49:47 -0400 (Mon, 11 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10908 $
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class hanEmail
{
	/**
	 * Emailer object reference
	 *
	 * @var		object
	 */
	public $emailer;
	
	/**
	 * Email header
	 *
	 * @var		string
	 */
	public $header;
	
	/**
	 * Email footer
	 *
	 * @var		string
	 */
	public $footer;
	
	/**
	 * Email from
	 *
	 * @var		string
	 */
	public $from;
	
	/**
	 * Email to
	 *
	 * @var		string
	 */
	public $to;
	
	/**
	 * Email cc's
	 *
	 * @var	array
	 */
	public $cc		= array();
	
	/**
	 * Email bcc's
	 *
	 * @var	array
	 */
	public $bcc		= array();
	
	/**
	 * Email subject
	 *
	 * @var		string
	 */
	public $subject;
	
	/**
	 * Email body
	 *
	 * @var		string
	 */
	public $message;
	
	/**
	 * HTML Mode
	 *
	 * @var		bool
	 */
	public $html_email = FALSE;
		
	/**
	 * Temp word swapping array
	 *
	 * @var		array
	 */
	protected $_words;
	
	/**
	 * Headers to pass to email lib
	 *
	 * @var		array
	 */
	protected $temp_headers		= array();
	
	protected $_attachments       = array();
	protected $editor;
	protected $plainTextTemplate  = '';
	protected $htmlTemplate		  = '';
	protected $htmlWrapper		  = '';
	protected $_loadedHtmlTemplateClass = '';
	
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
	/**#@-*/
	
	/**
	 * Construct
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		
		/* Set up default handler */
		$this->setHtmlEmail( $this->settings['email_use_html'] );
	}
	
	/**
	 * Sets whether or not this is a HTML email
	 * @param boolean duh $boolean
	 */
	public function setHtmlEmail( $boolean=null )
	{
		$this->html_email = ( $boolean ) ? true : false;
		
		if ( is_object( $this->emailer ) )
		{
			$this->emailer->setHtmlEmail( $boolean );
		}
	}
	
	/**
	 * Init method (setup stuff)
	 *
	 * @return	@e void
	 */
    public function init()
    {
		$this->header   = $this->settings['email_header'] ? $this->settings['email_header'] : '';
		$this->footer   = $this->settings['email_footer'] ? $this->settings['email_footer'] : '';
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classEmail.php', 'classEmail' );

		$this->emailer = new $classToLoad( array( 'debug'			=> $this->settings['fake_mail'] ? $this->settings['fake_mail'] : '0',
										 		  'debug_path'		=> DOC_IPS_ROOT_PATH . '_mail',
										 		  'smtp_host'		=> $this->settings['smtp_host'] ? $this->settings['smtp_host'] : 'localhost',
										 		  'smtp_port'		=> intval($this->settings['smtp_port']) ? intval($this->settings['smtp_port']) : 25,
										 		  'smtp_user'		=> $this->settings['smtp_user'],
										 		  'smtp_pass'		=> $this->settings['smtp_pass'],
										 		  'smtp_helo'		=> $this->settings['smtp_helo'],
										 		  'method'			=> $this->settings['mail_method'],
										 		  'wrap_brackets'	=> $this->settings['mail_wrap_brackets'],
										 		  'extra_opts'		=> $this->settings['php_mail_extra'],
										 		  'charset'			=> IPS_DOC_CHAR_SET,
										 		  'html'			=> $this->html_email ) );
    }
    
    /**
     * Clear out any temporary headers
     *
     * @return	@e void
     */
    public function clearHeaders()
    {
    	$this->temp_headers	= array();
    }
    
    /**
     * Manually set an email header
     *
     * @param	string	Header key
     * @param	string	Header value
     * @return	@e void
     */
    public function setHeader( $key, $value )
    {
    	$this->temp_headers[ $key ]	= $value;
    }
    
	/**
	 * Send an email
	 *
	 * @return	boolean		Email sent successfully
	 */
	public function sendMail()
	{	
		$this->init();
		
		if( $this->emailer->error )
		{
			return $this->fatalError( $this->emailer->error_msg, $this->emailer->error_help );
		}
		
		/* Add attachments if any */
		if ( count( $this->_attachments ) )
		{
			foreach( $this->_attachments as $a )
			{
				$this->emailer->addAttachment( $a[0], $a[1], $a[2] );
			}
		}
		
		$this->settings['board_name'] = $this->cleanMessage($this->settings['board_name']);
		
		$this->emailer->setFrom( $this->from ? $this->from : $this->settings['email_out'], $this->settings['board_name'] );
		$this->emailer->setTo( $this->to );
		
		foreach( $this->cc as $cc )
		{
			$this->emailer->addCC( $cc );
		}
		foreach( $this->bcc as $bcc )
		{
			$this->emailer->addBCC( $bcc );
		}
		
		if( count($this->temp_headers) )
		{
			foreach( $this->temp_headers as $k => $v )
			{
				$this->emailer->setHeader( $k, $v );
			}
		}

		//-----------------------------------------
		// Added strip_tags for the subject 4.16.2010
		// so we can have links in subject for inline
		// notifications and still use the subject
		//-----------------------------------------
		
		$this->emailer->setSubject( $this->_cleanSubject($this->subject) );
		
		/* If we're sending a HTML email, we need to manually send the plain text and HTML versions */
		if ( $this->html_email and $this->htmlTemplate )
		{
			/* Dynamically replace subject in template */
			$this->htmlTemplate = str_ireplace( array('<#subject#>'), IPSText::utf8ToEntities( $this->_cleanSubject($this->subject) ), $this->htmlTemplate );
			$this->emailer->setPlainTextContent( $this->plainTextTemplate );
			$this->emailer->setHtmlContent( $this->htmlTemplate );
		}
		else if ( $this->message && ! $this->plainTextTemplate )
		{
			/* Older methods pass message directly */
			$this->emailer->setBody( $this->message );
		}
		else
		{
			$this->emailer->setBody( $this->plainTextTemplate );
		}
		
		$this->emailer->sendMail();
		
		/* Clear out stuffs */
		$this->clearContent();
		
		// Unset HTML setting to remain backwards compatibility
		//$this->html_email = FALSE;
		
		if( $this->emailer->error )
		{
			return $this->fatalError( $this->emailer->error_msg, $this->emailer->error_help );
		}
		
		return true;
	}
	
	/**
	 * Set the plain text template
	 * @param string $string
	 */
	public function setPlainTextTemplate( $string )
	{
		/* Reset message too */
		$this->message           = '';
		
		$this->plainTextTemplate = $string;
	}
	
	/**
	 * Set the HTML template
	 * @param string $string
	 */
	public function setHtmlTemplate( $string )
	{
		/* Reset message too */
		$this->message           = '';
		
		$this->htmlTemplate = $string;
	}
	
	/**
	 * Return plain text content
	 * @return string
	 */
	public function getPlainTextContent()
	{
		return $this->plainTextTemplate;
	}
	
	/**
	 * Return HTML content
	 * @return string
	 */
	public function getHtmlContent()
	{
		return $this->htmlTemplate;
	}
	
	/**
	 * Removes all current stored messages, templates, etc.
	 */
	public function clearContent()
	{
		$this->setHtmlTemplate('');
		$this->setPlainTextTemplate('');
		$this->message  = '';
		$this->template = '';
	}
	
	/**
	 * Send custom html wrapper - HTML wrapper with <#content#> tag where content will be
	 * @param string $string
	 */
	public function setHtmlWrapper( $string )
	{
		$this->htmlWrapper = $string;
	}
	
	/**
	 * Retrieve an email template
	 *
	 * @param	string		Template key
	 * @param	string		Language to use
	 * @param	string		Language file to load
	 * @param	string		Application of language file
	 * @return	@e void
	 */
	public function getTemplate( $name, $language="", $lang_file='public_email_content', $app='core' )
	{
		/* Reset $this->message as legacy methods use $this->message when sending notifications, etc */
		$this->clearContent();
		
		/* Sometimes the lang_file & app can end up being empty - reset them */
		$lang_file	= empty($lang_file) ? 'public_email_content' : $lang_file;
		$app		= empty($app) ? 'core' : $app;
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if( $name == "" )
		{
			$this->error++;
			$this->fatalError( "A valid email template ID was not passed to the email library during template parsing", "" );
		}
		
		//-----------------------------------------
		// Default?
		//-----------------------------------------

		if( ! $language )
		{
			$language = IPSLib::getDefaultLanguage();
		}
		
		//-----------------------------------------
		// Check and get
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( $lang_file ), $app, $language, TRUE );
		
		//-----------------------------------------
		// Stored KEY?
		//-----------------------------------------
		
		if ( ! isset($this->lang->words[ $name ]) )
		{
			if ( $language == IPSLib::getDefaultLanguage() )
			{
				$this->fatalError( "Could not find an email template with an ID of '{$name}'", "" );
			}
			else
			{
				$this->registry->class_localization->loadLanguageFile( array( $lang_file ), $app, IPSLib::getDefaultLanguage() );
				
				if ( ! isset($this->lang->words[ $name ]) )
				{
					$this->fatalError( "Could not find an email template with an ID of '{$name}'", "" );
				}
			}
		}
		
		//-----------------------------------------
		// Subject?
		//-----------------------------------------
		
		if ( isset( $this->lang->words[ 'subject__'. $name ] ) )
		{
			$this->subject = stripslashes( $this->lang->words[ 'subject__'. $name ] );
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->template = stripslashes($this->lang->words[ $name ]) . stripslashes($this->lang->words['email_footer']);
		
		/* Returns it if called via setPlainTextTemplate */
		return $this->template;
	}
		
	/**
	 * Builds an email from a template, replacing variables
	 *
	 * @param	array		Replacement keys to values
	 * @param	bool		Do not "clean"
	 * @return	@e void
	 */
	public function buildMessage( $words, $noClean=false, $rawHtml=FALSE )
	{
		/* Init */
		$ptWords   = array();
		$htmlWords = array();
		
		/* Try this first */
		if ( ! $this->plainTextTemplate && ! $this->htmlTemplate && $this->message )
		{
			$this->setPlainTextTemplate( $this->message );
		}
		
		/* need to converge some stuff here */
		if ( ! $this->plainTextTemplate )
		{
			/* Sniff, sniff */
			if ( stristr( $this->template, '<br' ) )
			{
				$this->setHtmlTemplate( $this->template );
			}
			else
			{
				$this->setPlainTextTemplate( $this->template );
			}
		}
		
		/* HTML enabled but no specific template: Auto convert */
		if ( $this->html_email && ! $this->htmlTemplate )
		{
			/* It will be dynamically updated at the end */
			$this->setHtmlTemplate( $this->plainTextTemplate );
		}
		
		/* HTML email with HTML template but no plain text version */
		if ( $this->htmlTemplate && ! $this->plainTextTemplate )
		{
			$msg = $this->htmlTemplate;
			$msg = preg_replace( '/<#(.+?)#>/', '{{{-\1-}}}', $msg );
			$msg = str_replace( "<br />", "\n", $msg );
			$msg = str_replace( "<br>"  , "\n", $msg );
			$msg = IPSText::stripTags( $msg );
			
			$msg = html_entity_decode( $msg, ENT_QUOTES );
			$msg = str_replace( '&#092;', '\\', $msg );
			$msg = str_replace( '&#036;', '$', $msg );
			$msg = preg_replace( '/\{\{\{(.+?)\}\}\}/', '<#\1#>', $msg );
			
			$this->setPlainTextTemplate( $msg );
		}
		
		if ( $this->plainTextTemplate && ! $this->template && ( $this->html_email && ! $this->htmlTemplate ) )
		{
			$this->error++;
			$this->fatalError( "Could not build the email message, no template assigned", "Make sure a template is assigned first." );
		}
		
		/* Bit more clean up */
		$this->plainTextTemplate = str_replace( array( "\r\n", "\r", "\n" ), "\n", $this->plainTextTemplate );
		$this->htmlTemplate      = str_replace( array( "\r\n", "\r", "\n" ), "\n", $this->htmlTemplate );
		
		/* Add some default words */
		$words['BOARD_ADDRESS'] = $this->settings['board_url'] . '/index.' . $this->settings['php_ext'];
		$words['WEB_ADDRESS']   = $this->settings['home_url'];
		$words['BOARD_NAME']    = $this->settings['board_name'];
		$words['SIGNATURE']     = $this->settings['signature'] ? $this->settings['signature'] : '';
		
		/* Swap the words: 10.7.08 - Added replacements in subject */
		foreach( $words as $k => $v )
		{
			if ( ! $noClean )
			{
				$ptWords[ $k ] = $this->cleanMessage( $v );
			}
			
			$htmlWords[ $k ] = $v;
		}

		$this->_words = $ptWords;
		$this->plainTextTemplate = preg_replace_callback( "/<#(.+?)#>/", array( &$this, '_swapWords' ), $this->plainTextTemplate );
		
		$this->_words = $htmlWords;
		$this->htmlTemplate      = preg_replace_callback( "/<#(.+?)#>/", array( &$this, '_swapWords' ), str_replace( array( '&lt;#', '#&gt;' ), array( '<#', '#>' ), $this->htmlTemplate ) );
						
		$this->subject           = preg_replace_callback( "/<#(.+?)#>/", array( &$this, '_swapWords' ), $this->subject );
				
		$this->_words            = array();
		
		/* Final touches */
		$this->htmlTemplate      = $this->applyHtmlWrapper( $this->subject, ( $rawHtml ? $this->htmlTemplate : $this->convertTextEmailToHtmlEmail( $this->htmlTemplate ) ) );
		$this->htmlTemplate      = preg_replace( '#<!--hook\.([^\>]+?)-->#', '', $this->htmlTemplate );
		$this->htmlTemplate		 = $this->registry->getClass('output')->parseIPSTags( $this->htmlTemplate );
		$this->plainTextTemplate = IPSText::stripTags( stripslashes($this->lang->words['email_header']) ) . $this->plainTextTemplate . IPSText::stripTags( stripslashes($this->lang->words['email_footer']) );
		
		/* Some older apps use $this->message, so give them plaintext */
		$this->message = $this->plainTextTemplate;
	}
	
	/**
	 * Convert text email to HTML
	 * Pretty nifty method name too.
	 * 
	 * @param	string	Plain text email ready to go
	 * @return	string	We're all HTML'd up in here.
	 */
	public function convertTextEmailToHtmlEmail( $content )
	{
		//if ( strstr( $content, "\n" ) && ! preg_match( '#</([a-zA-Z]{1,6})>#', $content ) )
		//{
			$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
			
			$content = trim( $content, "\n" );
			
			/* It's probably HITMAL! */
			if ( ! is_object( $this->editor ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
				$this->editor = new $classToLoad();
				
				/* Reset HTML flags */
				$this->editor->setAllowHtml(false);
			}
			
			IPSText::getTextClass('bbcode')->parse_html   = 0;
			IPSText::getTextClass('bbcode')->parse_bbcode = 1;
			IPSText::getTextClass('bbcode')->skipXssCheck = true;
			
			/* Do not truncate URLs */
			$_tmp = $this->settings['__noTruncateUrl'];
			$this->settings['__noTruncateUrl'] = true;
			
			$content = IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->convertForRTE( $content ) );		
			
			/* Other stuffs */
			$content = preg_replace( '#(\-{10,120})#', '<hr>', $content );
			$content = preg_replace( '#(\={10,120})#', '<hr>', $content );
			$content = preg_replace( '#(?:\n{1,})<hr>#', "<hr>", $content );
			$content = preg_replace( '#<hr>(?:\n{1,})#', "<hr>", $content );
			$content = str_replace( '&nbsp;', ' ', $content );
			
			/* Replace newlines */
			$content = str_replace( "\n", "<br />\n", $content );
			
			$this->settings['__noTruncateUrl'] = $_tmp;
			IPSText::getTextClass('bbcode')->skipXssCheck = false;
	//	}
		
		return wordwrap( $content, 990, "\r\n" );
	}
	
	/**
	 * Add <html> tags and such if it doesn't have any already
	 * 
	 * @param	string	HTML email
	 * @return	string	VERY HTML email
	 */
	public function applyHtmlWrapper( $subject, $content )
	{
		/* Due to some legacy methods with mail queue, buildMessage can be called twice
		 * for good reason, but it can then re-apply the wrapper. So we add a unique comment
		 * and then check for this.
		 */
		if ( ! stristr( $content, '<!--::ipb.wrapper.added::-->' ) )
		{
			$content = '<!--::ipb.wrapper.added::-->' . $content;
			
			/* Inline wrapper */
			if ( $this->htmlWrapper )
			{
				return str_ireplace( '<#content#>', $content, $this->htmlWrapper );
			}
			
			/* Attempt to load external wrapper */
			if ( is_file( IPS_CACHE_PATH . 'cache/skin_cache/emailWrapper.php' ) )
			{
				if ( ! is_object( $this->_loadedHtmlTemplateClass ) )
				{
					$classToLoad = IPSLib::loadLibrary( IPS_CACHE_PATH . 'cache/skin_cache/emailWrapper.php', 'ipsEmailWrapper' );
					$this->_loadedHtmlTemplateClass = new $classToLoad();
				}
				
				return $this->_loadedHtmlTemplateClass->getTemplate( $content );
			}
			
			/* Still here? Fail safe */
			return $this->parseWithDefaultHtmlWrapper( $content );
		}
		
		return $content;
	}
	
	/**
	 * Replaces key with value
	 *
	 * @param	string		Key
	 * @return	string		Replaced variable
	 */
	protected function _swapWords( $matches )
	{
		return $this->_words[ $matches[1] ];
	}
	
	/**
	 * Cleans the email subject
	 *
	 * @param	string		In text
	 * @return	string		Out text
	 */
	protected function _cleanSubject( $subject )
	{
		$subject = strip_tags( $subject );
		
		$subject = str_replace( "&#036;", "\$", $subject );
		$subject = str_replace( "&#33;" , "!" , $subject );
		$subject = str_replace( "&#34;" , '"' , $subject );
		$subject = str_replace( "&#39;" , "'" , $subject );
		$subject = str_replace( "&#124;", '|' , $subject );
		$subject = str_replace( "&#38;" , '&' , $subject );
		$subject = str_replace( "&#58;" , ":" , $subject );
		$subject = str_replace( "&#91;" , "[" , $subject );
		$subject = str_replace( "&#93;" , "]" , $subject );
		$subject = str_replace( "&#064;", '@' , $subject );
		$subject = str_replace( "&nbsp;" , ' ', $subject );
		$subject = str_replace( "&amp;" , '&', $subject );
		
		return $subject;
	}
		
	/**
	 * Cleans an email message
	 *
	 * @param	string		Email content
	 * @return	string		Cleaned email content
	 */
	public function cleanMessage( $message = "" ) 
	{
		if ( ! $this->html_email )
		{
			$message = preg_replace_callback( '#\[url=(.+?)\](.+?)\[/url\]#', array( $this, "_formatUrl" ), $message );
		}

		//-----------------------------------------
		// Unconvert smilies 'cos at this point they are img tags
		//-----------------------------------------
		
		$message = IPSText::unconvertSmilies( $message );
		
		//-----------------------------------------
		// We may want to adjust this later, but for
		// now just strip any other html
		//-----------------------------------------
	
		$message = IPSText::stripTags( $message, '<br>' );

		IPSText::getTextClass( 'bbcode' )->parse_html		= 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 0;
		
		$plainText = '<br /><br />------------ QUOTE ----------<br />\\1<br />-----------------------------<br /><br />';
		$htmlText  = '<br /><div class="eQuote">\\1</div><br />';
		
		$message = preg_replace( '#\[quote(?:[^\]]+?)?\](.+?)\[/quote\]#s',  ( ( ! $this->html_email ) ? $plainText : $htmlText ), $message );

		$message = IPSText::getTextClass('bbcode')->stripAllTags( $message, true );
		
		//-----------------------------------------
		// Bear with me...
		//-----------------------------------------
		
		$message = str_replace( "\n"			, "\r\n", $message );
		$message = str_replace( "\r"			, ""	, $message );
		$message = str_replace( "<br>"			, "\r\n", $message );
		$message = str_replace( "<br />"		, "\r\n", $message );
		$message = str_replace( "\r\n\r\n"		, "\r\n", $message );
		
		$message = str_replace( "&quot;", '"' , $message );
		$message = str_replace( "&#092;", "\\", $message );
		$message = str_replace( "&#036;", "\$", $message );
		$message = str_replace( "&#33;" , "!" , $message );
		$message = str_replace( "&#34;" , '"' , $message );
		$message = str_replace( "&#39;" , "'" , $message );
		$message = str_replace( "&#40;" , "(" , $message );
		$message = str_replace( "&#41;" , ")" , $message );
		$message = str_replace( "&lt;"  , "<" , $message );
		$message = str_replace( "&gt;"  , ">" , $message );
		$message = str_replace( "&#124;", '|' , $message );
		$message = str_replace( "&amp;" , "&" , $message );
		$message = str_replace( "&#38;" , '&' , $message );
		$message = str_replace( "&#58;" , ":" , $message );
		$message = str_replace( "&#91;" , "[" , $message );
		$message = str_replace( "&#93;" , "]" , $message );
		$message = str_replace( "&#064;", '@' , $message );
		$message = str_replace( "&#60;" , '<' , $message );
		$message = str_replace( "&#62;" , '>' , $message );
		$message = str_replace( "&nbsp;" , ' ', $message );

		return $message;
	}
	
	/**
	 * Format url for email
	 *
	 * @param	array		preg_replace matches
	 * @return	string		Formatted url
	 */
	public function _formatUrl( $matches ) 
	{
		$matches[1]	= str_replace( array( '"', "'", '&quot;', '&#039;', '&#39;' ), '', $matches[1] );
		
		return $matches[2] . ' (' . $matches[1] . ')';
	}
	
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
		$this->_attachments[] = array( $data, $name, $ctype );
	}
	
	/**
	 * Log a fatal error
	 *
	 * @param	string		Message
	 * @param	string		Help key (deprecated)
	 * @return	bool
	 */
	protected function fatalError( $msg, $help="" )
	{
		$this->DB->insert( 'mail_error_logs',
										array(
												'mlog_date'     => time(),
												'mlog_to'       => $this->to,
												'mlog_from'     => $this->from,
												'mlog_subject'  => $this->subject,
												'mlog_content'  => substr( $this->message, 0, 200 ),
												'mlog_msg'      => $msg,
												'mlog_code'     => $this->emailer->smtp_code,
												'mlog_smtp_msg' => $this->emailer->smtp_msg
											 )
									  );
		
		return false;
	}

	/**
	 * Default wrapper for HTML emails - edit the one in '/cache/skin_cache/emailWrapper.php'
	 * 
	 * @param	string HTML content
	 * @return	string	HTML email done
	 */
	protected function parseWithDefaultHtmlWrapper( $content )
	{
		$doc =IPS_DOC_CHAR_SET;
		
$email = <<<HTML
	<html>
		<head>
			<meta content="text/html; charset={$doc}" http-equiv="Content-Type">
			<title><#subject#></title>
			<style type="text/css">
			* {
				font-family: Arial;
				font-size: 14px;
				color: #000;
				background: #fff;
				line-height: 140%;
			 }
			</style>
		</head>
		<body>
			{$content}
		</body>
	</head>
  </html>
HTML;

		return $email;
	}
}