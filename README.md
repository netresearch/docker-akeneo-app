This is

1. The composer package [netresearch/akeneo-bootstrap](https://packagist.org/packages/netresearch/akeneo-bootstrap) to bootstrap Akeneo projects and configure them from other packages rather than via their own config files (better updatability).
2. The Docker image [netresearch/akeneo-app](https://hub.docker.com/r/netresearch/akeneo-app/) providing a ready built [Akeneo PIM](https://www.akeneo.com/) project, ready to run with MongoDB and optimized for best possible updatability utilizing [akeneo-bootstrap](#composer-package-akeneo-bootstrap).

# Contents

- [Composer package](#composer-package)
  - [`akeneo-project`](#akeneo-project)
    - [Installation](#installation)
    - [Creating a project](#creating-a-project)
    - [Up/downgrading a project](#updowngrading-a-project)
  - [`akeneo-bootstrap`](#akeneo-bootstrap)
    - [Environment variables](#environment-variables)
    - [Customizing Akeneo from other packages](#customizing-akeneo-from-other-packages)
      - [Adding bundles](#adding-bundles)
      - [Adding configs](#adding-configs)
      - [Adding routings](#adding-routings)
- [Docker image](#docker-image)
  - [Run](#run)
  - [Environment variables](#environment-variables)
  - [Customize Akeneo](#customize-akeneo)

# Composer package

The common way to install Akeneo is to create a composer project from [akeneo/pim-community-standard](https://packagist.org/packages/akeneo/pim-community-standard) which has major drawbacks:

1. Akeneo [still](https://www.akeneo.com/forums/topic/please-add-akeneopim-community-dev-at-packagist/) didn't publish its core package to packagist which thus needs to be provided as composer repository. As the corresponding github repository has tons of branches and tags using it for composer is firstly damn slow and secondly makes composer reach the github API limits which can hardly be handled in any environment where you don't want to expose your github API key. Also there is no way for other packages to require *akeneo/pim-community-dev* as long as the github repository is not added in the root composer.json. 
2. `composer create-project` naturally creates the project once - if you want to update to a newer version of the original project there is no easy way to this with composer. Rather you'll be forced to update the copied sources following the upgrade instructions which is - at least for us - not what we would 
3. If you need to change Akeneo configs, routings or add Bundles you have to touch the code generated by `composer create-project` which makes updating even harder.

You could also download a ready built Akeneo project - the updating issues however remain.

This package provides a mechanism to work around the problems above:

1. It provides the plain shell script [akeneo-project](#akeneo-project) to create or upgrade an Akeneo project.
2. It provides the command line application [akeneo-bootstrap](#akeneo-bootstrap) which generates configuration in the project from according instructions in installed packages and sets Akeneo up for running.

It replaces [incenteev/parameter-handler](https://github.com/Incenteev/ParameterHandler) which is registered for composers post-install-cmd and post-update-cmd and thus is invoked automatically to generate the AppKernel, configs and routings when packages are installed (steps 1 - 4 of [akeneo-bootstrap](#akeneo-bootstrap) command).

## `akeneo-project`

Use the `akeneo-project` script to create or upgrade an Akeneo project. It also installs netresearch/akeneo-bootstrap into the project (which automatically invokes 1 - 4 of the [akeneo-bootstrap](#akeneo-bootstrap) command).

### Installation

The script is standalone but as it requires PHP and composer anyway you better install it with composer:

```bash
composer global require netresearch/akeneo-bootstrap
akeneo-project -h
```

### Creating a project

```bash
akeneo-project create -v 1.7.6
```

### Up/downgrading a project

```bash
akeneo-project upgrade -v 1.7.6
```

## `akeneo-bootstrap`

The `akeneo-bootstrap` command is shipped with the composer package `netresearch/akeneo-bootstrap` which is also installed by [akeneo-project](#akeneo-project). It executes the following steps:

1. Generate the AppKernel (overriding, not replacing the original Akeneo AppKernel) with bundles required by any installed packages
2. Generate (local) config and routing files invoking resources required by any installed packages
3. Generate the parameters.yml from environment variables
4. Fix PIM, ORO and Symfony requirements (adjust paths)
5. Clear cache if required by any of the previous steps
6. Boot the kernel
7. Wait for the database to be up
8. Ensure the Akeneo installation (check requirements, install / upgrade DB, dump assets)
9. Set export/import paths
10. Link static directories if required (Akeneo sometimes doesn't use the configured directories but fixed paths like `app/logs` - this step symlinks those directories to the configured ones like [LOG_DIR](#environment-variables))
11. chown the directories required to be writable from web (see [WEB_USER](#environment-variables))

### Environment variables

[akeneo-bootstrap](#akeneo-bootstrap) uses the following environment variables to configure Akeneo:

| Variable | Description | Default |
| --- | --- | --- |
| DATABASE_DRIVER | The database driver | pdo_mysql |
| DATABASE_HOST | The database host name | localhost |
| DATABASE_PORT | The database port | 3306 |
| DATABASE_NAME | Name of the database | akeneo_pim |
| DATABASE_USER | Database user name | akeneo_pim |
| DATABASE_PASSWORD | Database users password | akeneo_pim |
| LOCALE | The locale for Akeneo | en |
| SECRET | Entropy string for security related operations | `hash('sha256', uniquid())` |
| PIM_CATALOG_PRODUCT_STORAGE_DRIVER | Set this to `doctrine/mongodb-odm` when you want to use MongoDB | - |
| MONGODB_SERVER | If set (e.g. to `mongodb://mongodb:27017`), the DoctrineMongoDBBundle will automatically activated. | - |
| MONGODB_DATABASE | If you use MongoDb you must provide a database name, which will be used for the product collections | - |
| CACHE_DIR | Path for symfony caches | /var/cache/akeneo |
| LOG_DIR | Path for the Akeneo logs | /var/log/akeneo | 
| UPLOAD_DIR | Path where uploads should be stored | /var/opt/akeneo/uploads/product |
| CATALOG_STORAGE_DIR | catalog_storage_dir | /var/opt/akeneo/file_storage/catalog |
| ARCHIVE_DIR | archive_dir | /var/opt/akeneo/archive |
| EXPORT_PATH | Set this to change the directory to where exports should go. | /var/opt/akeneo/exports |
| IMPORT_PATH | Set this to change the directory from where imports should be read. | /var/opt/akeneo/imports |
| WEB_USER | User name to be set as owner for directories that need to be writable by Akeneo from Web | www-data.www-data |

The configuration via environment variables was chosen because the package is primarily targeted at installations in Docker containers. If you don't use such and don't want to clutter your environment variables with the above, you could put them into an [.env file](https://docs.docker.com/compose/env-file/) and run [akeneo-bootstrap](#akeneo-bootstrap) and composer commands like this:

```bash
eval $(cat .env) composer update
```

## Customizing Akeneo from other packages

[akeneo-bootstrap](#akeneo-bootstrap) will scan all installed packages for settings in their composer.json allowing you to register Bundles, configs and routings to customize Akeneo like so:

```json
{
   "name": "acme/akeneo-config",
   "version": "1.0.0",
   "require": {
       "akeneo-labs/custom-entity-bundle": "1.10.*"
   },
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

After installing or updating this package *akeneo-bootstrap* will automatically update the Kernel and local configuration and routing files to add the configured resources.

*Please note* that changes to this composer.json files only take effect when the package is actually updated or installed - if you only change it in the vendor package nothing will happen.

### Adding bundles

Bundles are to be registered as objects in an array in `extra.netresearch/akeneo-bootstrap.bundles` in your composer.json. Each of them must have a `class` property containing the bundle class and can have a `env` property containing an array of or an comma separated string with environment names:

```json
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

### Adding configs

Config files are to be registered as objects in an array in `extra.netresearch/akeneo-bootstrap.config` in your composer.json. Each of them must have a `resource` property containing the file path and can have a `env` property containing an array of or an comma separated string with environment names:

```json
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

### Adding routings

Routing files are to be registered as objects in an object in `extra.netresearch/akeneo-bootstrap.routing` in your composer.json. Each of them must have a `resource` property containing the file path and can have a `env` property containing an array of or an comma separated string with environment names. The keys are the keys under which they are registered in the routing_local.yml's:

```json
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

# Docker image

The Docker image [netresearch/akeneo-app](https://hub.docker.com/r/netresearch/akeneo-app) is an alpine image containing an Akeneo project setup with [akeneo-project](#akeneo-project) in `/var/www/html` and the `akeneo-project` script itself in `/opt/akeneo-bootstrap/bin/akeneo-project`.

When this image is used with [netresearch/akeneo-php:apache](https://hub.docker.com/r/netresearch/akeneo-php) or [netresearch/akeneo-php](https://hub.docker.com/r/netresearch/akeneo-php) with `akeneo-php-entrypoint` set as entrypoint [akeneo-bootstrap](#akeneo-bootstrap) will automatically be invoked on container start.

## Run

This image only contains source files. To run Akeneo with it, PHP, a MySQL/MariaDB database and - both optionally an Apache and a MongoDB - are required.

Akeneo has several PHP platform dependencies which is why we recommend using our Akeneo specialized [PHP Docker images](https://hub.docker.com/r/netresearch/akeneo-php/) for that (available as PHP only and PHP+Apache).

It's best to run it using docker-compose. See [here](https://github.com/netresearch/docker-akeneo-app/blob/master/docker-compose.yml) for an example.

In order to **develop with Akeneo**, you can additionally use this [docker-compose.override.yml](https://github.com/netresearch/docker-akeneo-app/blob/master/docker-compose.override.yml) along with a Dockerfile like the one below.

## Environment variables

As those for [akeneo-bootstrap](#environment-variables).

## Customize Akeneo

This image provides a ready built Akeneo project (`composer install` already done) which is not meant to be customized by hacking around in its configuration and kernel - it rather uses [netresearch/akeneo-bootstrap](#composer-package) to be [customizable by other packages](#customizing-akeneo-from-other-packages).

To install your own packages you should extend this image with a custom docker file - we suggest following [multistage build file](https://docs.docker.com/engine/userguide/eng-image/multistage-build/#use-multi-stage-builds) to keep the resulting image small:

```Dockerfile
FROM netresearch:akeneo-app as sources

FROM netresearch:akeneo-php as builder
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

# If you will mount a local directory to /var/www/html
# which is what you'll likely do during development
# it is a good idea to also copy akeneo-project
# (see docker-compose.override.yml for how to invoke)
COPY --from=sources /usr/local/bin/akeneo-project /opt/akeneo-bootstrap/bin/akeneo-project
```

# GitHub

If you have any problems, questions, feature requests or simply stars to give please visit the [GitHub repository](https://github.com/netresearch/docker-akeneo-app).
