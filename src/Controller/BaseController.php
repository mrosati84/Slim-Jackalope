<?php

namespace JRAdmin\Controller;

use Slim\Container;

class BaseController {

  /**
   * @var Container
   */
  protected $container;

  public function __construct(Container $container) {
    $this->container = $container;
  }

}
