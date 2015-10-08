

# The Tale Jade Template Engine

[![Build Status](https://travis-ci.org/Talesoft/tale-jade.svg?branch=master)](https://travis-ci.org/Talesoft/tale-jade)


> **Finally a fully-functional, complete and clean port of the Jade language to PHP**
>
> *— Abraham Lincoln*


The Tale Jade Template Engine brings the popular and powerful Templating-Language [Jade for Node.js](http://jade-lang.com) to PHP!

While previously there only existed ports of the existing Node.js Engine of Jade, **Tale Jade** is a completely new
implementation of the language utilizing the powerful and very specific features of PHP in your Jade application.

Build dynamic websites faster than ever before!


---


***Notice: Sorry that most documents are down right now, we're still working on them.***
***Our engine is really new and we're still documenting all functions of our***
***insane development process. Below you have a quick-guide to get up and running without the docs right now***


---


Initial API docs online now: [API Docs](http://jade.talesoft.io/docs)

## Getting Started

The composer package for Tale Jade is called [`talesoft/tale-jade`](https://packagist.org/packages/talesoft/tale-jade)

If you want to get started right now, hook up [composer](https://getcomposer.org/) and run

```
$ composer require talesoft/tale-jade:*
$ composer install
```

After that put the following line in your `index.php` (or anywhere you like)

```php

include 'vendor/autoload.php';

$renderer = new Tale\Jade\Renderer();

echo $renderer->render('your-jade-file');
```

This way, the renderer will search for `your-jade-file.jade` in your `PATH`-environment-variable.
Notice that the path passed to `render` needs to be relative, *always*.
To add alternative paths, scroll down a bit.

This uses the `stream` Renderer-adapter which doesn't work if you don't have `allow_url_fopen` enabled.
In this case, switch to the `file`-adapter

```php

$renderer = new Tale\Jade\Renderer([
    'adapter' => 'file'
]);
```

This will automatically create a `./cache`-directory in your document root.

To change this directory, use the `path`-option in the `adapterOptions`

```php

$renderer = new Tale\Jade\Renderer([
    'adapter' => 'file',
    'adapterOptions' => [
        'path' => '/your/absolute/cache/path'
    ]
]);
```


This will cache generated jade-files for 3600 seconds (1 hour) and render them through a file.
You can always watch the PHTML-output in the `cache`-directory this way.

To disable the caching (It will still generate the files, but it will generate them on each page call),
use the `lifeTime`-option of the `file`-adapter. Set it to `0` to disable the cache completely

```php

$renderer = new Tale\Jade\Renderer([
    'adapter' => 'file',
    'adapterOptions' => [
        'path' => '/your/absolute/cache/path',
        'lifeTime' => 0
    ]
]);
```


To enable formatting of the PHTML-output, use the `pretty`-option of the compiler

```php

$renderer = new Tale\Jade\Renderer([
    'compiler' => [
        'pretty' => false
    ]
]);
```

To use an own `views` or `templates` directory instead of the `PATH`-variable you can add paths to the compiler

```php

//Either with
$renderer = new Tale\Jade\Renderer([
    'compiler' => [
        'paths' => [__DIR__.'/views']
    ]
]);

//or with
$renderer->getCompiler()->addPath(__DIR__.'/views');
```

As soon as you pass *any* path, the loading from the `PATH`-environment-variable will be disabled and you
always load from your passed directory/ies

To pass variables to your Jade-file, use the second argument of the `render`-method

```php

echo $renderer->render('index', [
    'title' => 'Jade is awesome!',
    'content' => 'Oh yeah, it is.'
]);
```

These can be used inside the templates in several ways (Stick to the Jade-syntax, it works)


---


## Supported features

Tale Jade for PHP does not only implement **every existing feature** of Jade there is, it also brings in some new ones!

### Supported official Node.js Jade Features
- [Tags](http://jade.talesoft.io/examples/tags)
- [Classes](http://jade.talesoft.io/examples/classes)
- [IDs](http://jade.talesoft.io/examples/ids)
- [Doctypes](http://jade.talesoft.io/examples/doctypes)
- [Attributes](http://jade.talesoft.io/examples/attributes)
- [Mixins](http://jade.talesoft.io/examples/mixins)
- [Blocks (with prepend, append and replace support)](http://jade.talesoft.io/examples/blocks)
- [Expressions & Escaping](http://jade.talesoft.io/examples/expressions)
- [Block Expansion](http://jade.talesoft.io/examples/block-expansion)
- [Assignments (&attributes)](http://jade.talesoft.io/examples/assignments)
- [Comments](http://jade.talesoft.io/examples/comments)
- [Inline Code](http://jade.talesoft.io/examples/code)
- [Inheritance](http://jade.talesoft.io/examples/inheritance)
- [Includes (with filters)](http://jade.talesoft.io/examples/includes)
- [Conditionals (if, else, elseif, case, when)](http://jade.talesoft.io/examples/conditionals)
- [Loops (each, while, do)](http://jade.talesoft.io/examples/loops)
- [Interpolation (with Element Interpolation)](http://jade.talesoft.io/examples/interpolation)
- [Filters](http://jade.talesoft.io/examples/filters)
- [Mixin Blocks](http://jade.talesoft.io/examples/mixin-blocks)
- [Variadics](http://jade.talesoft.io/examples/variadics)


### Supported Tale Jade Features
- [Named parameters](http://jade.talesoft.io/examples/named-parameters)
- [Attribute Stacking](http://jade.talesoft.io/examples/attribute-stacking)
- [Filter Maps](http://jade.talesoft.io/examples/filter-map)
- [Cross Assignments](http://jade.talesoft.io/examples/cross-assignments)


### Other, unrelated, cool features

- UTF-8 support via PHP's mb_* extension
- Hackable compiler, parser and lexer
- Huge amount of (optional) configuration possibilities
- Graceful compiler forgiving many mistakes (e.g. spaces around the code)
- Lightning fast and clean compilation
- Detailed error handling
- Renderer with different adapters (ease-of-use vs. performance)
- Intelligent expression parsing



## Getting started

To install and use the Tale Jade library, follow our [Getting Started](http://jade.talesoft.io/getting-started) guide.

If you're interested, you might also look into our in-depth guides:

- [In-depth configuration](http://jade.talesoft.io/configuration)
- [Hacking Tale Jade](http://jade.talesoft.io/hacking)



## There's more to come...

Tale Jade is actively used and developed in many projects and is improved constantly.

We don't stick to the Jade-convention, but we'll always provide compatibility to Node.js Jade to
help reducing confusion.

We love Jade, we love PHP, we love Node.js and we love the official and original Jade-contributors.
We just don't love the way people implement existing stuff in PHP :)

Planned features:
- [ ] Import Attributes (`include some-file(some-var='some-value')`)
- [ ] Helper Libraries
- [ ] Aliases (Like mixins, just smaller)



## Get in touch

If you find a bug or miss a function, please use the [Issues](https://github.com/Talesoft/tale-jade/issues) on this page
to tell us about it. We will gladly hear you out :)

If you'd like to contribute, fork us, send us pull requests and we'll take a deep look at what you've been working at!
We're completely **Open Source**! You can do anything you like with our code as long as you stick to the
**MIT-license** we've appended.

You can also contact us via E-Mail.

If you're interested in other projects, you might contact us via E-Mail as well

**E-Mail: [info@talesoft.io](mailto:info@talesoft.io)**
