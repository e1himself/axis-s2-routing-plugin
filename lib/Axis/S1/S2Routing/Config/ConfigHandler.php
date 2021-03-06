<?php
/**
 * Date: 10.12.12
 * Time: 1:02
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\S2Routing\Config;

class ConfigHandler extends \sfRoutingConfigHandler
{
  /**
   * @var array|\Symfony\Component\Config\Resource\ResourceInterface[]
   */
  protected $resources;

  protected $routes;

  public function execute($configFiles)
  {
    $code = '$routes = new \Symfony\Component\Routing\RouteCollection();' . PHP_EOL;
    $code .= '// routes' . PHP_EOL;

    $data = array();
    foreach ($this->parse($configFiles) as $name => $routeConfig)
    {
      $class = $routeConfig[0];
      // wrap old symfony routes with LegacyRoute
      if (is_a($class, '\sfRoute', true) || is_a($class, '\sfRouteCollection', true))
      {
        if (!isset($options['compiler_class']))
        {
          $options['compiler_class'] = '\Axis\S1\S2Routing\LegacyRouteCompiler';
        }

        $routeClass = new \ReflectionClass($routeConfig[0] /* class */);
        $route = $routeClass->newInstanceArgs($routeConfig[1]);

        $routes = $route instanceof \sfRouteCollection ? $route : array($name => $route);
        foreach (\sfPatternRouting::flattenRoutes($routes) as $routeName => $route)
        {
          /** @var $route \sfRoute */
          $route->setDefaultOptions($options);
          $code .= sprintf(
            "\$routes->add(%s, new %s(unserialize(%s)));\n",
            var_export($routeName, true),
            '\Axis\S1\S2Routing\LegacyRoute',
            var_export(serialize($route), true)
          );
        }
      }
      else
      {
        $url = $routeConfig[1][0];
        $params = $routeConfig[1][1];
        $requirements = $routeConfig[1][2];
        $options = $routeConfig[1][3];

        $construction = sprintf("new %s(\n\t%s,%s,%s,%s\n)",
          $routeConfig[0], // class
          var_export($url,true),
          var_export($params, true),
          var_export($requirements, true),
          var_export($options, true)
        );

        $code .= sprintf(
          "\$routes->add(%s,%s);\n",
          var_export($name, true),
          $construction
        );
      }
    }

    $code .= '// used resources' . PHP_EOL;
    foreach ($configFiles as $file)
    {
      $code .= sprintf(
        "\$routes->addResource(new \\Symfony\\Component\\Config\\Resource\\FileResource(\n\t%s\n));\n",
        var_export($file, true)
      );
    }

    $class = __CLASS__;

    return sprintf("<?php\n".
        "// auto-generated by $class\n".
        "// date: %s\n%s\nreturn \$routes;", date('Y/m/d H:i:s'), $code
    );
  }

  public function getResources()
  {
    return $this->resources;
  }
}
