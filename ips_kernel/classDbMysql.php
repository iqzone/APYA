<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * MySQL Database Driver :: Further loads mysql or mysqli client appropriately
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Monday 28th February 2005 16:46
 * @version		$Revision: 10721 $
 */

/**
 * 1 = Replace into
 * 2 = Insert into...on duplicate key update
 */
define( 'REPLACE_TYPE', 2 );

/**
 * Handle base class definitions
 */
if ( ! class_exists( 'dbMain' ) )
{
	require_once( dirname( __FILE__ ) . '/classDb.php' );/*noLibHook*/
}

abstract class db_main_mysql extends dbMain
{
	/**
	 * Cached field names in table
	 *
	 * @var 		array
	 */
	protected $cached_fields		= array();

	/**
	 * Cached table names in database
	 *
	 * @var 		array
	 */
	protected $cached_tables		= array();
	
	/**
	 * Field name encapsulation character
	 *
	 * @see	http://community.invisionpower.com/tracker/issue-20621-postgresql-driver-field-names-not-properly-escaped/
	 * @var		string
	 */
	public $fieldNameEncapsulate	= '`';

    /**
	 * Return the connection ID
	 *
	 * @return	@e resource
	 */
	public function getConnectionId()
	{
		return $this->connection_id;
	}

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
	public function delete( $table, $where='', $orderBy='', $limit=array(), $shutdown=false )
	{
	    if ( ! $where )
	    {
		    $this->cur_query = "TRUNCATE TABLE " . $this->obj['sql_tbl_prefix'] . $table;
	    }
	    else
	    {
    		$this->cur_query = "DELETE FROM " . $this->obj['sql_tbl_prefix'] . $table . " WHERE " . $where;
		}

		if ( $where AND $orderBy )
		{
			$this->_buildOrderBy( $orderBy );
		}

		if ( $where AND $limit AND is_array( $limit ) )
		{
			$this->_buildLimit( $limit[0], $limit[1] );
		}

		$result	= $this->_determineShutdownAndRun( $this->cur_query, $shutdown );

		$this->cur_query	= '';

		return $result;
	}

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
	public function update( $table, $set, $where='', $shutdown=false, $preformatted=false, $debug=false )
    {
    	//-----------------------------------------
    	// Form query
    	//-----------------------------------------

    	$dba   = $preformatted ? $set : $this->compileUpdateString( $set );

    	$query = "UPDATE " . $this->obj['sql_tbl_prefix'] . $table . " SET " . $dba;

    	if ( $where )
    	{
    		$query .= " WHERE " . $where;
    	}
    	
    	return $this->_determineShutdownAndRun( $query, $shutdown );
    }

    /**
	 * Insert data into a table
	 *
	 * @param	string 		Table name
	 * @param	array 		Array of field => values
	 * @param	boolean		Run on shutdown
	 * @return	@e resource
	 */
	public function insert( $table, $set, $shutdown=false )
	{
    	//-----------------------------------------
    	// Form query
    	//-----------------------------------------

    	$dba   = $this->compileInsertString( $set );

		$query = "INSERT INTO " . $this->obj['sql_tbl_prefix'] . $table . " ({$dba['FIELD_NAMES']}) VALUES({$dba['FIELD_VALUES']})";

		return $this->_determineShutdownAndRun( $query, $shutdown );
    }

