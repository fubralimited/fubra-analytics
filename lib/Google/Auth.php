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
        $config = new Config();

        // Set GA api vars
        $this->client = new Google_Client ();
        $this->client->setApplicationName( $config->product_name );
        $this->client->setClientId ( $config->analytics['client_id'] );
        $this->client->setClientSecret ( $config->analytics['client_secret'] );
        $this->client->setRedirectUri ( $config->analytics['redirect_uri'] );
        $this->client->setDeveloperKey( $config->analytics['api_key'] );
        $this->client->setScopes( array('https://www.googleapis.com/auth/analytics.readonly') );

        // With offline access
        $this->client->setAccessType ( "offline" );

        // If redirected from oAuth use code param and get token
        // Also mark token as new to ensure the client gets unset
        if (isset($_GET['code'])) {

            $this->client->authenticate();
            $this->token = $this->client->getAccessToken();

            $this->is_new = true;
        }

        // If token is available, set on client and store in db options
        if ( $this->token ) {

            // Set on client
            $this->client->setAccessToken($this->token);

            // Store token
            $this->set_token($this->token);
        }

        // Check if token was set successfully and if not clear field from db
        if (!$this->client->getAccessToken()) $this->sign_out();

        // Unset client to ensure it's not used to create a new service
        // A new auth instance must be created for use with a service if new access token is received
        if( $this->is_new ) $this->client = NULL;

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

    private function set_token() {

        // Store auth token
        $this->options->set('oauth_token', $this->token);

        $this->success = true;

    }

    public function sign_out () {

        // Delete token from db
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
