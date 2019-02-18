<?php
/**
 * Created by PhpStorm.
 * User: Ali
 * Date: 18/02/2019
 * Time: 03:13 PM
 */

require __DIR__.'/vendor/autoload.php';
$client = new \GuzzleHttp\Client([
    'base_uri' => 'http://localhost:8000',
    'defaults' => [
        'http_errors' => false
    ]
]);


$nickname = 'ObjectOrienter'.rand(0, 999);
$data = array(
    'nickname' => $nickname,
    'avatarNumber' => 5,
    'tagLine' => 'a test dev!'
);
// 1) Create a programmer resource
$response = $client->post('/api/programmers', [
    'body' => json_encode($data)
]);
$programmerUrl = $response->getHeader('Location');
// 2) GET a programmer resource
$response = $client->get($programmerUrl);


$response = $client->post('/api/sms');
echo $response->getBody();
echo "\n\n";