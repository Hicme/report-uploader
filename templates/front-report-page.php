<?php get_header(); ?>

<div class="row">
  <div class="page-heading span12 clearfix alt-bg>">
    <h1><?php the_title(); ?></h1>
  </div>
</div>

<div class="inner-page-wrap clearfix">
  <div class="page-content clearfix">
    <div class="front-table__wrapper">
      <table>
        <thead>
          <tr>
            <th>
              Platform
            </th>
            <th>
              ISA Name
            </th>
            <th>
              ISA ID
            </th>
            <th>
              Date
            </th>
            <th>
              Download
            </th>
          </tr>
        </thead>
        <tbody>
          <?php do_action( 'print_front_table' ); ?>
        </tbody>
      </table>
    </div>
    <div class="front-pagination__wrapper">
      <?php do_action( 'print_front_table_pagination' ); ?>
    </div>
    <div class="link-pages"><?php wp_link_pages(); ?></div>
  </div>
</div>

<?php get_footer();