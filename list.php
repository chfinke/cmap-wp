<?php
    require_once( explode( "wp-content" , __FILE__ )[0] . "wp-load.php" );
    require_once( "./helpers.php" );
    get_header();
?>

<style>
    .plain { margin: 0; padding: 0; color: black; }
    .old { margin: 0; padding: 0; color: red; }
    .new { margin: 0; padding: 0; color: green; }
</style>

<div style="padding-left: 1em; padding-right: 1em;">

<?php
    if (user_is_moderator()) {
?>
    <div class="entry-content">
        <h2 class="page-title">Übersicht</h2>

<?php
        $sql = "
            SELECT ms.meta_value AS status, ma.meta_value AS active, count(*) AS cnt
                FROM $wpdb->posts p
                LEFT OUTER JOIN $wpdb->postmeta ms
                  ON    ms.post_id = p.ID
                    AND ms.meta_key='status'
                LEFT OUTER JOIN $wpdb->postmeta mp
                  ON    mp.post_id = p.ID
                    AND mp.meta_key='id_publish'
                LEFT OUTER JOIN $wpdb->postmeta ma
                  ON    ma.post_id = mp.meta_value
                    AND ma.meta_key='status'
                WHERE p.post_type='cmap'
                  AND p.post_status='draft'
                  AND ms.meta_value in ( 'unconfirmed', 'pending', 'publish' )
                GROUP BY ms.meta_value, ma.meta_value
            ";
        $lines = $wpdb->get_results( $sql );
?>
        <table>
<?php
        foreach ( $lines as $line ) {
?>
          <tr>
            <td align="right"><?php echo $line->cnt; ?></td>
            <td>
<?php
          switch ($line->status) {
            case 'unconfirmed':
                echo 'E-Mail nicht bestätigt';
                break;
            case 'pending':
                echo 'wartet auf Freigabe';
                break;
            case 'publish':
                echo 'veröffentlicht';
                break;
          }
          if ($line->status!= 'unconfirmed' && $line->active != 'active') {
            echo " inaktiv";
          }
?>
            </td>
          </tr>
<?php
        }
?>
        </table>

        <h2 class="page-title">offene Karteneinträge</h2>

<?php
        $sql = "
            SELECT *, ms.meta_value AS status, ma.meta_value AS active
                FROM $wpdb->posts p
                LEFT OUTER JOIN $wpdb->postmeta ms
                  ON    ms.post_id = p.ID
                    AND ms.meta_key='status'
                LEFT OUTER JOIN $wpdb->postmeta mp
                  ON    mp.post_id = p.ID
                    AND mp.meta_key='id_publish'
                LEFT OUTER JOIN $wpdb->postmeta ma
                  ON    ma.post_id = mp.meta_value
                    AND ma.meta_key='status'
                WHERE p.post_type='cmap'
                  AND p.post_status='draft'
                  AND ms.meta_value in ( 'unconfirmed', 'pending', 'publish' )
            ";
        $posts = $wpdb->get_results( $sql );

        foreach ( $posts as $post ) {
            $metas = $wpdb->get_results( "
                SELECT *
                    FROM $wpdb->postmeta
                    WHERE $wpdb->postmeta.post_id = $post->ID
                " );
            $meta_dict = get_meta_dict( $metas );

            if ($meta_dict["status"] == 'publish') {
                continue;
            }
?>
        <article class="post type-post status-publish format-standard hentry">
            <header class="entry-header">
                <h3 class="entry-title"><?php echo $post->post_title; ?></h3>
            </header>
            <div class="entry-content">
                <?php echo get_delta($post->ID, $wpdb, "\n"); ?>
                <p>
                    <a href="<?php echo get_bloginfo('wpurl')."/cmap/edit?id=".$post->ID; ?>">bearbeiten</a>
<?php
            if ($meta_dict["status"] == 'pending') {
?>
                    &nbsp;&nbsp;•&nbsp;&nbsp;
                    <a href="<?php echo get_bloginfo('wpurl')."/cmap/functions?action=publish&id=".$post->ID; ?>">bestätigen</a>
<?php
            }
?>
                </p>
            </div>
        </article>
<?php
        }
?>
        <br/>
        <input type="button" value="Zurück" onclick="window.location.href='<?php echo get_bloginfo('wpurl'); ?>/cmap'"/>
    </div>

<?php
    }
?>

</div>
<?php
    get_footer();
?>
