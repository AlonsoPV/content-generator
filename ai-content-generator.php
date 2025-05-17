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
                        <input type="text" name="aicg_api_key" id="aicg_api_key" value="<?php echo esc_attr(get_option('aicg_api_key')); ?>" size="50" />
                        <button type="button" id="test_api_connection" class="button button-secondary" style="margin-left: 10px;">
                            <span class="dashicons dashicons-update"></span> Probar conexión
                        </button>
                        <div id="api_status" style="margin-top: 10px; padding: 10px; border-radius: 4px; display: none;"></div>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#test_api_connection').on('click', function() {
            var button = $(this);
            var statusDiv = $('#api_status');
            var apiKey = $('#aicg_api_key').val();

            if (!apiKey) {
                statusDiv.html('<span style="color: #dc3232;">❌ Por favor, ingresa una API Key primero.</span>').show();
                return;
            }

            button.prop('disabled', true);
            statusDiv.html('<span style="color: #666;">🔄 Probando conexión...</span>').show();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_ai21_connection',
                    api_key: apiKey,
                    nonce: '<?php echo wp_create_nonce("test_ai21_connection"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        statusDiv.html('<span style="color: #46b450;">✅ Conexión exitosa con AI21 API</span>').show();
                    } else {
                        statusDiv.html('<span style="color: #dc3232;">❌ Error: ' + response.data + '</span>').show();
                    }
                },
                error: function() {
                    statusDiv.html('<span style="color: #dc3232;">❌ Error al conectar con el servidor</span>').show();
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>
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
    if ($hook !== 'toplevel_page_ai-content-generator-generator') return;
    
    wp_enqueue_style('ai-content-generator-style', plugins_url('assets/style.css', __FILE__));
    wp_enqueue_script('ai-content-generator-script', plugins_url('assets/script.js', __FILE__), array('jquery'), '1.0.0', true);
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

    $prompt = "Escribe contenido sobre '$palabra_clave' que contenga la estructura y este adaptado para la plataforma $tipo_contenido en idioma '$idioma' con un tono '$sentimiento' y estilo '$estilo'. \nIncorpora de manera orgánica y que haga lógica las siguientes palabras clave: $keywords_string (asegúrate de usarlas todas, en caso de que no haya no las incluyas). Dale la estructura dependiendo de la plataforma elegida. Asegura poner el titulo en las tags de h1 <h1>. No uses frases como 'En conlusión' 'En resumen' o 'para resumir'. Evita ser repetitivo.\nEl contenido debe ser fidedigno, verificable y en formato HTML válido, con la siguiente estructura:\n- Párrafos envueltos en <p>.\n- Enlaces con la etiqueta <a> con href y target='_blank'.\n- No incluyas backticks, etiquetas de código, ni la palabra 'html' antes o después del contenido.\n- Solo devuelve HTML válido y estructurado. Nada más.\nDebe tener aproximadamente y maximo de '$longitud' palabras. Asegurate de que el contenido tenga sentido y haga lógica.";
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

    // Limpiar el contenido de las comillas markdown
    $contenido = preg_replace('/^```html\s*|\s*```$/m', '', $contenido);
    $contenido = trim($contenido);

    // Extraer el título
    preg_match('/<h1>(.*?)<\/h1>/', $contenido, $matches);
    $titulo = !empty($matches[1]) ? $matches[1] : 'Título generado automáticamente';

    // Limpiar el contenido
    $contenido = preg_replace('/<h1>.*?<\/h1>/', '', $contenido);
    $contenido = wp_kses_post($contenido);

    error_log('Contenido generado exitosamente');
    wp_send_json_success(array(
        "title" => $titulo,
        "content" => trim($contenido)
    ));
}

