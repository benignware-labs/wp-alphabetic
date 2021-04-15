<?php

/**
 Plugin Name: Alphabetic
 Plugin URI: http://github.com/benignware/wp-alphabetic
 Description: Navigate posts alphabetically
 Version: 0.0.11
 Author: Rafael Nowrotek, Benignware
 Author URI: http://benignware.com
 License: MIT
*/

define('WP_ALPHABETIC_PAGINATED_LINK', '#wp-alphabetic-paginated-link');

require_once 'lib/functions.php';
require_once 'lib/settings.php';

add_action( 'registered_post_type', function($post_type) {
  $post_type_options = alphabetic_get_post_type_options();
  $type_options = $post_type_options[$post_type] ?: array();

  $taxonomy = $type_options['taxonomy'];

  if ($type_options['enabled']) {
    $post_type_object = get_post_type_object($post_type);
    $post_type_label = $post_type_object->label;

    register_taxonomy($taxonomy, array($post_type), array(
      'show_ui' => false,
    ));
  }
}, 10, 2);

add_filter( 'get_the_archive_title', function( $title ) {
  if (get_post_type() === 'glossary_entry') {
    $title = 'Glossary';
  }

  return $title;
} );

// Order
add_action( 'pre_get_posts', function( $query ) {
  $post_type = $query->get('post_type');

  if ($post_type) {
    if ( alphabetic_is_enabled($post_type) ) {
      if (is_archive()) {
        $query->set( 'posts_per_page', -1 );
        $query->set( 'max_num_pages', -1 );
        $query->set( 'numberposts', -1 );
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
     }
   }
 }

  return $query;
}, 1);

// Single Post Navigation
add_filter('get_previous_post_where', function($where) {
  global $post;

  $post_type = $post->post_type;

  if ( alphabetic_is_enabled($post_type) ) {
    $post_title = $post->post_title;
    $where = "WHERE p.post_title < '$post_title' AND p.post_type = '$post_type' AND ( p.post_status = 'publish' )";

    // Account for WPML
    if (defined('ICL_LANGUAGE_CODE')) {
      $where.= " AND language_code = '" . ICL_LANGUAGE_CODE . "'";
    }
  }
  return $where;
});

add_filter('get_next_post_where', function($where) {
  global $post;

  $post_type = $post->post_type;

  if ( alphabetic_is_enabled($post_type) ) {
    $post_title = $post->post_title;
    $where = "WHERE p.post_title > '$post_title' AND p.post_type = '$post_type' AND ( p.post_status = 'publish' )";

    // Account for WPML
    if (defined('ICL_LANGUAGE_CODE')) {
      $where.= " AND language_code = '" . ICL_LANGUAGE_CODE . "'";
    }
  }

  return $where;
});


add_filter('get_previous_post_sort', function($sort) {
  global $post;

  $post_type = $post->post_type;

  if ( alphabetic_is_enabled($post_type) ) {
    $sort = " ORDER BY p.post_title DESC";
  }

  return $sort;
});


add_filter('get_next_post_sort', function($sort) {
  global $post;

  $post_type = $post->post_type;

  if ( alphabetic_is_enabled($post_type) ) {
    $sort = " ORDER BY p.post_title ASC";
  }

  return $sort;
});

// Pagination
add_action( 'save_post', function( $post_id ) {
  // Verify if this is an autosave routine.
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      return $post_id;

  $post_type = get_post_type($post_id);

  $post_type_options = alphabetic_get_post_type_options();
  $type_options = $post_type_options[$post_type] ?: array();

  // Only run for enabled post types
  if (!$type_options['enabled']) {
    return $post_id;
  }

  // Check permissions
  if ( !current_user_can( 'edit_post', $post_id ) )
      return $post_id;

  // OK, we're authenticated: we need to find and save the data
  $taxonomy = $type_options['taxonomy'];

  // Set term as first letter of post title, lower case
  wp_set_post_terms( $post_id, strtolower(substr($_POST['post_title'], 0, 1)), $taxonomy );

  // Delete the transient that is storing the alphabet letters
  $transient = 'alphabetic_' . $post_type . '_archive';

  delete_transient( $transient );
} );

add_action('init', function() {
  $post_type_options = alphabetic_get_post_type_options();

  foreach($post_type_options as $post_type => $options) {
    if ($options['enabled']) {
      $alphabet = array();

      $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => $post_type
      ) );

      $taxonomy = $options['taxonomy'];

      $p = null;

      foreach( $posts as $p ) :
      // Set term as first letter of post title, lower case

      $term_name = strtolower(substr($p->post_title, 0, 1));
      wp_set_post_terms( $p->ID, $term_name, $taxonomy );
      endforeach;
    }
  }
}, 100);
