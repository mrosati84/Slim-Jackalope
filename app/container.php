<?php

/* -----------------------------------------------------------------------------
 * Set-up the application container.
 * -------------------------------------------------------------------------- */

use Jackalope\RepositoryFactoryJackrabbit;
use PHPCR\SimpleCredentials;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$container['settings']['displayErrorDetails'] = (bool) getenv('DISPLAY_ERRORS');
$container['settings']['determineRouteBeforeAppMiddleware'] = true;

$container['jackalope'] = function () {
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
  $view = new Twig(__DIR__ . '/../templates', [
    'cache' => __DIR__ . '/../cache',
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