// AJAX: Publicar post
add_action('wp_ajax_publicar_post', 'aicg_publicar_post_ajax');
function aicg_publicar_post_ajax() {
    error_log('Iniciando publicación de post');
    error_log('FILES recibidos: ' . print_r($_FILES, true));
    error_log('POST recibidos: ' . print_r($_POST, true));
    
    try {
        check_ajax_referer('generador_nonce', 'nonce');

        if (!isset($_POST['title']) || !isset($_POST['content'])) {
            wp_send_json_error("Faltan datos para publicar el contenido.");
            return;
        }

        $titulo = sanitize_text_field($_POST['title']);
        $contenido = wp_unslash($_POST['content']);

        // Limpiar el contenido de las comillas markdown
        $contenido = preg_replace('/^```html\s*|\s*```$/m', '', $contenido);
        $contenido = trim($contenido);

        if (empty($titulo) || empty($contenido)) {
            wp_send_json_error("El título y el contenido no pueden estar vacíos.");
            return;
        }

        // Verificar permisos
        if (!current_user_can('publish_posts')) {
            wp_send_json_error("No tienes permisos para publicar posts.");
            return;
        }

        // Subir imágenes
        $imagenes_urls = [];

        if (!empty($_FILES['imagenes']['name'][0])) {
            error_log('Procesando imágenes...');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $files = $_FILES['imagenes'];
            foreach ($files['name'] as $key => $value) {
                if (!empty($files['name'][$key])) {
                    error_log("Procesando imagen {$key}: {$files['name'][$key]}");
                    
                    $file = [
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key],
                    ];

                    error_log('Datos del archivo: ' . print_r($file, true));

                    // Verificar errores de subida
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        error_log('Error en subida de archivo: ' . $file['error']);
                        continue;
                    }

                    // Subida segura con compatibilidad completa
                    $attachment_id = media_handle_sideload($file, 0);

                    if (is_wp_error($attachment_id)) {
                        error_log('Error al subir imagen: ' . $attachment_id->get_error_message());
                        continue;
                    }

                    $url = wp_get_attachment_url($attachment_id);
                    error_log("Imagen subida exitosamente. ID: {$attachment_id}, URL: {$url}");

                    $imagenes_urls[] = [
                        'id'   => $attachment_id,
                        'url'  => $url
                    ];
                }
            }
        } else {
            error_log('No se encontraron imágenes para subir');
        }

        // Crear contenido con imágenes flotantes
        $content_with_images = '';
        $imagenes_count = count($imagenes_urls);

        // Contenido generado primero
        $content_with_images .= $contenido;

        // Primera imagen centrada después del contenido
        if ($imagenes_count > 0) {
            $url = esc_url($imagenes_urls[0]['url']);
            $content_with_images .= "<div style='text-align: center; margin-top: 24px;'>\n";
            $content_with_images .= "<img src='{$url}' alt='Imagen destacada' style='width: 100%; max-width: 450px; height: auto; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);'>\n";
            $content_with_images .= "</div>\n";
        }

        // Imágenes adicionales al final, alternando flotado
        if ($imagenes_count > 1) {
            $content_with_images .= "<div style='display: flex; flex-wrap: wrap; gap: 24px; margin-top: 32px; justify-content: center;'>\n";
            for ($i = 1; $i < $imagenes_count; $i++) {
                $url = esc_url($imagenes_urls[$i]['url']);
                $float = ($i % 2 === 0) ? 'left' : 'right';
                $margin = $float === 'left' ? 'margin-right: 20px;' : 'margin-left: 20px;';
                $content_with_images .= "<figure style='flex: 1 1 300px; max-width: 400px; min-width: 220px; text-align: center; {$margin} margin-bottom: 20px;'>\n";
                $content_with_images .= "<img src='{$url}' alt='Imagen' style='width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.10);'>\n";
                $content_with_images .= "</figure>\n";
            }
            $content_with_images .= "</div>\n";
        }

        $content_with_images .= '<div style="clear: both;"></div>';

        // Combinar imágenes con contenido original
        $contenido_final = $content_with_images;

        // Crear el post
        $nuevo_post = [
            'post_title'   => $titulo,
            'post_content' => $contenido_final,
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_type'    => 'post',
        ];

        $post_id = wp_insert_post($nuevo_post);

        if (is_wp_error($post_id)) {
            error_log('Error al crear post: ' . $post_id->get_error_message());
            wp_send_json_error('No se pudo crear el post');
        }

        // Asociar imágenes al post y destacar la primera
        foreach ($imagenes_urls as $i => $img) {
            wp_update_post([
                'ID' => $img['id'],
                'post_parent' => $post_id,
            ]);

            if ($i === 0) {
                set_post_thumbnail($post_id, $img['id']);
            }
        }

        clean_post_cache($post_id);
        wp_cache_delete($post_id, 'posts');
        wp_cache_delete($post_id, 'post_meta');

        // Limpiar caché de imágenes
        foreach ($imagenes_urls as $imagen) {
            wp_cache_delete($imagen['id'], 'posts');
            wp_cache_delete($imagen['id'], 'post_meta');
        }
        error_log('Caché limpiada para post e imágenes');

        $permalink = get_permalink($post_id);
        if (!$permalink) {
            error_log('Error al obtener permalink para el post ID: ' . $post_id);
            wp_send_json_error("Error al obtener la URL del post.");
            return;
        }
        error_log('Permalink generado: ' . $permalink);

        wp_send_json_success($permalink);
    } catch (Exception $e) {
        error_log('Excepción al publicar post: ' . $e->getMessage());
        wp_send_json_error("Error inesperado: " . $e->getMessage());
    }
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

