<?php

//Save images
if(Params::getParam('action_specific')=='images') { 
  $upload_dir_small = osc_themes_path() . 'elena/images/small_cat/';
  $upload_dir_large = osc_themes_path() . 'elena/images/large_cat/';

  if (!file_exists($upload_dir_small)) { mkdir($upload_dir_small, 0777, true); }
  if (!file_exists($upload_dir_large)) { mkdir($upload_dir_large, 0777, true); }

  $count_real = 0;
  for ($i=1; $i<=1000; $i++) {
    if(isset($_FILES['small' .$i]) and $_FILES['small' .$i]['name'] <> ''){

      $file_ext   = strtolower(end(explode('.', $_FILES['small' .$i]['name'])));
      $file_name  = $i . '.' . $file_ext;
      $file_tmp   = $_FILES['small' .$i]['tmp_name'];
      $file_type  = $_FILES['small' .$i]['type'];   
      $extensions = array("png");

      if(in_array($file_ext,$extensions )=== false) {
        $errors = __('extension not allowed, only allowed extension is .png!','elena');
      } 
				
      if(empty($errors)==true){
        move_uploaded_file($file_tmp, $upload_dir_small.$file_name);
        message_ok(__('Small image #','elena') . $i . __(' uploaded successfully.','elena'));
        $count_real++;
      } else {
        message_error(__('There was error when uploading small image #','elena') . $i . ': ' .$errors);
      }
    }
  }

  $count_real = 0;
  for ($i=1; $i<=1000; $i++) {
    if(isset($_FILES['large' .$i]) and $_FILES['large' .$i]['name'] <> ''){
      $file_ext   = strtolower(end(explode('.', $_FILES['large' .$i]['name'])));
      $file_name  = $i . '.' . $file_ext;
      $file_tmp   = $_FILES['large' .$i]['tmp_name'];
      $file_type  = $_FILES['large' .$i]['type'];   
      $extensions = array("png");

      if(in_array($file_ext,$extensions )=== false) {
        $errors = __('extension not allowed, only allowed extension is .png!','elena');
      }
				
      if(empty($errors)==true){
        move_uploaded_file($file_tmp, $upload_dir_large.$file_name);
        message_ok(__('Large image #','elena') . $i . __(' uploaded successfully.','elena'));
        $count_real++;
      } else {
        message_error(__('There was error when uploading large image #','elena') . $i . ': ' .$errors);
      }
    }
  }
}
?>

