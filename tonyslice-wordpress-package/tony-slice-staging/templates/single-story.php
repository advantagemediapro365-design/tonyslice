<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('tony-slice-staging-story'); ?>>
    <?php wp_body_open(); ?>
    <div class="tony-slice-staging-page" data-home-url="<?php echo esc_url(home_url('/tony-slice-preview/')); ?>">
        <div id="page-root"></div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
