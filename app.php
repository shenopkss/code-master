#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

require_once 'database.php';
require_once 'schemas.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use League\Flysystem\Filesystem as Filesystem;
use League\Flysystem\Adapter\Local;

$filesystem = new Filesystem($adapter = new Local('./src'));
$params = $argv[1];
$twig = (object)null;
if(strpos($params, '.twig') === false){
    $twig = new \Twig_Environment(new \Twig_Loader_String());
}else{
    $loader = new Twig_Loader_Filesystem('./templates');
    $twig = new Twig_Environment($loader, ['cache' => false]);
}

$function = new Twig_SimpleFunction('render', function($template, $data, $file) use ($twig, $filesystem) {
    $content = $twig->render($template, $data);
    $filesystem->put($file, $content);
});
$twig->addFunction($function);

$filter = new Twig_SimpleFilter('underscore', function ($string) {
    $string = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $string);
    return strtolower($string);
});
$twig->addFilter($filter);
$camelfilter = new Twig_SimpleFilter('camel', function ($string) {
    $result = '';
    $arr = explode('_', $string);
    foreach ($arr as $str) {
        if(strlen($str) > 2){
            $result .= strtoupper($str[0]) . substr($str, 1, strlen($str) - 1 );
        }else{
            $result .= strtoupper($str);
        }
    }

    return $result;
});
$twig->addFilter($camelfilter);

$hiveFilter = new Twig_SimpleFilter('hive', function ($string) {
    $type = '';
    switch(strtoupper($string)){
    case 'CHAR':
    case 'VARCHAR':
    case 'TINYTEXT':
    case 'TEXT':
    case 'MEDIUMTEXT':
    case 'LONGTEXT':
    case 'ENUM':
    case 'DATETIME':
    case 'TIME':
    case 'YEAR':
        $type = 'STRING';
        break;
    // case 'TINYINT':
        // $type = 'TINYINT';
        // break;
    case 'SMALLINT':
        $type = 'SMALLINT';
        break;
    case 'MEDIUMINT':
    case 'TINYINT':
    case 'INT':
    case 'TIMESTAMP':
        $type = 'INT';
        break;
    case 'BIGINT':
        $type = 'BIGINT';
        break;
    case 'FLOAT':
        $type = 'FLOAT';
        break;
    case 'DOUBLE':
    case 'DECIMAL':
        $type = 'DOUBLE';
        break;
    case 'DATE':
        $type = 'DATE';
        break;
    }
    return $type;
});
$twig->addFilter($hiveFilter);

$test_valueFilter = new Twig_SimpleFilter('test_value', function ($column) {
    $value = '';
    switch(strtoupper($column->type)){
    case 'CHAR':
    case 'VARCHAR':
    case 'TINYTEXT':
    case 'TEXT':
    case 'MEDIUMTEXT':
    case 'LONGTEXT':
    case 'ENUM':
    case 'DATETIME':
    case 'TIME':
    case 'YEAR':
        $value = $column->name . '0';
        break;
    case 'TINYINT':
    case 'SMALLINT':
    case 'MEDIUMINT':
    case 'INT':
    case 'TIMESTAMP':
    case 'BIGINT':
    case 'FLOAT':
    case 'DOUBLE':
    case 'DECIMAL':
        $value = 1;
        break;
    case 'DATE':
        $value = '2019-04-26 00:00:00';
        break;
    }
    return $value;
});
$twig->addFilter($test_valueFilter);

$dbName = Capsule::connection()->getDatabaseName();
$db = new DB($dbName);
$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . Capsule::connection()->getDatabaseName() . "'";
$tb_objs = Capsule::select($query);
foreach($tb_objs as $tb_obj){
    $table = new Table($tb_obj);
    $columns = Capsule::select("select COLUMN_NAME,DATA_TYPE,CHARACTER_MAXIMUM_LENGTH,COLUMN_COMMENT from information_schema.COLUMNS where table_name = '" . $table->name . "' and TABLE_SCHEMA = '" . $dbName . "'");
    foreach ($columns  as $column_obj) {
        if(in_array($column_obj->COLUMN_NAME, ['id', 'updated', 'created', 'updated_at', 'created_at', 'deleted_at'])){
            continue;
        }
        $column = new Column();
        $column->name = $column_obj->COLUMN_NAME;
        $column->comment = $column_obj->COLUMN_COMMENT;
        $column->type = $column_obj->DATA_TYPE;
        $column->size = intval($column_obj->CHARACTER_MAXIMUM_LENGTH);
        $column->table = $table;

        $table->columns[] = $column;
        $table->db = $db;
    }

    $pk = Capsule::select("select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME from INFORMATION_SCHEMA.KEY_COLUMN_USAGE where CONSTRAINT_NAME like 'PRIMARY' and table_name = '" . $table->name . "' and TABLE_SCHEMA ='" . Capsule::connection()->getDatabaseName() . "' limit 1");
    $table->primaryKey = $pk[0]->COLUMN_NAME;

    $db->tables[] = $table;
    $db->tablesMap[$table->name] = $table;
}
foreach($db->tables as &$table){
    $fks = Capsule::select("select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME from INFORMATION_SCHEMA.KEY_COLUMN_USAGE where CONSTRAINT_NAME like 'fk_%' and table_name = '" . $table->name . "' and TABLE_SCHEMA ='" . Capsule::connection()->getDatabaseName() . "'");
    foreach ($fks as $fk) {
        foreach ($table->columns as $column) {
            if($fk->COLUMN_NAME == $column->name){
                $column->referenceTable = $db->tablesMap[$fk->REFERENCED_TABLE_NAME];
                $table->foreignKeys[] = clone $column;
            }
        }
    }
    foreach ($table->columns as $column) {
        $isFk = false;
        foreach ($table->foreignKeys as $fk) {
            if($column->name == $fk->name){
                $isFk = true;
                break;
            }
        }
        if($isFk === false){
            $table->noForeignKeys[] = clone $column;
        }
    }


    $refs = Capsule::select("select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME from INFORMATION_SCHEMA.KEY_COLUMN_USAGE where CONSTRAINT_NAME like 'fk_%' and REFERENCED_TABLE_NAME = '" . $table->name . "' and TABLE_SCHEMA ='" . Capsule::connection()->getDatabaseName() . "'");
    $table->refTables = [];
    foreach($refs as $ref){
        $table->refTables[] = $db->tablesMap[$ref->TABLE_NAME];
    }
}

$twig->render($params, ['db' => $db]);
