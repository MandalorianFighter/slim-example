<?php

session_start();

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();
$repo = new Slim\Example\UserRepository();
$vali = new Slim\Example\Validator();

function filterByTerm($users, $term) {
    return array_filter($users, fn($user) => str_contains($user['nickname'], $term) !== false);
}

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});

$app->get('/users', function ($request, $response) use ($repo) {
    $users = $repo->all();
    $term = $request->getQueryParam('term');

    $usersList = $term ? filterByTerm($users, $term) : $users;
    $messages = $this->get('flash')->getMessages();
    $params = [
	'term' => $term,
        'flash' => $messages,
        'users' => $usersList
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->post('/users', function ($request, $response) use ($repo, $vali) {
    $users = $repo->all();
    $user = $request->getParsedBodyParam('user');
    
    $errors = $vali->validate($user);
    if (count($errors) === 0) {
        $id = uniqid();
        $users[$id] = $user;
        $repo->save($users);
        $this->get('flash')->addMessage('success', 'New user is created successfully');
        return $response->withRedirect('users', 302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['id' => '', 'nickname' => '', 'email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/{id}', function ($request, $response, $args) use ($repo) {
    $users = $repo->all();
    $id = $args['id'];
    
    if(!array_key_exists($id, $users)){
        return $response->withStatus(401)->write("Page not found");
    }
    $user = $repo->find($id);
    $params = ['id' => $id, 'nickname' => $user['nickname'], 'email' => $user['email']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->get('/users/{id}/edit', function ($request, $response, $args) use ($repo) {

    $id = $args['id'];
    $user = $repo->find($id);
    $messages = $this->get('flash')->getMessages();
    $params = [
	'id' => $id,
        'flash' => $messages,
        'user' => $user,
        'errors' => []
            ];
        return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');



$app->patch('/users/{id}', function ($request, $response, array $args) use ($repo, $vali, $router)  {
    $id = $args['id'];
    $user = $repo->find($id);
    $data = $request->getParsedBodyParam('user');

    $errors = $vali->validate($data);

    if (count($errors) === 0) {
        $user['nickname'] = $data['nickname'];
        $user['email'] = $data['email'];

        $users = $repo->all();
        $users[$id] = $user;
        $repo->save($users);

        $this->get('flash')->addMessage('success', 'User has been updated');
        
        $url = $router->urlFor('editUser', ['id' => $id]);
        return $response->withRedirect($url);
    }

    $params = [
        'userData' => $data,
        'user' => $user,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response, array $args) use ($repo, $router) {
    $id = $args['id'];
    $repo->remove($id);
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withRedirect($router->urlFor('users'));
});

$app->run();
