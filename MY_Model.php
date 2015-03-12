<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CI Model Wrapper
 *
 * An open source library for CodeIgniter to replace CI_Model
 *
 * @package		Application
 * @author		Hassan ur Rehman Abbasi
 * @author		DoozieLabs
 * @link		http://www.doozielabs.com
 *
 */

// ------------------------------------------------------------------------

/**
 * CI Model Wraper - MY_Model
 *
 * Requires PHP 5.5 or newer
 *
 * @package		Application
 * @subpackage	Core
 * @category	Libraries
 * @author		Hassan ur Rehman Abbasi
 * @author		DoozieLabs
 */
class MY_Model extends CI_Model {

	protected $columns		= array();
	private $query_settings	= array();
	private $ref			= array();

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct()
 	{
		log_message('debug', "[Model." . $this->_get_table_name() . "] - Initialized");
		$this->reset_query();
		$this->_validate_column_binding();
 	}

 	/**
 	 * Validates column binding for models
 	 *
 	 * @access	private
 	 * @param	`void`
 	 * @return	`boolean`
 	 *
 	 */
 	private function _validate_column_binding() {
 		if ( is_array($this->columns) && count($this->columns) ) {
 			foreach ($this->columns as $column => $value) {
 				if ( is_array($value) || is_object($value) ) {
 					trigger_error(get_class($this) . '::columns - Malformed column binding. Only string, numeric, and NULL are allowed', E_USER_ERROR);
 					return false;
 				}
 			}
 			return true;
 		}

 		if (get_class($this) != "MY_Model")
 			trigger_error(get_class($this) . '::columns - Columns not binded', E_USER_ERROR);

 		return false;
 	}

 	/**
	 * Retrieves Table Name of current Model
 	 *
 	 * To make this `function` work you need to add a `const table` in your derieved model `class`. If
 	 * `const table` is not set, it will consider model `class` name as the table name
 	 *
 	 * @access	protected
 	 * @param	`void`
 	 * @return	`string`	-	value of `const table` if it is set, otherwise `class` name
 	 */
	protected function _get_table_name () {
		$class = new ReflectionClass($this);
		$table = $class->getConstant("table");
		
		return $this->_is_valid_identifier($table) ? $table : $class->name;
	}

	/**
	 * Validates provided string as a database field / table name
	 *
	 * @access	private
	 * @param	`string`
	 * @return	`boolean`
	 *
	 */
	private function _is_valid_identifier ( $name ) {
		return (is_string($name) && preg_match("=^[a-zA-Z0-9_]+$=", $name)) ? true : false;
	}

	/**
	 * Retrieve and parse Primary Key constant
	 * 
	 * To make this `function` work you need to add a `const pk` in you derieved model `class`
	 *
 	 * @access	protected
	 * @param	`void`
	 * @return	`array`	-	Array of Primary Keys in case you have set `const pk`, otherwise returns `null`
	 *
	 */
	protected function _get_pk () {
		$class = new ReflectionClass($this);
		$pk = $class->getConstant("pk");	

		if (is_string($pk) && strlen($pk)) {
			if (preg_match("=^([a-zA-Z0-9_]+)(,[a-zA-Z0-9_]+)*$=", $pk)) {
				$pk = explode(",", $pk);
				foreach ($pk as $key => $value) {
					$pk[$key] = trim($value);
				}
				return $pk;
			}
			trigger_error(get_class($this) . '::pk Malformed primary keys', E_USER_NOTICE);
		}
		return null;
	}

	/**
	 * Retrieve and parse Auto Increment Key constant
	 * 
	 * To make this `function` work you need to add a `const ai` in you derieved model `class`
	 *
 	 * @access	protected
	 * @param	`void`
	 * @return	`string`	-	Auto Increment column name in case you have set `const ai`, otherwise returns `null`
	 *
	 */
	protected function _get_ai () {
		$class = new ReflectionClass($this);
		$ai = $class->getConstant("ai");
		if ( $ai && $this->_is_valid_identifier($ai) )
			return $ai;
		else if ( $ai )
			trigger_error(get_class($this) . '::ai Malformed auto increment key', E_USER_NOTICE);

		return null;
	}

