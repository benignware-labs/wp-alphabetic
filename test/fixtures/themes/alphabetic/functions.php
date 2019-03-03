<?php


add_action( 'wp_enqueue_scripts', function() {
  wp_deregister_style( 'twentyseventeen-style');
  wp_register_style('twentyseventeen-style', get_template_directory_uri(). '/style.css');
  wp_enqueue_style('twentyseventeen-style', get_template_directory_uri(). '/style.css');
  wp_enqueue_style( 'childtheme-style', get_stylesheet_directory_uri().'/style.css', array('twentyseventeen-style') );
} );

add_filter( 'alphabetic_paginate_links', function($link = '', $term = null) {
  if (!$term) {
    return $link;
  }

  $post_type = get_post_type();
  $post_id = get_the_ID();

  $sector = get_queried_object()->name;

  if (alphabetic_is_enabled($post_type)) {
    $taxonomy = alphabetic_get_taxonomy($post_type);

    $url = parse_url($link);

    //$term_slug = get_term_by( 'term' );

    echo 's: ' . $taxonomy;

    //var_dump(parse_url($link));
    /*
    $term_query = new WP_Query($args);

    $term = $term_query->get($taxonomy);
    */

    //$terms = get_terms($taxonomy);

    //$term = count($terms) > 0 ? $terms[0] : null;

    //var_dump($term->slug);
    $id = 'glossary-term-' . $term;

    $link = get_post_type_archive_link($post_type);
    $link = $link . '#' . $id;
    //exit;
  }

  return $link;
}, 2, 10);
