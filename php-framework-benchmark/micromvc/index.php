<?php
/**
 * Index
 *
 * This file defines the MVC processing logic for the system
 *
 * @package		MicroMVC
 * @author		David Pennington
 * @copyright	(c) 2010 MicroMVC Framework
 * @license		http://micromvc.com/license
 ********************************** 80 Columns *********************************
 */
if (isset($_GET['debug'])) {
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}

// System Start Time
define('START_TIME', microtime(true));

// System Start Memory
define('START_MEMORY_USAGE', memory_get_usage());

// Extension of all PHP files
define('EXT', '.php');

// Absolute path to the system folder
define('SP', realpath(dirname(__FILE__)). '/');

// Are we using windows?
define('WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// Include bootstrap
require('bootstrap.php');

//Is this an AJAX request?
define('AJAX_REQUEST',strtolower(server('HTTP_X_REQUESTED_WITH'))==='xmlhttprequest');

// What is the current domain?
define('DOMAIN', (server('HTTPS')=='on'?'https://':'http://').h(server('SERVER_NAME')?server('HTTP_HOST'):server('SERVER_NAME')));

// Custom init script?
if(config('init')) require('init.php');

// Get the current URL (defaulting to the index)
$url = ((url() ? explode('/', url()) : array()) + explode('/', config('index')));

// Get the controller and page
list($module, $controller) = array_slice($url, 0, 2);
$params = array_slice($url, 2);

// Routes allow custom URL
foreach(config('routes') as $regex => $path)
{
	if(preg_match("/^$regex/", url()))
	{
		list($module, $controller) = explode('/',$path);
		$params = $url;
		break;
	}
}

// Register events
foreach(config('events') as $event => $class)
{
	event($event, '', $class);
}

// Disabled, non-word (unsafe), and missing controllers are not allowed
if(in_array($module, config('disabled_modules')) OR preg_match('/\W/',$module.$controller) OR ! is_file(SP.$module.'/controller/'.$controller.'.php'))
{
	list($module, $controller) = explode('/', config('404'));
}

event('pre_controller');

// Load and run action
$controller = $module. '_controller_'. $controller;
$controller = new $controller();
call_user_func_array(array($controller, 'action'), $params);

// Render output
$controller->render();

event('post_controller', $controller);

if (!isset($_GET['debug'])) {
    die();
}

echo "Page rendered in <b>"
    . round((microtime(true) - START_TIME), 5) * 1000 ." ms</b>, taking <b>"
    . round((memory_get_usage() - START_MEMORY_USAGE) / 1024, 2) ." KB</b>";

$xhprof_data = xhprof_disable();

$XHPROF_ROOT = realpath(dirname(__FILE__) .'/..');
include_once $XHPROF_ROOT . "/xhprof/xhprof_lib/utils/xhprof_lib.php";
include_once $XHPROF_ROOT . "/xhprof/xhprof_lib/utils/xhprof_runs.php";

// save raw data for this profiler run using default
// implementation of iXHProfRuns.
$xhprof_runs = new XHProfRuns_Default();

// save the run under a namespace "xhprof_foo"
$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");

echo ", xhprof <a href=\"http://xhprof.pfb.example.com/xhprof_html/index.php?run=$run_id&source=xhprof_foo\">url</a>";

// End
