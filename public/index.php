<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$companies = [
['id' => '1', 'name' => 'Walmart'],
['id' => '2', 'name' => 'Amazon'],
['id' => '3', 'name' => 'Apple Inc.'],
['id' => '4', 'name' => 'CVS Health'],
['id' => '5', 'name' => 'ExxonMobil'],
['id' => '6', 'name' => 'UnitedHealth Group'],
['id' => '7', 'name' => 'Berkshire Hathaway']
];

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});

$app->get('/companies/{id}', function ($request, $response, array $args) use ($companies) {
    $id = $args['id'];
    $collection = collect($companies)->firstWhere('id', $id);
    
    if($collection) {
        return $response->write(json_encode($collection));
    } else {
        $response2 = $response->withStatus(401)->write("Page not found");
        return $response2;
    }
});

$app->run();
