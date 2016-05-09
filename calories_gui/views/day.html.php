<div style="height: 100%; padding-top: 20px;" class="<?= $calories_class ?>">
    <div class="container">
        <h3>Meals Today (Total: <?php echo $calories; ?>cal)</h3>
        <ul class="list-group">
            <?php if (count($meals) > 0) { ?>
                <?php foreach ($meals as $meal) { ?>
                    <div class="row">
                        <li class="list-group-item" data-meal-id="<?php echo $meal['id'] ?>">
                            <?php echo $meal['time'] ?> - 
                            <?php echo $meal['calories'] ?>cal 
                            Test<?php echo <?php echo strip_tags($meal['text']); ?>
                            <span class="pull-right">
                                <a data-type="edit_meal" data-meal-id="<?php echo $meal['id'] ?>" class="btn btn-sm">
                                    <span class="glyphicon glyphicon-pencil"></span>
                                </a>
                                <a data-type="delete_meal" class="btn btn-sm">
                                    <span class="glyphicon glyphicon-remove"></span>
                                </a>
                            </span>
                        </li>
                    </div>
                <?php } ?>
            <?php } ?>
        </ul>
        <a href=""
           class="btn btn-lg btn-primary btn-block"
           data-type="add_meal" data-user="<?php echo $user['id'] ?>">
           Add Meal
        </a>
    </div>
</div>