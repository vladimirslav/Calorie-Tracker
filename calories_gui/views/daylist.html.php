<div class="container">
    <div id="calendar">
        <?php if (count($meals_by_date) > 0) { ?>
            <?php foreach ($meals_by_date as $date => $meal_data) { ?>
                <h3 data-calories="<?php echo $meal_data['calories_total']?>">
                    <?php echo $date . ' - ' . $meal_data['calories_total'] . 'cal'; ?>
                </h3>
                <ul class="list-group">
                    <?php foreach ($meal_data['data'] as $meal) { ?>
                        <div class="row">
                            <li class="list-group-item" data-meal-id="<?php echo $meal['id'] ?>">
                                <?php echo $meal['time'] ?> - 
                                <?php echo $meal['calories'] ?>cal 
                                Test<?php echo <?php echo strip_tags($meal['text']); ?>
                                <span class="pull-right">
                                    <a data-type="edit_meal" data-meal-id="<?php echo $meal['id'] ?>" class="btn btn-sm">
                                        <span class="glyphicon glyphicon-pencil"></span>
                                    </a>
                                    <a data-type="delete_meal" data-no-redirect="true" class="btn btn-sm">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </a>
                                </span>
                            </li>
                        </div>
                    <?php } ?>
                </ul>
            <?php } ?>
        <?php } else { ?>
            <p>No Meals Found</p>
        <?php } ?>
    </div>
    <a href="" class="btn btn-lg btn-primary btn-block" data-type="mainpage">Back To Main</a>
</div>