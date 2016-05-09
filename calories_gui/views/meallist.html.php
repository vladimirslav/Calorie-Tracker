<div<div style="height: 100%; padding-top: 20px;" class="<?= $calories_class ?>">
    <div class="container">
        <h3>Meals of <?= $owner_name ?></h3>
        <ul class="list-group">
            <?php if (count($meals) > 0) { ?>
                <?php foreach ($meals as $meal) { ?>
                    <div class="row">
                        <li class="list-group-item" data-meal-id="<?php echo $meal['id'] ?>">
                            <?php echo $meal['date'] ?>
                            <?php echo $meal['time'] ?> - 
                            <?php echo $meal['calories'] ?>cal 
                            Test<?php echo <?php echo strip_tags($meal['text']); ?>
                            <span class="pull-right">
                                <a data-type="edit_meal" data-meal-id="<?php echo $meal['id'] ?>" class="btn btn-sm">
                                    <span class="glyphicon glyphicon-pencil"></span>
                                </a>
                                <a data-type="delete_meal" class="btn btn-sm" data-no-redirect="true">
                                    <span class="glyphicon glyphicon-remove"></span>
                                </a>
                            </span>
                        </li>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p>No meals found</p>
            <?php } ?>
            
        </ul>
        <div class="text-center">
            <ul class="pagination">
                <?php if ($page_num > 0) { ?>
                    <li>
                        <a href="#"
                           data-type="meallist"
                           data-page="<?php echo $page_num - 1; ?>"
                           data-user-id="<?php echo $owner_id ?>"
                           data-username="<?php echo $owner_name ?>"
                        >
                           <<
                        </a>
                    </li>
                <?php } ?>
                <?php if ($is_last == false) { ?>
                    <li>
                        <a href="#"
                           data-type="meallist"
                           data-page="<?php echo $page_num + 1; ?>"
                           data-user-id="<?php echo $owner_id ?>"
                           data-username="<?php echo $owner_name ?>"
                        >
                            >>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <a href=""
           class="btn btn-lg btn-primary btn-block"
           data-type="add_meal" data-owner="<?php echo $owner_id ?>">
           Add Meal For This User
        </a>
        <a href="" class="btn btn-lg btn-primary btn-block" data-type="userlist" data-page="0">Back to User List</a>
    </div>
</div>