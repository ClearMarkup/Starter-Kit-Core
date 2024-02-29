<?php

namespace ClearMarkup\Classes;

use Rakit\Validation\Validator;

/**
 * Class Api
 * 
 * This class represents an API handler and extends the Core class.
 * It provides methods for handling CSRF tokens, authentication, authorization, request body validation, and response handling.
 */
class Api extends Core
{
    private $requestBody = [];
    protected $validator;

    /**
     * Api constructor.
     * 
     * Initializes the Api object and sets up the validator with custom validation rules.
     */
    public function __construct()
    {
        parent::__construct();
        $this->validator = new Validator;

        $validation_files = glob(__DIR__ . '/../controller/validatorRules/*');
        foreach ($validation_files as $file) {
            $validatorName = basename($file, '.php');
            $validatorClass = 'ClearMarkup\Validation\\ExtendsRules\\' . $validatorName;
            $this->validator->addValidator($validatorName, new $validatorClass());
        };
    }

    /**
     * Verify CSRF token.
     * 
     * This method checks if the CSRF token in the request header matches the one stored in the session.
     * If the token is invalid or missing, it throws an error.
     * 
     * @return $this
     */
    public function csrf()
    {
        $_token = filter_input(INPUT_SERVER, 'HTTP_X_CSRF_TOKEN', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!$_token || !isset($_SESSION['_token'])) {
            $this->error('Invalid CSRF token. ', 405);
        } else if ($_token !== $_SESSION['_token']) {
            $this->error('Invalid CSRF token.', 405);
        }
        return $this;
    }

    /**
     * Check authentication status.
     * 
     * This method checks if the user is logged in or not based on the provided status.
     * If the user is not logged in and $status is true, it throws an error.
     * If the user is already logged in and $status is false, it throws an error.
     * 
     * @param bool $status The expected authentication status (true for logged in, false for logged out)
     * @return $this
     */
    public function auth($status = true)
    {
        if ($status) {
            if (!self::$authInstance->isLoggedIn()) {
                $this->error('You are not logged in.');
            }
        } else {
            if (self::$authInstance->isLoggedIn()) {
                $this->error('You are already logged in.');
            }
        }
        return $this;
    }

    /**
     * Check user status.
     * 
     * This method checks if the user has a specific status.
     * If the user does not have the specified status, it throws an error.
     * 
     * @param string $status The expected user status
     * @return $this
     */
    public function isStatus($status)
    {
        switch ($status) {
            case 'normal':
                if (!self::$authInstance->isNormal()) {
                    $this->error('You are not authorized to access this resource.');
                }
                break;
        }
        return $this;
    }

    /**
     * Check user role.
     * 
     * This method checks if the user has a specific role.
     * If the user does not have the specified role, it throws an error.
     * 
     * @param string $role The expected user role
     * @return $this
     */
    public function hasRole($role)
    {
        if (!self::$authInstance->hasRole($role)) {
            $this->error('You are not authorized to access this resource.');
        }
        return $this;
    }

    /**
     * Set request body.
     * 
     * This method sets the request body based on the provided type.
     * If the type is 'json', it decodes the JSON payload from the request body.
     * If the type is 'get', it sets the request body as the $_GET array.
     * If the type is 'post', it sets the request body as the $_POST array.
     * 
     * @param string $type The type of request body ('json', 'get', 'post')
     * @return $this
     */
    public function requestBody($type = 'json')
    {
        if ($type === 'json') {
            $this->requestBody = json_decode(file_get_contents("php://input"), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON payload.');
            }
        } else if ($type === 'get') {
            $this->requestBody = $_GET;
        } else if ($type === 'post') {
            $this->requestBody = $_POST;
        } else {
            $this->error('Invalid request body type.');
        }

        return $this;
    }

    /**
     * Validate request body.
     * 
     * This method validates the request body against the provided rules using the validator.
     * If the validation fails, it throws an error with the validation errors.
     * If the validation passes, it sets the request body as the validated data.
     * 
     * @param array $rules The validation rules
     * @return $this
     */
    public function validate($rules)
    {
        $validation = $this->validator->validate($this->requestBody, $rules);
        if ($validation->fails()) {
            $this->error(['elements' => $validation->errors()->firstOfAll()]);
        }
        $this->requestBody = $validation->getValidData();
        return $this;
    }

    /**
     * Get request body.
     * 
     * This method returns the entire request body or specific input values from the request body.
     * If $input is an array, it returns an array with the specified input values.
     * If $input is a string, it returns the value of the specified input.
     * If $input is null, it returns the entire request body.
     * 
     * @param mixed $input The input value(s) to retrieve from the request body
     * @return mixed The request body or the specified input value(s)
     */
    public function getBody($input = null)
    {
        if ($input) {
            if (is_array($input)) {
                $data = [];
                foreach ($input as $key) {
                    $data[$key] = $this->requestBody[$key];
                }
                return $data;
            } else {
                return $this->requestBody[$input];
            }
        }
        return $this->requestBody;
    }

    /**
     * Send success response.
     * 
     * This method sends a success response with the provided data and response code.
     * If the data is a string, it sends a JSON response with the status and message.
     * If the data is an array, it sends a JSON response with the status and merged data.
     * 
     * @param mixed $data The response data
     * @param int $responseCode The HTTP response code
     * @return void
     */
    private function response($status, $data = [], $responseCode = 200)
    {
        http_response_code($responseCode);
        header('Content-Type: application/json');
        if (is_string($data)) {
            echo json_encode([
                'status' => $status,
                'message' => $data
            ]);
        } else {
            echo json_encode(array_merge(['status' => $status], $data));
        }
        exit;
    }

    /**
     * Send success response.
     * 
     * This method sends a success response with the provided data and response code.
     * 
     * @param mixed $data The response data
     * @param int $responseCode The HTTP response code
     * @return void
     */
    public function success($data = [], $responseCode = 200)
    {
        $this->response('success', $data, $responseCode);
    }

    /**
     * Send error response.
     * 
     * This method sends an error response with the provided data and response code.
     * 
     * @param mixed $data The response data
     * @param int $responseCode The HTTP response code
     * @return void
     */
    public function error($data = [], $responseCode = 200)
    {
        $this->response('error', $data, $responseCode);
    }
}
