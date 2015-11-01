<?php
namespace Lebran\Banana\Orm;

use Lebran\Di\InjectableTrait;
use Lebran\Di\InjectableInterface;

/**
 * ORM allows you to access database items and their relationships in an OOP manner,
 * it is easy to setup and makes a lot of use of naming convention.
 *
 * @method mixed limit(int $limit = null) Set number of rows to return .
 *               If NULL is passed than no limit is used.
 *               Without arguments returns current limit, returns self otherwise.
 *
 * @method mixed offset(int $offset = null) Set the offset for the first row in result.
 *               If NULL is passed than no limit is used.
 *               Without arguments returns current offset, returns self otherwise.
 *
 * @method mixed order_by(string $column, string $dir) Adds a column to ordering parameters
 *
 * @method mixed where(mixed $key, mixed $operator = null, mixed $val = null) behaves just like Query_Database::where()
 *
 * @see     Query_Database::where()
 * @package ORM
 */
class Model implements InjectableInterface
{
    use InjectableTrait;

    /**
     * Specifies which column is treated as PRIMARY KEY
     *
     * @var string
     */
    protected $id = 'id';

    protected $table;

    protected $row;

    /**
     * @var
     */
    protected $query;

    protected $loaded;

    final public function __construct($di)
    {
        //var_dump($di);
        $this->di = $di;
        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }

        $this->query = $this->di->get('db.qb')->table($this->table);

