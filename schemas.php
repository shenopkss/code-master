<?php

class DB
{
    public $name;
    public $tables = [];
    public $tablesMap = [];
    public $commands = [];
    public $views = [];

    function __construct($name)
    {
        $this->name = $name;
    }
}

class Table
{
    public $name;
    public $db;
    public $primaryKey = [];
    public $columns = [];
    public $foreignKeys = [];
    public $noForeignKeys = [];

    function __construct($obj)
    {
        $name = $obj->TABLE_NAME;
        // if ($name[-1] == 's') {
        //     $name = substr($name, 0, strlen($name) - 1);
        // }else if ($name[-1] == 'es') {
        //     $name = substr($name, 0, strlen($name) - 2);
        // }
        $this->name = $name;
    }
}

class Command
{
}
class View
{
}

class Column
{
    public $table;
    public $referenceTable;
    public $name;
    public $comment;
    public $NonNull;
    public $type;
    public $subtype;
    public $isUnique;
    public $precision;
    public $scale;
    public $size;
}
