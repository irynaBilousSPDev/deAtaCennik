<?php /**
 * The template for displaying the footer
 *
 * @package  akademiata
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>

</main><!-- #main -->
</div><!-- #primary -->
</div><!-- #content -->
<?php locate_template('partials/footer.php', true, true); ?>

<?php wp_footer(); ?>

</body>
</html>