	/**
	 * Retrieve and parse Reference constant
	 * 
	 * To make this `function` work you need to add a `const ref` in you derieved model `class`
	 *
 	 * @access	protected
	 * @param	`void`
	 * @return	`array`	Array of relations in case you have set `const ref`, otherwise returns `null`
	 *
	 */
	protected function _get_refs ($ref = null) {
		if ( !$ref ) {
			$class	= new ReflectionClass($this);
			$ref	= $class->getConstant("ref");
		}
		if (is_string($ref) && strlen($ref)) {
			if (preg_match("=^([a-zA-Z0-9_]+:[a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)(,[a-zA-Z0-9_]+:[a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)*$=", $ref)) {
				$ref = explode(",", $ref);
				foreach ($ref as $key => $value) {
					$act_ref = array();
					$raw_ref = trim($value);
					$raw_ref = explode(":", $value);

					if (!count($raw_ref) == 2) continue;

					$act_ref['column'] = trim($raw_ref[0]);
					$raw_ref = explode(".", $raw_ref[1]);

					if (!count($raw_ref) == 2) continue;

					$act_ref['ref'] = array( "table" => trim($raw_ref[0]), "column" => trim($raw_ref[1]) );

					$ref[$key] = $act_ref;
				}
				return $ref;
			}
			trigger_error(get_class($this) . '::ref - Malformed references', E_USER_NOTICE);
		}
		return null;
	}
	
	/**
	 * Start database transaction
	 *
	 * This `function` is used to start database transaction. It is not necessary to call `start_transaction` of same Model
	 * on which you are performing database actions
	 * E.g: model_a->start_transaction(); $model_b->save();
	 *
 	 * @access	public
	 * @param	`void`	
	 * @return	`boolean`	`TRUE` in case transaction started, otherwise `FALSE`
	 *
	 */
	public function start_transaction() {
		$table = $this->_get_table_name();
		log_message("info", "[Model.$table] - (start transaction)");

		return $this->db->trans_begin() ? true : false;
	}
	
	/**
	 * Commit database transaction
	 *
	 * This function is used to commit database transaction. It is not necessary to call commit_transaction of same Model
	 * on which you performed database actions.
	 * E.g: model_a->start_transaction(); $model_a->save(); $model_b->commit_transaction();
	 *
 	 * @access	public
	 * @param	`void`	
	 * @return	`boolean`	-	`TRUE` in case transaction commited, otherwise `FALSE`
	 *
	 */
	public function commit_transaction() {
		$table = $this->_get_table_name();
		log_message("info", "[Model.$table] (commit transaction)");

		return $this->db->trans_commit() ? true : false;
	}
	
	/**
	 * Rollback database transaction
	 *
	 * This `function` is used to rollback database transaction. It is not necessary to call `rollback_transaction` of same Model
	 * on which you performed database actions.
	 * E.g:
	 * model_a->start_transaction(); $model_a->save(); $model_b->rollback_transaction();
	 *
 	 * @access	public
	 * @param	`void`	
	 * @return	`boolean`	-	`TRUE` in case transaction rolled back, otherwise `FALSE`
	 *
	 */
	public function rollback_transaction() {
		$table = $this->_get_table_name();
		log_message("info", "[Model.$table] (rollback transaction)");

		return $this->db->trans_rollback() ? true : false;
	}

	/**
	 * Resets search query parameters
	 *
	 * @access	public
	 * @param	`void`
	 * @return	`object`	-	Returns self
	 *
	 */
	public function reset_query ( ) {
		log_message('debug', "[Model." . $this->_get_table_name() . "] (reset_query)");
		$this->query_settings = array (
			'columns'	=> array( '*' ),
			'table'		=> $this->_get_table_name(),
			'where'		=> null,
			'order'		=> null,
			'group'		=> null,
			'having'	=> null,
			'limit'		=> null
		);

		return $this;
	}

