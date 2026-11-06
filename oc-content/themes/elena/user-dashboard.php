<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
  <head>
    <?php osc_current_web_theme_path('head.php') ; ?>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex, nofollow" />
  </head>

  <body>
    <?php osc_current_web_theme_path('header.php') ; ?>
    <div class="content user_account">
      <h1>
        <?php if(function_exists('profile_picture_show')) { profile_picture_show(40); } ?>
        <strong><?php _e('User account manager', 'elena') ; ?></strong>
      </h1>

      <div id="sidebar">
        <?php echo osc_private_user_menu(); ?>
      </div>
      <div id="main" class="dashboard">
        <h2 class="round2"><?php echo __('Hello', 'elena') . ' <span class="under">' . osc_logged_user_name() . '</span>, ' .__('welcome to your account', 'elena'); ?>!</h2>

        <h3><i class="fa fa-list"></i>&nbsp;<?php echo _e('Your latest listings', 'elena'); ?></h2>
        <?php if(osc_count_items() == 0) { ?>
          <div class="empty"><?php _e('No listings have been added yet', 'elena'); ?></div>
        <?php } else { ?>
          <?php $c = 1; ?>
          <?php while(osc_has_items()) { ?>
            <div class="dash-item<?php if($c%2 == 0) { ?> odd<?php } ?>">
              <span class="id">#<?php echo osc_item_id(); ?></span>
              <span class="titl"><a href="<?php echo osc_item_url(); ?>"><?php echo osc_item_title(); ?></a></span>
              <span class="date"><?php echo date("Y-m-d", strtotime(osc_item_pub_date()));; ?></span>
              <span class="price"><span class="round2"><?php if( osc_price_enabled_at_items() ) { echo osc_format_price(osc_item_price()); } ?></span></span>
              <span class="views"><i class="fa fa-male"></i>&nbsp;<?php echo osc_item_views(); ?>x</span>
              <span class="edit"><a href="<?php echo osc_item_edit_url(); ?>"><i class="fa fa-wrench"></i>&nbsp;<?php _e('Edit', 'elena'); ?></a></span>
              <?php if(osc_item_is_inactive()) {?>
                <span class="activate"><a href="<?php echo osc_item_activate_url();?>" ><i class="fa fa-rocket"></i>&nbsp;<?php _e('Activate', 'elena'); ?></a></span>
              <?php } ?>
            </div>
            <?php $c++; ?>
          <?php } ?>
        <?php } ?>

        <div class="count-alerts round2">
          <?php $alerts = Alerts::newInstance()->findByUser( osc_logged_user_id()); ?>
          <h3><i class="fa fa-bell-o"></i>&nbsp;<?php echo __('You have', 'elena') . ' <strong>' . count($alerts) . '</strong> ' . __('alerts, you can check them in section', 'elena'); ?> <a href="<?php echo osc_user_alerts_url(); ?>"><?php echo _e('Manage your alerts', 'elena'); ?></a></h2>
        </div>

        <?php $u = User::newInstance()->findByPrimaryKey(osc_logged_user_id()); ?>

        <?php if($u['s_website'] == '' or ($u['s_phone_land'] == '' and $u['s_phone_mobile'] == '') or $u['s_country'] == '' or $u['s_region'] == '' or $u['s_city'] == '' or $u['s_address'] == '') { ?>
          <div class="inform-profile">
            <h3><i class="fa fa-warning"></i>&nbsp;<?php echo __('You profile is not complete!', 'elena'); ?></h3>
            <span class="descr"><?php echo _e('We found that your profile is still not complete! Take a minute and enter as much information as possible, this will help you sell your stuffs faster.', 'elena'); ?></span>

            <?php if($u['s_phone_land'] == '' and $u['s_phone_mobile'] == '') { ?><span class="entry"><i class="fa fa-exclamation"></i>&nbsp;<?php echo _e('No phone number was entered. You should enter at least 1 phone number to your mobile or land phone', 'elena'); ?></span><?php } ?>
            <?php if($u['s_website'] == '') { ?><span class="entry"><i class="fa fa-exclamation"></i>&nbsp;<?php echo _e('You did not entered your website', 'elena'); ?></span><?php } ?>
            <?php if($u['s_country'] == '') { ?><span class="entry"><i class="fa fa-exclamation"></i>&nbsp;<?php echo _e('Country was not entered', 'elena'); ?></span><?php } ?>
            <?php if($u['s_region'] == '') { ?><span class="entry"><i class="fa fa-exclamation"></i>&nbsp;<?php echo _e('Region was not entered', 'elena'); ?></span><?php } ?>
            <?php if($u['s_city'] == '') { ?><span class="entry"><i class="fa fa-exclamation"></i>&nbsp;<?php echo _e('City was not entered', 'elena'); ?></span><?php } ?>
            <?php if($u['s_address'] == '') { ?><span class="entry"><i class="fa fa-exclamation"></i>&nbsp;<?php echo _e('Address was not entered', 'elena'); ?></span><?php } ?>

          </div>
        <?php } else { ?>
          <div class="inform-profile-ok"><i class="fa fa-thumbs-o-up"></i>&nbsp;<?php echo _e('Your profile is complete!', 'elena'); ?></div>
        <?php } ?>
      </div>
    </div>
    <?php osc_current_web_theme_path('footer.php'); ?>
  </body>
</html>