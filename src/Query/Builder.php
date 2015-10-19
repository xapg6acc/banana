<?php
namespace Lebran\Banana\Query;

class Builder
{
    protected $db;

    protected $grammar;

    protected $distinct = false;

    protected $fields = ['*'];

    protected $tables;

    protected $joins;

    protected $wheres;

    protected $groups;

    protected $havings;

    protected $orders;

    protected $limit;

    protected $offset;

    protected $unions;

    public function __construct($db, $grammar)
    {
        $this->grammar = $grammar;
        $this->db      = $db;
    }

    /**
     * @return $this
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function fields()
    {
        $this->fields = [];
        foreach (func_get_args() as $field) {
            if ($field instanceof \Closure) {
                $field = new static($this->db, $this->grammar);
                call_user_func(($field->bindTo($field, $this)));
            }
            $this->fields[] = $field;
        }
        return $this;
    }

    /**
     * Sets the table to perform operations on, also supports
     *
     * @return mixed Returns self if a table is passed, otherwise returns the table
     */
    public function table()
    {
        foreach (func_get_args() as $table) {
            if ($table instanceof \Closure) {
                $table = $this->subQuery($table);
            }
            $this->tables[] = $table;
        }
        return $this;
    }

    public function join($table, $one = null, $operator = null, $two = null, $type = '')
    {
        $this->joins[] = compact('table', 'one', 'operator', 'two', 'type');
        return $this;
    }

    /**
     * @param        $field
     * @param null   $operator
     * @param mixed  $data
     * @param string $boolean
     *
     * @return $this
     */
    public function where($field, $operator = null, $data = null, $boolean = 'AND')
    {
        if ($field instanceof \Closure) {
            $field = $this->subQuery($field);
        }

        if ($data instanceof \Closure) {
            $data = $this->subQuery($data);
        }
        $this->wheres[] = compact('field', 'operator', 'data', 'boolean');
        return $this;
    }

    public function orWhere($field, $operator = null, $data = null)
    {
        return $this->where($field, $operator, $data, 'OR');
    }

    public function groupBy($groups)
    {
        $this->groups = func_get_args();
        return $this;
    }

    public function having()
    {
        // TODO having
    }

    public function orderBy($field)
    {
        // TODO order
    }

    public function limit($limit = 1)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function union()
    {
        //TODO union
    }

    public function get()
    {
        $sql = $this->grammar->buildSelect($this->getSelectParts());
        return $this->db->query($sql);
    }


    public function insert(array $values)
    {
        //        var_dump($this->buildInsert($values));
        return $this->db->query($this->buildInsert($values));
    }

    protected function buildInsert($values)
    {
        $query = 'INSERT INTO ';

        // from table

        foreach ($this->tables as $table) {
            $tables[] = $this->compile($table);
        }
        $query .= array_shift($tables);
        $query .= '('.implode(',', array_keys($values)).')';

        $values_new = array_map(
            function ($value) {
                return "'".$value."'";
            },
            array_values($values)
        );
        $query .= ' VALUES('.implode(',', array_values($values_new)).')';

        return $query;
    }

    public function update(array $values)
    {
        //                var_dump($this->buildUpdate($values));
        return $this->db->query($this->buildUpdate($values));
    }

    protected function buildUpdate(array $values)
    {
        //        UPDATE `users` SET `user_id`=[value-1],`login`=[value-2],`password`=[value-3] WHERE 1
        $query = 'UPDATE ';

        foreach ($this->tables as $table) {
            $tables[] = $this->compile($table);
        }
        $query .= array_shift($tables);
        $query .= ' SET ';

        foreach ($values as $key => $value) {
            $values_new[] = $key." = '".$value."'";
        }
        $query .= implode(',', $values_new);

        // where
        if ($this->where) {
            $query .= ' WHERE '.$this->buildWhereClause();
        }

        return $query;
    }

    protected function subQuery(\Closure $closure)
    {
        $object = new static($this->db, $this->grammar);
        call_user_func(($closure->bindTo($object, $this)));
        return $object;
    }

    public function getSelectParts()
    {
        return [
            'distinct' => $this->distinct,
            'fields'   => $this->fields,
            'tables'   => $this->tables,
            'joins'    => $this->joins,
            'wheres'   => $this->wheres,
            'groups'   => $this->groups,
            'havings'  => $this->havings,
            'orders'   => $this->orders,
            'limit'    => $this->limit,
            'offset'   => $this->offset,
            'unions'   => $this->unions
        ];
    }
}