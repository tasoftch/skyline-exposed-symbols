<?php
return array (
  'purposes' => 
  array (
    'ACTIONCONTROLLER' => 
    array (
      '#' => 
      array (
        0 => 'Application\\Controller\\ContactActionController',
        1 => 'Application\\Controller\\IndexController',
        2 => 'Skyline\\API\\Controller\\AbstractAPIActionController',
        3 => 'Skyline\\CMS\\Controller\\AbstractTemplateActionController',
        4 => 'Skyline\\Application\\Controller\\AbstractActionController',
      ),
    ),
    'ERRORHANDLER' => 
    array (
      '#' => 
      array (
        0 => 'Skyline\\Kernel\\Service\\Error\\AbstractErrorHandlerService',
        1 => 'Skyline\\Kernel\\Service\\Error\\AbstractHTTPErrorHandlerService',
        2 => 'Skyline\\Kernel\\Service\\Error\\DisplayErrorHandlerService',
        3 => 'Skyline\\Kernel\\Service\\Error\\HTMLDevelopmentErrorHandlerService',
        4 => 'Skyline\\Kernel\\Service\\Error\\HTMLProductionErrorHandlerService',
        5 => 'Skyline\\Kernel\\Service\\Error\\LogErrorHandlerService',
      ),
    ),
  ),
  'method_purposes' => 
  array (
  ),
  'classes' => 
  array (
    'Application\\Controller\\ContactActionController' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Application\\Controller\\AbstractActionController',
      ),
    ),
    'Application\\Controller\\IndexController' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Application\\Controller\\AbstractActionController',
        1 => 'Skyline\\CMS\\Controller\\AbstractTemplateActionController',
      ),
    ),
    'Skyline\\API\\Controller\\AbstractAPIActionController' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Application\\Controller\\AbstractActionController',
      ),
      'isAbstract' => true,
    ),
    'Skyline\\CMS\\Controller\\AbstractTemplateActionController' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Application\\Controller\\AbstractActionController',
      ),
      'isAbstract' => true,
    ),
    'Skyline\\Application\\Controller\\AbstractActionController' => 
    array (
      'isAbstract' => true,
    ),
    'Skyline\\Kernel\\Service\\Error\\AbstractErrorHandlerService' => 
    array (
      'isAbstract' => true,
    ),
    'Skyline\\Kernel\\Service\\Error\\AbstractHTTPErrorHandlerService' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Kernel\\Service\\Error\\AbstractErrorHandlerService',
      ),
      'isAbstract' => true,
    ),
    'Skyline\\Kernel\\Service\\Error\\DisplayErrorHandlerService' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Kernel\\Service\\Error\\AbstractErrorHandlerService',
      ),
    ),
    'Skyline\\Kernel\\Service\\Error\\HTMLDevelopmentErrorHandlerService' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Kernel\\Service\\Error\\AbstractErrorHandlerService',
        1 => 'Skyline\\Kernel\\Service\\Error\\AbstractHTTPErrorHandlerService',
      ),
    ),
    'Skyline\\Kernel\\Service\\Error\\HTMLProductionErrorHandlerService' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Kernel\\Service\\Error\\AbstractErrorHandlerService',
        1 => 'Skyline\\Kernel\\Service\\Error\\AbstractHTTPErrorHandlerService',
      ),
    ),
    'Skyline\\Kernel\\Service\\Error\\LinearChainErrorHandlerService' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Kernel\\Service\\Error\\AbstractErrorHandlerService',
      ),
    ),
    'Skyline\\Kernel\\Service\\Error\\LogErrorHandlerService' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Kernel\\Service\\Error\\AbstractErrorHandlerService',
      ),
    ),
    'Skyline\\Kernel\\Service\\Error\\PriorityChainErrorHandlerService' => 
    array (
      'inheritance' => 
      array (
        0 => 'Skyline\\Kernel\\Service\\Error\\AbstractErrorHandlerService',
        1 => 'Skyline\\Kernel\\Service\\Error\\LinearChainErrorHandlerService',
      ),
    ),
  ),
);