        if ($this->table === null) {
            //            $this->table = str_replace("\\", '_', $this->model_name);
            //            $this->table = $this->plural($this->table);
        }
        /*foreach (array('belongs_to', 'has_one', 'has_many') as $rels)
        {
            $normalized = array();
            foreach ($this->$rels as $key => $rel)
            {
                if (!is_array($rel))
                {
                    $key = $rel;
                    $rel = array();
                }
                $normalized[$key] = $rel;
                if (!isset($rel['model']))
                {
                    $rel['model'] = $normalized[$key]['model'] = $rels == 'has_many' ? $this->singular($key) : $key;
                }

                $normalized[$key]['type'] = $rels;
                if (!isset($rel['key']))
                {
                    $normalized[$key]['key'] = $this->model_key( $rels != 'belongs_to' ? $this->model_name : $rel['model']);
                }

                if ($rels == 'has_many' && isset($rel['through']))
                {
                    if (!isset($rel['foreign_key']))
                    {
                        $normalized[$key]['foreign_key'] = $this->model_key($rel['model']);
                    }
                }

                $normalized[$key]['name'] = $key;
            }
            $this->$rels = $normalized;
        }*/
    }

    /**
     * Magic method for call Query_Database methods
     *
     * @param string $method    Method to call
     * @param array  $arguments Arguments passed to the method
     *
     * @return mixed  Returns self if parameters were passed. If no parameters where passed returns
     *                current value for the associated parameter
     * @throws \Exception If method doesn't exist
     */
    public function __call($method, $arguments)
    {
        if (!in_array($method, ['limit', 'offset', 'orderBy', 'where'])) {
            throw new \Exception("Method '{$method}' doesn't exist on .".get_class($this));
        }
        call_user_func_array(array($this->query, $method), $arguments);
        return $this;
    }

    /**
     * Finds all rows that meet set criteria.
     *
     * @return \PHPixie\ORM\Result Returns ORM Result that you can use in a 'foreach' loop.
     */
    public function findAll()
    {
        //$paths = $this->prepare_relations();
        $objects = $this->query->get()->fetchAll(\PDO::FETCH_CLASS, static::class, [$this->di]);
        return array_map(
            function ($item) {
                return $item->loaded(true);
            },
            $objects
        );
    }

    /**
     * Searches for the first row that meets set criteria. If no rows match it still returns an ORM model
     * but with its loaded() flag being False. calling save() on such an object will insert a new row.
     *
     * @return \PHPixie\ORM\Model Found item or new object of the current model but with loaded() flag being False
     */
    public function find()
    {
        return $this->query->get()->fetchObject(static::class, [$this->di])->loaded(true);
    }

    /**
     * Checks if the item is considered to be loaded from the database
     *
     * @return boolean Returns True if the item was loaded
     */
    public function loaded($loaded = null)
    {
        if (null === $loaded) {
            return $this->loaded;
        } else {
            $this->loaded = $loaded;
            return $this;
        }
    }

    /**
     * Magic method that allows accessing row columns and extensions as properties and also facilitates
     * access to relationships and custom properties defined in get() method.
     * If a relationship is being accessed, it will return an ORM model of the related table
     * and automatically alter its query so that all your previously set conditions will remain
     *
     * @param string $column Name of the column, property or relationship to get
     *
     * @return mixed   Requested property
     * @throws \Exception If neither property nor a relationship with such name is found
     */
    public function __get($column)
    {
        if (array_key_exists($column, $this->row)) {
            return $this->row[$column];
        }

        //        if (array_key_exists($column, $this->cached))
        //            return $this->cached[$column];

        //        if (($val = $this->get($column)) !== null) {
        //            $this->cached[$column] = $val;
        //            return $val;
        //        }

        //$relations = array_merge($this->has_one, $this->has_many, $this->belongs_to);
        //        if ($target = $this->pixie->arr($relations, $column, false)) {
        //            $model        = $this->pixie->orm->get($target['model']);
        //            $model->query = clone $this->query;
        //            if ($this->loaded()) {
        //                $model->query->where($this->id_field, $this->_row[$this->id_field]);
        //            }
        //            if ($target['type'] == 'has_many' && isset($target['through'])) {
        //                $last_alias    = $model->query->last_alias();
        //                $through_alias = $model->query->add_alias();
        //                $new_alias     = $model->query->add_alias();
        //                $model->query->join(
        //                    array($target['through'], $through_alias),
        //                    array(
        //                        $last_alias.'.'.$this->id_field,
        //                        $through_alias.'.'.$target['key'],
        //                    ),
        //                    'inner'
        //                );
        //                $model->query->join(
        //                    array($model->table, $new_alias),
        //                    array(
        //                        $through_alias.'.'.$target['foreign_key'],
        //                        $new_alias.'.'.$model->id_field,
        //                    ),
        //                    'inner'
        //                );
        //            } else {
        //                $last_alias = $model->query->last_alias();
        //                $new_alias  = $model->query->add_alias();
        //                if ($target['type'] == 'belongs_to') {
        //                    $model->query->join(
        //                        array($model->table, $new_alias),
        //                        array(
        //                            $last_alias.'.'.$target['key'],
        //                            $new_alias.'.'.$model->id_field,
        //                        ),
        //                        'inner'
        //                    );
        //                } else {
        //                    $model->query->join(
        //                        array($model->table, $new_alias),
        //                        array(
        //                            $last_alias.'.'.$this->id_field,
        //                            $new_alias.'.'.$target['key'],
        //                        ),
        //                        'inner'
        //                    );
        //                }
        //            }
        //            $model->query->fields("$new_alias.*");
        //            if ($target['type'] != 'has_many' && $this->loaded()) {
        //                $model                 = $model->find();
        //                $this->cached[$column] = $model;
        //            }
        //            return $model;
        //        }

        //        throw new \Exception("Property {$column} not found on {$this->model_name} model.");
    }

    /**
     * Magic method to update record values when set as properties or to add an ORM item to
     * a relation. By assigning an ORM model to a relationship a relationship is created between the
     * current item and the passed one  Using properties this way is a shortcut to the add() method.
     *
     * @param string $column Column or relationship name
     * @param mixed  $value  Column value or an ORM model to be added to a relation
     *
     * @return void
     * @see add()
     */
    public function __set($column, $value)
    {
        //$relations = array_merge($this->has_one, $this->has_many, $this->belongs_to);
        //        if (array_key_exists($column, $relations))
        //        {
        //            $this->add($column, $val);
        //        }
        //        else
        //        {
        $this->row[$column] = $value;
        //        }
        //        $this->cached = array();
    }

    /**
     * Saves the item back to the database. If item is loaded() it will result
     * in an update, otherwise a new row will be inserted
     *
     * @return \PHPixie\ORM\Model Returns self
     */
    public function save()
    {
        $query = $this->di->get('db.qb')
                          ->table($this->table);
        if ($this->loaded()) {
            $query->where($this->id, '=', $this->row[$this->id])
                  ->update($this->row);
        } else {
            $query->insert($this->row);
            $this->loaded = true;
        }

        /*if ($this->loaded()) {
            $id = $this->row[$this->id];
        } else {
            //$id = $this->conn->insert_id();
        }
        $row = $this->di->get('qb')
            ->table($this->table)
            ->where($this->id, $id)
            ->get()
            ->fetch(\PDO::FETCH_ASSOC);
        $this->values($row, true);*/
        return $this;
    }

    /**
     * Batch updates item columns using an associative array
     *
     * @param array   $row        Associative array of key => value pairs
     * @param boolean $loaded     Flag to consider the ORM item loaded. Useful if you selected
     *                            the row from the database and want to wrap it in ORM
     *
     * @return \PHPixie\ORM\Model Returns self
     */
    public function values($row, $loaded = false)
    {
        $this->row = array_merge($this->row, $row);
        if ($loaded) {
            $this->loaded = true;
        }
        //$this->cached = array();
        return $this;
    }
}