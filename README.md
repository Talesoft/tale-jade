

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

---


## Supported features

We support every single feature the [original Jade implementation](http://jade-lang.com/reference/) supports!
This always has been and will always be our main target.

**But why stop there?**
PHP has it's own features that are surely different from JavaScript's.
By utilizing those features we aim to bring in more, compatible features into the language to make the fastest template development ever possible!

---


### Supported official Node.js Jade Features

- [Tags](http://jade.talesoft.io/examples/tags)

```jade
nav
    ul
        li: a(href='#')
        li: a(href='#')
        li: a(href='#')
```

- [Classes](http://jade.talesoft.io/examples/classes)

```jade
div.row
    .col-md-6.col-sm-6
        p First half
    .col-md-6.col-sm-6
        p Second half
```

- [IDs](http://jade.talesoft.io/examples/ids)

```jade
form#mainForm
    
    .form-group
        input.input-lg#userNameInput(name='userName')
    
    .form-group
        input.input-lg#passwordInput(name='password')
        
    button#submitButton(type='submit')
```

- [Doctypes](http://jade.talesoft.io/examples/doctypes)

```jade
doctype html
doctype 5
//- will compile to <!DOCTYPE html>

doctype xml
//- will compile to <?xml version="1.0" encoding="utf-8"?>
```

- [Attributes](http://jade.talesoft.io/examples/attributes)

```jade

a(href='/my/path.html', target='_blank')

.col(class=col-md-6, class='col-sm-6')
//- will compile to <div class="col col-md-6 col-sm-6"></div>
```

- [Mixins](http://jade.talesoft.io/examples/mixins)

```jade

mixin custom-button(label, theme= 'default')
    a.btn(class='btn-#{$theme}')= $label
    
+custom-button('Button A')
+custom-button('Button B')
```

- [Blocks (with prepend, append and replace support)](http://jade.talesoft.io/examples/blocks)

```jade

block scripts
    script(src='/js/jquery.js')
    
append scripts
    script(src='/js/plugin.jquery.js')
    script(src='/js/plugin-2.jquery.js')
    
replace scripts
    //- rather take another framework?!
    script(src='/js/mootools.js')
```

- [Expressions & Escaping](http://jade.talesoft.io/examples/expressions)

```jade

p= $greeting

p!= $someVariableContainingHTML

input(value=$defaultValue)
```

- [Block Expansion](http://jade.talesoft.io/examples/block-expansion)

```jade
li: a(href='#'): i.fa.fa-gear

if $something: p Do Something
```

- [Cross Assignments (&attributes)](http://jade.talesoft.io/examples/assignments)

```jade
//- Other than the official Node.js, this works with any attribute
//- The official &attributes is not implemented fully right now

a&classes('btn', 'btn-default')

a&classes($classesFromScript)

a&href('http://host', '/sub-url', '/file.html')

$stylesArray= ['width' => '100%', 'background' => 'red']
div&styles($stylesArray)
```

- [Comments](http://jade.talesoft.io/examples/comments)

```jade

//- This will be compiled to a PHP comment and will not be visible in client output

// This will be compiled to a HTML comment and will be visible in client output

//
    you can easily
    go one level deeper
    and span a comment
    across multiple
    lines
```

- [Inline Code](http://jade.talesoft.io/examples/code)

```jade

<?php $i = 15; ?>
p Do something
- $i = 100
p Do something else
-
    $i = [
        'a',
        'b',
        'c'
    ]
```

- [Inheritance](http://jade.talesoft.io/examples/inheritance)

```jade

extends layouts/master

block content
    p This here will replace the "content"-block in the master-layout!
```

- [Includes (with filters)](http://jade.talesoft.io/examples/includes)

```jade

include some-jade-file

include some-php-file.php
// will be compiled to <?php ?> correctly

include some-css-file.css
// will be compiled to <style>..included content...</style>

include some-js-file.js
// will be compiled to <script>...included content...</script>
```

- [Conditionals (if, else, elseif, case, when, unless)](http://jade.talesoft.io/examples/conditionals)

```jade

if $something
    p Do something
else
    p Do something else
    
unless $error
    p.success Success!
    
case $someState
    when 'state-1': p Do anything
    when 'state-2'
        p Do some larger thing
    default
        p Do the default thing
```

- [Loops (each, while)](http://jade.talesoft.io/examples/loops)

```jade

each $item, $key in $items
    p Item at #{$key} is #{$item}!
    
while $i < 100
    p Do something until $i is 100
    - $i++
```

- [Interpolation (with Element Interpolation)](http://jade.talesoft.io/examples/interpolation)

```jade

p Hello, #{$user->name}, how are you today?

p.
    I'm in a really long text, but I need a link!
    I can simply use #[a(href='jade-interpolation.html') Jade Interpolation!]
```

- [Filters](http://jade.talesoft.io/examples/filters)

```jade

:css
    body, html {
        height: 100%;
    }
    
:js
    do.something();
    
    
:php
    
    function someFunc() {
        // Do something
    }
```

- [Mixin Blocks](http://jade.talesoft.io/examples/mixin-blocks)

```jade

mixin article(title= 'Untitled')
    header.article-header= $title
    article
        if block
            block
        else
            p No content for this article :(
            
+article('Article 1')
    p The content of my first article
    
+article('Article 2')
    p The content of my second article
```

- [Variadics](http://jade.talesoft.io/examples/variadics)

```jade

mixin post-list(...posts)
    each $post in $posts
        header= $post->title
        article= $post->content
        
        
+post-list($post1, $post2, $post3, $post4)
```


### Supported Tale Jade Features

- [Named Mixin Parameters](http://jade.talesoft.io/examples/named-parameters)

```jade

mixin table(searchQuery, page= 0, amount= 100, order='id:asc')
	table
		//... something


+table('search query', order='id:desc')
```

- [Attribute Stacking](http://jade.talesoft.io/examples/attribute-stacking)

```jade

a(href='http://host', href='/path', href='/file')
// will compile to <a href="http://host/path/file"></a>

a(class='btn', class='btn-default', class='btn-lg')
// will compile to <a class="btn btn-default btn-lg"></a>

div(style='width: 100%', style='height: 50%', style='background: red')
// will compile to <div style="width: 100%; height: 50%; background: red"></div>
```

- [Filter Maps](http://jade.talesoft.io/examples/filter-map)
See filters above. Official Node.js doesn't do filter-mapping based on file-extensions.

- [Cross Assignments](http://jade.talesoft.io/examples/cross-assignments)
See assignments above. They are quite more dynamic than the official implementation.

- [Direct Variable Access](http://jade.talesoft.io/examples/direct-variable-access)

```jade

$i= 100

$array(key1='value1', key2='value2')

$array(key3='value3')

//$array will be a merged array from all the attributes above

$i
//Will compile to print $i

```

- [More Loops (do-while, for)](http://jade.talesoft.io/examples/more-loops)

```jade

$i= 0
do
    p Do something with #{$i}
    $i= $i + 1
while $i < 100
	

for $i = 0; $i < 100; $i++
	p Do something with #{$i}
```


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


### There's more to come...

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

---


## Documentation Resources


***Notice: Sorry that most documents are down right now, we're still working on them. We added some examples so you know how all features work.***
***Want to help us? [E-Mail us!](mailto:info@talesoft.io)!***

The only difference between Node.js Jade and Tale Jade is, that Tale Jade uses PHP Expressions everywhere

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



[Official Node.js Jade Documentation](http://jade-lang.com)
The real thing. This is where everything that we do here originates from.
The syntax is the same, only the code-expressions are different.

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


## Tale Jade in other Projects

You're using a framework with a template engine already, but you really want to try out Jade?
Search no further.

Thanks to the Tale Jade Community we got some modules for existing frameworks that allow you to use Tale Jade easily!

### Laravel Framework
- [Official Tale Jade Bridge](http://github.com/Talesoft/tale-jade-laravel)

### Yii2 Framework
- [jacmoe's Extension](http://www.yiiframework.com/extension/yii2-tale-jade/)

### SimpleMVCFramework
- [cu's SMVC Fork](https://github.com/Talesoft/tale-jade-smvc)

**Your framework is missing? [Send us an e-mail](mailto:info@talesoft.io) and we'll get a bridge up and running as soon as possible!**

A great thanks to the contributors of these modules!

---


## Get in touch

If you find a bug or miss a function, please use the [Issues](https://github.com/Talesoft/tale-jade/issues) on this page
to tell us about it. We will gladly hear you out :)

If you'd like to contribute, fork us, send us pull requests and we'll take a deep look at what you've been working at!
We're completely **Open Source**! You can do anything you like with our code as long as you stick to the
**MIT-license** we've appended.

You can also contact us via E-Mail.

If you're interested in other projects, you might contact us via E-Mail as well

**E-Mail: [info@talesoft.io](mailto:info@talesoft.io)**


If you like what we created, [feel free to spend us a coffee!](https://www.paypal.me/TorbenKoehn)!

**Thank you for using Tale Jade. Let us spread the Jade-language together!**

