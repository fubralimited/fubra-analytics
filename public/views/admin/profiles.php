<?

$status_message = NULL;
$status_state = NULL;

// Update ignored if any is set
if (isset($_POST['profile_update'])) {

    $app->api->update_ignored($_POST['profile_update']);
    $status_message = 'Profiles updated';
    $status_state = 'alert-success';
}

// Get groups
$groups = $app->api->get_groups();

// Get profiles
$profiles = $app->api->get_profiles(true);

?>

<!-- Status message -->
<div class="status-bar text-center alert <?= $status_state ?>"><?= $status_message ?></div>

<!-- Profiles panel -->

<div class="panel panel-default profiles">

    <form role="form" method="POST" action="" class="filters form-inline" >

        <div class="panel-heading">
            
            <div class="form-group visits-filter">
                <button class="btn btn-info">Show 0 visits only</button>
                <button class="btn btn-info hidden">Show all</button>
            </div>

            <div class="form-group select-all">
                <button class="btn btn-info">Select all</button>
                <button class="btn btn-info hidden">Unselect all</button>
            </div>

            <div class="form-group pull-right">
                <button type="submit" class="btn btn-success">Update</button>
            </div>

        </div>

        <table id="profiles-table" class="table table-striped">
            <tr><th>Profile</th><th>Group</th><th>Total visits</th><th>Ignore</th></tr>

            <!-- Add hidden field to ensure profile_update gets posted even if nothing is checked -->
            <input type="hidden" name="profile_update[0]" value="">

            <!-- Create profile rows -->
            <? foreach ($profiles as $profile) : ?>

                <!-- Get total visits for profile -->
                <? $total_visits = $app->api->get_total_visits($profile['id']) ?>

              <tr class="profile <?= $total_visits ? 'visited' : NULL ?><?= $profile['ignored'] ? 'ignored' : NULL ?>">
                <td><?= $profile['name'] ?></td>

                <!-- Check if profile has a group set -->
                <? $group_isset = isset($groups[$profile['group']]); ?>

                <td>
                    <em><?= $group_isset ? $groups[$profile['group']] : 'None' ?></em>
                </td>

                <td>
                    <span class="label label-<?= $total_visits ? 'success' : 'danger' ?>"><?= $total_visits ?></span>
                </td>

                <td>
                    <div class="checkbox">
                        <label><input <?= $profile['ignored'] ? 'checked' : NULL ?> name="profile_update[<?= $profile['id'] ?>]" type="checkbox"></label>
                    </div>
                </td>
              </tr>
          
            <? endforeach; ?>
        </table>

    </form>
</div>