<h2 class="render-title <?php echo (osc_get_preference('footer_link', 'elena_theme') ? '' : 'separate-top'); ?>"><?php _e('Theme settings', 'elena'); ?></h2>
<form action="<?php echo osc_admin_render_theme_url('oc-content/themes/elena/admin/settings.php'); ?>" method="post">
  <input type="hidden" name="action_specific" value="settings" />
  <fieldset>
    <div class="form-horizontal">
      <div class="form-row">
        <div class="form-label"><?php _e('Search placeholder', 'elena'); ?></div>
        <div class="form-controls"><input type="text" class="xlarge" name="keyword_placeholder" value="<?php echo osc_esc_html( osc_get_preference('keyword_placeholder', 'elena_theme') ); ?>"></div>
      </div>

      <div class="form-row">
        <div class="form-label"><?php _e('Default logo', 'elena'); ?></div>
        <div class="form-controls">
          <div class="form-label-checkbox"><input type="checkbox" name="default_logo" value="1" <?php echo (osc_get_preference('default_logo', 'elena_theme') ? 'checked' : ''); ?> > <?php _e("Show default logo in case you didn't upload one previously", 'elena'); ?></div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-label"><?php _e('Footer description', 'elena'); ?></div>
        <div class="form-controls"><textarea style="width:600px;height:100px" name="footer_desc" value="<?php echo osc_esc_html( osc_get_preference('footer_desc', 'elena_theme') ); ?>"><?php echo osc_esc_html( osc_get_preference('footer_desc', 'elena_theme') ); ?></textarea></div>
      </div>

      <div class="form-row">
        <div class="form-label"><?php _e('Use Drag & Drop photo uploader', 'elena'); ?></div>
        <div class="form-controls">
          <div class="form-label-checkbox"><input type="checkbox" name="image_upload" value="1" <?php echo (osc_get_preference('image_upload', 'elena_theme') ? 'checked' : ''); ?> > <?php _e("Use new Drag & Drop image uploader instead old one", 'elena'); ?></div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-label"><?php _e('Map on homepage', 'elena'); ?></div>
        <div class="form-controls">
          <select name="active_map">
            <option value="ro-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'ro-map') { ?>selected="selected"<?php } ?> ><?php _e('Romania', 'elena'); ?>
            <option value="ao-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'ao-map') { ?>selected="selected"<?php } ?> ><?php _e('Angola', 'elena'); ?>
            <option value="be-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'be-map') { ?>selected="selected"<?php } ?> ><?php _e('Belgium', 'elena'); ?>
            <option value="br-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'br-map') { ?>selected="selected"<?php } ?> ><?php _e('Brazil', 'elena'); ?>
            <option value="ch-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'ch-map') { ?>selected="selected"<?php } ?> ><?php _e('Switzerland', 'elena'); ?>
            <option value="es-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'es-map') { ?>selected="selected"<?php } ?> ><?php _e('Spain', 'elena'); ?>
            <option value="fr-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'fr-map') { ?>selected="selected"<?php } ?> ><?php _e('France', 'elena'); ?>
            <option value="hu-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'hu-map') { ?>selected="selected"<?php } ?> ><?php _e('Hungary', 'elena'); ?>
            <option value="id-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'id-map') { ?>selected="selected"<?php } ?> ><?php _e('Indonesia', 'elena'); ?>
            <option value="jp-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'jp-map') { ?>selected="selected"<?php } ?> ><?php _e('Japan', 'elena'); ?>
            <option value="my-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'my-map') { ?>selected="selected"<?php } ?> ><?php _e('Malaysia', 'elena'); ?>
            <option value="ma-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'ma-map') { ?>selected="selected"<?php } ?> ><?php _e('Morocco', 'elena'); ?>
            <option value="pr-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'pr-map') { ?>selected="selected"<?php } ?> ><?php _e('Portugal', 'elena'); ?>
            <option value="pa-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'pa-map') { ?>selected="selected"<?php } ?> ><?php _e('Pakistan', 'elena'); ?>
            <option value="ua-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'ua-map') { ?>selected="selected"<?php } ?> ><?php _e('Ukraine', 'elena'); ?>
            <option value="de-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'de-map') { ?>selected="selected"<?php } ?> ><?php _e('Germany', 'elena'); ?>
            <option value="cl-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'cl-map') { ?>selected="selected"<?php } ?> ><?php _e('Chile', 'elena'); ?>
            <option value="it-map" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == 'it-map') { ?>selected="selected"<?php } ?> ><?php _e('Italy', 'elena'); ?>
            <option value="0" <?php if(osc_esc_html( osc_get_preference('active_map', 'elena_theme')) == '0') { ?>selected="selected"<?php } ?> ><?php _e('None', 'elena'); ?>
          </select>
        </div>
      </div>
      <div class="clear"></div>

      <div class="form-actions">
        <input type="submit" value="<?php _e('Save changes', 'elena'); ?>" class="btn btn-submit">
      </div>
    </div>
  </fieldset>
</form>

<br /><br />

