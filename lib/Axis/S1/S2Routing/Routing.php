<?php
/**
 * Date: 10.12.12
 * Time: 0:45
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\S2Routing;

/**
 * @property \sfEventDispatcher $dispatcher
 * @property \sfCache $cache
 */
class Routing extends \sfPatternRouting
{
  /**
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * Available options:
   *  * router:                           The router service key (retrieved from context)
   *
   * @see sfRouting
   */
  public function initialize(\sfEventDispatcher $dispatcher, \sfCache $cache = null, $options = array())
  {
    parent::initialize($dispatcher, $cache, $options);

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

  /**
   * Parses a URL to find a matching route and sets internal state.
   *
   * Returns false if no route match the URL.
   *
   * @param  string $url  URL to be parsed
   *
   * @return array|false  An array of parameters or false if the route does not match
   */
  public function parse($url)
  {
    if (false === $info = $this->findRoute($url))
    {
      $this->currentRouteName = null;
      $this->currentInternalUri = array();

      return false;
    }

    if ($this->options['logging'])
    {
      $this->dispatcher->notify(new \sfEvent($this, 'application.log', array(sprintf('Match route "%s" (%s) for %s with parameters %s', $info['name'], $info['pattern'], $url, str_replace("\n", '', var_export($info['parameters'], true))))));
    }

    // store the current internal URI
    $this->updateCurrentInternalUri($info['name'], $info['parameters']);

    $route = $this->getRouter()->getRouteCollection()->get($info['name']);

    $this->ensureDefaultParametersAreSet();

    if ($route instanceof LegacyRoute)
    {
      $route = $route->getRoute();
      $route->bind($this->options['context'], $info['parameters']);
    }
    $info['parameters']['_sf_route'] = $route;

    return $info['parameters'];
  }

  /**
   * @param string $name
   * @param array $params
   * @param bool $absolute
   * @return string
   * @throws \sfConfigurationException
   */
  public function generate($name, $params = array(), $absolute = false)
  {
    // fetch from cache
    if (null !== $this->cache)
    {
      $cacheKey = 'generate_'.$name.'_'.md5(serialize(array_merge($this->defaultParameters, $params))).'_'.md5(serialize($this->options['context']));
      if ($this->options['lookup_cache_dedicated_keys'] && $url = $this->cache->get('symfony.routing.data.'.$cacheKey))
      {
        return $this->fixGeneratedUrl($url, $absolute);
      }
      elseif (isset($this->cacheData[$cacheKey]))
      {
        return $this->fixGeneratedUrl($this->cacheData[$cacheKey], $absolute);
      }
    }

    if ($name) // named route
    {
      $route = $this->getRouter()->getRouteCollection()->get($name);
      if (!$route)
      {
        throw new \sfConfigurationException(sprintf('The route "%s" does not exist.', $name));
      }
      $this->ensureDefaultParametersAreSet();
    }
    else // find a matching route by parameters
    {
      $name = $this->getRouteThatMatchesParameters($params);
      if (false === $name)
      {
        throw new \sfConfigurationException(sprintf('Unable to find a matching route to generate url for params "%s".', is_object($params) ? 'Object('.get_class($params).')' : str_replace("\n", '', var_export($params, true))));
      }
      // get a route by name
      $route = $this->getRouter()->getRouteCollection()->get($name);
    }

    if ($route instanceof LegacyRoute && !is_a('\sfRequestRoute', get_class($route->getRoute()), true))
    {
      // generate url like it was done before using sfRoute::generate() method
      $url = $route->getRoute()->generate($params, $this->options['context'], $absolute);
    }
    else
    {
      // generate using S2 UrlGenerator
      $url = $this->getRouter()->generate($name, $params, $absolute);
    }

    // store in cache
    if (null !== $this->cache)
    {
      /** @var $cacheKey string */
      if ($this->options['lookup_cache_dedicated_keys'])
      {
        $this->cache->set('symfony.routing.data.'.$cacheKey, $url);
      }
      else
      {
        $this->cacheChanged = true;
        $this->cacheData[$cacheKey] = $url;
      }
    }

    return $this->fixGeneratedUrl($url, $absolute);
  }

  protected function getRouteThatMatchesUrl($url)
  {
    $this->ensureDefaultParametersAreSet();

    try
    {
      $parameters = $this->getRouter()->match($url);
      $routeName = $parameters['_route'];
      $route = $this->getRouter()->getRouteCollection()->get($routeName);
      return array(
        'name' => $routeName,
        'route' => $route,
        'pattern' => $route->getPattern(),
        'parameters' => $parameters
      );
    }
    catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $e)
    {
      return false;
    }
  }

  /**
   * @param array $parameters
   * @return bool|string Found route name or FALSE if failed
   */
  protected function getRouteThatMatchesParameters($parameters)
  {
    $this->ensureDefaultParametersAreSet();
    foreach ($this->getRouter()->getRouteCollection() as $name => $route)
    {
      if ($route instanceof LegacyRoute
        && $route->getRoute()->matchesParameters($parameters, $this->options['context']))
      {
        return $name;
      }
    }

    return false;
  }

  /**
   * @see sfRouting
   */
  public function loadConfiguration()
  {
    if ($this->options['load_configuration'])
    {
      // loads configuration
      $this->getRouter();
    }

    parent::loadConfiguration();
  }
}
