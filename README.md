# Assets version injector

No more browser cache surprise, no more forced page reloads to get latest styles, images, javascripts...

*Give me a HTML page or just a chunk, I will give you that back with md5 sum appended to asset URLs as a "version".*

```bash
composer require granam/assets-version
```

```php
$assetsVersionInjector = new \Granam\AssetsVersion\AssetsVersionInjector();
$contentWithVersions = $assetsVersionInjector->addVersionsToAssetLinks(
<<<HTML
<link href="/assets/css/style.css" rel="stylesheet" type="text/css"/>
<link href="/assets/css/perex.css" rel="stylesheet" type="text/css"/>
HTML
, __DIR__ . '/web');
/*
<link href="/assets/css/style.css?version=f395e65bcd4671fc77ce01c521c9e29a" rel="stylesheet" type="text/css"/>
<link href="/assets/css/perex.css?version=7ace2a8e3e0501c9539760a0e08d9378" rel="stylesheet" type="text/css"/>
*/
```