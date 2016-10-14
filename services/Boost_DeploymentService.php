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
        $result = system($cmd, $retval);

        if ($retval) {
            throw new Exception(sprintf('Halting: error running "%s"', $cmd));
        } else {
            return $result;
        }
    }


    /**
     * like sh(), but a quiet version
     *
     * @return string The result of the command
     */
    private function quietSh()
    {
        $cmd = call_user_func_array('sprintf', func_get_args());

        $result = system($cmd);

        if ($retval) {
            throw new Exception(sprintf('Halting: error running "%s"', $cmd));
        } else {
            return $result;
        }
    }

    static private function getEnvSettings($env)
    {
        $cfg = craft()->plugins->getPlugin('boost')->getSettings();

        switch ($env) {
            case 'prod':
                $settings = array(
                    'db' => $cfg->dbName,
                    'user' => $cfg->dbUser,
                    'pass' => $cfg->dbPass,
                    'host' => $cfg->dbHost
                );
                break;
            case 'dev':
                $settings = array(
                    'db' => $cfg->devDbName ?: 'dev_' . $cfg->dbName,
                    'user' => $cfg->devDbUser,
                    'pass' => $cfg->devDbPass,
                    'host' => $cfg->devDbHost
                );
                break;
            case 'stage':
                $settings = array(
                    'db' => $cfg->stageDbName ?: 'stage_' . $cfg->dbName,
                    'user' => $cfg->stageDbUser,
                    'pass' => $cfg->stageDbPass,
                    'host' => $cfg->stageDbHost
                );
                break;
            default:
                throw new Exception("Environment must be dev, stage, or prod, not $env.");
                break;
        }

        $settings['root'] = $cfg->envRoot . "/$env";

        $mysql_args = array();
        if ($settings['host']) {
            $mysql_args[] = '-h ' . $settings['host'];
        }
        if ($settings['user']) {
            $mysql_args[] = '-u ' . $settings['user'];
        }
        if ($settings['pass']) {
            $mysql_args[] = '--password="' . $settings['pass'] . '"';
        }
        $settings['mysql_args'] = implode(' ', $mysql_args);

        return (object) $settings;
    }

    /**
     * deploy() to an environment. Should be the short name (dev/stage/prod)
     * and not the full path.
     *
     * @param string $env
     *      Environment to deploy to.

     * @param boolean $copyDatabase
     *      Whether or not to copy the databsae from the canonical env.

     * @return void
     */
    public function deploy($env, $copyDatabase = true)
    {
        $cfg = craft()->plugins->getPlugin('boost')->getSettings();

        $src_cfg = static::getEnvSettings($cfg->canonicalEnv);
        $new_cfg = static::getEnvSettings($env);

        $tmp_root = $cfg->envRoot . "/new-$env";

        // Remove possible stale directory
        $this->sh("rm -rf \"$tmp_root\"");

        // Copy from canonical environment to start new environment
        $this->sh("rsync -a {$src_cfg->root}/ $tmp_root");

        // Determine target commit
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
        $vcs_dirs = array_map('trim', explode(' ', $cfg->vcsDirs));
        foreach ($vcs_dirs as $dir) {
            $filename = $cfg->vcsCache .'/' . $dir;
            if (is_dir($filename)) {
                $filename .= '/';
            }
            $this->sh(sprintf(
                "rsync -a %s %s",
                $filename,
                $tmp_root . '/' . $dir
            ));

            if ($cfg->resetOwnership) {
                $this->sh("chown -R {$cfg->resetOwnership} \"$tmp_root/$dir\"");
            }

            if ($cfg->resetPermissions) {
                $this->sh("chmod -R {$cfg->resetPermissions} \"$tmp_root/$dir\"");
            }
        }

        // Save commit
        $this->sh("echo %s > %s", $commit, "$tmp_root/boost.commit");

        // Run Composer if composer.json exists
        if (file_exists("$tmp_root/composer.json")) {
            $this->sh("composer install --ignore-platform-reqs -d %s", $tmp_root);
        }

        // Run NPM if package.json exists
        if (file_exists("$tmp_root/package.json")) {
            $original_cwd = getcwd();
            chdir($tmp_root);
            $this->sh("npm install --production");
            chdir($original_cwd);
        }

        // Run Pre Deployment Hooks
        if ($cfg->preDeploymentHooks) {
            $original_cwd = getcwd();
            chdir($tmp_root);
            $this->sh($cfg->preDeploymentHooks);
            chdir($original_cwd);
        }

        // Remove any old old-dir.
        $old_root = $cfg->envRoot . "/old-$env";
        $this->sh("rm -rf \"$old_root\"");

        // Copy canonical database if not deploying to the canonical environment
        if ($copyDatabase && $env != $cfg->canonicalEnv) {

            $dump_cmd = "mysqldump " . $src_cfg->mysql_args;

            if ($cfg->keepDatabase) {
                $dump_cmd .= " --add-drop-table";
            }

            $import_cmd = "mysql " . $new_cfg->mysql_args;

            // Dump source database.
            $this->sh("$dump_cmd -n {$src_cfg->db} > \"$tmp_root/db.dump\"");


            /**
             * This version of Boost is CUSTOMIZED. The dump_commmand creates
             * DROP TABLE commands are part of the dump, and it is loaded
             * directly back into the database like that.
             */
            if (!$cfg->keepDatabase) {
                $this->sh("$import_cmd -r \"DROP DATABASE {$new_cfg->db}; CREATE DATABASE {$new_cfg->db} CHARACTER SET 'UTF8';\"");
            }

            // Restore Database
            $this->sh("$import_cmd {$new_cfg->db} < \"$tmp_root/db.dump\"");

            if ($cfg->resetDbPermissions) {
                $this->sh("$import_cmd -r \"GRANT ALL ON {$new_cfg->db}.* TO {$cfg->resetDbPermissions}; FLUSH PRIVILEGES;\"");
            }
        }


        // Move live root folder to old and new root live.
        $this->sh("mv \"{$new_cfg->root}\" \"$old_root\"");
        $this->sh("mv \"$tmp_root\" \"{$new_cfg->root}\"");

        // Run After Deploy Hooks
        if ($cfg->postDeploymentHooks) {
            $original_cwd = getcwd();
            chdir($new_cfg->root);
            $this->sh($cfg->postDeploymentHooks);
            chdir($original_cwd);
        }

        // Clean up temporary old-* directory.
        $this->sh("rm -rf \"$old_root\"");

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
        $cfg = craft()->plugins->getPlugin('boost')->getSettings();

        if ($env == 'cache') {

            $original_cwd = getcwd();
            chdir($cfg->vcsCache);
            $commit = $this->sh("git rev-parse HEAD");
            chdir($original_cwd);

            return $commit;

        } else {

            if (strpos($env, '/') !== FALSE) {
                $file = "$env/boost.commit";
            } else {
                $file = $cfg->envRoot . "/$env/boost.commit";
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
        $cfg = craft()->plugins->getPlugin('boost')->getSettings();

        if (!file_exists($cfg->vcsCache)) {
            $this->sh("mkdir %s", $cfg->vcsCache);
        }

        $original_cwd = getcwd();
        chdir($cfg->vcsCache);

        if (!file_exists($cfg->vcsCache . "/.git")) {
            $this->sh("git clone %s .", $cfg->vcsUrl);
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
        $cfg = craft()->plugins->getPlugin('boost')->getSettings();

        $current_commit = $this->getCommit($env);

        $original_cwd = getcwd();
        chdir($cfg->vcsCache);

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
        $cfg = craft()->plugins->getPlugin('boost')->getSettings();

        foreach (glob($cfg->envRoot . "/*", GLOB_ONLYDIR) as $env) {
            $commit = $this->getCommit($env);

            printf("%s: %s\n",
                preg_replace('#^' . preg_quote($cfg->envRoot) . '/?#', '', $env),
                $commit
            );
        }
    }
}
