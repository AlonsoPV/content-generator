<?php
/*
Plugin Name: AI Content Generator
Description: Genera contenido y posts automáticamente usando AI21 API.
Version: 1.0
Author: eMotion Sites
*/

if (!defined('ABSPATH')) exit;

// Admin settings page for API key
add_action('admin_menu', function() {
    // Página de ajustes para la API Key
    add_options_page(
        'AI Content Generator Settings',
        'AI Content Generator',
        'manage_options',
        'ai-content-generator',
        'aicg_settings_page'
    );
    // Página principal del generador en el admin
    add_menu_page(
        'AI Content Generator',
        'AI Content Generator',
        'manage_options',
        'ai-content-generator-generator',
        'aicg_generator_admin_page',
        'dashicons-edit',
        30
    );
});

function aicg_settings_page() {
    ?>
    <div class="wrap">
        <h1>AI Content Generator Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('aicg_settings');
            do_settings_sections('aicg_settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">AI21 API Key</th>
                    <td>
                        <input type="text" name="aicg_api_key" value="<?php echo esc_attr(get_option('aicg_api_key')); ?>" size="50" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
add_action('admin_init', function() {
    register_setting('aicg_settings', 'aicg_api_key');
});

// Página del generador en el admin
function aicg_generator_admin_page() {
    echo '<div class="wrap">';
    echo '<h1>Generador de Contenido con IA</h1>';
    include plugin_dir_path(__FILE__) . 'assets/formulario.php';
    echo '</div>';
}

// Cargar scripts y estilos solo en la página del generador en el admin
add_action('admin_enqueue_scripts', function($hook) {
    // if ($hook !== 'toplevel_page_ai-content-generator-generator') return;
    wp_enqueue_style('ai-content-generator-style', plugins_url('assets/style.css', __FILE__));
    wp_enqueue_script('ai-content-generator-script', plugins_url('assets/script.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('ai-content-generator-script', 'AICG', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('generador_nonce')
    ));
});

// AJAX: Generar contenido
add_action('wp_ajax_generar_contenido', 'aicg_generar_contenido_ajax');
function aicg_generar_contenido_ajax() {
    check_ajax_referer('generador_nonce', 'nonce');

    $api_key = get_option('aicg_api_key');
    if (empty($api_key)) {
        wp_send_json_error("API key not set. Please configure it in the plugin settings.");
    }

    $palabra_clave = sanitize_text_field($_POST['idea']);
    $sentimiento = sanitize_text_field($_POST['sentimiento']);
    $longitud = intval($_POST['longitud']);
    $idioma = sanitize_text_field($_POST['idioma']);
    $estilo = sanitize_text_field($_POST['estilo']);
    $tipo_contenido = sanitize_text_field($_POST['tipo_contenido']);
    $keywords = isset($_POST['keywords']) ? json_decode(stripslashes($_POST['keywords']), true) : [];
    $keywords_string = '';
    foreach ($keywords as $item) {
        if (isset($item['keyword']) && isset($item['link'])) {
            $keywords_string .= "<a href='{$item['link']}' target='_blank'>{$item['keyword']}</a>, ";
        }
    }
    $keywords_string = rtrim($keywords_string, ', ');

    $prompt = "Escribe contenido sobre '$palabra_clave' que contenga la estructura y este adaptado para la plataforma $tipo_contenido en idioma '$idioma' con un tono '$sentimiento' y estilo '$estilo'. \nIncorpora las siguientes palabras clave: $keywords_string (asegúrate de usarlas todas). Dale la estructura dependiendo de la plataforma elegida. Asegura poner el titulo en las tags de h1 <h1>. Escribe oraciones cortas pero impactantes. No uses frases como 'En conlusión' 'En resumen' o 'para resumir'. Evita ser repetitivo.\nEl contenido debe ser fidedigno, verificable y en formato HTML válido, con la siguiente estructura:\n- Párrafos envueltos en <p>.\n- Enlaces con la etiqueta <a> con href y target='_blank'.\n- No incluyas backticks, etiquetas de código, ni la palabra 'html' antes o después del contenido.\n- Solo devuelve HTML válido y estructurado. Nada más.\nDebe tener aproximadamente y maximo de '$longitud' palabras.";

    $data = array(
        "model" => "jamba-large-1.6",
        "messages" => array(
            array("role" => "system", "content" => $prompt),
            array("role" => "user", "content" => "Genera el artículo con las indicaciones dadas.")
        ),
        "temperature" => 0.9,
        "top_p" => 0.8,
    );

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body' => json_encode($data),
        'timeout' => 60,
    );

    $response = wp_remote_post('https://api.ai21.com/studio/v1/chat/completions', $args);

    if (is_wp_error($response)) {
        wp_send_json_error("Error en la API.");
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($response_body['choices'][0]['message']['content'])) {
        wp_send_json_error("No se pudo generar contenido.");
    }

    $contenido = $response_body['choices'][0]['message']['content'] ?? "";

    preg_match('/<h1>(.*?)<\/h1>/', $contenido, $matches);
    $titulo = !empty($matches[1]) ? $matches[1] : 'Título generado automáticamente';

    $contenido = preg_replace('/<h1>.*?<\/h1>/', '', $contenido);
    $contenido = wp_kses_post($contenido);

    wp_send_json_success(array("title" => $titulo, "content" => trim($contenido)));
}

// AJAX: Publicar post
add_action('wp_ajax_publicar_post', 'aicg_publicar_post_ajax');
function aicg_publicar_post_ajax() {
    check_ajax_referer('generador_nonce', 'nonce');

    if (!isset($_POST['title']) || !isset($_POST['content'])) {
        wp_send_json_error("Faltan datos para publicar el contenido.");
        return;
    }

    $titulo = sanitize_text_field($_POST['title']);
    $contenido = wp_unslash($_POST['content']);

    // Subir imágenes
    $imagenes_urls = [];
    if (!empty($_FILES['imagenes']['name'][0])) {
        $files = $_FILES['imagenes'];
        foreach ($files['name'] as $key => $value) {
            if ($files['name'][$key]) {
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key],
                ];

                $upload = wp_handle_upload($file, ['test_form' => false]);
                if (isset($upload['error'])) continue;

                $attachment = [
                    'post_mime_type' => $upload['type'],
                    'post_title'     => sanitize_file_name($files['name'][$key]),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ];

                $attachment_id = wp_insert_attachment($attachment, $upload['file']);
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);

                $imagenes_urls[] = wp_get_attachment_url($attachment_id);
            }
        }
    }

    // Distribuir imágenes flotantes entre el contenido
    $content_with_images = '';
    foreach ($imagenes_urls as $imagen_index => $imagen_url) {
        if ($imagen_index == 0) {
            $content_with_images .= "<div style='text-align: center; margin-bottom: 10px;'><img src='{$imagen_url}' alt='Imagen' style='width: 100%; max-width: 750px; height: auto;'></div>";
        } else {
            $float = ($imagen_index % 2 == 0) ? 'left' : 'right';
            $content_with_images .= "<span style='overflow: hidden; margin: 10px; display: inline;'><img src='{$imagen_url}' alt='Imagen' style='width: 50%; height: auto; float: {$float}; margin: 15px;'></span>";
        }
    }
    $contenido = $content_with_images . $contenido;

    $nuevo_post = [
        'post_title'   => $titulo,
        'post_content' => $contenido,
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
        'post_type'    => 'post',
    ];

    $post_id = wp_insert_post($nuevo_post);

    if (is_wp_error($post_id)) {
        wp_send_json_error("Error al publicar el post.");
    }
    wp_send_json_success(get_permalink($post_id));
}

