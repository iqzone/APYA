<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Control Panel Functions
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Tue. 17th August 2004
 * @version		$Rev: 10721 $
 *
 */
class adminFunctions
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
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
	
	/**#@+
	 * Security keys
	 *
	 * @access	public
	 * @var		string
	 */
	public $generated_acp_hash;
	public $_admin_auth_key;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry				=  $registry;
		$this->DB					=  $this->registry->DB();
		$this->settings				=& $this->registry->fetchSettings();
		$this->request				=& $this->registry->fetchRequest();
		$this->lang					=  $this->registry->getClass('class_localization');
		$this->member				=  $this->registry->member();
		$this->memberData			=& $this->registry->member()->fetchMemberData();
		$this->generated_acp_hash	=  $this->generateSecureHash();
		$this->_admin_auth_key		=  $this->getSecurityKey();
		
		$this->registry->output->global_template = $this->registry->output->loadRootTemplate('cp_skin_global');

		//------------------------------------------
		// Message in a bottle?
		//------------------------------------------

		if( !empty($this->request['messageinabottleacp']) )
		{
			$this->request['messageinabottleacp']		= IPSText::getTextClass('bbcode')->xssHtmlClean( IPSText::UNhtmlspecialchars( urldecode( $this->request['messageinabottleacp'] ) ) );
			$this->registry->output->global_message		= $this->request['messageinabottleacp'];
			$this->registry->output->persistent_message	= intval($this->request['messagepersistent']);
		}
	}
	
	/**
	 * Fetch mod_rewrite rules
	 *
	 * @access	public
	 * @return	string
	 */
	public function fetchModRewrite()
	{
		$rules  = '';
		$_parse = parse_url( $this->settings['base_url'] );
		$_root  = preg_replace( "#/$#", "", $_parse['path'] );
		$_root  = str_replace( '/' . CP_DIRECTORY, '', $_root );
		$_root  = str_replace( 'index.php', '', $_root );
		
		$rules  = "&lt;IfModule mod_rewrite.c&gt;\n";
		$rules .= "Options -MultiViews\n";
		$rules .= "RewriteEngine On\n";
		$rules .= "RewriteBase $_root\n";
		$rules .= "RewriteCond %{REQUEST_FILENAME} .*\.(jpeg|jpg|gif|png)$\n"; 
		$rules .= "RewriteCond %{REQUEST_FILENAME} !-f\n"; 
		$rules .= "RewriteRule . {$_root}public/404.php [L]\n\n";
		$rules .= "RewriteCond %{REQUEST_FILENAME} !-f\n" .
				  "RewriteCond %{REQUEST_FILENAME} !-d\n" .
				  "RewriteRule . {$_root}index.php [L]\n";
		$rules .= "&lt;/IfModule&gt;\n";
		
		return $rules;
	}
	
	/**
	 * Get the staff member's "cookie"
	 *
	 * @access	public
	 * @param	string		Key
	 * @param	integer		Member ID [defaults to current member]
	 * @return	mixed		Stored cookie
	 */
	public function staffGetCookie( $key, $id=0 )
	{
		//-----------------------------------------
		// INIT, Yes, it is.
		//-----------------------------------------
		
		$id = ( $id ) ? $id : $this->memberData['member_id'];
		
		$_test = $this->DB->buildAndFetch( array( 
														'select' => 'sys_login_id, sys_cookie',
														'from'   => 'core_sys_login',
														'where'  => 'sys_login_id=' . intval( $id ) 
												)	);
		
		$cookie = ( $_test['sys_cookie'] ) ? unserialize( $_test['sys_cookie'] ) : array();

		return ( isset( $cookie[ $key ] ) ) ? $cookie[ $key ] : null;
	}
	
	/**
	 * Update the member's "cookie"
	 *
	 * @access	public
	 * @param	string		Key
	 * @param	mixed		Data
	 * @param	integer		Member id [defaults to current member]
	 * @return	boolean
	 */
	public function staffSaveCookie( $key, $data, $id=0 )
	{
		//-----------------------------------------
		// INIT, Yes, it is.
		//-----------------------------------------
		
		$id = ( $id ) ? $id : $this->memberData['member_id'];
		
		$_test = $this->DB->buildAndFetch( array( 'select' => 'sys_login_id, sys_cookie',
												  'from'   => 'core_sys_login',
												  'where'  => 'sys_login_id=' . intval( $id ) 
										  )		 );
		
		$cookie         = ( $_test['sys_cookie'] ) ? unserialize( $_test['sys_cookie'] ) : array();
		$cookie[ $key ] = $data;

		if ( $_test['sys_login_id'] )
		{
			$this->DB->update( 'core_sys_login', array( 'sys_cookie' => serialize( $cookie ) ), 'sys_login_id=' . intval( $id ) );
		}
		else
		{
			$this->DB->insert( 'core_sys_login', array( 'sys_cookie' => serialize( $cookie ), 'sys_login_id' => intval( $id ) ) );
		}
		
		return TRUE;
	}
	
	/**
 	 * Generate a md5 hash, used for authenticating forms
 	 *
 	 * @access	public
 	 * @return	string		MD5 secure hash
 	 * @see		getSecurityKey()
 	 */	
	public function generateSecureHash()
	{
		/* Generate Secure Hash */
		if ( IPB_ACP_IP_MATCH )
		{
			$ip_octets	= explode( ".", $this->member->ip_address );
	        $key		= md5( ( isset($this->memberData['joined']) ? $this->memberData['joined'] : 0 ) . "(&)" . $ip_octets[0] . '(&)' . $ip_octets[1] . '(&)' . ( isset($this->memberData['member_login_key']) ? $this->memberData['member_login_key'] : 0 ) );
		}
		else
		{
			$key		= md5( ( isset($this->memberData['joined']) ? $this->memberData['joined'] : 0 ) . '(&)' . ( isset($this->memberData['member_login_key']) ? $this->memberData['member_login_key'] : 0 ) );
		}
		
		return $key;
	}
	
	/**
 	 * Generate a md5 hash, used for authenticating forms
 	 *
 	 * @access	public
 	 * @return	string		MD5 secure hash
 	 * @see		checkSecurityKey()
 	 * @see		getSecurityKey()
 	 */	
	public function getSecurityKey()
	{
		if ( IPB_ACP_IP_MATCH )
		{
			return md5( ( isset($this->memberData['email']) ? $this->memberData['email'] : 0 ) . '^' . ( isset($this->memberData['joined']) ? $this->memberData['joined'] : 0 ) . '^' . $this->member->ip_address . md5( $this->settings['sql_pass'] ) );
		}
		else
		{
			return md5( ( isset($this->memberData['email']) ? $this->memberData['email'] : 0 ) . '^' . ( isset($this->memberData['joined']) ? $this->memberData['joined'] : 0 ) . '^' . md5( $this->settings['sql_pass'] ) );
		}
	}

	/**
	 * Checks the security key
	 *
	 * @access	public
	 * @param	string		md5 auth key [defaults to $_POST['_admin_auth_key']]
	 * @param	boolean		return and not die?
	 * @return	mixed		boolean false or outputs error on failure, else true
	 * @see		getSecurityKey()
	 */
	public function checkSecurityKey( $auth_key='', $return_and_not_die=false )
	{
		$auth_key = ( $auth_key ) ? $auth_key : trim( $_POST['_admin_auth_key'] );
		
		if ( $auth_key != $this->getSecurityKey() )
		{
			if ( $return_and_not_die )
			{
				return FALSE;
			}
			else
			{
				$this->registry->output->showError( $this->lang->words['func_security_mismatch'], 2100, null, null, 403 );
				exit();
			}
		}
		
		return true;
	}
	
	/**
	 * Save an entry to the admin logs
	 *
	 * @access	public
	 * @param	string		Action
	 * @return	boolean
	 */
	public function saveAdminLog( $action="" )
	{
		$this->DB->insert( 'admin_logs', array( 'appcomponent' => $this->request['app'],
												'module'       => $this->request['module'],
												'section'      => $this->request['section'],
												'do'           => $this->request['do'],
												'member_id'    => $this->memberData['member_id'],
												'ctime'        => time(),
												'note'         => $action,
												'ip_address'   => $this->member->ip_address,
						  )						);
		
		return true;
	}
	
	/**
	 * Import an XML file either from a fixed server location
	 * or via the upload fields. Upload fields are checked first
	 *
	 * @access	public
	 * @param	string		File location
	 * @return	string		XML contents
	 * @throws	Exception	Throws Exception gz_not_available if the zlib extension is not installed
	 * @note	This function is not restricted to XML.  It will return the contents of the uploaded file regardless of file type.
	 * @todo 	[Future] We should rename this function to something more generic.  It is not restricted to 'xml' so the name is misleading.
	 */
	public function importXml( $location='' )
	{
		//-----------------------------------------
		// Upload
		//-----------------------------------------
		
		$FILE_NAME  = $_FILES['FILE_UPLOAD']['name'];
		$FILE_TYPE  = $_FILES['FILE_UPLOAD']['type'];
		$UPLOAD_DIR = ( $this->settings['upload_dir'] AND is_dir( $this->settings['upload_dir'] ) ) ? $this->settings['upload_dir'] : DOC_IPS_ROOT_PATH.'uploads';
		
		//-----------------------------------------
		// Naughty Opera adds the filename on the end of the
		// mime type - we don't want this.
		//-----------------------------------------
		
		$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
		$content   = "";

		if ( $FILE_NAME AND ( $FILE_NAME != 'none' ) )
		{
			if ( move_uploaded_file( $_FILES[ 'FILE_UPLOAD' ]['tmp_name'], $UPLOAD_DIR . "/" . $FILE_NAME ) )
			{
				$location = $UPLOAD_DIR . "/" . $FILE_NAME;
			}
		}

		/* Now load it... $location could have been set by the upload check...*/
		if ( is_file( $location ) )
		{
			if ( substr( $location, -3 ) == '.gz' )
			{
				if ( !function_exists( 'gzopen' ) )
				{
					throw new Exception( 'gz_not_available' );
				}
			
				if ( $FH = @gzopen( $location, 'rb' ) )
				{
				 	while ( ! @gzeof( $FH ) )
				 	{
				 		$content .= @gzread( $FH, 1024 );
				 	}
				 	
					@gzclose( $FH );
				}
			}
			else //if ( substr( $location, -4 ) == '.xml' )
			{
				$content = file_get_contents( $location );
			}
		}

		/* Unlink the tmp file if it exists */
		if ( is_file( $UPLOAD_DIR . "/" . $FILE_NAME ) )
		{
			@unlink( $UPLOAD_DIR . "/" . $FILE_NAME );
		}
		
		return $content;
	}
}