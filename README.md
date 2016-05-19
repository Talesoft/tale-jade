

# Tale Jade for PHP


[![GitHub release](https://img.shields.io/github/release/talesoft/tale-jade.svg?style=flat-square)](https://github.com/Talesoft/tale-jade) [![Travis](https://img.shields.io/travis/Talesoft/tale-jade.svg?style=flat-square)](https://travis-ci.org/Talesoft/tale-jade) [![Packagist](https://img.shields.io/packagist/dt/talesoft/tale-jade.svg?style=flat-square)](https://packagist.org/packages/talesoft/tale-jade) [![HHVM](https://img.shields.io/hhvm/talesoft/tale-jade.svg?style=flat-square)](https://travis-ci.org/Talesoft/tale-jade) [![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://github.com/Talesoft/tale-jade/blob/master/LICENSE.md)
[![Gitter](https://img.shields.io/gitter/room/Talesoft/tale-jade.svg?maxAge=2592000?style=flat-square)](https://gitter.im/Talesoft/tale-jade)

> **Finally a fully-functional, complete and clean port of the Jade language to PHP**
>
> *— Abraham Lincoln*


The Tale Jade Template Engine brings the popular and powerful Templating-Language [Jade for Node.js](http://jade-lang.com) to PHP!

Tale Jade is the first complete and most powerful Jade implementation in PHP.

---

## Getting Started


### Install with [Composer](https://getcomposer.org)

[Download Composer](https://getcomposer.org/download/)

The composer package for Tale Jade is called [`talesoft/tale-jade`](https://packagist.org/packages/talesoft/tale-jade)

If you want to get started right now, hook up [composer](https://getcomposer.org/) and run

```bash
$ composer require "talesoft/tale-jade:*"
$ composer install
```

or add it to your `composer.json` by yourself

```json
{
    "require": {
        "talesoft/tale-jade": "*"
    }
}
```

### Install by downloading the sources

You can also download the sources by yourself.

Tale Jade is compatible with the [PSR-0](http://www.php-fig.org/psr/psr-0/) and [PSR-4](http://www.php-fig.org/psr/psr-4/) autoloading standards.

Put the sources inside your a `Tale/Jade` subfolder inside your autoloading directories, e.g. `library/Tale/Jade` and you're ready to go!

The easiest way might be to just put a clone of the repository there, that way you can update it easier

```bash
$ git clone git@github.com:Talesoft/tale-jade.git library/Tale/Jade
```

or as a sub-module if you're using git for your project as well

```bash
$ git submodule add git@github.com:Talesoft/tale-jade.git library/Tale/Jade
```


### Rendering a Jade Template

Include the `vendor/autoload.php` file of composer in your PHP script and get started with Tale Jade!

```php

use Tale\Jade;

//Include the vendor/autoload.php if you're using composer!
include('vendor/autoload.php');

$renderer = new Jade\Renderer();

echo $renderer->render('your-jade-file');
```

This way, the renderer will search for `your-jade-file.jade` in your `get_include_path()`-paths.
Notice that the path passed to `render` should be relative. You can give it absolute paths, but it will make caching harder.

We show you how to add alternative search paths further in the **Basic configuration** section below.

When the Jade-file gets rendered, a `./cache/views`-directory is created automatically and the compiled PHTML will be stored in that directory.

To change this directory, use the `cachePath`-option

```php

$renderer = new Jade\Renderer([
    'cache_path' => '/your/absolute/cache/path'
]);
```


The Jade-file will now be rendered to that directory on each call.

To enable a cache that won't render the files on each call, use the `lifeTime` option of the `file`-adapter


```php

$renderer = new Jade\Renderer([
    'ttl' => 3600 //Will cache the file for 3600 seconds (one hour)
]);
```


### Basic configuration


To enable formatting of the PHTML-output, use the `pretty`-option

```php

$renderer = new Jade\Renderer([
    'pretty' => true
]);
```


If you don't want to use the `get_include_path()`-paths (which could actually harbor a security risk in some cases), pass your own search paths to the Renderer.
Rendered and included Jade-files will be searched in those paths and not in the `get_include_path()`-paths anymore.

```php

//Either with
$renderer = new Jade\Renderer([
    'paths' => [__DIR__.'/views']
]);

//or with
$renderer->addPath(__DIR__.'/views');
```

As soon as you pass *any* path, the loading from the `get_include_path()`-paths will be disabled and you always load from your passed directory/ies.

To pass variables to your Jade-file, use the second argument of the `render`-method

```php

echo $renderer->render('index', [
    'title' => 'Jade is awesome!',
    'content' => 'Oh yeah, it is.'
]);
```

These can be used inside Jade as normal variables

```jade

h1= $title

+content-block($content)
```


---


## Supported features

We support every single feature the [original Jade implementation](http://jade-lang.com/reference/) supports!
This always has been and will always be our main target.

**But why stop there?**
PHP has it's own features that are surely different from JavaScript's.
By utilizing those features we aim to bring in more, compatible features into the language to make the fastest template development ever possible!

**You can try features and see a bunch of examples on our [sandbox site](http://sandbox.jade.talesoft.io)**

### Supported official Node.js Jade Features

- [Tags](http://sandbox.jade.talesoft.io)
- [Classes](http://sandbox.jade.talesoft.io?example=classes)
- [IDs](http://sandbox.jade.talesoft.io?example=ids)
- [Doctypes](http://sandbox.jade.talesoft.io?example=html-5)
- [Attributes](http://sandbox.jade.talesoft.io?example=attributes)
- [Mixins](http://sandbox.jade.talesoft.io?example=mixins)
- [Blocks](http://sandbox.jade.talesoft.io?example=blocks)
- [Expressions](http://sandbox.jade.talesoft.io?example=expressions)
- [Escaping](http://sandbox.jade.talesoft.io?example=escaping)
- [Block Expansion](http://sandbox.jade.talesoft.io?example=block-expansion)
- [Assignments](http://sandbox.jade.talesoft.io?example=assignments)
- [Comments](http://sandbox.jade.talesoft.io?example=comments)
- [Code](http://sandbox.jade.talesoft.io?example=code)
- [Inheritance](http://sandbox.jade.talesoft.io?example=inheritance)
- [Includes](http://sandbox.jade.talesoft.io?example=includes)
- [Conditionals](http://sandbox.jade.talesoft.io?example=conditionals)
- [Loops](http://sandbox.jade.talesoft.io?example=loops)
- [Interpolation](http://sandbox.jade.talesoft.io?example=interpolation)
- [Filters](http://sandbox.jade.talesoft.io?example=filters)
- [Mixin Blocks](http://sandbox.jade.talesoft.io?example=mixin-blocks)
- [Variadics](http://sandbox.jade.talesoft.io?example=variadics)

### Supported Tale Jade Features

- [Named Mixin Parameters](http://sandbox.jade.talesoft.io?example=named-mixin-parameters)
- [Attribute Stacking](http://sandbox.jade.talesoft.io?example=attribute-stacking)
- [Variable Access](http://sandbox.jade.talesoft.io?example=variable-access)
- [Do/while and for-Loops](http://sandbox.jade.talesoft.io?example=loops)

### Other cool features

- Automatic isset-checks for simple variables with `?`-flag to disable the behavior
- Inbuilt Markdown, CoffeeScript, LESS, SCSS/SASS and Stylus support
- Escapable text for HTML/-PHP-output
- UTF-8 support via PHP's mb_* extension
- Indentation detection and support for any indentation kind you like
- Hackable and customizable renderer, compiler, parser and lexer
- Huge amount of (optional) configuration possibilities
- Graceful compiler forgiving many mistakes (e.g. spaces around the code)
- Lightning fast and clean compilation
- Detailed error handling
- Renderer with different adapters
- Intelligent expression parsing based on bracket counting
- Huge documentation available
- Tested well and maintained actively


### There's more to come...

Tale Jade is actively used and developed in many projects and is improved constantly.

We don't stick to the Jade-convention, but we'll always provide compatibility to Node.js Jade to help reducing confusion.

We love Jade, we love PHP, we love Node.js and we love the official and original Jade-contributors.

**Planned features:**
- [x] Command line tools
- [ ] Import Attributes (`include some-file(some-var='some-value')`)
- [ ] Helper Libraries (Own custom helper libraries)
- [ ] Aliases (Like mixins, just smaller)
- [ ] Website Kit for easy website creation with Tale Jade
- [x] Stylus integration
- [x] CoffeeScript integration
- [x] Markdown integration
- [ ] Extensions and package manager

---


## Documentation Resources

[Tale Jade Live Compiler](http://sandbox.jade.talesoft.io)
A compiler for you to play with in your browser as well as a whole bunch of examples to give you a grasp of what Tale Jade is capable of.

[The Tale Jade API Docs](http://jade.talesoft.io/docs)
The documentation of the Tale Jade source code.
Generated with phpDocumentor, but is's fairly enlightening.

[Official Node.js Jade Documentation](http://jade-lang.com)
The real thing. This is where everything that we do here originates from.
The syntax is the same, only the code-expressions are different.

[Tale Jade Bootstrap](https://github.com/Talesoft/tale-jade-bootstrap) 
A quick-start project to get you up and running. Fork it, download it, play with it. 
Don't forget to run `composer install` before launching ([Download Composer](https://getcomposer.org/download/))

[Development Test Files](https://github.com/Talesoft/tale-jade-examples)
The example files we tested the engine with.
We cover all features somewhere in there, for sure!

[Tale Jade Unit Tests](https://github.com/Talesoft/tale-jade/tree/master/Test)
The Unit Tests we're using to ensure stability.
There will be new tests added constantly and most features are covered here.
It's PHP code, though.


---


## Tale Jade in for your favorite framework

You're using a framework with a template engine already, but you really want to try out Jade?
Search no further.

Thanks to the Tale Jade Community we got some modules for existing frameworks that allow you to use Tale Jade easily!

### Laravel Framework
- [Official Tale Jade Bridge](http://github.com/Talesoft/tale-jade-laravel)

### Yii2 Framework
- [jacmoe's Extension](https://github.com/jacmoe/yii2-tale-jade)

### SimpleMVCFramework
- [Cagatay's SMVC Fork](https://github.com/Talesoft/tale-jade-smvc)

### CakePHP 3
- [clthck's Plugin](https://github.com/clthck/cakephp-jade)

### FlightPHP
- [berkus' Integration](https://gist.github.com/berkus/f54347a4a1fd74e9e162)

### Symphony XSLT CMS
*(This is not the Symfony PHP Framework)*

- [vdcrea's Jade Editor](http://www.getsymphony.com/download/extensions/view/111595/)


**Your framework is missing? [Send us an e-mail](mailto:info@talesoft.io) and we'll get a bridge up and running as soon as possible!**

A great thanks to the contributors of these modules!

---


## Get in touch

If you find a bug or miss a function, please use the [Issues](https://github.com/Talesoft/tale-jade/issues) on this page
to tell us about it. We will gladly hear you out :)

Don't forget to [support our work](https://www.paypal.me/TorbenKoehn) if you like it!

If you'd like to contribute, fork us, send us pull requests and we'll take a deep look at what you've been working at!
We're completely **Open Source**! You can do anything you like with our code as long as you stick to the
**MIT-license** we've appended.

You can also contact us via E-Mail [info@talesoft.io](mailto:info@talesoft.io)


**Thank you for using Tale Jade. Let us spread the Jade-language together!**

