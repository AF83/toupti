<?php

$routes = HighwayToHeaven::instance();

$routes->add('', array('controller' => 'TestApp'));
$routes->add('index', array('controller' => 'TestApp', 'action' => 'index'));
$routes->add('post', array('controller' => 'TestApp', 'action' => 'post'));
$routes->add('upload', array('controller' => 'TestApp', 'action' => 'upload'));
$routes->add('multiple_template', array('controller' => 'TestApp', 'action' => 'multiple_template'));
$routes->add('500', array('controller' => 'TestApp', 'action' => 'error_500'));
