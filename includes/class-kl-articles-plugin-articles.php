<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class KL_Articles_Plugin_Articles extends KL_Articles_Plugin{
  private static $_instance = null;

  public $text_domain = 'kl-articles-plugin';
  public $cpt	        = 'tw-article';
  public $cat_tax     = 'kl_article_category';
  public $tag_tax     = 'kl_article_tag';

  public $cpt_slug  = "article";
  public $cat_slug  = "article-cat";
  public $tag_slug  = "article-tag";

  public $has_search   = false;
  public $has_archive  = false;
  public $has_category = false;
  public $has_tag      = false;

  public $has_article_contact = false;
  public $has_article_social  = false;
  public $has_article_urls    = false;
  public $has_article_status  = false;
  public $has_article_videos  = false;

  public $has_testimonials = false;
  public $has_client       = false;
  public $has_teams        = false;
  public $has_documents    = false;

  public function __construct($file = '', $version = '1.0.0'){
    parent::__construct($file , $version );

    $this->init_options();
    $this->init_post_type();
    $this->init_taxonomies();
    $this->init_metaboxes();

    /*********** Admin Filter Profile Image ***********/
    add_filter('manage_'.$this->cpt.'_posts_columns',       array($this, 'manage_posts_columns'), 5);
    add_action('manage_'.$this->cpt.'_posts_custom_column', array($this, 'manage_posts_custom_column'), 10, 2);
    add_action('admin_head', array($this, 'admin_column_width') );

    /*********** Admin Filter by Category ***********/
    add_filter('parse_query', array($this, 'convert_id_to_term_in_query') );
    add_action('restrict_manage_posts', array($this, 'filter_post_type_by_taxonomy') );

    /*********** Pre Get Posts Filter ***********/
    add_action('pre_get_posts', array($this, 'pre_get_posts') );

    /*********** Template Filter ***********/
    add_filter( 'template_include', array($this,'template_include'));

    /*********** Archive Title Filter ***********/
    add_filter('get_the_archive_title', array($this, 'archive_title') );

    /*********** Template Actions ***********/
    add_action( $this->_token.'_article_testimonials_action', 'tw_testimonials_plugin_post_testimonials_action', 10);

    add_action( $this->_token.'_article_team_action',   $this->_token.'_article_team_action', 10);
    add_action( $this->_token.'_article_documents_action', $this->_token.'_article_documents_action', 10);
    add_action( $this->_token.'_article_client_action', $this->_token.'_article_client_action', 10);



    /*********** Team Member Plugin Integration ***********/
    add_filter('tw_teams_plugin_team_members_basic_info_filter', array($this,'team_member_info_update'));
    add_filter('tw_teams_plugin_team_members_social_info_filter', array($this,'team_member_social_info_update'));
  }

  public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

  public function init_options(){
    $this->cpt_slug     = get_option('wpt_'.$this->cpt.'_slug')           ? get_option('wpt_'.$this->cpt.'_slug')          : "article";
    $this->cat_slug = get_option('wpt_'.$this->cpt.'_category_slug')  ? get_option('wpt_'.$this->cpt.'_category_slug') : "article";
    $this->tag_slug = get_option('wpt_'.$this->cpt.'_tag_slug')       ? get_option('wpt_'.$this->cpt.'_tag_slug')      : "article-tag";

    $this->has_search   = get_option('wpt_'.$this->cpt.'_search')=='on' ? true : false;
    $this->has_archive  = get_option('wpt_'.$this->cpt.'_archive')=='on'  ? true : false;
    $this->has_category = get_option('wpt_'.$this->cpt.'_category')=='on' ? true : false;
    $this->has_tag      = get_option('wpt_'.$this->cpt.'_tag')=='on'      ? true : false;

    $this->has_article_contact   = get_option('wpt_'.$this->cpt.'_article_contact') =='on'    ? true : false;
    $this->has_article_social    = get_option('wpt_'.$this->cpt.'_article_social')  =='on'    ? true : false;
    $this->has_article_urls      = get_option('wpt_'.$this->cpt.'_article_urls')    =='on'    ? true : false;
    $this->has_article_status    = get_option('wpt_'.$this->cpt.'_article_status')  =='on'    ? true : false;
    $this->has_article_videos    = get_option('wpt_'.$this->cpt.'_article_videos')  =='on'    ? true : false;

    $this->has_testimonials = get_option('wpt_'.$this->cpt.'_testimonials') =='on' ? true : false;
    $this->has_client       = get_option('wpt_'.$this->cpt.'_client')       =='on' ? true : false;
    $this->has_teams        = get_option('wpt_'.$this->cpt.'_team')         =='on' ? true : false;
    $this->has_documents    = get_option('wpt_'.$this->cpt.'_documents')    =='on' ? true : false;
  }
  public function init_post_type(){
    $this->register_post_type(
                            $this->cpt,
                            __( 'Articles',     $this->text_domain ),
                            __( 'Article',      $this->text_domain ),
                            __( 'Articles CPT', $this->text_domain),
                            array(
                              'menu_icon'=> 'dashicons-analytics',
                              'rewrite' => array('slug' => $this->cpt_slug),
                              'exclude_from_search' => $this->has_search,
                              'has_archive'     => $this->has_archive,
                              'show_in_rest'        => true,
                              'rest_base'           => $this->cpt_slug,
                          		'rest_controller_class' => 'WP_REST_Posts_Controller',
                            )
                        );

  }
  public function init_taxonomies(){
    if($this->has_category){
      $this->register_taxonomy(
        $this->cat_tax,
        __( 'Articles', $this->text_domain ),
        __( 'Article', $this->text_domain ),
        $this->cpt,
        array(
          'hierarchical'=>true,
          'rewrite'=>array('slug'=>$this->cat_slug)
        )
      );
    }

    if($this->has_tag){
     $this->register_taxonomy(
        $this->tag_tax,
        __( 'Article Tags', $this->text_domain ),
        __( 'Article Tag', $this->text_domain ),
        $this->cpt,
        array(
          'hierarchical'=>false,
          'rewrite'=>array('slug'=>$this->tag_slug)
        )
      );
    }
  }
  public function init_metaboxes(){
    if( !function_exists('acf_add_local_field_group') ){
      $this->load_acf();
    }

    if($this->has_category){
      $this->metabox_category();
    }

    if($this->has_article_contact){
      $this->metabox_article_contact();
    }

    if($this->has_article_status){
      $this->metabox_article_dates();
    }

    if($this->has_article_social){
      $this->metabox_article_social();
    }

    if($this->has_article_urls){
      $this->metabox_article_urls();
    }

    if($this->has_article_videos){
      $this->metabox_article_videos();
    }

    $this->metabox_article_gallery();

    if($this->has_teams){
      $this->metabox_article_teams();
    }

    if($this->has_testimonials){
      $this->metabox_article_testimonials();
    }

    if($this->has_client){
      $this->metabox_article_client();
    }

    if($this->has_documents){
      $this->metabox_article_documents();
    }

  }

  /*********** Load ACF ***********/
     public function load_acf(){
      add_filter('acf/settings/path', array($this, 'acf_settings_path') );
      add_filter('acf/settings/dir',  array($this, 'acf_settings_dir') );
      add_filter('acf/settings/show_admin', '__return_false');
      include_once( $this->dir.'/includes/plugins/advanced-custom-fields-pro/acf.php');
     }
 
  public function acf_settings_path( $path ) {
    return $this->dir.'/includes/plugins/advanced-custom-fields-pro/';
  }

  public function acf_settings_dir( $dir ) {
    return $this->dir.'/includes/plugins/advanced-custom-fields-pro/';
  }

  /*********** Metaboxes ***********/
  /*** Taxonomy Metaboxes ***/
  public function metabox_category(){
    acf_add_local_field_group(array (
    	'key' => $this->_token.'_article_category_options',
    	'title' => 'Article Article Options',
    	'fields' => array (
    		array (
    			'key' => $this->_token.'_category_img',
    			'label' => 'Image',
    			'name' => $this->_token.'_category_img',
    			'type' => 'image',
    			'instructions' => 'Choose a default image for this article',
    			'required' => 1,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'return_format' => 'array',
    			'preview_size' => 'thumbnail',
    			'library' => 'all',
    			'min_width' => '',
    			'min_height' => '',
    			'min_size' => '',
    			'max_width' => '',
    			'max_height' => '',
    			'max_size' => '',
    			'mime_types' => '',
    		),
    		array (
    			'key' => $this->_token.'_category_colour',
    			'label' => 'Colour',
    			'name' => $this->_token.'_category_colour',
    			'type' => 'color_picker',
    			'instructions' => 'Choose a colour for this gallery',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'default_value' => '#000000',
    		),
    	),
    	'location' => array (
    		array (
    			array (
    				'param' => 'taxonomy',
    				'operator' => '==',
    				'value' => $this->cat_tax,
    			),
    		),
    	),
    	'menu_order' => 0,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'field',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => 'TW Articles Plugin - Article Options',
    ));
  }

  /*** Post Type Metaboxes ***/
  public function metabox_article_contact(){
    $article_contact_fields = array (
    		'phone'=>array (
    			'key' => $this->_token.'_article_phone',
    			'label' => 'Phone',
    			'name' => $this->_token.'_article_phone',
    			'type' => 'text',
    			'instructions' => '',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => 50,
    				'class' => '',
    				'id' => '',
    			),
    			'default_value' => '',
    			'placeholder' => '1 (514) 555-8888',
    			'prepend' => '',
    			'append' => '',
    			'maxlength' => '',
    			'readonly' => 0,
    			'disabled' => 0,
    		),
    		'tollfree'=>array (
    			'key' => $this->_token.'_article_tollfree',
    			'label' => 'Toll Free Number',
    			'name' => $this->_token.'_article_tollfree',
    			'type' => 'text',
    			'instructions' => '',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => 50,
    				'class' => '',
    				'id' => '',
    			),
    			'default_value' => '',
    			'placeholder' => '1 800-555-5555',
    			'prepend' => '',
    			'append' => '',
    			'maxlength' => '',
    			'readonly' => 0,
    			'disabled' => 0,
    		),
    		'email'=>array (
    			'key' => $this->_token.'_article_email',
    			'label' => 'Email',
    			'name' => $this->_token.'_article_email',
    			'type' => 'email',
    			'instructions' => '',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => 50,
    				'class' => '',
    				'id' => '',
    			),
    			'default_value' => '',
    			'placeholder' => 'name@example.com',
    			'prepend' => '',
    			'append' => '',
    		),
    		'website'=>array (
    			'key' => $this->_token.'_article_url',
    			'label' => 'Website',
    			'name' => $this->_token.'_article_url',
    			'type' => 'url',
    			'instructions' => '',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => 50,
    				'class' => '',
    				'id' => '',
    			),
    			'default_value' => '',
    			'placeholder' => 'http://www.example.com',
    		),
    	);
    $article_contact_fields = apply_filters($this->_token.'_article_contact_fields_filter', $article_contact_fields);
    acf_add_local_field_group(array (
    	'key' => $this->_token.'_article_contact_group',
    	'title' => 'Contact Info',
    	'fields' => $article_contact_fields,
    	'location' => array (
    		array (
    			array (
    				'param' => 'post_type',
    				'operator' => '==',
    				'value' => $this->cpt,
    			),
    		),
    	),
    	'menu_order' => 0,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'label',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => '',
    ));
  }
  public function metabox_article_dates(){
    $fields = array (
    		'start_date' => array (
    			'key' => $this->_token.'_article_start_date',
    			'label' => 'Article Start Date',
    			'name' => $this->_token.'_article_start_date',
    			'type' => 'date_picker',
    			'instructions' => 'Enter the article start date.',
    			'required' => 1,
    			'conditional_logic' => '',
    			'wrapper' => array (
    				'width' => 50,
    				'class' => '',
    				'id' => '',
    			),
    			'display_format' => 'd/m/Y',
    			'return_format' => 'd/m/Y',
    			'first_day' => 1,
    		),
    		'end_date' => array (
    			'key' => $this->_token.'_article_end_date',
    			'label' => 'Article End Date',
    			'name' => $this->_token.'_article_end_date',
    			'type' => 'date_picker',
    			'instructions' => '<span style="color:red;">Leave blank if the article is <b>"In Progress"</b>.</span>',
    			'required' => 0,
    			'conditional_logic' => '',
    			'wrapper' => array (
    				'width' => 50,
    				'class' => '',
    				'id' => '',
    			),
    			'display_format' => 'd/m/Y',
    			'return_format' => 'd/m/Y',
    			'first_day' => 1,
    		),
    	);
    $fields = apply_filters($this->_token.'_article_status_fields_filter', $fields);
    acf_add_local_field_group(array (
    	'key' => $this->_token.'_article_dates',
    	'title' => 'Dates',
    	'fields' => $fields,
    	'location' => array (
    		array (
    			array (
    				'param' => 'post_type',
    				'operator' => '==',
    				'value' => $this->cpt,
    			),
    		),
    	),
    	'menu_order' => 1,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'field',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => '',
    ));

  }
  public function metabox_article_social(){
    $article_social_fields = array(
      'facebook'=>array (
    		'key' => $this->_token.'_article_facebook',
    		'label' => 'Facebook',
    		'name' => $this->_token.'_article_facebook',
    		//'type' => 'url',
    		'type' => 'text',
    		'instructions' => 'Facebook public profile or fan page',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		//'placeholder' => 'https://facebook.com/USERNAME',
    		'placeholder' => 'USERNAME',
    		'prepend' => 'https://facebook.com/',
    	),
    	'googleplus'=>array (
    		'key' => $this->_token.'_article_google_plus',
    		'label' => 'Google Plus',
    		'name' => $this->_token.'_article_google_plus',
    		//'type' => 'url',
    		'type' => 'text',
    		'instructions' => 'Google+ profile page',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		//'placeholder' => 'https://plus.google.com/+USERNAME',
    		'placeholder' => '+USERNAME',
    		'prepend' => 'https://plus.google.com/',
    	),
    	'twitter'=>array (
    		'key' => $this->_token.'_article_twitter',
    		'label' => 'Twitter',
    		'name' => $this->_token.'_article_twitter',
    		'type' => 'text',
    		'instructions' => 'Twitter username without @ symbol',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		'placeholder' => 'USERNAME',
    		'prepend' => 'https://twitter.com/',
    		'append' => '',
    		'maxlength' => '',
    		'readonly' => 0,
    		'disabled' => 0,
    	),
    	'instagram'=>array (
    		'key' => $this->_token.'_article_instagram',
    		'label' => 'Instagram Username',
    		'name' => $this->_token.'_article_instagram',
    		'type' => 'text',
    		'instructions' => 'Instagram username without the @ symbol',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		'placeholder' => 'USERNAME',
    		'prepend' => 'https://instagram.com/',
    		'append' => '',
    		'maxlength' => '',
    		'readonly' => 0,
    		'disabled' => 0,
    	),
    	'linkedin'=>array (
    		'key' => $this->_token.'_article_linkedin',
    		'label' => 'Linkedin',
    		'name' => $this->_token.'_article_linkedin',
    		//'type' => 'url',
    		'type' => 'text',
    		'instructions' => 'Linkedin Profile Page',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		//'placeholder' => 'https://linkedin.com/in/USERNAME',
    		'placeholder' => 'USERNAME',
    		'prepend' => 'https://linkedin.com/in/',
    	),
    	'slideshare'=>array (
    		'key' => $this->_token.'_article_slideshare',
    		'label' => 'Slideshare',
    		'name' => $this->_token.'_article_slideshare',
    		//'type' => 'url',
    		'type' => 'text',
    		'instructions' => 'Slideshare Profile Page',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		//'placeholder' => 'http://www.slideshare.net/USERNAME',
    		'placeholder' => 'USERNAME',
    		'prepend' => 'http://www.slideshare.net/',
    	),
    	'flickr'=>array (
    		'key' => $this->_token.'_article_flickr',
    		'label' => 'Flickr',
    		'name' => $this->_token.'_article_flickr',
    		//'type' => 'url',
    		'type' => 'text',
    		'instructions' => 'Flickr public profile page',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		//'placeholder' => 'https://flickr.com/photos/USERNAME',
    		'placeholder' => 'USERNAME',
    		'prepend' => 'https://flickr.com/photos/',
    	),
    	'pinterest'=>array (
    		'key' => $this->_token.'_article_pinterest',
    		'label' => 'Pinterest',
    		'name' => $this->_token.'_article_pinterest',
    		//'type' => 'url',
    		'type' => 'text',
    		'instructions' => 'Pinterest public page',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		//'placeholder' => 'https://pinterest.com/USERNAME',
    		'placeholder' => 'USERNAME',
    		'prepend' => 'https://pinterest.com/',
    	),
    	'youtube'=>array (
    		'key' => $this->_token.'_article_youtube',
    		'label' => 'Youtube',
    		'name' => $this->_token.'_article_youtube',
    		//'type' => 'url',
    		'type' => 'text',
    		'instructions' => 'Youtube channel or playlist url',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		'placeholder' => 'XXXX',
    		'prepend'=>'https://www.youtube.com/',
    	),
    	'vimeo'=>array (
    		'key' => $this->_token.'_article_vimeo',
    		'label' => 'Vimeo',
    		'name' => $this->_token.'_article_vimeo',
    		//'type' => 'url',
    		'type' => 'text',
    		'instructions' => 'Vimeo channel or playlist url',
    		'required' => 0,
    		'conditional_logic' => 0,
    		'wrapper' => array (
    			'width' => 50,
    			'class' => '',
    			'id' => '',
    		),
    		'default_value' => '',
    		//'placeholder' => 'https://vimeo.com/USERNAME',
    		'placeholder' => 'USERNAME',
    		'prepend' => 'https://vimeo.com/',
    	),
    );
    $article_social_fields = apply_filters($this->_token.'_article_social_info_filter', $article_social_fields);
    acf_add_local_field_group(array (
    	'key' => $this->_token.'_article_social',
    	'title' => 'Social Networks',
    	'fields' => $article_social_fields,
    	'location' => array (
    		array (
    			array (
    				'param' => 'post_type',
    				'operator' => '==',
    				'value' => $this->cpt,
    			),
    		),
    	),
    	'menu_order' => 5,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'field',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => '',
    ));

  }
  public function metabox_article_urls(){
    acf_add_local_field_group(array (
    	'key' => $this->_token.'_article_urls_group',
    	'title' => 'Article URLS',
    	'fields' => array (
    		array (
    			'key' => $this->_token.'_article_urls',
    			'label' => 'Article Info',
    			'name' => $this->_token.'_article_urls',
    			'type' => 'repeater',
    			'instructions' => 'Enter links to the article',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'collapsed' => $this->_token.'_link_url',
    			'min' => '',
    			'max' => '',
    			'layout' => 'table',
    			'button_label' => 'Add URL',
    			'sub_fields' => array (
    				array (
    					'key' => $this->_token.'_link_url',
    					'label' => 'URL',
    					'name' => $this->_token.'_link_url',
    					'type' => 'url',
    					'instructions' => '',
    					'required' => 1,
    					'conditional_logic' => 0,
    					'wrapper' => array (
    						'width' => 50,
    						'class' => '',
    						'id' => '',
    					),
    					'default_value' => '',
    					'placeholder' => 'http://www.example.com',
    				),
    				array (
    					'key' => $this->_token.'_link_title',
    					'label' => 'Link Title',
    					'name' => $this->_token.'_link_title',
    					'type' => 'text',
    					'instructions' => '',
    					'required' => 1,
    					'conditional_logic' => 0,
    					'wrapper' => array (
    						'width' => 50,
    						'class' => '',
    						'id' => '',
    					),
    					'default_value' => '',
    					'placeholder' => '',
    					'prepend' => '',
    					'append' => '',
    					'maxlength' => '',
    					'readonly' => 0,
    					'disabled' => 0,
    				),
    			),
    		),
    	),
    	'location' => array (
    		array (
    			array (
    				'param' => 'post_type',
    				'operator' => '==',
    				'value' => $this->cpt,
    			),
    		),
    	),
    	'menu_order' => 6,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'field',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => 'Article Info',
    ));

  }
  public function metabox_article_gallery(){
    $fields = array (
      		array (
      			'key'   => $this->_token.'_imgs',
      			'label' => 'Images',
      			'name'  => $this->_token.'_article_imgs',
      			'type'  => 'gallery',
      			'instructions' => 'Add images to this photo set',
      			'required' => 1,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'min' => '',
      			'max' => '',
      			'preview_size' => 'thumbnail',
      			'library' => 'all',
      			'min_width' => '',
      			'min_height' => '',
      			'min_size' => '',
      			'max_width' => '',
      			'max_height' => '',
      			'max_size' => '',
      			'mime_types' => '',
      		),
      	);
    $fields = apply_filters($this->_token.'_article_gallery_fields_filter', $fields);
    acf_add_local_field_group(array (
      	'key' => $this->_token.'_images',
      	'title' => 'Image Gallery',
      	'fields' => $fields,
      	'location' => array (
      		array (
      			array (
      				'param' => 'post_type',
      				'operator' => '==',
      				'value' => $this->cpt,
      			),
      		),
      	),
      	'menu_order' => 7,
      	'position' => 'normal',
      	'style' => 'default',
      	'label_placement' => 'top',
      	'instruction_placement' => 'field',
      	'hide_on_screen' => '',
      	'active' => 1,
      	'description' => 'TW Articles Plugin - Photo Gallery',
      ));
  }
  public function metabox_article_videos(){
    acf_add_local_field_group(array (
    	'key' => $this->_token.'_article_videos_group',
    	'title' => 'Article Videos',
    	'fields' => array (
    		array (
    			'key' => $this->_token.'_enable_article_videos',
    			'label' => 'Article Videos',
    			'name' => $this->_token.'_enable_article_videos',
    			'type' => 'true_false',
    			'instructions' => '',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'message' => 'Enable Videos',
    			'default_value' => 0,
    		),
    		array (
    			'key' => $this->_token.'_article_videos',
    			'label' => 'Videos',
    			'name' => $this->_token.'_article_videos',
    			'type' => 'repeater',
    			'instructions' => '',
    			'required' => 1,
    			'conditional_logic' => array (
    				array (
    					array (
    						'field' => $this->_token.'_enable_article_videos',
    						'operator' => '==',
    						'value' => '1',
    					),
    				),
    			),
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'collapsed' => '',
    			'min' => 1,
    			'max' => '',
    			'layout' => 'table',
    			'button_label' => 'Add Video',
    			'sub_fields' => array (
    				array (
    					'key' => $this->_token.'_article_video_embed',
    					'label' => 'Video URL',
    					'name' => $this->_token.'_article_video_embed',
    					'type' => 'oembed',
    					'instructions' => __('Enter the URL of the video you\'d like to add.',$this->text_domain),
    					'required' => 1,
    					'conditional_logic' => 0,
    					'wrapper' => array (
    						'width' => '',
    						'class' => '',
    						'id' => '',
    					),
    					'width' => 640,
    					'height' => 480,
    					'default_value' => '',
    					'placeholder' => 'https://www.youtube.com/watch?v=xxxxxxxx',
    				),
    			),
    		),
    	),
    	'location' => array (
    		array (
    			array (
    				'param' => 'post_type',
    				'operator' => '==',
    				'value' => $this->cpt,
    			),
    		),
    	),
    	'menu_order' => 8,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'label',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => 'TW Article Plugin - Article Videos',
    ));

  }

  /*** Relationship Metaboxes ***/
  public function metabox_article_teams(){
    acf_add_local_field_group(array (
    	'key' => $this->_token.'article_team_members_group',
    	'title' => 'Team Members',
    	'fields' => array (
    		array (
    			'key' => $this->_token.'_article_enable_team_members',
    			'label' => 'Team Members',
    			'name' => $this->_token.'_article_enable_team_members',
    			'type' => 'true_false',
    			'instructions' => '',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'message' => 'Enable Team Members',
    			'default_value' => 0,
    		),
    		array (
    			'key' => $this->_token.'_article_team_members',
    			'label' => 'Team Members',
    			'name' => $this->_token.'_article_team_members',
    			'type' => 'repeater',
    			'instructions' => '',
    			'required' => 1,
    			'conditional_logic' => array (
    				array (
    					array (
    						'field' => $this->_token.'_article_enable_team_members',
    						'operator' => '==',
    						'value' => '1',
    					),
    				),
    			),
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'collapsed' => $this->_token.'_article_team_member',
    			'min' => '',
    			'max' => '',
    			'layout' => 'table',
    			'button_label' => 'Add Team Member',
    			'sub_fields' => array (
    				array (
    					'key' => $this->_token.'_article_team_member',
    					'label' => 'Team Member',
    					'name' => $this->_token.'_article_team_member',
    					'type' => 'post_object',
    					'instructions' => 'Select a Team Member from the list. If the Team Member you are looking for is not shown, you must add them first before associating them to this article.',
    					'required' => 1,
    					'conditional_logic' => 0,
    					'wrapper' => array (
    						'width' => 50,
    						'class' => '',
    						'id' => '',
    					),
    					'post_type' => array (
    						0 => 'tw_team-member',
    					),
    					'taxonomy' => array (
    					),
    					'allow_null' => 0,
    					'multiple' => 0,
    					'return_format' => 'object',
    					'ui' => 1,
    				),
    				array (
    					'key' => $this->_token.'_article_article_role',
    					'label' => 'Article Role',
    					'name' => $this->_token.'_article_team_member_role',
    					'type' => 'text',
    					'instructions' => 'Enter the role this Team Member held for this article.',
    					'required' => 1,
    					'conditional_logic' => 0,
    					'wrapper' => array (
    						'width' => 50,
    						'class' => '',
    						'id' => '',
    					),
    					'default_value' => '',
    					'placeholder' => '',
    					'prepend' => '',
    					'append' => '',
    					'maxlength' => '',
    					'readonly' => 0,
    					'disabled' => 0,
    				),
    			),
    		),
    	),
    	'location' => array (
    		array (
    			array (
    				'param' => 'post_type',
    				'operator' => '==',
    				'value' => $this->cpt,
    			),
    		),
    	),
    	'menu_order' => 10,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'label',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => 'Article Team Members Options',
    ));
  }
  public function metabox_article_client(){
    acf_add_local_field_group(array (
    	'key' => $this->_token.'_article_client',
    	'title' => 'Client',
    	'fields' => array (
    		array (
    			'key' => $this->_token.'_article_enable_client',
    			'label' => 'Clients',
    			'name' => $this->_token.'_article_enable_client',
    			'type' => 'true_false',
    			'instructions' => 'Select if you would like to show a Client with this Article',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'message' => 'Enable Client',
    			'default_value' => 0,
    		),
    		array (
    			'key' => $this->_token.'_article_client',
    			'label' => 'Client',
    			'name' => $this->_token.'_article_client',
    			'type' => 'relationship',
    			'instructions' => 'Select a Client from the available list. Limit 1',
    			'required' => 1,
    			'conditional_logic' => array (
    				array (
    					array (
    						'field' => $this->_token.'_article_enable_client',
    						'operator' => '==',
    						'value' => '1',
    					),
    				),
    			),
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'post_type' => array (
    				0 => 'tw_client',
    			),
    			'taxonomy' => array (
    			),
    			'filters' => array (
    				0 => 'search',
    				//1 => 'taxonomy',
    			),
    			'elements' => array (
    				0 => 'featured_image',
    			),
    			'min' => '',
    			'max' => '1',
    			'return_format' => 'id',
    		),
    	),
    	'location' => array (
    		array (
    			array (
    				'param' => 'post_type',
    				'operator' => '==',
    				'value' => $this->cpt,
    			),
    		),
    	),
    	'menu_order' => 12,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'field',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => 'Article Client Option',
    ));
  }
  public function metabox_article_testimonials(){
    acf_add_local_field_group(array (
    	'key' => $this->_token.'_article_testimonials',
    	'title' => 'Testimonials',
    	'fields' => array (
    		array (
    			'key' => $this->_token.'_article_enable_testimonials',
    			'label' => 'Testimonials',
    			'name' => $this->_token.'_article_enable_testimonials',
    			'type' => 'true_false',
    			'instructions' => 'Select if you would like to show Testimonials with this Article',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'message' => 'Enable Testimonials',
    			'default_value' => 0,
    		),
    		array (
    			'key' => $this->_token.'_article_testimonials_title',
    			'label' => 'Title',
    			'name' => $this->_token.'_article_testimonials_title',
    			'type' => 'text',
    			'instructions' => 'Enter the title of the testimonials area.',
    			'required' => 0,
    			'conditional_logic' => array (
    				array (
    					array (
    						'field' => $this->_token.'_article_enable_testimonials',
    						'operator' => '==',
    						'value' => '1',
    					),
    				),
    			),
    			'wrapper' => array (
    				'width' => 50,
    				'class' => '',
    				'id' => '',
    			),
    			'default_value' => '',
    			'placeholder' => 'What they are saying...',
    			'prepend' => '',
    			'append' => '',
    			'maxlength' => '',
    			'readonly' => 0,
    			'disabled' => 0,
    		),
    		array (
    			'key' => $this->_token.'_article_testimonials_style',
    			'label' => 'Style',
    			'name' => $this->_token.'_article_testimonials_style',
    			'type' => 'select',
    			'instructions' => 'Select how you want to show the testimonials',
    			'required' => 1,
    			'conditional_logic' => array (
    				array (
    					array (
    						'field' => $this->_token.'_article_enable_testimonials',
    						'operator' => '==',
    						'value' => '1',
    					),
    				),
    			),
    			'wrapper' => array (
    				'width' => 50,
    				'class' => '',
    				'id' => '',
    			),
    			'choices' => array (
    				'grid' => 'Grid',
    				'carousel' => 'Slider Carousel',
    			),
    			'default_value' => array (
    				'carousel' => 'carousel',
    			),
    			'allow_null' => 0,
    			'multiple' => 0,
    			'ui' => 0,
    			'ajax' => 0,
    			'placeholder' => '',
    			'disabled' => 0,
    			'readonly' => 0,
    		),
    		array (
    			'key' => $this->_token.'_article_testimonials',
    			'label' => 'Testimonials',
    			'name' => $this->_token.'_article_testimonials',
    			'type' => 'relationship',
    			'instructions' => '',
    			'required' => 1,
    			'conditional_logic' => array (
    				array (
    					array (
    						'field' => $this->_token.'_article_enable_testimonials',
    						'operator' => '==',
    						'value' => '1',
    					),
    				),
    			),
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'post_type' => array (
    				0 => 'tw_testimonial',
    			),
    			'taxonomy' => array (
    			),
    			'filters' => array (
    				0 => 'search',
    				//1 => 'taxonomy',
    			),
    			'elements' => array (
    				0 => 'featured_image',
    			),
    			'min' => '',
    			'max' => '',
    			'return_format' => 'id',
    		),

    	),
    	'location' => array (
    		array (
    			array (
    				'param' => 'post_type',
    				'operator' => '==',
    				'value' => $this->cpt,
    			),
    		),
    	),
    	'menu_order' => 11,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'field',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => 'Article Testimonial Option',
    ));
  }
  public function metabox_article_documents(){
    acf_add_local_field_group(array (
    	'key' => $this->_token.'_article_documents',
    	'title' => 'Documents',
    	'fields' => array (
    		array (
    			'key' => $this->_token.'_article_enable_documents',
    			'label' => 'Documents',
    			'name' => $this->_token.'_article_enable_documents',
    			'type' => 'true_false',
    			'instructions' => 'Select if you would like to attached Documents to this Article',
    			'required' => 0,
    			'conditional_logic' => 0,
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'message' => 'Enable Documents',
    			'default_value' => 0,
    		),
    		array (
    			'key' => $this->_token.'_article_documents',
    			'label' => 'Documents',
    			'name' => $this->_token.'_article_documents',
    			'type' => 'relationship',
    			'instructions' => 'Select Documents from the available list. Min. 1',
    			'required' => 1,
    			'conditional_logic' => array (
    				array (
    					array (
    						'field' => $this->_token.'_article_enable_documents',
    						'operator' => '==',
    						'value' => '1',
    					),
    				),
    			),
    			'wrapper' => array (
    				'width' => '',
    				'class' => '',
    				'id' => '',
    			),
    			'post_type' => array (
    				0 => 'tw_document',
    			),
    			'taxonomy' => array (
    			),
    			'filters' => array (
    				0 => 'search',
    				//1 => 'taxonomy',
    			),
    			'elements' => '',
    			'min' => '1',
    			'max' => '',
    			'return_format' => 'object',
    		),
    	),
    	'location' => array (
    		array (
    			array (
    				'param' => 'post_type',
    				'operator' => '==',
    				'value' => $this->cpt,
    			),
    		),
    	),
    	'menu_order' => 13,
    	'position' => 'normal',
    	'style' => 'default',
    	'label_placement' => 'top',
    	'instruction_placement' => 'field',
    	'hide_on_screen' => '',
    	'active' => 1,
    	'description' => 'Article Documents Option',
    ));
  }


  /*********** Pre Get Posts Filter ***********/
  public function pre_get_posts($query) {
    $posts_per_page = get_option('wpt_'.$this->cpt.'_posts_per_page');
    if (!is_admin() && (is_tax($this->cat_tax) || is_tax($this->tag_tax)) && $query->is_tax){
      set_query_var( 'post_type', $this->cpt);
      set_query_var( 'orderby', 'title' );
      set_query_var( 'order', 'ASC' );
      set_query_var('posts_per_page', $posts_per_page);
    }
    if(!is_admin() && is_post_type_archive($this->cpt) ){
      set_query_var( 'orderby', 'title' );
      set_query_var( 'order', 'ASC' );
      set_query_var('posts_per_page', $posts_per_page);
    }
  }

  /*********** Admin Filter Profile Image ***********/
  public function manage_posts_columns($columns){
      $new['thumbnail'] = __('Image', $this->text_domain);
      foreach($columns as $key => $title){
        $new[$key] = $title;
      }
      return $new;
  }
  public function manage_posts_custom_column($column_name, $id){
    if($column_name == 'thumbnail'){
      $post_thumbnail_id = get_post_thumbnail_id($id);
      if ($post_thumbnail_id) {
          $post_thumbnail_img = wp_get_attachment_image_src($post_thumbnail_id, 'featured_preview');
          echo '<img src="'. $post_thumbnail_img[0] .'" width="50px" height="50px" />';
      }
    }
  }
  public function admin_column_width() {
    echo  '<style type="text/css">.column-thumbnail { width: 50px !important; overflow: hidden; }</style>';
  }

  /*********** Admin Filter by Category ***********/
  public function filter_post_type_by_taxonomy() {
    if($this->has_category){
    	global $typenow;
    	if ($typenow == $this->cpt) {
    		$selected      = isset($_GET[$this->cat_tax]) ? $_GET[$this->cat_tax] : '';
    		$info_taxonomy = get_taxonomy($this->cat_tax);
    		wp_dropdown_categories(array(
    			'show_option_all' => __("Show All {$info_taxonomy->label}"),
    			'taxonomy'        => $this->cat_tax,
    			'name'            => $this->cat_tax,
    			'orderby'         => 'name',
    			'selected'        => $selected,
    			'show_count'      => true,
    			'hide_empty'      => true,
    			'value_field'     => 'slug',
    		));
    	};
  	}
  }
  public function convert_id_to_term_in_query($query) {
    if($this->has_category){
    	global $pagenow;
    	$q_vars    = &$query->query_vars;
    	if ( $pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $this->cpt && isset($q_vars[$this->cat_tax]) && is_numeric($q_vars[$this->cat_tax]) && $q_vars[$this->cat_tax] != 0 ) {
    		$term = get_term_by('id', $q_vars[$this->cat_tax], $this->cat_tax);
    		$q_vars[$this->cat_tax] = $term->slug;
    	}
  	}
  }

  /*********** Template Filter ***********/
  public function template_include( $template ) {
    if ( is_singular($this->cpt) ) {
      return $this->get_template_hierarchy( 'single-'.$this->cpt );
    }elseif(is_post_type_archive($this->cpt) ){
      return $this->get_template_hierarchy( 'archive-'.$this->cpt );
    }elseif(is_tax($this->cat_tax)){
      return $this->get_template_hierarchy( 'taxonomy-'.$this->cat_tax );
    }elseif(is_tax($this->tag_tax)){
      return $this->get_template_hierarchy( 'taxonomy-'.$this->tag_tax );
    }

    return $template;
  }
  public function get_template_hierarchy($template){
    $template_slug = rtrim( $template, '.php' );
    $template = $template_slug . '.php';
    $theme_file = locate_template( array($template, 'plugins/'.$this->text_domain.'/templates/'.$template ) );
    if ( $theme_file!=='' ) {
        return $theme_file;
    }
    return $this->dir.'/templates/'.$template;
  }

  /*********** Archive Title Filter ***********/
  public function archive_title($title){
    if(is_post_type_archive($this->cpt)){
      $page_id = get_option('wpt_'.$this->cpt.'_page');
      $page_title = get_option('wpt_'.$this->cpt.'_archive_title');
      if($page_id){
        $title = get_the_title($page_id);
      }else{
       $title = $page_title;
      }

    }elseif(is_tax($this->cat_tax)){
      $title = sprintf( __( '%1$s: %2$s' ), __('Article',$this->text_domain), single_term_title( '', false ) );
    }elseif(is_tax($this->tag_tax)){
      $title = sprintf( __( '%1$s: %2$s' ), __('Article Tag',$this->text_domain), single_term_title( '', false ) );
    }

    return __($title, $this->text_domain);
  }

  /*********** Team Member Plugin Integration ***********/
  public function team_member_info_update($team_member_fields){
    $token = 'tw_teams_plugin';
    $team_member_fields['title']['wrapper']['width'] = '50';

    $fields['title'] = $team_member_fields['title'];
    unset($team_member_fields['title']);

    $fields['company'] = array (
  			'key' => $token.'_team_member_company',
  			'label' => 'Company',
  			'name' => $token.'_team_member_company',
  			'type' => 'text',
  			'instructions' => 'Team Member company or organization',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array (
  				'width' => '50',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'maxlength' => '',
  			'readonly' => 0,
  			'disabled' => 0,
  		);

    $merged = array_merge($fields, $team_member_fields);

    return $merged;
  }
  public function team_member_social_info_update($social){

    return $social;
  }


  /*********** Parent Ovveride ***********/
	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
     	$load_css = get_option('wpt_'.$this->cpt.'_load_css')=='on' ? true : false;
     	if($load_css){
     		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
     		wp_enqueue_style( $this->_token . '-frontend' );
       }
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
     	$load_js = get_option('wpt_'.$this->cpt.'_load_js')=='on' ? true : false;
     	if($load_js){
     		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
     		wp_enqueue_script( $this->_token . '-frontend' );
       }
	} // End enqueue_scripts ()

}
