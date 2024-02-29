<?php

namespace ClearMarkup\Classes;

/**
 * The View class is responsible for rendering views and managing data for the views.
 */
class View extends Core
{

    /**
     * The data array to store the assigned data for the views.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Constructs a new View object.
     * 
     * Initializes the View object by assigning site and user data.
     */
    public function __construct()
    {
        global $config, $match;

        $this->assign('site', [
            'name' => $config->sitename,
            'language' => explode('_', $config->locale)[0],
            'url' => $config->url,
            'version' => $config->version,
        ]);

        $db = new Db;
        if (self::$authInstance->isLoggedIn()) {
            $user = $db->table('users')->filter('id', self::$authInstance->getUserId())->get([
                'id',
                'email',
                'username',
                'status'
            ]);

            $user['needsEmailConfirmation'] = $db->table('users_confirmations')->filter([
                'user_id' => self::$authInstance->getUserId(),
                'expires[>]' => time()
            ])->has();
        } else {
            $user = false;
        }

        $this->assign('user', $user);
        $this->assign('page', [
            'name' => $match['name'] ?? null,
        ]);
    }

    /**
     * Assigns data to a specific key in the data array.
     *
     * @param string $key The key to assign the data to.
     * @param mixed $value The value to assign.
     * @return View Returns the View object for method chaining.
     */
    public function assign($key, $value)
    {
        if (isset($this->data[$key]) && is_array($this->data[$key]) && is_array($value)) {
            $this->data[$key] = (object) array_merge($this->data[$key], $value);
        } else if (isset($this->data[$key]) && is_object($this->data[$key]) && is_array($value)) {
            $this->data[$key] = (object) array_merge((array) $this->data[$key], $value);
        } else {
            $this->data[$key] = is_array($value) ? (object) $value : $value;
        }
        return $this;
    }

    /**
     * Checks the authentication status and redirects if necessary.
     *
     * @param bool $status The authentication status to check.
     * @return View Returns the View object for method chaining.
     */
    public function auth($status = true)
    {
        if ($status) {
            if (!self::$authInstance->isLoggedIn()) {
                header('Location: /login');
                exit;
            }
        } else {
            if (self::$authInstance->isLoggedIn()) {
                header('Location: /');
                exit;
            }
        }
        return $this;
    }

    /**
     * Checks if the user has a specific status and redirects if necessary.
     *
     * @param string $status The status to check.
     * @return View Returns the View object for method chaining.
     */
    public function isStatus($status)
    {
        switch ($status) {
            case 'normal':
                if (!self::$authInstance->isNormal()) {
                    http_response_code(403);
                    header('Location: /login');
                    exit;
                }
                break;
        }
        return $this;
    }

    /**
     * Checks if the user has a specific role and redirects if necessary.
     *
     * @param string $role The role to check.
     * @return View Returns the View object for method chaining.
     */
    public function hasRole($role)
    {
        if (!self::$authInstance->hasRole($role)) {
            http_response_code(403);
            header('Location: /login');
            exit;
        }
        return $this;
    }

    /**
     * Renders a view with the assigned data.
     *
     * @param string $view The name of the view file to render.
     * @param int $status The HTTP status code to set.
     * @return void
     */
    public function render($view, $status = 200)
    {
        extract($this->data);

        require(__DIR__ . '/../views/' . $view . '.view.php');
        http_response_code($status);
        exit;
    }

    /**
     * Renders a Twig template with the assigned data.
     *
     * @param string $view The name of the Twig template to render.
     * @param int $status The HTTP status code to set.
     * @return void
     */
    public function twig($view, $status = 200)
    {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../views');
        $twig = new \Twig\Environment($loader);

        $twig->addGlobal('csrf', new \Twig\Markup(Tools::csrf(), 'UTF-8'));
        $twig->addGlobal('headCsrf', new \Twig\Markup(Tools::headCsrf(), 'UTF-8'));

        echo $twig->render($view . '.twig', (array) $this->data);
        http_response_code($status);
        exit;
    }
}
