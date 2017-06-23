<?php

namespace JRAdmin\Controller;

use Interop\Container\Exception\ContainerException;
use Jackalope\Property;
use Jackalope\Session;
use PHPCR\PathNotFoundException;
use PHPCR\PropertyType;
use PHPCR\RepositoryException;
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
    return $this->container->get('view')->render($response, 'front.twig', [
      'current_route' => $request->getAttribute('route')->getName(),
    ]);
  }

  public function nodes(Request $request, Response $response, $args) {
    /* @var $session Session */
    $session = $this->container->get('jackalope');
    $nodes = $session->getRootNode()->getNodes();

    return $this->container->get('view')->render($response, 'nodes.twig', [
      'current_route' => $request->getAttribute('route')->getName(),
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
   * @throws \InvalidArgumentException
   * @throws ContainerValueNotFoundException
   * @throws \PHPCR\RepositoryException
   * @throws ContainerException
   * @throws \RuntimeException
   */
  public function nodes_json(Request $request, Response $response, $args) {
    /* @var $session Session */
    $session = $this->container->get('jackalope');
    $node_name = $request->getParam('id');
    $output = '<ul>';
    $node = null;

    try {
      $node = $session->getNode($node_name);
    }
    catch (\Exception $e) {
      $node = $session->getRootNode();
    }

    foreach ($node->getNodes() as $child_node) {
      $has_children = $child_node->hasNodes();
      $icon = $has_children ? 'jstree-folder' : 'jstree-file';
      $output .= sprintf('<li data-jstree=\'{"icon": "%s"}\' id="%s" class="%s">%s</li>',
        $icon,
        $child_node->getPath(),
        $has_children ? 'jstree-closed' : 'jstree-leaf',
        $child_node->getName());
    }

    $output .= '</ul>';

    $response->write($output);

    return $response;
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
      'current_route' => $request->getAttribute('route')->getName(),
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
  public function node_type(Request $request, Response $response, $args) {
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
      'current_route' => $request->getAttribute('route')->getName(),
      'node_type_name' => $node_type->getName(),
      'node_properties' => $node_properties,
      'property_types' => $property_types,
    ]);
  }

  /**
   * @param Request $request
   * @param Response $response
   * @param $args
   *
   * @return mixed
   * @throws \InvalidArgumentException
   * @throws \RuntimeException
   *
   * @throws ContainerException
   * @throws PathNotFoundException
   * @throws ContainerValueNotFoundException
   * @throws RepositoryException
   */
  public function node(Request $request, Response $response, $args) {
    $id = $request->getParam('id');
    /* @var $session Session */
    $session = $this->container->get('jackalope');
    $output = [];

    try {
      $node = $session->getNode($id);
      $output['id'] = $node->getIdentifier();
      $output['name'] = $node->getName();

      /* @var $property Property */
      foreach ($node->getProperties() as $property) {
        $output['properties'][] = [
          'name' => $property->getName(),
          'type' => $property->getType(),
          'value' => $property->getValue(),
        ];
      }

      return $response->withJson($output);
    }
    catch (PathNotFoundException $e) {
      // Node was not found.
      return $response->withStatus(404)
        ->withJson([
          'message' => 'node not found'
        ]);
    }
    catch (RepositoryException $e) {
      // Invalid path specified (not an absolute path).
      return $response->withStatus(500)
        ->withJson([
          'message' => 'invalid path'
        ]);
    }
  }

}
