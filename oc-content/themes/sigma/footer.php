<?php
/*
 * Copyright 2014 Osclass
 * Copyright 2026 Osclass by OsclassPoint.com
 *
 * Osclass maintained & developed by OsclassPoint.com
 * You may not use this file except in compliance with the License.
 * You may download copy of Osclass at
 *
 *     https://osclass-classifieds.com/download
 *
 * Do not edit or add to this file if you wish to upgrade Osclass to newer
 * versions in the future. Software is distributed on an "AS IS" basis, without
 * warranties or conditions of any kind, either express or implied. Do not remove
 * this NOTICE section as it contains license information and copyrights.
 */

?>
</div>
<?php osc_run_hook('after-main'); ?>
</div>
</section>

<?php osc_show_widgets('footer');?>

<?php osc_run_hook('footer_pre'); ?>

<footer>
  <?php osc_run_hook('footer_top'); ?>
  <div class="wrapper">
    <div class="box">
      <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact', 'sigma'); ?></a>

      <?php
        osc_reset_static_pages();
        while(osc_has_static_pages() ) {
      ?>
        <a href="<?php echo osc_static_page_url(); ?>"><?php echo osc_static_page_title(); ?></a>
      <?php } ?>

      <div class="clear"></div>

      <?php if(osc_subdomain_enabled() && osc_subdomain_type() != 'language') { ?>
        <?php echo osc_subdomain_links($with_images = false, $with_counts = true, $with_toplink = true, $limit = 50, $min_item_count = 0); ?>
      <?php } ?>

      <div class="clear"></div>

      <?php osc_run_hook('footer_links'); ?>

      <?php if((!defined('MULTISITE') || MULTISITE==0) && osc_get_preference('footer_link', 'sigma') !== '0') {
        echo '<div class="copy">' . sprintf(__('Powered by <a title="Osclass classifieds script" href="%s">best classifieds scripts</a> osclass'), 'https://osclass-classifieds.com') . '</div>';
      } ?>

      <?php if(osc_count_web_enabled_locales() > 1) { ?>
        <div class="language">
          <?php osc_goto_first_locale(); ?>
          <?php while(osc_has_web_enabled_locales() ) { ?>
            <a id="<?php echo osc_locale_code(); ?>" href="<?php echo osc_change_language_url ( osc_locale_code() ); ?>" class="<?php if(osc_locale_code() == osc_current_user_locale()) { ?>active<?php } ?>"><?php echo osc_locale_name(); ?></a>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  </div>

  <?php osc_run_hook('footer'); ?>
</footer>

<?php osc_run_hook('footer_after'); ?>

<!-- Mobile & Android App-Like Bottom Navigation Bar -->
<nav class="olx-app-bottom-nav">
  <a href="<?php echo osc_base_url(); ?>" class="olx-nav-tab <?php echo osc_is_home_page() ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
    <span>Início</span>
  </a>

  <a href="<?php echo osc_search_url(); ?>" class="olx-nav-tab <?php echo osc_is_search_page() ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    <span>Buscar</span>
  </a>

  <a href="<?php echo osc_item_post_url_in_category(); ?>" class="olx-nav-tab tab-publish">
    <div class="publish-circle">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
    </div>
    <span>Anunciar</span>
  </a>

  <a href="<?php echo osc_is_web_user_logged_in() ? osc_render_file_url('olx_chat/user_chat.php') : osc_user_login_url(); ?>" class="olx-nav-tab">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    <span>Chat</span>
  </a>

  <a href="<?php echo osc_is_web_user_logged_in() ? osc_user_dashboard_url() : osc_user_login_url(); ?>" class="olx-nav-tab">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
    <span>Conta</span>
  </a>
</nav>

<link href="<?php echo osc_assets_url('css/jquery-ui/jquery-ui.css'); ?>" rel="stylesheet" type="text/css" />

</body>
</html>
