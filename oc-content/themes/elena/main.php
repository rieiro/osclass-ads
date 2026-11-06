<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
  <head>
    <?php osc_current_web_theme_path('head.php'); ?>
  </head>
  
  <body>
    <?php osc_current_web_theme_path('header.php'); ?>

    <?php
      $map_short = osc_esc_html( osc_get_preference('active_map', 'elena_theme'));
      $map_class = "";
      $cat_cols = 4;

      if($map_short == 'br-map' or $map_short == 'ch-map' or $map_short == 'id-map' or $map_short == '' or $map_short == '0') {
        $map_class = "new-line";
        $cat_cols = 6;
      } else if ($map_short == 'jp-map') {
        $map_class = "shorter";
        $cat_cols = 3;
      } else if ($map_short == 'ma-map') {
        $map_class = "go-top";
      }
    ?>

    <div class="content home">
      <div id="main">
        <div class="main-left <?php echo $map_class; ?>">
          <div id="sel_cat"><i class="fa fa-list-ul"></i><?php _e('Select a category', 'elena'); ?></div>
          
          <?php
            $total_categories   = osc_count_categories();
            $col_max_cat = array();

            for($i = 1; $i <= $cat_cols; $i++) {
              $col_max_cat[$i] = round($total_categories/$cat_cols);
            }

            $col_max_cat[$cat_cols] = $total_categories - ($cat_cols - 1)*round($total_categories/$cat_cols);

            $dif = $col_max_cat[$cat_cols] - $col_max_cat[1];
            $col_max_cat[$cat_cols] = $col_max_cat[$cat_cols] - $dif;

            for($i = 1; $i <= $dif; $i++) {
              $col_max_cat[$i]++;
            }
          ?>
          
          <div class="categories <?php echo 'c' . $total_categories; ?> cat-cols<?php echo $cat_cols; ?>">
            <?php osc_goto_first_category(); ?>
            <?php
              $i = 1;
              $x = 1;
              $col = 1;
              $count_i_max = 100;
              
              if(osc_count_categories () > 0) {
                  echo '<div class="col c1">';
              }
            ?>
            
            <?php $count_i = 0; ?>
            <?php while ( osc_has_categories() ) { ?>
              <?php $count_i = $count_i + 1;?>
              <div class="category <?php if($count_i == $col_max_cat[$col]) { $count_i = 0; ?>last<?php } ?>">
                <a href="<?php echo osc_search_category_url(); ?>" title="<?php echo osc_category_description(); ?>" alt="<?php echo osc_category_name(); ?>">
                  <?php
                    if(file_exists(osc_themes_path() . 'elena/images/large_cat/' . osc_category_id() . '.png')) {
                      $cat_img = osc_base_url() . 'oc-content/themes/elena/images/large_cat/' . osc_category_id() . '.png';
                    } else {
                      $cat_img = osc_base_url() . 'oc-content/themes/elena/images/large_cat/default_cat.png';
                    }
                  ?>              
                  <img id="main_img" src="<?php echo $cat_img; ?>" alt="<?php echo osc_category_slug();?>"/>
                </a>
                
                <h1>
                  <strong>
                    <a href="<?php echo osc_search_category_url(); ?>"><?php echo osc_category_name(); ?></a>
                  </strong>
                </h1>
              </div>

              <?php
                if($i == $col_max_cat[$col] or $x == $total_categories) {
                  $i = 1;
                  $col++;
                  echo '</div>';
                  if($x < $total_categories) {
                      echo '<div class="col c'.$col.'">';
                  }
                } else {
                  $i++;
                }
                
                $x++;
              ?>
            <?php } ?>
          </div>

        </div>

        <div class="main-right <?php echo $map_class; ?>">
          <div id="sel_reg" class="resp"><i class="fa fa-map-marker"></i><?php _e('Select a region', 'elena'); ?></div>
          <div id="map-wrap-home">
            <?php if(function_exists('osc_base_url')) { $base_url = osc_base_url(); } else { $base_url = 'http://www.elena.mb-themes.com/'; } ?>

            <!-- MAP FEATURES -->
            <?php if($map_short <> '0' && $map_short <> '') { ?>
              <?php if($map_short == 'it-map') { ?>
                <?php if (function_exists('display_italy_map')) { display_italy_map(); } else { echo '<div class="error">' . __('Install ITALLY ROLLOVER MAP plugin to be able to see Itally map', 'elena') . '</div>'; } ?>
              <?php } else { ?>
                <?php include 'maps/' . $map_short . '/map.php'; ?>
              <?php } ?>
            <?php } ?>
          </div>
        </div>
      </div>

      <?php //osc_reset_latest_items();?>
      <div class="latest_ads">
        <h1><strong><?php _e('Latest Listings', 'elena'); ?></strong></h1>
        <?php if( osc_count_latest_items() === 0) { ?>
          <p class="empty"><?php _e('No Latest Listings', 'elena'); ?></p>
        <?php } else { ?>
          <div id="main">
            <div class="ad_list">          
              <?php $class = 'even'; $count = 0; ?>
              <?php while(osc_has_latest_items()) { ?>
                <div class="tr <?php echo $class; ?>">
                  <div class="td date"> 
                    <?php if(osc_item_is_premium()) { ?>
                      <div class="prem-keeper-l">
                        <span class="prem-title-l"><?php _e('premium', 'elena'); ?></span>
                      </div>
                    <?php } ?>

                    <?php echo osc_format_date(osc_item_pub_date()); ?>
                  </div>
                  
                  <div class="td photo <?php if(osc_count_item_resources() > 1) { ?>more-photo<?php } ?>">
                    <div class="photo-wrap">
                      <?php if(osc_count_item_resources()) { ?>
                        <a href="<?php echo osc_item_url(); ?>"><img src="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_item_title(); ?>" alt="<?php echo osc_item_title(); ?>" class="round2" /></a>
                      <?php } else { ?>
                        <a href="<?php echo osc_item_url(); ?>"><img src="<?php echo osc_base_url(); ?>oc-content/themes/elena/images/no_photo.gif" title="<?php echo osc_item_title(); ?>" alt="<?php echo osc_item_title(); ?>" class="round2" /></a>
                      <?php } ?>    

                      <?php if(osc_count_item_resources() > 1) { ?>
                        <img src="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_item_title(); ?>" class="photo-level" />
                      <?php } ?> 
                    </div>                         
                  </div>
                  
                  <div class="td text">
                    <h3><a id="s_tit" href="<?php echo osc_item_url(); ?>" title="<?php echo osc_item_title(); ?>"><?php echo ucfirst( osc_highlight( strip_tags( osc_item_title() ), 90 ) ); ?></a><div id="icon-link"></div><?php if( osc_price_enabled_at_items() ) { echo '<span id="zoznam_cena">' . osc_item_formated_price() . '</span>';} ?></h3>
                    <div class="clear"></div>
                    
                    <p class="loc-list">
                      <strong><?php if (osc_item_country() != '') { echo '<span id="zoznam_span">' . osc_item_country() . '</span> - '; } ?><?php if (osc_item_city() != '') { echo '<span id="zoznam_span">' . osc_item_city() . '</span> - '; } ?><?php if ( osc_item_region()!='') { echo '<span id="zoznam_span">' . osc_item_region() . '</span>'; } ?></span></strong>
                    </p>
                    <p id="zoznam_popis"><?php echo osc_highlight( strip_tags( osc_item_description() ), 130, '<span id="zoznam_highlight" title="Searched word">', '</span>'  ); ?></p>
                  </div>
                </div>
                
                <?php $class = ($class == 'even') ? 'odd' : 'even'; ?>
                <?php $count = $count + 1; ?>
              <?php } ?>
            </div>
          </div>

          <?php if( osc_count_latest_items() == osc_max_latest_items() ) { ?>
            <p class='pagination'><?php echo osc_search_pagination(); ?></p>
            <p class="see_more_link">
              <a href="<?php echo osc_search_show_all_url();?>">
                <strong><?php _e('See all offers', 'elena'); ?></strong>
              </a>
            </p>
          <?php } ?>
            
          <?php View::newInstance()->_erase('items'); ?>
        <?php } ?>
      </div>
    </div> 

    <?php osc_current_web_theme_path('footer.php'); ?>
  </body>
</html>