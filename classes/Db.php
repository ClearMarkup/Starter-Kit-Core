<?php

namespace ClearMarkup\Classes;

/**
 * The Db class represents a database connection and provides methods for interacting with the database.
 */
class Db extends Core
{

    private $table;
    private $where = [];
    private $orderBy;
    private $limit;
    private $relTable;

    /**
     * Creates a new Db instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Resets the state of the Db instance.
     *
     * @return void
     */
    private function resetState()
    {
        $this->table = null;
        $this->where = [];
        $this->orderBy = null;
        $this->limit = null;
        $this->relTable = null;
    }

    /**
     * Executes a transaction with the given callback function.
     *
     * @param callable $callback The callback function to execute within the transaction.
     * @return mixed The result of the transaction.
     */
    public function transaction($callback)
    {
        return self::$dbInstance->action($callback);
    }

    /**
     * Sets the name of the table for the query.
     *
     * @param string $table The name of the table.
     * @return $this The Db instance.
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Sets the column and order for sorting the query results.
     *
     * @param string|array $column The column or columns to order by.
     * @param string $order The order of the sorting (ASC or DESC).
     * @return $this The Db instance.
     */
    public function orderBy($column, $order = 'ASC')
    {
        if (is_array($column)) {
            $this->orderBy = $column;
        } else {
            $this->orderBy = [$column => $order];
        }
        return $this;
    }

    /**
     * Sets the maximum number of rows to return in the query results.
     *
     * @param int $limit The maximum number of rows.
     * @return $this The Db instance.
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets the WHERE conditions for the query.
     *
     * @param string|array $where The WHERE conditions.
     * @param mixed $filter The filter value (optional).
     * @return $this The Db instance.
     */
    public function filter($where, $filter = null)
    {
        if (is_string($where)) {
            $where = [$where => $filter];
        }
        $this->where = $where;
        return $this;
    }

    /**
     * Prepares the query by formatting the columns and operations.
     *
     * @param string|array $columns The columns to select.
     * @param string|null $operation The operation to apply to the columns.
     * @return array The prepared query columns and operations.
     */
    private function prepareQuery($columns = '*', $operation = null)
    {
        if (is_string($columns)) {
            if ($columns === '*') {
                $result = '*';
            } else {
                $result = [$columns];
                if ($operation !== null) {
                    $columns = [$columns => $operation];
                } else {
                    $columns = [$columns];
                }
            }
        } else {
            $result = [];
            foreach ($columns as $key => $value) {
                if (is_callable($value)) {
                    $result[] = $key;
                } elseif (is_string($value)) {
                    $result[] = is_string($key) ? $key : $value;
                }
            }
        }

        return [$result, $columns];
    }

    /**
     * Sets the related table information for performing a join operation.
     *
     * @param string $table The name of the related table.
     * @param array $where The WHERE conditions for the join operation.
     * @param string $column The column to join on.
     * @return $this The Db instance.
     */
    public function rel($table, $where, $column)
    {
        $this->relTable = [
            'table' => $table,
            'where' => $where,
            'column' => $column
        ];
        return $this;
    }

    /**
     * Executes a SELECT query and returns the query results.
     *
     * @param string|array $columns The columns to select.
     * @param string|null $operation The operation to apply to the columns.
     * @return array The query results.
     */
    public function select($columns = '*', $operation = null)
    {
        list($result, $columns) = $this->prepareQuery($columns, $operation);

        if ($this->relTable !== null) {
            $relData = self::$dbInstance->select($this->relTable['table'], $this->relTable['column'], $this->relTable['where']);

            if (empty($relData)) {
                $this->resetState();
                return [];
            }
            $data = self::$dbInstance->select($this->table, $result, array_merge($this->where, [
                'id' => $relData,
                'ORDER' => $this->orderBy,
                'LIMIT' => $this->limit
            ]));
        } else {
            $data = self::$dbInstance->select($this->table, $result, array_merge($this->where, [
                'ORDER' => $this->orderBy,
                'LIMIT' => $this->limit
            ]));
        }

        if (!is_array($data)) {
            $this->resetState();
            return $data;
        }

        if ($result !== '*') {
            foreach ($data as $key => $value) {
                foreach ($value as $column => $columnValue) {
                    if (isset($columns[$column])) {
                        $operation = $columns[$column];
                        if (is_callable($operation)) {
                            $data[$key][$column] = $operation($columnValue);
                        } elseif (is_string($operation)) {
                            $operations = explode('|', $operation);
                            $data[$key][$column] = self::applyOperations($columnValue, $operations);
                        }
                    }
                }
            }
        }

        $this->resetState();
        return $data;
    }

