<?php
/**
 * @copyright 2015 iMarc LLC
 * @author Kevin Hamer [kh] <kevin@imarc.net>
 * @author Jeff Turcotte [jt] <jeff@imarc.net>
 * @license Apache (see LICENSE file)
 */

namespace Craft;

/**
 * Boost_DeploymentService is a Craft plugin service that provides the ability
 * to handle deployments (although not migrations) with Craft.
 */
class Boost_DeploymentService extends BaseApplicationComponent
{
    /**
     * sh() is just a wrapper for system() that provides sprintf()
     * functionality and keeps all of the execution in one place for
     * normalization/sanitization/logging that we may want to add later.
     *
     * @return string The result of the command
     */
    private function sh()
    {
        $cmd = call_user_func_array('sprintf', func_get_args());

        echo "\n= $cmd =\n";
        return system($cmd);
    }


    /**
     * like sh(), but a quiet version
     *
     * @return string The result of the command
     */
    private function quietSh()
    {
        $cmd = call_user_func_array('sprintf', func_get_args());

        system($cmd);
    }


    /**
     * deploy() to an environment. Should be the short name (dev/stage/prod)
     * and not the full path.
     *
     * @param string $env  Environment to deploy to.
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

        if ($env == 'prod') {
            $target_commit = $this->getCommit('stage');
        } else {
            $target_commit = 'master';
        }

        if (!$target_commit) {
            throw new Exception("Unable to determine stage environment commit.");
        }


        // prep the VCS directory
        $this->prepVCSCache($target_commit);

        $commit = $this->getCommit('cache');

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

        $this->sh("echo %s > %s", $commit, "$new_env/boost.commit");

        if (file_exists("$new_env/composer.json")) {
            $this->sh("composer install --ignore-platform-reqs -d %s", $new_env);
        }

        if (file_exists("$new_env/gulpfile.js")) {
            $original_cwd = getcwd();
            chdir($new_env);
            $this->sh("gulp");
            chdir($original_cwd);
        }

        // Run Before Deploy Hooks
        if ($settings->preDeploymentHooks) {
            $original_cwd = getcwd();
            chdir($new_env);
            $this->sh($settings->preDeploymentHooks);
            chdir($original_cwd);
        }

        // Remove any old old-dir.
        $live_env = $settings->envRoot . "/$env";
        $old_env = $settings->envRoot . "/old-$env";
        $this->sh("rm -rf \"$old_env\"");

        if ($env != $settings->canonicalEnv) {
            if ($settings->canonicalEnv == 'prod') {
                $src_db = $settings->dbName;
            } elseif ($settings->canonicalEnv == 'dev') {
                $src_db = $settings->devDbName;
            } elseif ($settings->canonicalEnv == 'stage') {
                $src_db = $settings->stageDbName;
            } else {
                throw new Exception("Canonical environment must be dev, stage, or prod.");
            }

            if ($env == 'prod') {
                $new_db = $settings->dbName;
            } elseif ($env == 'dev') {
                $new_db = $settings->devDbName;
            } elseif ($env == 'stage') {
                $new_db = $settings->stageDbName;
            } else {
                throw new Exception("Target environment must be dev, stage, or prod.");
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

        // Run After Deploy Hooks
        if ($settings->postDeploymentHooks) {
            $original_cwd = getcwd();
            chdir($live_env);
            $this->sh($settings->postDeploymentHooks);
            chdir($original_cwd);
        }

        echo "\nDeployment Complete.\n";
    }


    /**
     * getCraftVersion() determines the current version of Craft in a directory
     * by looking for the craft/app/Info.php file.
     *
     * @param string $craft_base_path  Directory to search.
     * @return string                  Version number.
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
     * getCommit() returns the currrent commit of a given environment or the VCS cache.
     *
     * @param  string $env  either an environment name or 'cache' for the cache.
     * @return string
     */
    public function getCommit($env)
    {
        $settings = craft()->plugins->getPlugin('boost')->getSettings();

        if ($env == 'cache') {

            $original_cwd = getcwd();
            chdir($settings->vcsCache);
            $commit = $this->sh("git rev-parse HEAD");
            chdir($original_cwd);

            return $commit;

        } else {

            if (strpos($env, '/') !== FALSE) {
                $file = "$env/boost.commit";
            } else {
                $file = $settings->envRoot . "/$env/boost.commit";
            }

            return file_exists($file) ? trim(file_get_contents($file)) : false;
        }
    }


    /**
     * prepVCSCache() makes sure the VCS cache is at the specified commit.
     * Currently only supports Subversion, but ideally I'd like to extend this
     * to support git later.
     *
     * @param mixed $commit
     * @return void
     */
    public function prepVCSCache($commit)
    {
        $settings = craft()->plugins->getPlugin('boost')->getSettings();

        if (!file_exists($settings->vcsCache)) {
            $this->sh("mkdir %s", $settings->vcsCache);
        }

        $original_cwd = getcwd();
        chdir($settings->vcsCache);

        if (!file_exists($settings->vcsCache . "/.git")) {
            $this->sh("git clone %s .", $settings->vcsUrl);
        }

        $this->sh('git checkout master');
        $this->sh('git pull -q origin master');
        $this->sh("git checkout %s", $commit);

        chdir($original_cwd);
    }


    /**
     * Prints the git log for the commits that will be deployed to the specified environment
     *
     * @param string $env  An environment name
     * @return void
     */
    public function showLog($env)
    {
        $settings = craft()->plugins->getPlugin('boost')->getSettings();

        $current_commit = $this->getCommit($env);

        $original_cwd = getcwd();
        chdir($settings->vcsCache);

        $this->quietSh('git fetch -q origin master:refs/remotes/origin/master');
        $this->quietSh('git log %s...origin/master', $current_commit);
        echo "\n";

        chdir($original_cwd);
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
            $commit = $this->getCommit($env);

            printf("%s: %s\n",
                preg_replace('#^' . preg_quote($settings->envRoot) . '/?#', '', $env),
                $commit
            );
        }
    }
}
