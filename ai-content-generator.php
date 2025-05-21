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
    // Página de callback de LinkedIn
    add_submenu_page(
        null, // No mostrar en el menú
        'LinkedIn Callback',
        'LinkedIn Callback',
        'manage_options',
        'linkedin-callback',
        'linkedin_callback_handler'
    );
});

function aicg_settings_page() {
    if (isset($_GET['linkedin_connected'])) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>✅ Conexión con LinkedIn establecida correctamente.</p>
        </div>
        <?php
    }
    ?>
    <div class="wrap">
        <h1>Configuración del Generador de Contenido AI</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('aicg_options');
            do_settings_sections('aicg_options');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">API Key de AI21</th>
                    <td>
                        <input type="text" id="aicg_api_key" name="aicg_api_key" value="<?php echo esc_attr(get_option('aicg_api_key')); ?>" class="regular-text">
                        <button type="button" id="test_api_connection" class="button">Probar Conexión</button>
                        <div id="api_status" style="margin-top: 10px; display: none;"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">LinkedIn Client ID</th>
                    <td>
                        <input type="text" id="aicg_linkedin_client_id" name="aicg_linkedin_client_id" value="<?php echo esc_attr(get_option('aicg_linkedin_client_id')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">LinkedIn Client Secret</th>
                    <td>
                        <input type="password" id="aicg_linkedin_client_secret" name="aicg_linkedin_client_secret" value="<?php echo esc_attr(get_option('aicg_linkedin_client_secret')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">LinkedIn Access Token</th>
                    <td>
                        <?php if (get_option('aicg_linkedin_access_token')): ?>
                            <input type="password" id="aicg_linkedin_access_token" name="aicg_linkedin_access_token" value="<?php echo esc_attr(get_option('aicg_linkedin_access_token')); ?>" class="regular-text">
                            <button type="button" id="test_linkedin_connection" class="button">Probar Conexión</button>
                            <div id="linkedin_status" style="margin-top: 10px; display: none;"></div>
                        <?php else: ?>
                            <p>No hay token de acceso configurado.</p>
                            <a href="<?php echo esc_url(get_linkedin_auth_url()); ?>" class="button button-primary">Conectar con LinkedIn</a>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function aicg_register_settings() {
    register_setting('aicg_options', 'aicg_api_key');
    register_setting('aicg_options', 'aicg_linkedin_client_id');
    register_setting('aicg_options', 'aicg_linkedin_client_secret');
    register_setting('aicg_options', 'aicg_linkedin_access_token');
}
add_action('admin_init', 'aicg_register_settings');

function get_linkedin_auth_url() {
    $client_id = get_option('aicg_linkedin_client_id');
    if (empty($client_id)) {
        return new WP_Error('no_client_id', 'No hay Client ID configurado para LinkedIn.');
    }
    
    $redirect_uri = admin_url('admin.php?page=linkedin-callback');
    $scope = 'openid profile w_member_social';



    
    return sprintf(
        'https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&state=%s',
        urlencode($client_id),
        urlencode($redirect_uri),
        urlencode($scope),
        wp_create_nonce('linkedin_auth')
    );
}

