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
      'current_route' => $request->getAttribute('route')->getName(),
      'node_type_name' => $node_type->getName(),
      'node_properties' => $node_properties,
      'property_types' => $property_types,
    ]);
  }

}
