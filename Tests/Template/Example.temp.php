<?php

use Skyline\Render\Compiler\Template\MutableTemplate;
use Skyline\Render\Info\RenderInfoInterface;

// Templates must end with .temp.php
// If $this is a mutable template, it should register name, catalog, attributes and tags.
// Otherwise it must return a callable to be performed on render.
// If the callable is a closure, it's gonna bound to the render instance itself.

if($this instanceof MutableTemplate) {
    $this->setName("Test")
    ->setCatalogName("Catalog")
    ->addTag("my")
    ->addTag("Thomas");
    return true;
}
return function(RenderInfoInterface $info) {
    echo "OK";
};