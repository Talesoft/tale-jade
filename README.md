

# Tale Jade for PHP


[![Build Status](https://travis-ci.org/Talesoft/tale-jade.svg?branch=master)](https://travis-ci.org/Talesoft/tale-jade)


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

You can also just include all necessary files by yourself if you like

```php
include('path/to/tale-jade/Lexer/Exception.php');
include('path/to/tale-jade/Lexer.php');
include('path/to/tale-jade/Parser/Exception.php');
include('path/to/tale-jade/Parser/Node.php');
include('path/to/tale-jade/Parser.php');
include('path/to/tale-jade/Compiler/Exception.php');
include('path/to/tale-jade/Compiler.php');
include('path/to/tale-jade/Renderer.php');
include('path/to/tale-jade/Filter.php');
include('path/to/tale-jade/Renderer/AdapterBase.php');

//and the adapter you want to use ('file' by default)
include('path/to/tale-jade/Renderer/Adapter/File.php');

//or
include('path/to/tale-jade/Renderer/Adapter/Stream/Wrapper.php');
include('path/to/tale-jade/Renderer/Adapter/Stream.php');


//optional helper functions
include('path/to/tale-jade/functions.php');
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
Notice that the path passed to `render` needs to be relative, *always*.
We show you how to add alternative search paths further in the **Basic configuration** section below.

When the Jade-file gets rendered, a `./cache/views`-directory is created automatically and the compiled PHTML will be stored in that directory.

To change this directory, use the `path`-option in the `adapterOptions`

```php

$renderer = new Jade\Renderer([
    'adapterOptions' => [
        'path' => '/your/absolute/cache/path'
    ]
]);
```


The Jade-file will now be rendered to that directory on each call.

To enable a cache that won't render the files on each call, use the `lifeTime` option of the `file`-adapter


```php

$renderer = new Jade\Renderer([
    'adapterOptions' => [
        'lifeTime' => 3600 //Will cache the file for 3600 seconds (one hour)
    ]
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

### Learning Jade

We're still working on our tutorials, so for now you have to stick with the [original Jade documentation](http://jade-lang.com/reference/).

The only difference between it and Tale Jade is, that Tale Jade uses PHP Expressions everywhere

In loops:
`each $itemName, $keyName in $items`

In attributes:
`a(href=$url, href='/some/sub/path')`

In conditionals:
`if $someCondition`

In interpolation:
`| This is some text and this was #{$interpolated}!`

PHP Expressions are possible in most cases 
e.g. `(empty($someVar) ? 'Default Value' : "$someVar!")`
and you can use functions and classes normally
e.g. `h1= strtoupper('Tale Jade is awesome!')`


---


## Supported features

We support every single feature the [original Jade implementation](http://jade-lang.com/reference/) supports!
This always has been and will always be our main target.

**But why stop there?**
PHP has it's own features that are surely different from JavaScript's.
By utilizing those features we aim to bring in more, compatible features into the language to make the fastest template development ever possible!


---


***Notice: Sorry that most documents are down right now, we're still working on them.***
***Our engine is really new and we're still documenting all functions of our insane development process. The features actually are in and work.***

**We uploaded some example resources for you to get a grasp of the possibilities**

[The Tale Jade API Docs](http://jade.talesoft.io/docs)
The documentation of the Tale Jade source code.
Generated with phpDocumentor, but is's fairly enlightening.

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
- [Conditionals (if, else, elseif, case, when, unless)](http://jade.talesoft.io/examples/conditionals)
- [Loops (each, while)](http://jade.talesoft.io/examples/loops)
- [Interpolation (with Element Interpolation)](http://jade.talesoft.io/examples/interpolation)
- [Filters](http://jade.talesoft.io/examples/filters)
- [Mixin Blocks](http://jade.talesoft.io/examples/mixin-blocks)
- [Variadics](http://jade.talesoft.io/examples/variadics)


### Supported Tale Jade Features
- [Named Mixin Parameters](http://jade.talesoft.io/examples/named-parameters)
- [Attribute Stacking](http://jade.talesoft.io/examples/attribute-stacking)
- [Filter Maps](http://jade.talesoft.io/examples/filter-map)
- [Cross Assignments](http://jade.talesoft.io/examples/cross-assignments)
- [More Loops (do)](http://jade.talesoft.io/examples/more-loops)


### Other, unrelated, cool features

- UTF-8 support via PHP's mb_* extension
- Hackable and customizable renderer, compiler, parser and lexer
- Huge amount of (optional) configuration possibilities
- Graceful compiler forgiving many mistakes (e.g. spaces around the code)
- Lightning fast and clean compilation
- Detailed error handling
- Renderer with different adapters (ease-of-use vs. performance)
- Intelligent expression parsing
- Huge documentation available
- Tested well and maintained actively


## There's more to come...

Tale Jade is actively used and developed in many projects and is improved constantly.

We don't stick to the Jade-convention, but we'll always provide compatibility to Node.js Jade to help reducing confusion.

We love Jade, we love PHP, we love Node.js and we love the official and original Jade-contributors.
We just don't love the way people implement existing stuff in PHP :)

Planned features:
- [ ] Command line tools
- [ ] Import Attributes (`include some-file(some-var='some-value')`)
- [ ] Helper Libraries (Own custom helper libraries)
- [ ] Aliases (Like mixins, just smaller)
- [ ] Stylus integration
- [ ] CoffeeScript integration
- [ ] Markdown integration
- [ ] Extensions and package manager


## Get in touch

If you find a bug or miss a function, please use the [Issues](https://github.com/Talesoft/tale-jade/issues) on this page
to tell us about it. We will gladly hear you out :)

If you'd like to contribute, fork us, send us pull requests and we'll take a deep look at what you've been working at!
We're completely **Open Source**! You can do anything you like with our code as long as you stick to the
**MIT-license** we've appended.

You can also contact us via E-Mail.

If you're interested in other projects, you might contact us via E-Mail as well

**E-Mail: [info@talesoft.io](mailto:info@talesoft.io)**


**Thank you for using Tale Jade. Let us spread the Jade-language together!**

