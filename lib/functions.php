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

function _alphabetic_navigation_markup( $links, $class = 'posts-navigation', $screen_reader_text = '', $aria_label = '' ) {
  if ( empty( $screen_reader_text ) ) {
      $screen_reader_text = __( 'Posts navigation' );
  }
  if ( empty( $aria_label ) ) {
      $aria_label = $screen_reader_text;
  }

  $template = '
  <nav class="navigation %1$s" role="navigation" aria-label="%4$s">
      <h2 class="screen-reader-text">%2$s</h2>
      <div class="nav-links">%3$s</div>
  </nav>';

  $template = apply_filters( 'navigation_markup_template', $template, $class );

  return sprintf( $template, sanitize_html_class( $class ), esc_html( $screen_reader_text ), $links, esc_html( $aria_label ) );
}

function alphabetic_get_the_posts_pagination($args = array()) {
  global $wp_query;

  $post_type = $wp_query->get('post_type') ?: 'post';
  $post_type_obj = get_post_type_object($post_type);
  $post_type_label_name = $post_type_obj->labels->name;

  $post_type_options = alphabetic_get_post_type_options();

  if (!$post_type_options[$post_type]['enabled']) {
    return get_the_posts_pagination($args);
  }

  $options = $post_type_options[$post_type];
  $taxonomy = $post_type_options[$post_type]['taxonomy'];

  $alphabetic_taxonomy = alphabetic_get_taxonomy($post_type);
  $object_taxonomies = get_object_taxonomies($post_type);
  $object_taxonomies = array_filter($object_taxonomies, function($taxonomy) use ($alphabetic_taxonomy) {
    return $taxonomy !== $alphabetic_taxonomy;
  });

  $taxonomy_filter_values = array();

  foreach ($object_taxonomies as $object_taxonomy) {
    $object_taxonomy_value = get_query_var($object_taxonomy);

    if ($object_taxonomy_value) {
      $taxonomy_filter_values = array_merge($taxonomy_filter_values, explode(',', $object_taxonomy_value));
    }
  }

  $terms = get_terms($taxonomy);

  $alphabet = array();

  if ($terms) {
    foreach ($terms as $term){
      $alphabet[] = $term->slug;
    }
  }

  $current_value = get_query_var($taxonomy);
  $charset = alphabetic_get_charset();
  $posts = $wp_query->posts ?: array();

  // Make sure the nav element has an aria-label attribute: fallback to the screen reader text.
  if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
    $args['aria_label'] = $args['screen_reader_text'];
  }

  $args = wp_parse_args(
    $args,
    array(
      'screen_reader_text' => $post_type_label_name,
      'aria_label'         => $post_type_label_name,
      'class'              => 'pagination',
    )
  );

  $links = implode(
    '',
    array_map(function($char) use ($posts, $current_value, $alphabet) {
      $is_current = ($char == $current_value);
      $has_entries = in_array( $char, $alphabet );
      $has_entries = $has_entries && count(array_filter($posts, function($post) use ($char) {
        $title = strtolower(get_the_title($post));
        return $title && ($title[0] === $char);
      })) > 0;

      $classes = array(
        'page-numbers'
      );

      if ($is_current) {
        $classes[] = 'current';
      }
      $class = implode(' ', $classes);

      if (!$is_current && $has_entries) {
        $link = apply_filters( 'paginate_links', get_term_link( $char, $taxonomy ), $char );
        $link = esc_url( $link );
        return sprintf( '<a class="%s" href="%s">%s</a>', $class, $link, strtoupper($char) );
      } else {
        return sprintf( '<a class="%s">%s</as>', $class, strtoupper($char) );
      }
    }, $charset)
  );

  $links = apply_filters( 'paginate_links_output', $links);
  $navigation_markup = _alphabetic_navigation_markup($links, $args['class'], $args['screen_reader_text'], $args['aria_label']);

  return $navigation_markup;
}


function alphabetic_the_posts_pagination($args = array()) {
  echo alphabetic_get_the_posts_pagination($args);
}

add_filter( 'paginate_links', function($link = '', $term = null) {
  if (!$term) {
    return $link;
  }

  $post_type = get_post_type();
  $post_id = get_the_ID();

  $sector = get_queried_object()->name;

  if (alphabetic_is_enabled($post_type)) {
    $taxonomy = alphabetic_get_taxonomy($post_type);

    $url = parse_url($link);
    $id = $term;

    $link = get_post_type_archive_link($post_type);
    $link = $link . '#' . $id;

    return $link;
  }

  return $link;
}, 2, 10);