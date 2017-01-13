README
======

Boost is a Craft plugin that enables a very simplified deployment system based on our [typical process](http://handbook.imarc.net/deployment). While this software is functional, it should be treated as beta at best. It has strict requirements on how Craft is configured as well as the server environment.

Boost is designed to work with three environments – dev, stage, and prod. It automates copying assets and databases between evironments as well as pulling fresh code from a git repository.

* The database and assets are always copied from the current canonical environment (either dev or prod.)
* When deploying to dev or stage, the most recent commit from Git is used for VCS files.
* When deploying to prod, the current commit on stage is used for VCS files.

For more information, see the [iMarc Handbook](http://handbook.imarc.net/deployment).

Boost can

* Copy files from one Craft environment to another Craft environment on the same server,
* Copy Craft's database from one environment to another, and
* Pull new files from Git.

Boost **does not support**

* Migrations - including database migrations. All Database/Craft changes need to be made to the canonical environment only (dev initially, and prod once any content is put in prod.)


Craft Configuration
-------------------

First, you **must** be using **per environment configuration settings**. While it should be possible to use Craft's built-in, domain-based environments, we have only tested using Boost with custom code like this:

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

The benefit here is that environment is determined solely off of `CRAFT_BASE_PATH`, which avoids issues with domain aliases. **Both craft/config/general.php and craft/config/db.php** need to be setup like this.

### Keep Assets Relative

Second, if you want to avoid needing to reconfigure Craft after every deployment, you should use **relative paths when defining Asset Sources**. For example, you might use '../public/writable/documents' instead of '/var/www/example.com/prod/public/writable/documents'.


Installing and Configuring Boost
--------------------------------

Installing Boost is straight forward:

0. Put the plugin in craft/plugins/boost/.
0. Through Craft's admin panel, install the plugin.
0. Click on 'Boost', the now-hyperlinked name for the plugin to get to Boost's
   settings.
0. Fill out the settings.

Composer, NPM, and Gulp
-----------------------

If a `composer.json`, `package.json`, or `gulpfile.js` are found in the new environment, then `composer install --ignore-platform-reqs`, `npm install --production`, and `gulp` will be called respectively. For these files to exist in the new environment, you will **need to add them to the VCS Direcories setting below.** If you do not add them, then these command won't be automatically called.

Boost Settings
--------------

* **Environment Root** – a single directory containing all environments. For example, `/var/www/example.com/`.
* **Canonical Environment** – With environment to use as the canonical source for content. This is typically dev before a site is launched, and prod once the site is launched.
* **VCS URL** – This is the URL to checkout from version control. For example, `git@github.com:imarc/example-com.git`.
* **VCS Cache Directory** – Boost keeps a local clone of the repository in this directory. Typically, something like `/var/www/example.com/cache`.
* **VCS Directories** – This is a space separated list of relative paths to directories to copy from VCS into the environment as part of deployment. This might be something like
```
craft/plugins craft/templates public/css public/fonts public/img public/.htaccess composer.json
```

### Database Settings

For each database, you can specify the **name**, **user**, **password**, and **host**. All of these are optional except for the production database name. If The development or staging database names are omitted, then they use the production name prefixed with 'dev_' or 'stage_' respectively.

### Advanced Settings

* **Reset Ownership** – If specified, this will reset the ownership of files synced from version control to this value. (Example: `www-data:web`).
* **Reset File Permissions** – If specified, this is passed to `chmod` to reset the permissions of files and directories synced from version control. (Example: `g+rw`).
* **Keep Database** – Boost's default behavior is to delete and recreate each database so that it will only contain tables/records from the export. However, if you do not have permissions to do this, You can enable this setting and Boost will make sure the export includes `DROP TABLE` statements, so that it can replace the tables in the target database without needing to remake it. It does mean that tables that are part of the target environment and not the source environment will persist.
* **Reset Database Permissions** – If specified, database full database permissions are granted to this user when creating new databases. (Ex: `web@localhost`).
* **Delete Leftover Files** – If specified, after the VCS directories are synced, leftover files present in the VCS directories but not in the repository will be deleted.
    * **Protect From Deletion** – A space separated list of files and folders that exist in the VCS directories on the server to protect from being deleted. (Ex: `/writable*`)

### Hooks

* **Pre Deployment Hooks** – These are run within the new environment **before** it is deployed live. This is a good place to run any additional build steps.
* **Post Deployment Hooks** – These are run within the new environment **after** it is deployed live. This is a good place to run any kind of caching clearing.

Usage
-----

To call this command, we use Craft's default CLI:

    /var/www/<sitename>/dev/craft/app/etc/console/yiic boost

To deploy to a specific environment, use

    /var/www/<sitename>/dev/craft/app/etc/console/yiic boost deploy --env=stage

To deploy to a branch to a specific environment, use

    /var/www/<sitename>/dev/craft/app/etc/console/yiic boost deploy --env=stage --branch=branch-name

To show the log of commits that will be deployed to an env

    /var/www/<sitename>/dev/craft/app/etc/console/yiic boost log --env=stage

Shows the current versions of each of the environments

    /var/www/<sitename>/dev/craft/app/etc/console/yiic boost versions
