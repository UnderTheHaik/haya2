<?php get_header(); ?><main class="content-wrap"><?php while (have_posts()) { the_post(); ?><h1><?php the_title(); ?></h1><?php the_content(); } ?></main><?php get_footer(); ?>
