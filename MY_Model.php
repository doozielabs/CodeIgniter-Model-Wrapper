<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CI Model Wrapper
 *
 * An open source library for CodeIgniter to replace CI_Model
 *
 * @package		Application
 * @author		hassanabbasi (Hassan ur Rehman Abbasi)
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
 * @author		hassanabbasi (Hassan ur Rehman Abbasi)
 * @author		DoozieLabs
 */
class MY_Model extends CI_Model {
	/**
	 * Constructor
	 *
	 * @access public
	 */
	private static $table;
	function __construct()
 	{
  		MY_Model::$table = $this->_get_table_name();
  		log_message('debug', "[Model." . MY_Model::$table . "] Initialized");
 	}
	 
	protected function _get_table_name () {
		$klass = new ReflectionClass($this);
		return $klass->getConstant("table");
	}

	protected function _get_pk () {
		$klass = new ReflectionClass($this);
		$pk = $klass->getConstant("pk");	

		if ($pk) {
			$pk = explode(",", $pk);
			foreach ($pk as $key => $value) {
				$pk[$key] = trim($value);
			}
		}
		return $pk;
	}

	protected function _get_ai () {
		$klass = new ReflectionClass($this);
		return $klass->getConstant("ai");
	}

	protected function _get_fk () {
		$klass = new ReflectionClass($this);
		$fk = $klass->getConstant("fk");	

		if ($fk) {
			$fk = explode(",", $fk);
			foreach ($fk as $key => $value) {
				$act_fk = array();
				$raw_fk = trim($value);
				$raw_fk = explode(":", $value);

				if (!count($raw_fk) == 2) continue;

				$act_fk['column'] = trim($raw_fk[0]);
				$raw_fk_ref = explode(".", $raw_fk[1]);

				if (!count($raw_fk_ref) == 2) continue;

				$act_fk['ref'] = array( "table" => trim($raw_fk_ref[0]), "column" => trim($raw_fk_ref[1]) );

				$fk[$key] = $act_fk;
			}
		}
		return $fk;
	}
	
	public function start_transaction() {
		$table = MY_Model::$table;
		log_message("info", "[Model.$table] (start transaction)");
		// return $this->db->query("start transaction;") ? true : false;
		return $this->db->trans_begin() ? true : false;
	}
	
	public function commit_transaction() {
		$table = MY_Model::$table;
		log_message("info", "[Model.$table] (commit transaction)");
		// return $this->db->query("commit;") ? true : false;
		return $this->db->trans_commit() ? true : false;
	}
	
	public function rollback_transaction() {
		$table = MY_Model::$table;
		log_message("info", "[Model.$table] (rollback transaction)");
		// return $this->db->query("rollback;") ? true : false;
		return $this->db->trans_rollback() ? true : false;
	}
	
	protected function get_sequence () {
		$sequence = 1;
		$table = MY_Model::$table;
		
		$query = "insert into sequence (table_name, sequence_num) values('$table', $sequence) ";
		$query.= "on duplicate key update sequence_num = sequence_num + 1;";
		
		$seq_res = $this->db->query($query);
		if($seq_res) {
			$result = $this->find_one(array(
						"from" => "sequence",
						"where" => "table_name=?",
						"values" => array($table)
					));
			$sequence = $result ? $result->sequence_num : 1;
		}
		
		return $sequence;
	}
	
	protected function get_shard ($id) {
		return floor($id / 10000) + 1;
	}
	
	protected function create_shard () {
		$table = $this->_get_table_name();
		$sequence = $this->find_one(array(
					"from" => "sequence",
					"where" => "table_name=?",
					"values" => array($this->_get_table_name())
				));
		
		if($sequence && isset($sequence->sequence_num)) {
			$new_shard = $this->get_shard($sequence->sequence_num + 1);
			if($new_shard > $sequence->shard_num) {
				$new_shard = $table . "_shard_" . $new_shard;
				
				$db_responce = $this->db->query("create table $new_shard like $table;");
				if($db_responce) {
					$this->db->query("update sequence set shard_num=shard_num+1 where table_name='$table';");
					return true;
				}
			} else {
				log_message("info", "DoozieLabs: There is no need of new shard for table $table");
			}
		}
		
		log_message("warn", "DoozieLabs: sequence not found for table $table");
		return false;
	}

	public function make_wherein ($column, $values) {
		$template = array_pad(array(), count($values), "?");
		return "$column in (" . implode(", ", $template) . ")"; 
	}
	
