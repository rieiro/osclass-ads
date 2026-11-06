<?php
  $sQuery = osc_get_preference('keyword_placeholder', 'elena_theme');
  if(osc_count_countries() > 1) { $show_country = true; } else { $show_country = false; }
?>

<script type="text/javascript">
  var sQuery = '<?php echo osc_esc_js( $sQuery ); ?>' ;


  /*
  $(document).ready(function(){
    $('input[name=sPattern]').click(function(){
      $('input[name=sPattern]').prop('disabled', true);
      $(this).prop('disabled', false);
    });


    $('html').click(function() {
      $('input[name=sPattern]').prop('disabled', false);
    });

    $('input[name=sPattern]').click(function(event){
      event.stopPropagation();
    });

 
    if($('input[name=sPattern]:enabled').val() == sQuery) {
      $('input[name=sPattern]:enabled').css('color', 'gray');
    }

    $('input[name=sPattern]:enabled').click(function(){
      if($('input[name=sPattern]:enabled').val() == sQuery) {
        $('input[name=sPattern]:enabled').val('');
        $('input[name=sPattern]:enabled').css('color', '');
      }
    });

    $('input[name=sPattern]:enabled').blur(function(){
      if($('input[name=sPattern]:enabled').val() == '') {
        $('input[name=sPattern]:enabled').val(sQuery);
        $('input[name=sPattern]:enabled').css('color', 'gray');
      }
    });

    $('input[name=sPattern]:enabled').keypress(function(){
      $('input[name=sPattern]:enabled').css('background','');
    })

    if($('input[name=sPattern]:enabled').val() == '') { 
      $('input[name=sPattern]:enabled').val(sQuery); 
      $('input[name=sPattern]:enabled').css('color', 'gray');
    }
  });
  */


  $(document).ready(function() {
    if(!$('.cw-el').length) {
      $('.cw-list').hide();
      $('.cw-header').css('border-bottom', 'none');
    }

    $('#ul-wrap').hover(function() {
      $(this).find('#cat-box').stop(true, true).fadeIn('300');
      $(this).find('#cat-list').css('overflow-y', 'scroll');
    }, function() {
      $(this).find('#cat-box').stop(true, true).delay(500).fadeOut('300');
    });

    $('#cat-list li').click(function() {
      $('#sCategory').val( $(this).val() );
      $('#cat-text').text( $(this).text().trim() );
      $('#cat-box').stop(true, true).fadeOut('200');
    });

    $('#ul-wrap-loc').hover(function() {
      $(this).find('#loc-box').stop(true, true).fadeIn('300');
      $(this).find('#loc-list').css('overflow-y', 'scroll');
    }, function() {
      $(this).find('#loc-box').stop(true, true).delay(500).fadeOut('300');
    });

    $('#loc-list li').click(function(){
      $('#loc-text').text( $(this).text().trim() );

      var isreg = $(this).attr('rel');
      if(!isreg.indexOf("--")) { 
        $('#sCity').val(isreg.substring(2, isreg.length));
      } else if(!isreg.indexOf("//")) { 
        $('#sRegion').val(isreg.substring(2, isreg.length));
        $('#sCity').val('');
      } else {
        <?php if($show_country) { ?>$('#sCountry').val(isreg);<?php } ?>
        $('#sRegion').val('');
        $('#sCity').val('');
      }

      $('#loc-box').stop(true, true).fadeOut('200');
    });
  });
</script>

<?php if(@$_GET['sCountry'] <> '') { $ctr = $_GET['sCountry']; } else { $ctr = @$_GET['sCountry_radius']; } ?>
<?php if($ctr == '') { $ctr = strtoupper(substr(osc_current_user_locale(), 3)); } ?>