	/**
	 * Set columns for search query
	 *
	 * `select` function is used to specify the columns to be selected from database. This `function`
	 * itself don't execute the query. To execute see `find` function 
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`object`	-	Returns self
	 *
	 */
	public function select ( ) {
		if ( !func_num_args() )
			throw new Exception("MY_Model::select requires column names as string arguments. At least 1 column is required", 1);

		$this->query_settings['columns'] = array();
		foreach (func_get_args() as $column) {
			if ( is_string( $column ) ) {
				$this->query_settings['columns'][] = $column;
			} else {
				throw new Exception("MY_Model::select - accepts column names as string arguments. " . gettype($column) . " provided instead", 1);
			}
		}

		return $this;
	}

	/**
	 * Re-set columns for search query
	 *
	 * `reset_select` function is used to reset columns of search query to `*`
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`object`	-	Returns self
	 *
	 */
	public function reset_select ( ) {
		$this->query_settings['columns'] = array("*");

		return $this;
	}

	/**
	 * Set Table(s) for search query
	 *
	 * `select` function is used to specify the columns to be selected from database. This `function`
	 * itself don't execute the query. To execute see `find` function 
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`object`	-	Returns self
	 *
	 */
	public function from ( $table ) {
		if ( is_string( $table ) ) {
			$this->query_settings['table'] = $table;

		} else {
			throw new Exception("MY_Model::from - accepts table name(s) as a string argument", 1);
		}

		return $this;
	}

	/**
	 * Re-set Table(s) for search query
	 *
	 * `reset_select` function is used to reset the table value for search query. It revertes table name to the value set in `const table`
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`object`	-	Returns self
	 *
	 */
	public function reset_from ( ) {
		$this->query_settings['table'] = $this->_get_table_name();

		return $this;
	}

	/**
	 * Set where filter for search query
	 *
	 * `where` function is used to specify the condition according to which data is filtered 
	 *
 	 * @access	public
	 * @param	`string`	-	Condition query
	 * @param	`mixed`		-	(optional) values for condition, can provide multiple values as different arguments
	 * @return	`object`	-	Returns self
	 *
	 */
	public function where ( $query ) {
		$CI =& get_instance();
		$CI->load->helper( 'array' );

		$values = func_get_args();
		unset( $values[0] );
		$values = $this->_flatten_array( $values );

		if ( ( count( explode( "?", $query ) ) - 1 ) == count( $values ) ) {

			foreach($values as $value) {
				if ( is_array( $value ) || is_object( $value ) ) {
					throw new Exception("MY_Model::where - accepts values in string | numeric | NULL. " . gettype( $value ) . " provided instead.", 1);
					
				} else if (is_null($value)){
					$value = "NULL";

				} else {
					$value = $this->db->escape( $value );

				}
				$query = substr_replace($query, $value, strpos($query, "?"), 1);
			}
			$this->query_settings['where'] .= $query;
		} else {
			throw new Exception("MY_Model::where - number of values does not match number of '?' in query", 1);
			
		}

		return $this;
	}

	/**
	 * Prepares `where in` filter for search query
	 *
 	 * @access	public
	 * @param	`string`	-	Column name
	 * @param	`array`		- 	Array of possible values
	 * @return	`string`	-	Returns prepared string for `where in` condition. Pass this in `where` function to make it work.
	 *							E.g: $model->where( $model->make_wherein( 'column_name', $values ), $values )->find(); 
	 *
	 */
	public function make_wherein ($column, $values) {
		$CI =& get_instance();
		$CI->load->helper( 'array' );

		$template = array_pad(array(), count( $this->_flatten_array($values) ), "?");
		return "$column in (" . implode(", ", $template) . ")"; 
	}

