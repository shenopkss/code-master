<?php

class DB{
    public $name;
    public $tables = [];
    public $commands = [];
    public $views = [];

    function __construct($name){
        $this->name = $name;
    }
}

class Table{
    public $name;
    public $db;
    public $primaryKey = [];
    public $columns = [];
    public $foreignKeys = [];

    function __construct($obj){
        $this->name = $obj->TABLE_NAME;
    }
}

class Command{}
class View{}

class Column{
    public $table;
    public $name;
    public $NonNull;
    public $type;
    public $isUnique;
    public $precision;
    public $scale;
    public $size;

    function __construct($obj){
        $this->name = $obj->COLUMN_NAME;
        $this->type = $obj->DATA_TYPE;
    }
}
