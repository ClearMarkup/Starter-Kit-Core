<?php

use ClearMarkup\Classes\Core;
use ClearMarkup\Classes\View;

class App extends Core
{
    /**
     * App constructor.
     * 
     * Initializes the database and authentication instances.
     */
    public function __construct()
    {
        parent::__construct();

        // CORS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            // Pre-flight request. Exit successfully.
            exit(0);
        }

        // Config
        if (!file_exists(self::getProjectRoot() . 'config.php')) {
            die('Please run <code>php cm init</code> to create the config file.');
        } else {
            require_once(self::getProjectRoot() . 'config.php');
        }

        // Set custom session name
        session_name($config->session_name);
        session_start();

        // Show errors if debug is true
        if ($config->debug) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        // Set the locale into the instance of gettext
        putenv('LC_ALL=' . $config->locale);
        setlocale(LC_ALL, $config->locale);
        bindtextdomain('messages', self::getProjectRoot() . 'locales');
        textdomain('messages');
        bind_textdomain_codeset('core', 'UTF-8');

        // Router
        $router = new AltoRouter();

        self::applyCallbackToFiles('php', __DIR__ . '/routes', function ($file) use ($config, $router) {
            require_once($file);
        });

        $match = $router->match();

        if (is_array($match) && is_callable($match['target'])) {
            call_user_func_array($match['target'], $match['params']);
        } else {
            $view = new View;
            $view->render('404', 404);
        };
    }
}