<form action="<?php echo osc_base_url(true) ; ?>" method="get" class="search nocsrf" onsubmit="doSearch();">
  <input type="hidden" name="page" value="search" />
  <input type="hidden" name="sCompany" class="sCompany" id="sCompany" value="<?php echo Params::getParam('sCompany');?>" />

  <?php if(osc_is_ad_page() or osc_is_search_page()) { ?>

    <fieldset class="alt-search">
      <!--<input type="text" class="light-shadow" name="sPattern" id="query" value="<?php echo osc_esc_html( ( osc_search_pattern() != '' ) ? osc_search_pattern() : $sQuery ); ?>" />-->
      <input type="text" class="light-shadow" name="sPattern" id="query" placeholder="<?php echo $sQuery; ?>" value="<?php echo osc_esc_html( osc_search_pattern() ) ; ?>" />

      <?php  if ( osc_count_categories() ) { ?>
        <?php
          $cat_id = osc_search_category_id();
          $cat_id = @$cat_id[0];
          if($cat_id <> '' and $cat_id <> 0) { $has_cat = true; } else { $has_cat = false; }
          $cat = Category::newInstance()->findByPrimaryKey($cat_id);
          $cat_name = @$cat['s_name'];
        ?>
        
        <input id="sCategory" type="hidden" name="sCategory" value="<?php if($has_cat) { echo $cat_id; } ?>" />
        <?php if($show_country) { ?><input type="hidden" name="sCountry" id="sCountry" value="<?php echo $ctr;?>" /><?php } ?>
        <input type="hidden" name="sRegion" id="sRegion" value="<?php echo osc_search_region();?>" />
        <input type="hidden" name="sCity" id="sCity" value="<?php echo osc_search_city();?>" />

        <div id="ul-wrap">
          <div id="cat-text" class="round3 light-shadow"><?php if($has_cat) { echo $cat_name; } else { _e('Select category', 'elena'); } ?></div>
          <div id="cat-box">
            <div class="tool-top-arrow"></div>
            <?php elena_categories_select('sCategory', null, __('Select a category', 'elena')); ?>
          </div>
          <span class="arrow-icon"></span>
        </div>

        <div id="ul-wrap-loc">
          <div id="loc-text" class="round3 light-shadow"><?php echo (osc_search_city() <> '' ? osc_search_city() : ( osc_search_region() <> '' ? osc_search_region() : __('Select location', 'elena'))); ?></div>
          <div id="loc-box">
            <div class="tool-top-arrow"></div>
            <?php elena_location_selector() ; ?>        
          </div>                 
          <span class="arrow-icon"></span>
        </div>
      <?php  } ?>

      <div id="keep">
        <button id="srch" type="submit"><?php _e('Search', 'elena') ; ?></button>
      </div>
    </fieldset>

  <?php } else { ?>

    <fieldset class="main classic-search">
      <input type="text" class="light-shadow" name="sPattern" id="query" value="<?php echo osc_esc_html( ( osc_search_pattern() != '' ) ? osc_search_pattern() : $sQuery ); ?>" />
      <div id="keep">
        <button id="srch" type="submit"><?php _e('Search', 'elena') ; ?></button>
      </div>    
    </fieldset>

  <?php } ?>

  <div id="search-example"></div>
</form>

<?php 
  $alt_cat = osc_search_category_id();
  $cat_id = (@$alt_cat[0] <> '' ? $alt_cat[0] : false); 

  if($cat_id) { 
    $cat = Category::newInstance()->findByPrimaryKey($cat_id);
  }

  if(file_exists(osc_themes_path() . 'elena/images/large_cat/' . $cat_id . '.png')) {
    $cat_img = osc_base_url() . 'oc-content/themes/elena/images/large_cat/' . $cat_id . '.png';
  } else {
    $cat_img = osc_base_url() . 'oc-content/themes/elena/images/large_cat/default_cat.png';
  }
?>

<?php if(osc_is_search_page()) { ?>
  <div id="cat-wide">
    <div class="cw-header">
      <div class="cw-close"></div>
      <img src="<?php echo $cat_img; ?>" alt="<?php echo $cat['s_name']; ?>" />
      <div class="cw-h">
        <?php if($cat_id) { ?>
          <div class="cw-name"><?php echo $cat['s_name']; ?></div>        
          <div class="cw-desc">
            <?php echo $cat['i_num_items'] . ' ' . __('listings', 'elena') . '. ' . $cat['s_description']; if($cat['s_description'] <> '') { echo '.'; } ?> 
            <a class="cw-href" href="<?php echo mb_item_post_url_in_category($cat['pk_i_id']); ?>" target="_blank"><?php _e('Sell in this category', 'elena'); ?> &raquo;</a>
          </div>
        <?php } else { ?>
          <div class="cw-name-alt"><?php _e('Select category', 'elena'); ?></div>        
          <div class="cw-desc">
            <?php echo __('Search in', 'elena') . ' ' . osc_total_active_items()  . ' ' . __('active listings', 'elena') . '. ' . @$cat['s_description']; ?>. 
          </div>
        <?php } ?>
      </div>
    </div>

    <div class="cw-list">
      <?php elena_cw_list($cat_id); ?>
    </div>
  </div>
<?php } ?>