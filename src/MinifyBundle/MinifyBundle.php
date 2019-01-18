<?php

namespace MinifyBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class MinifyBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    const PACKAGE_NAME = 'leoparden/minify';

    public function getComposerPackageName()
    {
        return self::PACKAGE_NAME;
    }
    
    public function getVersion()
    {
        return 'v1.0.1';
    }
}
