<?php

namespace ClearMarkup\Classes;

use ClearMarkup\Classes\Core;
use ClearMarkup\Classes\View;
use AltoRouter;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

/**
 * This class represents the core functionality of the application.
 */
class App extends Core
{
    /**
     * App constructor.
     * 
     * Initializes the application by loading the environment variables, starting the session, and routing the request.
     */
    public function __construct()
    {
        try {
            $dotenv = Dotenv::createImmutable(self::getProjectRoot());
            $dotenv->load();
        } catch (InvalidPathException $e) {
            die('Please run <code>php cm init</code> to create the .env file.');
            exit;
        }


        session_name($_ENV['SESSION_NAME']);
        session_start();

        parent::__construct();

        // CORS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            // Pre-flight request. Exit successfully.
            exit(0);
        }

        // Show errors if debug is true
        if ($_ENV['DEBUG']) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        // Set the locale into the instance of gettext
        putenv('LC_ALL=' . $_ENV['LOCALE']);
        setlocale(LC_ALL, $_ENV['LOCALE']);
        bindtextdomain('messages', self::getProjectRoot() . 'locales');
        textdomain('messages');
        bind_textdomain_codeset('core', 'UTF-8');

        // Router
        $router = new AltoRouter();

        Tools::applyCallbackToFiles('php', self::getProjectRoot() . 'routes', function ($file) use ($router) {
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
