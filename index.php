<?php

require_once __DIR__ . '/vendor/autoload.php';

use Jackalope\RepositoryFactoryJackrabbit;
use PHPCR\SimpleCredentials;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$container = new Slim\Container();
$app = new Slim\App($container);

/* -----------------------------------------------------------------------------
 * Set-up the application container.
 * -------------------------------------------------------------------------- */

$container['settings']['displayErrorDetails'] = (bool) getenv('DISPLAY_ERRORS');
$container['settings']['determineRouteBeforeAppMiddleware'] = true;

$container['jackalope'] = function ($container) {
  $jackrabbit_url = getenv('JACKRABBIT_URL');
  $user           = getenv('JACKRABBIT_USER');
  $pass           = getenv('JACKRABBIT_PASSWORD');
  $workspace      = getenv('JACKRABBIT_WORKSPACE');

  $factory = new RepositoryFactoryJackrabbit();
  $repository = $factory->getRepository(
    array('jackalope.jackrabbit_uri' => $jackrabbit_url)
  );
  $credentials = new SimpleCredentials($user, $pass);

  return $repository->login($credentials, $workspace);
};

$container['view'] = function ($container) {
  $view = new Twig(__DIR__ . '/templates', [
    'cache' => __DIR__ . '/cache',
    'auto_reload' => (bool) getenv('TWIG_AUTO_RELOAD'),
  ]);

  /* @var $request Request */
  $request = $container['request'];

  /* @var $uri Uri */
  $uri = $request->getUri();

  // Instantiate and add Slim specific extension
  $basePath = rtrim(str_ireplace('index.php', '',
    $uri->getBasePath()), '/');
  $view->addExtension(new TwigExtension($container['router'], $basePath));
  $view->getEnvironment()->addGlobal('app', $container);

  return $view;
};

$container['AdminController'] = function ($container) {
  return new JRAdmin\Controller\AdminController($container);
};

/* -----------------------------------------------------------------------------
 * Register pplication routes.
 * -------------------------------------------------------------------------- */

$app->get('/', 'AdminController:front')->setName('front');
$app->get('/nodes', 'AdminController:nodes')->setName('nodes');
$app->get('/node', 'AdminController:node')->setName('nodes');
$app->get('/nodes_json', 'AdminController:nodes_json');
$app->get('/node-types', 'AdminController:node_types')
  ->setName('node_types');
$app->get('/node-types/{id}', 'AdminController:node_type');

$app->run();
