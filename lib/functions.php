<?php

function alphabetic_is_admin() {
    //Ajax request are always identified as administrative interface page
    //so let's check if we are calling the data for the frontend or backend
    if (wp_doing_ajax()) {
        $admin_url = get_admin_url();
        //If the referer is an admin url we are requesting the data for the backend
        return (substr($_SERVER['HTTP_REFERER'], 0, strlen($admin_url)) === $admin_url);
    }

    //No ajax request just use the normal function
    return is_admin();
}

function alphabetic_get_charset() {
  return range('a', 'z');
}

function alphabetic_get_post_type_options() {
  $args = array(
   'public'   => true,
   '_builtin' => false
  );

  $output = 'names'; // 'names' or 'objects' (default: 'names')
  $operator = 'and'; // 'and' or 'or' (default: 'and')

  $post_types = get_post_types( $args, $output, $operator );

  $post_type_options = get_option('post_types');

  $options = array();

  foreach ($post_types as $post_type) {
    $type_options = array();

    if (isset($post_type_options[$post_type])) {
      $type_options = $post_type_options[$post_type];
    }

    $options[$post_type] = array_merge(array(
      'enabled' => 0,
      'taxonomy' => $post_type . '-dictionary',
    ), $type_options);
  }

  return $options;
}

function alphabetic_is_enabled($post_type = null) {
  global $post;

  if (!$post_type) {
    $post_type = get_post_type($post);
  }

  if (!$post_type || !is_string($post_type)) {
    return false;
  }

  $post_type_options = alphabetic_get_post_type_options();

  if (isset($post_type_options[$post_type])) {
    if ($post_type_options[$post_type]['enabled']) {
      return true;
    }
  }

  return false;
}

function alphabetic_get_taxonomy($post_type = null) {
  global $post;

  if (!$post_type) {
    $post_type = get_post_type($post);
  }

  $post_type_options = alphabetic_get_post_type_options();

  if (isset($post_type_options[$post_type]) && $post_type_options[$post_type]['enabled']) {
    return $post_type_options[$post_type]['taxonomy'];
  }

  return null;
}

function alphabetic_get_options($post_type = null) {
  global $post;

  if (!$post_type) {
    $post_type = get_post_type($post);
  }

  $post_type_options = alphabetic_get_post_type_options();

  return isset($post_type_options[$post_type]) && $post_type_options[$post_type];
}

// WPML
function alphabetic_get_the_posts_pagination($args = array()) {
  global $wp_query;

  $post_type = $wp_query->get('post_type') ?: 'post';
  $post_type_options = alphabetic_get_post_type_options();

  if (!$post_type_options[$post_type]['enabled']) {
    return get_the_posts_pagination($args);
  }

  $options = $post_type_options[$post_type];
  $taxonomy = $post_type_options[$post_type]['taxonomy'];

  $taxonomies = get_taxonomies();

  //print_r($taxonomies);

  $terms = get_terms($taxonomy);

  $alphabet = array();

  if ($terms){
    foreach ($terms as $term){
      $alphabet[] = $term->slug;
    }
  }

  $charset = alphabetic_get_charset();
?>

  <div class="navigation pagination">
    <div class="nav-links">
      <?php
        foreach($charset as $i) :
          $is_current = ($i == get_query_var($taxonomy));
          $has_entries = in_array( $i, $alphabet );

          $classes = array(
            'page-numbers'
          );

          if ($is_current) {
            $classes[] = 'current';
          }
          $class = implode(' ', $classes);

          if (!$is_current && $has_entries) {
            $link = esc_url( apply_filters( 'alphabetic_paginate_links', get_term_link( $i, $taxonomy ), $i ) );
            printf( '<a class="%s" href="%s">%s</a>', $class, $link, strtoupper($i) );
          } else {
            printf( '<span class="%s">%s</span>', $class, strtoupper($i) );
          }

        endforeach;

      ?>
    </div>
  </div>
  <?php
}


function alphabetic_the_posts_pagination($args = array()) {
  echo alphabetic_get_the_posts_pagination($args);
}
