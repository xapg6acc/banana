<?php
namespace Lebran\Banana;

use PDO;
use PDOStatement;
use PDOException;

class Connection extends PDO
{
    /**
     * @var string Driver type.
     */
    protected $type;

    /**
     * Initializes database connection.
     *
     * @param array $config Database configs.
     */
    public function __construct($config)
    {
        $this->type = $config['type'];
        $config     = $this->prepareConfig($config);
        parent::__construct($config['dsn'], $config['user'], $config['password'], $config['options']);
        foreach ($config['attributes'] as $key => $value) {
            $this->setAttribute($key, $value);
        }

        if ($this->type !== 'sqlite') {
            $this->exec("SET NAMES 'utf8'");
        }
    }

    /**
     * @param $config
     *
     * @return mixed
     */
    protected function prepareConfig($config)
    {
        $dsn = [];
        foreach ($config['dsn'] as $key => $value) {
            $dsn[] = is_int($key)?$value:$key.'='.$value;
        }
        $prepared['dsn']      = $config['type'].':'.implode(';', $dsn);
        $prepared['user']     = empty($config['user'])?:$config['user'];
        $prepared['password'] = !array_key_exists('password', $config)?:$config['password'];
        $prepared['options']  = [];
        if (array_key_exists('options', $config)) {
            $prepared['options'] = array_merge($prepared['options'], $config['options']);
        }
        $prepared['attributes'] = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        if (array_key_exists('attributes', $config)) {
            $prepared['attributes'] = $config['attributes'] + $prepared['attributes'];
        }
        return $prepared;
    }


    /**
     * Gets the id of the last inserted row.
     *
     * @return mixed Row id
     */
    public function insertId()
    {
        if ($this->type === 'pgsql') {
            return $this->query('SELECT lastval() as id')->fetch(PDO::FETCH_ASSOC)['id'];
        }
        return $this->lastInsertId();
    }

    /**
     * Gets column names for the specified table.
     *
     * @param string $table Name of the table to get columns from.
     *
     * @return array Array of column names.
     * @throws PDOException
     */
    public function columnList($table)
    {
        $columns = [];
        if ($this->type === 'mysql') {
            $list = $this->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($list as $column) {
                $columns[] = $column['Field'];
            }
        } else if ($this->type === 'pgsql') {
            $list = $this->query(
                "SELECT column_name FROM information_schema.columns WHERE table_name = '$table' and table_catalog = current_database();"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($list as $column) {
                $columns[] = $column['column_name'];
            }
        } else if ($this->type === 'sqlite') {
            $list = $this->query("PRAGMA table_info('$table')")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($list as $column) {
                $columns[] = $column['name'];
            }
        } else {
            throw new PDOException('Supports only: mysql, sqlite, pgsql');
        }
        return $columns;
    }

    /**
     * Executes a prepared statement query.
     *
     * @param string $sql    A prepared statement query.
     * @param array  $params Named parameters for the query.
     *
     * @return PDOStatement object.
     * @throws PDOException
     */
    public function execute($sql, array $params = [])
    {
        $query = $this->prepare($sql);
        if (!$query->execute($params)) {
            $error = $query->errorInfo();
            throw new PDOException($error[2].' in query: '.$sql, $error[0]);
        }
        return $query;
    }
}