// Add AJAX handler for testing API connection
add_action('wp_ajax_test_ai21_connection', function() {
    check_ajax_referer('test_ai21_connection', 'nonce');
    
    $api_key = sanitize_text_field($_POST['api_key']);
    
    if (empty($api_key)) {
        wp_send_json_error('API Key no proporcionada');
        return;
    }

    $data = array(
        "model" => "jamba-large-1.6",
        "messages" => array(
            array("role" => "system", "content" => "Test de conexión"),
            array("role" => "user", "content" => "Responde con 'OK' si puedes leer este mensaje.")
        ),
        "temperature" => 0.7,
        "top_p" => 0.8,
    );

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'body' => json_encode($data),
        'timeout' => 15
    );

    $response = wp_remote_post('https://api.ai21.com/studio/v1/chat/completions', $args);

    if (is_wp_error($response)) {
        wp_send_json_error('Error de conexión: ' . $response->get_error_message());
        return;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code === 200 && isset($response_body['choices'][0]['message']['content'])) {
        wp_send_json_success('✅ Conexión de API exitosa - El sistema está listo para generar contenido');
    } else {
        $error_message = isset($response_body['error']) ? $response_body['error'] : 'Error desconocido';
        wp_send_json_error('Error de API: ' . $error_message);
    }
}); 

add_action('wp_ajax_test_upload_images', 'test_upload_images');
function test_upload_images() {
    if (!current_user_can('upload_files')) {
        wp_send_json_error("Sin permisos");
    }

    error_log("✅ Entrando a test_upload_images");
    error_log(print_r($_FILES, true));

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $results = [];

    if (!empty($_FILES['imagenes']['name'][0])) {
        foreach ($_FILES['imagenes']['name'] as $i => $name) {
            $file = [
                'name'     => $_FILES['imagenes']['name'][$i],
                'type'     => $_FILES['imagenes']['type'][$i],
                'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                'error'    => $_FILES['imagenes']['error'][$i],
                'size'     => $_FILES['imagenes']['size'][$i],
            ];

            $id = media_handle_sideload($file, 0);

            if (is_wp_error($id)) {
                $results[] = ['error' => $id->get_error_message()];
            } else {
                $results[] = ['id' => $id, 'url' => wp_get_attachment_url($id)];
            }
        }
    }

    wp_send_json_success($results);
}