    /**
	 * Insert record into table if not present, otherwise update existing record
	 *
	 * @param	string 		Table name
	 * @param	array 		Array of field => values
	 * @param	array 		Array of fields to check
	 * @param	boolean		[Optional] Run on shutdown
	 * @return	@e resource
	 */
	public function replace( $table, $set, $where, $shutdown=false )
	{
    	//-----------------------------------------
    	// Form query
    	//-----------------------------------------

    	$dba	= $this->compileInsertString( $set );

		if( REPLACE_TYPE == 1 OR $this->getSqlVersion() < 41000 )
		{
			$query	= "REPLACE INTO " . $this->obj['sql_tbl_prefix'] . $table . " ({$dba['FIELD_NAMES']}) VALUES({$dba['FIELD_VALUES']})";
		}
		else
		{
			//$dbb	= $this->compileUpdateString( $set );
			$dbb	= array();

			foreach( $set as $k => $v )
			{
				$dbb[]	= "{$k}=VALUES({$k})";
			}

			$dbb	= implode( ',', $dbb );

			$query	= "INSERT INTO " . $this->obj['sql_tbl_prefix'] . $table . " ({$dba['FIELD_NAMES']}) VALUES({$dba['FIELD_VALUES']}) ON DUPLICATE KEY UPDATE " . $dbb;
		}

		if ( class_exists( 'IPSDebug' ) )
		{
			IPSDebug::addLogMessage( $query, 'replaceintolog' );
		}

    	return $this->_determineShutdownAndRun( $query, $shutdown );
    }

    /**
	 * Kill a thread
	 *
	 * @param	integer 	Thread ID
	 * @return	@e resource
	 */
	public function kill( $threadId )
	{
	    return $this->query( "KILL {$threadId}" );
	}

    /**
	 * Subqueries supported by driver?
	 *
	 * @return	@e boolean
	 */
	public function checkSubquerySupport()
	{
		$this->getSqlVersion();

		if ( $this->sql_version >= 41000 )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
	 * Fulltext searching supported by driver?
	 *
	 * @return	@e boolean
	 */
	public function checkFulltextSupport()
	{
		$this->getSqlVersion();

		if ( $this->sql_version >= 32323 AND strtolower($this->connect_vars['mysql_tbl_type']) == 'myisam' )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
	 * Boolean fulltext searching supported by driver?
	 *
	 * @return	@e boolean
	 */
	public function checkBooleanFulltextSupport()
	{
		$this->getSqlVersion();

		if ( $this->sql_version >= 40010 AND strtolower($this->connect_vars['mysql_tbl_type']) == 'myisam' )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
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
	    $current			= $this->return_die;
		$this->return_die 	= true;
		$this->error      	= "";
		$return 		  	= false;
		$indexes			= array();

		$q = $this->query( "SHOW INDEX FROM " . $this->obj['sql_tbl_prefix'] . $table );

		if ( $q AND $this->getTotalRows($q) )
		{
			while( $check = $this->fetch($q) )
			{
				$indexes[ $check['Key_name'] ] = $check['Key_name'];
			}
		}
		
		$this->error		= "";
		$this->return_die	= $current;
		$this->error_no   	= 0;
		$this->failed     	= false;

		return ( in_array( $index, $indexes ) ) ? true : false;
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
	    if( isset($this->cached_fields[ $table ]) )
	    {
		    if( in_array( $field, $this->cached_fields[ $table ] ) )
		    {
			    return true;
		    }
		    else
		    {
			    return false;
		    }
	    }

	    $current			= $this->return_die;
		$this->return_die 	= true;
		$this->error      	= "";
		$return 		  	= false;

		$q = $this->query( "SHOW fields FROM " . $this->obj['sql_tbl_prefix'] . $table );

		if( $q AND $this->getTotalRows($q) )
		{
			while( $check = $this->fetch($q) )
			{
				$this->cached_fields[ $table ][] = $check['Field'];
			}
		}

		if ( !$this->failed AND in_array( $field, $this->cached_fields[ $table ] ) )
		{
			$return = true;
		}

		$this->error		= "";
		$this->return_die	= $current;
		$this->error_no   	= 0;
		$this->failed     	= false;

		return $return;
	}

    /**
	 * Drop database table
	 *
	 * @param	string		Table to drop
	 * @return	@e resource
	 */
	public function dropTable( $table )
	{
		return $this->query( "DROP TABLE IF EXISTS " . $this->obj['sql_tbl_prefix'] . $table );
	}

    /**
	 * Drop field in database table
	 *
	 * @param	string		Table name
	 * @param	string		Field to drop
	 * @return	@e resource
	 */
	public function dropField( $table, $field )
	{
		return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " DROP " . $field );
	}

    /**
	 * Add field to table in database
	 *
	 * @param	string		Table name
	 * @param	string		Field to add
	 * @param	string		Field type
	 * @param	string		[Optional] Default value
	 * @return	@e resource
	 */
	public function addField( $table, $field, $type, $default=NULL )
	{
		$default = $default !== NULL ? "DEFAULT {$default}" : 'NULL';

		return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " ADD {$field} {$type} {$default}" );
	}

