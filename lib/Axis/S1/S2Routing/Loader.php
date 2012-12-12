<?php
/**
 * Date: 11.12.12
 * Time: 4:15
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\S2Routing;

class Loader extends \Symfony\Component\Config\Loader\Loader
{

  /**
   * Loads a resource.
   *
   * @param mixed  $resource The resource
   * @param string $type     The resource type
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function load($resource, $type = null)
  {
    if ($cache = \sfContext::getInstance()->getConfigCache()->checkConfig($resource, true))
    {
      $routes = require($cache);
      return $routes;
    }
  }

  /**
   * Returns true if this class supports the given resource.
   *
   * @param mixed  $resource A resource
   * @param string $type     The resource type
   *
   * @return Boolean true if this class supports the given resource, false otherwise
   */
  public function supports($resource, $type = null)
  {
    return is_string($resource) && substr($resource, -4) == '.yml';
  }
}
