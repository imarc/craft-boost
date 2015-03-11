README
======

Boost is a Craft plugin that enables a very simplified deployment system based
on our [typical process](http://handbook.imarc.net/deployment). While this
software is functional, it should be treated as beta at best. It has strict
requirements on how Craft is configured as well as the server environment.

Boost can

* Copy files from one Craft environment to another Craft environment on the same
  server,
* Copy Craft's database from one environment to another, and
* Pull new files from Git.

Boost **does not support**

* Migrations - including database migrations. All Database/Craft changes need to
  be made to the canonical environment only (dev initially, and prod once any
  content is put in prod.)


Craft Configuration
-------------------

First, you **must** be using **per environment configuration settings**. While
it should be possible to use Craft's built-in, domain-based environments, we
have only tested using Boost with custom code like this:

```php
/* craft/config/general.php */

$env = preg_replace('#^.*/#', '', dirname(CRAFT_BASE_PATH));

$config = [
        'omitScriptNameInUrls' => true,
        'maxUploadFileSize' => 104857600
];

switch ($env) {
        case 'dev':
                $config = array_merge($config, [
                        'siteUrl' => 'http://dev.example.com',
                        'devMode' => true,
                        'useCompressedJs' => false,
                ]);
                break;
        case 'stage':
                $config = array_merge($config, [
                        'siteUrl' => 'http://stage.example.com',
                        'useCompressedJs' => false,
                ]);
                break;
        case 'prod':
                $config = array_merge($config, [
                        'siteUrl' => 'http://www.example.com',
                ]);
                break;
        default:
                die("Unfortunately, the server is misconfigured. Please review the configuration in config/general.php.");
}

return $config;
```

The benefit here is that environment is determined solely off of
`CRAFT_BASE_PATH`, which avoids issues with domain aliases. **Both
craft/config/general.php and craft/config/db.php** need to be setup like this.

### Keep Assets Relative

Second, if you want to avoid needing to reconfigure Craft after every
deployment, you should use **relative paths when defining Asset Sources**. For
example, you might use '../public/writable/documents' instead of
'/var/www/example.com/prod/public/writable/documents'.


Installing and Configuring Boost
--------------------------------

Installing Boost is straight forward:

0. Put the plugin in craft/plugins/boost/.
0. Through Craft's admin panel, install the plugin.
0. Click on 'Boost', the now-hyperlinked name for the plugin to get to Boost's
   settings.
0. Fill out the settings.

Boost Settings
--------------

* **Environment Root** – a single directory containing all environments. For
  example, `/var/www/example.com/`.
* **Canonical Environment** – With environment to use as the canonical source
  for content. This is typically dev before a site is launched, and prod once
  the site is launched.
* **Base Database Name** – This database name is used for the **proudction**
  environment. For dev and stage, the database name will be prefixed with
  dev\_ or stage\_ respectively.
* **VCS URL** – This is the URL to checkout from version control. For example,
  `git@github.com:imarc/example-com.git`.
* **VCS Cache Directory** – Boost keeps a local clone of the repository in this
  directory. Typically, something like `/var/www/example.com/cache`.
* **VCS Directories** – This is a space separated list of relative paths to
  directories to copy from VCS into the environment as part of deployment. This
  might be something like

```
craft/plugins craft/templates public/css public/fonts public/img public/.htaccess
```

Usage
-----

To call this command, we use Craft's default CLI:

    dev/craft/app/etc/console/yiic deploy

To deploy to a specific environment, use

    dev/craft/app/etc/console/yiic deploy --env=stage