	/**
	 * Add index to database column
	 *
	 * @param	string		Table name		table
	 * @param	string		Index name		multicol
	 * @param	string		Fieldlist		col1, col2
	 * @param	bool		Is primary key?
	 * @return	@e resource
	 */
	public function addIndex( $table, $name, $fieldlist, $isPrimary=false )
	{
		$fieldlist = ( $fieldlist ) ? $fieldlist : $name;

		if ( $isPrimary )
		{
			return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " ADD PRIMARY KEY  ( " . $fieldlist . ' )' );
		}
		else
		{
			return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " ADD INDEX " . $name . ' ( ' . $fieldlist . ' )' );
		}
	}

    /**
	 * Change field in database table
	 *
	 * @param	string		Table name
	 * @param	string		Existing field name
	 * @param	string		New field name
	 * @param	string		Field type
	 * @param	string		[Optional] Default value
	 * @return	@e resource
	 */
	public function changeField( $table, $old_field, $new_field, $type='', $default=NULL )
	{
		$default = $default !== NULL ? "DEFAULT {$default}" : 'NULL';

		return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " CHANGE {$old_field} {$new_field} {$type} {$default}" );
	}

    /**
	 * Optimize database table
	 *
	 * @param	string		Table name
	 * @return	@e resource
	 */
	public function optimize( $table )
	{
		return $this->query( "OPTIMIZE TABLE " . $this->obj['sql_tbl_prefix'] . $table );
	}

    /**
	 * Add fulltext index to database column
	 *
	 * @param	string		Table name
	 * @param	string		Field name
	 * @return	@e mixed
	 */
	public function addFulltextIndex( $table, $field )
	{
		if( $this->checkFulltextSupport() )
		{
			return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " ADD FULLTEXT({$field})" );
		}
		else
		{
			return null;
		}
	}

    /**
	 * Get table schematic
	 *
	 * @param	string		Table name
	 * @return	@e array
	 */
	public function getTableSchematic( $table )
	{
		$current			= $this->return_die;
		$this->return_die 	= true;

		$qid = $this->query( "SHOW CREATE TABLE " . $this->obj['sql_tbl_prefix'] . $table );

		$this->return_die 	= $current;

		if( $qid )
		{
			return $this->fetch($qid);
		}
		else
		{
			return array();
		}
	}
	
    /**
	 * Get array of field names in table
	 *
	 * @param	string		Table name
	 * @return	@e array
	 */
	public function getFieldNames( $table )
	{
		//-----------------------------------------
		// Inline field name caching
		//-----------------------------------------
		
		static $_fields		= array();
		
		if( count($_fields[ $table ]) )
		{
			return $_fields[ $table ];
		}

		$current			= $this->return_die;
		$this->return_die 	= true;

		$qid = $this->query( "SHOW FIELDS FROM " . $this->obj['sql_tbl_prefix'] . $table );

		$this->return_die 	= $current;

		if( $qid )
		{
			while( $r = $this->fetch($qid) )
			{
				$_fields[ $table ][]	= $r['Field'];
			}

			return $_fields[ $table ];
		}
		else
		{
			return array();
		}
	}

    /**
	 * Determine if table already has a fulltext index
	 *
	 * @param	string		Table name
	 * @return	@e boolean
	 */
	public function getFulltextStatus( $table )
	{
		$result = $this->getTableSchematic( $table );

		if ( preg_match( "/FULLTEXT KEY/i", $result['Create Table'] ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
	 * Build order by clause
	 *
	 * @param	string		Order by clause
	 * @return	@e void
	 */
	protected function _buildOrderBy( $order )
	{
    	if ( $order )
    	{
    		$this->cur_query .= ' ORDER BY ' . $order;
    	}
	}

    /**
	 * Build group by clause
	 *
	 * @param	string		Having clause
	 * @return	@e void
	 */
	protected function _buildHaving( $having_clause )
	{
    	if ( $having_clause )
    	{
    		$this->cur_query .= ' HAVING ' . $having_clause;
    	}
    }

    /**
	 * Build having clause
	 *
	 * @param	string		Group by clause
	 * @return	@e void
	 */
	protected function _buildGroupBy( $group )
	{
    	if ( $group )
    	{
    		$this->cur_query .= ' GROUP BY ' . $group;
    	}
    }

    /**
	 * Build limit clause
	 *
	 * @param	integer		Start offset
	 * @param	integer		[Optional] Number of records
	 * @return	@e void
	 */
	protected function _buildLimit( $offset, $limit=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$offset = intval( $offset );
		$offset = ( $offset < 0 ) ? 0 : $offset;
		$limit  = intval( $limit );

    	if ( $limit )
    	{
    		$this->cur_query .= ' LIMIT ' . $offset . ',' . $limit;
    	}
    	else
    	{
    		$this->cur_query .= ' LIMIT ' . $offset;
    	}
	}

    /**
	 * Build concat string
	 *
	 * @param	array		Array of data to concat
	 * @return	@e string
	 */
	public function buildConcat( $data )
	{
		$return_string = '';

		if( is_array($data) AND count($data) )
		{
			$concat = array();

			foreach( $data as $databit )
			{
				$concat[] = $databit[1] == 'string' ? "'" . $databit[0] . "'" : $databit[0];
			}

			if( count($concat) )
			{
				$return_string = "CONCAT(" . implode( ',', $concat ) . ")";
			}
		}

		return $return_string;
	}

    /**
	 * Build CAST string
	 *
	 * @param	string		Value to CAST
	 * @param	string		Type to cast to
	 * @return	@e string
	 */
	public function buildCast( $data, $columnType )
	{
		/* mySQL does not support casting as VARCHAR, but it isn't needed either */
		if( $columnType == 'VARCHAR' )
		{
			return $data;
		}
		
		return "CAST( {$data} AS {$columnType} )";
	}

    /**
	 * Build between statement
	 *
	 * @param	string		Column
	 * @param	integer		Value 1
	 * @param	integer		Value 2
	 * @return	@e string
	 */
	public function buildBetween( $column, $value1, $value2 )
	{
		return "{$column} BETWEEN {$value1} AND {$value2}";
	}

    /**
	 * Build regexp string (ONLY supports a regexp equivalent of "or field like value")
	 *
	 * @param	string		Database column
	 * @param	array		Array of values to allow
	 * @return	@e string
	 */
	public function buildRegexp( $column, $data )
	{
		return "{$column} REGEXP '," . implode( ',|,', $data ) . ',|\\\*\'';
	}

	/**
	 * Build LIKE CHAIN string (ONLY supports a regexp equivalent of "or field like value")
	 *
	 * @param	string		Database column
	 * @param	array		Array of values to allow
	 * @param	boolean		Treat numerically
	 * @return	@e string
	 */
	public function buildLikeChain( $column, $data, $isNumerical=true )
	{
		$return = $column . "='*'";
		$comma  = ',';
		
		if ( ! is_array( $data ) )
		{
			return '1=1';
		}
		
		if ( ! $isNumerical )
		{
			$comma  = '';
			$first  = array_shift( $data );
			$return = $column . ' LIKE \'%' . $first . '%\'';
		}
		
		foreach( $data as $id )
		{
			$return .= " OR " . $column . " LIKE '%" . $comma . $id . $comma . "%'";
		}

		return $return;
	}
	
	/**
	 * Returns a portion of a WHERE query suitable for use.
	 * Specific to tables with a field that has a comma separated list of IDs.
	 *
	 * @param	array		Array of perm IDs
	 * @param	string		DB field to search in
	 * @param	boolean		check for '*' in the field to denote 'global'
	 * @return	@e mixed
	 */
	public function buildWherePermission( array $ids, $field='', $incStarCheck=true )
	{
		/* Just use a LIKE chain for items without a specific implementation */
		$where = '( ';
		$_or   = array();
		
		foreach( $ids as $i )
		{
			if ( ! $i )
			{
				continue;
			}
			
			if ( is_numeric( $i ) )
			{
				$_or[] = "FIND_IN_SET(" . $i . "," . $field . ")";
			}
		}
		
		if ( count( $_or ) )
		{
			$where .= '( ' . implode( " OR ", $_or ) . ' )';
		}
		else
		{
			$where .= "1 = 1";
		}
		
		if ( $incStarCheck === true )
		{
			$where .= ' OR ( ' . $field . '=\'*\' )';
		}
		
		return $where . ' )';
	}
	
	/**
	 * Set Timezone
	 *
	 * @param	float		UTC Offset (e.g. 6 or -4.5)
	 * @return	@e void
	 */
	public function setTimeZone( $offset )
	{
		$offset = number_format( floatval( $offset ), 2 );
		$decimal = substr( $offset, strpos( $offset, '.' ) + 1 );
		$offset = ( $offset >= 0 ? '+' . floor( $offset ) : ceil( $offset ) ) . ':' . str_pad( $decimal = ( 60 / 100 ) * $decimal, 2, '0' );

		$this->query( "SET time_zone = '{$offset}';" );
	}
	
    /**
	 * Build instr string
	 *
	 * @param	string		String to look in
	 * @param	string		String to look for
	 * @return	@e string
	 */
	public function buildInstring( $look_in, $look_for )
	{
		if( $look_for AND $look_in )
		{
			return "INSTR('" . $look_in . "', " . $look_for . ")";
		}
		else
		{
			return '';
		}
	}

    /**
	 * Build substr string
	 *
	 * @param	string		String of characters/Column
	 * @param	integer		Offset
	 * @param	integer		[Optional] Number of chars
	 * @return	@e string
	 */
	public function buildSubstring( $look_for, $offset, $length=0 )
	{
		$return = '';

		if( $look_for AND $offset )
		{
			$return = "SUBSTR(" . $look_for . ", " . $offset;

			if( $length )
			{
				$return .= ", " . $length;
			}

			$return .= ")";
		}

		return $return;
	}

    /**
	 * Build distinct string
	 *
	 * @param	string		Column name
	 * @return	@e string
	 */
	public function buildDistinct( $column )
	{
		return "DISTINCT(" . $column . ")";
	}

    /**
	 * Build length string
	 *
	 * @param	string		Column name
	 * @return	@e string
	 */
	public function buildLength( $column )
	{
		return "LENGTH(" . $column . ")";
	}

	/**
	 * Build lower string
	 *
	 * @param	string		Column name
	 * @return	@e string
	 */
	public function buildLower( $column )
	{
		return "LOWER(" . $column . ")";
	}

    /**
	 * Build right string
	 *
	 * @param	string		Column name
	 * @param	integer		Number of chars
	 * @return	@e string
	 */
	public function buildRight( $column, $chars )
	{
		return "RIGHT(" . $column . "," . intval($chars) . ")";
	}

    /**
	 * Build left string
	 *
	 * @param	string		Column name
	 * @param	integer		Number of chars
	 * @return	@e string
	 */
	public function buildLeft( $column, $chars )
	{
		return "LEFT(" . $column . "," . intval($chars) . ")";
	}

    /**
	 * Build "is null" and "is not null" string
	 *
	 * @param	boolean		is null flag
	 * @return	@e string
	 */
	public function buildIsNull( $is_null=true )
	{
		return $is_null ? " IS NULL " : " IS NOT NULL ";
	}
	
    /**
	 * Build coalesce statement
	 *
	 * @param	array		Values to check
	 * @return	@e string
	 */
	public function buildCoalesce( $values=array() )
	{
		if( !count($values) )
		{
			return '';
		}

		return "COALESCE(" . implode( ',', $values ) . ")";
	}

    /**
	 * Build from_unixtime string
	 *
	 * @param	string		Column name
	 * @param	string		[Optional] Format
	 * @return	@e string
	 */
	public function buildFromUnixtime( $column, $format='' )
	{
		if( $format )
		{
			return "FROM_UNIXTIME(" . $column . ", '{$format}')";
		}
		else
		{
			return "FROM_UNIXTIME(" . $column . ")";
		}
	}

	/**
	 * Build unix_timestamp string
	 *
	 * @param	string		Column name
	 * @return	@e string
	 */
	public function buildUnixTimestamp( $column )
	{
		return "UNIX_TIMESTAMP(" . $column . ")";
	}

    /**
	 * Build date_format string
	 *
	 * @param	string		Date string
	 * @param	string		Format
	 * @return	@e string
	 */
	public function buildDateFormat( $column, $format )
	{
		return "DATE_FORMAT(" . $column . ", '{$format}')";
	}
	
	/**
	 * Build rand() string
	 *
	 * @return	@e string
	 */
	public function buildRandomOrder()
	{
		return "RAND()";
	}

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
	public function buildSearchStatement( $column, $keyword, $booleanMode=true, $returnRanking=false, $useFulltext=true )
	{
		if( !$useFulltext OR strtolower($this->connect_vars['mysql_tbl_type']) != 'myisam' )
		{
			return "{$column} LIKE '%{$keyword}%'";
		}
		else
		{
			return "MATCH( {$column} ) AGAINST( '{$keyword}' " . ( $booleanMode === TRUE ? 'IN BOOLEAN MODE' : '' ) . " )" . ( $returnRanking === TRUE ? ' as ranking' : '' );
		}
	}
	
	/**
	 * Builds a call to MD5 equivalent
	 *
	 * @param	string		Column name or value to MD5-hash
	 * @return	@e string
	 */
	public function buildMd5Statement( $statement )
	{
		return "MD5(" . $statement . ")";
	}
	
	/**
	 * Build calc rows 
	 * We don't have to do anything for MySQL 4+ as it's handled internally
	 * This is always called before the limit is applied
	 *
	 * @return	@e void
	 */
	protected function _buildCalcRows()
	{
		return "";

		/* For other engines */
		/*if ( $this->cur_query )
		{
			$_query = preg_replace( "#SELECT\s{1,}(.+?)\s{1,}FROM\s{1,}#i", "SELECT count(*) as count FROM ", $this->cur_query );

			$this->query( $_query );
			$count = $this->fetch();

			$this->_calcRows = intval( $count['count'] );
		}*/
	}

    /**
	 * Build select statement
	 *
	 * @param	string		Columns to retrieve
	 * @param	string		Table name
	 * @param	string		[Optional] Where clause
	 * @param	array 		[Optional] Joined table data
	 * @param	boolean		Calculate total rows too
	 * @return	@e void
	 */
	protected function _buildSelect( $get, $table, $where, $add_join=array(), $calcRows=FALSE )
	{
		$_calcRows = ( $calcRows === TRUE ) ? 'SQL_CALC_FOUND_ROWS ' : '';

		if( !count($add_join) )
		{
			if( is_array( $table ) )
			{
				$_tables	= array();

				foreach( $table as $tbl => $alias )
				{
					$_tables[] = $this->obj['sql_tbl_prefix'] . $tbl . ' ' . $alias;
				}

				$table	= implode( ', ', $_tables );
			}
			else
			{
				$table	= $this->obj['sql_tbl_prefix'] . $table;
			}

	    	$this->cur_query .= "SELECT {$_calcRows}{$get} FROM " . $table;

	    	if ( $where != "" )
	    	{
	    		$this->cur_query .= " WHERE " . $where;
	    	}

	    	return;
    	}
    	else
		{
	    	//-----------------------------------------
	    	// OK, here we go...
	    	//-----------------------------------------

	    	$select_array   = array();
	    	$from_array     = array();
	    	$joinleft_array = array();
	    	$where_array    = array();
	    	$final_from     = array();
	    	$select_array[] = $get;
	    	$from_array[]   = $table;
	    	$hasLeft		= false;
	    	$hasInner		= false;

	    	if ( $where )
	    	{
	    		$where_array[]  = $where;
	    	}

	    	//-----------------------------------------
	    	// Loop through JOINs and sort info
	    	//-----------------------------------------

	    	if ( is_array( $add_join ) and count( $add_join ) )
	    	{
	    		foreach( $add_join as $join )
	    		{
	    			if ( ! is_array( $join ) )
	    			{
	    				continue;
	    			}
	    			
	    			# Push join's select to stack
	    			if ( !empty($join['select']) )
	    			{
	    				$select_array[] = $join['select'];
	    			}

	    			if ( empty($join['type']) OR $join['type'] == 'left' )
	    			{
	    				$hasLeft = true;
	    				# Join is left or not specified (assume left)
	    				$tmp = " LEFT JOIN ";

	    				foreach( $join['from'] as $tbl => $alias )
						{
							$tmp .= $this->obj['sql_tbl_prefix'].$tbl.' '.$alias;
						}

	    				if ( $join['where'] )
	    				{
	    					$tmp .= " ON ( ".$join['where']." ) ";
	    				}

	    				$joinleft_array[] = $tmp;

	    				unset( $tmp );
	    			}
	    			else if ( $join['type'] == 'inner' )
	    			{
	    				$hasInner = true;
	    				
	    				# Join is inline
	    				$from_array[]  = $join['from'];

	    				if ( $join['where'] )
	    				{
	    					$where_array[] = $join['where'];
	    				}
	    			}
	    			else
	    			{
	    				# Not using any other type of join
	    			}
	    		}
	    	}

	    	//-----------------------------------------
	    	// Build it..
	    	//-----------------------------------------

	    	foreach( $from_array as $i )
			{
				foreach( $i as $tbl => $alias )
				{
					$final_from[] = $this->obj['sql_tbl_prefix'] . $tbl . ' ' . $alias;
				}
			}

	    	$get     = implode( ","     , $select_array   );
	    	
	    	#http://bugs.mysql.com/bug.php?id=37925
	    	$table   = ( $hasLeft === true && $hasInner === true ) ? '(' . implode( ",", $final_from ) . ')' : implode( ",", $final_from );
	    	$where   = implode( " AND " , $where_array    );
	    	$join    = implode( "\n"    , $joinleft_array );

	    	$this->cur_query .= "SELECT {$_calcRows}{$get} FROM {$table}";

	    	if ( $join )
	    	{
	    		$this->cur_query .= " " . $join . " ";
	    	}

	    	if ( $where != "" )
	    	{
	    		$this->cur_query .= " WHERE " . $where;
	    	}
		}
	}

}

if ( extension_loaded('mysqli') AND ! defined( 'FORCE_MYSQL_ONLY' ) )
{
	require( dirname( __FILE__ ) . "/classDbMysqliClient.php" );/*noLibHook*/
}
else
{
	require( dirname( __FILE__ ) . "/classDbMysqlClient.php" );/*noLibHook*/
}
