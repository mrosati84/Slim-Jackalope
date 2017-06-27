<?php

/* -----------------------------------------------------------------------------
 * Register pplication routes.
 * -------------------------------------------------------------------------- */

$app->get('/', 'AdminController:front')->setName('front');

$app->get('/nodes', 'AdminController:nodes')->setName('nodes');

$app->get('/node', 'AdminController:node_show')->setName('node_show');
$app->post('/node', 'AdminController:node_create')->setName('node_create');
$app->put('/node', 'AdminController:node_update')->setName('node_update');
$app->delete('/node', 'AdminController:node_delete')->setName('node_delete');

$app->get('/node-types', 'AdminController:node_types')
  ->setName('node_types');
$app->get('/node-types/{id}', 'AdminController:node_type');
