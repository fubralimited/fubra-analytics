<?php

namespace GA;

use \Google_Client;
use \FA\Options;
use \FA\Config;

/**
* Google API oAuth class
*/
class Auth {

    /**
     * FA Options instance
     * @var object
     */
    private $options;

    /**
     * FA Config instance
     * @var object
     */
    private $config;

    /**
     * OAuth token
     * @var string
     */
    private $token;

    /**
     * Goole api client object
     * @var object
     */
    public $client;

    /**
     * OAuth success status
     * @var  bool [description]
     */
    public $success;

    /**
     * Marks token as new
     * A new token indicates the client isn't fit for use and must be recreated
     * @var boolean
     */
    private $is_new = false;

    /**
     * Instance of this class.
     * @var object
     */
    private static $instance = null;

    /**
     * Authenticates client and manages storing of the auth token
     */
    private function __construct() {

        // Create new FA Options instance
        $this->options = new Options();

        // Get token if set in db options
        $this->token = $this->options->get('oauth_token');

        // Get configuration
        $this->config = new Config();

        $this->create_client();

        // With offline access
        $this->client->setAccessType ( "offline" );

        // If redirected from oAuth use code param and get token
        // Also mark token as new to ensure the client gets unset
        if ( isset($_GET['code']) ) {

            $this->client->authenticate();
            $this->token = $this->client->getAccessToken();

            // Regenerate client as a new client cannot be used to create a service
            $this->create_client();
        }

        // If token is available, set on client and store in db options
        if ( $this->token ) {

            // Set on client
            $this->client->setAccessToken($this->token);

            // Store token
            $this->set_token($this->token);

            // Check users matches config user
            $this->check_valid_user();
        }

        // Check if token was set successfully and if not clear field from db
        if (!$this->client->getAccessToken()) $this->sign_out();

    }

    /**
     * Return an instance of this class.
     * Used instead of constructor to avoid multiple auth instances
     * @return object A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Creates oauth client
     */
    private function create_client() {

        // Remove client
        unset($this->client);

        // Set GA api vars
        $this->client = new Google_Client ();
        $this->client->setApplicationName( $this->config->product_name );
        $this->client->setClientId ( $this->config->analytics['client_id'] );
        $this->client->setClientSecret ( $this->config->analytics['client_secret'] );
        $this->client->setRedirectUri ( $this->config->analytics['redirect_uri'] );
        $this->client->setDeveloperKey( $this->config->analytics['api_key'] );
        $this->client->setScopes( array(
            'https://www.googleapis.com/auth/analytics.readonly',
            'https://www.googleapis.com/auth/userinfo.email'
            ) );
    }

    /**
     * Check that oauth user matched user specified in the config file
     * Having different users authorise the app WILL cause data problems as not
     * all users have access to the same profiles.
     */
    private function check_valid_user() {

        // Create oauth service
        $oauth2Service = new \Google_Oauth2Service($this->client);

        // Get user info
        $userinfo = $oauth2Service->userinfo->get();

        // Get email and match to config user
        $user = $userinfo["email"];

        // If user doesn't match configured auth user simply sign out
        if ($user !== $this->config->api_user) $this->sign_out();
    }

    /**
     * Stores auth token in db and sets auth success to true
     */
    private function set_token() {

        // Store auth token
        $this->options->set('oauth_token', $this->token);
        $this->success = true;
    }

    /**
     * Removes auth token and user from db
     * Also sets auth success to false
     */
    public function sign_out () {

        // Delete token and user from db
        $this->options->delete('oauth_token');
        $this->success = false;
    }

    /**
     * Return OAuth login url
     * @return string OAuth url
     */
    public static function get_auth_url() {

        // Create instance of self
        $inst = new self();

        // Return auth url
        return $inst->client->createAuthUrl();
    }
}
