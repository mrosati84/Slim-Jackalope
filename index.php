<?php

require_once __DIR__ . '/vendor/autoload.php';

use Jackalope\RepositoryFactoryJackrabbit;
use Jackalope\Session;
use PHPCR\SimpleCredentials;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$container = new Slim\Container();
$app = new Slim\App($container);

/* -----------------------------------------------------------------------------
 * Setting the application container.
 * -------------------------------------------------------------------------- */

$container['settings']['displayErrorDetails'] = (bool) getenv('DISPLAY_ERRORS');

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
  $basePath = rtrim(str_ireplace('index.php', '', $uri->getBasePath()), '/');
  $view->addExtension(new TwigExtension($container['router'], $basePath));

  return $view;
};

/* -----------------------------------------------------------------------------
 * Application routes.
 * -------------------------------------------------------------------------- */

$app->get('/', function(Request $request, Response $response) {
  /* @var $session Session */
  $session = $this->get('jackalope');
  $rootNode = $session->getNode('/pages');

  return $this->view->render($response, 'index.twig', [
    'nodes' => $rootNode->getNodes()
  ]);
});

$app->get('/pages/{id}', function($request, $response, $args) {
  /* @var $session Session */
  $session = $this->get('jackalope');
  $node = $session->getNode('/pages/' . $args['id']);

  return $this->view->render($response, 'node.twig', [
    'node' => $node
  ]);
});

$app->run();
