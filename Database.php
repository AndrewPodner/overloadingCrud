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
    public function __construct()
    {
        $this->db = new mysqli('hostname', 'user', 'password', 'database_name');
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
                $tableName = $this->camelCaseToUnderscore($arr[0]);
                $fieldName = $this->camelCaseToUnderscore($arr[1]);
                $where[$fieldName] = $arrParams[0];
                return $this->get($tableName, $where);
                break;

            // Update
            case 'upd':
                $string = str_replace('update', '', $function);
                $arr = explode('By', $string);
                $tableName = $this->camelCaseToUnderscore($arr[0]);
                $fieldName = $this->camelCaseToUnderscore($arr[1]);
                $where[$fieldName] = $arrParams[0];
                $set = $arrParams[1];
                return $this->update($tableName, $set, $where);
                break;

            // Delete
            case 'del':
                $string = str_replace('delete', '', $function);
                $arr = explode('By', $string);
                $tableName = $this->camelCaseToUnderscore($arr[0]);
                $fieldName = $this->camelCaseToUnderscore($arr[1]);
                $where[$fieldName] = $arrParams[0];
                return $this->delete($tableName, $where);
                break;

            // Insert
            case 'ins':
                $string = str_replace('insert', '', $function);
                $tableName = $this->camelCaseToUnderscore($string);
                $arrInsert = $arrParams[0];
                return $this->insert($tableName, $arrInsert);
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
        $field = key($where);
        $value = $where[$field];

        $sql = "select * from $tableName where $field = '$value'";
        $rs = $this->db->query($sql);

        // If it is a single record, return an associative array
        if ($rs->num_rows == 1) {
            return $rs->fetch_assoc();

            // If there are multiple records, build an array of associative arrays
        } elseif ($rs->num_rows > 1) {
            while ($row = $rs->fetch_assoc()) {
                $output[] = $row;
            }
            return $output;

            // If there are no records, return false
        } else {
            return false;
        }
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
        $field = key($where);
        $value = $where[$field];

        foreach ($set as $fld => $val) {
            $arrSet[] = $fld . "= '$val'";
        }

        $setStmt = implode(',', $arrSet);
        $sql = "update $tableName set $setStmt where $field = '$value'";
        $query = $this->db->query($sql);
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
        $field = key($where);
        $value = $where[$field];

        $sql = "delete from $tableName where $field = '$value'";
        $query = $this->db->query($sql);
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
        foreach ($arrData as $fieldName => $value) {
            $arrFields[] = $fieldName;
            $arrValues[] = "'" . $value . "'";
        }
        $fieldString = implode(',', $arrFields);
        $valueString = implode(',', $arrValues);
        $sql = "insert into $tableName ($fieldString) values ($valueString)";
        $result = $this->db->query($sql);
        return $this->db->affected_rows;
    }
}