	/**
	 * Re-sets where condition for search query
	 *
 	 * @access	public
	 * @param	`void`
	 * @return 	`object`	-	Returns self
	 *
	 */
	public function reset_where ( ) {
		$this->query_settings['where'] = null;

		return $this;
	}

	/**
	 * Sets `group by` for search query 
	 *
 	 * @access	public
	 * @param	`string`	-	Column name with sorting order, can provide unlimited columns as different arguments
	 * @return	`object`	-	Returns self
	 *
	 */
	public function group ( ) {
		if ( !func_num_args() )
			throw new Exception("MY_Model::group requires column names as string arguments. At least 1 column is required", 1);

		$this->query_settings['group'] = array();
		foreach (func_get_args() as $column) {
			if ( is_string( $column ) ) {
				$this->query_settings['group'][] = $column;
			} else {
				throw new Exception("MY_Model::group - accepts column names as string arguments. " . gettype($column) . " provided instead", 1);
			}
		}

		return $this;
	}

	/**
	 * Re-sets `group by` for search query
	 *
 	 * @access	public
	 * @param	`void`
	 * @return 	`object`	-	Returns self
	 *
	 */
	public function reset_group ( ) {
		$this->query_settings['group'] = null;

		return $this;
	}

	/**
	 * Set having filter for search query
	 *
	 * `having` function is used to specify the condition according to which grouped data is filtered 
	 *
 	 * @access	public
	 * @param	`string`	-	Condition query
	 * @param	`mixed`		-	(optional) values for condition, can provide multiple values as different arguments
	 * @return	`object`	-	Returns self
	 *
	 */
	public function having ( $query ) {
		$CI =& get_instance();
		$CI->load->helper( 'array' );

		$values = func_get_args();
		unset( $values[0] );
		$values = $this->_flatten_array( $values );

		if ( ( count( explode( "?", $query ) ) - 1 ) == count( $values ) ) {

			foreach($values as $value) {
				if ( is_array( $value ) || is_object( $value ) ) {
					throw new Exception("MY_Model::having - accepts values in string | numeric | NULL. " . gettype( $value ) . " provided instead.", 1);
					
				} else if (is_null($value)){
					$value = "NULL";

				} else {
					$value = $this->db->escape( $value );

				}
				$query = substr_replace($query, $value, strpos($query, "?"), 1);
			}
			$this->query_settings['having'] .= $query;
		} else {
			throw new Exception("MY_Model::having - number of values does not match number of '?' in query", 1);
			
		}

		return $this;
	}
	
	/**
	 * Re-sets having condition for search query
	 *
 	 * @access	public
	 * @param	`void`
	 * @return 	`object`	-	Returns self
	 *
	 */
	public function reset_having ( ) {
		$this->query_settings['having'] = null;

		return $this;
	}

	/**
	 * Set data sorting order for search query
	 *
 	 * @access	public
	 * @param	`string`	-	column names with sorting order. can provide multiple columns as different arguments
	 * @return	`object`	-	Returns self
	 *
	 */
	public function order ( ) {
		$arguments = func_get_args();
		if ( !func_num_args() ) {
			throw new Exception("MY_Model::order - accepts {string} arguments. At least 1 argument is required", 1);
		}

		$this->query_settings['order'] = array();
		foreach ($arguments as $column) {
			if ( is_string( $column ) ) {
				$this->query_settings['order'][] = $column;
			} else {
				$this->reset_order();
				throw new Exception("MY_Model::order - accepts column names as string arguments. {" . gettype($column) . "} provided instead", 1);
			}
		}

		return $this;
	}

	/**
	 * Re-set data sorting for search query
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`object`	- Returns self
	 *
	 */
	public function reset_order ( ) {
		$this->query_settings['order'] = null;

		return $this;
	}

