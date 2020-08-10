<?php
require 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
//use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
$capsule = new Capsule;
$capsule->addConnection(array(
    'driver'    => 'mysql',
    'host'      => getenv('DB_HOST'),
    'database'  => getenv('DB_DATABASE'),
    'username'  => getenv('DB_USERNAME'),
    'password'  => getenv('DB_PASSWORD'),
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => ''
), "honghu");

// $capsule->addConnection(array(
//     'driver'    => 'mysql',
//     'host'      => getenv('MOSHEN_ZQ_DB_HOST'),
//     'database'  => getenv('MOSHEN_ZQ_DB_DATABASE'),
//     'username'  => getenv('MOSHEN_ZQ_DB_USERNAME'),
//     'password'  => getenv('MOSHEN_ZQ_DB_PASSWORD'),
//     'charset'   => 'utf8',
//     'collation' => 'utf8_unicode_ci',
//     'prefix'    => ''
// ), "moshen_zq");

//$capsule->setEventDispatcher(new Dispatcher(new Container));
// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();
// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();
