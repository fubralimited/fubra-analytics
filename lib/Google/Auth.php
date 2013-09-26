<?php

namespace GA;

use \FA\Options;
use \FA\Config;

/**
* Google API oAuth class
*/
class Auth {

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
     * Authenticates client and manages storing of the auth token
     */
    function __construct() {

        // Create new FA Options instance
        $options = new \FA\Options();

        // Get token if set in db options
        $oauth_token = $options->get('oauth_token');

        // Get configuration
        $config = new \FA\Config();

        // Set GA api vars
        $this->client = new \Google_Client ();
        $this->client->setApplicationName( $config->analytics['product_name'] );
        $this->client->setClientId ( $config->analytics['client_id'] );
        $this->client->setClientSecret ( $config->analytics['client_secret'] );
        $this->client->setRedirectUri ( $config->analytics['redirect_uri'] );
        $this->client->setDeveloperKey( $config->analytics['api_key'] );
        $this->client->setScopes( array('https://www.googleapis.com/auth/analytics.readonly') );

        // With offline access
        $this->client->setAccessType ( "offline" );

        // If redirected from oAuth use code param and get token
        if (isset($_GET['code'])) {

            $this->client->authenticate();
            $oauth_token = $this->client->getAccessToken();
        }

        // If token is available, set on client and store in db options
        if ( $oauth_token ) {

            // Set on client
            $this->client->setAccessToken($oauth_token);

            // Store auth token
            $options->set('oauth_token', $oauth_token);

            $this->success = true;
        }

        // Check if token was set successfully and if not clear field from db
        if (!$this->client->getAccessToken()) {

            $options->delete('oauth_token');
            $this->success = false;
        }
    }

    /**
     * Return OAuth login url
     * @return string OAuth url
     */
    public function get_auth_url() {

        return $this->client->createAuthUrl();
    }
}
