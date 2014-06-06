<?php
namespace Craft;

/**
 * Boost_DeploymentService is a Craft plugin service that provides the ability
 * to handle deployments (although not migrations) with Craft.
 *
 * @copyright 2014 iMarc LLC
 * @author Kevin Hamer [kh] <kevin@imarc.net>
 */
class Boost_DeploymentService extends BaseApplicationComponent
{
	/**
	 * sh() is just a wrapper for system() that provides sprintf()
	 * functionality and keeps all of the execution in one place for
	 * normalization/sanitization/logging that we may want to add later.
	 *
	 * @return void
	 */
	private function sh()
	{
		$cmd = call_user_func_array('sprintf', func_get_args());

		echo "\n= $cmd =\n";
		system($cmd);
	}

	/**
	 * deploy() to an environment. Should be the short name (dev/stage/prod)
	 * and not the full path.
	 *
	 * @param mixed $env
	 * @return void
	 */
	public function deploy($env)
	{
		$settings = craft()->plugins->getPlugin('boost')->getSettings();

		$src_env = $settings->envRoot . '/' . $settings->canonicalEnv;
		$new_env = $settings->envRoot . "/new-$env";

		// Remove possible stale directory
		$this->sh("rm -rf \"$new_env\"");

		// Copy from canonical environment to start new environment
		$this->sh("rsync -a $src_env/ $new_env");

		// pre the VCS directory
		$this->prepVCSCache('HEAD');

		// Copy from the VCS cache to the new environment
		$vcs_dirs = array_map('trim', explode(' ', $settings->vcsDirs));
		foreach ($vcs_dirs as $dir) {
			$filename = $settings->vcsCache .'/' . $dir;
			if (is_dir($filename)) {
				$filename .= '/';
			}
			$this->sh(sprintf(
				"rsync -a %s %s",
				$filename,
				$new_env . '/' . $dir
			));

			$this->sh("chown -R www-data:web \"$new_env/$dir\"");
			$this->sh("chmod -R g+rw \"$new_env/$dir\"");
		}


		// Remove any old old-dir.
		$live_env = $settings->envRoot . "/$env";
		$old_env = $settings->envRoot . "/old-$env";
		$this->sh("rm -rf \"$old_env\"");


		if ($env != $settings->canonicalEnv) {
			if ($settings->canonicalEnv == 'prod') {
				$src_db = $settings->dbName;
			} else {
				$src_db = $settings->canonicalEnv . '_' . $settings->dbName;
			}

			if ($env == 'prod') {
				$new_db = $settings->dbName;
			} else {
				$new_db = $env . '_' . $settings->dbName;
			}

			// Dump source database.
			$this->sh("mysqldump -n $src_db > \"$new_env/db.dump\"");

			// SITE IS OFFLINE STARTING NOW
			$this->sh("echo \"DROP DATABASE $new_db; CREATE DATABASE $new_db CHARACTER SET 'UTF8';\" | mysql");

			// Restore Database
			$this->sh("mysql $new_db < \"$new_env/db.dump\"");

			// Fix web permissions
			$this->sh("echo \"GRANT ALL ON $new_db.* TO 'web'@'localhost'; FLUSH PRIVILEGES;\" | mysql");
		}


		// Move current env to old and new env live.
		$this->sh("mv \"$live_env\" \"$old_env\"; mv \"$new_env\" \"$live_env\"");

		echo "\nDeployment Complete.\n";
	}

	/**
	 * getCraftVersion() determines the current version of Craft in a directory
	 * by looking for the craft/app/Info.php file.
	 *
	 * @param mixed $craft_base_path
	 * @return void
	 */
	public function getCraftVersion($craft_base_path)
	{
		$filename = "$craft_base_path/craft/app/Info.php";

		if (file_exists($filename)) {
			$file = fopen($filename, 'r');

			while ($line = fgets($file)) {
				if (preg_match("/define\('CRAFT_BUILD', '([0-9]+)'\);/", $line, $matches)) {
					return $matches[1];
				}
			}
		}

		return false;
	}

	/**
	 * getVCSVersion() looks for a 'boost.rev' file which Boost doesn't even
	 * create yet.
	 *
	 * @param mixed $craft_base_path
	 * @return void
	 */
	public function getVCSVersion($craft_base_path)
	{
		$filename = "$craft_base_path/boost.rev";
		if (file_exists($filename)) {
			return trim(file_get_contents($filename));
		}

		return false;
	}

	/**
	 * prepVCSCache() makes sure the VCS cache is at the specified revision.
	 * Currently only supports Subversion, but ideally I'd like to extend this
	 * to support git later.
	 *
	 * @param mixed $rev
	 * @return void
	 */
	public function prepVCSCache($rev)
	{
		$settings = craft()->plugins->getPlugin('boost')->getSettings();

		if (!file_exists($settings->vcsCache)) {
			$this->sh(sprintf(
				"svn checkout -r %s %s %s",
				$rev,
				$settings->vcsUrl,
				$settings->vcsCache
			));
		} else {
			$this->sh(sprintf(
				"svn up -r %s %s",
				$rev,
				$settings->vcsCache
			));
		}
	}

	/**
	 * showVersions() displays all craft and VCS versions.
	 *
	 * @return void
	 */
	public function showVersions()
	{
		$settings = craft()->plugins->getPlugin('boost')->getSettings();

		foreach (glob($settings->envRoot . "/*", GLOB_ONLYDIR) as $env) {
			$craft_ver = $this->getCraftVersion($env);
			$vcs_ver   = $this->getVCSVersion($env);

			if ($vcs_ver) {
				echo "$env\t$craft_ver\tr$vcs_ver\n";
			} else {
				echo "$env\t$craft_ver\n";
			}
		}
	}
}
