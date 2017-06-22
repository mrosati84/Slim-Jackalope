<?php

namespace JRAdmin\Controller;

use Interop\Container\Exception\ContainerException;
use Jackalope\Session;
use PHPCR\PropertyType;
use Slim\Exception\ContainerValueNotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class AdminController
 *
 * @package JRAdmin\Controller
 */
class AdminController extends BaseController {

  /**
   * @param Request $request
   * @param Response $response
   * @param array $args
   *
   * @return mixed
   *
   * @throws ContainerException
   */
  public function front(Request $request, Response $response, $args) {
    return $this->container->get('view')->render($response, 'index.twig');
  }

  public function nodes($request, $response, $args) {
    /* @var $session Session */
    $session = $this->container->get('jackalope');
    $nodes = $session->getRootNode()->getNodes();

    return $this->container->get('view')->render($response, 'nodes.twig', [
      'nodes' => $nodes,
    ]);
  }

  /**
   * @param Request $request
   * @param Response $response
   * @param array $args
   *
   * @return mixed
   *
   * @throws ContainerException
   * @throws \RuntimeException
   */
  public function nodes_json(Request $request, Response $response, $args) {
    /* @var $session Session */
    $session = $this->container->get('jackalope');
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
  }

  /**
   * @param Request $request
   * @param Response $response
   * @param array $args
   *
   * @return mixed
   *
   * @throws ContainerException
   * @throws ContainerValueNotFoundException
   */
  public function node_types(Request $request, Response $response, $args) {
    /* @var $session Session */
    $session = $this->container->get('jackalope');
    $node_types = $session->getWorkspace()->getNodeTypeManager()
      ->getAllNodeTypes();

    return $this->container->get('view')->render($response, 'node-types.twig', [
      'node_types' => $node_types,
    ]);
  }

  /**
   * @param Request $request
   * @param Response $response
   * @param array $args
   *
   * @return mixed
   *
   * @throws ContainerException
   * @throws ContainerValueNotFoundException
   */
  public function node(Request $request, Response $response, $args) {
    /* @var $session Session */
    $session = $this->container->get('jackalope');
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

    return $this->container->get('view')->render($response, 'node-type.twig', [
      'node_type_name' => $node_type->getName(),
      'node_properties' => $node_properties,
      'property_types' => $property_types,
    ]);
  }

}
