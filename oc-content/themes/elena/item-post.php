<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
  <head>
    <?php osc_current_web_theme_path('head.php') ; ?>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex, nofollow" />
    
    <!-- only item-post.php -->
    <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
    <?php if(osc_images_enabled_at_items()) ItemForm::photos_javascript(); ?>

    <script type="text/javascript">
      <?php if(osc_get_preference('image_upload', 'elena_theme') <> 1) { ?>
        function uniform_input_file(){
          photos_div = $('div.photos');
          $('div',photos_div).each(
            function(){
              if( $(this).find('div.uploader').length == 0  ){
                divid = $(this).attr('id');
                if(divid != 'photos'){
                  divclass = $(this).hasClass('box');
                  if( !$(this).hasClass('box') & !$(this).hasClass('uploader') & !$(this).hasClass('row')){
                    $("div#"+$(this).attr('id')+" input:file").uniform({fileDefaultText: fileDefaultText,fileBtnText: fileBtnText});
                  }
                }
              }
            }
          );
        }
      <?php } ?>
      
      setInterval("uniform_plugins()", 250);

      function uniform_plugins() {        
        var content_plugin_hook = $('#plugin-hook').text();
        content_plugin_hook = content_plugin_hook.replace(/(\r\n|\n|\r)/gm,"");
        if( content_plugin_hook != '' ){
          
          var div_plugin_hook = $('#plugin-hook');
          var num_uniform = $("div[id*='uniform-']", div_plugin_hook ).length;
          if( num_uniform == 0 ){
            if( $('#plugin-hook input:text').length > 0 ){
              $('#plugin-hook input:text').uniform();
            }
            if( $('#plugin-hook select').length > 0 ){
              $('#plugin-hook select').uniform();
            }
          }
        }
      }

      <?php if(osc_locale_thousands_sep()!='' || osc_locale_dec_point() != '') { ?>
      $().ready(function(){
        $("#price").blur(function(event) {
          var price = $("#price").attr("value");
          <?php if(osc_locale_thousands_sep()!='') { ?>
          while(price.indexOf('<?php echo osc_esc_js(osc_locale_thousands_sep());  ?>')!=-1) {
            price = price.replace('<?php echo osc_esc_js(osc_locale_thousands_sep());  ?>', '');
          }
          <?php }; ?>
          <?php if(osc_locale_dec_point()!='') { ?>
          var tmp = price.split('<?php echo osc_esc_js(osc_locale_dec_point())?>');
          if(tmp.length>2) {
            price = tmp[0]+'<?php echo osc_esc_js(osc_locale_dec_point())?>'+tmp[1];
          }
          <?php }; ?>
          $("#price").attr("value", price);
        });
      });
      <?php }; ?>
    </script>
    <!-- end only item-post.php -->
  </head>
  <body>

  <?php 
    $def_cat['fk_i_category_id'] = Params::getParam('sCategory'); 
    $country = Country::newInstance()->listAll();
    $cookie_loc = array();
    
    if(osc_is_web_user_logged_in()) {

      // GET LOCATION OF LOGGED USER
      $cookie_loc = osc_user();

      // IF THERE IS JUST 1 COUNTRY, PRE-SELECT IT TO ENABLE REGION DROPDOWN
      if(count($country) == 1) {
        $country = Country::newInstance()->listAll();
        $country = $country[0];
        $cookie_loc['fk_c_country_code'] = $country['pk_c_code'];
      }
    } else {

      // GET LOCATION FROM SEARCH
      if(Params::getParam('sCountry') <> '') {    
        if(strlen(Params::getParam('sCountry')) == 2) {
          $cookie_loc['fk_c_country_code'] = Params::getParam('sCountry');
        } else {
          $country = Country::newInstance()->findByName(Params::getParam('sCountry'));
          $cookie_loc['fk_c_country_code'] = $country['pk_c_code'];
        }
      } else {
        // IF THERE IS JUST 1 COUNTRY, PRE-SELECT IT TO ENABLE REGION DROPDOWN
        if(count($country) == 1) {
          $country = Country::newInstance()->listAll();
          $country = $country[0];
          $cookie_loc['fk_c_country_code'] = $country['pk_c_code'];
        }
      }



      if(Params::getParam('sRegion') <> '') {
        if(is_numeric(Params::getParam('sRegion'))) {
          $cookie_loc['fk_i_region_id'] = Params::getParam('sRegion');
        } else {
          $region = Region::newInstance()->findByName(Params::getParam('sRegion'));
          $cookie_loc['fk_i_region_id'] = $region['pk_i_id'];
        }
      }
    }


    if(Params::getParam('sCity') <> '') {
      if(is_numeric(Params::getParam('sCity'))) {
        $cookie_loc['fk_i_city_id'] = Params::getParam('sCity');
      } else {
        $city = City::newInstance()->findByName(Params::getParam('sCity'), $cookie_loc['fk_i_region_id']);
        $cookie_loc['fk_i_city_id'] = $city['pk_i_id'];
      }
    }
    
    $region_list = array();
    if(@$cookie_loc['fk_c_country_code'] <> '') {
      $region_list = Region::newInstance()->findByCountry($cookie_loc['fk_c_country_code']);
    } else {
      $region_list = RegionStats::newInstance()->listRegions("%%%%", ">=");
    }

    $city_list = array();
    if(@$cookie_loc['fk_i_region_id'] <> '') {
      $city_list = City::newInstance()->findByRegion($cookie_loc['fk_i_region_id']);
    }
  ?>

  <div id="cat-wrap">
  <div id="cat-list">
    <div class="close-wrap"></div>
    <a class="back-wrap"><div class="icon-add-back"></div><span><?php _e('Back', 'elena'); ?></span></a>
  
    <h2><?php _e('Select a category', 'elena'); ?></h2>

    <div class="cat-block">
    <?php osc_goto_first_category() ; ?>
    <?php while ( osc_has_categories() ) { ?>
      <div class="single-cat round3" id="<?php echo osc_category_id();?>">
      <a href="#" title="<?php echo osc_category_description() ; ?>" alt="<?php echo osc_category_name() ; ?>">
        <h3><?php echo osc_category_name(); ?></h3>
        <img id="main_img" src="<?php if(file_exists(osc_themes_path() . 'elena/images/large_cat/' . osc_category_id() . '.png')) { echo osc_base_url() . 'oc-content/themes/elena/images/large_cat/' . osc_category_id() . '.png'; } else { echo osc_base_url() . 'oc-content/themes/elena/images/large_cat/default_cat.png'; } ?>" alt="<?php echo osc_category_slug();?>"/>
      </a>
      </div>
    <?php } ?>
    </div>

    <?php osc_goto_first_category() ; ?>
    <?php $categories = Category::newInstance()->toTree(); ?>
    <?php foreach($categories as $c) { ?>
    <div class="subcat-list" id="sub-<?php echo $c['pk_i_id'];?>" style="display:none">

      <?php mb_subcat_list($c['categories']); ?>

      <?php /* while( osc_has_subcategories()) { ?>
      <h3><a href="#" class="single-subcat" id="<?php echo osc_category_id();?>"><?php echo osc_category_name() ; ?></a></h3>
      <?php } */ ?>
    </div>
    <?php } ?>
  </div>
  </div>

  <script type="text/javascript">
  $(document).ready(function(){
    $('.subcat-list h3 .icon-add-next').click(function(){
    $(this).siblings('.sub').first().fadeToggle(300);
    });

    $('#cat-wrap').hide();
    $('.cat-alt').click(function(){
    $('#cat-wrap').fadeIn(300);
    }); 
    $('.close-wrap').click(function(){
    $('#cat-wrap').fadeOut(300);
    }); 
  });

  $('.single-cat').click(function(){
    var catID = $(this).attr('id');
    $('.cat-block').fadeOut(300);
    $('#sub-' + $(this).attr('id')).fadeIn(300);
  });

  $('.back-wrap').click(function(){
    $('.cat-block').fadeIn(300);
    $('.subcat-list').fadeOut(300);
  });

  $('.single-subcat').click(function(){
    var subcatID = $(this).attr('id');
    $('#cat-wrap').fadeOut(300);
    $('#catId').val($(this).attr('id'));
    $('#uniform-catId span').html($('#catId').find(':selected').text());
  });
  </script>

    <?php osc_current_web_theme_path('header.php') ; ?>
    <div class="content add_item">
      <h1><strong><?php _e('Add new listing', 'elena'); ?></strong></h1>
      <ul id="error_list"></ul>
      <form name="item" action="<?php echo osc_base_url(true);?>" method="post" enctype="multipart/form-data">
        <fieldset>
        <input type="hidden" name="action" value="item_add_post" />
        <input type="hidden" name="page" value="item" />
          <div class="box general_info">
            <h2><?php _e('General Information', 'elena'); ?></h2>
            <div class="row _200">
              <label for="catId"><?php _e('Category', 'elena'); ?> *</label>
              <?php $def["fk_i_category_id"] = @$_POST['cat_id']; ?>
              <?php ItemForm::category_select(null, $def, __('Select a category', 'elena')); ?>
              <div id="alt_cat_del"><?php _e('or pick', 'elena'); ?></div><div id="alt_category"></div>
            </div>
            <div class="row _150">
              <?php ItemForm::multilanguage_title_description(); ?>
            </div>
          </div>
          <?php if( osc_price_enabled_at_items() ) { ?>
          <div class="box price">
            <label for="price"><?php _e('Price', 'elena'); ?></label>
            <?php ItemForm::price_input_text(); ?>
            <?php ItemForm::currency_select(); ?> <?php _e('or', 'elena'); ?> 
            <select id="PriceSelect">
            <option value="0"><?php _e('Select option', 'elena'); ?></option>
            <option value="1"><?php _e('Free', 'elena'); ?></option>
            <option value="2"><?php _e('Check with seller', 'elena'); ?></option>
            </select>   
          </div>
          
          <?php } ?>

          <?php if( osc_images_enabled_at_items() ) { ?>
          <div class="box photos photoshow <?php if(osc_get_preference('image_upload', 'elena_theme') == 1) { echo 'drag_drop'; } ?>">
            <h2><?php _e('Photos', 'elena'); ?><span id="novy_foto">(<?php _e('Listings with photo are more effecient', 'elena'); ?>)</span></h2>
            <div id="photos">

            <?php if(osc_images_enabled_at_items()) {
              if(modern_is_fineuploader() && osc_get_preference('image_upload', 'elena_theme') == 1) {
                ItemForm::ajax_photos();
                echo '</div>';
            } else { ?>
              <div class="row">
                <input type="file" name="photos[]" />
              </div>
              </div>
              <a href="#" onclick="addNewPhoto(); uniform_input_file(); return false;"><?php _e('Add new photo', 'elena'); ?></a>
            <?php } ?>
          </div>
          <?php } } ?>
        
          <div class="box location">
            <h2><?php _e('Location', 'elena'); ?></h2>

            <div class="row" <?php if(count($country) == 1) { ?>style="display:none;"<?php } ?>>
              <label for="countryId"><?php _e('Country', 'elena'); ?></label>
              <?php ItemForm::country_select(Country::newInstance()->listAll(), $cookie_loc); ?>
            </div>         

            <div class="row">
              <label for="regionId"><?php _e('Region', 'elena'); ?></label>
              <?php ItemForm::region_select($region_list, $cookie_loc); ?>
            </div>

            <div class="row">
              <label for="city"><span><?php _e('City', 'elena'); ?></span></label>
              <?php ItemForm::city_select($city_list, $cookie_loc); ?>
            </div>

            <div class="row">
              <label for="address"><?php _e('Address', 'sofia'); ?></label>
              <?php ItemForm::address_text(osc_user()); ?>
            </div>
          </div>
          <!-- seller info -->
          <?php if(!osc_is_web_user_logged_in() ) { ?>
          <div class="box seller_info">
            <h2><?php _e("Seller's information", 'elena'); ?></h2>
            <div class="row">
              <label for="contactName"><?php _e('Name', 'elena'); ?></label>
              <?php ItemForm::contact_name_text() ; ?>
            </div>            
            <div class="row">
              <label for="phone"><?php _e('Mobil', 'elena'); ?></label>
              <?php ItemForm::city_area_text(osc_user()) ; ?>
            </div>
            <div class="row">
              <label for="contactEmail"><?php _e('E-mail', 'elena'); ?> *</label>
              <?php ItemForm::contact_email_text() ; ?>
            </div>
            <div class="row">
              <div id="novy_email_check">
                <?php ItemForm::show_email_checkbox() ; ?>
              </div>
              <label for="showEmail" id="novy_email_show"><?php _e('Show email on listing page', 'elena'); ?></label>
            </div>
          </div>
          <?php }; ?>
          <?php ItemForm::plugin_post_item(); ?>
          <hr>
          <br /><br />

          <div style="float:left;clear:both;width:100%;margin:5px 0 10px 0;">
            <?php osc_run_hook("anr_captcha_form_field"); ?>
          </div>


        <div class="clear"></div>
        <button  type="submit"><?php _e('Publish', 'elena'); ?></button>
        </fieldset>
      </form>
    </div>
    <?php osc_current_web_theme_path('footer.php') ; ?>

    <script type="text/javascript">
      tabberAutomatic();
    </script>
    <script type="text/javascript">
    document.getElementById("PriceSelect").onchange = function(){ 
      if (document.getElementById("PriceSelect").value == 0) {
        document.getElementById("price").readOnly=false;
        document.getElementById("price").style.backgroundColor="#fff";
        document.getElementById("price").style.color="#000";
      } else if (document.getElementById("PriceSelect").value == 1) {
        document.getElementById("price").value = 0;
        document.getElementById("price").readOnly=true;
        document.getElementById("price").style.backgroundColor="#ddd";
        document.getElementById("price").style.color="#ddd";
      } else if (document.getElementById("PriceSelect").value == 2) {
        document.getElementById("price").value = "";
        document.getElementById("price").readOnly=true;
        document.getElementById("price").style.backgroundColor="#ddd";
        document.getElementById("price").style.color="#ddd";
      }
    }
    </script>

    <script type="text/javascript"> 
    $(document).ready(function(){ 
      $('#cat-wrap').hide();
      $('#alt_category').click(function(){
        $('#cat-wrap').fadeIn(300);
        $('body').addClass('noscroll');
      }); 
      $('.close-wrap, .single-subcat').click(function(){
        $('#cat-wrap').fadeOut(300);
        $('body').removeClass('noscroll');
        $('#catId').click();
      });

      $("#catId").click(function(){
        var cat_id = $(this).val();
        var url = '<?php echo osc_base_url(); ?>index.php';
        var result = '';

        if(cat_id != '') {
        if(catPriceEnabled[cat_id] == 1) {
          $("#price").closest("div").show();
        } else {
          $("#price").closest("div").hide();
          $('#price').val('') ;
        }

        $.ajax({
          type: "POST",
          url: url,
          data: 'page=ajax&action=runhook&hook=item_form&catId=' + cat_id,
          dataType: 'html',
          success: function(data){
          $("#plugin-hook").html(data);
        }
        });
      }
      });
    });
    </script>

    <script>
      $('img[src$="<?php echo osc_base_url(); ?>oc-content/uploads/temp/"]').parent().parent('.qq-upload-success').remove();
      $(document).ready(function() {
        if(!$('li.qq-upload-success').length) {
          $('.drag_drop #photos h3').hide();
        } else {
          $('.drag_drop #photos h3').show();
        }
      });
    </script>

    <!-- JAVASCRIPT AJAX LOADER FOR COUNTRY/REGION/CITY SELECT BOX -->
    <script>
      $(document).ready(function(){
        <?php if($def_cat['fk_i_category_id'] <> 0 and $def_cat['fk_i_category_id'] <> '' && osc_get_preference('drop_cat', 'elena_theme') == 1) { ?>
          draw_select(osc.item_post.category_tree_id.length + 1, $("#catId").val());
        <?php } ?>

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

                  $("#region").before('<div class="selector" id="uniform-regionId"><span><?php _e('Select a region', 'elena'); ?></span><select name="regionId" id="regionId" ></select></div>');
                  $("#region").remove();

                  $("#city").before('<div class="selector" id="uniform-cityId"><span><?php _e('Select a city', 'elena'); ?></span><select name="cityId" id="cityId" ></select></div>');
                  $("#city").remove();
                  
                  $("#regionId").val("");
                  $("#uniform-regionId").find('span').text('<?php _e('Select a region', 'elena'); ?>');
                } else {

                  $("#regionId").parent().before('<input placeholder="<?php _e('Enter a region', 'elena'); ?>" type="text" name="sRegion" id="region" />');
                  $("#regionId").parent().remove();
                  
                  $("#cityId").parent().before('<input placeholder="<?php _e('Enter a city', 'elena'); ?>" type="text" name="sCity" id="city" />');
                  $("#cityId").parent().remove();

                  $("#city").val('');
                }

                $("#regionId").html(result);
                $("#cityId").html('<option selected value=""><?php _e('Select a city', 'elena'); ?></option>');
                $("#uniform-cityId").find('span').text('<?php _e('Select a city', 'elena'); ?>');
                $("#cityId").attr('disabled',true);
                $("#uniform-cityId").addClass('disabled');
              }
             });

           } else {

             // add empty select
             $("#region").before('<div class="selector" id="uniform-regionId"><span><?php _e('Select a region', 'elena'); ?></span><select name="regionId" id="regionId" ><option value=""><?php _e('Select a region', 'elena'); ?></option></select></div>');
             $("#region").remove();
             
             $("#city").before('<div class="selector" id="uniform-cityId"><span><?php _e('Select a city', 'elena'); ?></span><select name="cityId" id="cityId" ><option value=""><?php _e('Select a city', 'elena'); ?></option></select></div>');
             $("#city").remove();

             if( $("#regionId").length > 0 ){
               $("#regionId").html('<option value=""><?php _e('Select a region', 'elena'); ?></option>');
             } else {
               $("#region").before('<div class="selector" id="uniform-regionId"><span><?php _e('Select a region', 'elena'); ?></span><select name="regionId" id="regionId" ><option value=""><?php _e('Select a region', 'elena'); ?></option></select></div>');
               $("#region").remove();
             }

             if( $("#cityId").length > 0 ){
               $("#cityId").html('<option value=""><?php _e('Select a city', 'elena'); ?></option>');
             } else {
               $("#city").parent().before('<div class="selector" id="uniform-cityId"><span><?php _e('Select a city', 'elena'); ?></span><select name="cityId" id="cityId" ><option value=""><?php _e('Select a city', 'elena'); ?></option></select></div>');
               $("#city").parent().remove();
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

                  $("#city").before('<div class="selector" id="uniform-cityId"><span><?php _e('Select a city', 'elena'); ?></span><select name="cityId" id="cityId" ></select></div>');
                  $("#city").remove();

                  $("#cityId").val("");
                  $("#uniform-cityId").find('span').text('<?php _e('Select a city', 'elena'); ?>');
                } else {
                  result += '<option value=""><?php _e('No cities found', 'elena'); ?></option>';
                  $("#cityId").parent().before('<input type="text" placeholder="<?php _e('Enter a city', 'elena'); ?>" name="sCity" id="city" />');
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
          $("#city").attr('disabled',true);
          $("#uniform-cityId").addClass('disabled');
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


        //Make sure selects are not empty on load
        if($('#uniform-regionId span').text() == '') {
          $('#uniform-regionId span').text('<?php _e('Select region', 'elena'); ?>')
        }

        if($('#uniform-cityId span').text() == '') {
          $('#uniform-cityId span').text('<?php _e('Select city', 'elena'); ?>')
        }

        if($('#region').val() == '') {
          $('#region').attr('placeholder', '<?php _e('Select region', 'elena'); ?>')
        }

        if($('#city').val() == '') {
          $('#city').attr('placeholder', '<?php _e('Select city', 'elena'); ?>')
        }
 
      });
    </script>
  </body>
</html>	