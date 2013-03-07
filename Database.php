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
     * Return the \mysqli connection
     */
    public function getMysqli()
    {
        return $this->db;
    }

    /**
     * Enable variable escaping according to Mysqli
     *
     * @param  mixed        $str string|array to be mysqli escaped
     * @return string|array
     */
    protected function escape($str)
    {
        if (is_array($str)) {
          return array_map(array($this, 'escape'), $str);
        } elseif (is_string($str)) {
          return "'". $this->db->real_escape_string($str). "'";
        }
    }

    /**
     * Changes a camelCase table or field name to lowercase,
     * underscore spaced name
     *
     * @param  string $string camelCase string
     * @return string underscore_space string
     */
    protected function camelCaseToUnderscore($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
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
     * @param  string     $function
     * @param  array      $arrParams
     * @return array|bool
     */
    public function __call($function, array $params = array())
    {
        if (! preg_match('/^(get|update|insert|delete)(.*)$/', $function, $matches)) {
            throw new BadMethodCallException($function.' is an Invalid Method Call');
        }

        if ('insert' == $matches[1]) {
            if (! is_array($params[0]) || count($params[0]) < 1) {
                throw new InvalidArgumentException('params for insert must be an array');
            }

            return $this->insert($this->camelCaseToUnderscore($matches[2]), $params[0]);
        }

        list($tableName, $fieldName) = explode('By', $matches[2], 2);
        if (! isset($tableName, $fieldName)) {
            throw new BadMethodCallException($function.' is an Invalid Method Call');
        }

        if ('update' == $matches[1]) {
            if (! is_array($params[1]) || count($params[1]) < 1) {
                throw new InvalidArgumentException('params for update must be an array');
            }

            return $this->update(
                $this->camelCaseToUnderscore($tableName),
                array($this->camelCaseToUnderscore($fieldName) => $params[0]),
                $params[1]
            );
        }

        //select and delete method
        return $this->{$matches[1]}(
            $this->camelCaseToUnderscore($tableName),
            array($this->camelCaseToUnderscore($fieldName) => $params[0])
        );
    }

    /**
     * Record retrieval method
     *
     * @param  string     $tableName name of the table
     * @param  array      $where     (key is field name)
     * @return array|bool (associative array for single records, multidim array for multiple records)
     */
    protected function get($tableName, array $where)
    {
        $res = $this->db->query(
                "SELECT * FROM $tableName WHERE ".key($where).' = '.$this->escape(current($where));
                );
        if (! $res) {
            throw RunTimeException("Error Code [".$this->db->errno."] : ". $this->db->error);
        }
        if ($res->num_rows == 1) {
            return $res->fetch_assoc();
        } elseif ($res->num_rows > 1) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }

            return $output;
        }

        return false;
    }

    /**
     * Update Method
     *
     * @param  string $tableName
     * @param  array  $set       (associative where key is field name)
     * @param  array  $where     (associative where key is field name)
     * @return int    number of affected rows
     */
    protected function update($tableName, array $set, array $where)
    {
        $arrSet = array();
        foreach ($set as $field => $value) {
            $arrSet[] = $field . ' = '. $this->escape($value);
        }

        $res = $this->db->query(
            "UPDATE $tableName SET ".implode(',', $arrSet)."
            WHERE ".key($where). ' = '. $this->escape(current($where));
        );
        if (! $res) {
          throw new RunTimeException("Error Code [".$this->db->errno."] : ". $this->db->error);
        }

        return $this->db->affected_rows;
    }

    /**
     * Delete Method
     *
     * @param  string $tableName
     * @param  array  $where     (associative where key is field name)
     * @return int    number of affected rows
     */
    protected function delete($tableName, array $where)
    {
        $res = $this->db->query(
            "DELETE FROM $tableName WHERE ".key($where).' = '.$this->escape(current($where));
        );
        if (! $res) {
          throw new RunTimeException("Error Code [".$this->db->errno."] : ". $this->db->error);
        }

        return $this->db->affected_rows;
    }

    /**
     * Insert Method
     *
     * @param  string $tableName
     * @param  array  $arrData   (data to insert, associative where key is field name)
     * @return int    number of affected rows
     */
    protected function insert($tableName, array $data)
    {
        $res = $this->db->query(
            "INSERT INTO $tableName (".implode(',', array_keys($data)).")
            VALUES (".implode(',', $this->escape(array_values($data))).")"
        );
        if (! $res) {
          throw new RunTimeException("Error Code [".$this->db->errno."] : ". $this->db->error);
        }

        return $this->db->affected_rows;
    }
}
