<?php
spl_autoload_register('_autoload');

function _autoload($className) {
  $rootNamespace = 'Abstract';
  $path = dirname(__DIR__ . '../') . '/src';
  $safeClassName = str_replace($rootNamespace . '\\', '', $className);
  include $path . '/' . str_replace('\\', '/', $safeClassName) . '.php';
}
