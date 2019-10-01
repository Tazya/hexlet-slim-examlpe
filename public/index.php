<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$repo = new App\Repository();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    $router->urlFor('users');
    $router->urlfor('user', ['id' => 4]);
    return $response->write('Welcome!');
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/users', function ($request, $response) use ($repo, &$router) {
    $term = $request->getQueryParam('term');
    $users = $repo->all();
    if (!empty($term)) {
    	$filtered_users = array_filter($users, function ($user) use ($term) {
            if (strpos(ucfirst($user['name']), ucfirst($term)) === 0) {
                return $user;
            }
    	});
    	$params = ['users' => $filtered_users, 'term' => $term, 'newUserLink' => $router->urlFor('userNew')];
    } else {
    	$params = ['users' => $users, 'term' => [], 'newUserLink' => $router->urlFor('userNew')];
    }
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->post('/users', function ($request, $response) use ($repo) {
    $validator = new App\Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        $repo->save($user);
        return $response->withHeader('Location', '/')
          ->withStatus(302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('usersForm');

$app->get('/users/new', function ($request, $response) use ($router){
		
    $params = [
        'user' => [],
        'errors' => [],
        'usersLink' => $router->urlFor('users')
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('userNew');

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря http://php.net/manual/ru/closure.bindto.php
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->run();