function linkedin_callback_handler() {
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos para acceder a esta página.'));
    }

    // Solo para debugging inicial: desactiva temporalmente el check del state (lo puedes volver a activar luego)
    // if (!isset($_GET['state']) || !wp_verify_nonce($_GET['state'], 'linkedin_auth')) {
    //     wp_die(__('Error de seguridad: Estado inválido.'));
    // }

    if (!isset($_GET['code'])) {
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : 'unknown';
        $error_description = isset($_GET['error_description']) ? sanitize_text_field($_GET['error_description']) : 'No se recibió el código de autorización.';
        
        echo '<div class="notice notice-error">';
        echo '<p>❌ Error al conectar con LinkedIn:</p>';
        echo '<p>Código de error: ' . esc_html($error) . '</p>';
        echo '<p>Descripción: ' . esc_html($error_description) . '</p>';
        echo '</div>';
        return;
    }

    $code = sanitize_text_field($_GET['code']);
    $client_id = get_option('aicg_linkedin_client_id');
    $client_secret = get_option('aicg_linkedin_client_secret');
    $redirect_uri = admin_url('admin.php?page=linkedin-callback');

    $response = wp_remote_post('https://www.linkedin.com/oauth/v2/accessToken', [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ],
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        wp_die('❌ Error de conexión con LinkedIn: ' . $response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['access_token'])) {
        wp_die('❌ No se recibió un token válido. Respuesta completa:<br><pre>' . print_r($body, true) . '</pre>');
    }

    update_option('aicg_linkedin_access_token', $body['access_token']);
    update_option('aicg_linkedin_token_created', time());
    update_option('aicg_linkedin_expires_in', $body['expires_in'] ?? 5184000); // Default a 2 meses

    echo '<div class="notice notice-success"><p>✅ Conectado con LinkedIn correctamente. Token guardado.</p></div>';
    echo '<p><a href="' . admin_url('options-general.php?page=ai-content-generator') . '" class="button button-primary">Volver a configuración</a></p>';
}


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
    // Verificar nonce
    if (!check_ajax_referer('generador_nonce', 'nonce', false)) {
        wp_send_json_error('Error de seguridad: Nonce inválido.');
        return;
    }

    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos para realizar esta acción.');
        return;
    }

    // Verificar API key
    $api_key = get_option('aicg_api_key');
    if (empty($api_key)) {
        wp_send_json_error('API key no configurada. Por favor, configura la API key en los ajustes del plugin.');
        return;
    }

    // Validar y sanitizar datos de entrada
    $palabra_clave = isset($_POST['idea']) ? sanitize_text_field($_POST['idea']) : '';
    if (empty($palabra_clave)) {
        wp_send_json_error('La idea no puede estar vacía.');
        return;
    }

    $sentimiento = isset($_POST['sentimiento']) ? sanitize_text_field($_POST['sentimiento']) : 'neutral';
    $longitud = isset($_POST['longitud']) ? intval($_POST['longitud']) : 500;
    $idioma = isset($_POST['idioma']) ? sanitize_text_field($_POST['idioma']) : 'es';
    $estilo = isset($_POST['estilo']) ? sanitize_text_field($_POST['estilo']) : 'formal';
    $tipo_contenido = isset($_POST['tipo_contenido']) ? sanitize_text_field($_POST['tipo_contenido']) : 'post';

    // Procesar keywords
    $keywords = [];
    if (isset($_POST['keywords'])) {
        $keywords_data = json_decode(stripslashes($_POST['keywords']), true);
        if (is_array($keywords_data)) {
            foreach ($keywords_data as $item) {
                if (isset($item['keyword']) && isset($item['link'])) {
                    $keywords[] = array(
                        'keyword' => sanitize_text_field($item['keyword']),
                        'link' => esc_url_raw($item['link'])
                    );
                }
            }
        }
    }

    $keywords_string = '';
    foreach ($keywords as $item) {
        $keywords_string .= "<a href='{$item['link']}' target='_blank'>{$item['keyword']}</a>, ";
    }
    $keywords_string = rtrim($keywords_string, ', ');

    // Preparar el prompt
    $prompt = "Escribe contenido sobre '$palabra_clave' que contenga la estructura y este adaptado para la plataforma $tipo_contenido en idioma '$idioma' con un tono '$sentimiento' y estilo '$estilo'. \nIncorpora de manera orgánica y que haga lógica las siguientes palabras clave: $keywords_string (asegúrate de usarlas todas, en caso de que no haya no las incluyas). Dale la estructura dependiendo de la plataforma elegida. Asegura poner el titulo en las tags de h1 <h1>. No uses frases como 'En conlusión' 'En resumen' o 'para resumir'. Evita ser repetitivo.\nEl contenido debe ser fidedigno, verificable y en formato HTML válido, con la siguiente estructura:\n- Párrafos envueltos en <p>.\n- Enlaces con la etiqueta <a> con href y target='_blank'.\n- No incluyas backticks, etiquetas de código, ni la palabra 'html' antes o después del contenido.\n- Solo devuelve HTML válido y estructurado. Nada más.\nDebe tener aproximadamente y maximo de '$longitud' palabras.
     Asegurate de que el contenido tenga sentido y haga lógica.
     Instrucciones especiales:

Si es para correo, usa un asunto atractivo y CTA al final. Asegura poner el asunto como titulo y en las tags de h1 <h1>

Si es para LinkedIn, usa formato tipo storytelling con emojis opcionales.

Si es para blog, estructura con subtítulos y lenguaje SEO-friendly. Asegura poner el titulo en las tags de h1 <h1>

