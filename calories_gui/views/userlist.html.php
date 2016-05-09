<div class="container">
    <h3>System Users</h3>
    <ul class="list-group">
        <?php if (count($users) > 0) { ?>
            <?php foreach ($users as $selected_user) { ?>
                <div class="row">
                    <li class="list-group-item" 
                        data-user-id="<?php echo $selected_user['id'] ?>" 
                        data-username="<?php echo htmlspecialchars($selected_user['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php echo htmlspecialchars($selected_user['name'], ENT_QUOTES, 'UTF-8') ?> - 
                        <?php echo $selected_user['role'] ?>
                        <span class="pull-right">
                            <a data-type="meallist" 
                               data-page="0"
                               data-user-id="<?php echo $selected_user['id'] ?>"
                               data-username="<?php echo $selected_user['name'] ?>"
                               class="btn btn-sm" tooltip="List Meals">
                                <span class="glyphicon glyphicon-th-list"></span>
                            </a>
                            <a data-type="edit_user" class="btn btn-sm" tooltip="Edit User">
                                <span class="glyphicon glyphicon-pencil"></span>
                            </a>
                            <a data-type="delete_user" class="btn btn-sm" tooltip="Remove User">
                                <span class="glyphicon glyphicon-remove"></span>
                            </a>
                        </span>
                    </li>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p>No Users Found</p>
        <?php } ?>
    </ul>
    <ul class="pagination">
        <?php if ($page_num > 0) { ?>
            <li><a href="#" data-type="userlist" data-page="<?php echo $page_num - 1; ?>"><<</a></li>
        <?php } ?>
        <?php if ($is_last == false) { ?>
            <li><a href="#" data-type="userlist" data-page="<?php echo $page_num + 1; ?>">>></a></li>
        <?php } ?>
    </ul>
</div>