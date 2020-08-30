#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';

$entry = $argv[1];
$database = $argv[2];

$env = ".env.$database";
$dotenv = Dotenv\Dotenv::create(__DIR__, $env);
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;
use League\Flysystem\Filesystem as Filesystem;
use League\Flysystem\Adapter\Local;

require_once 'database.php';
require_once 'schemas.php';

$filesystem = new Filesystem($adapter = new Local('./src'));
$twig = (object) null;

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false
]);

$function = new \Twig\TwigFunction('render', function ($template, $data, $file) use ($twig, $filesystem) {
    $content = $twig->render($template, $data);
    $filesystem->put($file, $content);
});
$twig->addFunction($function);

$filter = new \Twig\TwigFilter('underscore', function ($string) {
    $string = preg_replace('/(?<=\\w)(?=[A-Z])/', "_$1", $string);
    return strtolower($string);
});
$twig->addFilter($filter);
$camelfilter = new \Twig\TwigFilter('camel', function ($string) {
    //复数形式改成单数
    if ($string[-1] == 's') {
        $string = substr($string, 0, strlen($string) - 1);
    } else if ($string[-1] == 'es') {
        $string = substr($string, 0, strlen($string) - 2);
    }

    $result = '';
    $arr = explode('_', $string);
    foreach ($arr as $str) {
        if (strlen($str) > 2) {
            $result .= strtoupper($str[0]) . substr($str, 1, strlen($str) - 1);
        } else {
            $result .= strtoupper($str);
        }
    }

    return $result;
});
$twig->addFilter($camelfilter);

$lcamelfilter = new \Twig\TwigFilter('lcamel', function ($string) {
    $result = '';
    $arr = explode('_', $string);
    foreach ($arr as $index => $str) {
        if ($index == 0) {
            $result .= strtolower($str);
            continue;
        }
        if (strlen($str) > 2) {
            $result .= strtoupper($str[0]) . substr($str, 1, strlen($str) - 1);
        } else {
            $result .= strtoupper($str);
        }
    }

    return $result;
});

$twig->addFilter($lcamelfilter);

// $hiveFilter = new \Twig\TwigFilter('hive', function ($string) {
//     $type = '';
//     switch (strtoupper($string)) {
//         case 'CHAR':
//         case 'VARCHAR':
//         case 'TINYTEXT':
//         case 'TEXT':
//         case 'MEDIUMTEXT':
//         case 'LONGTEXT':
//             // case 'Enum':
//             // case 'DATETIME':
//             // case 'TIME':
//             // case 'YEAR':
//             $type = 'STRING';
//             break;
//             // case 'TINYINT':
//             // $type = 'TINYINT';
//             // break;
//         case 'SMALLINT':
//             $type = 'SMALLINT';
//             break;
//         case 'MEDIUMINT':
//         case 'TINYINT':
//         case 'INT':
//         case 'TIMESTAMP':
//             $type = 'INT';
//             break;
//         case 'BIGINT':
//             $type = 'BIGINT';
//             break;
//         case 'FLOAT':
//             $type = 'FLOAT';
//             break;
//         case 'DOUBLE':
//         case 'DECIMAL':
//             $type = 'DOUBLE';
//             break;
//         case 'DATE':
//             $type = 'DATE';
//             break;
//     }
//     return $type;
// });
// $twig->addFilter($hiveFilter);

$test_valueFilter = new \Twig\TwigFilter('test_value', function ($column) {
    $value = '';
    switch ($column->type) {
        case 'Int':
        case 'Long':
        case 'Float':
        case 'Double':
            $value = 1;
            break;
        case 'String':
            $value = '"' . $column->name . '0' .  '"';
            break;
        case 'List':
            $value = 'listOf(' . '"' . $column->name . '0' .  '"' . ')';
            break;
        case 'JsonObject':
            $value = 'JsonObject()';
            break;
        case 'Boolean':
            $value = "true";
            break;
        case 'Enum':
            $value = $column->subtype . '.values()[0]';
            break;
        case 'Date':
            $value = 'Date()';
            break;
        default:
            $value = $column->type;
    }
    return $value;
});
$twig->addFilter($test_valueFilter);

$default_value = new \Twig\TwigFilter('default_value', function ($column) {
    $value = '';
    switch ($column->type) {
        case 'Int':
        case 'Long':
        case 'Float':
        case 'Double':
            $value = 0;
            break;
        case 'String':
            $value = '""';
            break;
        case 'List':
            $value = 'listOf()';
            break;
        case 'JsonObject':
            $value = 'JsonObject()';
            break;
        case 'Boolean':
            $value = "true";
            break;
        case 'Enum':
            $value = $column->subtype . '.values()[0]';
            break;
        case 'Date':
            $value = 'Date()';
            break;
        default:
            $value = "";
    }
    return $value;
});
$twig->addFilter($default_value);

$twig->addFilter(new \Twig\TwigFilter('cast', function ($column) {
    $value = '';
    switch ($column->type) {
        case 'Boolean':
            $value = 'boolean';
            break;
        case 'Int':
            $value = 'integer';
            break;
        case 'Long':
            $value = 'bigint';
            break;
        case 'Date':
        case 'Datetime':
            $value = 'datetime';
            break;
        case 'Float':
            $value = 'float';
            break;
        case 'Double':
            $value = 'double';
            break;
        case 'Decimal':
            $value = 'decimal';
            break;
        case 'Json':
            $value = 'array';
            break;
        default:
            $value = 'string';
    }
    return $value;
}));

