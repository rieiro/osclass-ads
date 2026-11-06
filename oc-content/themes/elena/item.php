<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <script type="text/javascript" src="<?php echo osc_assets_url('js/jquery3/jquery.fancybox.min.js') ; ?>"></script>
  <link href="<?php echo osc_assets_url('css/jquery.fancybox.min.css') ; ?>" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js') ; ?>"></script>
  <!--[if IE]>
    <style>#side_cena {background: url('<?php echo osc_base_url(); ?>oc-content/themes/elena/images/item-price-fix.png') no-repeat left center;}</style>
  <![endif]-->
</head>

<?php if(osc_count_item_resources() > 1) {?>
  <script>
    $(document).ready(function(){
      $("#sem_zobrazit_href").on('click', function(e) {
        e.preventDefault();
        $(".item #photos .photo-ul a").first().click();
      });

      $(".big-box a").on('click', function(e) {
        e.preventDefault();
        $(".item #photos .photo-ul a").eq($(this).attr('rel')).click();
      });
    });
  </script>
<?php } ?>

<body>   
  <?php osc_current_web_theme_path('header.php') ; ?>
  <?php if( osc_item_is_expired () ) { ?><div id="exp_mes"><?php _e('This listing is expired!', 'elena'); ?></div><?php } ?>

  <div class="content item">

    <?php if(osc_is_web_user_logged_in() && osc_item_user_id() == osc_logged_user_id()) { ?>
      <div id="s-tools">
        <div class="lead"><i class="fa fa-wrench"></i> <?php _e('Seller\'s tools', 'patricia'); ?></div>
        <div class="text"><?php _e('You are seller of this item and therefore you can edit or delete it.', 'patricia'); ?></div>
        <a href="<?php echo osc_item_edit_url(); ?>"><i class="fa fa-arrow-right"></i><?php _e('Edit listing', 'patricia'); ?></a>
        <a href="<?php echo osc_item_delete_url(); ?>" onclick="return confirm('<?php _e('Are you sure you want to delete this listing? This action cannot be undone.', 'patricia'); ?>?')"><i class="fa fa-arrow-right"></i><?php _e('Delete listing', 'patricia'); ?></a>
      </div>
    <?php } ?>

    <div id="item_head">
      <div class="inner">
        <h1><strong><?php echo ucfirst(osc_item_title()); ?></strong></h1>
        <?php if (function_exists('post_qrcode_url')) { ?>
          <a id="top-ico-qr" href="<?php post_qrcode_url();?>" rel="image_group"><img src="<?php echo osc_base_url();?>oc-content/themes/elena/images/qr-icon.gif" alt="QR code" /></a>
        <?php } ?>

        <?php if (function_exists('show_printpdf')) {?>
          <a id="top-ico-pdf" href="<?php echo osc_base_url(); ?>oc-content/plugins/printpdf/download.php?item=<?php echo osc_item_id(); ?>"><img id="inzerat_ico" src="<?php echo osc_base_url();?>oc-content/themes/elena/images/pdf-icon.gif"></a>
        <?php } ?>

        <?php if (function_exists('print_ad')) { ?>
          <div id="usporna_tlac"><?php print_ad(); ?></div>
        <?php } ?>

        <p id="report">
          <strong><?php _e('Mark as', 'elena') ; ?></strong>
          <span>
            <a id="item_spam" href="<?php echo osc_item_link_spam() ; ?>" rel="nofollow"><?php _e('spam', 'elena') ; ?></a>
            <a id="item_bad_category" href="<?php echo osc_item_link_bad_category() ; ?>" rel="nofollow"><?php _e('misclassified', 'elena') ; ?></a>
            <a id="item_repeated" href="<?php echo osc_item_link_repeated() ; ?>" rel="nofollow"><?php _e('duplicated', 'elena') ; ?></a>
            <a id="item_expired" href="<?php echo osc_item_link_expired() ; ?>" rel="nofollow"><?php _e('expired', 'elena') ; ?></a>
            <a id="item_offensive" href="<?php echo osc_item_link_offensive() ; ?>" rel="nofollow"><?php _e('offensive', 'elena') ; ?></a>
          </span>
        </p>
      </div>
    </div>
      
    <div id="main">
      <div id="type_dates">
        <span id="dates_mesto"><?php echo osc_item_region(); if(osc_item_city()!='') {echo ' - ' . osc_item_city(); } ?></span><span id="dates_od">|</span>
        <span id="dates_ost"><?php if ( osc_item_pub_date() != '' ) echo __('Published date', 'elena') . ': ' . osc_item_pub_date(); ?>, <?php _e('Listing', 'elena');?>: <?php echo osc_item_id() ; ?></span>
      </div>
      
      <div id="item_inner">
        <?php if( osc_images_enabled_at_items() && osc_count_item_resources() > 0 ) { ?>          
          <div id="photos">
            <?php for ( $i = 0; osc_has_item_resources(); $i++ ) { ?>
              <?php if( $i == 0 ) { ?>
                <a class="round3" id="sem_zobrazit_href" href="<?php echo osc_resource_url(); ?>" <?php if(osc_count_item_resources() == 1) {?>rel="image_group"<?php } ?> title="<?php _e('Photo', 'elena'); ?> <?php echo $i+1;?> <?php _e('of', 'elena'); ?> <?php echo osc_count_item_resources();?>">
                  <span class="enlarge"></span>
                  <img id="sem_zobrazit" class="round3" src="<?php echo osc_resource_url(); ?>" alt="<?php echo osc_item_title(); ?>"/>
                </a>
                
                <?php if( osc_count_item_resources() > 1 ) { ?>
                  <ul class="photo-ul">
                    <li >
                      <a class="round3 thumb<?php echo $i+1;?>" href="<?php echo osc_resource_url(); ?>" rel="image_group" title="<?php _e('Photo', 'elena'); ?> <?php echo $i+1;?> <?php _e('of', 'elena'); ?> <?php echo osc_count_item_resources();?>">
                        <img class="round3" src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_item_title(); ?>" onmouseover="return zobrazit_obrazok('<?php echo osc_resource_url();?>', '<?php echo osc_resource_url();?>');" />
                      </a>
                    </li>
                  <?php } ?>
                <?php } else { ?>
                  <li>
                    <a class="round3" href="<?php echo osc_resource_url(); ?>" rel="image_group" title="<?php _e('Photo', 'elena'); ?> <?php echo $i+1;?> <?php echo ' ' . __('of', 'elena') . ' '; ?> <?php echo osc_count_item_resources();?>">
                      <img class="round3" src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_item_title(); ?>" onmouseover="return zobrazit_obrazok('<?php echo osc_resource_url();?>', '<?php echo osc_resource_url();?>');" />
                    </a>
                  </li>
                <?php } ?>            
              <?php } ?>
            
              <?php if( osc_count_item_resources() > 1 ) { ?>
                </ul>
              <?php } ?>

            <?php osc_reset_resources(); ?>
            <?php if( osc_images_enabled_at_items() ) { ?>          
              <div class="big-box">
              <?php for ( $i = 0; osc_has_item_resources() ; $i++ ) { ?>
                <a class="round3" rel="<?php echo $i;?>" href="<?php echo osc_resource_url(); ?>" title="<?php _e('Photo', 'elena'); ?> <?php echo $i+1;?> <?php _e('of', 'elena'); ?> <?php echo osc_count_item_resources();?>">
                  <img class="round3" src="<?php echo osc_resource_url(); ?>" alt="<?php echo osc_item_title(); ?>"/>
                </a>
              <?php } ?>
              </div>
            <?php } ?>

            <div class="expand-gal round3"><div><?php _e('Expand gallery', 'elena'); ?></div><span class="left"></span><span class="left last"></span><span></span><span class="last"></span></div>
          </div>
        <?php } ?>
       
        <div id="description">
          <p class="item_desc"><?php echo osc_item_description(); ?></p>
          
          <div id="custom_fields">
            <?php if( osc_count_item_meta() >= 1 ) { ?>
              <div class="meta_list"><h2 class="no"><?php _e('Details', 'elena'); ?></h2>
                <?php while ( osc_has_item_meta() ) { ?>
                  <?php if(osc_item_meta_value()!='') { ?>
                    <div class="meta">
                      <span><?php echo osc_item_meta_name(); ?>:</span> <?php echo osc_item_meta_value(); ?>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
            <?php } ?>
          </div>
          <?php osc_run_hook('item_detail', osc_item() ) ; ?>          
        </div>
      </div>

      <div id="rel_ads">
       <?php if (function_exists('related_ads_start')) {related_ads_start();} ?>
      </div>
      
      <?php if( osc_comments_enabled() ) { ?>
        <?php if( osc_reg_user_post_comments () && osc_is_web_user_logged_in() || !osc_reg_user_post_comments() ) { ?>
          <div id="comments">
            <h2><?php _e('Comments', 'elena'); ?><span>(<?php echo osc_item_total_comments();?>)</span></h2>
            <ul id="comment_error_list"></ul>
            <?php CommentForm::js_validation(); ?>
            
            <?php if( osc_count_item_comments() >= 1 ) { ?>
              <div class="comments_list">
                <?php while ( osc_has_item_comments() ) { ?>
                  <div class="simple-comment">
                    <div class="comment-image">
                      <?php if(function_exists('profile_picture_show')) { profile_picture_show( 50, 'comment' ); } ?>
                    </div>
                    <div class="comment">
                      <h3><strong><?php echo osc_comment_title() ; ?></strong> <em><?php _e("by", 'elena') ; ?> <?php echo osc_comment_author_name() ; ?>:</em></h3>
                      <p><?php echo osc_comment_body() ; ?> </p>
                      <?php if ( osc_comment_user_id() && (osc_comment_user_id() == osc_logged_user_id()) ) { ?>
                        <p>
                          <a rel="nofollow" href="<?php echo osc_delete_comment_url(); ?>" title="<?php _e('Delete your comment', 'elena'); ?>"><?php _e('Delete', 'elena'); ?></a>
                        </p>
                      <?php } ?>
                    </div>
                  </div>
                <?php } ?>
                <div class="pagination">
                  <?php echo osc_comments_pagination(); ?>
                </div>
              </div>
            <?php } ?>

            <div id="i-block">
              <div id="comments">
                <div id="comment_form_wrap">
                  <form action="<?php echo osc_base_url(true) ; ?>" method="post" name="comment_form" id="comment_form">
                    <fieldset>
                      <h3><span><?php _e('Add new comment', 'elena') ; ?></span></h3>
                      <div class="comment-desc"><?php _e('Post any questions you have or your experience with this item or seller. All comments are moderated', 'elena') ; ?></div>
                      <div class="clear"></div>
                      
                      <input type="hidden" name="action" value="add_comment" />
                      <input type="hidden" name="page" value="item" />
                      <input type="hidden" name="id" value="<?php echo osc_item_id() ; ?>" />
                      <?php if(osc_is_web_user_logged_in()) { ?>
                        <input type="hidden" name="authorName" value="<?php echo osc_esc_html( osc_logged_user_name() ); ?>" />
                        <input type="hidden" name="authorEmail" value="<?php echo osc_logged_user_email();?>" />
                      <?php } else { ?>
                        <div class="third">
                          <label for="authorName"><?php _e('Name', 'elena') ; ?></label> 
                          <?php CommentForm::author_input_text(); ?>
                          <div class="small-info"><?php _e('Real name or Username', 'elena'); ?></div>
                        </div>
                        <div class="third">
                          <label for="authorEmail"><span><?php _e('E-mail', 'elena') ; ?></span><span class="req">*</span></label> 
                          <?php CommentForm::email_input_text(); ?>
                          <div class="small-info"><?php _e('Will not be published', 'elena'); ?></div>
                        </div>          
                      <?php } ?>
                      
                      <div class="third" id="tit">
                        <label for="title"><?php _e('Title', 'elena') ; ?></label>
                        <?php CommentForm::title_input_text(); ?>
                        <div class="small-info"><?php _e('Review, feedback or question', 'elena'); ?></div>
                      </div>
                    
                      <?php CommentForm::body_input_textarea(); ?>
                      <div class="req-what"><div class="req">*</div><div class="small-info"><?php _e('This field is required', 'elena'); ?></div></div>

                      <div style="float:left;clear:both;width:100%;margin:15px 0 5px 0;">
                        <?php osc_run_hook("anr_captcha_form_field"); ?>
                      </div>

                      <button type="submit" id="blue"><?php _e('Send comment', 'elena') ; ?></button>
                    </fieldset>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php } ?>
      <?php } ?>
    </div>        

    <div id="sidebar" class="floating">
      <div class="scroller" style="display:block">
        <?php if( osc_price_enabled_at_items() ) { ?>
          <div id="side_cena">
            <?php echo trim(str_replace(osc_item_currency_symbol(),"",osc_item_formated_price())); ?>
            <?php if (osc_item_currency_symbol()<>'') { ?>
              <div id="side_currency"><?php echo osc_item_currency_symbol(); ?></div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>

      <div class="clear"></div>
      <div id="side_name">
        <div class="clear"></div>
        <div id="side_prof_img">
          <?php if(function_exists('profile_picture_show')) { profile_picture_show( 65, 'item' ); } ?>
        </div>
        
        <div id="side_prof">
        <div id="side_user_name">
          <?php
            $user = User::newInstance()->findByPrimaryKey(osc_item_user_id());
            if(osc_rewrite_enabled()) {
              $url = osc_base_url().osc_get_preference('rewrite_user_profile')."/".@$user['s_username'];
            } else {
              $url = sprintf(osc_base_url(true) . '?page=user&action=pub_profile&id=%d', osc_item_user_id());
            }
          ?>
          <?php if(@$user['s_username'] <> '') { ?>
            <a href="<?php echo $url; ?>" title="<?php echo __('Show profile of', 'elena') . ' ' . osc_item_contact_name(); ?>"><?php echo osc_item_contact_name(); ?></a> &#8594;
          <?php } else { ?>
            <?php echo osc_item_contact_name(); ?>
          <?php } ?>
        </div>
        <div id="side_reg">
          <?php if(function_exists('seller_post')) { ?>
            <?php if ( osc_item_user_id() != 0 ) { ?>
              <?php seller_post(); ?>&nbsp;(<?php $user = User::newInstance()->findByPrimaryKey(osc_item_user_id());$num_items_user = (@$user['i_items'] > 0 ? @$user['i_items'] : 0);echo $num_items_user;?>)
            <?php } else { ?>
              <?php _e('Unregistered user', 'elena'); ?>
            <?php } ?> 
          <?php } else { ?>
            <?php _e('Unregistered user', 'elena'); ?>
          <?php } ?>
        </div>
      </div>
    </div>

    <div class="scroller2">
      <div id="contact">
        <div id="side_nadpis"><?php _e('Contact seller', 'elena'); ?> <?php if(osc_item_show_email()) {?><span class="show-email-i">(<?php echo osc_item_contact_email();?>)</span><?php } ?></div>
        <div id="side_mobil"><?php _e('Phone', 'elena'); ?>
          <div class="cislo">
            <?php 
              if(osc_item_user_id() <> 0) {
                $item_user = User::newInstance()->findByPrimaryKey(osc_item_user_id());
              }
            ?>

            <?php 
              $mobile = '';
              if($mobile == '') { $mobile = osc_item_city_area(); }      
              if($mobile == '' && osc_item_user_id() <> 0) { $mobile = @$item_user['s_phone_mobile']; }      
              if($mobile == '' && osc_item_user_id() <> 0) { $mobile = @$item_user['s_phone_land']; }      
              if($mobile == '') { $mobile = __('No phone number', 'elena'); }    
              echo $mobile;  
            ?> 
          </div>
        </div>
        <div id="side_loc">
          <div class="city"><div class="left"><?php _e('City', 'elena'); ?>:</div> <div class="right"><?php echo osc_item_city();?></div></div>
          <div class="region"><div class="left"><?php _e('Region', 'elena'); ?>:</div> <div class="right"><?php echo osc_item_region();?></div></div>
        </div>
        
        <div id="side_white"></div>
        <div id="side_nadpis_butt" class="side_klik"><?php _e("Send message", 'elena'); ?></div>
        
        <div id="side_sprava">
          <div id="kontakt_inner" class="box2">
            <?php if( osc_item_is_expired () ) { ?>
              <p>
                <?php _e("This listing is expired, you cannot contact seller.", 'elena') ; ?>
              </p>
            <?php } else if( ( osc_logged_user_id() == osc_item_user_id() ) && osc_logged_user_id() != 0 ) { ?>
              <p>
                <?php _e("It's your own listing, you can't contact the publisher.", 'elena') ; ?>
              </p>
            <?php } else if( osc_reg_user_can_contact() && !osc_is_web_user_logged_in() ) { ?>
              <p>
                <?php _e("You must log in or register a new account in order to contact the advertiser", 'elena') ; ?>
              </p>
              <p class="contact_button">
                <strong><a href="<?php echo osc_user_login_url() ; ?>"><?php _e('Login', 'elena') ; ?></a></strong>
                <strong><a href="<?php echo osc_register_account_url() ; ?>"><?php _e('Register for a free account', 'elena'); ?></a></strong>
              </p>
            <?php } else { ?> 
              <ul id="error_list"></ul>
              <?php ContactForm::js_validation(); ?>
              <form action="<?php echo osc_base_url(true) ; ?>" method="post" name="contact_form" id="contact_form">
                <?php osc_prepare_user_info() ; ?>
                <fieldset>
                  <div id="inzerat_meno">
                    <label><?php _e('Your name', 'elena') ; ?>:</label>
                    <?php ContactForm::your_name(); ?>
                  </div>
                  
                  <div id="inzerat_email">
                    <label><?php _e('Your e-mail address', 'elena') ; ?>:</label>
                    <?php ContactForm::your_email(); ?>
                  </div>

                  <div class="clear"></div>
                  <label><?php _e('Message', 'elena') ; ?>:</label>
                  <?php ContactForm::your_message(); ?>

                  <?php osc_run_hook('item_contact_form', osc_item_id()); ?>

                  <input type="hidden" name="action" value="contact_post" />
                  <input type="hidden" name="page" value="item" />
                  <input type="hidden" name="id" value="<?php echo osc_item_id() ; ?>" />

                  <!-- ReCaptcha -->
                  <?php if( osc_recaptcha_public_key() ) { ?>
                    <script type="text/javascript">
                      var RecaptchaOptions = {
                        theme : 'custom',
                        custom_theme_widget: 'recaptcha_widget'
                      };
                    </script>

                    <div id="recaptcha_widget">
                      <div id="recaptcha_image"><img /></div>
                      <span class="recaptcha_only_if_image"><?php _e('Enter the words above','elena'); ?>:</span>
                      <input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />
                      <div><a href="javascript:Recaptcha.showhelp()"><?php _e('Help', 'tatiana'); ?></a></div>
                    </div>
                    <?php osc_show_recaptcha(); ?>
                    <script>$(document).ready(function(){ $('#recaptcha_image').css({'width':'100%','height':'auto'}); });</script>
                  <?php } ?>

                  <button type="submit"><?php _e('Send', 'elena') ; ?></button>
                </fieldset>
              </form>
            <?php } ?>
          </div>
      
          <div id="side_mapa"><?php osc_run_hook('location') ; ?></div>
        </div>        
      </div>
    
      <div id="mapa_nastroje">   
        <ul id="item_tools">
          <li><img id="inzerat_ico" src="<?php echo osc_base_url() .'oc-content/themes/elena/images/mail_icon.gif'; ?>"><a id="inzerat_icohref" href="<?php echo osc_item_send_friend_url() ; ?>" rel="nofollow"><?php _e('Send to a friend', 'elena'); ?></a></li>
          <?php if (function_exists('show_printpdf')) {?><li><img id="inzerat_ico" src="<?php echo osc_base_url();?>oc-content/themes/elena/images/pdf_icon.gif"><?php show_printpdf(); ?></li><?php } ?>
          <?php if ( osc_item_user_id() != 0 and function_exists('seller_post') ) { ?><li><img id="inzerat_ico" src="<?php echo osc_base_url() .'oc-content/themes/elena/images/list_icon.png'; ?>"><?php seller_post(); ?> <span class="co">(<?php $user = User::newInstance()->findByPrimaryKey(osc_item_user_id());$num_items_user = (@$user['i_items'] > 0 ? @$user['i_items'] : 0);echo $num_items_user;?>)</span></li><?php } ?>
          <li><img id="inzerat_ico" src="<?php echo osc_base_url() .'oc-content/themes/elena/images/facebook-icon.png'; ?>"><script>function fbs_click() {u=location.href;t=document.title;window.open('http://www.facebook.com/sharer.php?u='+encodeURIComponent(u)+'&t='+encodeURIComponent(t),'sharer','toolbar=0,status=0,width=626,height=436');return false;}</script><a id="inzerat_icohref" href="http://www.facebook.com/share.php?u=<url>" onclick="return fbs_click()" target="_blank"><?php _e('Share on facebook', 'elena'); ?></a></li>
          <?php if (function_exists('post_qrcode_url')) { ?><li><img id="inzerat_ico" src="<?php echo osc_base_url() .'oc-content/themes/elena/images/qrcode-icon.png'; ?>"><a id="inzerat_icohref" href="<?php post_qrcode_url();?>" rel="image_group" title="QR code for listing: <?php echo osc_item_title(); ?>"><?php _e('Show QR code', 'elena'); ?></a></li><?php } ?>
        </ul>       
      </div>
    </div> 
  </div>


  <script type="text/javascript">
    $(document).ready(function() {
      $('.item #sidebar.floating').scrollToFixed({ limit: $($('#footer')).offset().top - $('.item #sidebar').height() - 40 });

      if($(this).width() <= 480) {
        $('.item #sidebar').removeClass('floating');
      }
    });

  </script>

  <script type="text/javascript">
    $(document).ready(function(){
      $("a[rel=image_group]").fancybox({
        openEffect : 'none',
        closeEffect : 'none',
        nextEffect : 'fade',
        prevEffect : 'fade',
        loop : false,
        helpers : {
          title : {
            type : 'inside'
          }
        }
      });
    });
  </script>
  
  <script type="text/javascript"> 
    $(document).ready(function(){ 
      $(".box1").hide();      
      $('#message, #yourName, #yourEmail').click(function(){
        $(".box1").slideDown();
      }); 
    }); 
  </script>
  
  <script type="text/javascript"> 
    $(document).ready(function(){ 
      $('.box2').hide();
      $('.side_klik').click(function(){
        $('.box2').slideToggle();
      });

      $('.expand-gal').click(function(){
        if($('span.over').length) {
          $('.photo-ul, #sem_zobrazit_href').delay(300).slideDown(200);
          $('.big-box').slideUp(300);
          $(this).find('span').removeClass('over');
          $(this).find('div').html("<?php _e('Expand gallery', 'elena'); ?>");
        } else {
          $('.photo-ul, #sem_zobrazit_href').slideUp(200);
          $('.big-box').delay(400).slideDown(300);
          $(this).find('span').addClass('over');
          $(this).find('div').html("<?php _e('Collapse gallery', 'elena'); ?>");
        }
      }); 
    }); 
  </script>
  
  <script type="text/javascript">
    function zobrazit_obrazok(obrazok,adresa){
      obr=document.getElementById('sem_zobrazit');
      obr.src=obrazok;
      obr=document.getElementById('sem_zobrazit_href');
      obr.href=adresa;
      return false;
    }
  </script>



    <!-- CHECK IF PRICE IN THIS CATEGORY IS ENABLED -->
    <script>
    $(document).ready(function(){
      var cat_id = <?php echo osc_item_category_id(); ?>;
      var catPriceEnabled = new Array();

      <?php
        $categories = Category::newInstance()->listAll( false );
        foreach( $categories as $c ) {
          if( $c['b_price_enabled'] != 1 ) {
            echo 'catPriceEnabled[ '.$c['pk_i_id'].' ] = '.$c[ 'b_price_enabled' ].';';
          }
        }
      ?>

      if(catPriceEnabled[cat_id] == 0) {
        $("#side_cena").hide(0);
      }
    });
    </script>
  
  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>		