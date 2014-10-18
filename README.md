# Orno\Di by [@philipobenito](https://twitter.com/philipobenito)

[![Latest Version](http://img.shields.io/packagist/v/orno/di.svg?style=flat)](https://github.com/orno/di/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/orno/di/master.svg?style=flat)](https://travis-ci.org/orno/di)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/orno/di.svg?style=flat)](https://scrutinizer-ci.com/g/orno/di/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/orno/di.svg?style=flat)](https://scrutinizer-ci.com/g/orno/di)
[![Total Downloads](https://img.shields.io/packagist/dt/orno/di.svg?style=flat)](https://packagist.org/packages/orno/di)

Orno\Di is a small but powerful dependency injection container that allows you to decouple components in your application in order to write clean and testable code. The container can automatically resolve dependencies of objects resolved through it.

## Installation

Add `orno/di` to your `composer.json`.

```json
{
    "require": {
        "orno/di": "2.*"
    }
}
```

Allow Composer to autoload the container.

```php
<?php

include 'vendor/autoload.php';
```

## Usage

- [Constructor Injection](#constructor-injection)
- [Setter Injection](#setter-injection)
- [Factory Closures](#factory-closures)
- [Automatic Dependency Resolution](#automatic-dependency-resolution)
- [Caching](#caching)
- [Configuration](#configuration)

### Constructor Injection

The container can be used to register objects and inject constructor arguments such as dependencies or config items.


For example, if we have a `Session` object that depends on an implementation of a `StorageInterface` and also requires a session key string. We could do the following:

```php
class Session
{
    protected $storage;

    protected $sessionKey;

    public function __construct(StorageInterface $storage, $sessionKey)
    {
        $this->storage    = $storage;
        $this->sessionKey = $sessionKey;
    }
}

interface StorageInterface
{
    // ..
}

class Storage implements StorageInterface
{
    // ..
}

$container = new \Orno\Di\Container;

$container->add('Storage');

$container->add('session', 'Session')
          ->withArgument('Storage')
          ->withArgument('my_super_secret_session_key');

$session = $container->get('session');
```

### Setter Injection

If you prefer setter injection to constructor injection, a few minor alterations can be made to accommodate this.

```php
class Session
{
    protected $storage;

    protected $sessionKey;

    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function setSessionKey($sessionKey)
    {
        $this->sessionKey = $sessionKey;
    }
}

interface StorageInterface
{
    // ..
}

class Storage implements StorageInterface
{
    // ..
}

$container = new Orno\Di\Container;

$container->add('session', 'Session')
          ->withMethodCall('setStorage', ['Storage'])
          ->withMethodCall('setSessionKey', ['my_super_secret_session_key']);

$session = $container->get('session');
```

This has the added benefit of being able to manipulate the behaviour of the object with optional setters. Only call the methods you need for this instance of the object.

### Factory Closures

The most performant way to use Orno\Di is to use factory closures/anonymous functions to build your objects. By registering a closure that returns a fully configured object, when resolved, your object will be lazy loaded as and when you need access to it.

Consider an object `Foo` that depends on another object `Bar`. The following will return an instance of `Foo` containing a member `bar` that contains an instance of `Bar`.

```php
class Foo
{
    public $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}

class Bar
{
    // ..
}

$container = new \Orno\Di\Container;

$container->add('foo', function() {
    $bar = new Bar;
    return new Foo($bar);
});

$foo = $container->get('foo');
```

### Automatic Dependency Resolution

Orno\Di has the power to automatically resolve your objects and all of their dependencies recursively by inspecting the type hints of your constructor arguments. Unfortunately, this method of resolution has a few small limitations but is great for smaller apps. First of all, you are limited to constructor injection and secondly, all injections must be objects.

```php
class Foo
{
    public $bar;

    public $baz;

    public function __construct(Bar $bar, Baz $baz)
    {
        $this->bar = $bar;
        $this->baz = $baz;
    }
}

class Bar
{
    public $bam;

    public function __construct(Bam $bam)
    {
        $this->bam = $bam;
    }
}

class Baz
{
    // ..
}

class Bam
{
    // ..
}
```

In the above code, `Foo` has 2 dependencies `Bar` and `Baz`, `Bar` has a further dependency of `Bam`. Normally you would have to do the following to return a fully configured instance of `Foo`.

```php
$bam = new Bam;
$baz = new Baz;
$bar = new Bar($bam);
$foo = new Foo($bar, $baz);
```

With nested dependencies, this can become quite cumbersome and hard to keep track of. With the container, to return a fully configured instance of `Foo` it is as simple as requesting `Foo` from the container.

```php
$container = new \Orno\Di\Container;

$foo = $container->get('Foo');
```

Existing definitions can also automatically resolve dependencies.

```php
$container = new \Orno\Di\Container;
$container=>add('Foo', 'Foo')
    ->autowire();

$foo = $container->get('Foo');
```

### Caching

By injecting [Orno\Cache](https://github.com/orno/cache) in to the container, it will cache any reflection based resolution for you meaning that there is less bootstrap/config in your development time.

```php
$config = [
    'servers' => [
        ['127.0.0.1', 11211, 12]
    ],
    'expiry' => '24h'
];

$memcached = new \Orno\Cache\Adapter\MemcachedAdapter($config);
$cache = new \Orno\Cache\Cache($memcached);

$container = new \Orno\Di\Container($cache);

$foo = $container->get('Foo');
```

In the above example, `Foo` will be reflected on by the container as there is no defined alias. The container will build a definition from that reflection and cache the result in Memcached for 24 hours.

### Configuration

As your project grows, so will your dependency map. At this point it may be worth abstracting your mappins in to a config file. You can store your mappings in an array, or any object implementing the `ArrayAccess` interface.  We recommend using [Orno\Config](https://github.com/orno/config), which allows you to store your mappings in either PHP arrays, XML or YAML.

**Note:** When using an array, or other ArrayAccess object, the mappings **must** be under a key named `di`.

```php
class Foo
{
    public $bar;

    public $baz;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function setBaz(Baz $baz)
    {
        $this->baz = $baz;
    }
}

class Bar
{
    // ..
}

class Baz
{
    // ..
}
```

To map the above code you may do the following.

```php
<?php // array_config.php

return [
    'Foo' => [
        'class'     => 'Foo',
        'arguments' => [
            'Bar'
        ],
        'methods'   => [
            'setBaz' => ['Baz']
        ]
    ],
    'Bar' => 'Bar',
    'Baz' => 'Baz'
];
```

#### Using Orno/Config

```php
$loader = new \Orno\Config\File\ArrayFileLoader('path/to/config/array_config.php', 'di');
$config = (new \Orno\Config\Repository)->addFileLoader($loader);

$container = new \Orno\Di\Container(null, $config);

$foo = $container->get('Foo');
```

#### Using Plain PHP

```php
$config = [
    'di' => require 'path/to/config/array_config.php',
];

$container = new \Orno\Di\Container(null, $config);

$foo = $container->get('Foo');
```


