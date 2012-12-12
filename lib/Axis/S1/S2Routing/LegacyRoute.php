<?php
/**
 * Date: 10.12.12
 * Time: 1:48
 * Author: Ivan Voskoboynyk
 */
namespace Axis\S1\S2Routing;

class LegacyRoute extends \Symfony\Component\Routing\Route
{
  /**
   * @var \sfRoute
   */
  protected $route;

  /**
   * @param \sfRoute $sf_route
   */
  public function __construct($sf_route)
  {
    $this->route = $sf_route;

    parent::__construct(
      $sf_route->getPattern(),
      $sf_route->getDefaults(),
      $this->convertRequirments($sf_route->getRequirements()),
      $sf_route->getOptions()
    );
  }

  /**
   * @return \sfRoute
   */
  public function getRoute()
  {
    return $this->route;
  }

  /**
   * @return string
   */
  public function serialize()
  {
    $serialized = parent::serialize();
    $data = unserialize($serialized);
    $data['route'] = $this->route;
    return serialize($data);
  }

  /**
   * @param string $data
   */
  public function unserialize($data)
  {
    parent::unserialize($data);
    $data = unserialize($data);
    $this->route = $data['route'];
  }

  /**
   * @param array $requirements
   * @return array
   */
  protected function convertRequirments($requirements)
  {
    if (isset($requirements['sf_method']))
    {
      $requirements['_method'] = $requirements['sf_method'];
      unset($requirements['sf_method']);
    }
    foreach ($requirements as $name => $value)
    {
      if (is_array($value))
      {
        // convert array requirements to equivalent regex
        $requirements[$name] = implode('|', $value);
      }
    }
    return $requirements;
  }
}
