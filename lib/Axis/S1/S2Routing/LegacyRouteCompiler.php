<?php
/**
 * Date: 11.12.12
 * Time: 5:55
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\S2Routing;

use Axis\S1\S2Routing\LegacyRoute;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\RouteCompiler;

class LegacyRouteCompiler extends RouteCompiler
{
  /**
   * Compiles the current route instance.
   *
   * @param Route $route A Route instance
   *
   * @return CompiledRoute A CompiledRoute instance
   */
  public function compile(Route $route)
  {
    if ($route instanceof LegacyRoute)
    {
      $legacy = $route->getRoute();
      $legacy->compile();

      $tokens = $legacy->getTokens();
      $prefix = $this->getStaticPrefix($tokens);

      $tokens = $this->convertTokens($tokens, $legacy->getRequirements(), $legacy->getOptions());
      $variables = array_keys($this->getVariables($tokens));

      $compiled = new CompiledRoute(
        $prefix,
        $legacy->getRegex(),
        array_reverse($tokens),
        $variables
      );

      return $compiled;
    }
    else
    {
      return parent::compile($route);
    }
  }

  /**
   * Retrieves variables map from tokens array
   *
   * @param array $tokens
   * @return array Variables map (name => regexp)
   */
  protected function getVariables($tokens)
  {
    // filter only variable tokens
    $tokens = array_filter($tokens, function($token) {
      return $token[0] == 'variable';
    });

    $variables = array();
    // convert tokens array to variable's map (name => regexp)
    foreach ($tokens as $token)
    {
      $variables[$token[3]] = $token[2];
    }
    return $variables;
  }

  /**
   * Converts s1 route tokens into s2 route tokens format
   *
   * @param array $tokens
   * @param array $requirements
   * @param array $options
   * @return array
   */
  protected function convertTokens($tokens, $requirements, $options)
  {
    $prev = null;

    // remove with empty [1] separators and stars
    $tokens = array_filter($tokens, function($token) {
      return !($token[0] == 'separator' && $token[1] == '') && !($token[0] == 'text' && $token[2] == '*');
    });
    // merge text with separators
    $tokens = array_map(function($token) {
      // merge text with separator
      if ($token[0] == 'text')
      {
        $token[2] = $token[1] . $token[2];
      }
      // treat survived separators as text
      if ($token[0] == 'separator')
      {
        $token[1] = ''; // remove separator part
        $token[0] = 'text';
      }
      return $token;
    }, $tokens);

    $tokens[] = array('end'); // fictive end token
    $prev = null;
    $result = array();

    // merge neighbor text tokens
    foreach($tokens as $token)
    {
      if ($prev)
      {
        if ($token[0] == 'text' && $prev[0] == 'text')
        {
          $token[2] = $prev[2] . $token[2];
          $prev = null;
        }
      }
      // check once - $prev could be nullified
      if ($prev)
      {
        $result[] = $prev;
      }
      $prev = $token;
    }

    // fix text tokens for S2 format
    $tokens = array_map(function($token) {
      if ($token[0] == 'text')
      {
        return array('text', $token[2]);
      }
      return $token;
    }, $result);


    // use default or custom configured route var regexp
    $variable_regex = isset($options['variable_regex']) ? $options['variable_regex'] : '[\w\d_]+';
    // fix variables tokens - replace :var_name with variable regexp
    $tokens = array_map(function($token) use ($requirements, $variable_regex) {
      if ($token[0] == 'variable')
      {
        $token[2] = isset($requirements[$token[3]]) ? $requirements[$token[3]] : $variable_regex;
      }
      return $token;
    }, $tokens);

    return $tokens;
  }

  /**
   * @param $tokens
   * @return string
   *
   * @see sfRoute::postCompile()
   */
  protected function getStaticPrefix($tokens)
  {
    $staticPrefix = '';
    // find and join all static tokens at the beginning
    foreach ($tokens as $token)
    {
      switch ($token[0])
      {
        case 'separator':
          break;
        case 'text':
          if ($token[2] !== '*')
          {
            // non-star text is static
            $staticPrefix .= $token[1].$token[2];
            break;
          }
        default:
          // everything else indicates variable parts. break switch and for loop
          break 2;
      }
    }
    return $staticPrefix;
  }
}
