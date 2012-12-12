<?php
/**
 * Date: 10.12.12
 * Time: 0:45
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\S2Routing;

class BackwardCompatibleRouting extends \sfPatternRouting
{
  /**
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * @var array|\Symfony\Component\Config\Resource\ResourceInterface[]
   */
  protected $resources;

  public function __construct(\sfEventDispatcher $dispatcher, \sfCache $cache = null, $options = array())
  {
    if (isset($options['router']))
    {
      if (substr($options['router'],0,1) != '@')
      {
        throw new \InvalidArgumentException(
          sprintf('Router parameter should define a declared service name prefixed with @. "%s" given.',
            $options['router']
          )
        );
      }

      $this->router = substr($options['router'], 1);
    }
    parent::__construct($dispatcher, $cache, $options);
  }

  /**
   * @return \Symfony\Component\Routing\RouterInterface
   */
  public function getRouter()
  {
    if (is_string($this->router))
    {
      $this->router = \sfContext::getInstance()->get($this->router);
    }
    return $this->router;
  }

  public function loadConfiguration()
  {
    parent::loadConfiguration();
  }

  /**
   * @param $class
   */
  public function getS2Routes($class)
  {

  }
}
