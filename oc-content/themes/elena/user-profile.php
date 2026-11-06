<?php
  $locales  = __get('locales');
  $user = osc_user();
  osc_enqueue_style('jquery-ui-custom', osc_current_web_theme_styles_url('jquery-ui/jquery-ui-1.8.20.custom.css'));
?>

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
        <strong><?php _e('User account manager', 'elena'); ?></strong>
      </h1>
      <div id="sidebar">
        <?php echo osc_private_user_menu(); ?>
      </div>
      
      <div id="main" class="modify_profile">
        <h2 class="round2"><i class="fa fa-puzzle-piece"></i>&nbsp;<?php _e('Update your profile', 'elena'); ?></h2>
        <?php UserForm::location_javascript(); ?>

        <div id="b-left">
          <form action="<?php echo osc_base_url(true); ?>" method="post">
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="profile_post" />
            <fieldset>
              <div class="row">
                <label for="name"><?php _e('Name', 'elena'); ?></label>
                <?php UserForm::name_text(osc_user()); ?>
              </div>
              <div class="row">
                <label for="email"><?php _e('Username', 'elena'); ?></label>
                <span class="update u-name">
                  <?php echo osc_user_username(); ?>
                  <?php if(osc_user_username()==osc_user_id()) { ?>
                    <a href="<?php echo osc_change_user_username_url(); ?>"><?php _e('Modify username', 'elena'); ?></a>
                  <?php } ?>
                </span>
              </div>
              <div class="row">
                <label for="email"><?php _e('E-mail', 'elena'); ?></label>
                <span class="update">
                  <span class="u-mail"><?php echo osc_user_email(); ?></span>
                  <div class="clear"></div>
                  <a href="<?php echo osc_change_user_email_url(); ?>"><i class="fa fa-at"></i>&nbsp;<?php _e('Modify e-mail', 'elena'); ?></a>
                  <a href="<?php echo osc_change_user_password_url(); ?>" ><i class="fa fa-unlock-alt"></i>&nbsp;<?php _e('Modify password', 'elena'); ?></a>
                </span>
              </div>
              <div class="row">
                <label for="user_type"><?php _e('User type', 'elena'); ?></label>
                <?php UserForm::is_company_select(osc_user()); ?>
              </div>
              <div class="row">
                <label for="phoneMobile"><?php _e('Mobile phone', 'elena'); ?></label>
                <?php UserForm::mobile_text(osc_user()); ?>
              </div>
              <div class="row">
                <label for="phoneLand"><?php _e('Phone', 'elena'); ?></label>
                <?php UserForm::phone_land_text(osc_user()); ?>
              </div>
              <div class="row">
                <label for="country"><?php _e('Country', 'elena'); ?> <sup>*</sup></label>
                <?php UserForm::country_select(osc_get_countries(), osc_user()); ?>
              </div>
              <div class="row">
                <label for="region"><?php _e('Region', 'elena'); ?> <sup>*</sup></label>
                <?php UserForm::region_select(osc_get_regions(), osc_user()); ?>
              </div>
              <div class="row">
                <label for="city"><?php _e('City', 'elena'); ?> <sup>*</sup></label>
                <?php UserForm::city_select(osc_get_cities(), osc_user()); ?>
              </div>
              <div class="row">
                <label for="address"><?php _e('Address', 'elena'); ?></label>
                <?php UserForm::address_text(osc_user()); ?>
              </div>
              <div class="row">
                <label for="webSite"><?php _e('Website', 'elena'); ?></label>
                <?php UserForm::website_text(osc_user()); ?>
              </div>
              <div class="row <?php if(osc_count_web_enabled_locales() == 1) { ?>one-lang<?php } ?>" style="float:left">
                <label for="user-info"><?php _e('Information', 'elena'); ?></label>
                <?php UserForm::multilanguage_info($locales, osc_user()); ?>
              </div>
              <div class="row butts">
                <button type="submit"><?php _e('Update', 'elena'); ?></button>

                <?php if(osc_logged_user_email() <> 'demo@demo.com') { ?>
                  <a href="#" id="delete_account"><i class="fa fa-trash-o"></i>&nbsp;<?php _e('Delete my account', 'elena'); ?></a>
                <?php } ?>
              </div>
              <?php osc_run_hook('user_form'); ?>
            </fieldset>
          </form>
        </div>
        
        <div id="b-right" class="round2">
           <?php if(function_exists('profile_picture_upload')) { profile_picture_upload();} ?>

           <div class="s-row"><i class="fa fa-chevron-right"></i>&nbsp;<?php _e('More information about you will help you to sell your stuffs', 'elena'); ?></div>
           <div class="s-row"><i class="fa fa-chevron-right"></i>&nbsp;<?php _e('Write just valid information', 'elena'); ?></div>
           <div class="s-row"><i class="fa fa-chevron-right"></i>&nbsp;<?php _e('If you save at least 1 phone number, your possible customers will get to you much faster', 'elena'); ?></div>
           <div class="s-row"><i class="fa fa-chevron-right"></i>&nbsp;<?php _e('You can choose if you are Company or Person', 'elena'); ?></div>
           <div class="s-row"><i class="fa fa-chevron-right"></i>&nbsp;<?php _e('If you have list of your products on own website, save it to your profile', 'elena'); ?></div>
        </div>
      </div>
    </div>

    <div id="dialog-delete-account" title="<?php _e('Delete account', 'elena'); ?>" class="has-form-actions hide">
      <div class="form-horizontal">
        <div class="form-row">
          <p><?php _e('All your listings and alerts will be removed, this action can not be undone.', 'elena');?></p>
        </div>
      </div>
    </div>
    

    <?php osc_current_web_theme_path('footer.php'); ?>
    
    <script type="text/javascript">
      $(document).ready(function(){
        $("#delete_account").click(function(){
          $("#dialog-delete-account").dialog('open');
          $(".ui-dialog").css('top', '100px');
          $(".ui-widget-overlay").css('z-index', '99');
          $('.ui-widget-overlay').height($('body').outerHeight());
        });

        $("#dialog-delete-account").dialog({
          autoOpen: false,
          modal: true,
          buttons: {
            "<?php echo osc_esc_js(__('Delete', 'elena')); ?>": function() {
              window.location = '<?php echo osc_base_url(true).'?page=user&action=delete&id='.osc_user_id().'&secret='.$user['s_secret']; ?>';
            },
            "<?php echo osc_esc_js(__('Cancel', 'elena')); ?>": function() {
              $( this ).dialog( "close" );
            }
          }
        });

        if($('.tabbertab').length) {
          $('.tabbertab').prepend('<div class="inside"><div class="bg"></div></div>');
        }
      });
    </script>
  </body>
</html>