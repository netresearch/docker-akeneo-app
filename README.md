# Akeneo

This is an **alpine** image providing a ready built [Akeneo PIM](https://www.akeneo.com/) project, ready to run with MongoDB and optimized for best possible updatability. Therefor it provides a system which allows any packages to add Bundles, configs, or routing entries.

## What is Akeneo?

> A Product Information Management system (also known as PIM, PCM or Products MDM) is a tool that helps companies to centralize and harmonize all the technical and marketing information of their catalogs and products.

*(Quoted from [Akeneo Website](https://www.akeneo.com/))*

## How to use this image

### Run

This image only contains source files. To run Akeneo with it, PHP, a MySQL/MariaDB database and - both optionally an Apache and a MongoDB - are required.

Akeneo has several PHP platform dependencies which is why we recommend using our Akeneo specialized [PHP Docker images](https://hub.docker.com/r/netresearch/akeneo-php/) for that (available as PHP only and PHP+Apache).

It's best to run it using docker-compose. See [here](https://github.com/netresearch/docker-akeneo-app/blob/master/docker-compose.yml) for an example.

### Enironment variables

||Variable||Description||Default||
|DATABASE_DRIVER|The database driver|pdo_mysql|
|DATABASE_HOST|The database host name|localhost|
|DATABASE_PORT|The database port|3306|
|DATABASE_NAME|Name of the database|akeneo_pim|
|DATABASE_USER|Database user name|akeneo_pim|
|DATABASE_PASSWORD|Database users password|akeneo_pim|
|LOCALE|The locale for Akeneo|en|
|SECRET|Entropy string for security related operations|`hash('sha256', uniquid())`|
|PIM_CATALOG_PRODUCT_STORAGE_DRIVER|Set this to `doctrine/mongodb-odm` when you want to use MongoDB|-|
|MONGODB_SERVER|If set (e.g. to `mongodb://mongodb:27017`), the DoctrineMongoDBBundle will automatically activated.|-|
|MONGODB_DATABASE|If you use MongoDb you must provide a database name, which will be used for the product collections|-|
|CACHE_DIR|Path for symfony caches|/var/cache/akeneo|
|LOG_DIR|Path for the Akeneo logs|/var/log/akeneo| 
|UPLOAD_DIR|Path where uploads should be stored|/var/opt/akeneo/uploads/product|
|CATALOG_STORAGE_DIR|catalog_storage_dir|/var/opt/akeneo/file_storage/catalog|
|ARCHIVE_DIR|archive_dir|/var/opt/akeneo/archive|
|EXPORT_PATH|Set this to change the directory to where exports should go.|/var/opt/akeneo/exports|
|IMPORT_PATH|Set this to change the directory from where imports should be read.|/var/opt/akeneo/imports|

## Customizing Akeneo

This image provides a ready built Akeneo project (`composer install` already done) which is not meant to be customized by hacking around in its configuration and kernel - it rather provides a system to be customizable by other packages.

This system is provided by the [netresearch/akeneo-bootstrap](https://github.com/netresearch/docker-akeneo-app/tree/master/packages/netresearch/akeneo-bootstrap) package shipped with the installation. It replaces [incenteev/parameter-handler](https://github.com/Incenteev/ParameterHandler) which is registered for composers post-install-cmd and post-update-cmd and thus is invoked automatically to generate the AppKernel, (local) configs and (local) routings when packages are installed. Also it provides the binary `./bin/akeneo-bootstrap` which will do a fulle Akeneo installation for you. When this image is used with [netresearch/akeneo-php:apache](https://hub.docker.com/r/netresearch/akeneo-php) or [netresearch/akeneo-php](https://hub.docker.com/r/netresearch/akeneo-php) with `akeneo-php-entrypoint` set as entrypoint this binary will automatically be invoked on container start.

The following steps will be taken by akeneo-bootstrap:

1. After composer update/install and with ./bin/akeneo-bootstrap

    1. Generate the AppKernel (overriding, not replacing the original Akeneo AppKernel) with bundles required by any installed packages
    2. Generate (local) config and routing files invoking resources required by any installed packages
    3. Generate the parameters.yml from environment variables
    4. Fix PIM, ORO and Symfony requirements (adjust paths)
    
2. With ./bin/akeneo-bootstrap only

    4. Clear cache if required by any of the previous steps
    5. Boot the kernel
    6. Wait for the database to be up
    7. Seize the Akeneo installation (check requirements, install / upgrade DB, dump assets)
    8. Set export/import paths
    9. chown the directories required to be writable
    
### Adding Bundes and configs via composer packages

akeneo-bootstrap will scan all installed packages for settings in their composer.json allowing you to register Bundles, configs and routings to customize Akeneo like so:

```:json
{
   "name": "acme/akeneo-config",
   "version": "1.0.0",
   "require": {
       "akeneo-labs/custom-entity-bundle": "1.10.*"
   }
   "extra": {
       "netresearch/akeneo-bootstrap": {
            "bundles": [
                { "class": "Pim\\Bundle\\CustomEntityBundle\\PimCustomEntityBundle" }
            ],
            "routing": {
                "pim_customentity": {
                    "resource": "@PimCustomEntityBundle/Resources/config/routing.yml",
                    "prefix": "/reference-data"
                }
            }
        }
    }
}
```

#### Adding bundles

Bundles are to be registered as objects in an array in `extra.netresearch/akeneo-bootstrap.bundles` in your composer.json. Each of them must have a `class` property containing the bundle class and can have a `env` property containing an array of or an comma separated string with environment names:

```:json
{
   "name": "acme/akeneo-config",
   "version": "1.0.0",
   "extra": {
       "netresearch/akeneo-bootstrap": {
            "bundles": [
                {
                    "class": "Acme\\Bundle\\AllEnvsBundle"
                },
                {
                    "class": "Acme\\Bundle\\ProdOnlyBundle",
                    "env": "prod"
                },
                {
                    "class": "Acme\\Bundle\\DevAndTestOnlyBundle",
                    "env": ["dev", "test"]
                }
            ]
        }
    }
}
```

**Note** that *all* packages are scanned for this. This allows you to require a single package in the project which requires other packages that contain such configuration as well.

#### Adding configs

Config files are to be registered as objects in an array in `extra.netresearch/akeneo-bootstrap.config` in your composer.json. Each of them must have a `resource` property containing the file path and can have a `env` property containing an array of or an comma separated string with environment names:

```:json
{
   "name": "acme/akeneo-config",
   "version": "1.0.0",
   "extra": {
       "netresearch/akeneo-bootstrap": {
            "config": [
                {
                    "resource": "%kernel.root_dir%/../vendor/acme/akeneo-config/Resources/config/general.yml"
                },
                {
                    "resource": "%kernel.root_dir%/../vendor/acme/akeneo-config/Resources/config/prod.yml",
                    "env": "prod"
                },
                {
                    "resource": "%kernel.root_dir%/../vendor/acme/akeneo-config/Resources/config/dev.yml",
                    "env": ["dev", "test"]
                }
            ]
        }
    }
}
```

#### Adding configs

Routing files are to be registered as objects in an object in `extra.netresearch/akeneo-bootstrap.routing` in your composer.json. Each of them must have a `resource` property containing the file path and can have a `env` property containing an array of or an comma separated string with environment names. The keys are the keys under which they are registered in the routing_local.yml's:

```:json
{
   "name": "acme/akeneo-config",
   "version": "1.0.0",
   "extra": {
       "netresearch/akeneo-bootstrap": {
            "routing": {
                "all_envs": {
                    "resource": "@AllEnvsBundle/Resources/config/routing.yml"
                },
                "prod": {
                    "resource": "%kernel.root_dir%/../vendor/acme/akeneo-config/Resources/config/routing_prod.yml",
                    "env": "prod"
                },
                "test": {
                    "resource": "%kernel.root_dir%/../vendor/acme/akeneo-config/Resources/config/routing_dev.yml",
                    "env": ["dev", "test"]
                }
            }
        }
    }
}
```

### Requiring custom packages

To install your own packages you have to extend this image with a custom docker file - we suggest following [multistage build file](https://docs.docker.com/engine/userguide/eng-image/multistage-build/#use-multi-stage-builds) to keep the resulting image small:

```
FROM netresearch:akeneo-app as sources

FROM netresearch:akeneo-php as builder
COPY --from=sources /src/packages /src/packages
COPY --from=sources /var/www/html /var/www/html
WORKDIR /var/www/html

# You can use private packages by adding them into 
# /src/packages/{vendor}/{packagename}
# THOSE PACKAGES NEED TO HAVE A version TO BE SET IN THEIR composer.json
# see above for further information
COPY ./packages/acme/akeneo-config /src/packages/acme/akeneo-config

RUN composer require acme/akeneo-config

FROM alpine
COPY --from=builder /src/packages /src/packages
COPY --from=builder /var/www/html /var/www/html
```

## Development setup

In order to develop with Akeneo, you can use this [docker-compose.override.yml` similar to [this one]() 

## GitHub

If you have any problems, questions, feature requests or simply stars to give please visit the [GitHub repository](https://github.com/netresearch/docker-akeneo-app).
