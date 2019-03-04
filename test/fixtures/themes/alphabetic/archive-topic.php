<?php
/**
 * The template for displaying archive pages
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

<div class="wrap">

	<header class="page-header">
		<?php
			the_archive_title( '<h1 class="page-title display-4">', '</h1>' );
			the_archive_description( '<div class="taxonomy-description">', '</div>' );
		?>
	</header><!-- .page-header -->

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
			$taxonomy = alphabetic_get_taxonomy('topic');
			$charset = alphabetic_get_charset();
			$terms = get_terms($taxonomy);

			$alphabet = array();

		  if ($terms){
		    foreach ($terms as $term){
		      $alphabet[] = $term->slug;
		    }
		  }

			foreach($alphabet as $i) :
				$is_current = ($i == get_query_var($taxonomy));
				$has_entries = in_array( $i, $alphabet );

				$classes = array(
					''
				);

				if ($is_current) {
					$classes[] = 'current';
				}
				$class = implode(' ', $classes);
				$id = 'glossary-term-' . $i;

				$before = '<section class="glossary-section" id="' . $id . '">';
				$after = '</section>';

				echo $before;

				if (!$is_current && $has_entries) {
					printf( '<h1><a class="%s" href="%s">%s</a></h1>', $class, get_term_link( $i, $taxonomy ), strtoupper($i) );
				} else {
					printf( '<h1><span class="%s">%s</span></h1>', $class, strtoupper($i) );
				}

				echo $after;

				$args = array (
					'post_type' => 'topic',
					'tax_query' => array(
		        array (
	            'taxonomy' => $taxonomy,
	            'field' => 'slug',
	            'terms' => $i,
		        )
			    ),
				);
				query_posts( $args );

				if ( have_posts() ): ?>
					<div class="glossary-term-links">
						<?php while ( have_posts() ) : ?>
							<div>
								<?php
						        the_post();
						        // Do stuff with the post content.
										printf( '<a class="glossary-term-link" href="%s">%s</a>', get_the_permalink(), get_the_title() );
										// get_template_part( 'template-parts/post/content', 'glossary' );
								?>
							</div>


					   <?php endwhile; ?>
					</div>
				<?php else: ?>
					<?php
				    // Insert any content or load a template for no posts found.
					?>
				<?php endif;

				wp_reset_query();


			endforeach;

		?>

		<?php

		if ( have_posts() ) : ?>
			<?php
			alphabetic_the_posts_pagination( array(
				'prev_text' => twentyseventeen_get_svg( array( 'icon' => 'arrow-left' ) ) . '<span class="screen-reader-text">' . __( 'Previous page', 'twentyseventeen' ) . '</span>',
				'next_text' => '<span class="screen-reader-text">' . __( 'Next page', 'twentyseventeen' ) . '</span>' . twentyseventeen_get_svg( array( 'icon' => 'arrow-right' ) ),
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'twentyseventeen' ) . ' </span>',
			) );
		else :

			get_template_part( 'template-parts/post/content', 'none' );

		endif; ?>

		</main><!-- #main -->
	</div><!-- #primary -->
	<?php get_sidebar(); ?>
</div><!-- .wrap -->

<?php get_footer();
