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

  const DEFAULT_NODE_TYPE = 'nt:unstructured';

  /**
   * @param Request $request
   * @param Response $response
   *
   * @return mixed
   *
   * @throws ContainerException
   */
  public function front(Request $request, Response $response) {
    return $this->container->get('view')->render($response, 'front.twig', [
      'current_route' => $request->getAttribute('route')->getName(),
    ]);
  }

  /**
   * Get a list of nodes.
   *
   * @param Request $request
   * @param Response $response
   *
   * @return mixed
   *
   * @throws ContainerValueNotFoundException
   * @throws \PHPCR\RepositoryException
   * @throws ContainerException
   */
  public function nodes(Request $request, Response $response) {
    /* @var Session $session */
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
   *
   * @return mixed
   *
   * @throws ContainerException
   * @throws ContainerValueNotFoundException
   */
  public function node_types(Request $request, Response $response) {
    /* @var Session $session */
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
    /* @var Session $session */
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
  public function node_show(Request $request, Response $response) {
    $id = $request->getParam('id');
    $type = $request->getParam('type');
    /* @var Session $session */
    $session = $this->container->get('jackalope');
    $output = [];

    if ($type === 'list' && $request->isXhr()) {
      try {
        $node = $session->getNode($id);
      }
      catch (\Exception $e) {
        $node = $session->getRootNode();
      }

      return $this->container->get('view')->render($response, 'nodes_ajax.twig', [
        'node' => $node,
      ]);
    }

    try {
      $node = $session->getNode($id);
      $output['id'] = $node->getIdentifier();
      $output['name'] = $node->getName();

      /* @var $property Property */
      foreach ($node->getProperties() as $property) {
        $property_value = $property->getValue();
        $property_definition = $property->getDefinition();

        if (is_object($property_value)) {
          if (get_class($property_value) === \DateTime::class) {
            $property_value = $property_value->format('Y-m-d H:i:s');
          }
        }
        if (is_resource($property_value)) {
          $property_value = '<i>resource</i>';
        }

        $output['properties'][] = [
          'name' => $property->getName(),
          'type' => $property->getType(),
          'value' => $property_value,
          'protected' => $property_definition->isProtected(),
        ];
      }

      return $response->withJson($output);
    }
    catch (PathNotFoundException $e) {
      // Node was not found.
      return $response->withStatus(404)
        ->withJson([
          'message' => $e->getMessage()
        ]);
    }
    catch (RepositoryException $e) {
      // Invalid path specified (not an absolute path).
      return $response->withStatus(500)
        ->withJson([
          'message' => $e->getMessage()
        ]);
    }
  }

  /**
   * @param Request $request
   * @param Response $response
   *
   * @return Response
   *
   * @throws \PHPCR\ReferentialIntegrityException
   * @throws \PHPCR\NodeType\NoSuchNodeTypeException
   * @throws \PHPCR\InvalidItemStateException
   * @throws \PHPCR\AccessDeniedException
   * @throws \PHPCR\Version\VersionException
   * @throws \PHPCR\NodeType\ConstraintViolationException
   * @throws \PHPCR\Lock\LockException
   * @throws \PHPCR\ItemExistsException
   * @throws \InvalidArgumentException
   * @throws \Slim\Exception\ContainerValueNotFoundException
   * @throws \PHPCR\RepositoryException
   * @throws \PHPCR\PathNotFoundException
   * @throws \RuntimeException
   * @throws ContainerException
   */
  public function node_create(Request $request, Response $response) {
    /* @var Session $session */
    $session = $this->container->get('jackalope');
    $response_body = $request->getBody()->getContents();
    $data = [];

    parse_str($response_body, $data);

    if ($data['name'] && $data['parent']) {
      $parent_node = $session->getNode($data['parent']);
      $parent_node->addNode($data['name'], self::DEFAULT_NODE_TYPE);

      $session->save();
    }

    return $response;
  }

  /**
   * Update an existing node (i.e. add properties).
   *
   * @param Request $request
   * @param Response $response
   *
   * @return Response
   *
   * @throws ContainerValueNotFoundException
   * @throws \InvalidArgumentException
   * @throws \RuntimeException
   * @throws ContainerException
   */
  public function node_update(Request $request, Response $response) {
    $id = $request->getParam('id');
    $action = $request->getParam('action');
    /* @var Session $session */
    $session = $this->container->get('jackalope');

    try {
      $node = $session->getNode($id);

      switch ($action) {
        case 'rename':
          $new_name = $request->getParam('new_name');

          if ($new_name) {
            // Rename the node.
            $node->rename($new_name);
            $session->save();

            return $response->withJson([
              'message' => 'node renamed',
            ]);
          }

          break;

        case 'move':
          $old_path = $request->getParam('old_path');
          $new_path = $request->getParam('new_path');

          $session->move($old_path, $new_path);
          $session->save();

          return $response->withJson([
            'message' => 'node moved'
          ]);

          break;

        case 'copy':
          break;
      }

      /* @var array $properties */
      $properties = $request->getParam('properties');

      foreach ($properties as $property) {
        foreach (['name', 'type', 'value'] as $key) {
          if (!array_key_exists($key, $property)) {
            continue;
          }
        }

        // Update an existing property or set a new one.
        $node->setProperty($property['name'], $property['value'], (int) $property['type']);
      }

      $session->save();

      return $response->withJson([
        'properties' => $properties,
      ]);
    }
    catch (PathNotFoundException $e) {
      // Node was not found.
      return $response->withStatus(404)
        ->withJson([
          'message' => $e->getMessage()
        ]);
    }
    catch (RepositoryException $e) {
      // Invalid path specified (not an absolute path).
      return $response->withStatus(500)
        ->withJson([
          'message' => $e->getMessage()
        ]);
    }
  }

  /**
   * Delete a node.
   *
   * @param Request $request
   * @param Response $response
   *
   * @return Response
   *
   * @throws ContainerValueNotFoundException
   * @throws \InvalidArgumentException
   * @throws ContainerException
   * @throws \RuntimeException
   */
  public function node_delete(Request $request, Response $response) {
    $id = $request->getParam('id');
    /* @var Session $session */
    $session = $this->container->get('jackalope');

    try {
      $node = $session->getNode($id);
      $node->remove();
      $session->save();

      return $response->withJson([
        'message' => 'node deleted'
      ]);
    }
    catch (PathNotFoundException $e) {
      // Node was not found.
      return $response->withStatus(404)
        ->withJson([
          'message' => $e->getMessage()
        ]);
    }
    catch (RepositoryException $e) {
      // Invalid path specified (not an absolute path).
      return $response->withStatus(500)
        ->withJson([
          'message' => $e->getMessage()
        ]);
    }
  }

}
