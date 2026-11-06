<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
  <head>
    <?php osc_current_web_theme_path('head.php'); ?>
    <?php if( osc_count_items() == 0 || Params::getParam('iPage') > 0 || stripos($_SERVER['REQUEST_URI'], 'search') )  { ?>
      <meta name="robots" content="noindex, nofollow" />
      <meta name="googlebot" content="noindex, nofollow" />
    <?php } else { ?>
      <meta name="robots" content="index, follow" />
      <meta name="googlebot" content="index, follow" />
    <?php } ?>
  </head>
  
  <body>
    <?php osc_current_web_theme_path('header.php'); ?>
    <div class="content list search-page">
      <div id="show_hide_filters"></div>

      <div id="sidebar-wrap">
        <div id="sidebar">
          <?php
            $cat_id = osc_search_category_id();
            $cat_id = @$cat_id[0];
          ?>

          <form action="<?php echo osc_base_url(true); ?>" method="get" onsubmit="doSearch();" class="search-sidebar nocsrf">
            <input type="hidden" name="page" value="search" />
            <input type="hidden" name="sCategory" value="<?php echo $cat_id; ?>" />
            <input type="hidden" name="sOrder" value="<?php echo osc_search_order(); ?>" />
            <input type="hidden" name="iOrderType" value="<?php $allowedTypesForSorting = Search::getAllowedTypesForSorting(); echo $allowedTypesForSorting[osc_search_order_type()]; ?>" />

            <?php foreach(osc_search_user() as $userId) { ?>
              <input type="hidden" name="sUser[]" value="<?php echo $userId; ?>" />
            <?php } ?>

            <h3><?php _e('Advanced search', 'elena'); ?></h3>
            <div class="del"></div>
            <fieldset class="box location">
              <h6><i class="fa fa-pencil"></i> <?php _e('Search', 'elena'); ?></h6>
              
              <div class="row one_input">
                <?php $sQuery = osc_get_preference('keyword_placeholder', 'elena_theme'); ?>
                <input type="text" name="sPattern" id="query" class="query-side" placeholder="<?php echo $sQuery; ?>" value="<?php echo osc_esc_html( osc_search_pattern() ); ?>" />
              </div>

              <?php $aCountries = Country::newInstance()->listAll(); ?>
              
              <div class="row" <?php if(count($aCountries) <= 1 ) {?>style="display:none;"<?php } ?>>
                <h6><i class="fa fa-location-arrow"></i> <?php _e('Country', 'elena') ; ?></h6>

                <?php
                  $s_country = array();
                  // IF THERE IS JUST 1 COUNTRY, PRE-SELECT IT TO ENABLE REGION DROPDOWN
                  if(osc_count_countries() <= 1) {
                    //$s_country = Country::newInstance()->listAll();
                    //$s_country = $s_country[0];
                  }
                ?>

                <select id="countryId" name="sCountry">
                  <option value=""><?php _e('Select a country', 'elena'); ?></option>

                  <?php foreach ($aCountries as $country) {?>
                    <option value="<?php echo $country['pk_c_code']; ?>" <?php if(Params::getParam('sCountry') <> '' && (Params::getParam('sCountry') == $country['pk_c_code'] or Params::getParam('sCountry') == $country['s_name']) or @$s_country['pk_c_code'] <> '' && @$s_country['pk_c_code'] = $country['pk_c_code']) { ?>selected="selected"<?php } ?>><?php echo $country['s_name'] ; ?></option>
                  <?php } ?>
                </select>
              </div>

            
              <?php
                $current_country = Params::getParam('country') <> '' ? Params::getParam('country') : Params::getParam('sCountry');
                if($current_country == '') {
                  $current_country = osc_search_country();
                }

                if($current_country <> '') {
                  $aRegions = Region::newInstance()->findByCountry($current_country);
                } else {
                  if(osc_count_countries() <= 1) {
                    $aRegions = Region::newInstance()->findByCountry(@$s_country['pk_c_code']);
                  } else {
                    $aRegions = '';
                  }
                }
              ?>

              <div class="row">
                <h6><i class="fa fa-map-marker"></i> <?php _e('Region', 'elena') ; ?></h6>

                <?php if(is_array($aRegions) && count($aRegions) >= 1 ) { ?>
                  <select id="regionId" name="sRegion" <?php if(Params::getParam('sRegion') == '' && Params::getParam('region')) {?>disabled<?php } ?>>
                    <option value=""><?php _e('Select a region', 'elena'); ?></option>
                    
                    <?php foreach ($aRegions as $region) {?>
                      <option value="<?php echo $region['pk_i_id']; ?>" <?php if(Params::getParam('sRegion') == $region['pk_i_id'] or Params::getParam('sRegion') == $region['s_name']) { ?>selected="selected"<?php } ?>><?php echo $region['s_name']; ?></option>
                    <?php } ?>
                  </select>
                <?php } else { ?>
                  <input type="text" name="sRegion" id="sRegion-side" value="<?php echo Params::getParam('sRegion'); ?>" placeholder="<?php _e('Enter a region', 'elena'); ?>" />
                <?php } ?>
              </div>
              
              <?php 
                $current_region = Params::getParam('region') <> '' ? Params::getParam('region') : Params::getParam('sRegion');
                if($current_region == '') {
                  $current_region = osc_search_region();
                }

                if(!is_numeric($current_region) && $current_region <> '') {
                  $reg = Region::newInstance()->findByName($current_region);
                  $current_region = @$reg['pk_i_id'];
                }

                if($current_region <> '') {
                  $aCities = City::newInstance()->findByRegion($current_region);
                } else {
                  $aCities = '';
                }
              ?>

              <div class="row">
                <h6><i class="fa fa-home"></i> <?php _e('City', 'elena') ; ?></h6>

                <?php if(is_array($aCities) && count($aCities) >= 1 ) { ?>
                  <select name="sCity" id="cityId" <?php if(Params::getParam('sCity') == '' && Params::getParam('city') == '') {?>disabled<?php } ?>> 
                    <option value=""><?php _e('Select a city', 'elena'); ?></option>

                    <?php if($aCities <> '') { ?>
                    <?php foreach ($aCities as $city) {?>
                      <option value="<?php echo $city['pk_i_id']; ?>" <?php if(Params::getParam('sCity') == $city['pk_i_id'] or Params::getParam('sCity') == $city['s_name']) { ?>selected="selected"<?php } ?>><?php echo $city['s_name']; ?></option>
                    <?php } ?>
                    <?php } ?>
                  </select>
                <?php } else { ?>
                  <input type="text" name="sCity" id="sCity-side" value="<?php echo Params::getParam('sCity'); ?>" placeholder="<?php _e('Enter a city', 'elena'); ?>" />
                <?php } ?>
              </div>
            </fieldset>

            <div class="od"></div>
            
            <div class="s-image"></div>
            <h6><?php _e('Photo', 'elena'); ?></h6>
            
            <div class="del2"></div>
            
            <fieldset class="box show_only">
              <?php if( osc_images_enabled_at_items() ) { ?>
                <div class="row checkboxes">
                   <input type="checkbox" name="bPic" id="withPicture" value="1" <?php echo (osc_search_has_pic() ? 'checked="checked"' : ''); ?> />
                   <label for="withPicture"><?php _e('Show only listings with photo', 'elena'); ?></label>
                </div>
              <?php } ?>


              <?php if( osc_price_enabled_at_items() ) { ?>
                <div class="clear"></div>
                <div class="od"></div>
                
                <div class="s-price"></div>
                <h6><?php _e('Price', 'elena'); ?></h6>
                
                <div class="del2"></div>
                <div class="row two_input">
                  <div class="clear"></div>
                  <div id="hladat_cena_left">
                    <input type="text" id="priceMin" name="sPriceMin" value="<?php echo osc_search_price_min(); ?>" placeholder="<?php if( osc_search_price_min() == '') { _e('Price min.', 'elena');} else { osc_search_price_min();} ?>" size="6" maxlength="6" />
                  </div>
                  
                  <div class="s-price-range">
                    <div class="price-in"></div>
                  </div>
                  
                  <div id="hladat_cena_right">
                    <input type="text" id="priceMax" name="sPriceMax" value="<?php echo osc_search_price_max(); ?>" placeholder="<?php if( osc_search_price_max() == '') { _e('Price max.', 'elena');} else { osc_search_price_max();} ?>" size="6" maxlength="6" />
                  </div>
                </div>
              <?php } ?>

              <div class="extra-search">
                <?php
                  if(osc_search_category_id()) {
                    osc_run_hook('search_form', osc_search_category_id());
                  } else {
                    osc_run_hook('search_form');
                  }
                ?>
              </div>            
            </fieldset>

            <button type="submit"><?php _e('Search', 'elena'); ?></button>
            <div class="clear"></div>
          </form>
        </div>

        
        <?php $max_sub = 3; ?>

        <div id="menu">
          <h3 id="menu_h3">
            <span><?php _e('Categories', 'elena'); ?></span>
            <div id="closer" class="s-minus"></div>
          </h3>
          <div class="clear"></div>

          <?php $search_params = elena_search_params(); ?>

          <div class="menu-wrap">
            <?php $current_cat = osc_category_id($locale = ""); ?>

            <?php osc_goto_first_category(); ?>              
            <?php while ( osc_has_categories() ) { ?>
              <?php $parent_id = osc_category_id($locale = ""); ?>
              
              <div class="category">
                <?php $search_params['sCategory'] = osc_category_id(); ?>
                <img class="small_img" src='<?php echo osc_current_web_theme_url(); ?>images/small_cat/<?php echo osc_category_id(); ?>.png' /><h2 <?php if ($parent_id == $current_cat) { echo ' class="is_parent" '; }  ?>><a href="<?php echo osc_search_url($search_params); ?>"><?php echo osc_category_name(); ?> </a> <span>(<?php echo osc_category_total_items(); ?>)</span></h2>

                <?php if ( osc_count_subcategories() > 0 ) { ?>
                  <ul class="subcategory" id="showSubcat<?php echo $cat_id; ?>" >
                    <?php $pocitaj_subcat = 1; ?>
                    
                    <?php while ( osc_has_subcategories() ) {  $subcat_id = osc_category_id($locale = ""); if ($subcat_id == $cat_id) {?><!-- Code for selected subcategory --><?php }  ?>
                      <?php $search_params['sCategory'] = osc_category_id(); ?>
                      <?php $child_id = osc_category_id($locale = ""); ?>
                      
                      <li id="topbar_element" <?php if ($child_id == $current_cat) { echo ' class="is_child" '; }  ?>><a class="category <?php echo osc_category_slug(); ?>" href="<?php echo osc_search_url($search_params); ?>"><?php echo osc_category_name(); ?></a></li>
                      <?php if ($pocitaj_subcat == $max_sub) { ?>
                        <li onclick="hide_viac('viac<?php echo $parent_id; ?>')" class="viac_main" id="viac<?php echo $parent_id; ?>">
                          <?php _e('Show more', 'elena'); ?>
                        </li>
                        
                        <div class="ukaz" id="viac<?php echo $parent_id; ?>block">
                      <?php } ?>
                      <?php $pocitaj_subcat++; ?>
                    <?php } ?>
                    
                    <?php if ($pocitaj_subcat > $max_sub) { ?></div><?php } // ending of div that can be hidden and then expanded?>
                  </ul>
                <?php } ?>
              </div>                
            <?php } ?>
          </div>
        </div>

        <div id="bottom-menu-grad"></div>
      </div>

      <div id="main" class="s-list">
        <div class="ad_list">
          <div id="list_head">
            <div class="inner">
              <h1>
                <strong><?php echo search_title(); ?></strong>
              </h1>
              
              <?php osc_alert_form(); ?>

              <div class="list-gallery">
                <span><?php _e('Change view:', 'elena'); ?></span>
                <?php $old_show = Params::getParam('sShowAs') == '' ? 'list' : Params::getParam('sShowAs'); ?>

                <?php $params['sShowAs'] = 'list'; ?>
                <a href="<?php echo osc_update_search_url($params); ?>" <?php echo ($old_show == $params['sShowAs'] ? 'class="active"' : ''); ?> title="<?php _e('Switch to list view', 'elena'); ?>"><i class="fa fa-th-list"></i></a>
                <?php $params['sShowAs'] = 'gallery'; ?>
                <a href="<?php echo osc_update_search_url($params); ?>" <?php echo ($old_show == $params['sShowAs'] ? 'class="active"' : ''); ?> title="<?php _e('Switch to grid view', 'elena'); ?>"><i class="fa fa-th"></i></a>
              </div>

              <div class="user_type_buttons">
                <div class="all <?php if(Params::getParam('sCompany') == '' or Params::getParam('sCompany') == null) { ?>active<?php } ?>"><span><?php _e('All'); ?></span></div>
                <div class="individual <?php if(Params::getParam('sCompany') == '0') { ?>active<?php } ?>"><span><?php _e('Personal'); ?></span></div>
                <div class="company <?php if(Params::getParam('sCompany') == '1') { ?>active<?php } ?>"><span><?php _e('Company'); ?></span></div>
              </div>

              <p class="see_by">
                <span id="zoznam_count">
                  <?php _e('Showing', 'elena'); ?> <?php echo osc_default_results_per_page_at_search()*(osc_search_page())+1; ?> - <?php echo osc_default_results_per_page_at_search()*(osc_search_page()+1)+osc_count_items()-osc_default_results_per_page_at_search(); ?> <?php _e('listings of', 'elena'); ?> <?php echo osc_search_total_items() ?>
                </span>
                
                <?php _e('Sort by', 'elena'); ?>:
                <?php $i = 0; ?>
                <?php $orders = osc_list_orders(); ?>
                
                <?php foreach($orders as $label => $params) { ?>
                  <?php $orderType = ($params['iOrderType'] == 'asc') ? '0' : '1'; ?>
                  
                  <?php if(osc_search_order() == $params['sOrder'] && osc_search_order_type() == $orderType) { ?>
                    <a class="current" href="<?php echo osc_update_search_url($params); ?>"><?php echo $label; ?></a>
                  <?php } else { ?>
                    <a href="<?php echo osc_update_search_url($params); ?>"><?php echo $label; ?></a>
                  <?php } ?>
                  
                  <?php if ($i != count($orders)-1) { ?>
                    <span>|</span>
                  <?php } ?>
                  
                  <?php $i++; ?>
                <?php } ?>
              </p>
            </div>
          </div>
          
          <?php if(osc_count_items() == 0) { ?>
            <p class="empty" ><?php printf(__('There are no results matching "%s"', 'elena'), osc_search_pattern()); ?></p>
          <?php } else { ?>
            <?php require(osc_search_show_as() == 'list' ? 'search_list.php' : 'search_gallery.php'); ?>
          <?php } ?>
                       
          <div class="paginate" >
            <?php echo osc_search_pagination(); ?>
          </div>
          
          <div class="clear"></div>
        </div>
      </div>
    </div>

    <div class="clear"></div>

    <script>
      function hide_viac(id) {
        $("#" + id).slideUp("slow");
        $("#" + id + "block").show("slow", "swing");
      }
    </script>

    <script>
      $(document).ready(function() {
        $('.user_type_buttons div').click(function() {
        if($(this).hasClass('all')) {
          $('input#sCompany').val('');
        }

        if($(this).hasClass('individual')) {
          $('input#sCompany').val(0);
        }

        if($(this).hasClass('company')) {
          $('input#sCompany').val(1);
        }

        $('form.search').submit();
        });
      });
    </script>

    <!-- JAVASCRIPT AJAX LOADER FOR COUNTRY/REGION/CITY SELECT BOX -->
    <script>
      $(document).ready(function(){
        $("body").on("change", "#countryId", function(){
          var pk_c_code = $(this).val();
          var url = '<?php echo osc_base_url(true)."?page=ajax&action=regions&countryId="; ?>' + pk_c_code;
          var result = '';

          if(pk_c_code != '') {
            $("#regionId").attr('disabled',false);
            $("#uniform-regionId").removeClass('disabled');
            $("#cityId").attr('disabled',true);
            $("#uniform-cityId").addClass('disabled');

            $.ajax({
              type: "POST",
              url: url,
              dataType: 'json',
              success: function(data){
                var length = data.length;
                
                if(length > 0) {

                  result += '<option value=""><?php _e('Select a region', 'elena'); ?></option>';
                  for(key in data) {
                    result += '<option value="' + data[key].pk_i_id + '">' + data[key].s_name + '</option>';
                  }

                  $("#sRegion-side").before('<div class="selector" id="uniform-regionId"><span><?php _e('Select a region', 'elena'); ?></span><select name="sRegion" id="regionId" ></select></div>');
                  $("#sRegion-side").remove();

                  $("#sCity-side").before('<div class="selector" id="uniform-cityId"><span><?php _e('Select a city', 'elena'); ?></span><select name="sCity" id="cityId" ></select></div>');
                  $("#sCity-side").remove();
                  
                  $("#regionId").val("");
                  $("#uniform-regionId").find('span').text('<?php _e('Select a region', 'elena'); ?>');
                } else {

                  $("#regionId").parent().before('<input placeholder="<?php _e('Enter a region', 'elena'); ?>" type="text" name="sRegion" id="sRegion-side" />');
                  $("#regionId").parent().remove();
                  
                  $("#cityId").parent().before('<input placeholder="<?php _e('Enter a city', 'elena'); ?>" type="text" name="sCity" id="sCity-side" />');
                  $("#cityId").parent().remove();

                  $("#sCity-side").val('');
                }

                $("#regionId").html(result);
                $("#cityId").html('<option selected value=""><?php _e('Select a city', 'elena'); ?></option>');
                $("#uniform-cityId").find('span').text('<?php _e('Select a city', 'elena'); ?>');
              }
             });

           } else {

             // add empty select
             $("#sRegion-side").before('<div class="selector" id="uniform-regionId"><span><?php _e('Select a region', 'elena'); ?></span><select name="sRegion" id="regionId" ><option value=""><?php _e('Select a region', 'elena'); ?></option></select></div>');
             $("#sRegion-side").remove();
             
             $("#sCity-side").before('<div class="selector" id="uniform-cityId"><span><?php _e('Select a city', 'elena'); ?></span><select name="sCity" id="cityId" ><option value=""><?php _e('Select a city', 'elena'); ?></option></select></div>');
             $("#sCity-side").remove();

             if( $("#regionId").length > 0 ){
               $("#regionId").html('<option value=""><?php _e('Select a region', 'elena'); ?></option>');
             } else {
               $("#sRegion-side").before('<div class="selector" id="uniform-regionId"><span><?php _e('Select a region', 'elena'); ?></span><select name="sRegion" id="regionId" ><option value=""><?php _e('Select a region', 'elena'); ?></option></select></div>');
               $("#sRegion-side").remove();
             }

             if( $("#cityId").length > 0 ){
               $("#cityId").html('<option value=""><?php _e('Select a city', 'elena'); ?></option>');
             } else {
               $("#sCity-side").parent().before('<div class="selector" id="uniform-cityId"><span><?php _e('Select a city', 'elena'); ?></span><select name="sCity" id="cityId" ><option value=""><?php _e('Select a city', 'elena'); ?></option></select></div>');
               $("#sCity-side").parent().remove();
             }

             $("#regionId").attr('disabled',true);
             $("#uniform-regionId").addClass('disabled');
             $("#uniform-regionId").find('span').text('<?php _e('Select a region', 'elena'); ?>');
             $("#cityId").attr('disabled',true);
             $("#uniform-cityId").addClass('disabled');
             $("#uniform-cityId").find('span').text('<?php _e('Select a city', 'elena'); ?>');

          }
        });

        $("body").on("change", "#regionId", function(){
          var pk_c_code = $(this).val();
          var url = '<?php echo osc_base_url(true)."?page=ajax&action=cities&regionId="; ?>' + pk_c_code;
          var result = '';

          if(pk_c_code != '') {
            
            $("#cityId").attr('disabled',false);
            $("#uniform-cityId").removeClass('disabled');

            $.ajax({
              type: "POST",
              url: url,
              dataType: 'json',
              success: function(data){
                var length = data.length;
                if(length > 0) {
                  result += '<option selected value=""><?php _e('Select a city', 'elena'); ?></option>';
                  for(key in data) {
                    result += '<option value="' + data[key].pk_i_id + '">' + data[key].s_name + '</option>';
                  }

                  $("#sCity-side").before('<div class="selector" id="uniform-cityId"><span><?php _e('Select a city', 'elena'); ?></span><select name="sCity" id="cityId" ></select></div>');
                  $("#sCity-side").remove();

                  $("#cityId").val("");
                  $("#uniform-cityId").find('span').text('<?php _e('Select a city', 'elena'); ?>');
                } else {
                  result += '<option value=""><?php _e('No cities found', 'elena'); ?></option>';
                  $("#cityId").parent().before('<input type="text" placeholder="<?php _e('Enter a city', 'elena'); ?>" name="sCity" id="sCity-side" />');
                  $("#cityId").parent().remove();
                }
                $("#cityId").html(result);
              }
            });
          } else {
            $("#cityId").attr('disabled',true);
            $("#uniform-cityId").addClass('disabled');
            $("#uniform-cityId").find('span').text('<?php _e('Select a city', 'elena'); ?>');
          }
        });

        if( $("#regionId").attr('value') == "")  {
          $("#cityId").attr('disabled',true);
          $("#uniform-cityId").addClass('disabled');
        } else {
          $("#cityId").attr('disabled',false);
          $("#uniform-cityId").removeClass('disabled');
        }

        if($("#countryId").length != 0) {
          if( $("#countryId").attr('value') == "")  {
            $("#regionId").attr('disabled',true);
            $("#uniform-regionId").addClass('disabled');
          }
        }

        //Make sure when select loads after input, span wrap is correctly filled
        $(".row").on('change', '#cityId, #regionId', function() {
          $(this).parent().find('span').text($(this).find("option:selected" ).text());
        });

      });
    </script>
 
    <?php osc_current_web_theme_path('footer.php'); ?>
  </body>
</html>