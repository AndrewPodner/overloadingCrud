<?php

/**
 * File Description: Simple class for using overloading / dynamic methods
 * to accomplish basic CRUD functionality for a MySQL database
 *
 * @author     Andrew Podner <andy@unassumingphp.com>
 * @copyright  2013
 * @license    MIT <http://opensource.org/licenses/MIT>
 */

namespace Crud;

class Database
{

    /**
     * database connection object
     * @var \PDO
     */
    protected $pdo;

    /**
     * Connect to the database
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Return the pdo connection
     */
    public function getPdo()
    {
        return $this->pdo;
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
        if ('lastInsertId' === $function) {
            return $this->pdo->lastInsertId();
        } elseif (! preg_match('/^(get|update|insert|delete)(.*)$/', $function, $matches)) {
            throw new \BadMethodCallException($function.' is an Invalid Method Call');
        }

        if ('insert' == $matches[1]) {
            if (! is_array($params[0]) || count($params[0]) < 1) {
                throw new \InvalidArgumentException('params for insert must be an array');
            }

            return $this->insert($this->camelCaseToUnderscore($matches[2]), $params[0]);
        }

        list($tableName, $fieldName) = explode('By', $matches[2], 2);
        if (! isset($tableName, $fieldName)) {
            throw new \BadMethodCallException($function.' is an Invalid Method Call');
        }

        if ('update' == $matches[1]) {
            if (! is_array($params[1]) || count($params[1]) < 1) {
                throw new \InvalidArgumentException('params for update must be an array');
            }

            return $this->update(
                $this->camelCaseToUnderscore($tableName),
                $params[1],
                array($this->camelCaseToUnderscore($fieldName) => $params[0])
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
        $stmt = $this->pdo->prepare("SELECT * FROM $tableName WHERE ".key($where) . ' = ?');
        try {
            $stmt->execute([current($where)]);
            $res = $stmt->fetchAll();
            if (! $res || count($res) != 1) {
                return $res;
            }

            return current($res);
        } catch (\PDOException $e) {
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
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
        $arrSet = array_map(
            function($value) {
                return $value . '=:' . $value;
            },
            array_keys($set)
        );

        $stmt = $this->pdo->prepare(
            "UPDATE $tableName SET ". implode(',', $arrSet).' WHERE '. key($where). '=:'. key($where) . 'Field'
        );

        foreach ($set as $field => $value) {
            $stmt->bindValue(':'.$field, $value);
        }
        $stmt->bindValue(':'.key($where) . 'Field', current($where));
        try {
            $stmt->execute();

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
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
        $stmt = $this->pdo->prepare("DELETE FROM $tableName WHERE ".key($where) . ' = ?');
        try {
            $stmt->execute(array(current($where)));

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
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
        $stmt = $this->pdo->prepare(
                "INSERT INTO $tableName (".implode(',', array_keys($data)).")
                VALUES (".implode(',', array_fill(0, count($data), '?')).")"
                );
        try {
            $stmt->execute(array_values($data));

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
    }
}
