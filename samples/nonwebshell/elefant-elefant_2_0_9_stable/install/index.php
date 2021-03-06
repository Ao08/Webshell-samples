<?php

/**
 * Elefant CMS - http://www.elefantcms.com/
 *
 * Copyright (c) 2011 Johnny Broadway
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

if (file_exists ('../conf/installed') || file_exists ('installed')) {
	$_GET['step'] = 'finished';
}

function encrypt_pass ($plain) {
	$base = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	$salt = '$2a$07$';
	for ($i = 0; $i < 22; $i++) {
		$salt .= $base[rand (0, 61)];
	}
	return crypt ($plain, $salt . '$');
}

// check the error log for errors
error_reporting (E_ALL & ~E_NOTICE);
ini_set ('display_errors', 'Off');

// apparently we still have to deal with this... *sigh*
if (get_magic_quotes_gpc ()) {
	function stripslashes_gpc (&$value) {
		$value = stripslashes ($value);
	}
	array_walk_recursive ($_GET, 'stripslashes_gpc');
	array_walk_recursive ($_POST, 'stripslashes_gpc');
	array_walk_recursive ($_COOKIE, 'stripslashes_gpc');
	array_walk_recursive ($_REQUEST, 'stripslashes_gpc');
}

// get the global configuration
date_default_timezone_set('GMT');

require_once ('../lib/Functions.php');
require_once ('../lib/I18n.php');
require_once ('../lib/Form.php');
require_once ('../lib/DB.php');
require_once ('../lib/Template.php');
require_once ('../lib/Model.php');
require_once ('../lib/ExtendedModel.php');
require_once ('../apps/admin/models/Webpage.php');
require_once ('../apps/blocks/models/Block.php');
require_once ('../apps/user/models/User.php');
require_once ('../apps/admin/models/Versions.php');

// create core objects
$i18n = new I18n ('../lang', array ('negotiation_method' => 'http'));
$tpl = new Template ('UTF-8');
$tpl->cache_folder = '../cache';

$steps = array (
	'introduction',
	'license',
	'requirements',
	'database',
	'settings',
	'finished'
);
$_GET['step'] = (isset ($_GET['step']) && in_array ($_GET['step'], $steps)) ? $_GET['step'] : 'introduction';

$data = array ();

// handle the request
switch ($_GET['step']) {
	case 'introduction':
		if ($_SERVER['REQUEST_URI'] !== '/install/' && $_SERVER['REQUEST_URI'] !== '/install/index.php') {
			$data['subdir'] = true;
		} else {
			$data['subdir'] = false;
		}

		break;


	case 'requirements':
		// check permissions
		$apache = ($_SERVER['SERVER_SOFTWARE'] == 'Apache') ? true : (strpos (php_sapi_name (), 'apache') === 0) ? true : false;
		$data = array (
			'req' => array (
				__ ('PHP version must be 5.3+') => PHP_VERSION > '5.3',
				__ ('.htaccess file is missing from the site root. Please save the following file to your server:') . '</p><p><a href="https://raw.github.com/jbroadway/elefant/master/.htaccess" target="_blank">https://raw.github.com/jbroadway/elefant/master/.htaccess</a>' => (! $apache || ($apache && @file_exists ('../.htaccess'))),
				__ ('apps folder is not writeable. Please run:</p><p><tt>chmod 775 apps</tt>') => is_writeable ('../apps'),
				__ ('cache folder is not writeable. Please run:</p><p><tt>chmod -R 775 cache conf files lang layouts</tt>') => is_writeable ('../cache'),
				__ ('conf folder is not writeable. Please run:</p><p><tt>chmod -R 775 cache conf files lang layouts</tt>') => is_writeable ('../conf'),
				__ ('files folder is not writeable. Please run:</p><p><tt>chmod -R 775 cache conf files lang layouts</tt>') => is_writeable ('../files'),
				__ ('lang folder is not writeable. Please run:</p><p><tt>chmod -R 775 cache conf files lang layouts</tt>') => is_writeable ('../lang'),
				__ ('layouts folder is not writeable. Please run:</p><p><tt>chmod -R 775 cache conf files lang layouts</tt>') => is_writeable ('../layouts'),
				__ ('PHP PDO extension is missing.') => extension_loaded ('pdo'),
				__ ('Apache mod_rewrite extension must be installed.') => (php_sapi_name () != 'apache2handler' || ! function_exists ('apache_get_modules') || in_array ('mod_rewrite', apache_get_modules ()))
			),
			'passed' => 0
		);
		$data['passed'] = array_sum ($data['req']) == count ($data['req']);
		
		break;


	case 'database':
		$data['ready'] = false;
		$data['error'] = false;
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// got the settings, test and create schema
			$_POST['host'] = $_POST[$_POST['driver'] . '_host'];
			$_POST['port'] = $_POST[$_POST['driver'] . '_port'];
			$_POST['name'] = $_POST[$_POST['driver'] . '_name'];
			$_POST['user'] = $_POST[$_POST['driver'] . '_user'];
			$_POST['pass'] = $_POST[$_POST['driver'] . '_pass'];
			$_POST['prefix'] = $_POST[$_POST['driver'] . '_prefix'];

			DB::$prefix = $_POST['prefix'];

			if (! DB::open ($_POST)) {
				$data['error'] = DB::error ();
			} else {
				$data['error'] = false;

				// create the database
				$sqldata = sql_split (file_get_contents ('../conf/install_' . $_POST['driver'] . '.sql'));
				foreach ($sqldata as $sql) {
					if (! DB::execute ($sql)) {
						$data['error'] = DB::error ();
						DB::execute ('rollback');
						break;
					}
				}
				
				$wp = new Webpage ('index');
				Versions::add ($wp);
				$b = new Block ('members');
				Versions::add ($b);

				// write the settings
				if (! $data['error']) {
					$config = file_get_contents ('../conf/config.php');
					// good to replace database settings
					$_POST['pass'] = str_replace (['"', '$'], ['\"', '\$'], $_POST['pass']);
					$dbinfo = $tpl->render ('dbinfo', $_POST);
					$config = preg_replace ('/\[Database\].*\[Mongo\]/s', $dbinfo, $config);
					if (! file_put_contents ('../conf/config.php', $config)) {
						$data['error'] = __ ('Failed to write to conf/config.php');
					} else {
						$data['ready'] = true;
					}
				}
			}
		} else {
			// set some default values
			$_POST['mysql_host'] = 'localhost';
			$_POST['pgsql_host'] = 'localhost';
			$_POST['mysql_port'] = '3306';
			$_POST['pgsql_port'] = '5432';
			$_POST['sqlite_prefix'] = 'elefant_';
			$_POST['mysql_prefix'] = 'elefant_';
			$_POST['pgsql_prefix'] = 'elefant_';
		}
		break;


	case 'settings':
		$data['ready'] = false;
		$data['error'] = false;
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// got the settings, save them
			$config = file_get_contents ('../conf/config.php');
			$config = preg_replace ('/site_name = .*(?:\r\n|\r|\n)/', 'site_name = "' . $_POST['site_name'] . '"'. PHP_EOL, $config, 1);
			$config = preg_replace ('/email_from = .*(?:\r\n|\r|\n)/', 'email_from = "' . $_POST['email_from'] . '"'. PHP_EOL, $config, 1);
			$config = preg_replace ('/site_key = .*(?:\r\n|\r|\n)/', 'site_key = "' . md5 (uniqid (rand (), true)) . '"'. PHP_EOL, $config, 1);
			if (! file_put_contents ('../conf/config.php', $config)) {
				$data['error'] = __ ('Failed to write to conf/config.php');
			} else {
				// create the admin user now
				$conf = parse_ini_file ('../conf/config.php', true);
				$conf['Database']['master']['master'] = true;
				if (isset ($conf['Database']['master']['file'])) {
					$conf['Database']['master']['file'] = '../' . $conf['Database']['master']['file'];
				}

				DB::$prefix = $conf['Database']['prefix'];

				if (! DB::open ($conf['Database']['master'])) {
					$data['error'] = DB::error ();
				} else {
					$date = gmdate ('Y-m-d H:i:s');
					if (! DB::execute (
						"update `#prefix#user` set `email` = ?, `password` = ?, `name` = ? where `id` = 1",
						$_POST['email_from'],
						encrypt_pass ($_POST['pass']),
						$_POST['your_name']
					)) {
						$data['error'] = DB::error ();
					} else {
						$data['ready'] = true;
					}
				}
			}
		} else {
			// set some default values
			$_POST['site_name'] = 'Your Site Name';
			$_POST['email_from'] = 'you@example.com';
		}
		break;


	case 'finished':
		@umask (0000);
		@touch ('../conf/installed');
		break;
}

echo $tpl->render ($_GET['step'], $data);

/*
echo '<br clear="both" />';
info ($data);
//*/
