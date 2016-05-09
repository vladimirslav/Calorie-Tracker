<div class="container" id="settings" 
    data-username="<?php echo $settings_user_name; ?>" 
    data-user-id="<?php echo $settings_user_id ?>"
>
    <form class="form-settings">
        <?php if ($settings_user_id != $user['id']) { ?>
            <h2 class="form-settings-heading">Edit <?php echo $settings_user_name; ?> profile</h2>
        <?php } else { ?>
            <h2 class="form-settings-heading">Settings</h2>
        <?php } ?>
        <label for="name" class="sr-only">Name</label>
        <input type="text" id="name" class="form-control" placeholder="Name" required>
        <label for="daily_calories" class="sr-only">Daily Calories</label>
        <input type="text" id="daily_calories" class="form-control" placeholder="Calories" required>

        <?php if (strcmp($user['role'], 'administrator') === 0 || $settings_user_id == $user['id']) { ?> 
            <label for="password" class="sr-only">Password</label>
            <input type="password" id="password" class="form-control" placeholder="Password" required>
            <label for="confirm_password" class="sr-only">Confirm Password</label>
            <input type="password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
        <?php } ?>
        
        <?php if (strcmp($user['role'], 'administrator') === 0 && $settings_user_id != $user['id']) { ?> 
        <div class="dropdown">
            <span>Role:</span><span id="roleval"></span>
            <button class="btn btn-default dropdown-toggle"
                    type="button" id="roleselector" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                Select New Role
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="roleselector">
                <li><a href="#" data-type="selectrole">User</a></li>
                <li><a href="#" data-type="selectrole">Moderator</a></li>
                <li><a href="#" data-type="selectrole">Administrator</a></li>
            </ul>
        </div>
        <?php } ?>
    </form>
    <a class="btn btn-lg btn-primary btn-block" data-type="savesettings" id="mainaction">Save Settings</a>
    <?php if ($settings_user_id != $user['id']) { ?>
        <a class="btn btn-lg btn-primary btn-block" data-type="delete_user_from_profile">Delete User From The System</a>
    <?php } else { ?>
        <a class="btn btn-lg btn-primary btn-block" data-type="deleteme">Delete Me From The System</a>
    <?php } ?>
</div>