<?php

// Create new data instance
$api = new \GA\API();


$data = 'some data';


// Check data object is good to go
if ( $api->authenticated ) {
    
    $data = $api->get('2013-04-13');
}





$app->render('index.php', array(

        'host' => \GA\Auth::get_auth_url(),
        'data' => $data

    ));