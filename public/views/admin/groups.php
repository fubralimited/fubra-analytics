
<?

$status_message = NULL;
$status_state = NULL;

// Check new group has been added
if (isset($_POST['group_create'])) {
    
    // Create group and set status
    $group_created = $app->api->create_group($_POST['group_create']);
    $status_message = $group_created ? 'Group succesfully created' : 'Group already exists';
    $status_state = $group_created ? 'alert-success' : 'alert-warning';
}

// Check group has been deleted
if (isset($_POST['group_delete'])) {
    
    // Create group and set status
    $group_deleted = $app->api->delete_group($_POST['group_delete']);
    $status_message = $group_deleted ? 'Group succesfully deleted' : 'Group does not exist';
    $status_state = $group_deleted ? 'alert-success' : 'alert-warning';
}

// Check groups has been updated
if (isset($_POST['group_update'])) {
    
    // Update profile groups
    $app->api->update_groups($_POST['group_update']);
    $status_message = 'Profiles updated';
    $status_state = 'alert-success';
}

// Get groups
$groups = $app->api->get_groups();

// Get profiles
$profiles = $app->api->get_profiles();

?>

<!-- Status message -->
<div class="status-bar text-center alert <?= $status_state ?>"><?= $status_message ?></div>

<div class="row">

    <div class="col-md-6">
        <div class="panel panel-default groups">
          <div class="panel-heading">
            <h3 class="panel-title">Add new group</h3>
          </div>
          <div class="panel-body">
            <form role="form" method="POST" action="" class="add_group form-inline" >

              <div class="form-group">
                <input name="group_create" type="text" class="form-control" placeholder="Group name">
              </div>
              <button type="submit" class="btn btn-info">Create</button>

            </form>
          </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="panel panel-default groups">
          <div class="panel-heading">
            <h3 class="panel-title">Delete group</h3>
          </div>
          <div class="panel-body">
            <form role="form" method="POST" action="" class="delete_group form-inline" >

                <select name="group_delete" class="form-control">
                    <? foreach ($groups as $id => $name) : ?>
                        <option value="<?= $id ?>"><?= $name ?></option>
                    <? endforeach; ?>
                </select>

              <button type="submit" class="btn btn-info">Delete</button>

            </form>
          </div>
        </div>
    </div>

</div>


<!-- Profile -> Group panel -->

<h3>Group Profiles</h3>

<!-- Main group panel -->
<div class="panel panel-default profiles">

    <form role="form" method="POST" action="" class="filters form-inline" >

      <div class="panel-heading">

            <div class="row">
                <div class="form-group col-md-4">
                    <label for="group_filter">Filter by group: &nbsp;&nbsp;</label>
                    <select id="group_filter" class="form-control input-sm">

                            <!-- Add all (default option) -->
                            <option value="all">All</option>
                            <option value="none">None</option>
                            <? foreach ($groups as $id => $name) : ?>
                                <option value="<?= $id ?>"><?= $name ?></option>
                            <? endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-md-6">
                    <label for="profile_filter">Filter by profile: &nbsp;&nbsp;</label>
                    <input id="profile_filter" type="text" class="form-control input-sm" placeholder="Profile name">
                </div>

                <div class="form-group col-md-2">
                    <button type="submit" class="pull-right btn btn-success btn-lg">Update</button>
                </div>
            </div>

      </div>

        <table id="profiles-table" class="table table-striped">
            <tr><th>Profile</th><th>Group</th><th>Set group</th></tr>

            <!-- Create profile rows -->
            <? foreach ($profiles as $profile) : ?>
              <tr class="profile group-id-<?= $profile['group'] ? $profile['group'] : 'none' ?>" data-name="<?= $profile['name'] ?>">
                <td><?= $profile['name'] ?></td>

                <!-- Check if profile has a group set -->
                <? $group_isset = isset($groups[$profile['group']]); ?>

                <td><span class="label <?= $group_isset ? 'label-primary' : 'label-warning' ?>">
                    <?= $group_isset ? $groups[$profile['group']] : 'None' ?>
                </span></td>
                <td>

                    <!-- Create group select list -->
                    <select name="group_update[<?= $profile['id'] ?>]" class="form-control input-sm">

                        <!-- Add empty option -->
                        <option value="">None</option>

                        <? foreach ($groups as $id => $name) : ?>
                            <option <?= ($profile['group'] == $id) ? 'selected' : NULL; ?> value="<?= $id ?>">
                                <?= $name ?>
                            </option>
                        <? endforeach; ?>
                    </select>

                </td>
              </tr>
          
            <? endforeach; ?>
        </table>

    </form>
</div>


