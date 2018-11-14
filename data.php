<?php
    require 'vendor/autoload.php';
    use Resume\Response;
 
    $json = file_get_contents('php://input');
    $params = json_decode($json);
    $response = new Response($params);
    echo $response->Get();