	public function find ($options = array()) {
		$load_refs	= ((is_bool($options) && $options === true) || (is_array($options) && isset($options['load_refs']) && $options['load_refs'] === true)) ? true : false;
		$options	= is_array($options) ? $options : array();
		$table		= $this->_get_table_name();
		
		if(!is_array($options)) {
			log_message("error", "[Model.$table] (find) - Invalid arguments " . gettype($options) . " provided instead of array.");
			$options = array();
		}
		
		// SELECT
		$query = "select ";
		
		// COLUMNS
		if(isset($options['columns']) && is_array($options['columns'])) {
			$query .= join(', ', $options['columns']) . " ";
		} elseif (isset($options['columns']) && is_string($options['columns'])) {
			$query .= $options['columns'];
		} else {
			$query .= "*";
		}
		
		// FROM
		if(isset($options['from']) && is_string($options['from'])) {
			$table = $options['from'];
		}
		$query .= " from $table";
		
		
		// WHERE
		if(isset($options['where']) && is_string($options['where']) && isset($options['values']) && is_array($options['values'])) {
			$where = $options['where'];
			
			if(count($options['values']) != count(explode("?", $options['where']))-1) {
				log_message("error", "[Model.$table] (find) - Invalid where clause supplied in options.");
			} else {
				foreach($options['values'] as $value) {
					if(is_string($value)) {
						// $value = "'" . $value . "'";
					} else if (is_null($value)){
						$value = "NULL";
					}
					$start = strpos($where, "?");
					$where = substr_replace($where, $value, $start, 1);
				}
				$query .= " where " . $where;
			}
		}
		
		// GROUP BY
		if(isset($options['group']) && is_string($options['group'])) {
			$query .= " group by " . $options['group'];
		}

		// HAVING
		if(isset($options['having']) && is_string($options['having'])) {
			$query .= " having " . $options['having'];
		}		

		// ORDER BY
		if(isset($options['order']) && is_string($options['order'])) {
			$query .= " order by " . $options['order'];
		}
		
		// LIMIT
		if(isset($options['limit']) && (is_string($options['limit']) || is_int($options['limit']))) {
			$query .= " limit " . $options['limit'];
		} elseif(isset($options['limit']) && is_array($options['limit']) && count($options['limit']) == 2) {
			$query .= " limit " . join(", ", $options['limit']);
		}
		
		$query .= ";";
		
		log_message("info", "[Model.$table] (find) - $query");
		
		$db_responce = $this->db->query($query);
		// print_r($this->get_error_message());
		// print_r($db_responce->result());
		$results = $db_responce->result(($table == $this->_get_table_name() || (isset($options['same_obj']) && $options['same_obj'])) ? get_class($this) : "object");

		if ( ($table == $this->_get_table_name() || (isset($options['same_obj']) && $options['same_obj'])) && $load_refs && $fks = $this->_get_fk() ) {
			foreach ($results as $key => $row) {
				$results[$key]->ref = array();
				foreach ($fks as $fk) {
					if ( !isset( $results[$key]->$fk['column'] ) ) continue;
					$fk_col		= $fk['column'];
					$ref_table	= $fk['ref']['table'];
					$ref_col	= $fk['ref']['column'];

					$CI =& get_instance();
					$CI->load->model( $ref_table, $ref_table, true );
					$results[$key]->ref["{$fk_col}:{$ref_table}"] = $CI->$ref_table->find( array (
							"where"		=> "{$ref_col} = ?",
							"values"	=> array($results[$key]->$fk_col)
						)
					);

				}
			}
		}

		return $results;
	}

	public function load_refs ( ) {
		
		if ( $fks = $this->_get_fk() ) {
			$this->ref = array();
			foreach ($fks as $fk) {
				if ( !isset( $this->$fk['column'] ) ) { continue; };

				$fk_col		= $fk['column'];
				$ref_table	= $fk['ref']['table'];
				$ref_col	= $fk['ref']['column'];

				$CI =& get_instance();
				$CI->load->model( $ref_table, $ref_table, true );
				$this->ref["{$fk_col}:{$ref_table}"] = $CI->$ref_table->find( array (
						"where"		=> "{$ref_col} = ?",
						"values"	=> array($this->$fk_col)
					)
				);

			}
			return true;
		}
		return false;
	}
	
	public function find_one ($options = array()) {
		$load_refs	= ((is_bool($options) && $options === true) || (is_array($options) && isset($options['load_refs']) && $options['load_refs'] === true)) ? true : false;
		$options	= is_array($options) ? $options : array();
		if ( $load_refs ) {
			$options['load_refs'] = true;
		}

		$options["limit"] = 1;
		
		$result = $this->find($options);
		if($result && is_array($result) && count($result)) {
			$result = $result[0];
		} else {
			$result = false;
		}
		
		return $result;
	}

	public function copy ($object) {
		$copied = false;
		if ($object instanceof self) {
			$object_array = (array) $object;

			foreach ($object_array as $property => $value) {
				if (preg_match("=^[a-zA-Z\_][a-zA-Z0-9\_]+$=", $property)) {
					$this->$property = $value;
					$copied = true;
				}
			}
		}

		return $copied;
	}

	public function load ($options = array()) {
		$copied = false;

		$load_refs	= ((is_bool($options) && $options === true) || (is_array($options) && isset($options['load_refs']) && $options['load_refs'] === true)) ? true : false;
		$options	= is_array($options) ? $options : array();
		if ( $load_refs === true ) {
			$options['load_refs'] = true;
		}
		$options["same_obj"] = true;
		
		$object = $this->find_one($options);
		if ($object) {
			$copied = $this->copy($object);
		}

		return $copied;
	}
	
