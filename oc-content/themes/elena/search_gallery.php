<div id="s-gal">
  <?php osc_get_premiums(2); ?>
                
  <?php if(osc_count_premiums() > 0) {?>
    <div class="not-premium"><?php _e('Premium listings', 'elena'); ?></div>

    <div id="prem">
      <?php $class = 'even'; $count = 0; ?>
      <?php while(osc_has_premiums()) { ?>
        <div class="tr <?php echo $class; ?>">
          <div class="td date"> 


            <?php echo osc_format_date(osc_premium_pub_date()); ?>
          </div>
          
          <div class="td photo <?php if(osc_count_premium_resources() > 1) { ?>more-photo<?php } ?>">
            <div class="prem-keeper">
              <span class="prem-title" title="<?php _e('Premium', 'elena'); ?>"><i class="fa fa-star"></i></span>
            </div>

            <?php if( osc_price_enabled_at_items() ) { echo '<span id="zoznam_cena">' . osc_premium_formated_price() . '</span>';} ?>

            <div class="photo-wrap">
              <?php if(osc_count_premium_resources()) { ?>
                <a href="<?php echo osc_premium_url(); ?>"><img src="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_premium_title(); ?>" alt="<?php echo osc_premium_title(); ?>" class="round2" /></a>
              <?php } else { ?>
                <a href="<?php echo osc_premium_url(); ?>"><img src="<?php echo osc_base_url(); ?>oc-content/themes/elena/images/no_photo.gif" title="<?php echo osc_premium_title(); ?>" alt="<?php echo osc_premium_title(); ?>" class="round2" /></a>
              <?php } ?>    
            </div>                         
          </div>
          
          <div class="td text">
            <h3><a id="s_tit" href="<?php echo osc_premium_url(); ?>" title="<?php echo osc_premium_title(); ?>"><?php echo ucfirst( osc_highlight( strip_tags( osc_premium_title() ), 90 ) ); ?></a></h3>
            <div class="clear"></div>
            
            <p class="loc-list">
              <strong><?php if (osc_premium_country() != '') { echo '<span id="zoznam_span">' . osc_premium_country() . '</span> - '; } ?><?php if (osc_premium_city() != '') { echo '<span id="zoznam_span">' . osc_premium_city() . '</span> - '; } ?><?php if ( osc_premium_region()!='') { echo '<span id="zoznam_span">' . osc_premium_region() . '</span>'; } ?></span></strong>
            </p>
          </div>
        </div>
        
        <?php $class = ($class == 'even') ? 'odd' : 'even'; ?>
        <?php $count = $count + 1; ?>
      <?php } ?>
    </div>
  <?php } ?>

  <?php if(osc_count_premiums() > 0) {?>
    <div class="not-premium"><?php _e('Other listings', 'elena'); ?></div>
  <?php } ?>



  <?php $class = 'even'; $count = 0; ?>
  <?php while(osc_has_items()) { ?>
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

        <?php if( osc_price_enabled_at_items() ) { echo '<span id="zoznam_cena">' . osc_item_formated_price() . '</span>';} ?>

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