<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
  <head>
    <?php osc_current_web_theme_path('head.php'); ?>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex, nofollow" />
  </head>
  
  <body>
    <?php osc_current_web_theme_path('header.php'); ?>
    <div class="content user_account">
      <h1>
        <?php if(function_exists('profile_picture_show')) { profile_picture_show(40); } ?>
        <strong><?php _e('User account manager', 'elena') ; ?></strong>
      </h1>
      
      <div id="sidebar">
        <?php echo osc_private_user_menu(); ?>
      </div>
      
      <div id="main">
        <h2 class="round2"><i class="fa fa-bullhorn"></i>&nbsp;<?php _e('Your alerts', 'elena'); ?></h2>
        <?php if(osc_count_alerts() == 0) { ?>
          <div class="empty"><?php _e('You do not have any alerts yet', 'elena'); ?></div>
        <?php } else { ?>
          <?php $c = 1; ?>
          <?php while(osc_has_alerts()) { ?>
            <div class="userItem" >
              <div class="hed round2"><i class="fa fa-bell-o"></i>&nbsp;<?php echo __('Alert', 'elena') . ' #' . $c; ?> <a onclick="javascript:return confirm('<?php echo osc_esc_js(__('This action can\'t be undone. Are you sure you want to continue?', 'elena')); ?>');" href="<?php echo osc_user_unsubscribe_alert_url(); ?>"><i class="fa fa-trash-o"></i>&nbsp;<?php _e('Delete this alert', 'elena'); ?></a></div>
              <div id="alerts_list" >
              <?php while(osc_has_items()) { ?>
                <div class="item-entry" >
                  <?php if( osc_images_enabled_at_items() ) { ?>
                    <?php if(osc_count_item_resources()) { ?>
                      <a class="photo round2" href="<?php echo osc_item_url(); ?>"><img class="round2" src="<?php echo osc_resource_thumbnail_url(); ?>" width="150" height="125" title="<?php echo osc_item_title(); ?>" alt="<?php echo osc_item_title(); ?>" /></a>
                    <?php } else { ?>
                      <a class="photo round2" href="<?php echo osc_item_url(); ?>"><img class="round2" src="<?php echo osc_current_web_theme_url('images/no_photo.gif'); ?>" title="" alt="" width="150" height="125"/></a>
                    <?php } ?>
                  <?php } ?>

                  <div class="data-wrap">
                    <span><a href="<?php echo osc_item_url(); ?>"><?php echo osc_item_title(); ?></a></span>
                    <span><?php _e('Publication date', 'elena'); ?>: <?php echo osc_format_date(osc_item_pub_date()); ?></span>
                    <span class="a-price"><?php if( osc_price_enabled_at_items() ) { _e('Price', 'elena'); ?>: <strong><?php echo osc_format_price(osc_item_price()); } ?></strong></span>
                  </div>
                </div>
              <?php } ?>
              <?php if(osc_count_items() == 0) { ?>
                <div class="item-entry" >
                  <?php _e('No listings fits to search/alert criteria', 'elena'); ?>
                </div>
              <?php } ?>
              </div>
            </div>
            <?php $c++; ?>
          <?php } ?>
        <?php  } ?>
      </div>
    </div>
    <?php osc_current_web_theme_path('footer.php'); ?>
  </body>
</html>