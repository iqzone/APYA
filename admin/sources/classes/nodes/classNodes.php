<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Very lightweight simple, basic node class
 * Owner: Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 10721 $
 */

class classNodes
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	protected $caches;
	
	/**
	 * Item Cache
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_nodeCache = array();
	
	
	/**
	 * FIELD: item ID
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlPrimaryID  = '';
	
	/**
	 * FIELD: parent ID
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlParentID = '';
	
	/**
	 * FIELD: Is folder flag
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlFolderFlag = '';
	
	/**
	 * FIELD: Item name field
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlTitle     = '';
	
	/**
	 * SQL Field: From
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlTable        = '';
	
	/**
	 * SQL Fields: Additional 'where' information
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlWhere       = '';
	
	/**
	 * SQL Fields: Select information
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlSelect       = '*';
	
	/**
	 * SQL Fields: Node path
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlNodeLevel       = '';
	
	/**
	 * SQL Fields: Node left
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlNodeLeft       = '';
	
	/**
	 * SQL Fields: Node right
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlNodeRight       = '';
	
	/**
	 * SQL Fields: Order field
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlOrder       = '';
	
	/**
	 * SQL Fields: Pass IPB formatted add_join array
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlJoins       = '';
	
	/**
	 * SQL Fields: SQL limit
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlLimit       = null;
	
	/**
	 * SQL Fields: Tbl prefix used when using joins
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlTblPrefix       = '';
	
	/**
	 * Debug cache
	 * Stores random debug messages and whatnot
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_debugMsgs = array();
	
	/**
	 * Method constructor
	 *
	 * @access	public
	 * @param	object		Registry Object
	 * @param	array 		Array of settings:
	 *						[ 'sqlPrimaryID'	   (the SQL table field for the item ID)
	 *						  'sqlParentID'  	   (the SQL table field for the parent ID)
	 *						  'sqlTitle'		   (the SQL table field for the item title)
	 *						  'sqlSelect'		   (the SQL table select fields (* by default))
	 *						  'sqlTable'		   (the SQL table name)
	 *						  'sqlNodeLevel'       (the SQL table field for the node level)
	 *						  'sqlNodeLeft'        (the SQL table field for node left)
	 *					      'sqlNodeRight'       (the SQL table field for node right)
	 *						  'sqlWhere'		   (Any additional 'where' information *Optional) ]
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry   =  ipsRegistry::instance();
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	public function init( array $settings )
	{
		/* Sort out settings */
		$this->_sqlPrimaryID		 = isset( $settings['sqlPrimaryID']       ) ? $settings['sqlPrimaryID']       : '';
		$this->_sqlParentID		     = isset( $settings['sqlParentID']        ) ? $settings['sqlParentID']        : '';
		$this->_sqlNodeLevel	     = isset( $settings['sqlNodeLevel']        ) ? $settings['sqlNodeLevel']      : '';
		$this->_sqlNodeRight	     = isset( $settings['sqlNodeRight']       ) ? $settings['sqlNodeRight']       : '';
		$this->_sqlNodeLeft          = isset( $settings['sqlNodeLeft'] )        ? $settings['sqlNodeLeft']		  : '';
		$this->_sqlOrder	         = isset( $settings['sqlOrder']           ) ? $settings['sqlOrder']           : '';
		$this->_sqlTitle			 = isset( $settings['sqlTitle']           ) ? $settings['sqlTitle']           : '';
		$this->_sqlTable			 = isset( $settings['sqlTable']           ) ? $settings['sqlTable']           : '';
		$this->_sqlWhere			 = isset( $settings['sqlWhere']           ) ? $settings['sqlWhere']           : '';
		$this->_sqlSelect			 = isset( $settings['sqlSelect']          ) ? $settings['sqlSelect']          : $this->_sqlPrimaryID . ', ' . $this->_sqlParentID . ',' . $this->_sqlTitle . ',' . $this->_sqlNodeLeft . ',' . $this->_sqlOrder . ',' . $this->_sqlNodeLevel . ',' . $this->_sqlNodeRight;
		$this->_sqlJoins			 = isset( $settings['sqlJoins']			  ) ? $settings['sqlJoins']			  : array();
		$this->_sqlTblPrefix		 = isset( $settings['sqlTblPrefix']       ) ? $settings['sqlTblPrefix']       : 'xyxy';
		$this->_sqlLimit 			 = isset( $settings['sqlLimit']			  ) ? $settings['sqlLimit']			  : null;
	}
    	
    /**
     * Allow set/get data
     *
     */
    public function __call( $method, $arguments )
	{
		$firstBit = substr( $method, 0, 3 );
		$theRest  = substr( $method, 3 );
	
		if ( $firstBit == 'set' )
		{
			switch( $theRest )
			{
				case 'Order':
					$this->_sqlOrder = $arguments[0];
				break;
				case 'Where':
					$this->_sqlWhere = $arguments[0];
				break;
				case 'Select':
					$this->_sqlSelect = $arguments[0];
				break;
				case 'Limit':
					$this->_sqlLimit = $arguments[0];
				break;
				case 'Joins':
					$this->_sqlJoins = $arguments[0];
				break;
				case 'TblPrefix':
					$this->_sqlTblPrefix = $arguments[0];
				break;
			}
		}
		else if ( $firstBit == 'get' )
		{
			switch( $theRest )
			{
				case 'Order':
					return $this->_sqlOrder;
				break;
				case 'Where':
					return $this->_sqlWhere;
				break;
				case 'Select':
					return $this->_sqlSelect;
				break;
				case 'Limit':
					return $this->_sqlLimit;
				break;
				case 'Joins':
					return $this->_sqlJoins;
				break;
				case 'TblPrefix':
					return $this->_sqlTblPrefix;
				break;
			}
		}
		else
		{
			trigger_error( "Call to undefined function " . $method . " in classNodes.php" );
		}
	}
	
    /**
     * Fetches a single node's content
     *
     * @access	public
     * @param	int
     * @return	array
     */
    public function fetchNodeContent( $nodeId )
    {
    	$fetch    = ( is_numeric( $nodeId ) ) ? '=' . intval( $nodeId ) : ' IN (' . implode( ',', IPSLib::cleanIntArray( (array) $nodeId ) ) . ')';
    	$gotCache = array();
    	
    	if ( ! is_numeric( $nodeId ) )
    	{
    		foreach( $nodeId as $id )
    		{
    			if ( isset( $this->_nodeCache[ $id ] ) )
    			{
    				$gotCache[ $id ] = $this->_nodeCache[ $id ];
    			}
    		}
    		
    		if ( count( $gotCache ) == count( $nodeId ) )
    		{
    			return $gotCache;
    		}
    		else
    		{
    			$gotCache = array();
    		}
    	}
    	else
    	{
    		if ( isset( $this->_nodeCache[ $nodeId ] ) )
    		{
    			return $this->_nodeCache[ $nodeId ];
    		}
    	}
    	
    	/* Still here? */
    	if ( is_numeric( $nodeId ) OR ! count( $gotCache ) )
    	{
    		if ( is_array( $this->_sqlJoins ) and count( $this->_sqlJoins ) )
			{ 
				$this->DB->build( array( 'select'   => $this->_sqlTblPrefix . '.' . $this->_sqlSelect,
										 'from'     => array( $this->_sqlTable => $this->_sqlTblPrefix  ),
									 	 'where'    => $this->_sqlTblPrefix . '.' . $this->_sqlPrimaryID . $fetch,
										 'add_join' => $this->_sqlJoins  ) );
			}
			else
			{
				$this->DB->build( array( 'select'   => $this->_sqlSelect,
									 	 'from'     => $this->_sqlTable,
									 	 'where'    => $this->_sqlPrimaryID . $fetch ) );
			}
			
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				$return[ $row[ $this->_sqlPrimaryID ] ]           = $row;
				$this->_nodeCache[ $row[ $this->_sqlPrimaryID ] ] = $row;
			}
    	}
    	
    	return ( is_numeric( $nodeId ) ) ? $this->_nodeCache[ $nodeId ] : $return;
    }
    
    /**
     * Fetches left/right/level data
     *
     * @access	public
     * @param	int
     * @return	array - left, right, level
     */
    public function fetchNodeInfo( $nodeId )
    {
    	/* If this is ID 0... */
    	if ( $nodeId == 0 )
    	{
    		$left  = $this->DB->buildAndFetch( array( 'select' => 'MIN(' . $this->_sqlNodeLeft . ') as nleft',
    												  'from'   => $this->_sqlTable,
    												  'where'  => $this->_sqlNodeLevel . '=0' ) );
    		
    		$right = $this->DB->buildAndFetch( array( 'select' => 'MAX(' . $this->_sqlNodeRight . ') as nright',
    												  'from'   => $this->_sqlTable,
    												  'where'  => $this->_sqlNodeLevel . '=0' ) );
    		
    		return array( $left['nleft'], $right['nright'], 0 );
    	}
    	else
    	{
	    	$data = $this->fetchNodeContent( $nodeId );
	    	
	    	return array( $data[ $this->_sqlNodeLeft ],
	    				  $data[ $this->_sqlNodeRight ],
	    				  $data[ $this->_sqlNodeLevel ] );
    	}
    }

    /**
     * Fetches parent info
     *
     * @access	public
     * @param 	int Node id
     * @param 	string	Where data
     * @return 	DB row
    */
    public function fetchParentInfo( $nodeId, $where = '')
    {
    	/* Init where */
    	$where = ( $where ) ? $where : $this->_sqlWhere;
    	
    	/* Fetch noded data */
    	$nodeData = $this->fetchNodeInfo( $nodeId );
    	
    	if ( count( $nodeData ) )
    	{
    		return false;
    	}
    	
    	/* Lets go */
    	list( $leftId, $rightId, $level ) = $nodeData;
    	
    	/* Jump up a level */
    	$level--;
    	
    	$where = ( $where ) ? ' AND (' . $where . ')' : '';
    	
    	/* Fetch data */
    	if ( is_array( $this->_sqlJoins ) and count( $this->_sqlJoins ) )
		{
			$this->DB->build( array( 'select'   => $this->_sqlTblPrefix . '.' . $this->_sqlSelect,
									 'from'     => array( $this->_sqlTable => $this->_sqlTblPrefix  ),
								 	 'where'    => $this->_sqlTblPrefix . '.' . $this->_sqlNodeLeft . ' < ' . $leftId .
								 	 			   ' AND ' . $this->_sqlTblPrefix . '.' . $this->_sqlNodeLeft . ' > ' . $rightId .
								 	 			   ' AND ' . $this->_sqlTblPrefix . '.' . $this->_sqlNodeLevel . ' = ' . $level .
								 	 			   $where,
								 	 'order'    => $this->_sqlTblPrefix . '.' . $this->_sqlNodeLeft,
									 'add_join' => $this->_sqlJoins  ) );
		}
		else
		{
			$this->DB->build( array( 'select'   => $this->_sqlSelect,
								 	 'from'     => $this->_sqlTable,
								 	 'where'    => $this->_sqlNodeLeft . ' < ' . $leftId .
								 	 			   ' AND ' . $this->_sqlNodeLeft . ' > ' . $rightId .
								 	 			   ' AND ' . $this->_sqlNodeLevel . ' = ' . $level .
								 	 			   $where,
								 	 'order'    => $this->_sqlNodeLeft ) );
		}
		
		$o = $this->DB->execute();
    
    	$this->_nodeCache[ $nodeId ] = $row = $this->DB->fetch( $o );
    	
    	return ( count( $row ) ) ? $row : false;
    }

	/**
     * Returns DB resource to all nodes
     *
     * @access	public
     * @param	string		Where string
     * @return   boolean
    */
    public function fetchTree( $where='' )
    {
    	/* Init where */
    	$where = ( $where ) ? $where : $this->_sqlWhere;
    	
    	/* Fetch data */
    	if ( is_array( $this->_sqlJoins ) and count( $this->_sqlJoins ) OR ! empty( $this->_sqlTblPrefix ) )
		{
			$_order = $this->getOrder();
			$order  = ( ! empty( $_order ) ) ? $_order : $this->_sqlTblPrefix . '.' . $this->_sqlNodeLeft;
			
			$this->DB->build( array( 'select'   => $this->_sqlTblPrefix . '.' . $this->_sqlSelect,
									 'from'     => array( $this->_sqlTable => $this->_sqlTblPrefix  ),
									 'where'    => $where,
									 'limit'    => $this->getLimit(),
								 	 'order'    => $order,
									 'add_join' => $this->_sqlJoins  ) );
		}
		else
		{
			$_order = $this->getOrder();
			$order  = ( ! empty( $_order ) ) ? $_order : $this->_sqlNodeLeft;
			
			$this->DB->build( array( 'select'   => $this->_sqlSelect,
								 	 'from'     => $this->_sqlTable,
								 	 'where'    => $where,
									 'limit'    => $this->getLimit(),
								 	 'order'    => $order ) );
		}
    
    	/* Set up handler */
    	$this->_dbHandle = $this->DB->execute();
    	
        return true;
    }
    
    /**
     * Fetch the branch starting with $nodeId
     *
     * @access	public
     * @param	int		Node Id
     * @param	string	Where clause
     * @return 	boolean
     */
	public function fetchBranch( $nodeId, $inWhere='' )
	{
		/* Init where */
    	$inWhere = ( $inWhere ) ? $inWhere : $this->_sqlWhere;
    	
		/* Set up tbl prefix */
		$a = ( is_array( $this->_sqlJoins ) and count( $this->_sqlJoins ) ) ? $this->_sqlTblPrefix : 'aaa';
		
		/* Set up select */
		$select = $a . '.' . $this->_sqlSelect;
		
		$where  = 'bbb.' . $this->_sqlPrimaryID . '=' . $nodeId . ' AND ' . $a . '.' . $this->_sqlNodeLeft . ' >= bbb.' . $this->_sqlNodeLeft . ' AND ' . $a . '.' . $this->_sqlNodeRight . ' <= bbb.' . $this->_sqlNodeRight;
												  
        /* Custom where? */
        $where .= ( $inWhere ) ? ' AND (' . $inWhere . ')' : '';
        
         $_joins   = $this->_sqlJoins;
         $_joins[] = array( 'from' => array( $this->_sqlTable => 'bbb' ),
        				    'type' => 'inner' );
        
        $this->DB->build( array( 'select'   => $select,
								 'from'     => array( $this->_sqlTable => $a  ),
								 'where'    => $where,
								 'order'    => $a . '.' . $this->_sqlNodeLeft,
								 'add_join' => $_joins  ) );
		
        
        /* Set up handler */
    	$this->_dbHandle = $this->DB->execute();
    	
        return true;
    }

	/**
     * Fetch the parents of $nodeId
     *
     * @access	public
     * @param	int		Node Id
     * @param	string	Where clause
     * @return 	boolean
     */
	public function fetchParents( $nodeId, $inWhere='' )
	{
		/* Init where */
    	$inWhere = ( $inWhere ) ? $inWhere : $this->_sqlWhere;
    	
		/* Set up tbl prefix */
		$a = ( is_array( $this->_sqlJoins ) and count( $this->_sqlJoins ) ) ? $this->_sqlTblPrefix : 'aaa';
		
		/* Set up select */
		$select = $a . '.' . $this->_sqlSelect;
		
		$where  = 'bbb.' . $this->_sqlPrimaryID . '=' . $nodeId . ' AND bbb.' . $this->_sqlNodeLeft . ' BETWEEN ' . $a . '.' . $this->_sqlNodeLeft . ' AND ' . $a . '.' . $this->_sqlNodeRight;
												  
        /* Custom where? */
        $where .= ( $inWhere ) ? ' AND (' . $inWhere . ')' : '';
        
        $_joins   = $this->_sqlJoins;
        $_joins[] = array( 'from' => array( $this->_sqlTable => 'bbb' ),
        				   'type' => 'inner' );
        
        $this->DB->build( array( 'select'   => $select,
								 'from'     => array( $this->_sqlTable => $a  ),
								 'where'    => $where,
								 'order'    => $a . '.' . $this->_sqlNodeLeft,
								 'add_join' => $_joins  ) );
		
        
        /* Set up handler */
    	$this->_dbHandle = $this->DB->execute();
    	
        return true;
    }
    
	/**
     * Fetch the children of $nodeId
     *
     * @access	public
     * @param	int		Node Id
     * @param	string	Where clause
     * @return 	boolean
     */
	public function fetchChildren( $nodeId, $inWhere='' )
	{
		/* Init where */
    	$inWhere = ( $inWhere ) ? $inWhere : $this->_sqlWhere;
    	
		/* Set up tbl prefix */
		$a = ( is_array( $this->_sqlJoins ) and count( $this->_sqlJoins ) ) ? $this->_sqlTblPrefix : 'aaa';
		
		/* Set up select */
		$select = $a . '.' . $this->_sqlSelect;
		
		if ( $nodeId == 0 )
		{
			$where = '(1=1)';
		}
		else
		{
			$where  = 'bbb.' . $this->_sqlPrimaryID . '=' . $nodeId . ' AND ' . $a . '.' .  $this->_sqlNodeLeft . ' > ' . 'bbb.' . $this->_sqlNodeLeft . ' AND ' . $a . '.' .  $this->_sqlNodeRight . ' < bbb.' . $this->_sqlNodeRight;
		}
												  
        /* Custom where? */
        $where .= ( $inWhere ) ? ' AND (' . $inWhere . ')' : '';
        
        $_joins   = $this->_sqlJoins;
        
        if ( $nodeId > 0 )
		{
	        $_joins[] = array( 'from' => array( $this->_sqlTable => 'bbb' ),
	        				   'type' => 'inner' );
		}
        
        $this->DB->build( array( 'select'   => $select,
								 'from'     => array( $this->_sqlTable => $a  ),
								 'where'    => $where,
								 'order'    => $a . '.' . $this->_sqlNodeLeft,
								 'limit'    => $this->getLimit(),
								 'add_join' => $_joins  ) );
		
        
        /* Set up handler */
    	$this->_dbHandle = $this->DB->execute();
    	
        return true;
    }

    /**
     * Returns an item with parents and children
     *
     * @access	public
     * @param	int		Node Id
     * @param	string	Where clause
     * @return 	boolean
     */
    public function fetchSlice( $nodeId, $inWhere='' )
    {
    	/* Init where */
    	$inWhere = ( $inWhere ) ? $inWhere : $this->_sqlWhere;
    	
    	/* Init */
    	$i = 0;
    	
    	/* Set up tbl prefix */
		$a = ( is_array( $this->_sqlJoins ) and count( $this->_sqlJoins ) ) ? $this->_sqlTblPrefix : 'aaa';
		
		/* Select */
		$select = $a . '.' . $this->_sqlNodeLeft . ', ' .  $a . '.' . $this->_sqlNodeRight . ', ' . $a . '.' . $this->_sqlNodeLevel;	
		$where  = 'bbb.' . $this->_sqlPrimaryID . '=' . $nodeId . ' AND bbb.' . $this->_sqlNodeLeft . ' BETWEEN ' . $a . '.' . $this->_sqlNodeLeft . ' AND ' . $a . '.' . $this->_sqlNodeRight;
												  
        /* Custom where? */
        $where .= ( $inWhere ) ? ' AND (' . $inWhere . ')' : '';
        
        $_joins   = $this->_sqlJoins;
        $_joins[] = array( 'from' => array( $this->_sqlTable => 'bbb' ),
        				   'type' => 'inner' );
        
        $this->DB->build( array( 'select'   => $select,
								 'from'     => array( $this->_sqlTable => $a  ),
								 'where'    => $where,
								 'order'    => $a . '.' . $this->_sqlNodeLeft,
								 'add_join' => $_joins  ) );
		
        
        /* Set up handler */
    	$this->_dbHandle = $this->DB->execute();
    	
    	$len = $this->recordCount();
    	
    	/* Set up tbl prefix */
		$a = ( is_array( $this->_sqlJoins ) and count( $this->_sqlJoins ) ) ? $this->_sqlTblPrefix : 'aaa';
		
		$_where = ( $inWhere ) ? ' AND (' . $inWhere . ')' : '';
		
		/* start main where */
		$where = '(' . $a . '.' . $this->_sqlNodeLevel . ' =1';
		
		while( $row = $this->DB->fetch( $this->_dbHandle ) )
		{
			if ( ( ++$i == $len ) && ( $row[ $this->_sqlNodeLeft ] + 1 ) == $row[ $this->_sqlNodeRight ] )
			{
                break;
            }
            
            $where .= ' OR (' . $a .'.'. $this->_sqlNodeLevel . ' = ' . ( $row[$this->_sqlNodeLevel] + 1 )
            	   . ' AND '  . $a .'.'. $this->_sqlNodeLeft  . ' > ' .   $row[$this->_sqlNodeLeft]
            	   . ' AND '  . $a .'.'. $this->_sqlNodeRight . ' < ' .   $row[$this->_sqlNodeRight] . ')';
		} 
    	
    	$where .= ")" . $_where;
    	
    	/* Fetch data */
    	if ( is_array( $this->_sqlJoins ) and count( $this->_sqlJoins ) )
		{
			$this->DB->build( array( 'select'   => $this->_sqlTblPrefix . '.' . $this->_sqlSelect,
									 'from'     => array( $this->_sqlTable => $this->_sqlTblPrefix  ),
									 'where'    => $where,
								 	 'order'    => $this->_sqlTblPrefix . '.' . $this->_sqlNodeLeft,
									 'add_join' => $this->_sqlJoins  ) );
		}
		else
		{
			$this->DB->build( array( 'select'   => $a . '.' . $this->_sqlSelect,
								 	 'from'     => $this->_sqlTable . ' ' . $a,
								 	 'where'    => $where,
								 	 'order'    => $a . '.' . $this->_sqlNodeLeft ) );
		}
		
		/* Set up handler */
    	$this->_dbHandle = $this->DB->execute();
    	
        return true;
    }
    
    /**
     * Is descendant of
     * Checks to see if a node is a descendant of another node. Simple really
     * @param	array	node (descendant)
     * @param	array	node (root parent)
     */
    public function isDescendantOf( $desc, $parent )
    {
    	if ( ! isset( $desc[ $this->_sqlNodeLeft ] ) && ! isset( $desc[ $this->_sqlNodeRight ] ) )
    	{
    		return false;
    	}
    	
    	if ( ! isset( $parent[ $this->_sqlNodeLeft ] ) && ! isset( $parent[ $this->_sqlNodeRight ] ) )
    	{
    		return false;
    	}
    	
    	if ( $desc[ $this->_sqlNodeLeft ] > $parent[ $this->_sqlNodeLeft ] && $desc[ $this->_sqlNodeLeft ] < $parent[ $this->_sqlNodeRight ] )
    	{
    		return true;
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * Add node
     *
     * @param	int		Parent Node ID
     * @param	array	Array of fields to insert
     * @return	int		Insert ID
     */
    public function addNode( $parentId, $data )
    {
    	$nInfo = $this->fetchNodeInfo( $parentId );
    	
    	if ( ! isset( $nInfo[0] ) OR empty( $parentId ) )
    	{
    		 /* Add without parents? */
    		 if ( empty( $parentId ) )
    		 {
    		 	/* Find the highest right value */
    		 	$right = $this->DB->buildAndFetch( array( 'select'   => '*',
									 	 				  'from'     => $this->_sqlTable,
									 	 				  'order'    => $this->_sqlNodeRight . ' DESC',
    		 											  'limit'    => array( 0, 1 ) ) );
    		 	$leftId  = $right[ $this->_sqlNodeRight ] + 1;
    		 	$rightId = $right[ $this->_sqlNodeRight ] + 2;
    		 	$level	 = 0;
			
	        	$data[ $this->_sqlNodeLeft ]  = $leftId;
	        	$data[ $this->_sqlNodeRight ] = $rightId;
	        	$data[ $this->_sqlNodeLevel ] = $level;
    		 }
    		 else
    		 {
    		 	return false;
    		 }
    	}
    	else
    	{
			list( $leftId, $rightId, $level ) = $nInfo;
			
	        $data[ $this->_sqlNodeLeft ]  = $rightId;
	        $data[ $this->_sqlNodeRight ] = ( $rightId + 1 );
	        $data[ $this->_sqlNodeLevel ] = ( $level + 1 );
	        
	        /* Set data: Shift over the elements */
			$set = $this->_sqlNodeLeft .  ' = CASE WHEN ' . $this->_sqlNodeLeft  . '>'  . $rightId . ' THEN ' . $this->_sqlNodeLeft  . '+2 ELSE ' . $this->_sqlNodeLeft  . ' END, '
				 . $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeRight . '>=' . $rightId . ' THEN ' . $this->_sqlNodeRight . '+2 ELSE ' . $this->_sqlNodeRight . ' END ';
			
			/* Pre formatted */
			$this->DB->update( $this->_sqlTable, $set, $this->_sqlNodeRight . '>=' . $rightId, false, true );
    	}	
        
        /* Insert new row */
        $this->DB->insert( $this->_sqlTable, $data );
      	
        return $this->DB->getInsertId();
    }
   
    /**
     * Move and node and all children to a new parent node
     *
     * @param	int		Node ID
     * @param	int		New parent ID
     * @return	int		Insert ID
     */
    public function moveNode( $nodeId, $newParentId )
    {
    	$nInfo = $this->fetchNodeInfo( $nodeId );
    	
    	if ( ! isset( $nInfo[0] ) )
    	{
    		return false;
    	}
    	
    	list( $leftId, $rightId, $level ) = $nInfo;
    	
    	$nInfo = $this->fetchNodeInfo( $newParentId );
    	
    	if ( ! isset( $nInfo[0] ) )
    	{
    		return false;
    	}
    	
    	list( $leftIdP, $rightIdP, $levelP ) = $nInfo;
    	
		/* Make sure it can't be moved into itself or into an invalid node */
        if ( $nodeId == $newParentId || $leftId == $leftIdP || ( $leftIdP >= $leftId && $leftIdP <= $rightId ) || ( $newParentId > 0 && $level == $levelP + 1 && $leftId > $leftIdP && $rightId < $rightIdP ) )
        {
            return false;
        }
     
    	if ( $newParentId == 0 )
        { 
        	/* set the math right */
        	$levelP = -1;
        	
            $set = $this->_sqlNodeLevel . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLevel . sprintf( '%+d', - ( $level - 1 ) + $levelP ) . ' ELSE ' . $this->_sqlNodeLevel . ' END, '
                 . $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $rightId . ' AND ' . $rightIdP . ' THEN ' . $this->_sqlNodeLeft . '-' . ( $rightId - $leftId + 1 ) . ' '
                 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLeft . '+' . ( $rightIdP - $rightId ) . ' ELSE ' . $this->_sqlNodeLeft . ' END, '
                 . $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeRight . ' BETWEEN ' . ( $rightId + 1 ) . ' AND ' . ( $rightIdP ) . ' THEN ' . $this->_sqlNodeRight . '-' . ( $rightId - $leftId + 1 ) . ' '
                 . 'WHEN ' . $this->_sqlNodeRight . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeRight . '+' . ( $rightIdP - $rightId ) . ' ELSE ' . $this->_sqlNodeRight . ' END ';
           
            $whr = '(' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ' ' . 'OR ' . $this->_sqlNodeRight . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ')';
        }
        else if ( $leftIdP < $leftId && $rightIdP > $rightId && ( $levelP < $level - 1 ) )
        {
            $set = $this->_sqlNodeLevel . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLevel . sprintf('%+d', - ( $level - 1) + $levelP ) . ' ELSE ' . $this->_sqlNodeLevel . ' END, '
                 . $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeRight . ' BETWEEN ' . ( $rightId + 1 ) . ' AND ' . ( $rightIdP - 1 ) . ' THEN ' . $this->_sqlNodeRight . '-' . ( $rightId - $leftId + 1) . ' '
                 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeRight . '+' . ( ( ( $rightIdP - $rightId - $level + $levelP ) / 2 ) * 2 + $level - $levelP - 1 ) . ' ELSE ' . $this->_sqlNodeRight . ' END, '
                 . $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . ( $rightId + 1 ) . ' AND ' . ( $rightIdP - 1 ) . ' THEN ' . $this->_sqlNodeLeft . '-' . ( $rightId - $leftId + 1 ) . ' '
                 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLeft . '+' . ( ( ( $rightIdP - $rightId - $level + $levelP ) / 2 ) * 2 + $level - $levelP - 1 ) . ' ELSE ' . $this->_sqlNodeLeft . ' END ';
            
            $whr = $this->_sqlNodeLeft . ' BETWEEN ' . ( $leftIdP + 1 ) . ' AND ' . ( $rightIdP - 1 );
        }
        else if ( $leftIdP < $leftId )
        {
            $set = $this->_sqlNodeLevel . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLevel . sprintf( '%+d', - ( $level - 1 ) + $levelP ) . ' ELSE ' . $this->_sqlNodeLevel . ' END, '
	             . $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $rightIdP . ' AND ' . ( $leftId - 1 ) . ' THEN ' . $this->_sqlNodeLeft . '+' . ( $rightId - $leftId + 1 ) . ' '
	             . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLeft . '-' . ( $leftId - $rightIdP ) . ' ELSE ' . $this->_sqlNodeLeft . ' END, '
	             . $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeRight . ' BETWEEN ' . $rightIdP . ' AND ' . $leftId . ' THEN ' . $this->_sqlNodeRight . '+' . ( $rightId - $leftId + 1) . ' '
	             . 'WHEN ' . $this->_sqlNodeRight . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeRight . '-' . ( $leftId - $rightIdP ) . ' ELSE ' . $this->_sqlNodeRight . ' END ';
	        
	        $whr = '(' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId. ' ' . 'OR ' . $this->_sqlNodeRight . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId . ')';
        }
        else
        { 
            $set = $this->_sqlNodeLevel . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLevel . sprintf( '%+d', - ( $level - 1 ) + $levelP ) . ' ELSE ' . $this->_sqlNodeLevel . ' END, '
                 . $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $rightId . ' AND ' . $rightIdP . ' THEN ' . $this->_sqlNodeLeft . '-' . ( $rightId - $leftId + 1 ) . ' '
                 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLeft . '+' . ( $rightIdP - 1 - $rightId ) . ' ELSE ' . $this->_sqlNodeLeft . ' END, '
                 . $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeRight . ' BETWEEN ' . ( $rightId + 1 ) . ' AND ' . ( $rightIdP - 1 ) . ' THEN ' . $this->_sqlNodeRight . '-' . ( $rightId - $leftId + 1 ) . ' '
                 . 'WHEN ' . $this->_sqlNodeRight . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeRight . '+' . ( $rightIdP - 1 - $rightId ) . ' ELSE ' . $this->_sqlNodeRight . ' END ';
           
            $whr = '(' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ' ' . 'OR ' . $this->_sqlNodeRight . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ')';
        }
      	
        //print "<pre>";
        //print wordwrap( $set, 100, '<br />' );
        //print "\n" . wordwrap( $whr, 100, '<br />' );
        //exit();
        /* Pre formatted */
		$this->DB->update( $this->_sqlTable, $set, $whr, false, true );
		
        return true;
    }
    
    
	/**
     * Delete a node. Any children are moved to root.
     *
     * @param	int		Node ID
     * @return	boolean
     */
	public function deleteNode( $nodeId )
	{
		$nInfo = $this->fetchNodeInfo( $nodeId );
    	
		if ( ! isset( $nInfo[0] ) )
    	{
    		return false;
    	}
    	
    	list( $leftId, $rightId, $level ) = $nInfo;
    	
    	/* Delete from DB */
    	$this->DB->delete( $this->_sqlTable, $this->_sqlPrimaryID . '=' . intval( $nodeId ) );

	    /* Move other nodes down */
	    $set = $this->_sqlNodeLevel . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLevel . ' - 1 ELSE ' . $this->_sqlNodeLevel . ' END, '
	     	 . $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeRight . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeRight . ' - 1 '
	     	 . 'WHEN ' . $this->_sqlNodeRight . ' > ' . $rightId . ' THEN ' . $this->_sqlNodeRight . ' - 2 ELSE ' . $this->_sqlNodeRight . ' END, '
	     	 . $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_sqlNodeLeft . ' - 1 '
	     	 . 'WHEN ' . $this->_sqlNodeLeft . ' > ' . $rightId . ' THEN ' . $this->_sqlNodeLeft . ' - 2 ELSE ' . $this->_sqlNodeLeft . ' END ';
	    
	    $whr = $this->_sqlNodeRight . ' > ' . $leftId;
	     
		/* Pre formatted */
		$this->DB->update( $this->_sqlTable, $set, $whr, false, true );
		
        return true;
	 }
	
	/**
     * Delete a node branch (node + children)
     *
     * @param	int		Node ID
     * @return	boolean
     */
	public function deleteBranch( $nodeId )
	{
		$nInfo = $this->fetchNodeInfo( $nodeId );
    	
		if ( ! isset( $nInfo[0] ) )
    	{
    		return false;
    	}
    	
    	list( $leftId, $rightId, $level ) = $nInfo;
    	
    	/* Delete from DB */
    	$this->DB->delete( $this->_sqlTable, $this->_sqlNodeLeft . ' BETWEEN ' . $leftId . ' AND ' . $rightId );
	
		/* Fix position */    
		$deltaId = ( ( $rightId - $leftId ) + 1 );
		
		$set = $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' > ' . $leftId.' THEN ' . $this->_sqlNodeLeft . ' - ' . $deltaId . ' ELSE ' . $this->_sqlNodeLeft . ' END, '
	     	 . $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeRight . ' > ' . $leftId . ' THEN ' . $this->_sqlNodeRight . ' - ' . $deltaId . ' ELSE ' . $this->_sqlNodeRight . ' END ';
		
		$whr = $this->_sqlNodeRight . ' > ' . $rightId;
	    
	    /* Pre formatted */
		$this->DB->update( $this->_sqlTable, $set, $whr, false, true );
		
		return true;
	 }

    /**
     * Switch a node's position within it's level
     *
     * @access	public
     * @param	int		ID1
     * @param	int		ID 2
     * @param	string	position (before/after)
     * @return 	boolean
     */
    public function movePosition( $id1, $id2, $position='after')
    {
    	$nInfo = $this->fetchNodeInfo( $id1 );
    	
    	if ( ! isset( $nInfo[0] ) )
    	{
    		return false;
    	}
    	
    	list( $leftId1, $rightId1, $level1 ) = $nInfo;
    	
    	$nInfo = $this->fetchNodeInfo( $id2 );
    	
    	if ( ! isset( $nInfo[0] ) )
    	{
    		return false;
    	}
    	
    	list( $leftId2, $rightId2, $level2 ) = $nInfo;
    	
    	/* On the level? */
    	if ( $level1 != $level2 )
    	{
    		return false;
    	}
    	
        /* Before? */
        if ( $position == 'before' )
        {
            if ( $leftId1 > $leftId2 )
            {
                $set = $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_sqlNodeRight . ' - ' . ($leftId1 - $leftId2) . ' '
                	 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_sqlNodeRight . ' +  ' . ( $rightId1 - $leftId1 + 1 ) . ' ELSE ' . $this->_sqlNodeRight . ' END, '
                	 . $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_sqlNodeLeft . ' - ' . ($leftId1 - $leftId2) . ' '
                	 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_sqlNodeLeft . ' + ' . ( $rightId1 - $leftId1 + 1 ) . ' ELSE ' . $this->_sqlNodeLeft . ' END ';
                
                $whr = $this->_sqlNodeLeft . ' BETWEEN ' . $leftId2 . ' AND ' . $rightId1;
            }
            else
            {
                $set = $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_sqlNodeRight . ' + ' . ( ( $leftId2 - $leftId1 ) - ( $rightId1 - $leftId1 + 1 ) ) . ' '
                	 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . ( $rightId1 + 1 ) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->_sqlNodeRight . ' - ' . ( ( $rightId1 - $leftId1 + 1 ) ) . ' ELSE ' . $this->_sqlNodeRight . ' END, '
                	 . $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_sqlNodeLeft . ' + ' . ( ( $leftId2 - $leftId1 ) - ( $rightId1 - $leftId1 + 1 ) ) . ' '
                	 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . ( $rightId1 + 1 ) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->_sqlNodeLeft . ' - ' . ( $rightId1 - $leftId1 + 1 ) . ' ELSE ' . $this->_sqlNodeLeft . ' END ';
                
                $whr = $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . ($leftId2 - 1);
            }
        }
        
        if ( $position == 'after' )
        {
            if ( $leftId1 > $leftId2 )
            {
                $set = $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_sqlNodeRight . ' - ' . ( $leftId1 - $leftId2 - ( $rightId2 - $leftId2 + 1 ) ) . ' '
                	 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . ( $rightId2 + 1 ) . ' AND ' . ( $leftId1 - 1 ) . ' THEN ' . $this->_sqlNodeRight . ' +  ' . ( $rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_sqlNodeRight . ' END, '
                	 . $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_sqlNodeLeft . ' - ' . ($leftId1 - $leftId2 - ( $rightId2 - $leftId2 + 1 ) ) . ' '
                	 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . ( $rightId2 + 1 ) . ' AND ' . ( $leftId1 - 1 ) . ' THEN ' . $this->_sqlNodeLeft . ' + ' . ( $rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_sqlNodeLeft . ' END ';
                
                $whr = $this->_sqlNodeLeft . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . $rightId1;
            }
            else
            {
                $set = $this->_sqlNodeRight . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_sqlNodeRight . ' + ' . ($rightId2 - $rightId1) . ' '
                	 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . ( $rightId1 + 1 ) . ' AND ' . $rightId2 . ' THEN ' . $this->_sqlNodeRight . ' - ' . ( ( $rightId1 - $leftId1 + 1 ) ) . ' ELSE ' . $this->_sqlNodeRight . ' END, '
                	 . $this->_sqlNodeLeft . ' = CASE WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_sqlNodeLeft . ' + ' . ($rightId2 - $rightId1) . ' '
                	 . 'WHEN ' . $this->_sqlNodeLeft . ' BETWEEN ' . ( $rightId1 + 1 ) . ' AND ' . $rightId2 . ' THEN ' . $this->_sqlNodeLeft . ' - ' . ( $rightId1 - $leftId1 + 1 ) . ' ELSE ' . $this->_sqlNodeLeft . ' END ';
                
                $where = $this->_sqlNodeLeft . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId2;
            }
        }
        
        /* Pre formatted */
		$this->DB->update( $this->_sqlTable, $set, $whr, false, true );
		
        return true;
    }
    
    
    /**
    * Returns record count
    *
    * @return int
    */
    public function recordCount()
    {
        return $this->DB->getTotalRows( $this->_dbHandle );
    }

    /**
    * Returns the current row.
    *
    * @return array
    */
    public function nextRow()
    {
        return $this->DB->fetch( $this->_dbHandle );
    }
    
    /**
     * Rebuild the tree
     *
     * @access	public
     * @param	int		Root ID
     * @param	int		Left value
     * @param	int		Depth
     * @return	nothing
     */
    function rebuildTree( $root=0, $left=1, $depth=-1 )
    {
    	$right  = $left + 1;
    	$nDepth = $depth + 1;
    	
    	$this->DB->build( array( 'select' => '*',
    							 'from'   => $this->_sqlTable,
    							 'where'  => $this->_sqlParentID . '=' . intval( $root ),
    							 'order'  => $this->_sqlOrder ) );
    							 
    	$o = $this->DB->execute();
    	
    	while( $row = $this->DB->fetch( $o ) )
    	{
    		$right = $this->rebuildTree( $row[ $this->_sqlPrimaryID ], $right, $nDepth );
    	} 
    	
    	$this->DB->update( $this->_sqlTable, array( $this->_sqlNodeLeft  => $left,
    												$this->_sqlNodeRight => $right,
    												$this->_sqlNodeLevel => $depth ), $this->_sqlPrimaryID . '=' . $root );
    												
    	return $right + 1;
    }
    
	/**
	 * Drop the node cache
	 * 
	 */
	public function dropCache()
	{
		$this->_nodeCache = array();
	}
	
    /**
     * Show the entire tree as HITMUHL
     *
     * @access	public
     */
    public function debugShowTree()
    {
    	/* Init */
    	$content = '';
    	
    	/* reset params */
    	$this->setOrder(null);
    	$this->setWhere(null);
    	
    	/* Load entire tree */
    	$this->fetchTree();
    	
    	while( $item = $this->nextRow() )
    	{
        	$content .= str_repeat( '&nbsp;', 6 * $item[ $this->_sqlNodeLevel ] ) . '(' . $item[ $this->_sqlPrimaryID ] . ')<strong>' . $item[ $this->_sqlTitle ] . '</strong><br />';
        }
        
        return $content;
    }
}

/**
 * Static class of functions for post processing node data
 */
class nodeFunctions
{
	/**
	 * Create a nestled array of data from a tree or branch
	 *
	 * @access	public
	 * @param	array		array of data from classNodes::fetch*
	 * @param	string		Field name for 'right'
	 * @return	Array		
	 */
	static public function nestle( array $array, $nDepthField='level' )
	{
		/* Init */
		$nested = array();
        $depths = array();
		
        foreach( $array as $key => $data )
        {
            if ( $data[ $nDepthField ] == 0 )
            {
                $nested[ $key ] = $data;
                $depths[ $data[ $nDepthField ] + 1] = $key;
            }
            else
            {
                $parent =& $nested;
                
                for( $i = 1; $i <= ( $data[ $nDepthField ] ); $i++ )
                {
                    $parent =& $parent[ $depths[ $i ] ];
                }

                $parent['_children_'][ $key ] = $data;
                $depths[ $data[ $nDepthField ] + 1 ] = $key;
            }
        }

        return $nested;
	}

}