	/**
	 * Set row limits on search query
	 *
 	 * @access	public
	 * @param	`int`		-	In case of 2 arguments, starting position, otherwise number of rows
	 * @param	`int`		-	(optional) Number of rows
	 * @return	`object`	-	Returns self
	 *
	 */
	public function limit ( $skip, $numrows = null ) {
		if ( func_num_args() > 2 ) {
			throw new Exception("MY_Model::limit - accepts at max 2 {int} arguments. Argument 1 is required", 1);
		} elseif ( !is_numeric($skip) ) {
			throw new Exception("MY_Model::limit - accepts Argument 1 as {int}. {" . gettype($skip) . "} provided instead", 1);
		} elseif ( !is_numeric($numrows) && !is_null($numrows) ) {
			throw new Exception("MY_Model::limit - accepts optional Argument 2 as {int}. {" . gettype($skip) . "} provided instead", 1);
		}
			
		$this->query_settings['limit'] = implode(", ", func_get_args());

		return $this;
	}

	/**
	 * Re-set row limits on search query
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`object`	- Returns self
	 *
	 */
	public function reset_limit ( ) {
		$this->query_settings['limit'] = null;

		return $this;
	}

	/**
	 * Find Results / Execute search query
	 *
 	 * @access	public
	 * @param	`boolean`	-	Load references of result rows, see `function` `load_refs` for detail
	 * @param	`boolean`	-	Specify the resulting object type, if it is set to `true`, resulting rows will be the object 
	 *							of same `class` as your model. In case of `false`, `function` find check if you have sepecified any
	 *							other table to select data from, in that case, resulting rows will be unknown object. Otherwise
	 *							result will be same as in case of `true`
	 * @return	`array`		-	In case of successful execution, Array of resulting rows, otherwise empty array
	 *
	 */
	public function find ( $load_refs = false, $same_obj = false ) {
		$table = $this->_get_table_name();
		log_message("debug", "[Model.$table] (find) - Process started");

		$query = "SELECT ";

		// COLUMNS
		if ( $this->query_settings['columns'] ) {
			$query.= implode(", ", $this->query_settings['columns']);

		} else {
			throw new Exception("MY_Model::find - columns not set", 1);
			
		}

		// FROM
		$this->query_settings['table'] = $this->query_settings['table'] ? $this->query_settings['table'] : $table;
		$query .= " FROM " . $this->query_settings['table'];

		// WHERE
		if ( $this->query_settings['where'] )
			$query .= " WHERE " . $this->query_settings['where'];

		// GROUP BY
		if( $this->query_settings['group'] ) {
			$query .= " GROUP BY " . implode(", ", $this->query_settings['group']);
		}

		// HAVING
		if( $this->query_settings['having'] ) {
			$query .= " HAVING " . $this->query_settings['having'];
		}		

		// ORDER BY
		if( $this->query_settings['order'] ) {
			$query .= " ORDER BY " . implode(", ", $this->query_settings['order']);
		}
		
		// LIMIT
		if( $this->query_settings['limit'] ) {
			$query .= " LIMIT " . $this->query_settings['limit'];
		}

		$query .= ";";

		log_message('debug', "[Model.$table] (find) - Query: $query");

		$results = array();
		if ( $db_responce = $this->db->query($query) ) {
			$results = $db_responce->result("array");
			if ( is_array( $results ) ) {
				if ( $load_refs )
					log_message('debug', "[Model.$table] (find) - Loading References");

				foreach ($results as $rkey => $result) {
					if ($table == $this->query_settings['table'] || $same_obj) {
						$results[$rkey] = $this->_cast($result);
					}

					if ( $load_refs && is_subclass_of($results[$rkey], "MY_Model") )
						$results[$rkey]->load_refs();
				}
			}
			if ( !$results ) $results = array();

		} else {
			log_message('debug', "[Model.$table] (find) - Error: " . $this->get_error_message());
		}

		$this->reset_query();
		return $results;
	}

