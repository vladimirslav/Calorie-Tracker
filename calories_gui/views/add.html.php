<div class="container">
  <form>
    <h2><?php echo ($is_edit) ? 'Edit Meal Record' : 'Add Meal'; ?></h2>
    <label for="email" class="sr-only">Comment/Text</label>
    <input type="text" id="text" class="form-control" placeholder="Comment/Text" required autofocus>
    <label for="Calories" class="sr-only">Calories</label>
    <input type="text" id="calories" class="form-control" placeholder="Calories" required>
    <label for="date" class="sr-only">Date</label>
    <input type="text" id="date" class="form-control" placeholder="Confirm Date" required>
    <label for="time" class="sr-only">Time</label>
    <input type="text" id="time" class="form-control" placeholder="Time" required>

    <a class="btn btn-lg btn-primary btn-block" id="mainaction"
       data-type="<?php echo ($is_edit) ? 'update_meal' : 'post_meal'; ?>"
       data-meal-id="<?php echo ($meal_id) ?>"
       data-owner="<?php echo ($owner_id) ?>">
        <?php echo ($is_edit) ? 'Save Changes' : 'Add Meal'; ?>
    </a>
    <a class="btn btn-lg btn-primary btn-block" data-type="mainpage">
        Cancel
    </a>
  </form>
</div>