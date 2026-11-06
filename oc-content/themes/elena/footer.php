<?php
  osc_show_widgets('footer');
  $sQuery = osc_esc_js(osc_get_preference('keyword_placeholder', 'elena_theme'));
?>

</div></div>

<!-- /container -->
<!-- footer -->

<div id="footer">
  <div class="footer-design">
    <div class="dnu">
      <div class="wrap-f">
        <a href="<?php echo osc_base_url();?>" title="<?php _e("Go to home page", 'elena');?>"><?php _e("Home", 'elena');?></a>
        <?php osc_goto_first_category() ; ?>
        <?php while ( osc_has_categories() ) { ?>
          <div><span class="dele">|</span> <a href="<?php echo osc_search_category_url(); ?>" title="<?php echo osc_category_description() ; ?>"><?php echo osc_category_name(); ?></a></div>
        <?php } ?>
      </div>

      <?php if( osc_users_enabled() || ( !osc_users_enabled() && !osc_reg_user_post() )) { ?>
        <a class="btn btn-orange" id="publish_button" href="<?php echo osc_item_post_url() ; ?>"><?php _e("Publish your ad for free", 'elena');?></a>
      <?php } ?>
    </div>
  </div>

  <div class="inner">
    <div class="block">
      <div class="b">
        <?php osc_reset_static_pages() ; ?>
        <div class="tit"><?php _e("Useful information", 'elena');?></div>

        <ul>
          <?php $p_c = 0; ?>
          <?php while( osc_has_static_pages() and $p_c < 5 ) { ?>
            <li><a href="<?php echo osc_static_page_url() ; ?>"><?php echo osc_static_page_title() ; ?></a></li>
            <?php $p_c = $p_c + 1; ?>
          <?php } ?> 
        </ul>
      </div>

      <?php osc_goto_first_category() ; ?>
      <div class="b">
        <div class="tit"><?php _e("Favorite categories", 'elena');?></div>
          <ul>
            <?php $count_i = 0; ?>
            <?php while ( osc_has_categories() ) { if ($count_i < 5) { ?>
              <li><a href="<?php echo osc_search_category_url() ; ?>"><?php echo osc_category_name() ; ?> </a></li>
            <?php } $count_i = $count_i + 1; } ?>
          </ul>
        </div>

        <?php osc_reset_latest_items();?>
        <div class="b">
          <div class="tit"><?php _e("Latest listings", 'elena');?></div>
          <ul>
            <?php $count_i = 0; ?>
            <?php while(osc_has_latest_items())  { if ($count_i < 5) { ?>
              <li><a href="<?php echo osc_item_url() ; ?>" title="<?php echo osc_item_title(); ?>"><?php echo ucfirst( osc_highlight( strip_tags( osc_item_title() ), 50 ) ); ?></a></li>
            <?php } $count_i = $count_i + 1; } ?>
          </ul>
        </div>
      </div>

      <div class="block">
        <div class="border"></div>
        <div class="tit"><?php _e("Best classifieds script Osclass", 'elena'); ?></div>
        <div class="site-desc">
          <?php echo osc_get_preference('footer_desc', 'elena_theme'); ?>
        </div>
      </div>

      <div class="block2">
        <div class="border"></div>
          <div id="footer_popis"><span class="bold">&copy; <?php echo date("Y"); ?></span>
          <div id="t1"></div><div id="t2"></div><div id="t3"></div><div id="t4"></div><div id="t5"></div><div id="t6"></div><div id="t7"></div><div id="t8"></div>
          <div class="bla">
            <a href="https://osclasspoint.com/">OSclass plugins and themes</a> &gt; 
            <a href="https://osclass-classifieds.com">Classifieds Script Osclass</a> &gt; 
            <a id="link" href="<?php echo osc_contact_url(); ?>"><?php _e('Contact', 'elena') ; ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- /footer -->

<!-- skripy a .js -->
<script type="text/javascript">
  var sQuery = '<?php echo $sQuery ; ?>'; 
  function doSearch() {  
    if($('#query').val() == sQuery || $('.query-side').val() == sQuery) {
      $('#query').val('');
      $('.query-side').val('');
    }
  }
</script>

<?php osc_run_hook('footer') ; ?>