    /**
     * Executes a SELECT query and returns a single row of the query results.
     *
     * @param string|array $columns The columns to select.
     * @param string|null $operation The operation to apply to the columns.
     * @return mixed The query result.
     */
    public function get($columns = '*', $operation = null)
    {
        list($result, $columns) = $this->prepareQuery($columns, $operation);

        if ($this->relTable !== null) {
            $relData = self::$dbInstance->get($this->relTable['table'], $this->relTable['column'], $this->relTable['where']);

            if (empty($relData)) {
                $this->resetState();
                return null;
            }
            $data = self::$dbInstance->get($this->table, $result, array_merge($this->where, [
                'id' => $relData
            ]));
        } else {
            $data = self::$dbInstance->get($this->table, $result, $this->where);
        }

        if (!is_array($data)) {
            $this->resetState();
            return $data;
        }

        if ($result !== '*') {
            foreach ($data as $column => $columnValue) {
                if (isset($columns[$column])) {
                    $operation = $columns[$column];
                    if (is_callable($operation)) {
                        $data[$column] = $operation($columnValue);
                    } elseif (is_string($operation)) {
                        $operations = explode('|', $operation);
                        $data[$column] = self::applyOperations($columnValue, $operations);
                    }
                }
            }
        }

        if (count($data) === 1) {
            $this->resetState();
            return array_shift($data);
        } else {
            $this->resetState();
            return $data;
        }
    }

    /**
     * Checks if a record exists in the table based on the WHERE conditions.
     *
     * @return bool True if the record exists, false otherwise.
     */
    public function has()
    {
        if ($this->relTable !== null) {
            $relData = self::$dbInstance->get($this->relTable['table'], $this->relTable['column'], $this->relTable['where']);

            $data = self::$dbInstance->has($this->table, array_merge($this->where, [
                'id' => $relData
            ]));
        } else {
            $data = self::$dbInstance->has($this->table, $this->where);
        }

        $this->resetState();
        return $data;
    }

    /**
     * Counts the number of records in the table based on the WHERE conditions.
     *
     * @return int The number of records.
     */
    public function count()
    {
        if ($this->relTable !== null) {
            $relData = self::$dbInstance->get($this->relTable['table'], $this->relTable['column'], $this->relTable['where']);

            $data = self::$dbInstance->count($this->table, array_merge($this->where, [
                'id' => $relData
            ]));
        } else {
            $data = self::$dbInstance->count($this->table, $this->where);
        }

        $this->resetState();
        return $data;
    }

    /**
     * Inserts a new record into the table.
     *
     * @param array $data The data to insert.
     * @return mixed The result of the insert operation.
     */
    public function insert($data)
    {
        $data = self::$dbInstance->insert($this->table, $data);
        $this->resetState();
        return $data;
    }

    /**
     * Updates records in the table based on the WHERE conditions.
     *
     * @param array $data The data to update.
     * @return mixed The result of the update operation.
     */
    public function update($data)
    {
        $data = self::$dbInstance->update($this->table, $data, $this->where);
        $this->resetState();
        return $data;
    }

    /**
     * Deletes records from the table based on the WHERE conditions.
     *
     * @return mixed The result of the delete operation.
     */
    public function delete()
    {
        $data = self::$dbInstance->delete($this->table, $this->where);
        $this->resetState();
        return $data;
    }

    /**
     * Retrieves the ID from the table based on the selector.
     *
     * @param string $table The name of the table.
     * @param string $selector The selector value.
     * @return mixed The ID value.
     */
    public function getIdFromSelector($table, $selector)
    {
        $data = self::$dbInstance->get($table, 'id', ['selector' => $selector]);
        return $data;
    }
}