	/**
	 * Loads References
	 *
	 * This `function` works only if you have specified `const ref` in your derived model. See ReadMe.md for details
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`boolean`	-	`true` in case of References are loaded. Otherwise `false`
	 *
	 */
	public function load_refs ( $refs = null ) {
		
		if ( $refs = $this->_get_refs( $refs ) ) {
			$this->ref = array();
			foreach ($refs as $ref) {
				$ref_col		= $ref['column'];
				$ref_table		= $ref['ref']['table'];
				$ref_table_col	= $ref['ref']['column'];
				
				if ( !isset( $this->columns[$ref_col] ) ) continue;

				$CI =& get_instance();
				$CI->load->model( $ref_table, $ref_table, true );
				$this->ref["{$ref_col}:{$ref_table}"] = $CI->$ref_table->where("{$ref_table_col} = ?",  $this->columns[$ref_col])->find();

			}
			return true;
		}
		return false;
	}
	
	/**
	 * Finds a single result
	 *
	 * Same as `find`, but it just returns single row
	 *
 	 * @access	public
	 * @param	`boolean`		- See `find` for datail
	 * @param	`boolean`		- See `find` for derial
	 * @return	`mixed`			- In case of successful search, returns `object`, otherwise `false`
	 *
	 */
	public function find_one ($load_refs = false, $same_obj = false) {
		$result = $this->limit(1)->find($load_refs, $same_obj);
		if($result && is_array($result) && count($result)) {
			return $result[0];
		}
		
		return false;
	}

	/**
	 * Copy model object
	 *
	 * Will copy only if provided object is a same model.
	 *
 	 * @access	private
	 * @param	`object`	-	model object to copy
	 * @return	`boolean`	-	`true` in case of successful copy, othewise `false`
	 *
	 */
	private function copy ($object) {
		$copied = false;
		if ($object instanceof self) {
			$object_array = (array) $object;

			foreach ($object_array as $property => $value) {
				$property_data = explode(chr(0), $property);
				if ( count($property_data) == 3) {
					if ( $property_data[1] == "MY_Model") {
						$this->$property_data[2] = $value;

					} else {
						$class = new ReflectionClass($this);
						if ($class->hasProperty($property_data[2])) {
							$class_property = $class->getProperty($property_data[2]);
							$class_property->setAccessible(true);
							$class_property->setValue($this, $value);
							$copied = true;
						}
					}
				} else if ( count($property_data) == 1) {
					$this->$property = $value;
				}
			}
		}

		return $copied;
	}

	/**
	 * Loads search result in it self, instead of returning it
	 *
	 * Same as `find_one`, it jsut don't return result, but loads in requesting model
	 *
 	 * @access	public
	 * @param	`boolean`	-	See `find` for detail
	 * @param	`boolean`	-	See `find` for detail
	 * @return	`boolean`	-	`true` in case of successful loading, otherwise `false`
	 *
	 */
	public function load ( $load_refs = false, $same_obj = false ) {
		$loaded = false;
		$object = $this->find_one( $load_refs, $same_obj );
		if ($object) {
			$loaded = $this->copy($object);
		}

		return $loaded;
	}
	
	/**
	 * Count resulting rows
	 *
	 * Same as find, it just don't return result, but returns count of resulting rows
	 *
 	 * @access	public
	 * @param	`void`
	 * @param	`int`	Count of resulting rows
	 *
	 */
	public function count () {
		$result = $this->find();
		if($result && is_array($result) && count($result)) {
			return count($result);
		}
	
		return 0;
	}
	
