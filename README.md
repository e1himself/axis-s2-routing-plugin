AxisS2RoutingPlugin
===================

symfony 1.x plugin that integrates Symfony2 routing component into your application.

It is a kinda failed try to bring Symfony2 routes into symfony1. But `sfPatternRouting` class is 
way too strong interwoven into symfony1 that it is practically impossible to replace it with other
routing class. 

See [AxisCurlyRoutingPlugin](https://github.com/e1himself/axis-curly-routing-plugin). It uses Symfony2 routing system
on a lower level (without replaceing sfPatternRouting but just introducing new route class).


Installation
------------

Use [Composer](http://getcomposer.org/). Just add this dependency to your `composer.json`:

```json
  "require": {
    "axis/axis-s2-routing-plugin": "dev-master"
  }
```

Configuration
-------------

First, you should replace standard `routing.yml` config handler with the new one.
Add this to your `config_handlers.yml`:

```yaml
config/routing.yml:
  class:    \Axis\S1\S2Routing\Config\ConfigHandler
  file:     %SF_PLUGINS_DIR%/AxisS2RoutingPlugin/lib/Axis/S1/S2Routing/Config/ConfigHandler.php
```

Than replace symfony's default `sfPatternRouting` with the plugin's routing class
by adding this to your `factories.yml`:

```yaml
all:
  routing:
    class: \Axis\S1\S2Routing\Routing
    param:
      router: @axis.s2routing.router
      generate_shortest_url:            true
      extra_parameters_as_query_string: true
```

Usage
-----

Now you can define routes in `routing.yml` files
using new [Symfony2 Routing Component](https://github.com/symfony/Routing) syntax
along with the old routes. Just define `sf2Route` as route class and it's all.

```yaml
s2_hello: # Symfony2 route
  class: sf2Route
  url:   '/s2/{name}'
  param: { module: test, action: hello }

s1_hello: # symfony1 route
  url:   '/s1/:name'
  param: { module: test, action: hello }
```

Important notes
---------------

* `sfActions::getRoute()` will return `sf2Route` class for new S2 routes
  and `sfRoute` (or subclass) instances for the old ones as it was before.
  *Be careful*: `sf2Route` is not compatible with symfony1 `sfRoute` instances.
* This is plugin is not compatible with
  [AxisModuleRoutingPlugin](https://github.com/e1himself/axis-module-routing-plugin)
  at the moment.     But I'm working on that.
* There is no support of collection and object routes for S2 routes for now.

Advantages
----------

So why is this for?

Symfony1 routing system went out of date. The main problem is that it strictly constrains
how your routes should look like and what you can use as variable value.

### Powerful caching

`S2RoutingPlugin` brings powerful Symfony2 routing cache to your project.

### Hierarchical URLs

The trigger reason of integrating S2 Routing was the ability to use *path variables* in routes.
For example you want to use something like hierarchical structure in your urls:

You could do this with default symfony1 routing:
```yaml
asset:
  url: /:path/:filename.:sf_format
  param: { ... }
  requirements:
    path: .*
```

This works well routing requests from `/my/assets/path/image.png` to defined controller
but when you need to generate url for that path you'll get this: `/my%2Fassets%2Fpath/image.png`.

Yeah, the custom coded Route class could handle that. But S2 Routing does this out of the box:
```yaml
asset:
  url: /{path}/{filename}.{sf_format}
  param: { ... }
  class: sf2Route
  requirements:
    path: .*
```
And there is many other handy features you can use.

### Variables delimited by any symbols

```yaml
blog_post:
  url: /blog/{slug}-{id}.html # you cannot use path like '/blog/:slug-:id.html' using symfony1 routing
  param: { ... }
  class: sf2Route
  requirements:
    slug: .+
    id:   \d+
```

### Scheme requirement

```yaml
login:
  url:  /login
  param: { ... }
  class: sfRoute
  requirements:
    _scheme: https
```
