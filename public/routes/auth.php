<?php

// Initialise GA\Auth to use returned token
new \GA\Auth();

// Redirect home
$app->redirect('/');