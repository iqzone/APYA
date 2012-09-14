<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.2.0
 * Custom Profile Fields
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class customProfileFields
{
	/**
	 * Member ID
	 *
	 * @var		int
	 */
	public $member_id		= 0;
	
	/**
	 * Member ID
	 *
	 * @var		int
	 */
	public $mem_data_id		= 0;
	
	/**
	 * Array of incoming data
	 *
	 * @var		array
	 */
	public $in_fields		= array();
	
	/**
	 * Final/parsed data
	 *
	 * @var		array
	 */
	public $out_fields		= array();
	
	/**
	 * Chosen output data
	 *
	 * @var		array
	 */
	public $out_chosen		= array();
	
	/**
	 * Member record
	 *
	 * @var		array
	 */	
	public $member_data		= array();
	
	/**
	 * Field names
	 *
	 * @var		array
	 */
	public $field_names		= array();
	
	/**
	 * Field descriptions
	 *
	 * @var		array
	 */	
	public $field_desc		= array();
	
	/**
	 * Error fields
	 *
	 * @var		array
	 */
	public $error_fields	= array();
	
	/**
	 * Error messages
	 *
	 * @var		array
	 */	
	public $error_messages	= array();
	
	/**
	 * What type of parse this is
	 *
	 * @var		string
	 */
	public $type			= '';
	
	/**
	 * Is an admin
	 *
	 * @var		bool
	 */
	public $admin			= false;
	
	/**
	 * Is an admin
	 *
	 * @var		bool
	 */
	public $supmod			= false;	
	
	/**
	 * Initialized yet
	 *
	 * @var		bool
	 */
	public $init			= false;	
	
	/**
	 * Cache data
	 *
	 * @var		array
	 */
	public $cache_data		= array();
	
	/**
	 * Custom fields library
	 *
	 * @var		object
	 */
	public $cfields;
	
	/**
	 * Skin group to use for view parsing
	 *
	 * @var		string
	 */
	public $skinGroup		= '';
	
	/**
	 * Database handle
	 *
	 * @var		object
	 */
	protected $DB;
	
	/**
	 * Already added fields
	 * Prevents MSSQL getting confused
	 *
	 * @var		array
	 */
	protected $_addedFields = array();
	
	/* Ability to pull fields from groups */
	protected $fromGroups = array();
	
	/* Ability to exlude fields from groups */
	protected $notGroups = array();
	
	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Shortcuts */
		$this->DB		  = ipsRegistry::DB();
		$this->cache_data = ipsRegistry::cache()->getCache( 'profilefields' );
		
		/* User Setup */
		$this->member_id  = ipsRegistry::member()->getProperty( 'member_id' );
		$this->admin      = intval( ipsRegistry::member()->getProperty( 'g_access_cp' ) );
		$this->supmod     = intval( ipsRegistry::member()->getProperty( 'g_is_supmod' ) );
	}
	
	/**
	 * Set only to show from x groups
	 *
	 * @param	mixed	string or array
	 */
	public function setFromGroups( $in )
	{
		$this->fromGroups = ( is_array( $in ) ) ? $in : array( $in );
	}
	
	/**
	 * Set only to show from x groups
	 *
	 * @param	mixed	string or array
	 */
	public function setNotGroups( $in )
	{
		$this->notGroups = ( is_array( $in ) ) ? $in : array( $in );
	}
	
	/**
	 * Initializes cache, loads kernel class, and formats data for kernel class
	 *
	 * @param	string	$type	Set to view for displaying the field normally or edit for displaying in a form
	 * @param	bool	$mlist	Whether this is the memberlist or not
	 * @return	@e void
	 */
	public function initData( $type='view', $mlist=0 )
	{	
		/* Store Type */
		$this->type = $type;

		/* Get Member */
		if( ! count( $this->member_data ) and $this->mem_data_id && ! $mlist )
		{
			$this->member_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'pfields_content', 'where' => 'member_id='.intval($this->mem_data_id) ) );
		}
		
		if( count( $this->member_data ) )
		{
			$this->mem_data_id = isset( $this->member_data['member_id'] ) ? $this->member_data['member_id'] : 0;
		}
		
		if( ! $this->init )
		{
			/* Cache data... */
			if( ! is_array( $this->cache_data ) )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'pfields_data', 'order' => 'pf_group_id,pf_position' ) );
				$this->DB->execute();
				
				while ( $r = $this->DB->fetch() )
				{
					$this->cache_data[ $r['pf_id'] ] = $r;
				}
			}
		}
			
		/* Get names... */
		if( is_array( $this->cache_data ) and count( $this->cache_data ) )
		{
			foreach( $this->cache_data as $id => $data )
			{
				/* Field names and descriptions */
				$this->field_names[ $id ] = $data['pf_title'];
				$this->field_desc[ $id ]  = $data['pf_desc'];
				
				/* In Fields */
				foreach( $this->cache_data as $id => $data )
				{
					$data['pf_key']       = ( ! empty( $data['pf_key'] ) ) ? $data['pf_key'] : '_key_' . $data['pf_id'];
					$data['pf_group_key'] = $data['pf_group_key'] ? $data['pf_group_key'] : '_other';
					
					if( $mlist )
					{
						$this->in_fields[ $id ] = '';
						
						if( !empty(ipsRegistry::$request[ 'field_' . $id ]) )
						{
							if( is_string(ipsRegistry::$request[ 'field_' . $id ]) )
							{
								$this->in_fields[ $id ] = urldecode(ipsRegistry::$request[ 'field_' . $id ]);
							}
							else if( is_array(ipsRegistry::$request[ 'field_' . $id ]) )
							{
								foreach( ipsRegistry::$request[ 'field_' . $id ] as $k => $v )
								{
									$this->in_fields[ $id ][ $k ] = urldecode($v);
								}
							}
						}
					}
					else
					{
						$_val	= '';

						if( is_string(ipsRegistry::$request[ 'field_' . $id ]) )
						{
							$_val	= urldecode(ipsRegistry::$request[ 'field_' . $id ]);
						}
						else if( is_array(ipsRegistry::$request[ 'field_' . $id ]) )
						{
							foreach( ipsRegistry::$request[ 'field_' . $id ] as $k => $v )
							{
								$_val[ $k ] = urldecode($v);
							}
						}
							
						$this->in_fields[ $id ] = isset( $this->member_data['field_'.$id] ) ? $this->member_data['field_' . $id] : $_val;
					}					
				}						
			}
		}

		/* Clean up on aisle #4 */
		$this->out_fields = array();
		$this->out_chosen = array();

		/* Format data for kernel class */
		foreach( $this->cache_data as $k => $v )
		{
			/* Add any option to dropdown */
			if( $v['pf_type'] == 'drop' && $mlist )
			{
				$v['pf_content'] = '0=|' . $v['pf_content'];
			}			
			/* Field Info */
			$this->cache_data[$k]['id']    = $v['pf_id'];
			$this->cache_data[$k]['type']  = $v['pf_type'];
			$this->cache_data[$k]['data']  = $v['pf_content'];
			$this->cache_data[$k]['value'] = $this->in_fields[$k];
			
			/* Field Restrictions */
			$this->cache_data[$k]['restrictions'] = array(
					
															'max_size'	=> isset( $v['pf_max_input'] )		? $v['pf_max_input']	: '',
															'min_size'	=> isset( $v['pf_min_input'] )		? $v['pf_min_input']	: '',
															'not_null'	=> isset( $v['pf_not_null'] )		? $v['pf_not_null']		: '',
															'format'	=> isset( $v['pf_input_format'] )	? $v['pf_input_format']	: '',
															'urlfilter'	=> isset( $v['pf_filtering'] )		? $v['pf_filtering']	: '',
														);
		}

		/* Kernel profile field class */
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classCustomFields.php', 'classCustomFields' );
		
		IPSDebug::setMemoryDebugFlag( "Get CustomFields Kernel Class", $_NOW );
		
		$this->cfields_obj = new $classToLoad( $this->cache_data, $type );
		$this->cfields     = $this->cfields_obj->cfields;

		$this->init = 1;
	}

	/**
	 * Parses fields for saving into the database, results are stored in $this->out_fields
	 *
	 * @param	array	$field_data	Array that contains the fields to parse, usually $this->request
	 * @return	@e void
	 */
	public function parseToSave( $field_data, $mode='normal' )
	{
		/* Parse the fields */
		$save_fields = $this->cfields_obj->getFieldsToSave( $field_data );
		
		/* Save the raw error data */
		$this->error_fields = $save_fields['errors'];

		/* Reformat the errors into nicer output */
		if( is_array( $this->error_fields ) && count( $this->error_fields ) )
		{
			/* Make sure error message texts are loaded */
			ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_profile' ), 'members' );
			
			foreach( $this->error_fields as $id => $err )
			{
				/* Can we view this field? */
				if( ! $this->_checkFieldAuth( $this->cfields[ str_replace( 'field_', '', $id ) ], $mode ) )
				{
					continue;		
				}

				$_error_messages = array();
				
				foreach( $err as $e )
				{
					$_error_messages[] = ipsRegistry::getClass( 'class_localization' )->words[ 'profile_field_error__' . $e ];
				}

				$this->error_messages[$id] = $this->cache_data[ str_replace( 'field_', '', $id ) ]['pf_title'] . ': ' . implode( ', ', $_error_messages );
			}
		}

		/* Loop through our custom fields */
		foreach( $this->cfields as $id => $field )
		{
			/* Can we view this field? */
			if( ! $this->_checkFieldAuth( $field, $mode ) )
			{
				continue;
			}
			
			/* Now add any missing content fields */
			if ( ! isset( $this->member_data[ 'field_' . $id ] ) OR IN_ACP )
			{
				if ( ! $this->DB->checkForField( "field_$id", 'pfields_content' ) AND ! isset( $this->_addedFields[ $id ] ) )
				{
					$this->DB->addField( 'pfields_content', "field_$id", 'text' );
					
					$this->_addedFields[ $id ] = $id;
				}
			}

			$this->out_fields[ 'field_' . $id ] = IPSText::getTextClass( 'bbcode' )->stripBadWords( $save_fields['save_array']['field_' . $id] );
			
			//-----------------------------------------
			// Blacklisted urls
			// @link	http://community.invisionpower.com/tracker/issue-7872-url-black-list-in-profile/
			//-----------------------------------------
			
			if( $this->cache_data[$id]['restrictions']['urlfilter'] AND !IPSText::getTextClass( 'bbcode' )->checkBlacklistUrls( $save_fields['save_array']['field_' . $id] ) )
			{
				/* Make sure error message texts are loaded */
				ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_profile' ), 'members' );
			 
				if( isset( $this->error_messages[ 'field_' . $id ] ) )
				{
					$this->error_messages['field_' . $id] .= ', ' . ipsRegistry::getClass( 'class_localization' )->words['profile_field_error__blacklist'];
				}
				else
				{
					$this->error_messages['field_' . $id] = $this->cache_data[ $id ]['pf_title'] . ': ' . ipsRegistry::getClass( 'class_localization' )->words['profile_field_error__blacklist'];
				}
			}
		}
		
		//print_r($this->error_messages);
	}
	
	/**
	 * Parses fields for viewing, results are stored in $this->out_fields
	 *
	 * @param	bool	$check_topic_format		Whether to apply topic formatting
	 * @param	string	$location				Location being called from
	 * @return	@e void
	 */
	public function parseToView( $check_topic_format=0, $location='profile' )
	{
		/* Loop through our custom fields */
		foreach( $this->cfields as $id => $field )
		{
			/* Can we view this field? */
			if ( ! $this->_checkFieldAuth( $field ) )
			{
				continue;		
			}
			
			/* Topic Format */
			if( $check_topic_format )
			{
				if ( ! $field->raw_data['pf_topic_format'] OR $location != 'topic' )
				{
					/* Override formatting with skin function? */
					if( $this->skinGroup )
					{
						/* Check for a field level skin bit first */
						$__func = 'customField__' . $field->raw_data['pf_key'];
						
						if( method_exists( ipsRegistry::getClass( 'output' )->getTemplate( $this->skinGroup ), $__func ) )
						{	
							$this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] = ipsRegistry::getClass( 'output')->getTemplate( $this->skinGroup )->$__func( $field );
							continue;
						}
												
						/* Now check for a group level skin bit */
						$__func = 'customFieldGroup__' . $field->raw_data['pf_group_key'];
						
						if( method_exists( ipsRegistry::getClass( 'output' )->getTemplate( $this->skinGroup ), $__func ) )
						{
							$this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] = ipsRegistry::getClass( 'output')->getTemplate( $this->skinGroup )->$__func( $field );
							continue;
						}						
						
						/* Now check for a generic skin bit */
						$__func = 'customField__generic';
						
						if( method_exists( ipsRegistry::getClass( 'output' )->getTemplate( $this->skinGroup ), $__func ) )
						{
							$this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] = ipsRegistry::getClass( 'output')->getTemplate( $this->skinGroup )->$__func( $field );
							continue;
						}
					}
	
					if ( ! $field->raw_data['pf_topic_format'] )
					{
						continue;
					}
				}
			}

			$this->out_fields[ $field->raw_data['pf_group_key'] ][ $field->raw_data['pf_key'] ] = $field->getValue();
		
			/* Format using the format configured in the ACP */
			if( $check_topic_format )
			{
				/* Save the value */
				$current_value = isset( $this->out_fields[ $field->raw_data['pf_group_key'] ][ $field->raw_data['pf_key'] ] ) ? $this->out_fields[ $field->raw_data['pf_group_key'] ][ $field->raw_data['pf_key'] ] : '';

				if( $current_value || $current_value == '0' )
				{
					/* Get the format */
					$this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] = $field->raw_data['pf_topic_format'];
					
					$this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] = str_replace( '{title}'  , $field->raw_data['pf_title'], $this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] );
					$this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] = str_replace( '{key}'    , $this->in_fields[$field->id]  , $this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] );
					$this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] = str_replace( '{content}', $current_value                , $this->out_fields[ $field->raw_data['pf_group_key'] ] [ $field->raw_data['pf_key'] ] );				
				}
			}
		}
	}
	
	/**
	 * Fetch group keys and titles
	 *
	 * @return	array 	array( pf_group_key => pf_group_name )
	 */
	public function fetchGroupTitles()
	{
		$return = array();
		
		/* Loop through our custom fields */
		foreach( $this->cfields as $id => $field )
		{
			/* Can we view this field? */
			if( ! $this->_checkFieldAuth( $field ) )
			{
				continue;		
			}
			
			$return[ $field->raw_data['pf_group_key'] ] = $field->raw_data['pf_group_name'];
		}
		
		return $return;
	}
	
	/**
	 * Parses fields for editing, results are stored in $this->out_fields
	 *
	 * @param	string	[$mode]	register or normal, normal is default
	 * @return	@e void
	 */
	public function parseToEdit( $mode='normal' )
	{
		/* Loop through our custom fields */
		foreach( $this->cfields as $id => $field )
		{
			/* Can we view this field? */
			if( ! $this->_checkFieldAuth( $field, $mode ) )
			{
				continue;
			}
			
			if( $mode == 'register' && ! $field->raw_data['pf_show_on_reg'] )
			{
				continue;
			}

			$this->out_fields[ $id ] = $field->getValue();

		}
	}
	
	/**
	 * Checks to see if the field is viewable by the current user
	 *
	 * @param	array	$field	Array of field data
	 * @param	string	$mode	Register, or blank
	 * @return	bool
	 */
	protected function _checkFieldAuth( $field, $mode='normal' )
	{
		/* Admin / mod only? */
		if( $field->raw_data['pf_admin_only'] )
		{
			if ( ! $this->admin AND ! $this->supmod )
			{
				return false;
			}
		}
		
		/* Whitelist */
		if ( count( $this->fromGroups ) )
		{
			if ( in_array( $field->raw_data['pf_group_key'], $this->fromGroups ) )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
		/* Blacklist */
		if ( count( $this->notGroups ) )
		{
			if ( in_array( $field->raw_data['pf_group_key'], $this->notGroups ) )
			{
				return false;
			}
		}
		
		/* Member Edit? */
		if( $this->type == 'edit' && ! $field->raw_data['pf_member_edit'] )
		{
			if ( $mode != 'register' AND $mode != 'mlist' AND ! $this->admin AND !$this->supmod )
			{
				return false;
			}
		}
		
		/* Registering */
		if ( $mode == 'register' AND ! $field->raw_data['pf_show_on_reg'] AND ! IN_ACP ) // Bug Fix #21163
		{
			return false;
		}
		
		/* Private FIeld */
		if( $field->raw_data['pf_member_hide'] )
		{
			$pass = 0;
			
			if ( $this->admin )
			{
				$pass = 1;
			}
			else if ( $this->supmod )
			{
				$pass = 1;
			}
			else if ( ($this->member_id and ( $this->member_id == $this->mem_data_id )) OR ($this->type == 'edit' AND $mode == 'register') )
			{
				$pass = 1;
			}
			else
			{
				$pass = 0;
			}
			
			if ( ! $pass )
			{
				return false;
			}
		}

		return true;
	}
	
	/**
	 * Returns the id for the specified key
	 *
	 * @param	string	$key	Field key
	 * @return	integer
	 */
	public function getFieldIDByKey( $key )
	{
		$field = 0;
		
		foreach( $this->cache_data as $id => $_c )
		{
			if( $_c['pf_key'] == $key )
			{
				$field = $id;
				break;
			}
		}
		
		return $field;
	}
}