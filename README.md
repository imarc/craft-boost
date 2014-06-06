README
======

**This plugin is vey early, and documentation is sparse or incomplete. Be warned.**

Boost is loosely inspired by Patton but worse. It supports

* Copying files from one environment to another,
* Copying the database from one environment to another, and
* Pulling new files for Subversion.

**It does not support**

* Migrations - including database migrations. All Database/Craft changes need to be made to the canonical environment only (dev initially, and prod once any content is put in prod.)
* There are some places that need to be fixed manually:
	* General Settings > Site URL
	* Every local asset path

Installation
------------

First, you **must** be using **per environment configuration settings**. I did not use Craft's built-in, domain-based switching as I couldn't figure out a robust way to make it cleanly treat both www and prod the same. How I did this on [https://code.imarc.net/filedetails.php?repname=bidneedham.org&path=%2Ftrunk%2Fcraft%2Fconfig%2Fgeneral.php BIDNeedham is on code.imarc.net]. Do **both general.php and db.php**.

Installing the plugin is straight forward: 

0. Put the files in craft/plugins/boost/.
0. Through Craft's admin panel, install the plugin.
0. Click on 'Boost', the now-hyperlinked name for the plugin to get to Boost's settings.
0. Fill out the settings. For the last setting, I'm using "craft/plugins craft/templates public/css public/fonts public/img public/js" as the directories to copy from SVN.

Usage
-----

To call this command, we use Craft's default CLI:

	dev/craft/app/etc/console/yiic deploy

To deploy to a specific environment, use

	dev/craft/app/etc/console/yiic deploy --env=stage
