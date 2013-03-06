<?php

/**
 * File Description: Simple class for using overloading / dynamic methods
 * to accomplish basic CRUD functionality for a MySQL database
 *
 * @author     Andrew Podner <andy@unassumingphp.com>
 * @copyright  2013
 * @license    MIT <http://opensource.org/licenses/MIT>
 */
class Database
{

    /**
     * database connection object
     * @var \mysqli
     */
    protected $db;

    /**
     * Connect to the database
     */
    public function __construct(Mysqli $dbh)
    {
        $this->db = $dbh;
    }

    /**
     * handler for dynamic CRUD methods
     *
     * Format for dynamic methods names -
     * Create:  insertTableName($arrData)
     * Retrieve: getTableNameByFieldName($value)
     * Update: updateTableNameByFieldName($value, $arrUpdate)
     * Delete: deleteTableNameByFieldName($value)
     *
     * @param string $function
     * @param array $arrParams
     * @return array|bool
     */
    public function __call($function, $arrParams)
    {

        $action = substr($function, 0, 3);
        switch ($action) {
            // Record Retrieval
            case 'get':
                $string = str_replace('get', '', $function);
                $arr = explode('By', $string);
                return $this->get(
                    $this->camelCaseToUnderscore($arr[0]), 
                    array(
                        $this->camelCaseToUnderscore($arr[1]) => 
                        $this->db->real_escape_string($arrParams[0])
                    )
                );
                break;

            // Update
            case 'upd':
                $string = str_replace('update', '', $function);
                $arr = explode('By', $string);
                return $this->update(
                    $this->camelCaseToUnderscore($arr[0]), 
                    array_map(array($this->db, 'real_escape_string'), $arrParams[1]), 
                    array(
                        $this->camelCaseToUnderscore($arr[1]) =>
                        $this->db->real_escape_string($arrParams[0])
                    )
                );
                break;

            // Delete
            case 'del':
                $string = str_replace('delete', '', $function);
                $arr = explode('By', $string);
                return $this->delete(
                    $this->camelCaseToUnderscore($arr[0]),
                    array(
                        $this->camelCaseToUnderscore($arr[1]) =>
                        $this->db->real_escape_string($arrParams[0]);
                    )
                );
                break;

            // Insert
            case 'ins':
                $string = str_replace('insert', '', $function);
                return $this->insert(
                    $this->camelCaseToUnderscore($string), 
                    array_map(array($this->db, 'real_escape_string'), $arrParams[0])
                );
                break;
        }
    }

    /**
     * Changes a camelCase table or field name to lowercase,
     * underscore spaced name
     *
     * @param string $string camelCase string
     * @return string underscore_space string
     */
    protected function camelCaseToUnderscore($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    /**
     * Record retrieval method
     *
     * @param string $tableName name of the table
     * @param array $where (key is field name)
     * @return array|bool (associative array for single records, multidim array for multiple records)
     */
    protected function get($tableName, $where)
    {
        $rs = $this->db->query(
            "SELECT * FROM $tableName WHERE ".key($where)." = '".current($where)."'"
        );
        if ($rs->num_rows == 1) {
            return $rs->fetch_assoc();
        } elseif ($rs->num_rows > 1) {
            while ($row = $rs->fetch_assoc()) {
                $output[] = $row;
            }
            return $output;
        }
        return false;
    }

    /**
     * Update Method
     *
     * @param string $tableName
     * @param array $set (associative where key is field name)
     * @param array $where (associative where key is field name)
     * @return int number of affected rows
     */
    protected function update($tableName, $set, $where)
    {
        foreach ($set as $filed => $value) {
            $arrSet[] = $field . "= '$value'";
        }

        $this->db->query(
            "UPDATE $tableName SET ".implode(',', $arrSet)." 
            WHERE ".key($where)." = '".current($where)."'"
        );
        return $this->db->affected_rows;
    }

    /**
     * Delete Method
     *
     * @param string $tableName
     * @param array $where (associative where key is field name)
     * @return int number of affected rows
     */
    protected function delete($tableName, $where)
    {
        $this->db->query(
            "DELETE FROM $tableName WHERE ".key($where)." = '".current($where)."'"
        );
        return $this->db->affected_rows;
    }

    /**
     * Insert Method
     *
     * @param string $tableName
     * @param array $arrData (data to insert, associative where key is field name)
     * @return int number of affected rows
     */
    protected function insert($tableName, $arrData)
    {
        $arrValues = array_map(
            function($value){ 
                return "'".$value."'"; 
            }, 
            array_values($arrData)
        );

        $this->db->query(
            "INSERT INTO $tableName (".implode(',', array_keys($arrData)).") 
            VALUES (".implode(',', $arrValues).")"
        );
        return $this->db->affected_rows;
    }
}
