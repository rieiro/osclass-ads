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
        <?php echo osc_private_user_menu() ; ?>
      </div>
      <div id="main">
        <h2><?php _e('Your listings', 'elena'); ?></h2>
        <?php if(osc_count_items() == 0) { ?>
          <h3 class="empty"><?php _e("You do not have any listings yet", 'elena'); ?></h3>
        <?php } else { ?>
          <?php while(osc_has_items()) { ?>
            <div class="user-item">
              <?php if( osc_item_is_expired () ) { ?><span class="user-listing-expired"><?php _e('Listing expired', 'elena'); ?> (!)</span><?php } ?>
              <?php if( osc_images_enabled_at_items() ) { ?>
                <a href="<?php echo osc_item_url(); ?>" class="user-img">
                  <?php if( osc_count_item_resources() > 0 ) { ?>
                    <?php for ( $i = 0; osc_has_item_resources() ; $i++ ) { ?>
                      <?php if( $i == 0 ) { ?>
                        <img src="<?php echo osc_resource_thumbnail_url(); ?>" width="100" alt="<?php echo osc_item_title() ; ?>" title="<?php echo osc_item_title() ; ?>"/>
                      <?php } ?>
                    <?php } ?>
                  <?php } else { ?>
                    <img src="<?php echo osc_current_web_theme_url('images/no_photo.gif') ; ?>" width="100" alt="<?php echo osc_item_title() ; ?>" title="<?php echo osc_item_title() ; ?>"/>
                  <?php } ?>
                </a>
              <?php } ?>

              <h3><a href="<?php echo osc_item_url(); ?>"><?php echo osc_item_title(); ?></a></h3>
                
              <div class="user-elem"><?php _e('Publish', 'elena'); ?>: <?php echo date("Y/m/d", strtotime(osc_item_pub_date())); ?></div>
              <div class="user-elem"><?php _e('Expire', 'elena'); ?>: 
                <?php if(date("Y", strtotime(osc_item_dt_expiration())) > 9000) { ?>
                  <?php _e('never', 'elena'); ?>
                <?php } else { ?>
                  <?php echo date("Y/m/d", strtotime(osc_item_dt_expiration())); ?>
                <?php } ?>
              </div>
              <div class="user-elem user-price"><?php if( osc_price_enabled_at_items() ) { _e('Price', 'elena') ; ?>: <strong><?php echo osc_format_price(osc_item_price()); } ?></strong></div>

              <div class="options">
                <?php if (function_exists('republish_url')) {echo republish_url();} ?>
                <a class="user-edit" href="<?php echo osc_item_edit_url(); ?>"><?php _e('Edit', 'elena'); ?></a>
                <a class="user-delete" onclick="javascript:return confirm('<?php echo osc_esc_js(__('This action can not be undone. Are you sure you want to continue?', 'elena')); ?>')" href="<?php echo osc_item_delete_url();?>" ><?php _e('Delete', 'elena'); ?></a>
                <?php if(osc_item_is_inactive()) {?>
                  <a class="user-activate" href="<?php echo osc_item_activate_url();?>" ><?php _e('Activate', 'elena'); ?></a>
                <?php } ?>
              </div>
            </div>
          <?php } ?>

          <div class="clear"></div>
          <div class="paginate" >
            <?php for($i = 0 ; $i < osc_list_total_pages() ; $i++) { ?>
              <?php if($i == osc_list_page()) {  _e('Page: ', 'elena'); ?>
                <?php printf('<a class="searchPaginationSelected" href="%s">%d</a>', osc_user_list_items_url($i + 1), ($i + 1)); ?>
              <?php } else { ?>
                <?php printf('<a class="searchPaginationNonSelected" href="%s">%d</a>', osc_user_list_items_url($i + 1), ($i + 1)); ?>
              <?php } ?>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
    <?php osc_current_web_theme_path('footer.php') ; ?>
  </body>
</html>