<?php
namespace Lebran\Banana\Query;

class Grammar
{
    protected static $select = [
        'distinct',
        'fields',
        'tables',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'unions'
    ];

    //protected static $operators = ['=', '!=', '>', '<', 'IN'];

    public function buildSelect(array $parts)
    {
        foreach (static::$select as $part) {
            if (!empty($parts[$part])) {
                $builtParts[] = $this->{'build'.ucfirst($part)}($parts[$part]);
            }
        }

        return 'SELECT '.implode(' ', $builtParts);
    }

    public function buildInsert()
    {

    }

    protected function buildDistinct($distinct)
    {
        return $distinct?'DISTINCT':'';
    }

    protected function buildFields($fields)
    {
        $fields = array_map([$this, 'buildField'], $fields);
        return implode(',', $fields);
    }

    protected function buildField($field)
    {
        switch (gettype($field)) {
            case 'string':
                return $this->quoteField($field);
                break;
            case 'array':
                return $this->buildAlias($this->buildField($field[0]), $this->quote($field[1]));
                break;
            case 'object':
                return $this->buildSelect($field->getSelectParts());
                break;
        }
    }

    protected function buildTables($tables)
    {
        $tables = array_map([$this, 'buildTable'], $tables);
        return 'FROM '.implode(',', $tables);
    }

    protected function buildTable($table)
    {
        switch (gettype($table)) {
            case 'string':
                return $this->quoteTable($table);
                break;
            case 'array':
                return $this->buildAlias($this->buildTable($table[0]), $this->quote($table[1]));
                break;
            case 'object':
                return '('.$this->buildSelect($table->getSelectParts()).')';
                break;
        }
    }

    protected function buildJoins($joins)
    {
        $joins = array_map([$this, 'buildJoin'], $joins);
        return implode(',', $joins);
    }

    protected function buildJoin($join)
    {
        $query = $join['type'].' JOIN ';
        if (!$join['one'] && !$join['two']) {
            $query = 'NATURAL'.$query;
            $query .= $this->quoteTable($join['table']);
        } else {
            $query .= $this->quoteTable($join['table']);
            if (!$join['two']) {
                $query .= ' USING('.$this->quoteField($join['one']).')';
            } else {
                $query .= ' ON('.$this->quoteField($join['one'])
                    .' '.$join['operator'].' '
                    .$this->quoteField($join['two']).')';
            }
        }
        return $query;
    }

    protected function buildWheres($wheres)
    {
        return 'WHERE '.$this->buildWheresNested($wheres);
    }

    protected function buildWheresNested($wheres)
    {
        $wheres[0]['boolean'] = null;
        return implode(' ', array_map([$this, 'buildWhere'], $wheres));
    }

    protected function buildWhere($where)
    {
        $query = '';
        if ($where['boolean'] && in_array(strtoupper($where['boolean']), ['OR', 'AND'])) {
            $query .= $where['boolean'].' ';
        }

        if (is_string($where['field'])) {
            $query .= $this->quoteField($where['field']).' ';
            //if (is_string($where['operator']) && in_array($where['operator'], self::$operators)) {
                $query .= $where['operator'].' ';
            //} else {
                // TODO operators
            //}

            switch (gettype($where['data'])) {
                case 'string':
                case 'integer':
                case 'double':
                    $query .= $this->quote($where['data']);
                    break;
                case 'null':
                    $query .= 'NULL';
                    break;
                case 'array':
                    if(strtoupper($where['operator']) === 'BETWEEN'){
                        $query .= $where['data'][0].' AND '.$where['data'][1];
                    } else {
                        $query .= '('.implode(',', $where['data']).')';
                    }
                    break;
                case 'object':
                    $query .= '('.$this->buildSelect($where['data']->getSelectParts()).')';
                    break;
                default:
                    throw new \InvalidArgumentException();
            }
        } else if (is_object($where['field'])) {
            $query .= '('.$this->buildWheresNested($where['field']->getSelectParts()['wheres']).')';
        } else {
            throw new \InvalidArgumentException();
        }
        // TODO check another types

        return $query;
    }

    protected function buildGroups($groups)
    {
        $groups = array_map([$this, 'buildField'], $groups);
        return 'GROUP BY '.implode(',', $groups);
    }

    protected function buildLimit($limit){
        return 'LIMIT '.$limit;
    }

    protected function buildOffset($offset){
        return 'OFFSET '.$offset;
    }

    protected function buildAlias($string, $alias)
    {
        return $string.' as '.$alias;
    }

    protected function quote($string)
    {
        return "'".$string."'";
    }

    protected function quoteField($string)
    {
        return ($string === '*')?$string:"`".$string."`";
    }

    protected function quoteTable($string)
    {
        return "`".$string."`";
    }
}