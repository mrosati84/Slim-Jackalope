<?php

require_once __DIR__ . '/vendor/autoload.php';

use Jackalope\Node;
use Jackalope\RepositoryFactoryJackrabbit;
use Jackalope\Session;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
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
  return $this->view->render($response, 'index.twig');
});

$app->get('/nodes', function ($request, $response) {
  /* @var $session Session */
  $session = $this->get('jackalope');
  $nodes = $session->getRootNode()->getNodes();

  return $this->view->render($response, 'nodes.twig', [
    'nodes' => $nodes,
  ]);
});

$app->get('/nodes_json', function (Request $request, Response $response) {
  /* @var $session Session */
  $session = $this->get('jackalope');
  $node_name = $request->getParam('node_name');
  $status = 200;
  $body = [];

  try {
    $node = $session->getNode($node_name);

    foreach ($node->getNodes() as $child_node) {
      $body['children'][] = [
        'name' => $child_node->getName(),
        'path' => $child_node->getPath(),
        'has_nodes' => $child_node->hasNodes(),
      ];
    }
  } catch (\Exception $e) {
    $status = 500;
    $body['message'] = $e->getMessage();
  }

  return $response->withJson([
    'data' => $body,
  ], $status);
});

$app->get('/node-types', function ($request, $response) {
  /* @var $session Session */
  $session = $this->get('jackalope');
  $node_types = $session->getWorkspace()->getNodeTypeManager()
    ->getAllNodeTypes();

  return $this->view->render($response, 'node-types.twig', [
    'node_types' => $node_types,
  ]);
});

$app->get('/node-types/{id}', function ($request, $response, $args) {
  /* @var $session Session */
  $session = $this->get('jackalope');
  $node_type = $session->getWorkspace()->getNodeTypeManager()
    ->getNodeType($args['id']);
  $node_properties = $node_type->getPropertyDefinitions();

  $property_types = [
    PropertyType::STRING        => 'String',
    PropertyType::DATE          => 'Date',
    PropertyType::BINARY        => 'Binary',
    PropertyType::DOUBLE        => 'Double',
    PropertyType::DECIMAL       => 'Decimal',
    PropertyType::LONG          => 'Long',
    PropertyType::BOOLEAN       => 'Boolean',
    PropertyType::NAME          => 'Name',
    PropertyType::PATH          => 'Path',
    PropertyType::URI           => 'Uri',
    PropertyType::REFERENCE     => 'Reference',
    PropertyType::WEAKREFERENCE => 'Weak reference',
    PropertyType::UNDEFINED     => 'Undefined',
  ];

  return $this->view->render($response, 'node-type.twig', [
    'node_type_name' => $node_type->getName(),
    'node_properties' => $node_properties,
    'property_types' => $property_types,
  ]);
});

$app->run();
