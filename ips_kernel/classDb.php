<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Database Abstraction Layer
 * Last Updated: $Date: 2012-05-29 04:58:00 -0400 (Tue, 29 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Monday 28th February 2005 16:46
 * @version		$Revision: 10803 $
 *
 * Basic Usage Examples
 * <code>
 * $db = new db_driver();
 * Update:
 * $db->update( 'table', array( 'field' => 'value', 'field2' => 'value2' ), 'id=1' );
 * Insert
 * $db->insert( 'table', array( 'field' => 'value', 'field2' => 'value2' ) );
 * Delete
 * $db->delete( 'table', 'id=1' );
 * Select
 * $db->build( array( 'select' => '*',
 *						   'from'   => 'table',
 *						   'where'  => 'id=2 and mid=1',
 *						   'order'  => 'date DESC',
 *						   'limit'  => array( 0, 30 ) ) );
 * $db->execute();
 * while( $row = $db->fetch() ) { .... }
 * Select with join
 * $db->build( array( 'select'   => 'd.*',
 * 						   'from'     => array( 'dnames_change' => 'd' ),
 * 						   'where'    => 'dname_member_id='.$id,
 * 						   'add_join' => array( 0 => array( 'select' => 'm.members_display_name',
 * 													 'from'   => array( 'members' => 'm' ),
 * 													 'where'  => 'm.member_id=d.dname_member_id',
 * 													 'type'   => 'inner' ) ),
 * 						   'order'    => 'dname_date DESC' ) );
 *  $db->execute();
 * </code>
 */

/**
 * This can be overridden by using
 * $DB->allow_sub_select = 1;
 * before any query construct
 */

define( 'IPS_DB_ALLOW_SUB_SELECTS', 0 );

/**
 * Database interface
 */
interface interfaceDb
{
    /**
	 * Connect to database server
	 *
	 * @return	@e boolean
	 */
	public function connect();

    /**
	 * Return the connection ID
	 *
	 * @return	@e resource
	 */
	public function getConnectionId();
	
    /**
	 * Close database connection
	 *
	 * @return	@e boolean
	 */
	public function disconnect();
	
	/**
	 * Returns the currently formed SQL query
	 *
	 * @return	@e string
	 */
	public function fetchSqlString();

    /**
	 * Delete data from a table
	 *
	 * @param	string 		Table name
	 * @param	string 		[Optional] Where clause
	 * @param	string		[Optional] Order by
	 * @param	array		[Optional] Limit clause
	 * @param	boolean		[Optional] Run on shutdown
	 * @return	@e resource
	 */
	public function delete( $table, $where='', $orderBy='', $limit=array(), $shutdown=false );
	
    /**
	 * Update data in a table
	 *
	 * @param	string 		Table name
	 * @param	mixed 		Array of field => values, or pre-formatted "SET" clause
	 * @param	string 		[Optional] Where clause
	 * @param	boolean		[Optional] Run on shutdown
	 * @param	boolean		[Optional] $set is already pre-formatted
	 * @return	@e resource
	 */
	public function update( $table, $set, $where='', $shutdown=false, $preformatted=false );
	
    /**
	 * Insert data into a table
	 *
	 * @param	string 		Table name
	 * @param	array 		Array of field => values
	 * @param	boolean		[Optional] Run on shutdown
	 * @return	@e resource
	 */
	public function insert( $table, $set, $shutdown=false );
	
    /**
	 * Insert record into table if not present, otherwise update existing record
	 *
	 * @param	string 		Table name
	 * @param	array 		Array of field => values
	 * @param	array 		Array of fields to check
	 * @param	boolean		[Optional] Run on shutdown
	 * @return	@e resource
	 */
	public function replace( $table, $set, $where, $shutdown=false );
	
    /**
	 * Run a "kill" statement
	 *
	 * @param	integer 	Thread ID
	 * @return	@e resource
	 */
	public function kill( $threadId );

    /**
	 * Takes array of set commands and generates a SQL formatted query
	 *
	 * @param	array		Set commands (select, from, where, order, limit, etc)
	 * @return	@e void
	 */
	public function build( $data );
	
    /**
	 * Build a query based on template from cache file
	 *
	 * @param	string		Name of query file method to use
	 * @param	array		Optional arguments to be parsed inside query function
	 * @param	string		Optional class name
	 * @return	@e void
	 */
	public function buildFromCache( $method, $args=array(), $class='sql_queries' );
	
    /**
	 * Executes stored SQL query
	 *
	 * @return	@e resource
	 */
	public function execute();
	
    /**
	 * Stores a query for shutdown execution
	 *
	 * @return	@e mixed
	 */
	public function executeOnShutdown();
	
    /**
	 * Generates and executes SQL query, and returns the first result
	 *
	 * @param	array		Set commands (select, from, where, order, limit, etc)
	 * @return	@e array
	 */
	public function buildAndFetch( $data );
	
    /**
	 * Execute a direct database query
	 *
	 * @param	string		Database query
	 * @param	boolean		[Optional] Do not convert table prefix
	 * @return	@e resource
	 */
	public function query( $the_query, $bypass=false );

    /**
	 * Retrieve number of rows affected by last query
	 *
	 * @return	@e integer
	 */
	public function getAffectedRows();
	
    /**
	 * Retrieve number of rows in result set
	 *
	 * @param	resource	[Optional] Query id
	 * @return	@e integer
	 */
	public function getTotalRows( $query_id=null );
	
    /**
	 * Retrieve latest autoincrement insert id
	 *
	 * @return	@e integer
	 */
	public function getInsertId();
	
    /**
	 * Retrieve the current thread id
	 *
	 * @return	@e integer
	 */
	public function getThreadId();
	
    /**
	 * Free result set from memory
	 *
	 * @param	resource	[Optional] Query id
	 * @return	@e void
	 */
	public function freeResult( $query_id=null );

    /**
	 * Retrieve row from database
	 *
	 * @param	resource	[Optional] Query result id
	 * @return	@e mixed
	 */
	public function fetch( $query_id=null );
	
	/**
	 * Return the number calculated rows (as if there was no limit clause)
	 *
	 * @param	string 		[ alias name for the count(*) ]
	 * @return	@e integer
	 */
	public function fetchCalculatedRows( $alias='count' );
	
    /**
	 * Get array of fields in result set
	 *
	 * @param	resource	[Optional] Query id
	 * @return	@e array
	 */
	public function getResultFields( $query_id=null );

    /**
	 * Subqueries supported by driver?
	 *
	 * @return	@e boolean
	 */
	public function checkSubquerySupport();
	
    /**
	 * Fulltext searching supported by driver?
	 *
	 * @return	@e boolean
	 */
	public function checkFulltextSupport();
	
    /**
	 * Boolean fulltext searching supported by driver?
	 *
	 * @return	@e boolean
	 */
	public function checkBooleanFulltextSupport();
	
	/**
	 * Test to see whether an index exists in a table
	 *
	 * @param	string		Index name
	 * @param	string		Table name
	 * @return	@e boolean
	 */
	public function checkForIndex( $index, $table );

    /**
	 * Test to see whether a field exists in a table
	 *
	 * @param	string		Field name
	 * @param	string		Table name
	 * @return	@e boolean
	 */
	public function checkForField( $field, $table );
	
    /**
	 * Test to see whether a table exists
	 *
	 * @param	string		Table name
	 * @return	@e boolean
	 */
	public function checkForTable( $table );
	
    /**
	 * Drop database table
	 *
	 * @param	string		Table to drop
	 * @return	@e resource
	 */
	public function dropTable( $table );
	
    /**
	 * Drop field in database table
	 *
	 * @param	string		Table name
	 * @param	string		Field to drop
	 * @return	@e resource
	 */
	public function dropField( $table, $field );
	
    /**
	 * Add field to table in database
	 *
	 * @param	string		Table name
	 * @param	string		Field to add
	 * @param	string		Field type
	 * @param	string		[Optional] Default value
	 * @return	@e resource
	 */
	public function addField( $table, $field, $type, $default=NULL );
	
	/**
	 * Add index to database column
	 *
	 * @param	string		Table name		table
	 * @param	string		Index name		multicol
	 * @param	string		Fieldlist		col1, col2
	 * @param	bool		Is primary key?
	 * @return	@e resource
	 * @todo 	[Future] Add support for fulltext indexes (right now can only do generic index or primary key)
	 * @see		addFulltextIndex()
	 */
	public function addIndex( $table, $name, $fieldlist, $isPrimary=false );
	
    /**
	 * Change field in database table
	 *
	 * @param	string		Table name
	 * @param	string		Existing field name
	 * @param	string		New field name
	 * @param	string		[Optional] Field type
	 * @param	string		[Optional] Default value
	 * @return	@e resource
	 */
	public function changeField( $table, $old_field, $new_field, $type='', $default=NULL );
	
    /**
	 * Optimize database table
	 *
	 * @param	string		Table name
	 * @return	@e resource
	 */
	public function optimize( $table );
	
    /**
	 * Add fulltext index to database column
	 *
	 * @param	string		Table name
	 * @param	string		Field name
	 * @return	@e resource
	 */
	public function addFulltextIndex( $table, $field );
	
    /**
	 * Get table schematic
	 *
	 * @param	string		Table name
	 * @return	@e array
	 */
	public function getTableSchematic( $table );
	
    /**
	 * Get array of table names in database
	 *
	 * @return	@e array
	 */
	public function getTableNames();
	
    /**
	 * Get array of field names in table
	 *
	 * @param	string		Table name
	 * @return	@e array
	 */
	public function getFieldNames( $table );
	
    /**
	 * Determine if table already has a fulltext index
	 *
	 * @param	string		Table name
	 * @return	@e boolean
	 */
	public function getFulltextStatus( $table );
	
    /**
	 * Retrieve SQL server version
	 *
	 * @return	@e string
	 */
	public function getSqlVersion();

    /**
	 * Set debug mode flag
	 *
	 * @param	boolean		[Optional] Set debug mode on/off
	 * @return	@e void
	 */
	public function setDebugMode( $enable=false );
	
    /**
	 * Returns current number queries run
	 *
	 * @return	@e integer
	 */
	public function getQueryCount();
	
	/**
	 * Flushes the currently queued query
	 *
	 * @return	@e void
	 */
	public function flushQuery();

    /**
	 * Load extra SQL query file
	 *
	 * @param	string 		File name
	 * @param	string 		Classname of file
	 * @return	@e void
	 */
	public function loadCacheFile( $filepath, $classname );
	
	/**
	 * Checks to see if a query file has been loaded
	 *
	 * @param	string 		Classname of file
	 * @return	@e void
	 */
	public function hasLoadedCacheFile( $classname );

	/**
	 * Set fields that shouldn't be escaped
	 *
	 * @param	array 		SQL table fields
	 * @return	@e void
	 */
	public function preventAddSlashes( $fields=array() );
	
    /**
	 * Compiles SQL fields for insertion
	 *
	 * @param	array		Array of field => value pairs
	 * @return	@e array
	 */
	public function compileInsertString( $data );
	
    /**
	 * Compiles SQL fields for update query
	 *
	 * @param	array		Array of field => value pairs
	 * @return	@e string
	 */
	public function compileUpdateString( $data );
	
    /**
	 * Escape strings for DB insertion
	 *
	 * @param	string		Text to escape
	 * @return	@e string
	 */
	public function addSlashes( $t );

    /**
	 * Build concat string
	 *
	 * @param	array		Array of data to concat
	 * @return	@e string
	 */
	public function buildConcat( $data );
	
    /**
	 * Build CAST string
	 *
	 * @param	string		Value to CAST
	 * @param	string		Type to cast to
	 * @return	@e string
	 */
	public function buildCast( $data, $columnType );
	
    /**
	 * Build between statement
	 *
	 * @param	string		Column
	 * @param	integer		Value 1
	 * @param	integer		Value 2
	 * @return	@e string
	 */
	public function buildBetween( $column, $value1, $value2 );
	
    /**
	 * Build regexp 'or'
	 *
	 * @param	string		Column
	 * @param	array		Array of values to allow
	 * @return	@e string
	 */
	public function buildRegexp( $column, $data );
	
	/**
	 * Build LIKE CHAIN string (ONLY supports a regexp equivalent of "or field like value")
	 *
	 * @param	string		Database column
	 * @param	array		Array of values to allow
	 * @return	@e string
	 */
	public function buildLikeChain( $column, $data );
	
    /**
	 * Build instr string
	 *
	 * @param	string		String to look in
	 * @param	string		String to look for
	 * @return	@e string
	 */
	public function buildInstring( $look_in, $look_for );
	
    /**
	 * Build substr string
	 *
	 * @param	string		String of characters/Column
	 * @param	integer		Offset
	 * @param	integer		[Optional] Number of chars
	 * @return	@e string
	 */
	public function buildSubstring( $look_for, $offset, $length=0 );
	
    /**
	 * Build distinct string
	 *
	 * @param	string		Column name
	 * @return	@e string
	 */
	public function buildDistinct( $column );
	
    /**
	 * Build length string
	 *
	 * @param	string		Column name
	 * @return	@e string
	 */
	public function buildLength( $column );
	
	/**
	 * Build lower string
	 *
	 * @param	string		Column name
	 * @return	@e string
	 */
	public function buildLower( $column );
	
    /**
	 * Build right string
	 *
	 * @param	string		Column name
	 * @param	integer		Number of chars
	 * @return	@e string
	 */
	public function buildRight( $column, $chars );
	
    /**
	 * Build left string
	 *
	 * @param	string		Column name
	 * @param	integer		Number of chars
	 * @return	@e string
	 */
	public function buildLeft( $column, $chars );

	/**
	 * Builds a call to MD5 equivalent
	 *
	 * @param	string		Column name or value to MD5-hash
	 * @return	@e string
	 */
	public function buildMd5Statement( $statement );
	
    /**
	 * Build "is null" and "is not null" string
	 *
	 * @param	boolean		is null flag
	 * @return	@e string
	 */
	public function buildIsNull( $is_null=true );

    /**
	 * Build coalesce statement
	 *
	 * @param	array		Values to check
	 * @return	@e string
	 */
	public function buildCoalesce( $values=array() );
	
	/**
	 * Build from_unixtime string
	 *
	 * @param	string		Column name
	 * @param	string		[Optional] Format
	 * @return	@e string
	 */
	public function buildFromUnixtime( $column, $format='' );

	/**
	 * Build unix_timestamp string
	 *
	 * @param	string		Column name
	 * @return	@e string
	 */
	public function buildUnixTimestamp( $column );
	
    /**
	 * Build date_format string
	 *
	 * @param	string		Date string
	 * @param	string		Format
	 * @return	@e string
	 */
	public function buildDateFormat( $column, $format );
	
	/**
	 * Build rand() string
	 *
	 * @return	@e string
	 */
	public function buildRandomOrder();
	
	/**
	 * Build fulltext search string
	 *
	 * @param	string		Column to search against
	 * @param	string		String to search
	 * @param	boolean		Search in boolean mode
	 * @param	boolean		Return a "as ranking" statement from the build
	 * @param	boolean		Use fulltext search
	 * @return	@e string
	 */
	public function buildSearchStatement( $column, $keyword, $booleanMode=true, $returnRanking=false, $useFulltext=true );

    /**
	 * Prints SQL error message
	 *
	 * @param	string		Additional error message
	 * @return	@e mixed
	 */
	public function throwFatalError( $the_error='' );
	
	/**
	 * Returns a portion of a WHERE query suitable for use.
	 * Specific to tables with a field that has a comma separated list of IDs.
	 *
	 * @param	array		Array of perm IDs
	 * @param	string		DB field to search in
	 * @param	boolean		check for '*' in the field to denote 'global'
	 * @return	@e mixed
	 */
	public function buildWherePermission( array $ids, $field='', $incStarCheck=true );

	/**
	 * Set Timezone
	 *
	 * @param	float		UTC Offset (e.g. 6 or -4.5)
	 * @return	@e void
	 */
	public function setTimeZone( $offset );
	
    /**
	 * Logs SQL error message to log file
	 *
	 * @param	string		SQL Query
	 * @param	string		Data to log (i.e. error message)
	 * @param	integer		Timestamp for log
	 * @return	@e void
	 */
	public function writeDebugLog( $query, $data, $endtime );
}

/**
 * Abstract database class
 */
abstract class dbMain
{
	/**
	 * DB object array
	 *
	 * @var 		array
	 */
	public $obj = array(	"sql_database"			=> ""			,
							"sql_user"				=> "root"		,
							"sql_pass"				=> ""			,
							"sql_host"				=> "localhost"	,
							"sql_port"				=> ""			,
							"persistent"			=> "0"			,
							"sql_tbl_prefix"		=> ""			,
							"cached_queries"		=> array()		,
							'shutdown_queries'		=> array()		,
							'debug'					=> 0			,
							'use_shutdown'			=> 1			,
							'query_cache_file'		=> ''			,
							'force_new_connection'	=> 0			,
							'error_log'				=> ''			,
							'use_error_log'			=> 0			,
							'use_debug_log'			=> 0			,
							'bad_log'				=> ''			,
							'use_bad_log'			=> 0			,
					 );
	
	/**
	 * Error message
	 *
	 * @var		string
	 */
	public $error 				= "";
	
	/**
	 * Error code
	 *
	 * @var 	mixed
	 */
	public $error_no			= 0;
	
	/**
	 * Return error message or die inline
	 *
	 * @var 	boolean
	 */
	public $return_die        = false;
	
	/**
	 * DB query failed
	 *
	 * @var 	boolean
	 */
	public $failed            = false;
	
	/**
	 * Object reference to query cache file
	 *
	 * @var 	object
	 */
	protected $sql               = null;
	
	/**
	 * Current sql query
	 *
	 * @var 	string
	 */
	protected $cur_query         = "";
	
	/**
	 * Current DB query ID
	 *
	 * @var 	resource
	 */
	protected $query_id          = null;
	
	/**
	 * Current DB connection ID
	 *
	 * @var 	resource
	 */
	protected $connection_id     = null;
	
	/**
	 * Number of queries run so far
	 *
	 * @var 	integer
	 */
	public $query_count       = 0;
	
	/**
	 * Escape / don't escape slashes during insert ops
	 *
	 * @var		boolean
	 */
	public $manual_addslashes = false;
	
	/**
	 * Is a shutdown query
	 *
	 * @var 	boolean
	 */
	protected $is_shutdown       = false;
	
	/**
	 * Prefix already converted
	 *
	 * @var 	boolean
	 */
	public $no_prefix_convert = false;
	
	/**
	 * DB record row
	 *
	 * @var 	array
	 */
	public $record_row        = array();
	
	/**
	 * Extra classes loaded
	 *
	 * @var 	array
	 */
	public $loaded_classes    = array();
	
	/**
	 * Optimization to stop querying the same loaded cache over and over
	 *
	 * @var		array
	 */
	protected $_triedToLoadCacheFiles = array();
	
	/**
	 * Connection variables set when installed
	 *
	 * @var 	array
	 */
	public $connect_vars      = array();
	
	/**
	 * Over-ride guessed data types in insert/update ops
	 *
	 * @var 	array
	 */
	protected $_dataTypes   = array();
	
	/**
	 * Select which fields aren't escaped during insert/update ops.  Set using preventAddSlashes()
	 *
	 * @var 	array
	 */
	protected $no_escape_fields  = array();
	
	/**
	 * Classname of query cache file
	 *
	 * @var 	string
	 */
	public $sql_queries_name  = 'sql_queries';
	
	/**
	 * SQL server version (human)
	 *
	 * @var 	string
	 */
	public $sql_version			= "";
	
	/**
	 * SQL server version (long)
	 *
	 * @var 	string
	 */
	public $true_version		= 0;
	
	/**
	 * Allow sub selects for this query
	 *
	 * @var 	boolean
	 */
	public $allow_sub_select = false;
	
	/**
	 * Use (root path)/cache/ipsDriverError.php template
	 *
	 * @var 	boolean
	 */
	public $use_template = true;
	
	/**
	 * Driver Class Name
	 * 
	 * @var		string
	 */
	protected $usingClass = '';
	
	/**
	 * Field encapsulation character
	 * 
	 * @see	http://community.invisionpower.com/tracker/issue-19899-postgresql-driver-fields-not-properly-escaped/
	 * @var		string
	 */
	public $fieldEncapsulate	= "'";
	
	/**
	 * Field name encapsulation character
	 * 
	 * @see	http://community.invisionpower.com/tracker/issue-20621-postgresql-driver-field-names-not-properly-escaped/
	 * @var		string
	 */
	public $fieldNameEncapsulate	= '';

	/**
	 * db_driver constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		//--------------------------------------------
		// Set up any required connect vars here:
		//--------------------------------------------
		
     	$this->connect_vars = array();
	}
    
	/**
	 * Global connect class
	 *
	 * @return	@e void
	 */
	public function connect()
	{
		$this->usingClass   = strtolower( get_class( $this ) );
		$this->writeDebugLog( '{start}', '', '' );
	}
	
	/**
	 * Returns the currently formed SQL query
	 *
	 * @return	@e string
	 */
	public function fetchSqlString()
	{
		return $this->cur_query;
	}
	
	/**
	 * Turns table identity insert on/off
	 *
	 * @param	string		Table name
	 * @param	string		On or Off
	 * @return	@e string
	 */
	public function setTableIdentityInsert( $tableName, $status='OFF' )
	{
	}
	
	/**
	 * Force a column data type
	 *
	 * @param	mixed	$column	Column name, or an array of column names
	 * @param	string	$type	Data type to use
	 * @return	@e void
	 */
	public function setDataType( $column, $type='string' )
	{
		if( is_array($column) )
		{
			foreach( $column as $_column )
			{
				$this->_dataTypes[ $_column ]	= $type;
			}
		}
		else
		{
			$this->_dataTypes[ $column ]	= $type;
		}
	}
	
	/**
	 * Reset forced data types
	 *
	 * @return	@e void
	 */
	public function resetDataTypes()
	{
		$this->_dataTypes	= array();
	}
	
    /**
	 * Takes array of set commands and generates a SQL formatted query
	 *
	 * @param	array		Set commands (select, from, where, order, limit, etc)
	 * @return	@e void
	 */
    public function build( $data )
    {
		/* Inline build from cache files? Not all drviers may have a cache file.. */
		if ( $this->usingClass != 'db_driver_mysql' AND ( $data['queryKey'] AND $data['queryLocation'] AND $data['queryClass'] ) )
		{ 
			if ( self::loadCacheFile( $data['queryLocation'], $data['queryClass'] ) === TRUE )
			{
				self::buildFromCache( $data['queryKey'], $data['queryVars'], $data['queryClass'] );
				return;
			}
		}
		
    	if ( !empty($data['select']) )
    	{
    		$this->_buildSelect( $data['select'], $data['from'], isset($data['where']) ? $data['where'] : '', isset( $data['add_join'] ) ? $data['add_join'] : array(), isset( $data['calcRows'] ) ? $data['calcRows'] : '' );
    	}
    	
    	if ( !empty($data['update']) )
    	{
    		$this->update( $data['update'], $data['set'], isset($data['where']) ? $data['where'] : '', false, true );
    		return;
    	}
    	
    	if ( !empty($data['delete']) )
    	{
    		$this->delete( $data['delete'], $data['where'], $data['order'], $data['limit'], false );
    		return;
    	}
    	
    	if ( !empty($data['group']) )
    	{
    		$this->_buildGroupBy( $data['group'] );
    	}
    	
    	if ( !empty($data['having']) )
    	{
    		$this->_buildHaving( $data['having'] );
    	} 	
    	
    	if ( !empty($data['order']) )
    	{
    		$this->_buildOrderBy( $data['order'] );
    	}
    	
		if ( isset( $data['calcRows'] ) AND $data['calcRows'] === TRUE )
		{
			$this->_buildCalcRows();
		}
		
    	if ( isset($data['limit']) && is_array( $data['limit'] ) )
    	{
    		$this->_buildLimit( $data['limit'][0], $data['limit'][1] );
    	}
    }
    
    /**
	 * Build a query based on template from cache file
	 *
	 * @param	string		Name of query file method to use
	 * @param	array		Optional arguments to be parsed inside query function
	 * @param	string		Optional class name
	 * @return	@e void
	 */
    public function buildFromCache( $method, $args=array(), $class='sql_queries' )
    {
    	$instance = null;
	
    	if ( $class == 'sql_queries' and method_exists( $this->sql, $method ) )
		{
    		$instance = $this->sql;
		}
		else if( $class != 'sql_queries' AND method_exists( $this->loaded_classes[ $class ], $method ) )
		{
    		$instance = $this->loaded_classes[ $class ];
		}

		if ( $class == 'sql_queries' and !method_exists( $this->sql, $method ) )
		{
			if ( is_array( $this->loaded_classes ) )
			{
				foreach ( $this->loaded_classes as $class_name => $class_instance )
				{
					if ( method_exists( $this->loaded_classes[ $class_name ], $method ) )
					{
						$instance = $this->loaded_classes[ $class_name ];
						continue;
					}
				}
			}
		}
		
    	if( $instance )
    	{
    		$this->cur_query .= $instance->$method( $args );
    	}
    }
    
    /**
	 * Executes stored SQL query
	 *
	 * @return	@e resource
	 */
    public function execute()
    {
    	if ( $this->cur_query != "" )
    	{
    		$res = $this->query( $this->cur_query );
    	}
    	
    	$this->cur_query   	= "";
    	$this->is_shutdown 	= false;

    	return $res;
    }
    
    /**
	 * Stores a query for shutdown execution
	 *
	 * @return	@e mixed
	 */
    public function executeOnShutdown()
    {
    	if ( ! $this->obj['use_shutdown'] )
    	{
    		$this->is_shutdown 		= true;
    		return $this->execute();
    	}
    	else
    	{
    		$this->obj['shutdown_queries'][] = $this->cur_query;
    		$this->cur_query = "";
    	}
    }
    
    /**
	 * Generates and executes SQL query, and returns the first result
	 *
	 * @param	array		Set commands (select, from, where, order, limit, etc)
	 * @return	@e array
	 */
    public function buildAndFetch( $data )
    {
    	$this->build( $data );

    	$res = $this->execute();
    	
    	if ( !empty($data['select']) )
    	{
    		return $this->fetch( $res );
    	}
    }
    
    /**
	 * Determine if query is shutdown and run it
	 *
	 * @param	string 		Query
	 * @param	boolean 	[Optional] Run on shutdown
	 * @return	@e mixed
	 */
	protected function _determineShutdownAndRun( $query, $shutdown=false )
	{
    	//-----------------------------------------
    	// Shut down query?
    	//-----------------------------------------
    	
    	$current							= $this->no_prefix_convert;
    	$this->no_prefix_convert 			= true;
    	
    	if ( $shutdown )
    	{
    		if ( ! $this->obj['use_shutdown'] )
			{
				$current_shutdown			= $this->is_shutdown;
				$this->is_shutdown 			= true;
				$return 					= $this->query( $query );
				$this->no_prefix_convert 	= $current;
				$this->is_shutdown 			= $current_shutdown;
				return $return;
			}
			else
			{
				$this->obj['shutdown_queries'][] = $query;
				$this->no_prefix_convert 	= $current;
				$this->cur_query 			= "";
			}
    	}
    	else
    	{
    		$return 					= $this->query( $query );
    		$this->no_prefix_convert 	= $current;
    		return $return;
    	}
	}

	/**
	 * Test to see whether an index exists in a table
	 *
	 * @param	string		Index name
	 * @param	string		Table name
	 * @return	@e boolean
	 */
	public function checkForIndex( $index, $table )
	{
		return false;
	}
	
    /**
	 * Test to see whether a field exists in a table
	 *
	 * @param	string		Field name
	 * @param	string		Table name
	 * @return	@e boolean
	 */
	public function checkForField( $field, $table )
	{
		$current			= $this->return_die;
		$this->return_die 	= true;
		$this->error      	= "";
		
		$this->build( array( 'select' => "COUNT($field) as count", 'from' => $table ) );
		$this->execute();
		
		$return = true;
		
		if ( $this->failed )
		{
			$return = false;
		}
		
		$this->error		= "";
		$this->return_die	= $current;
		$this->error_no   	= 0;
		$this->failed     	= false;
		
		return $return;
	}

    /**
	 * Set debug mode flag
	 *
	 * @param	boolean		[Optional] Set debug mode on/off
	 * @return	@e void
	 */
	public function setDebugMode( $enable=false )
	{
    	$this->obj['debug'] = intval($enable);
    
    	//-----------------------------------------
     	// If debug, no shutdown....
     	//-----------------------------------------
     	
     	if ( $this->obj['debug'] )
     	{
     		$this->obj['use_shutdown'] = 0;
     	}
	}
	
    /**
	 * Returns current number queries run
	 *
	 * @return	@e integer
	 */
	public function getQueryCount()
	{
		return $this->query_count;
	}
    
	/**
	 * Flushes the currently queued query
	 *
	 * @return	@e void
	 */
	public function flushQuery()
	{
		$this->cur_query = "";
	}
	
    /**
	 * Set SQL Prefix
	 *
	 * @return	@e void
	 */
	protected function _setPrefix()
	{
		/*if ( ! defined( 'IPSDB::driver()' ) )
     	{
     		$this->obj['sql_tbl_prefix'] = isset($this->obj['sql_tbl_prefix']) ? $this->obj['sql_tbl_prefix'] : 'ibf_';
     		
     		define( 'IPSDB::driver()', $this->obj['sql_tbl_prefix'] );
     	}*/
	}
	
	/**
	 * Has loaded cache file
	 *
	 * @param	string 		Classname
	 * @return	@e boolean
	 */
	public function hasLoadedCacheFile( $classname )
	{
		if ( isset( $this->loaded_classes[ $classname ] ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
    /**
	 * Load extra SQL query file
	 *
	 * @param	string 		File name
	 * @param	string 		Classname of file
	 * @param	boolean		Ignore missing files, FALSE $this->error is set, TRUE, nothing happens.
	 * @return	@e boolean
	 */
	public function loadCacheFile( $filepath, $classname, $ignoreMissing=FALSE )
	{
		/* Tried to load this already? */
		if ( ! isset( $this->_triedToLoadCacheFiles[ $classname ] ) )
		{
			/* Try and load it */
	    	if ( ! is_file( $filepath ) AND $ignoreMissing === FALSE )
	    	{
	    		$this->error	= "Cannot locate {$filepath} - exiting!";

				$this->_triedToLoadCacheFiles[ $classname ] = FALSE;
	    	}
	    	else if ( $this->hasLoadedCacheFile( $classname ) !== TRUE )
	    	{
	    		require_once( $filepath );/*noLibHook*/
    		
	    		if( class_exists( $classname ) )
	    		{
	    			$this->loaded_classes[ $classname ] = new $classname( $this );
				
					$this->_triedToLoadCacheFiles[ $classname ] = TRUE;
	    		}
	    	}
		}

		return $this->_triedToLoadCacheFiles[ $classname ];
	}
	
	/**
	 * Returns a portion of a WHERE query suitable for use.
	 * Specific to tables with a field that has a comma separated list of IDs.
	 *
	 * @param	array		Array of perm IDs
	 * @param	string		DB field to search in
	 * @param	boolean		check for '*' in the field to denote 'global'
	 * @return	@e string
	 */
	public function buildWherePermission( array $ids, $field='', $incStarCheck=true )
	{
		/* Just use a LIKE chain for items without a specific implementation */
		$where = '(' . $this->buildLikeChain( $field, $ids ) . ')';
		
		if ( $incStarCheck === true )
		{
			$where .= ' OR ( ' . $field . '=\'*\' )';
		}
		
		return "( " . $where . " )";
	}
	
    /**
	 * Load Query cache file
	 *
	 * @return	@e void
	 */
	protected function _loadCacheFile()
	{
		if ( $this->obj['query_cache_file'] )
     	{
     		require_once( $this->obj['query_cache_file'] );/*noLibHook*/
     	
			$sql_queries_name = $this->sql_queries_name ? $this->sql_queries_name : 'sql_queries';

     		$this->sql = new $sql_queries_name( $this );
     	}
	}

	/**
	 * Set fields that shouldn't be escaped
	 *
	 * @param	array 		SQL table fields
	 * @return	@e void
	 */
	public function preventAddSlashes( $fields=array() )
	{
		$this->no_escape_fields = $fields;
	}
	
    /**
	 * Compiles SQL fields for insertion
	 *
	 * @param	array		Array of field => value pairs
	 * @return	@e array
	 */
	public function compileInsertString( $data )
    {
    	$field_names	= "";
		$field_values	= "";

		foreach( $data as $k => $v )
		{
			$add_slashes = 1;
			
			if ( $this->manual_addslashes )
			{
				$add_slashes = 0;
			}
			
			if ( !empty($this->no_escape_fields[ $k ]) )
			{
				$add_slashes = 0;
			}
			
			if ( $add_slashes )
			{
				$v = $this->addSlashes( $v );
			}
			
			$field_names  .= $this->fieldNameEncapsulate . "$k" . $this->fieldNameEncapsulate . ',';
			
			//-----------------------------------------
			// Forcing data type?
			//-----------------------------------------
			
			if ( substr( $v, -1 ) == '\\' )
			{
				$v = preg_replace( '#\\\{1}$#', '&#92;', $v );
			}
					
			if ( !empty($this->_dataTypes[ $k ]) )
			{
				if ( $this->_dataTypes[ $k ] == 'string' )
				{
					$field_values .= $this->fieldEncapsulate . $v . $this->fieldEncapsulate . ',';
				}
				else if ( $this->_dataTypes[ $k ] == 'int' )
				{
					$field_values .= intval($v).",";
				}
				else if ( $this->_dataTypes[ $k ] == 'float' )
				{
					$field_values .= floatval($v).",";
				}
				if ( $this->_dataTypes[ $k ] == 'null' )
				{
					$field_values .= "NULL,";
				}
			}
			
			//-----------------------------------------
			// No? best guess it is then..
			//-----------------------------------------
			
			else
			{
				if ( is_numeric( $v ) and strcmp( intval($v), $v ) === 0 )
				{
					$field_values .= $v.",";
				}
				else
				{
					$field_values .= $this->fieldEncapsulate . $v . $this->fieldEncapsulate . ',';
				}
			}
		}
		
		$field_names  = rtrim( $field_names, ","  );
		$field_values = rtrim( $field_values, "," );
	
		return array( 'FIELD_NAMES'  => $field_names,
					  'FIELD_VALUES' => $field_values,
					);
	}
	
    /**
	 * Compiles SQL fields for update query
	 *
	 * @param	array		Array of field => value pairs
	 * @return	@e string
	 */
	public function compileUpdateString( $data )
	{
		$return_string = "";
		
		foreach( $data as $k => $v )
		{
			//-----------------------------------------
			// Adding slashes?
			//-----------------------------------------
			
			$add_slashes = 1;
			
			if ( $this->manual_addslashes )
			{
				$add_slashes = 0;
			}
			
			if ( !empty($this->no_escape_fields[ $k ]) )
			{
				$add_slashes = 0;
			}
			
			if ( $add_slashes )
			{
				$v = $this->addSlashes( $v );
			}
			
			//-----------------------------------------
			// Forcing data type?
			//-----------------------------------------
			
			if ( !empty($this->_dataTypes[ $k ]) )
			{
				if ( $this->_dataTypes[ $k ] == 'string' )
				{
					if ( substr( $v, -1 ) == '\\' )
					{
						$v = preg_replace( '#\\\{1}$#', '&#92;', $v );
					}
			
					$return_string .= $k . '=' . $this->fieldEncapsulate . $v . $this->fieldEncapsulate . ',';
				}
				else if ( $this->_dataTypes[ $k ] == 'int' )
				{
					if ( strstr( $v, 'plus:' ) )
					{
						$return_string .= $k . "=" . $k . '+' . intval( str_replace( 'plus:', '', $v ) ).",";
					}
					else if ( strstr( $v, 'minus:' ) )
					{
						$return_string .= $k . "=" . $k . '-' . intval( str_replace( 'minus:', '', $v ) ).",";
					}
					else
					{
						$return_string .= $k . "=" . intval($v) . ",";
					}
				}
				else if ( $this->_dataTypes[ $k ] == 'float' )
				{
					$return_string .= $k . "=" . floatval($v) . ",";
				}
				else if ( $this->_dataTypes[ $k ] == 'null' )
				{
					$return_string .= $k . "=NULL,";
				}
			}
			
			//-----------------------------------------
			// No? best guess it is then..
			//-----------------------------------------
			
			else
			{
				if ( is_numeric( $v ) and strcmp( intval($v), $v ) === 0 )
				{
					$return_string .= $k . "=" . $v . ",";
				}
				else
				{
					if ( substr( $v, -1 ) == '\\' )
					{
						$v = preg_replace( '#\\\{1}$#', '&#92;', $v );
					}
					
					$return_string .= $k . '=' . $this->fieldEncapsulate . $v . $this->fieldEncapsulate . ',';
				}
			}
		}
		
		$return_string = rtrim( $return_string, "," );

		return $return_string;
	}
	
    /**
	 * Remove quotes from a DB query
	 *
	 * @param	string		Raw text
	 * @return	@e string
	 */
	protected function _removeAllQuotes( $t )
	{
		//-----------------------------------------
		// Remove quotes
		//-----------------------------------------
		
		$t = preg_replace( "#\\\{1,}[\"']#s", "", $t );
		$t = preg_replace( "#'[^']*'#s"    , "", $t );
		$t = preg_replace( "#\"[^\"]*\"#s" , "", $t );
		$t = preg_replace( "#\"\"#s"        , "", $t );
		$t = preg_replace( "#''#s"          , "", $t );

		return $t;
	}
	
    /**
	 * Build order by clause
	 *
	 * @param	string		Order by clause
	 * @return	@e void
	 */
	abstract protected function _buildOrderBy( $order );

    /**
	 * Build having clause
	 *
	 * @param	string		Having clause
	 * @return	@e void
	 */
	abstract protected function _buildHaving( $having_clause );
	
    /**
	 * Build group by clause
	 *
	 * @param	string		Group by clause
	 * @return	@e void
	 */
	abstract protected function _buildGroupBy( $group );

    /**
	 * Build limit clause
	 *
	 * @param	integer		Start offset
	 * @param	integer		[Optional] Number of records
	 * @return	@e void
	 */
	abstract protected function _buildLimit( $offset, $limit=0 );
	
    /**
	 * Build select statement
	 *
	 * @param	string		Columns to retrieve
	 * @param	string		Table name
	 * @param	string		[Optional] Where clause
	 * @param	array 		[Optional] Joined table data
	 * @return	@e void
	 */
	abstract protected function _buildSelect( $get, $table, $where, $add_join=array(), $calcRows=FALSE );
	
	/**
	 * Generates calc rows in the query if supported / runs count(*) if not
	 *
	 * @return	@e void		Sets $this->_calcRows
	 */
	abstract protected function _buildCalcRows();

    /**
	 * Prints SQL error message
	 *
	 * @param	string		Additional error message
	 * @return	@e mixed
	 */
	public function throwFatalError( $the_error='' )
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------

		$this->error	= $this->_getErrorString();
		$this->error_no	= $this->_getErrorNumber();
		$errTemplate    = 'ipsDriverError';
		$nonIPSErrors   = array( '1012-1021', '1023-1027', '1030-1031', '1034-1043', '1129-1131', '1150-1153', 1194, 1195, '1203-1207' );
		
		/* MySQL: Is this non IPS error? */
		if ( $this->usingClass == 'db_driver_mysql' )
		{
			if ( stristr( $this->error, 'has gone away') OR stristr( $this->error, 'connect to local mysql server through socket') )
			{
				$errTemplate = 'ipsServerError';
			}
			else if ( $this->error_no )
			{
				foreach( $nonIPSErrors as $id )
				{
					if ( strstr( $id, '-' ) )
					{
						list( $a, $b ) = explode( '-', $id );
						
						if ( $a AND $b )
						{
							if ( $this->error_no >= $a AND $this->error_no <= $b )
							{
								$errTemplate = 'ipsServerError';
							}
						}
					}
					else if ( $this->error_no == intval( $id ) )
					{
						$errTemplate = 'ipsServerError';
					}
				}
			}
		}
		
		/* Fetch debug information */
		$_debug   = debug_backtrace();
		$_dString = '';

		if ( is_array( $_debug ) and count( $_debug ) )
		{
			foreach( $_debug as $idx => $data )
			{
				/* Remove non-essential items */
				if ( $data['class'] == 'dbMain' OR $data['class'] == 'ips_DBRegistry' OR $data['class'] == 'ipsRegistry' OR $data['class'] == 'ipsController' OR $data['class'] == 'ipsCommand' OR $data['class'] == 'db_driver_mysql' )
				{
					continue;
				}
				
				$_dbString[ $idx ] = array( 'file'     => $data['file'],
											'line'     => $data['line'],
											'function' => $data['function'],
											'class'    => $data['class'] );
			}
		}
		
		$_error_string  = "\n ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------";
		$_error_string .= "\n Date: ". date( 'r' );
		$_error_string .= "\n Error: " . $this->error_no . ' - ' . $this->error;
		$_error_string .= "\n IP Address: " . $_SERVER['REMOTE_ADDR'] . ' - ' . $_SERVER['REQUEST_URI'];
		$_error_string .= "\n ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------";
		$_error_string .= "\n " . $the_error;
		$_error_string .= "\n .--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------.";
		
		if ( count( $_dbString ) )
		{
			$_error_string .= "\n | File                                                                       | Function                                                                      | Line No.          |";
			$_error_string .= "\n |----------------------------------------------------------------------------+-------------------------------------------------------------------------------+-------------------|";
			
			foreach( $_dbString as $i => $data )
			{
				if ( defined('DOC_IPS_ROOT_PATH') )
				{
					$data['file'] = str_replace( DOC_IPS_ROOT_PATH, '', $data['file'] );
				}
				
				/* Reset */
				$data['func'] = "[" . $data['class'] . '].' . $data['function'];
				
				/* Pad right */
				$data['file'] = str_pad( $data['file'], 75 );
				$data['func'] = str_pad( $data['func'], 78 );
				$data['line'] = str_pad( $data['line'], 18 );
				
				$_error_string .= "\n | " . $data['file'] . "| " . $data['func'] . '| ' . $data['line'] . '|';
				$_error_string .= "\n '----------------------------------------------------------------------------+-------------------------------------------------------------------------------+-------------------'";
			}
		}
		
    	if ( $this->return_die == true )
    	{
			$this->error  = ( $this->error == "" ? $the_error : $this->error );
    		$this->failed = true;
    		return;
    	}
     	else if ( $this->obj['use_error_log'] AND $this->obj['error_log'] )
		{
			if ( $FH = @fopen( $this->obj['error_log'], 'a' ) )
			{
				@fwrite( $FH, $_error_string );
				@fclose( $FH );
			}
			 
			/* Write to latest log also */
			if ( is_dir( DOC_IPS_ROOT_PATH . 'cache' ) )
			{
				@file_put_contents( DOC_IPS_ROOT_PATH . 'cache/sql_error_latest.cgi', trim( $_error_string ) );
			}
			
			if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
			{
				@header( "HTTP/1.0 503 Service Unavailable" );
			}
			else
			{
				@header( "HTTP/1.1 503 Service Unavailable" );
			}

			if( $this->use_template )
			{
				require_once( DOC_IPS_ROOT_PATH . 'cache/skin_cache/'. $errTemplate . '.php' );/*noLibHook*/
				$template = new ipsDriverErrorTemplate();
				print $template->showError( $errTemplate == 'ipsServerError' ? true : false, $the_error );
			}
			else
			{
				print "<html><head><title>IPS Driver Error</title>
						<style>P,BODY{ font-family:arial,sans-serif; font-size:11px; }</style></head><body>
			    		   <blockquote><h1>IPS Driver Error</h1><b>There appears to be an error with the database.</b><br>
			    		   You can try to refresh the page by clicking <a href=\"javascript:window.location=window.location;\">here</a>
					  </body></html>";
			}
		}
		else
		{
    		$the_error .= "\n\nSQL error: ".$this->error."\n";
	    	$the_error .= "SQL error code: ".$this->error_no."\n";
	    	$the_error .= "Date: ".date("l dS F Y h:i:s A");
			$the_error .= "\n\n" . $_error_string;
			
			if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
			{
				@header( "HTTP/1.0 503 Service Unavailable" );
			}
			else
			{
				@header( "HTTP/1.1 503 Service Unavailable" );
			}
			    	
			if( $this->use_template )
			{
				require_once( DOC_IPS_ROOT_PATH . 'cache/skin_cache/'. $errTemplate . '.php' );/*noLibHook*/
				$template = new ipsDriverErrorTemplate();
				print $template->showError( true, $the_error );
			}
			else
			{
		    	print "<html><head><title>IPS Driver Error</title>
		    		   <style>P,BODY{ font-family:arial,sans-serif; font-size:11px; }</style></head><body>
		    		   <blockquote><h1>IPS Driver Error</h1><b>There appears to be an error with the database.</b><br>
		    		   You can try to refresh the page by clicking <a href=\"javascript:window.location=window.location;\">here</a>.
		    		   <br><br><b>Error Returned</b><br>
		    		   <form name='mysql'><textarea rows=\"15\" cols=\"60\">".htmlspecialchars($the_error)."</textarea></form><br>We apologise for any inconvenience</blockquote></body></html>";
	    	}
		}
		
		//-----------------------------------------
		// Need to clear this for shutdown queries
		//-----------------------------------------
		
		$this->cur_query	= '';
		
        exit();
    }
    
    /**
	 * Logs SQL error message to log file
	 *
	 * @param	string		SQL Query
	 * @param	string		Data to log (i.e. error message)
	 * @param	integer		Timestamp for log
	 * @return	@e void
	 */
	public function writeDebugLog( $query, $data, $endtime, $fileToWrite='', $backTrace=FALSE )
	{
		$fileToWrite = ( $fileToWrite ) ? $fileToWrite : $this->obj['debug_log'];
		
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		if ( ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] ) OR ( $this->obj['use_bad_log'] AND $this->obj['bad_log'] )  OR ( $this->obj['use_slow_log'] AND $this->obj['slow_log'] ) )
		{
			if ( $query == '{start}' AND ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] ) )
			{
				$_string = "\n\n\n\n\n==============================================================================";
				$_string .= "\n=========================      START       ===================================";
				$_string .= "\n========================= " . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . " ===================================";
				$_string .= "\n==============================================================================";
			}
			else if ( $query == '{end}' AND ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] ) )
			{
				$_string  = "\n==============================================================================";
				$_string .= "\n=========================        END       ===================================";
				$_string .= "\n========================= " . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . " ===================================";
				$_string .= "\n==============================================================================";
			}
			else if ( $query != '{start}' AND $query != '{end}' )
			{
				$_string  = "\n==============================================================================";
				$_string .= "\n URL: ".  $_SERVER['PHP_SELF'] . $_SERVER['REQUEST_URI'];
				$_string .= "\n Date: ". date( 'r' );
				$_string .= "\n IP Address: " . $_SERVER['REMOTE_ADDR'];
				$_string .= "\n Time Taken: ".$endtime;
				$_string .= "\n ".$query;
				$_string .= "\n".$data;
				$_string .= "\n==============================================================================";
			}
		
			if ( $_string AND $FH = @fopen( $fileToWrite, 'a' ) )
			{
				@fwrite( $FH, $_string );
				@fclose( $FH );
			}
		}
	}
	
	/**
	 * Return an object handle for a loaded class
	 *
	 * @param	string 		Class to return
	 * @return	@e object
	 */
	public function fetchLoadedClass( $class )
	{
		return ( is_object( $this->loaded_classes[ $class ] ) ) ? $this->loaded_classes[ $class ] : NULL;
	}
	
    /**
	 * Get SQL error number
	 *
	 * @return	@e mixed
	 */
	abstract protected function _getErrorNumber();
	
    /**
	 * Get SQL error message
	 *
	 * @return	@e string
	 */
	abstract protected function _getErrorString();
		
	/**
	 * db_driver destructor: Runs shutdown queries and closes connection
	 *
	 * @return	@e void
	 */
	public function __destruct()
	{
		$this->return_die = true;
		
		if ( count( $this->obj['shutdown_queries'] ) )
		{
			foreach( $this->obj['shutdown_queries'] as $q )
			{
				$this->query( $q );
			}
		}
		
		$this->writeDebugLog( '{end}', '', '' );

		$this->obj['shutdown_queries'] = array();
		
		$this->disconnect();
	}
}