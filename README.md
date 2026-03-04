# content-items-manager-bundle

Installation
=

Add the repository to the composer.json file:
```
    "repositories": {
        "mugoweb/content-items-manager-bundle": {
            "type": "vcs",
            "url": "https://github.com/mugoweb/content-items-manager-bundle"
        }
    }
```

Install the package:
```composer require mugoweb/content-items-manager-bundle:dev-master```

Enable the bundle in _config/bundles.php_:
```MugoWeb\ContentItemsManagerBundle\MugoWebContentItemsManagerBundle::class => ['all' => true],```

The routes should be added to a new file (e.g.: _config/routes/mugoweb_content_items_manager_bundle.yaml_):
```
mugoweb_content_items_manager_bundle:
    resource: '@MugoWebContentItemsManagerBundle/Resources/config/routes.yaml'
```