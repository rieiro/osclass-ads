<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
  <head>
    <?php osc_current_web_theme_path('head.php') ; ?>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex, nofollow" />
    <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js') ; ?>"></script>
  </head>
  
  <body>
    <?php osc_current_web_theme_path('header.php') ; ?>
    <div class="content user_forms">
      <div class="inner">
        <h1><?php _e('Contact us', 'elena') ; ?></h1>
        <ul id="error_list"></ul>
        <form action="<?php echo osc_base_url(true) ; ?>" method="post" name="contact" id="contact">
          <input type="hidden" name="page" value="contact" />
          <input type="hidden" name="action" value="contact_post" />
          <fieldset>
            <label for="subject"><?php _e('Subject', 'elena') ; ?> (<?php _e('optional', 'elena'); ?>)</label> <?php ContactForm::the_subject() ; ?>
            <label for="message"><?php _e('Message', 'elena') ; ?></label> <?php ContactForm::your_message() ; ?>
            <label for="yourName"><?php _e('Your name', 'elena') ; ?> (<?php _e('optional', 'elena'); ?>)</label> <?php ContactForm::your_name() ; ?>
            <label for="yourEmail"><?php _e('Your e-mail address', 'elena') ; ?></label> <?php ContactForm::your_email(); ?>

            <div style="float:left;clear:both;width:100%;margin:5px 0 10px 0;">
              <?php osc_run_hook("anr_captcha_form_field"); ?>
            </div>

            <button type="submit"><?php _e('Send', 'elena') ; ?></button>
          </fieldset>
        </form>
      </div>
    </div>
    
    <?php ContactForm::js_validation(); ?>
    <?php osc_current_web_theme_path('footer.php') ; ?>
  </body>
</html>