<style>
#load_image, #load_image table {float:left;width:100%;clear:both;}
#load_image tr, #load_image td, #load_image th {float:left;}
#load_image tr {width:100%;clear:both;float:left;border-bottom: 1px dotted #eee;}
#load_image td {width:20%;background:#ffffff;float:left;height:29px;font-size:12px;}
#load_image td.id, #load_image th.id {width:10%;}
#load_image td.icon, #load_image th.icon {width:10%;}
#load_image td.id, #load_image td.name {padding-top:7px;padding-bottom:7px;}
#load_image td.name {overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
#load_image td.name, #load_image th.name {width:30%;}
#load_image th {padding:8px 0;width:20%;font-size:12px;}
#load_image th.id {padding-left:5px;}
#load_image .btn-submit {float:left;clear:both;margin-top:15px;} 
#load_image .sub0 {padding-left:0px;font-weight:bold}
#load_image .sub1 {padding-left:10px;}
#load_image .sub2 {padding-left:20px;}
#load_image .sub3 {padding-left:30px;}
#load_image .sub4 {padding-left:40px;}
#load_image .parent {margin-top:15px;}
#load_image .icon {padding:8px 0 7px 0;text-align:center;}
#load_image .add_img {padding:7px 0;float:left;}
#load_image * {box-sizing: border-box;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;}
</style>

<form name="promo_form" id="load_image" action="<?php echo osc_admin_render_theme_url('oc-content/themes/elena/admin/settings.php'); ?>" method="POST" enctype="multipart/form-data" >
  <input type="hidden" name="action_specific" value="images" />
  <fieldset>
    <div class="form-horizontal">
      <table>
        <tr style="background:#eee;font-weight:bold;text-align:left;">
          <th class="id">ID</th>
          <th class="name">Name</th>
          <th class="icon">Has small image</th>
          <th>Small image (20x20 px)</th>
          <th class="icon">Has large image</th>
          <th>Large image (80x66 px)</th>
        </tr>

        <?php osc_has_subcategories_special(Category::newInstance()->toTree(),  0); ?> 
      </table>

      <input type="submit" value="<?php _e('Save images', 'elena'); ?>" class="btn btn-submit">
    </div>
  </fieldset>
</form>

<?php
function osc_has_subcategories_special($categories, $deep = 0) {
  foreach($categories as $c) { 
    echo '<tr' . ($deep == 0 ? ' class="parent"' : '') . '>';
    echo '<td class="id">' . $c['pk_i_id'] . '</td>';
    echo '<td class="sub' . $deep . ' name">' . $c['s_name'] . '</td>';

    if (file_exists(osc_themes_path() . 'elena/images/small_cat/' . $c['pk_i_id'] . '.png')) { 
      echo '<td class="icon"><img src="' . osc_base_url() . 'oc-content/themes/elena/images/img_yes.png" alt="Has Image" /></td>';  
    } else {
      echo '<td class="icon"><img src="' . osc_base_url() . 'oc-content/themes/elena/images/img_no.png" alt="Has not Image" rel="' . $c['pk_i_id'] . '.png'. '" /></td>';  
    }

    echo '<td><a class="add_img" id="small' . $c['pk_i_id'] . '" href="#">' . __('Add small image', 'elena') . '</a>';

    if (file_exists(osc_themes_path() . 'elena/images/large_cat/' . $c['pk_i_id'] . '.png')) { 
      echo '<td class="icon"><img src="' . osc_base_url() . 'oc-content/themes/elena/images/img_yes.png" alt="Has Image" /></td>';  
    } else {
      echo '<td class="icon"><img src="' . osc_base_url() . 'oc-content/themes/elena/images/img_no.png" alt="Has not Image" /></td>';  
    }

    echo '<td><a class="add_img" id="large' . $c['pk_i_id'] . '" href="#">' . __('Add large image', 'elena') . '</a>';
    echo '</tr>';

    if(isset($c['categories']) && is_array($c['categories']) && !empty($c['categories'])) {
      osc_has_subcategories_special($c['categories'], $deep+1);
    }   
  }
}

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
?>

<script>
$('.add_img').click(function() {
  var id = $(this).attr('id');
  $(this).parent().html('<input type="file" name="' + id + '" />');
  return false;
});
</script>
