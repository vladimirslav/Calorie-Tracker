<?php

require_once(__DIR__.'/../config.php');

class DB
{

    private static $connection;
    private static $connected = false;


    /* fiels in key => value format 
       alternatively fields element can be an array (params)
       params are:
        'sign' - comparison sign ('=') used by default
        'val' - field value that is used in comparison
        'key' - key
    */
    private static function prepare_conditions($where) {
        if ($where) {
            $conditions = array();
            foreach ($where as $key => $val) {
            
                $value = $val;
                $sign = '=';
                $field = $key;
                $is_cond = strcmp(substr($field, 0, 5), '_cond') == 0;
                
                if ($is_cond && is_array($val))
                {
                    $sign = $val['sign'];
                    $value = $val['val'];
                    $field = $val['key'];
                }
            
                if (is_numeric($value)) {
                    array_push($conditions, $field . $sign . (int) $value);
				} else if (is_array($value)) {
					array_push($conditions, $field . ' IN (' . join(",", $value) . ')');
                } else {
                    array_push($conditions, $field . $sign . "'" . self::$connection->real_escape_string($value) . "'");
                }
            }

            return ' WHERE ' . implode(" AND ", $conditions);
        } else {
            return '';
        }
    }

    private static function prepare_order($order) {
        if ($order) {
            $fields = array();
            foreach ($order as $field => $value) {
                array_push($fields, $field . " " . (($value == 1) ? 'asc' : 'desc'));
            }
            return ' ORDER BY ' . implode(" AND ", $fields);
        } else {
            return '';
        }
    }

    private static function prepare_fields_for_insert($fields) {
        if ($fields) {
            $processed_fields = array();
            foreach ($fields as $field => $value) {
                if ($value === null) {
                    $processed_fields[] = $field . "=NULL";
                } else if (is_numeric($value)) {
                    $processed_fields[] = $field . "=" . (int) $value;
                } else {
                    $processed_fields[] = $field . "='" . self::$connection->real_escape_string($value) . "'";
                }
            }

            return implode(",", $processed_fields);
        } else {
            return '';
        }
    }

    private static function Connect() {
        if (!self::$connected) {
            self::$connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            self::$connected = true;
        }
    }

    /*
     * Fetches the data from database
     * $table - the table to query
     * $flags - array of arrays. Format: 
     ** "where" => array("field" => "value"))
     ** "order" => array("field" => 1 for asc, any other value for desc))
    */
    
    public static function Query($table, $flags) {
        self::Connect();
        $query = "SELECT * FROM " . $table;
        if (isset($flags["where"])) {
            $query .= self::prepare_conditions($flags["where"]);
        }
        if (isset($flags['order'])) {
            $query .= self::prepare_order($flags['order']);
        }
        if (isset($flags['limit'])) {
            $query .= " LIMIT " . (int)($flags['limit']);
        }
        if (isset($flags['offset'])) {
            $query .= " OFFSET " . (int)($flags['offset']);
        }
        return self::RawQuery($query);
    }
	
	public static function Truncate($table) {
		self::Connect();
		self::RawQuery('TRUNCATE '.self::$connection->real_escape_string($table));
	}

    public static function Update($table, $data, $flags) {
        self::Connect();
        if (isset($flags['where'])) {
            $query = 'UPDATE ' . $table . ' SET ' . self::prepare_fields_for_insert($data) . self::prepare_conditions($flags['where']);
            $res = self::RawQuery($query);
            if ($res) {
                return self::$connection->affected_rows;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public static function Insert($table, $data, $flags = array()) {
        self::Connect();
        $query = 'INSERT INTO ' . $table . ' SET ' . self::prepare_fields_for_insert($data);
        if (isset($flags['duplicate'])) {
            $query .= ' ON DUPLICATE KEY UPDATE name=name';
        }
        if (DEBUG_DB) {
            echo $query;
        }
        $res = self::RawQuery($query);
        if ($res) {
            return self::$connection->insert_id;
        } else {
            return 0;
        }
    }

    public static function Escape($str) {
        self::Connect();
        return self::$connection->real_escape_string($str);
    }
    
    public static function RawQuery($query) {
        self::Connect();
        if (DEBUG_DB) {
            echo $query;
        }
        return self::$connection->query($query);
    }

    public static function GetSingle($table, $flags) {
        $result = null;
        $result = self::Query($table, $flags);
        if ($result && $result->num_rows) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    }
	
	public static function Delete($table, $flags) {
		if (!$flags || !isset($flags['where'])) {
			return null;
		}
		
        self::Connect();
        $query = "DELETE FROM " . $table;
        $query .= self::prepare_conditions($flags["where"]);
        
        if (isset($flags['order'])) {
            $query .= self::prepare_order($flags['order']);
        }
		
        return self::RawQuery($query);
	}

    /*
     * Same as query, but
     * can point out 'sort_by' parameter and the function
     * will return associative array, sorted by given field
    */
    public static function GetAsArray($table, $flags = array()) {
        $result = null;        
        $result = self::Query($table, $flags);
        $results = array();
        if ($result && $result->num_rows) {
            while ($row = $result->fetch_assoc()) {
                $f_name = null;
                if (isset($flags['sort_by'])) {
                    $f_name = $flags['sort_by'];
                }
                if ($f_name && isset($row[$f_name])) {
                    $results[$row[$f_name]] = $row;
                } else {
                    $results[] = $row;
                }
            }
        }
        if (DEBUG_DB) {
            echo 'Results:';
            print_r($results);
        }
        return $results;
    }
    
    public static function GetError()
    {
        return self::$connection->error;
    }
    
    public static function GetErrorCode()
    {
        return self::$connection->errno;
    }
    
    public static function GetConnectionErrorCode()
    {
        return self::$connection->connect_errno;
    }
    
    public static function GetConnectionError()
    {
        return self::$connection->connect_error;
    }

}

