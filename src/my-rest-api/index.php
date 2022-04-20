<?php


use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use MongoDB\BSON\ObjectId;
use Phalcon\Http\Response;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


require_once "../vendor/autoload.php";
$loader = new Loader();
$loader->registerNamespaces(
    [
        'MyApp\Models' => __DIR__ . '/models/',
    ]
);
$loader->register();
// echo __DIR__;
$container = new FactoryDefault();
$container->set(
    'mongo',
    function () {
        $mongo = new \MongoDB\Client("mongodb://mongo", array("username" => 'root', "password" => "password123"));
        // mongo "mongodb+srv://sandbox.g819z.mongodb.net/myFirstDatabase" --username root

        return $mongo;
    },
    true
);

$app = new Micro($container);

$app->get(
    '/products/get',
    function () use ($app) {
        $header = $app->request->getHeaders();
        if (array_key_exists('Bearer', $header)) {
            $token = $header['Bearer'];
            $key = "example_key";
            $token = JWT::decode($token, new Key($key, 'HS256'));
            if ($token->role == 'admin') {
                $result = "";
                $products = $app->mongo->api->products->find();
        
                foreach ($products as $p) {
        
                    $result .= json_encode($p);
                }
                echo $result;

            } else {
                echo "Access Denied";
            }

        } else {
            echo "Access token not passed";
        }
    }
);
$app->get(
    '/gettoken/role/{role}',
    function ($role) use ($app) {
        // echo "hello";
        // echo $role;
        $key = "example_key";
        $payload = array(
            "iss" => "http://example.org",
            "aud" => "http://example.com",
            "iat" => 1356999524,
            "nbf" => 1357000000,
            "role" => $role
        );
        $jwt = JWT::encode($payload, $key, 'HS256');
        echo $jwt;
    }
);
$app->get(
    '/api/products/search/{name}',
    function ($name) use ($app) {
        $limit = $app->request->getQuery('limit');
        if ($limit != '') {
            
            $limit = intval($limit);
        } else {
            
            $limit = -1;
        }
        // echo $limit;
        $header = $app->request->getHeaders();
        if (array_key_exists('Bearer', $header)) {

            $token = $header['Bearer'];
            $key = "example_key";
            $token = JWT::decode($token, new Key($key, 'HS256'));
            if ($token->role == 'admin') {
    
                $name = urldecode($name);
                $array = explode(" ", $name);
                print_r($array);
                $result = '';
                foreach ($array as $val) {
                   
                    $product = $app->mongo->api->products->find(['$or' => [['name' => $val], ['variation' => $val]]]);
                    echo "<pre>";
                    foreach ($product as $p) {
        
                        // print_r($p);
                        if($limit > 0 || $limit === -1 )
                        $result .= json_encode($p);
                        if($limit > 0)
                        $limit--;
                        
                    }
                }
                echo $result;
            } else {
                echo "Access Denied";
            }
        } else {
            echo "Access Token Not Passed";
        }

    }
);




$app->post(
    '/api/products',
    function () use ($app) {
        // $robot = $app->request->getJsonRawBody();
        $product = $app->request->getPost();
        echo "<pre>";
        print_r($product);
        $result = $app->mongo->api->products->insertOne(['name' => $product['name'], "variation" => $product['variation'], "price" => $product['price'], "stock" => $product['stock']]);
        print_r($result);
    }
);

$app->get(
    '/auth/product',
    function () use ($app) {
        echo "<pre>";
        $header = $app->request->getHeaders();
        // print_r($header);
        $token = $header['Bearer'];
        $key = "example_key";
        $token = JWT::decode($token, new Key($key, 'HS256'));
        print_r($token);
        
       
    }
);



$app->handle(
    $_SERVER["REQUEST_URI"]
);
// echo $_SERVER["REQUEST_URI"];