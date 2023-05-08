<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Url;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Config;
use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Http\Response\Cookies;
use Phalcon\Logger;
use date\Date;
use Phalcon\Cache;
use Phalcon\Cache\Adapter\Redis;
use Phalcon\Storage\SerializerFactory;

$config = new Config([]);

// Define some absolute path constants to aid in locating resources
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Register an autoloader
$loader = new Loader();
require_once BASE_PATH.'/vendor/autoload.php';

$loader->registerDirs(
    [
        APP_PATH . "/controllers/",
        APP_PATH . "/models/",
    ]
);

$loader->registerNamespaces(
    [
        "date" => APP_PATH . "/assets/",
    ]
);

$loader->registerClasses([
    "Myescaper" => APP_PATH . '/component/Myescaper.php'
]);

$loader->register();
$container = new FactoryDefault();

$container->set(
    'cache',
    function () {
        $serilaizefactory = new SerializerFactory();
        $options = [
            'host' => 'redis',
            'post' => 6379,
            'auth' => '',
            'persistant' => false,
            'defaultSerializer' => 'Php'
        ];
        $adapter = new Redis($serilaizefactory, $options);
        $cache = new Cache($adapter);
        return $cache;
    }
);


$container->set(
    'view',
    function () {
        $view = new View();
        $view->setViewsDir(APP_PATH . '/views/');
        return $view;
    }
);

$container->set(
    'url',
    function () {
        $url = new Url();
        $url->setBaseUri('/');
        return $url;
    }
);

$container->set(
    'session',
    function () {
        $session = new Manager();
        $files = new Stream(
            [
                'savePath' => '/tmp',
            ]
        );

        $session
            ->setAdapter($files)
            ->start();

        return $session;
    }
);

$container->set(
    'cookies',
    function () {
        $cookies = new Cookies();
        $cookies->useEncryption(false);
        return $cookies;
    }
);

$container->set(
    "date",
    function () {
        return new Date();
    }
);

$container->set(
    'db',
    function () {
        return new Mysql(
            [
                'host'     => 'mysql-server',
                'username' => 'root',
                'password' => 'secret',
                'dbname'   => 'phalconApp',
            ]
        );
    }
);

$container->set(
    'mongo',
    function () {
        $mongo = new MongoClient();

        return $mongo->selectDB('phalt');
    },
    true
);

$container->set(
    'logger',
    function () {
        $login = new  Phalcon\Logger\Adapter\Stream(APP_PATH . '/logs/login.log');
        $signup = new  Phalcon\Logger\Adapter\Stream(APP_PATH . '/logs/signup.log');
        $logger  = new Logger(
            'messages',
            [
                'login' => $login,
                'signup' => $signup
            ]
        );
        return $logger;
    }
);

$application = new Application($container);

try {
    // Handle the request
    $response = $application->handle(
        $_SERVER["REQUEST_URI"]
    );

    $response->send();
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
}