	/**
	 * Save Changes in Model
	 *
	 * This `function` is used for both, insert and update. Will automatically detect either it has tp
	 * be inserted or is a new entry. To make this function work properly. You need to add `const pk`
	 * in your model
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`mixed`	-	If table has `auto_increment` pk, it will return value of auto_increment key
	 *						on insert. Otherwise `true` in case of successful execution, else `false`
	 *
	 */
	public function save ( ) {
		$vars = $this->columns;
		$table = $this->_get_table_name();
		$saved = false;

		$data = array();
		foreach( $vars as $key => $value ) {
			if ( is_array($value) || is_object( $value ) ) {
				continue;
				
			} else {
				$data[$key] = $value;

			}
		}
		
		log_message("info", "[Model.$table] (save)");
		try {
			$pk_vals	= array();
			$row_exists	= false;

			if ( ($pks = $this->_get_pk( )) && is_array($pks) && count($pks) ) {
				// log_message('debug', "[Model.$table] (save) - have pks with values = " . print_r($pks, true));
				$where	= array();
				$values	= array();

				foreach ($pks as $pk) {
					if ( !isset( $this->columns[$pk] ) || !$this->columns[$pk] ) {
						$where = null;
						$values = null;
					}
					$where[]		= "$pk = ?";
					$values[]		= $this->columns[$pk];
					$pk_vals[$pk]	= $this->columns[$pk];
				}
				
				if ($where && $values) {
					$this->reset_query();
					$row_exists = $this->where(implode(" and ", $where), $values)->count() ? true : false;
				}

				log_message('debug', "[Model.$table] (save) - row result = " . ($row_exists ? 'yes' : 'no'));

			}
			if ( $row_exists ) {
				log_message('debug', "[Model.$table] (save) - updating");
				$saved = $this->db->update( $this->_get_table_name(), $data, $pk_vals);
				log_message('debug', "[Model.$table] (save) - Query: " . $this->db->last_query());
				if ( !$saved ) {
					log_message('debug', "[Model.$table] (save) - Error: " . $this->get_error_message() );
				} else {
					return true;
				}

			} else {
				log_message('debug', "[Model.$table] (save) - Inserting");
				$saved = $this->db->insert( $this->_get_table_name( ), $data ); 
				log_message('debug', "[Model.$table] (save) - Query: " . $this->db->last_query());
				
				if ( $saved ) {
					$insert_id = $this->db->insert_id( );
					if ( $ai = $this->_get_ai() ) {
						$this->columns[$ai] = $insert_id;
					}
					log_message('debug', "[Model.$table] (save) - insert_id = $insert_id");
		 			return $insert_id ? $insert_id : true;
				} else {
					log_message('debug', "[Model.$table] (save) - Error: " . $this->get_error_message() );
					return false;
				}
			}
		} catch (Exception $e) {
			log_message("info", "[Model.$table] (save) - catch: " . print_r($e, true));
			return false;
		}

		log_message("info", "[Model.$table] (save) - error: " . $this->db->_error_message());
		return false;
	}
	
	/**
	 * Delete Row
	 *
 	 * @access	public
	 * @param	`string`	-	(optional) Column name, by value of which you want to delete rows. Can specify multiple columns as different arguments
	 *							If no arguments are provided, uses `const pk` to delete row
	 * @return	`boolean`	-	`true` in case of successful deletion, otherwise `false`
	 *
	 */
	public function delete( ) {
		$args	= func_get_args();
		$where	= count($args) ? $args : $this->_get_pk();
		$table	= $this->_get_table_name();
		$vars	= $this->columns;
		
		foreach ($where as $key=>$column) {
			$where[$key] = "{$column}={$vars[$column]}";
		}
		$where = count($where) > 0 ? implode(" and ", $where) : "id={$vars['id']}";
		
		$query = "delete from $table where $where";
		
		log_message("info", "[Model.$table] (delete) - $query");
		
		$db_responce = $this->db->query($query);
		return $db_responce ? true : false;
	}
	
	/**
	 * Clean Table
	 *
	 * Deletes all rows from the model table
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`boolean`	-	`true` in case of successful deletion, otherwise `false`
	 *
	 */
	public function clean_table() {
		$table = $this->_get_table_name();
		$query = "delete from $table";
		
		log_message("info", "[Model.$table] (delete all) - $query");
		
		$db_responce = $this->db->query($query);
		return $db_responce ? true : false;
	}

