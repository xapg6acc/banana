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
}