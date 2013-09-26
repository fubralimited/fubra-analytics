<?php

$ga_auth = new \GA\Auth();

$authUrl = $ga_auth->get_auth_url();

$ga_data = new \GA\Data($ga_auth->client);

$ga_data->update_report_visits();



$app->render('index.php', array( 'host' => $authUrl, 'data' => null ));