	public function count ($options = array()) {
		$result = $this->find($options);
		if($result && is_array($result) && count($result)) {
			return count($result);
		}
	
		return 0;
	}
	
	public function save ( $update = false ) {
		$vars = get_object_vars($this);
		$table = $this->_get_table_name();
		
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
					if ( !isset( $this->$pk ) || !$this->$pk ) {
						$where = null;
						$values = null;
					}
					$where[]		= "$pk = ?";
					$values[]		= $this->db->escape( $this->$pk );
					$pk_vals[$pk]	= $this->$pk;
				}
				
				if ($where && $values) {
					$row_exists = $this->count( array (
						"where" => implode(" and ", $where),
						"values" => $values
					) ) ? true : false;
				}

				log_message('debug', "[Model.$table] (save) - row result = " . ($row_exists ? 'yes' : 'no'));

			}
			if ( $row_exists ) {
				log_message('debug', "[Model.$table] (save) - updating");
				return $this->db->update( $this->_get_table_name(), $data, $pk_vals);

			} elseif ( !$row_exists &&  $this->db->insert( $this->_get_table_name( ), $data ) ) {
				$insert_id = $this->db->insert_id( );
				if ( $ai = $this->_get_ai() ) {
					$this->$ai = $insert_id;
				}
				log_message('debug', "[Model.$table] (save) - insert_id = $insert_id");
	 			return $insert_id ? $insert_id : true;

			} else {
				return false;
			}
		} catch (Exception $e) {
			log_message("info", "[Model.$table] (save) - catch: " . print_r($e, true));
			return false;
		}
		log_message("info", "[Model.$table] (save) - error: " . $this->db->_error_message());

		return false;
	}

	public function save_unsafe ($duplicate=false) {
		$vars = get_object_vars($this);
		$table = $this->_get_table_name();
		
		$data = array();
		foreach($vars as $key=>$value) {
			if (is_array($value) || is_object($value)) {
				continue;
				
			} else if(is_string($vars[$key])) {
				$data[$key] = "'" . $value . "'";
				
			} else if(is_numeric($vars[$key])) {
				$data[$key] = $value;
				
			} else if(is_null($vars[$key])) {
				$data[$key] = 'NULL';
				
			}
		}
		
		// INSERT INTO TABLE (COLUMN[, ...]) VALUES (VALUE[, ...])
		$query = "insert into " . $this->_get_table_name() . " (" . implode(",", array_keys($data)) . ") values (" . implode(",", array_values($data)) . ")";
		
		// ON DUPLICATE KEY UPDATE
		if($duplicate) {
			$query .= " on duplicate key update ";
			$update = array();
			
			// COLUMN=VALUE[, ...]
			foreach($data as $key=>$value) {
				if($key == 'id' || $value . "" == 'NULL' || is_array($value) || is_object($value)) continue;
				
				array_push($update, $key . "=" . $value);
			}
			$query .= implode(", ", $update);
		}
		
		log_message("info", "[Model.$table] (save) - $query");
		
		$db_responce = $this->db->query($query);
		return $db_responce ? true : false;
	}
	
	public function update_unsafe () {
		$table = $this->_get_table_name();
		$vars = get_object_vars($this);
		
		// UPDATE TABLE SET
		$query = "update $table set ";
		$update = array();
		
		// COLUMN=VALUE[, ...]
		foreach($vars as $key=>$value) {
			if (is_array($value) || is_object($value)) {
				continue;
				
			} else if(is_string($vars[$key])) {
				array_push($update, $key . "='" . $value . "'");
				
			} else if(!is_null($vars[$key])) {
				array_push($update, $key . "=" . $value);
				
			}
		}
		$query .= implode(', ', $update);
		
		log_message("info", "[Model.$table] (update) - $query");
		
		$db_responce = $this->db->query($query);
		return $db_responce ? true : false;
	}
	
	public function delete($where=array()) {
		$table = $this->_get_table_name();
		$vars = get_object_vars($this);
		
		foreach ($where as $key=>$column) {
			$where[$key] = "$column=$vars[$column]";
		}
		$where = count($where) > 0 ? implode(" and ", $where) : "id=$vars[id]";
		
		$query = "delete from $table where $where";
		
		log_message("info", "[Model.$table] (delete) - $query");
		
		$db_responce = $this->db->query($query);
		return $db_responce ? true : false;
	}
	
	public function delete_all() {
		$table = $this->_get_table_name();
		$query = "delete from $table";
		
		log_message("info", "[Model.$table] (delete all) - $query");
		
		$db_responce = $this->db->query($query);
		return $db_responce ? true : false;
	}

	public function get_error_message () {
		return $this->db->_error_message();
	}
}
// END Model Class

/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */