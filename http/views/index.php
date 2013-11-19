
<!DOCTYPE html>
<html>
  <head>
    <title><?= $app->config->product_name . ' | ' . ucfirst($route['main']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="/views/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="/views/css/cosmo.theme.bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="/views/css/style.css" rel="stylesheet" media="screen">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="../../assets/js/html5shiv.js"></script>
      <script src="../../assets/js/respond.min.js"></script>
    <![endif]-->
  </head>
    
  <body style="">

    <!-- Wrap all page content here -->
    <div id="wrap">

      <!-- Fixed navbar -->
      <div class="navbar navbar-default navbar-fixed-top">
        <div class="container">
          <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand main_title" href="/"><?= $app->config->product_name ?></a>
          </div>
          <div class="collapse navbar-collapse">

            <!-- Menu items -->
            <? require 'menu.php'; ?>

            <!-- Auth button -->
            <? if ( ! $app->auth->success ) : ?>
              <a href="<?= $app->auth->get_auth_url(); ?>" class="auth_button btn btn-success pull-right">Authorise</em></a>
            <? endif; ?>

          </div><!--/.nav-collapse -->
        </div>
      </div>

      <!-- Begin page content -->
      <div class="container">

        <!-- Check whether to display auth alert -->
        <? if ( ! $app->auth->success ) : ?>
          <div class="status-bar text-center alert-warning">
            Application needs to be authorised as <em><?= $app->config->api_user ?></em>
          </div>
        <? endif; ?>

        <!-- Get temlpate -->
        <? require $route['template']; ?>

<!-- Footer -->
      </div>
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="/views/js/bootstrap.min.js"></script>

    <!-- Load custom script -->
    <script src="/views/js/script.js"></script>

    <!-- jQuery sortable plugin -->
    <script src='/views/js/jquery-sortable.js'></script>

  </body>
</html>