$twig->addFilter(new \Twig\TwigFilter('json_type', function ($column) {
    switch ($column->type) {
        case 'Int':
            $value = 'Integer';
            break;
        case 'List':
            $value = 'JsonArray';
            break;
        case 'Enum':
            $value = 'String';
            break;
        case 'JsonObject':
            $value = 'JsonObject';
            break;
        default:
            $value = $column->type;
    }
    return $value;
}));

$twig->addFunction(new \Twig\TwigFunction('env', function ($key) {
    return getenv($key);
}));

$connection = 'default';
$conn = Capsule::connection($connection);;
$dbName = $conn->getDatabaseName();
$db = new DB($dbName);

$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $dbName . "'";

$tb_objs = $conn->select($query);

$tables = getenv('TABLES');

foreach ($tb_objs as $tb_obj) {
    if ($tables != false) {
        $target = explode(',', $tables);
        if (!in_array($tb_obj->TABLE_NAME, $target)) {
            continue;
        }
    }
    $table = new Table($tb_obj);
    $columns = $conn->select("select COLUMN_NAME,DATA_TYPE,CHARACTER_MAXIMUM_LENGTH,COLUMN_COMMENT from information_schema.COLUMNS where table_name = '" . $table->name . "' and TABLE_SCHEMA = '" . $dbName . "' order by COLUMN_NAME");
    foreach ($columns as $column_obj) {
        if (in_array($column_obj->COLUMN_NAME, ['id', 'updated', 'created', 'updated_at', 'created_at', 'deleted_at'])) {
            continue;
        }
        $type = '';
        $subtype = '';
        if (!empty($column_obj->COLUMN_COMMENT)) {
            $arr = explode(',', $column_obj->COLUMN_COMMENT);

            if (count($arr) > 0) {
                $t = explode(':', $arr[0]);

                if (count($t) == 2) {
                    $type = $t[0];
                    $subtype = $t[1];
                }
            }
        }
        if ($type == '') {
            switch (strtoupper($column_obj->DATA_TYPE)) {
                case 'TINYINT':
                    $type = 'Boolean';
                    break;
                case 'SMALLINT':
                case 'MEDIUMINT':
                case 'INT':
                    $type = 'Int';
                    break;
                case 'BIGINT':
                    $type = 'Long';
                    break;
                case 'DATE':
                case 'DATETIME':
                    $type = 'Date';
                    break;
                case 'FLOAT':
                    $type = 'Float';
                    break;
                case 'DOUBLE':
                    $type = 'Double';
                    break;
                case 'DECIMAL':
                    $type = 'Float';
                    break;
                case 'JSON':
                    $type = 'Json';
                    break;
                default:
                    $type = 'String';
            }
        }
        $column = new Column();
        $column->name = $column_obj->COLUMN_NAME;
        $column->comment = $column_obj->COLUMN_COMMENT;
        $column->type = $type;
        $column->subtype = $subtype;
        $column->size = intval($column_obj->CHARACTER_MAXIMUM_LENGTH);
        $column->table = $table;

        $table->columns[] = $column;
        $table->db = $db;
    }

    $pk = $conn->select("select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME from INFORMATION_SCHEMA.KEY_COLUMN_USAGE where CONSTRAINT_NAME = 'PRIMARY' and table_name = '" . $table->name . "' and TABLE_SCHEMA ='" .  $dbName . "' limit 1");
    $table->primaryKey = count($pk) > 0 ? $pk[0]->COLUMN_NAME : 'id';

    $db->tables[] = $table;
    $db->tablesMap[$table->name] = $table;
}

foreach ($db->tables as &$table) {
    $sql = "select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME from INFORMATION_SCHEMA.KEY_COLUMN_USAGE where CONSTRAINT_NAME != 'PRIMARY' and table_name = '" . $table->name . "' and REFERENCED_TABLE_NAME is not null and TABLE_SCHEMA ='" . $dbName . "'";
    $fks = $conn->select($sql);
    foreach ($fks as $fk) {
        foreach ($table->columns as $column) {
            if ($fk->COLUMN_NAME == $column->name) {
                $column->referenceTable = $db->tablesMap[$fk->REFERENCED_TABLE_NAME];
                $table->foreignKeys[] = clone $column;
            }
        }
    }
    foreach ($table->columns as $column) {
        $isFk = false;
        foreach ($table->foreignKeys as $fk) {
            if ($column->name == $fk->name) {
                $isFk = true;
                break;
            }
        }
        if ($isFk === false) {
            $table->noForeignKeys[] = clone $column;
        }
    }


    $refs = $conn->select("select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME from INFORMATION_SCHEMA.KEY_COLUMN_USAGE where CONSTRAINT_NAME != 'PRIMARY' and REFERENCED_TABLE_NAME = '" . $table->name . "' and table_name is not null and TABLE_SCHEMA ='" . $dbName . "'");

    $table->refTables = [];
    foreach ($refs as $ref) {
        $table->refTables[] = $db->tablesMap[$ref->TABLE_NAME];
    }
}
foreach ($db->tables as &$table) {
    $table->realname = $table->name;
    $table->name = str_replace(getenv('PREFIX'), '', $table->name);

    // if($table->name == 'course'){
    //     foreach($table->columns as $c){
    //         var_dump($c->name, $c->type);
    //     }
    //     exit;
    // }
}

// foreach($db->tables as &$table){
//     if($table->name =='tb_platform_channel'){
//         var_dump($table->refTables);
//         exit();
//     }
// }

$twig->render($entry, ['db' => $db]);
