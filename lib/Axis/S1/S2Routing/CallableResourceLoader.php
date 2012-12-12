<?php
/**
 * Date: 10.12.12
 * Time: 1:22
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\S2Routing;

class CallableResourceLoader extends \Symfony\Component\Config\Loader\Loader
{
  /**
   * @var string
   */
  protected $method;

  /**
   * @param string $method
   */
  function __construct($method)
  {
    $this->method = $method;
  }

  /**
   * Loads a resource.
   *
   * @param mixed  $resource The resource
   * @param string $type     The resource type
   * @return mixed
   */
  public function load($resource, $type = null)
  {
    return call_user_func(array($resource, $this->method), $type);
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
    return is_callable(array($resource, $this->method));
  }
}