	/**
	 * Get error messages of query
	 *
	 * Returns error message of last executed query, if any
	 *
 	 * @access	public
	 * @param	`void`
	 * @return	`string`	-	Error message of last executed query, if any
	 *
	 */
	public function get_error_message () {
		return $this->db->_error_message();
	}

	public function export( ) {
		$strip	= array_merge( array( "query_settings" ), func_get_args() );
		$props	= get_object_vars($this);

		var_dump($props);
		foreach ($strip as $strip_prop) {
			echo $strip_prop .", ";
			if ( isset($props[$strip_prop]) ) {
				unset( $props[$strip_prop] );
				echo 'done|';
			}
			else
				echo 'fail|';

		}
		echo "<br>";

		if ( isset( $props['ref'] ) ) {
			foreach ($props['ref'] as $ref => $ref_array) {
				foreach ($ref_array as $key => $ref_obj) {
					$props['ref'][$ref][$key] = $ref_obj->export();
				}
			}
		}

		return (object) $props;
	}

	
	public function get_ref( $ref = null, $ref_ind = null ) {
		if ( $ref ) {
			if ( isset( $this->ref, $this->ref[$ref] ) ) {
				return ( is_numeric($ref_ind) && isset($this->ref[$ref][$ref_ind]) 
						? $this->ref[$ref][$ref_ind] 
						: $this->ref[$ref] );
			}
			
		} else if ( isset( $this->ref ) ) {
			return $this->ref;
			
		}
		return null;
	}

	private function _undefined_func_exception ($func) {
		throw new Exception("Call to undefined function '$func' on " . get_class($this) . " Model", 1);

	}

	private function _recursive_flatten_array ( $carry, $item ) {
		if ( is_array( $item ) )
			$carry = array_merge($carry, array_reduce( $item, array(get_class($this), "_recursive_flatten_array"), array() ) );
		else
			$carry[] = $item;

		return $carry;
	}

	private function _cast ( $array ) {
		$class = get_class($this);
		$data = 'O:'.strlen($class).':"'.$class.'":1:{s:10:"' . chr(0).'*'.chr(0) . 'columns";'.serialize($array).'}';
		$model_object = unserialize($data);
		$model_object->reset_query();

		return $model_object;
	}

	private function _set_prop ( $prop, $value ) {
		if ( array_key_exists($prop, $this->columns ) )
			$this->columns[$prop] = $value;
		else
			$this->_undefined_func_exception("set_$prop");
	}

	private function _get_prop ( $prop ) {
		if ( array_key_exists($prop, $this->columns ) )
			return $this->columns[$prop];

		$this->_undefined_func_exception("get_$prop");
	}

	/**
	 * 
	 *
	 * @see	http://php.net/manual/en/language.oop5.overloading.php#object.call
	 */
	public function __call( $name, $arguments ) {
		$func = substr($name, 0, 4);
		$prop = substr($name, 4);
		if ( $func == "set_" ) {
			$this->_set_prop( $prop, $arguments[0] ? $arguments[0] : null );

		} else if ( $func == "get_" ) {
			return $this->_get_prop( $prop );

		} else {
			$this->_undefined_func_exception($name);
			
		}
	}

	public function __toString() {
		return json_encode($this->strip_props(), true);
	}

	public function __invoke( $load_refs = false, $same_obj = false ) {
		return $this->find( $load_refs, $same_obj );
	}

	public function __debugInfo() {
		return array_merge(get_object_vars($this), array( "last_db_error" => $this->get_error_message() ));
	}

	private function _flatten_array( $array ) {
			return array_reduce( $array, array(get_class($this), "_recursive_flatten_array"), array());	
	}
}
// END Model Class

/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */