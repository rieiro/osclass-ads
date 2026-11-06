<?php

// IF CATEGORY IS 0, REPLACE WITH EMPTY
if(Params::getParam('sCategory') == 0) {
  if(method_exists('Params', 'unsetParam')) {
    Params::unsetParam('sCategory');
  } else {
    Params::setParam('sCategory', '');
  }
}

define('ELENA_THEME_VERSION', '3.3.0');

if(!function_exists('osc_search_country')) {
  function osc_search_country() {
    return View::newInstance()->_get('search_country');
  }
}

function elena_search_params() {
 return array(
   'sCategory' => Params::getParam('sCategory'),
   'sCountry' => Params::getParam('sCountry'),
   'sRegion' => Params::getParam('sRegion'),
   'sCity' => Params::getParam('sCity'),
   'sPriceMin' => Params::getParam('sPriceMin'),
   'sPriceMin' => Params::getParam('sPriceMax'),
   'sCompany' => Params::getParam('sCompany'),
   'sShowAs' => Params::getParam('sShowAs')
  );
}

// Radius search compatibility
if(!function_exists('radius_installed')) {
  function radius_installed() {
    return '';
  }
}

if(!function_exists('mb_item_post_url_in_category')) {
  function mb_item_post_url_in_category($catId) {
    if ( osc_rewrite_enabled() ) {
      $path = osc_base_url() . osc_get_preference('rewrite_item_new') . '/' . $catId;
    } else {
      $path = sprintf(osc_base_url(true) . '?page=item&action=item_add&catId=%d', $catId);
    }

    return $path;
  }
}

function elena_filter_user_type() {
  if(Params::getParam('sCompany') <> '' and Params::getParam('sCompany') <> null) {
    Search::newInstance()->addJoinTable( 'pk_i_id', DB_TABLE_PREFIX.'t_user', DB_TABLE_PREFIX.'t_item.fk_i_user_id = '.DB_TABLE_PREFIX.'t_user.pk_i_id', 'LEFT OUTER' ) ; // Mod

    if(Params::getParam('sCompany') == 1) {
      Search::newInstance()->addConditions(sprintf("%st_user.b_company = 1", DB_TABLE_PREFIX));
    } else {
      Search::newInstance()->addConditions(sprintf("coalesce(%st_user.b_company, 0) <> 1", DB_TABLE_PREFIX, DB_TABLE_PREFIX));
    }
  }
}

osc_add_hook('search_conditions', 'elena_filter_user_type');
  