Si es para WhatsApp, manténlo breve, directo y con tono conversacional.";

    // Preparar la petición a AI21
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

    // Realizar la petición a AI21
    $response = wp_remote_post('https://api.ai21.com/studio/v1/chat/completions', $args);

    if (is_wp_error($response)) {
        wp_send_json_error('Error al conectar con AI21: ' . $response->get_error_message());
        return;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_error('Error en la API de AI21 (código ' . $response_code . '): ' . $body);
        return;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($response_body['choices'][0]['message']['content'])) {
        wp_send_json_error('No se pudo generar contenido. Respuesta inesperada de AI21.');
        return;
    }

    $contenido = $response_body['choices'][0]['message']['content'] ?? "";

    // Limpiar el contenido
    $contenido = preg_replace('/^```html\s*|\s*```$/m', '', $contenido);
    $contenido = trim($contenido);

    // Extraer el título
    preg_match('/<h1>(.*?)<\/h1>/', $contenido, $matches);
    $titulo = !empty($matches[1]) ? $matches[1] : 'Título generado automáticamente';

    // Limpiar el contenido
    $contenido = preg_replace('/<h1>.*?<\/h1>/', '', $contenido);
    $contenido = wp_kses_post($contenido);

    // Enviar respuesta exitosa
    wp_send_json_success(array(
        "title" => $titulo,
        "content" => trim($contenido)
    ));
}

// AJAX: Publicar post
add_action('wp_ajax_aicg_publicar_post', 'aicg_publicar_post_ajax');
function aicg_publicar_post_ajax() {
    check_ajax_referer('aicg_publicar_post', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos para realizar esta acción.');
    }

    $titulo = sanitize_text_field($_POST['titulo']);
    $tipo_contenido = sanitize_text_field($_POST['tipo_contenido']);
    $palabras_clave = sanitize_text_field($_POST['palabras_clave']);

    if (empty($titulo) || empty($tipo_contenido) || empty($palabras_clave)) {
        wp_send_json_error('Todos los campos son obligatorios.');
    }

    // Generar contenido con AI21
    $api_key = get_option('aicg_api_key');
    if (empty($api_key)) {
        wp_send_json_error('API Key no configurada.');
    }

    $prompt = "Escribe un post sobre {$titulo}. Palabras clave: {$palabras_clave}. El post debe ser informativo, profesional y atractivo.";
    
    $response = wp_remote_post('https://api.ai21.com/v1/complete', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'prompt' => $prompt,
            'maxTokens' => 1000,
            'temperature' => 0.7,
            'topP' => 0.8
        ))
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Error al generar contenido: ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['completions'][0]['text'])) {
        wp_send_json_error('Error al procesar la respuesta de AI21.');
    }

    $contenido = $data['completions'][0]['text'];

    // Procesar imágenes si existen
    $imagenes_count = 0;
    $imagen_url = '';
    if (!empty($_FILES['imagenes'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        foreach ($_FILES['imagenes']['name'] as $i => $name) {
            $file = array(
                'name'     => $_FILES['imagenes']['name'][$i],
                'type'     => $_FILES['imagenes']['type'][$i],
                'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                'error'    => $_FILES['imagenes']['error'][$i],
                'size'     => $_FILES['imagenes']['size'][$i]
            );

            $id = media_handle_sideload($file, 0);
            if (!is_wp_error($id)) {
                $imagenes_count++;
                if ($imagenes_count === 1) {
                    $imagen_url = wp_get_attachment_url($id);
                }
            }
        }
    }

    // Agregar imagen al contenido si existe
    if ($imagenes_count > 0 && !empty($imagen_url)) {
        $contenido = '<div style="text-align: center; margin-bottom: 20px;"><img src="' . esc_url($imagen_url) . '" alt="' . esc_attr($titulo) . '" style="max-width: 450px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>' . $contenido;
    }

    if ($tipo_contenido === 'linkedin') {
        // Publicar en LinkedIn
        $result = publicar_en_linkedin($titulo, $contenido);
        if (is_wp_error($result)) {
            wp_send_json_error('Error al publicar en LinkedIn: ' . $result->get_error_message());
        }
        wp_send_json_success('Post publicado en LinkedIn correctamente.');
    } else {
        // Publicar en WordPress
        $post_data = array(
            'post_title'    => $titulo,
            'post_content'  => $contenido,
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id()
        );

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            wp_send_json_error('Error al publicar en WordPress: ' . $post_id->get_error_message());
        }

        // Establecer imagen destacada si existe
        if ($imagenes_count > 0) {
            $attachment_id = attachment_url_to_postid($imagen_url);
            if ($attachment_id) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }

        wp_send_json_success('Post publicado en WordPress correctamente.');
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
add_action('wp_ajax_test_ai21_connection', 'test_ai21_connection');
function test_ai21_connection() {
    check_ajax_referer('test_ai21_connection', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos para realizar esta acción.');
    }

    $api_key = sanitize_text_field($_POST['api_key']);
    if (empty($api_key)) {
        wp_send_json_error('API Key no proporcionada.');
    }

    $response = wp_remote_post('https://api.ai21.com/v1/complete', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'prompt' => 'Test connection',
            'maxTokens' => 1
        ))
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Error al conectar con AI21: ' . $response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        wp_send_json_success('Conexión exitosa con AI21 API');
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_error('Error de API: ' . $body);
    }
}

