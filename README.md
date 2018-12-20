# Pimcore MinifyBundle
Minify HTML, Stylesheet and Javascript

## Installation
```
composer require leoparden/minify
```

## Usage:
```twig
{{ minify({
   'test.css' : '/bundles/test/css/test.css',
   'test.js' : '/bundles/test/js/test.js',
   'all.less' : '/bundles/test/less/all.less',
   },
   {
       'async': true,
       'output': 'output_test',
       'lessVariables': {
           '@test' : '#ccc'
       },
       'media': 'all'
}) }}
```

