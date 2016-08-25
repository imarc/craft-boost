Changelog
=========

## 2.0.0

* Added settings for passwords per database
* Added advanced settings:
    * reset ownership
    * reset file permissions
    * keep database
    * reset database permissions
* Added deploy wrapper script
* Improved layout of plugin settings

### 1.2.0

* No longer leave behind old-* ddirectories
* Added support for custom dev and stage DB names; still will default to using dev_<PRODDB> and stage_<PRODDB>
* Added Pre Deployment Hooks and Post Deployment Hooks
    * These are called directly by `system()` as simple scripts.
* Added support for `package.json` and gulp
    * If `package.json` is available (copied into the new environment) then Boost will run `npm install --production`
    * If `gulpfile.js` is available (copied into the new environment) then Boost will run `gulp`

### 1.1.0

* Added support for composer; Boost will run composer install if a a composer.json is available. That means you likely need to add composer.json to your files to deploy to enable.

### 1.0.2

* Support added for PHP 5.3 (also added in 0.3.4)

## 1.0.0

* Boost is flagged as stable

### 0.3.3

* Boost is a craft-plugin, installable via composer installers

### 0.3.2

* Added help to console command
* Fixed notice thrown by boost versions

### 0.3.1

* Fixed issue with detached HEAD where pull would not be properly merged

### 0.3.0

* Altered 'deploy' command to 'boost' command with a deploy action
* Added log action
* Removed default action
* Made indentation consistent

### 0.2.1

* Cleanup of documentation and code in preparation for releasing Boost as open
  source.

### 0.2.0

* Dropping support for subversion, adding support for Git

### 0.1.0

* Support for subversion only initial implementation