// Handler para enviar el contenido como correo electrónico
add_action('wp_ajax_enviar_correo_contenido', function() {
    check_ajax_referer('generador_nonce', 'nonce');
    $to_raw = sanitize_text_field($_POST['to']);
    $subject = sanitize_text_field($_POST['subject']);
    $cc_raw = sanitize_text_field($_POST['cc']);
    $bcc_raw = sanitize_text_field($_POST['bcc']);
    $content = wp_kses_post($_POST['content']);
    $title = sanitize_text_field($_POST['title']);

    // Procesar múltiples correos separados por coma
    $to = array_filter(array_map('trim', explode(',', $to_raw)));
    $cc = array_filter(array_map('trim', explode(',', $cc_raw)));
    $bcc = array_filter(array_map('trim', explode(',', $bcc_raw)));

    // Validar que todos los correos sean válidos
    foreach ($to as $email) {
        if (!is_email($email)) {
            wp_send_json_error("Correo(s) en 'Para' no válido(s): $email");
        }
    }
    foreach ($cc as $email) {
        if ($email && !is_email($email)) {
            wp_send_json_error("Correo(s) en 'Con copia a' no válido(s): $email");
        }
    }
    foreach ($bcc as $email) {
        if ($email && !is_email($email)) {
            wp_send_json_error("Correo(s) en 'Con copia oculta a' no válido(s): $email");
        }
    }

    if (empty($to) || empty($subject) || empty($content)) {
        wp_send_json_error("Faltan campos obligatorios.");
    }

    $headers = array('Content-Type: text/html; charset=UTF-8');
    if (!empty($cc)) {
        $headers[] = 'Cc: ' . implode(',', $cc);
    }
    if (!empty($bcc)) {
        $headers[] = 'Bcc: ' . implode(',', $bcc);
    }

    $body = "<h2>{$title}</h2>" . $content;

    $sent = wp_mail($to, $subject, $body, $headers);

    if ($sent) {
        wp_send_json_success();
    } else {
        wp_send_json_error("No se pudo enviar el correo. Verifica la configuración SMTP de tu sitio.");
    }
}); 