add_action('wp_ajax_test_upload_images', 'test_upload_images');
function test_upload_images() {
    check_ajax_referer('test_upload_images', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos para realizar esta acción.');
    }

    if (!isset($_FILES['imagenes'])) {
        wp_send_json_error('No se han subido imágenes.');
    }

    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['path'];
    $upload_url = $upload_dir['url'];

    $success = true;
    $errors = array();
    $uploaded_files = array();

    foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
            $file_name = sanitize_file_name($_FILES['imagenes']['name'][$key]);
            $file_type = wp_check_filetype($file_name);

            if (!$file_type['ext']) {
                $errors[] = "El archivo $file_name no es una imagen válida.";
                $success = false;
                continue;
            }

            $new_file_name = wp_unique_filename($upload_path, $file_name);
            $new_file_path = $upload_path . '/' . $new_file_name;

            if (move_uploaded_file($tmp_name, $new_file_path)) {
                $uploaded_files[] = array(
                    'name' => $new_file_name,
                    'url' => $upload_url . '/' . $new_file_name
                );
            } else {
                $errors[] = "Error al mover el archivo $file_name.";
                $success = false;
            }
        } else {
            $errors[] = "Error al subir el archivo " . $_FILES['imagenes']['name'][$key];
            $success = false;
        }
    }

    if ($success) {
        wp_send_json_success(array(
            'message' => 'Imágenes subidas correctamente.',
            'files' => $uploaded_files
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Error al subir algunas imágenes.',
            'errors' => $errors
        ));
    }
}

function publicar_en_linkedin($titulo, $contenido) {
    $access_token = get_option('aicg_linkedin_access_token');
    if (empty($access_token)) {
        return new WP_Error('no_token', 'No hay token de acceso configurado para LinkedIn.');
    }

    // Validar el contenido
    if (empty($contenido)) {
        return new WP_Error('empty_content', 'El contenido no puede estar vacío.');
    }

    // Validar longitud del contenido
    $contenido_length = strlen(strip_tags($contenido));
    if ($contenido_length > 3000) {
        return new WP_Error('content_too_long', 'El contenido excede el límite de 3000 caracteres. Longitud actual: ' . $contenido_length);
    }

    // Validar formato del contenido
    if (strpos($contenido, '<script') !== false || strpos($contenido, 'javascript:') !== false) {
        return new WP_Error('invalid_content', 'El contenido contiene código JavaScript no permitido.');
    }

    // Obtener el ID del usuario
    $response = wp_remote_get('https://api.linkedin.com/v2/me', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        )
    ));

    if (is_wp_error($response)) {
        return new WP_Error('api_error', 'Error al obtener el ID de usuario: ' . $response->get_error_message());
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        $error_data = json_decode($body, true);
        $error_message = isset($error_data['message']) ? $error_data['message'] : $body;
        
        switch ($response_code) {
            case 401:
                return new WP_Error('unauthorized', 'Token de acceso inválido o expirado. Por favor, reconecta con LinkedIn.');
            case 403:
                return new WP_Error('forbidden', 'No tienes permisos para realizar esta acción en LinkedIn.');
            case 429:
                return new WP_Error('rate_limit', 'Has excedido el límite de solicitudes a la API de LinkedIn. Por favor, espera unos minutos.');
            default:
                return new WP_Error('api_error', 'Error en la API de LinkedIn (código ' . $response_code . '): ' . $error_message);
        }
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['id'])) {
        return new WP_Error('api_error', 'Error al procesar la respuesta de LinkedIn: ' . $body);
    }

    $user_id = $data['id'];

    // Preparar el contenido del post
    $post_data = array(
        'author' => 'urn:li:person:' . $user_id,
        'lifecycleState' => 'PUBLISHED',
        'specificContent' => array(
            'com.linkedin.ugc.ShareContent' => array(
                'shareCommentary' => array(
                    'text' => $contenido
                ),
                'shareMediaCategory' => 'NONE'
            )
        ),
        'visibility' => array(
            'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
        )
    );

    // Publicar el post
    $response = wp_remote_post('https://api.linkedin.com/v2/ugcPosts', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0'
        ),
        'body' => json_encode($post_data)
    ));

    if (is_wp_error($response)) {
        return new WP_Error('api_error', 'Error al publicar en LinkedIn: ' . $response->get_error_message());
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 201) {
        $body = wp_remote_retrieve_body($response);
        $error_data = json_decode($body, true);
        $error_message = isset($error_data['message']) ? $error_data['message'] : $body;
        
        switch ($response_code) {
            case 400:
                return new WP_Error('bad_request', 'Error en el formato del contenido: ' . $error_message);
            case 401:
                return new WP_Error('unauthorized', 'Token de acceso inválido o expirado. Por favor, reconecta con LinkedIn.');
            case 403:
                return new WP_Error('forbidden', 'No tienes permisos para publicar en LinkedIn.');
            case 429:
                return new WP_Error('rate_limit', 'Has excedido el límite de publicaciones en LinkedIn. Por favor, espera unos minutos.');
            default:
                return new WP_Error('api_error', 'Error al publicar en LinkedIn (código ' . $response_code . '): ' . $error_message);
        }
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['id'])) {
        return true;
    } else {
        return new WP_Error('api_error', 'Error al procesar la respuesta de LinkedIn: ' . $body);
    }
}

