<?php
  $address = '';

  $address = array(osc_user_city_area(), osc_user_address());
  $address = array_filter($address);
  $address = implode(', ', $address);
  
  $loc = array(osc_user_country(), osc_user_region(), osc_user_city());
  $loc = array_filter($loc);
  $loc = implode(', ', $loc);

  $user_keep = osc_user();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
  <head>
    <?php osc_current_web_theme_path('head.php'); ?>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex, nofollow" />
    <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
  </head>
  
  <body>
    <?php View::newInstance()->_exportVariableToView('user', $user_keep); ?>
    <?php osc_current_web_theme_path('header.php'); ?>
    
    <div class="content item user_public_profile">
      <div id="item_head">
        <div class="inner">
          <?php if(function_exists('profile_picture_show')) { profile_picture_show(40); } ?>
          <h1><?php echo sprintf(__('%s\'s profile', 'elena'), osc_user_name()); ?></h1>
        </div>
      </div>
      
      <div id="main" class="public_prof">
        <div id="description">
          <h2><?php _e('User details', 'elena'); ?></h2>
          <ul id="user_data">
            <li><?php _e('Full name', 'elena'); ?>: <strong><?php echo osc_user_name(); ?></strong></li>
            <?php if ( osc_user_phone_mobile() != "" ) { ?><li><?php _e("Mobile phone", 'elena'); ?>: <strong><?php echo osc_user_phone_mobile(); ?></strong></li><?php } ?>
            <?php if ( osc_user_phone() != "" && osc_user_phone() != osc_user_phone_mobile() ) { ?><li><?php _e("Phone", 'elena'); ?>: <strong><?php echo osc_user_phone(); ?></strong></li><?php } ?>          
            <?php if ($address != '') { ?><li><?php _e('Address', 'elena'); ?>: <strong><?php echo $address; ?></strong></li><?php } ?>
            <?php if ($loc != '') { ?><li><?php _e('Location', 'elena'); ?>: <strong><?php echo $loc; ?></strong></li><?php } ?>
            <?php if (osc_user_website() != '') { ?><li><?php _e('Website', 'elena'); ?>: <strong><?php echo osc_user_website(); ?></strong></li><?php } ?>
            <?php if (osc_user_info() != '') { ?><li><?php if (osc_user_info()!='') { ?><?php _e('Description', 'elena'); ?>: <?php echo osc_user_info(); ?><?php } ?></li><?php } ?>
          </ul>
        </div>
        
        <div class="ad_list">      
          <div id="s-gal">
            <div class="not-premium"><?php _e('User listings', 'elena'); ?> <a class="all-user" href="<?php echo osc_base_url(true).'index.php?page=search&seller_post='.osc_user_id(); ?>">Show all listings</a></div>

            <?php $class = 'even'; $count = 0; ?>
            <?php osc_query_item(array("author" => osc_user_id()) ); ?>

            <?php while(osc_has_custom_items()) { ?>
              <div class="tr <?php echo $class; ?>">
                <div class="td date"> 
                  <?php echo osc_format_date(osc_item_pub_date()); ?>
                </div>
                
                <div class="td photo <?php if(osc_count_item_resources() > 1) { ?>more-photo<?php } ?>">
                  <?php if(osc_item_is_premium()) { ?>
                    <div class="prem-keeper">
                      <span class="prem-title" title="<?php _e('Premium', 'elena'); ?>"><i class="fa fa-star"></i></span>
                    </div>
                  <?php } ?>

                <?php if( osc_price_enabled_at_items() ) { echo '<span id="zoznam_cena">' . osc_item_formated_price() . '</span>'; } ?>

                <div class="photo-wrap">
                  <?php if(osc_count_item_resources()) { ?>
                    <a href="<?php echo osc_item_url(); ?>"><img src="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_item_title(); ?>" alt="<?php echo osc_item_title(); ?>" class="round2" /></a>
                  <?php } else { ?>
                    <a href="<?php echo osc_item_url(); ?>"><img src="<?php echo osc_base_url(); ?>oc-content/themes/elena/images/no_photo.gif" title="<?php echo osc_item_title(); ?>" alt="<?php echo osc_item_title(); ?>" class="round2" /></a>
                  <?php } ?>  
                </div>             
              </div>
                
              <div class="td text">
                <h3><a id="s_tit" href="<?php echo osc_item_url(); ?>" title="<?php echo osc_item_title(); ?>"><?php echo ucfirst( osc_highlight( strip_tags( osc_item_title() ), 90 ) ); ?></a></h3>
                <div class="clear"></div>
                
                <p class="loc-list">
                  <strong><?php if (osc_item_country() != '') { echo '<span id="zoznam_span">' . osc_item_country() . '</span> - '; } ?><?php if (osc_item_city() != '') { echo '<span id="zoznam_span">' . osc_item_city() . '</span> - '; } ?><?php if ( osc_item_region()!='') { echo '<span id="zoznam_span">' . osc_item_region() . '</span>'; } ?></span></strong>
                </p>
              </div>
            </div>
            
            <?php $class = ($class == 'even') ? 'odd' : 'even'; ?>
            <?php $count = $count + 1; ?>
          <?php } ?>
        </div>
      </div>
    </div>
    
    <div id="sidebar">
      <?php if(osc_logged_user_id()!=  osc_user_id()) { ?>
        <?php if(osc_reg_user_can_contact() && osc_is_web_user_logged_in() || !osc_reg_user_can_contact() ) { ?>
          <div id="contact">
            <h2><?php _e("Contact seller", 'elena'); ?></h2>
            <div class="s-del"></div>

            <ul id="error_list"></ul>
            <?php ContactForm::js_validation(); ?>
            <form action="<?php echo osc_base_url(true); ?>" method="post" name="contact_form" id="contact_form">
              <input type="hidden" name="action" value="contact_post" />
              <input type="hidden" name="page" value="user" />
              <input type="hidden" name="id" value="<?php echo osc_user_id(); ?>" />
              <?php osc_prepare_user_info(); ?>
              
              <fieldset>
                <label for="yourName"><?php _e('Your name', 'elena'); ?>:</label> <?php ContactForm::your_name(); ?>
                <label for="yourEmail"><?php _e('Your e-mail address', 'elena'); ?>:</label> <?php ContactForm::your_email(); ?>
                <label for="phoneNumber"><?php _e('Phone number', 'elena'); ?> (<?php _e('optional', 'elena'); ?>):</label> <?php ContactForm::your_phone_number(); ?>
                <label for="message"><?php _e('Message', 'elena'); ?>:</label> <?php ContactForm::your_message(); ?>
                <?php if( osc_recaptcha_public_key() ) { ?>
                  <script type="text/javascript">
                    var RecaptchaOptions = {
                      theme : 'custom',
                      custom_theme_widget: 'recaptcha_widget'
                    };
                  </script>
                  
                  <style type="text/css"> div#recaptcha_widget, div#recaptcha_image > img { width:280px; } </style>
                  <div id="recaptcha_widget">
                    <div id="recaptcha_image"><img /></div>
                    <span class="recaptcha_only_if_image"><?php _e('Enter the words above','elena'); ?>:</span>
                    <input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />
                    <div><a href="javascript:Recaptcha.showhelp()"><?php _e('Help', 'elena'); ?></a></div>
                  </div>
                <?php } ?>
                
                <?php osc_show_recaptcha(); ?>
                <button type="submit"><?php _e('Send', 'elena'); ?></button>
              </fieldset>
            </form>
          </div>
        <?php } ?>
      <?php } ?>
    </div>
  </div>
   
  <?php osc_current_web_theme_path('footer.php'); ?>
  </body>
</html>