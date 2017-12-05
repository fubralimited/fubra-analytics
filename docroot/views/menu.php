<ul class="nav navbar-nav">

  <li class="<?= ($route['main'] == 'dashboard') ? 'active' : NULL ?>"><a href="/dashboard">Dashboard</a></li>
  <li class="<?= ($route['main'] == 'admin') ? 'active' : NULL ?>">
    <a href="" class="dropdown-toggle" data-toggle="dropdown">Admin <b class="caret"></b></a>
    <ul class="dropdown-menu">
      <li><a href="/admin/groups">Groups</a></li>
      <li><a href="/admin/profiles">Profiles</a></li>
    </ul>
  </li>
  <li class="dropdown <?= ($route['main'] == 'reports') ? 'active' : NULL ?>">
    <a href="" class="dropdown-toggle" data-toggle="dropdown">Reports <b class="caret"></b></a>
    <ul class="dropdown-menu">
      <li><a href="/reports/daily">Daily</a></li>
      <li><a href="/reports/airport_guides">Airport Guides</a></li>
    </ul>
  </li>
</ul>