// Agregar handler para probar la conexión con LinkedIn
add_action('wp_ajax_test_linkedin_connection', 'test_linkedin_connection');
function test_linkedin_connection() {
    check_ajax_referer('test_linkedin_connection', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos para realizar esta acción.');
    }

    $access_token = get_option('aicg_linkedin_access_token');
    if (empty($access_token)) {
        wp_send_json_error('No hay token de acceso configurado para LinkedIn.');
    }

    $response = wp_remote_get('https://api.linkedin.com/v2/me', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        )
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Error al conectar con LinkedIn: ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['id'])) {
        wp_send_json_success('Conexión exitosa con LinkedIn. ID de usuario: ' . $data['id']);
    } else {
        wp_send_json_error('Error al obtener datos de LinkedIn: ' . $body);
    }
}

function aicg_linkedin_callback_page() {
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para acceder a esta página.');
    }

    $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
    if (empty($code)) {
        wp_die('Código de autorización no proporcionado.');
    }

    $client_id = get_option('aicg_linkedin_client_id');
    $client_secret = get_option('aicg_linkedin_client_secret');
    $redirect_uri = admin_url('admin.php?page=aicg-linkedin-callback');

    $response = wp_remote_post('https://www.linkedin.com/oauth/v2/accessToken', array(
        'body' => array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri
        )
    ));

    if (is_wp_error($response)) {
        wp_die('Error al obtener el token de acceso: ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['access_token'])) {
        update_option('aicg_linkedin_access_token', $data['access_token']);
        wp_redirect(admin_url('admin.php?page=aicg-settings&linkedin_connected=1'));
        exit;
    } else {
        wp_die('Error al procesar la respuesta de LinkedIn: ' . $body);
    }
}

function aicg_add_admin_menu() {
    add_menu_page(
        'Generador de Contenido AI',
        'Generador AI',
        'manage_options',
        'aicg-settings',
        'aicg_settings_page',
        'dashicons-admin-generic'
    );

    add_submenu_page(
        'aicg-settings',
        'Callback de LinkedIn',
        'Callback de LinkedIn',
        'manage_options',
        'aicg-linkedin-callback',
        'aicg_linkedin_callback_page'
    );
}
add_action('admin_menu', 'aicg_add_admin_menu');

function aicg_admin_scripts() {
    wp_enqueue_script('aicg-admin', plugins_url('assets/script.js', __FILE__), array('jquery'), '1.0.0', true);
    wp_localize_script('aicg-admin', 'AICG', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aicg_publicar_post'),
        'linkedin_nonce' => wp_create_nonce('test_linkedin_connection'),
        'upload_nonce' => wp_create_nonce('test_upload_images'),
        'ai21_nonce' => wp_create_nonce('test_ai21_connection')
    ));
}
add_action('admin_enqueue_scripts', 'aicg_admin_scripts');
