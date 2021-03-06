<?php
    require_once( explode( "wp-content" , __FILE__ )[0] . "wp-load.php" );
    require_once( "./helpers.php" );

    $action = $_GET['action'];

    $plain = isset($_GET['plain']);

    if (!$plain) {
        get_header();
?>
<div style="padding-left: 1em; padding-right: 1em;">
<?php
    }
    $action = $_GET['action'];

    if ( $action == 'publish') {
        // functions?action=publish&id=123
?>
    <h2 class="page-title">Eintrag bestätigen</h2>
<?php
        $id = $_GET["id"];

        if ( !user_is_moderator() ) {
?>
    <p>Zugangsdaten falsch oder abgelaufen</p>
<?php
        } elseif ( get_post_meta( $id, 'status', true) != "pending" ) {

?>
    <p>Bereits bestätigt</p>

    <input type="button" value="Zurück" onclick="document.location.href='<?php echo get_bloginfo('wpurl'); ?>/cmap/';"/>
<?php
        } else {
            publish($id);
?>
    <p>Erfolgreich bestätigt</p>

    <input type="button" value="Zurück" onclick="document.location.href='<?php echo get_bloginfo('wpurl'); ?>/cmap/';"/>

<?php
        }

    } elseif ( $action == 'save') {
        // functions?action=save

        $id = $_POST["id"];
        if ( $id == -1 ) {
            $status = 'create';
        } else {
            $status = 'edit';
        }
        $token = $_POST["token"];

        if ( !((isset($status)&&$status == 'create')) && !user_is_moderator() && !check_token($id, $token) ) {
?>
    <p>Zugangsdaten falsch oder abgelaufen</p>
<?php
        } else {
            if ( $status == 'create' ) {
                $id = wp_insert_post(
                    array(
                        'comment_status' => 'closed',
                        'ping_status'    => 'closed',
                        'post_author'    => wp_get_current_user(),
                        'post_name'         => '',
                        'post_title'     => $_POST["title"],
                        'post_status'    => 'draft',
                        'post_type'      => 'cmap',
                    )
                );
            } else {
                $post = get_post( $id );
                $post->post_title = $_POST["title"];
                wp_insert_post( $post );
            }

            update_post_meta( $id, 'address', $_POST["address"] );
            update_post_meta( $id, 'lat', $_POST["lat"] );
            update_post_meta( $id, 'lon', $_POST["lon"] );

            if (!user_is_moderator()) {
                update_post_meta( $id, 'status', "pending" );
            }

            if ( $status == 'create' ) {
                update_post_meta( $id, 'email', $_POST["email"] );
                update_post_meta( $id, 'status', "unconfirmed" );
                $token = set_token($id);
                $email = $_POST["email"];
                $subject = 'Bestätige deinen Karteneintrag auf '.get_bloginfo('name');
                $body = 'Hallo,<br/>du hast einen neuen Karteneinträge auf '.get_bloginfo('name').' gemacht.<br/>
                Zur Bestätigung klicke bitte auf diesen Link:<br/>
                <a href="'.get_bloginfo('wpurl').'/cmap/functions?action=confirm&id='.$id.'&token='.$token.'">'.get_bloginfo('wpurl').'/cmap/confirm?id='.$id.'&token='.$token.'</a>';
                $headers = array('Content-Type: text/html; charset=UTF-8');

                wp_mail( $email, $subject, $body, $headers );
?>
    <p>Bestätigungs-E-Mail wurde verschickt</p>
    <input type="button" value="Zurück" onclick="window.history.go(-2);"/>
<?php
            } else {
                if (user_is_moderator()) {
                    if ( $_POST["confirm"] == "1" ) {
                        update_post_meta( $id, 'status', "pending" );
                    }
                    if ( $_POST["publish"] == "1" ) {
                        update_post_meta( $id, 'status', "publish" );
                    }
                    if ( get_post_meta( $id, 'status', true) == "publish" ) {
                        publish($id);
                    }
                }
?>
    <h2 class="page-title">Karteneintrag bearbeiten</h2>
    <p>Eintrag geändert</p>
    <input type="button" value="anzeigen" onclick="window.location.href='<?php echo get_bloginfo('wpurl'); ?>/cmap/'"/>
    <input type="button" value="Zurück" onclick="window.history.go(-1);"/>
<?php
            }
        }

    } elseif ( $action == 'confirm') {
        // functions?action=confirm
?>
    <h2 class="page-title">Neuer Karteneintrag</h2>
<?php
        $id = $_GET["id"];
        $token = $_GET["token"];

        if ( !check_token($id, $token) ) {
?>
        <p>Zugangsdaten falsch oder abgelaufen</p>
<?php
            echo "Zugangsdaten falsch oder abgelaufen";
        } elseif ( get_post_meta( $id, 'status', true) != "unconfirmed" ) {
?>
        <p>Eintrag bereits bestätigt</p>

        <input type="button" value="bearbeiten" onclick="window.location.href='<?php echo get_bloginfo('wpurl'); ?>/cmap/edit?id=<?php echo $id; ?>&token=<?php echo $token; ?>'"/>
<?php
        } else {
            update_post_meta( $id, 'status', "pending" );
?>
        <p>Erfolgreich bestätigt</p>

        <input type="button" value="bearbeiten" onclick="window.location.href='<?php echo get_bloginfo('wpurl'); ?>/cmap/edit?id=<?php echo $id; ?>&token=<?php echo $token; ?>'"/>
<?php
        }

    } elseif ( $action == 'delete') {
        // functions?action=delete
?>
    <h2 class="page-title">Karteneintrag löschen</h2>
<?php
        $id = $_GET['id'];
        $token = $_GET["token"];

        if ( !user_is_moderator() && !check_token($id, $token) ) {
?>
        <p>Zugangsdaten falsch oder abgelaufen</p>
<?php
        } else {
            if (isset($_GET['confirmed'])) {
                wp_delete_post( get_post_meta($id,"id_publish",true), true );
                wp_delete_post( $id, true );
?>
    <p>Eintrag gelöscht</p>
<?php
            } else {
?>
    <p>Eintrag wirklich löschen?</p>

    <input type="button" value="Löschen" onclick="window.location.href='<?php echo get_bloginfo('wpurl'); ?>/cmap/functions?action=delete&id=<?php echo $_GET['id']; ?>&confirmed&token=<?php echo $_GET['token']; ?>'"/>
    <input type="button" value="Zurück" onclick="window.history.go(-1);"/>
<?php
            }
        }
    }

    if (!$plain) {
?>
</div>
<?php
        get_footer();
    }
?>
