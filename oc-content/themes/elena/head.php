<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<title><?php echo meta_title(); ?></title>
<meta name="title" content="<?php echo osc_esc_html(meta_title()); ?>" />
<?php if( meta_description() != '' ) { ?><meta name="description" content="<?php echo osc_esc_html(meta_description()); ?>" /><?php } ?>
<?php if( function_exists('meta_keywords') ) { ?>
  <?php if( meta_keywords() != '' ) { ?>
    <meta name="keywords" content="<?php echo osc_esc_html(meta_keywords()); ?>" />
  <?php } ?>
<?php } ?>

<?php if( osc_get_canonical() != '' ) { ?>
  <link rel="canonical" href="<?php echo osc_get_canonical(); ?>"/>
<?php } ?>

<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="Mon, 01 Jul 1970 00:00:00 GMT" />
<meta name="robots" content="index, follow" />
<meta name="googlebot" content="index, follow" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php osc_get_item_resources(); ?>
<?php if(osc_is_ad_page()) { ?><meta property="og:image" content="<?php echo osc_resource_url(); ?>" /><?php } ?>

<script type="text/javascript">
  var fileDefaultText = '<?php echo osc_esc_js( __('No file selected', 'elena') ); ?>';
  var fileBtnText     = '<?php echo osc_esc_js( __('Choose File', 'elena') ); ?>';
</script>

<?php
  osc_enqueue_style('style', osc_current_web_theme_url('style.css?v=' . date('YmdHis')));
  osc_enqueue_style('tabs', osc_current_web_theme_url('tabs.css'));
  osc_enqueue_style('font-awesome', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
  osc_enqueue_style('responsive', osc_current_web_theme_url('responsive.css'));

  osc_register_script('global', osc_current_web_theme_js_url('global.js?v=' . date('YmdHis')));
  osc_register_script('elena', osc_current_web_theme_js_url('elena.js?v=' . date('YmdHis')));
  osc_register_script('date', osc_base_url() . 'oc-includes/osclass/assets/js/date.js');
  
  if (!defined('JQUERY_VERSION') || JQUERY_VERSION == '1') {
    osc_register_script('jquery-uniform', osc_current_web_theme_js_url('jquery.uniform.js'), 'jquery');
  }
  
  osc_enqueue_script('jquery');
  osc_enqueue_script('jquery-ui');
  osc_enqueue_script('jquery-uniform');
  osc_enqueue_script('tabber');
  osc_enqueue_script('global');
  osc_enqueue_script('elena');
  osc_enqueue_script('jquery-fineuploader');

  if(osc_is_search_page() or osc_is_ad_page() or osc_is_publish_page())  {
    osc_enqueue_script('date');
    osc_enqueue_style('priceSlider', '//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.min.css');
  }
?>

<?php
  if(osc_is_ad_page()) {
    osc_register_script('scroll-to-fixed', osc_current_web_theme_js_url('jquery-scrolltofixed-min.js'));
    osc_enqueue_script('scroll-to-fixed');
  }
?>

<?php osc_run_hook('header'); ?>