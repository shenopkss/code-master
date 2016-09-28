#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
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
    $twig = new Twig_Environment($loader, array(
        // 'cache' => '/path/to/compilation_cache',
    ));
}

$function = new Twig_SimpleFunction('render', function($template, $data, $file) use ($twig, $filesystem) {
    $content = $twig->render($template, ['data' => $data]);
    $filesystem->put($file, $content);
});
$twig->addFunction($function);

$filter = new Twig_SimpleFilter('underscore', function ($string) {
    $string = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $string);
    return strtolower($string);
});
$twig->addFilter($filter);
$camelfilter = new Twig_SimpleFilter('camel', function ($string) {
    $string = preg_replace_callback(
        "/(_([a-z]))/",
        function($match){
            return strtoupper($match[2]);
        },
        $string
    );
    return preg_replace_callback(
        "/^\\w/",
        function($match){
            return strtoupper($match[0]);
        },
        $string
    );
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
    case 'TINYINT':
        $type = 'TINYINT';
        break;
    case 'SMALLINT':
        $type = 'SMALLINT';
        break;
    case 'MEDIUMINT':
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

$dbName = Capsule::connection()->getDatabaseName();
$db = new DB($dbName);
$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . Capsule::connection()->getDatabaseName() . "'";
$tb_objs = Capsule::select($query);
foreach($tb_objs as $tb_obj){
    $table = new Table($tb_obj);
    $columns = Capsule::select("select * from information_schema.COLUMNS where table_name = '" . $table->name . "' and TABLE_SCHEMA = '" . $dbName . "'");
    foreach ($columns  as $column_obj) {
        $column = new Column($column_obj);
        $table->columns[] = $column;
    }
    $fks = Capsule::select("select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME from INFORMATION_SCHEMA.KEY_COLUMN_USAGE where CONSTRAINT_NAME like 'fk_%' and table_name = '" . $table->name . "' and TABLE_SCHEMA ='" . Capsule::connection()->getDatabaseName() . "'");
    foreach ($fks as $fk) {
        $table->foreignKeys[] = $fk->REFERENCED_TABLE_NAME;
    }
    $db->tables[] = $table;
    $db->tables[$table->name] = $table;
}

print $twig->render($params, ['db' => $db]);
print "\n";
