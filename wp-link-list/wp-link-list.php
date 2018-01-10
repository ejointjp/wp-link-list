<?php
/*
Plugin Name: WP Link List
Plugin URI: http://e-joint.jp/works/wp-link-list/
Description: WP Link List is shortcode base generate link list plugin.
Version: 0.1.6
Author: e-JOINT.jp
Author URI: http://e-joint.jp
Text Domain: wp-link-list
Domain Path: /languages
License: GPL2
*/

/*  Copyright 2017 e-JOINT.jp (email : mail@e-joint.jp)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Wp_link_list
{

  private $options;
  const VERSION = '0.1.6';

  public function __construct()
  {
    //翻訳ファイルの読み込み
    load_plugin_textdomain('wp-link-list', false, basename(dirname(__FILE__)) . '/languages');

    //管理画面へCSS, JSの追加
    add_action('admin_enqueue_scripts', array(&$this, 'add_admin_css_js'));

    //リストのCSSを追加
    add_action('wp_enqueue_scripts', array(&$this, 'add_css'));

    // メニューの追加
    add_action( 'admin_menu', array( &$this, 'add_plugin_page' ) );

    // ページの初期化
    add_action( 'admin_init', array( &$this, 'page_init' ) );

    //カスタム投稿タイプの追加
    add_action('init', array(&$this, 'create_post_type'));

    //カスタムフィールドの追加
    add_action('admin_init', array(&$this, 'add_custom_field'));

    //カスタムフィールドの保存
    add_action('save_post', array(&$this, 'save_custom_field'));

    //ショートコードの設定
    add_shortcode ('link-list', array(&$this, 'link_list_shortcode'));

  }

  //管理画面の設定にメニューを追加
  public function add_plugin_page()
  {
    // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    //   $page_title: 設定ページの<title>部分
    //   $menu_title: メニュー名
    //   $capability: 権限 ( 'manage_options' や 'administrator' など)
    //   $menu_slug : メニューのslug
    //   $function  : 設定ページの出力を行う関数
    //   $icon_url  : メニューに表示するアイコン
    //   $position  : メニューの位置 ( 1 や 99 など )
    add_options_page( 'WP Link List', 'WP Link List', 'manage_options', 'wpll-setting', array( &$this, 'create_admin_page' ) );

    // 設定のサブメニューとしてメニューを追加する場合は下記のような形にします。
    // add_options_page( 'テスト設定', 'テスト設定', 'manage_options', 'wpll-setting', array( $this, 'create_admin_page' ) );
  }

  //設定ページの初期化
  public function page_init()
  {
    // 設定を登録します(入力値チェック用)。
    // register_setting( $option_group, $option_name, $sanitize_callback )
    //   $option_group    : 設定のグループ名
    //   $option_name     : 設定項目名(DBに保存する名前)
    //   $sanitize_callback : 入力値調整をする際に呼ばれる関数
    // register_setting( 'wpll-setting', 'wpll-setting', array( $this, 'sanitize' ) );
    register_setting( 'wpll-setting', 'wpll-setting' );

    // 入力項目のセクションを追加します。
    // add_settings_section( $id, $title, $callback, $page )
    //   $id     : セクションのID
    //   $title  : セクション名
    //   $callback : セクションの説明などを出力するための関数
    //   $page   : 設定ページのslug (add_menu_page()の$menu_slugと同じものにする)
    add_settings_section( 'wpll-setting-section-id', '', '', 'wpll-setting' );

    // 入力項目のセクションに項目を1つ追加します(今回は「メッセージ」というテキスト項目)。
    // add_settings_field( $id, $title, $callback, $page, $section, $args )
    //   $id     : 入力項目のID
    //   $title  : 入力項目名
    //   $callback : 入力項目のHTMLを出力する関数
    //   $page   : 設定ページのslug (add_menu_page()の$menu_slugと同じものにする)
    //   $section  : セクションのID (add_settings_section()の$idと同じものにする)
    //   $args   : $callbackの追加引数 (必要な場合のみ指定)
    // add_settings_field( 'message', 'メッセージ', array( &$this, 'message_callback' ), 'wpll-setting', 'wpll-setting-section-id' );
    add_settings_field( 'nocss', __('Do not use default CSS', 'wp-link-list'), array( &$this, 'nocss_callback' ), 'wpll-setting', 'wpll-setting-section-id' );
    add_settings_field( 'orderby', __('Link list orderby', 'wp-link-list'), array( &$this, 'orderby_callback' ), 'wpll-setting', 'wpll-setting-section-id' );
    add_settings_field( 'order', __('Link list order', 'wp-link-list'), array( &$this, 'order_callback' ), 'wpll-setting', 'wpll-setting-section-id' );
    add_settings_field( 'target', __('Value of target attribute', 'wp-link-list'), array( &$this, 'target_callback' ), 'wpll-setting', 'wpll-setting-section-id' );
    add_settings_field( 'display', __('Display', 'wp-link-list'), array( &$this, 'display_callback' ), 'wpll-setting', 'wpll-setting-section-id' );
    add_settings_field( 'balloon', __('Use Balloon', 'wp-link-list'), array( &$this, 'balloon_callback' ), 'wpll-setting', 'wpll-setting-section-id' );
    add_settings_field( 'thumbnail-size', __('Size of thumbnail', 'wp-link-list'), array( &$this, 'thumbnail_size_callback'), 'wpll-setting', 'wpll-setting-section-id');
  }

  //設定ページのHTMLを作成
  public function create_admin_page()
  {
    // 設定値を取得します。
    $this->options = get_option( 'wpll-setting' );
    ?>
    <div class="wrap">
      <h2>WP Link List</h2>
      <?php
      // add_options_page()で設定のサブメニューとして追加している場合は
      // 問題ありませんが、add_menu_page()で追加している場合
      // options-head.phpが読み込まれずメッセージが出ない(※)ため
      // メッセージが出るようにします。
      // ※ add_menu_page()の場合親ファイルがoptions-general.phpではない
      global $parent_file;
      if ( $parent_file != 'options-general.php' ) {
        require(ABSPATH . 'wp-admin/options-head.php');
      }
      ?>
      <form method="post" action="options.php">
      <?php
        // 隠しフィールドなどを出力します(register_setting()の$option_groupと同じものを指定)。
        settings_fields( 'wpll-setting' );
        // 入力項目を出力します(設定ページのslugを指定)。
        do_settings_sections( 'wpll-setting' );
        // 送信ボタンを出力します。
        submit_button();
      ?>
      <p><?php echo __('Please paste the short code below into the place you want to output.', 'wp-link-list'); ?></p>
      <p><strong>[link-list]</strong></p>
      </form>
    </div>
    <?php
  }

  public function nocss_callback()
  {
    ?><input type="checkbox" id="nocss" name="wpll-setting[nocss]" value="1"<?php checked($this->options['nocss'], 1); ?>><?php
  }

  public function orderby_callback()
  {
    ?><select name="wpll-setting[orderby]">
      <option value="menu_order"<?php selected($this->options['orderby'], 'menu_order'); ?>><?php echo __('By numerical order of "order"', 'wp-link-list'); ?></option>
      <option value="title"<?php selected($this->options['orderby'], 'title'); ?>><?php echo __('By Title', 'wp-link-list'); ?></option>
      <option value="post_date"<?php selected($this->options['orderby'], 'post_date'); ?>><?php echo __('By post date', 'wp-link-list'); ?></option>
      </select><?php
  }

  public function order_callback()
  {
    ?><select name="wpll-setting[order]">
      <option value="ASC"<?php selected($this->options['order'], 'ASC'); ?>><?php echo __('ASC', 'wp-link-list'); ?></option>
      <option value="DESC"<?php selected($this->options['order'], 'DESC'); ?>><?php echo __('DESC', 'wp-link-list'); ?></option>
      </select><?php
  }

  public function target_callback()
  {
    ?><select name="wpll-setting[target]">
      <option value=""><?php echo __('None', 'wp-link-list'); ?></option>
      <option value="_blank"<?php selected($this->options['target'], '_blank'); ?>>_blank</option>
      </select><?php
  }

  public function display_callback()
  {
    ?><select name="wpll-setting[display]">
      <option value="list"<?php selected($this->options['display'], 'list'); ?>><?php echo __('Title only', 'wp-link-list'); ?></option>
      <option value="thumbnail"<?php selected($this->options['display'], 'thumbnail'); ?>><?php echo __('Title and thumbnail', 'wp-link-list'); ?></option>
      <option value="description"<?php selected($this->options['display'], 'description'); ?>><?php echo __('Title and description', 'wp-link-list'); ?></option>
      <option value="all"<?php selected($this->options['display'], 'all'); ?>><?php echo __('Title, thumbnail and description', 'wp-link-list'); ?></option>
      </select><?php
  }

  public function balloon_callback()
  {
    ?><select name="wpll-setting[balloon]">
      <option value="">なし</option>
      <option value="up"<?php selected($this->options['balloon'], 'up'); ?>><?php echo __('Display on top', 'wp-link-list'); ?></option>
      <option value="left"<?php selected($this->options['balloon'], 'left'); ?>><?php echo __('Display on left', 'wp-link-list'); ?></option>
      <option value="right"<?php selected($this->options['balloon'], 'right'); ?>><?php echo __('Display on right', 'wp-link-list'); ?></option>
      <option value="down"<?php selected($this->options['balloon'], 'down'); ?>><?php echo __('Display on bottom', 'wp-link-list'); ?></option>
      </select>

      <p><?php echo __('Balloon display of the description of the list Valid when "Display" setting is "Title only list format" "Title and thumbnail".', 'wp-link-list'); ?></p>
      <?php
  }

  public function thumbnail_size_callback()
  {
    //設定されているサムネイルサイズの一覧を取得
    $size_list = get_intermediate_image_sizes();

    ?><select name="wpll-setting[thumbnail-size]">
        <?php foreach( $size_list as $size ){
          echo '<option value="' . $size . '"' . selected($this->options['thumbnail-size'], $size) . '>' . $size . '</option>' . "\n";
        } ?>
      </select><?php
  }

  //管理画面用CSS,JSの読み込み
  public function add_admin_css_js(){
    // wp_enqueue_script('jquery-validate', plugins_url('js/jquery.validate.min.js', __FILE__), array('jquery'), '1.16.0', false);
    wp_enqueue_script('wpll-js', plugins_url('assets/js/wp-link-list.js', __FILE__), array('jquery'), self::VERSION, false);
    wp_enqueue_style('wpll-css-admin', plugins_url('assets/css/admin.css', __FILE__), '', self::VERSION);
  }

  //リストのCSSの読み込み
  public function add_css(){
    //設定値を取得
    $this->options =  get_option('wpll-setting');

    if(!$this->options['nocss']){
      wp_enqueue_style('wpll-css', plugins_url('assets/css/wp-link-list.css', __FILE__), '', self::VERSION);
    }

    if($this->options['balloon']){
      wp_enqueue_style('wpll-css-balloon', plugins_url('assets/css/balloon.css', __FILE__), '', self::VERSION);
    }
  }

  //カスタム投稿タイプの追加
  public function create_post_type(){

    $labels = array(
    'name' => __('Links', 'wp-link-list'),
    'singluar_name' => __('Link', 'wp-link-list'),
    'add_new' => __('Add New', 'wp-link-list'),
    'add_new_item' => __('Add new Link', 'wp-link-list'),
    'edit_item' => __('Edit Link', 'wp-link-list'),
    'new_item' => __('New Link', 'wp-link-list'),
    'all_items' => __('All Links', 'wp-link-list'),
    'view_item' => __('View Link', 'wp-link-list'),
    'search_item' => __('Search Link', 'wp-link-list'),
    'not_found' => __('Link not found', 'wp-link-list'),
    'not_found_in_trash' => __('Link not found in Trash', 'wp-link-list'),
    'parent_item_colon' => '',
    'menu_name' => __('Links', 'wp-link-list')
    );

    $supports = array(
    'title',
    'thumbnail',
    'page-attributes'
    );

    register_post_type('links', array(
    'labels' => $labels,
    'public' => true,
    'has_archive' => false,
    'exclude_from_search' => false,
    'publicly_queryable' => false,
    'show_ui' => true,
    'show_in_nav_menus' => false,
    'menu_icon' => 'dashicons-admin-links',
    'menu_position' => 5,
    'hierarchical' => false,
    'supports' => $supports
    ));
  }

  //カスタムフィールドの作成
  public function add_custom_field(){
    add_meta_box('wpll', __('Settings of Link', 'wp-link-list'), array(&$this, 'insert_link_field'), 'links', 'normal', 'high');
  }

  //カスタムフィールドのHTML
  public function insert_link_field(){
    global $post;
    wp_nonce_field(wp_create_nonce(__FILE__), 'wpll_nonce');

    $html = '<div class="wpll-field">';
    $html .= '<p>';
    $html .= '<label for="wpll-url">';
    $html .= '<span><strong>URL</strong></span>';
    $html .= '<input type="text" name="wpll-url" value="' . get_post_meta($post->ID, 'wpll-url', true) . '">';
    $html .= '</p>';
    $html .= '<p>';
    $html .= '<label for="wpll-description">';
    $html .= '<span><strong>説明文</span>';
    $html .= '<textarea name="wpll-description" rows="3">';
    $html .= get_post_meta($post->ID, 'wpll-description', true);
    $html .= '</textarea>';
    $html .= '</p>';
    $html .= '</div>';


    echo $html;
  }

  //カスタムフィールドの保存
  public function save_custom_field($post_id){
    global $post;
    $my_nonce = isset($_POST['wpll_nonce']) ? $_POST['wpll_nonce']: null; //設定したnonce を取得

    //nonceを取得し､値が書き換えられていれば何もしない
    if(!wp_verify_nonce($my_nonce, wp_create_nonce(__FILE__))){
    return $post_id;
    }

    //自動保存ルーチンかどうかチェック｡そうだった場合は何もしない｡（記事の自動保存処理として呼び出された場合の対策
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
    return $post_id;
    }

    //ユーザーが編集権限を持っていない場合は何もしない
    if(!current_user_can('edit_post', $post->ID)){
    return $post_id;
    }

    //'lists'投稿タイプの場合のみ実行
    if($_POST['post_type'] == 'links'){
    update_post_meta($post->ID, 'wpll-url', $_POST['wpll-url']);
    update_post_meta($post->ID, 'wpll-description', $_POST['wpll-description']);
    }
  }

  //ショートコードの定義
  public function link_list_shortcode(){
    extract(shortcode_atts(array(
    ), $atts));

    $args = array(
    	'posts_per_page'   => -1,
    	'offset'       => 0,
    	'category'     => '',
    	'orderby'      => 'post_date',
    	'order'      => 'DESC',
    	'include'      => '',
    	'exclude'      => '',
    	'meta_key'     => '',
    	'meta_value'     => '',
    	'post_type'    => 'links',
    	'post_mime_type'   => '',
    	'post_parent'    => '',
    	'post_status'    => 'publish',
    	'suppress_filters' => true
    );

    $options = get_option( 'wpll-setting' );

    //オプションでorderby（リンクの並び順の基準）が設定されていれば優先する
    if($options['orderby']){
      $args['orderby'] = $options['orderby'];
    }

    //オプションでorder（リンクの並び順）が設定されていれば優先する
    if($options['order']){
      $args['order'] = $options['order'];
    }

    //オプションでtarget（ターゲット属性の値）が設定されている場合は使用する
    $target = $options['target'] ? ' target=' . $options['target'] : '';

    //オプションでthumbnail_sizeが設定されている場合は使用する
    $thumbnail_size = $options['thumbnail-size'] ? $options['thumbnail-size'] : 'thumbnail';

    $myposts = get_posts( $args );
    global $post;

    if($myposts){

      if($options['display'] == 'thumbnail'){

        $html = '<div class="wpll wpll--thumbnail">' . "\n";

        foreach($myposts as $post): setup_postdata($post);

          $title = get_the_title();
          $url = get_post_meta($post->ID, 'wpll-url', true);
          $description = get_post_meta($post->ID, 'wpll-description', true);

          $html .= '<div class="wpll__item">' . "\n";
          $html .= '<div class="wpll__img">' . "\n";
          $html .= '<a href="' . $url . '" title="' . $description . '"' . $target . '>';
          $html .= get_the_post_thumbnail($post->ID, $thumbnail_size);
          $html .= '</a>' . "\n";
          $html .= '</div>' . "\n";
          $html .= '<div class="wpll__title">' . "\n";

          if($options['balloon']){
            $html .= '<a href="' . $url . '" data-balloon="' . $description . '" data-balloon-pos="' . $options['balloon']  . '" data-balloon-length="fit"' . $target . '>' . $title . '</a>' . "\n";
          } else {
            $html .= '<a href="' . $url . '" title="' . $description . '"' . $target . '>' . $title . '</a>' . "\n";
          }

          $html .= '</div>' . "\n";
          $html .= '</div>' . "\n";
        endforeach;

        $html .= '</div>' . "\n";

      } elseif ($options['display'] == 'description'){

        $html = '<div class="wpll wpll--description">' . "\n";

        foreach($myposts as $post): setup_postdata($post);

          $title = get_the_title();
          $url = get_post_meta($post->ID, 'wpll-url', true);
          $description = get_post_meta($post->ID, 'wpll-description', true);

          $html .= '<div class="wpll__item">' . "\n";
          $html .= '<div class="wpll__title">' . "\n";
          $html .= '<a href="' . $url . '"' . $target . '>' . $title . '</a>' . "\n";
          $html .= '</div>' . "\n";
          $html .= '<div class="wpll__description">' . $description . '</div>' . "\n";
          $html .= '</div>' . "\n";
        endforeach;

        $html .= '</div>' . "\n";

      } elseif ($options['display'] == 'all'){

        $html = '<div class="wpll wpll--all">' . "\n";

        foreach($myposts as $post): setup_postdata($post);

          $title = get_the_title();
          $url = get_post_meta($post->ID, 'wpll-url', true);
          $description = get_post_meta($post->ID, 'wpll-description', true);

          $html .= '<div class="wpll__item">' . "\n";
          $html .= '<div class="wpll__img">' . "\n";
          $html .= '<a href="' . $url . '"' . $target . '>';
          $html .= get_the_post_thumbnail($post->ID, $thumbnail_size);
          $html .= '</a>' . "\n";
          $html .= '</div>' . "\n";
          $html .= '<div class="wpll__content">' . "\n";
          $html .= '<div class="wpll__title">' . "\n";
          $html .= '<a href="' . $url . '"' . $target . '>' . $title . '</a>' . "\n";
          $html .= '</div>' . "\n";
          $html .= '<div class="wpll__description">' . $description . '</div>' . "\n";
          $html .= '</div>' . "\n";
          $html .= '</div>' . "\n";
        endforeach;

        $html .= '</div>' . "\n";

      } else {

        $html = '<ul class="wpll">' . "\n";

        foreach($myposts as $post): setup_postdata($post);

          $title = get_the_title();
          $url = get_post_meta($post->ID, 'wpll-url', true);
          $description = get_post_meta($post->ID, 'wpll-description', true);

          $html .= '<li class="wpll__title">' . "\n";

          if($options['balloon']){
            $html .= '<a href="' . $url . '" data-balloon="' . $description . '" data-balloon-pos="' . $options['balloon']  . '" data-balloon-length="fit"' . $target . '>' . $title . '</a>' . "\n";
          } else {
            $html .= '<a href="' . $url . '" title="' . $description . '"' . $target . '>' . $title . '</a>' . "\n";
          }

          $html .= '</li>' . "\n";
        endforeach;

        $html .= '</ul>' . "\n";
      }
      return $html;
    }
    wp_reset_postdata();
  }
}

$wpll = new Wp_link_list();
