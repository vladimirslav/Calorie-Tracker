<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container">
    <div class="navbar-header">
      <span class="navbar-brand"><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>
        (<?php echo $user['daily_calories']; ?> cal/day)
      </span>
    </div>
    <div id="navbar" class="navbar-collapse collapse">
      <ul class="nav navbar-nav">
        <li <?php markIfActive($page, 'main') ?>>
            <a href="#" data-type="mainpage">Home</a>
        </li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <?php if ($is_admin) { ?>
            <li <?php markIfActive($page, 'userlist') ?>>
                <a href="#" data-type="userlist" data-page="0">User List</a>
            </li>
        <?php } ?>
        <li <?php markIfActive($page, array('statistics', 'daylist')) ?>>
            <a href="#" data-type="statistics">Statistics</a>
        </li>
        <li <?php markIfActive($page, 'settings') ?>>
            <a href="#" data-type="settings">Settings</a>
        </li>
        <li <?php markIfActive($page, 'about') ?>>
            <a href="#" data-type="about">About</a>
        </li>
        <li>
            <a href="#" data-type="logout">Log Out</a>
        </li>
      </ul>
    </div><!--/.nav-collapse -->
  </div>
</nav>