if(!function_exists('message_ok')) {
  function message_ok( $text ) {
    $final  = '<div style="padding: 1%;width: 98%;margin-bottom: 15px;" class="flashmessage flashmessage-ok flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


if(!function_exists('message_error')) {
  function message_error( $text ) {
    $final  = '<div style="padding: 1%;width: 98%;margin-bottom: 15px;" class="flashmessage flashmessage-error flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


if(!function_exists('osc_count_countries')) {
  function osc_count_countries() {
    if ( !View::newInstance()->_exists('contries') ) {
      View::newInstance()->_exportVariableToView('countries', Search::newInstance()->listCountries( ">=", "country_name ASC" ) );
    }
    return View::newInstance()->_count('countries');
  }
}


if(modern_is_fineuploader() and osc_get_osclass_section() == 'item_add' or osc_get_osclass_section() == 'item_edit') {
  if(!OC_ADMIN) {
    osc_enqueue_style('fine-uploader-css', osc_assets_url('js/fineuploader/fineuploader.css'));
  }
  osc_enqueue_script('jquery-fineuploader');
}


function modern_is_fineuploader() {
  if(class_exists('Scripts')) {
    return Scripts::newInstance()->registered['jquery-fineuploader'] && method_exists('ItemForm', 'ajax_photos');
  }
}


function elena_cw_list($current_id) {
  elena_cw(Category::newInstance()->toTree(), $current_id);
}

function elena_cw($categories, $current_id) {
  $search_params = elena_search_params();

  foreach($categories as $c) {
    $search_params['sCategory'] = $c['pk_i_id'];

    if(!$current_id) {
      if(file_exists(osc_themes_path() . 'elena/images/large_cat/' . $c['pk_i_id'] . '.png')) {
        $subcat_img = osc_base_url() . 'oc-content/themes/elena/images/large_cat/' . $c['pk_i_id'] . '.png';
      } else {
        $subcat_img = osc_base_url() . 'oc-content/themes/elena/images/large_cat/default_cat.png';
      }

      echo '<a class="cw-el" href="' . osc_search_url($search_params) . '">';
      echo '<img src="' . $subcat_img . '" alt="' . $c['s_name'] . '" />';
      echo '<span class="cw-href cw-cat-span">' . $c['s_name'] . '</span>';
      echo '</a>';      
    }

    if(isset($c['categories']) && is_array($c['categories']) && $current_id) {
      elena_subcw($c['categories'], $current_id, $c['pk_i_id']);
    }
  }
}


function elena_subcw($categories, $current_id, $this_id) {
  foreach($categories as $c) {
    if($this_id == $current_id) {
      if(file_exists(osc_themes_path() . 'elena/images/large_cat/' . $c['pk_i_id'] . '.png')) {
        $subcat_img = osc_base_url() . 'oc-content/themes/elena/images/large_cat/' . $c['pk_i_id'] . '.png';
      } else {
        $subcat_img = osc_base_url() . 'oc-content/themes/elena/images/large_cat/default_cat.png';
      }

      echo '<a class="cw-el" href="' . osc_search_url(array('sCategory' => $c['pk_i_id'])) . '">';
      echo '<img src="' . $subcat_img . '" alt="' . $c['s_name'] . '" />';
      echo '<span class="cw-href cw-cat-span">' . $c['s_name'] . '</span>';
      echo '</a>';      
    }

    if(isset($c['categories']) && is_array($c['categories'])) {
      elena_subcw($c['categories'], $current_id, $c['pk_i_id']);
    }
  }
}

/*********************************************************************************************/

function elena_categories_select($name = 'sCategory', $category = null, $default_str = null) {
  if($default_str == null) { $default_str = __('Select a category', 'elena'); }
  elena_category_select(Category::newInstance()->toTree(), $category, $default_str, $name);
}


function elena_category_select($categories, $category, $default_item = null, $name = "sCategory") {
  echo '<ul name="' . $name . '" id="cat-list">';
  echo '<li class="all" value="">' . __('All categories', 'elena') . '</li>';

  foreach($categories as $c) {
    echo '<li class="bold" value="' . $c['pk_i_id'] . '"' . ( (@$category['pk_i_id'] == $c['pk_i_id']) ? 'selected="selected"' : '' ) . '>' . $c['s_name'] . '</li>';
    if(isset($c['categories']) && is_array($c['categories'])) {
      elena_subcategory_select($c['categories'], $category, $default_item, 1);
    }
  }
  echo '</ul>';
}


function elena_subcategory_select($categories, $category, $default_item = null, $deep = 0) {
  $deep_string = "";
  for($var = 0;$var<$deep;$var++) {
    $deep_string .= '&nbsp;&nbsp;';
  }
  $deep++;
  foreach($categories as $c) {
    echo '<li class="level' . $deep . '" value="' . $c['pk_i_id'] . '"' . ( (@$category['pk_i_id'] == $c['pk_i_id']) ? 'selected="selected"' : '' ) . '>' . $deep_string.$c['s_name'] . '</li>';
    if(isset($c['categories']) && is_array($c['categories'])) {
      elena_subcategory_select($c['categories'], $category, $default_item, $deep);
    }
  }
}


function elena_categories_tree($categories = null, $selected = null, $depth = 0) {
  if( ( $categories != null ) && is_array($categories) ) {
    echo '<ul id="cat' . $categories[0]['fk_i_parent_id'] . '">';

    $d_string = '';
    for($var_d = 0; $var_d < $depth; $var_d++) {
      $d_string .= "&nbsp;&nbsp;&nbsp;&nbsp;";
    }

    foreach($categories as $c) {
      echo '<li>';
      echo $d_string . '<input type="checkbox" name="categories[]" value="' . $c['pk_i_id'] . '" onclick="javascript:checkCat(\'' . $c['pk_i_id'] . '\', this.checked);" ' . ( in_array($c['pk_i_id'], $selected) ? 'checked="checked"' : '' ) . ' />' . ( ( $depth == 0 ) ? '<span>' : '' ) . $c['s_name'] . ( ( $depth == 0 ) ? '</span>' : '' );
      elena_categories_tree($c['categories'], $selected, $depth + 1);
      echo '</li>';
    }
    echo '</ul>';
  }
}


//Start Location Selector
function elena_location_selector() {
  //View::newInstance()->_exportVariableToView('list_regions', Search::newInstance()->listRegions('%%%%', '>=', 'region_name ASC') ) ;
  //View::newInstance()->_exportVariableToView('list_countries', Search::newInstance()->listCountries('%%%%', '>=', 'country_name ASC') ) ;
      
  $curr_country = '';
  $curr_reg = osc_search_region();
  $curr_city = osc_search_city();
  if($curr_country == '') { $curr_country = osc_item_country(); }
  if($curr_reg == '') { $curr_reg = osc_item_region(); }
  if($curr_city == '') { $curr_city = osc_item_city(); }
  if($curr_country == '') { $curr_country = @$_GET['sCountry']; }
  if($curr_country == '') { $curr_country = @$_GET['sCountry_radius']; }
  if($curr_country == '') { $curr_country = strtoupper(substr(osc_current_user_locale(), 3)); }
  if($curr_reg == '') { $curr_reg = @$_GET['sRegion']; }
  if($curr_reg == '') { $curr_reg = @$_GET['sRegion_radius']; }
  if($curr_city == '') { $curr_city = @$_GET['sCity']; }
  if($curr_city == '') { $curr_city = @$_GET['sCity_radius']; }

  if(osc_count_countries() > 1) {
    $del = '&nbsp;&nbsp;&nbsp;';
    $show_country = true;
  } else {
    $del = '';
    $show_country = false;
  }

  if(strlen($curr_country) > 2) { 
    $cc = Country::newInstance()->findByName($curr_country);
    $curr_country = $cc['pk_c_code'];
  }

  echo '<ul id="loc-list" name="Locator" data-placeholder="' . __('Select a location', 'elena') . '"  id="Locator">';
  echo '<li class="all" rel="">' . __('All locations', 'elena') . '</li>';

  if($show_country) {
    while(osc_has_list_countries()) {
      if($show_country) {
        echo '<li rel="' . osc_list_country_name() . '" ' . ( (osc_list_country_code() == $curr_country  and $curr_reg == '' and $curr_city == '') ? 'selected="selected"' : '' ) . '>' . osc_list_country_name() . '</li>';
      }

      if(osc_list_country_code() == $curr_country) { 
        while(osc_has_list_regions($curr_country) ) { 
          echo '<li rel="//' . osc_list_region_name() . '" ' . ( ((osc_list_region_name() == $curr_reg or osc_list_region_id() == $curr_reg) and $curr_city == '') ? 'selected="selected"' : '' ) . '>' . $del . osc_list_region_name() . '</li>';

          if(osc_list_region_name() == $curr_reg) { 
            $myreg_id = '';
            if( $curr_reg != '' ) {
              $v_reg_id  = Region::newInstance()->findByName($curr_reg);
              if(isset($v_reg_id['pk_i_id'])) {
              $myreg_id = $v_reg_id['pk_i_id'];
              }
            }

            while(osc_has_list_cities($myreg_id)) { 
              echo '<li class="city-level" rel="--' . osc_list_city_name() . '" ' . ( ((osc_list_city_name() == $curr_city or osc_list_city_id() == $curr_city) and $curr_city <> '') ? 'selected="selected"' : '' ) . '>&nbsp;&nbsp;&nbsp;' . $del . osc_list_city_name() . '</li>';
            }
          } 
        } // End region loop
      }
    } // End country loop 

  } else {

    while(osc_has_list_regions() ) { 
      echo '<li rel="//' . osc_list_region_name() . '" ' . ( ((osc_list_region_name() == $curr_reg or osc_list_region_id() == $curr_reg)  and $curr_city == '') ? 'selected="selected"' : '' ) . '>' . $del . osc_list_region_name() . '</li>';

      if(osc_list_region_name() == $curr_reg) { 
        $myreg_id = '';
        if( $curr_reg != '' ) {
          $v_reg_id  = Region::newInstance()->findByName($curr_reg);
          if(isset($v_reg_id['pk_i_id'])) {
            $myreg_id = $v_reg_id['pk_i_id'];
          }
        }
 
        while(osc_has_list_cities($myreg_id)) { 
          echo '<li class="city-level" rel="--' . osc_list_city_name() . '" ' . ( ((osc_list_city_name() == $curr_city or osc_list_city_id() == $curr_city) and $curr_city <> '') ? 'selected="selected"' : '' ) . '>&nbsp;&nbsp;&nbsp;' . $del . osc_list_city_name() . '</li>';
        }
      }
    }
  }

  echo '</ul>';
  View::newInstance()->_erase('cities');
  View::newInstance()->_erase('regions');
  View::newInstance()->_erase('countries');
}
// End Location selector


if( !OC_ADMIN ) {
  if( !function_exists('add_close_button_action') ) {
    function add_close_button_action(){
      echo '<script type="text/javascript">';
        echo '$(".flashmessage .ico-close").click(function(){';
          echo '$(this).parent().hide();';
        echo '});';
      echo '</script>';
    }
    osc_add_hook('footer', 'add_close_button_action') ;
  }
}


function mb_subcat_list($categories) {
  $zindex = 200;
  echo '<div class="icon-close-div"></div>';
  foreach($categories as $c) {
    echo '<h3 style="z-index: ' . $zindex . '">';
      echo '<a href="#" class="single-subcat" id="' . $c['pk_i_id'] . '">' . $c['s_name'] . '</a>';

      if(isset($c['categories']) && is_array($c['categories']) && !empty($c['categories'])) {
        echo '<div class="icon-add-next"></div>';
        echo '<div class="sub" style="display:none">';
          mb_subcat_list($c['categories']);
        echo '</div>';
      }
    echo '</h3>';
    $zindex = $zindex - 1;
  }     
}


function osc_item_how_much() {
  if ( osc_item_field("b_premium") > 0 ) {
    return osc_item_field("b_premium");
  } else { 
    return false;
  }
}



function theme_elena_actions_admin() {
  if( Params::getParam('file') == 'oc-content/themes/elena/admin/settings.php' ) {
    if( Params::getParam('donation') == 'successful' ) {
      osc_set_preference('donation', '1', 'elena_theme');
      osc_reset_preferences();
    }
  }

  switch( Params::getParam('action_specific') ) {
    case('settings'):
      $footerLink  = Params::getParam('footer_link');
      $defaultLogo = Params::getParam('default_logo');
      $image_upload = Params::getParam('image_upload');

      osc_set_preference('active_map', Params::getParam('active_map'), 'elena_theme');
      osc_set_preference('keyword_placeholder', Params::getParam('keyword_placeholder'), 'elena_theme');
      osc_set_preference('footer_link', ($footerLink ? '1' : '0'), 'elena_theme');
      osc_set_preference('default_logo', ($defaultLogo ? '1' : '0'), 'elena_theme');
      osc_set_preference('footer_desc', Params::getParam('footer_desc'), 'elena_theme');
      osc_set_preference('image_upload', ($image_upload ? '1' : '0'), 'elena_theme');

      osc_add_flash_ok_message(__('Theme settings updated correctly', 'elena'), 'admin');
      header('Location: ' . osc_admin_render_theme_url('oc-content/themes/elena/admin/settings.php')); exit;
    break;
    case('upload_logo'):
      $package = Params::getFiles('logo');
      if( $package['error'] == UPLOAD_ERR_OK ) {
        if( move_uploaded_file($package['tmp_name'], WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" ) ) {
          osc_add_flash_ok_message(__('The logo image has been uploaded correctly', 'elena'), 'admin');
        } else {
          osc_add_flash_error_message(__("An error has occurred, please try again", 'elena'), 'admin');
        }
      } else {
        osc_add_flash_error_message(__("An error has occurred, please try again", 'elena'), 'admin');
      }
      header('Location: ' . osc_admin_render_theme_url('oc-content/themes/elena/admin/header.php')); exit;
    break;
    case('remove'):
      if(file_exists( WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" ) ) {
        @unlink( WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" );
        osc_add_flash_ok_message(__('The logo image has been removed', 'elena'), 'admin');
      } else {
        osc_add_flash_error_message(__("Image not found", 'elena'), 'admin');
      }
      header('Location: ' . osc_admin_render_theme_url('oc-content/themes/elena/admin/header.php')); exit;
    break;
  }
}

osc_add_hook('init_admin', 'theme_elena_actions_admin');
osc_admin_menu_appearance(__('Header logo', 'elena'), osc_admin_render_theme_url('oc-content/themes/elena/admin/header.php'), 'header_elena');
osc_admin_menu_appearance(__('Theme settings', 'elena'), osc_admin_render_theme_url('oc-content/themes/elena/admin/settings.php'), 'settings_elena');

if( !function_exists('logo_header') ) {
  function logo_header() {
    $html = '<img border="0" alt="' . osc_page_title() . '" src="' . osc_current_web_theme_url('images/logo.jpg') . '" />';
    if( file_exists( WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" ) ) {
      return $html;
    } else if( osc_get_preference('default_logo', 'elena_theme') && (file_exists( WebThemes::newInstance()->getCurrentThemePath() . "images/default-logo.jpg")) ) {
      return '<img border="0" alt="' . osc_page_title() . '" src="' . osc_current_web_theme_url('images/default-logo.jpg') . '" />';
    } else {
      return osc_page_title();
    }
  }
}

function chosen_region_select() {
  //View::newInstance()->_exportVariableToView('list_regions', Search::newInstance()->listRegions('%%%%', '>=', 'region_name ASC') ) ;
  $aLoc = Region::newInstance()->listAll();

  echo '<select name="sRegion" data-placeholder="' . __('Select a region...', 'elena') . '"  id="sRegion">' ;
  echo '<option value ="">' . __('Select region', 'elena') . '</option>' ;
  foreach( $aLoc as $loc ) {
     echo '<option value="' . $loc['s_name'] . '">' . $loc['s_name'] . '</option>' ;
  }
  echo '</select>' ;
}

// install update options
if( !function_exists('elena_theme_install') ) {
  function elena_theme_install() {
    osc_set_preference('active_map', __('ro-map', 'elena'), 'elena_theme');
    osc_set_preference('keyword_placeholder', __('ie. PHP Programmer', 'elena'), 'elena_theme');
    osc_set_preference('footer_desc', __('You can change this text via admin panel on theme settings.', 'elena'), 'elena_theme');
    osc_set_preference('version', ELENA_THEME_VERSION, 'elena_theme');
    osc_set_preference('footer_link', true, 'elena_theme');
    osc_set_preference('donation', '0', 'elena_theme');
    osc_set_preference('default_logo', '1', 'elena_theme');
    osc_set_preference('image_upload', '1', 'elena_theme');

    osc_reset_preferences();
  }
}

if(!function_exists('check_install_elena_theme')) {
  function check_install_elena_theme() {
    $current_version = osc_get_preference('version', 'elena_theme');
    //check if current version is installed or need an update<
    if( !$current_version ) {
      elena_theme_install();
    }
  }
}

check_install_elena_theme();


// USER MENU FIX
function elena_user_menu_fix() {
  $user = User::newInstance()->findByPrimaryKey( osc_logged_user_id() );
  View::newInstance()->_exportVariableToView('user', $user);
}

osc_add_hook('header', 'elena_user_menu_fix');
?>