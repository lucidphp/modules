# Resource locator

[![Author](http://img.shields.io/badge/author-iwyg-blue.svg?style=flat-square)](https://github.com/iwyg)
[![Source Code](http://img.shields.io/badge/source-lucid/resource-blue.svg?style=flat-square)](https://github.com/lucidphp/resource/tree/master)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/lucidphp/resource/blob/master/LICENSE.md)

[![Build Status](https://img.shields.io/travis/lucidphp/resource/master.svg?style=flat-square)](https://travis-ci.org/lucidphp/resource)
[![Code Coverage](https://img.shields.io/coveralls/lucidphp/resource/master.svg?style=flat-square)](https://coveralls.io/r/lucidphp/resource)
[![HHVM](https://img.shields.io/hhvm/lucid/resource/dev-master.svg?style=flat-square)](http://hhvm.h4cc.de/package/lucid/resource)

## Requirements

```
php >= 5.6
```

## Installation

```bash
> composer require lucid/resource
```

## Locating resources

```php
<?php
use Lucid\Resource\Locator;

$locator = new Loacor(['/path/to/dirA', '/path/to/dirB']);

foreach ($locator->locate('config.php') as $resource) {
	$resource->... // do stuff
}

```
## Resources

### File Resources

```php
<?php
use Lucid\Resource\FileResource;

$res = new FileResource($file);
$res->getResource(); // returns "/path/file"

$res->isValid($time) // if not mofified since $time
```

### Object Resources

```php
<?php
use Lucid\Resource\ObjectResource;
use Acme\MyObject;

$res = new ObjectResource(new MyObject);
$res->getResource(); // returns "/path/to/Acme/MyObject.php

$res->isValid($time) // if not mofified since $time
```

### Resource Collections
```php
<?php
use Lucid\Resource\Collection;
use Lucid\Resource\FileResource;
use Lucid\Resource\ObjectResource;

$resources = new Collection();

$resources->addResource(new ObjectResource($onject));
$resources->addResource(new FileResource($file));

$resources->addObjectResource($object);
$resources->addFileResource($file);

$resources->all(); // [ObjectResource $resource, FileResource $resource, ... ]

$resources->isValid(time()); // bool
```
