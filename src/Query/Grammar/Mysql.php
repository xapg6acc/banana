<?php
namespace Lebran\Banana\Query\Grammar;

use \Lebran\Banana\Query\Grammar;

class Mysql extends Grammar
{
    protected static $delete = [
        'fields',
        'tables',
        'joins',
        'wheres',
        'orders',
        'limit'
    ];

    protected static $update = [
        'sets',
        'wheres',
        'orders',
        'limit'
    ];

    protected function quoteField($string)
    {
        if(false !== strpos($string, '.')){
            return implode('.',array_map([$this, 'quoteField'], explode('.', $string)));
        } else {
            return ($string === '*' || is_int($string))?$string:'`'.$string.'`';
        }
    }

    protected function quoteTable($string)
    {
        return '`'.$string.'`';
    }

    public function buildUpdate(array $parts)
    {
        $query = 'UPDATE '.implode(',', array_map([$this, 'buildTable'], $parts['tables']));
        return $query.$this->buildParts(static::$update, $parts);
    }

    protected function buildSets($values)
    {
        $sets = [];
        foreach ($values as $key => $value){
            if(strripos($key, ',') !== false) {
                $keys = array_fill_keys(explode(',', $key), $value);
                foreach ($keys as $k => $v){
                    $sets[] = $this->buildSet($k, $value);
                }
            }else{
                $sets[] = $this->buildSet($key, $value);
            }
        }
        return ' SET '.implode(', ', $sets);
    }
}