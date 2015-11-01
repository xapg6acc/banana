<?php
namespace Lebran\Banana;

use Lebran\Di\Container;
use Lebran\Banana\Query\Builder;
use Lebran\Banana\Query\Grammar;

class Provider extends Container
{
    /**
     * Initialize defaults banana service.
     *
     * @param array $configs
     *
     * @throws \Exception
     */
    public function register($configs)
    {
        $this->set(
            'db.connection',
            function () use ($configs) {
                return new Connection($configs);
            },
            true
        );

        $this->set(
            'db.grammar',
            function () use ($configs){
                $grammar = '\\Lebran\\Banana\\Query\\Grammar\\'.strtoupper($configs['type']);
                return class_exists($grammar)? new $grammar:  new Grammar;
            },
            true
        );

        $this->set(
            'db.qb',
            function () {
                /** @var Connection $connection */
                $connection = $this->get('db.connection');

                /** @var Grammar $grammar */
                $grammar = $this->get('db.grammar');
                return new Builder($connection, $grammar);
            }
        );
    }
}