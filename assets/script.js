// File: assets/script.js

console.log('Script cargado - inicio del archivo');

// Variables globales
let tituloEdit, tituloText, contenidoEdit, contenidoText, tipoContenido, publicarBtn, imagenesDiv, emailForm, enviarCorreoBtn, emailStatus;
let isInitialized = false;

document.addEventListener("DOMContentLoaded", function() {
    if (isInitialized) {
        console.log('Script ya inicializado, evitando reinicialización');
        return;
    }
    
    console.log('DOMContentLoaded ejecutado');
    
    // Inicializar elementos primero
    tituloEdit = document.getElementById("tituloEdit");
    tituloText = document.getElementById("tituloText");
    contenidoEdit = document.getElementById("contenidoEdit");
    contenidoText = document.getElementById("contenidoText");
    tipoContenido = document.getElementById("tipo_contenido");
    publicarBtn = document.getElementById("publicarPost");
    imagenesDiv = document.getElementById("imagenes-wrapper");
    emailForm = document.getElementById("email-form");
    enviarCorreoBtn = document.getElementById("enviarCorreo");
    emailStatus = document.getElementById("emailStatus");

    // Verificar elementos críticos
    if (!tituloEdit) console.error('No se encontró el elemento tituloEdit');
    if (!tituloText) console.error('No se encontró el elemento tituloText');
    if (!contenidoEdit) console.error('No se encontró el elemento contenidoEdit');
    if (!contenidoText) console.error('No se encontró el elemento contenidoText');
    if (!tipoContenido) console.error('No se encontró el elemento tipoContenido');
    if (!publicarBtn) console.error('No se encontró el elemento publicarPost');
    if (!imagenesDiv) console.error('No se encontró el elemento imagenes-wrapper');
    if (!emailForm) console.error('No se encontró el elemento email-form');
    if (!enviarCorreoBtn) console.error('No se encontró el elemento enviarCorreo');
    if (!emailStatus) console.error('No se encontró el elemento emailStatus');

    // Inicializar funcionalidades
    initElementos();
    initMostrarBoton();
    initTituloSync();
    initContenidoSync();
    initTabs();
    initKeywordManagement();
    initCopiarTexto();
    initGenerarPost();
    initPublicarPost();
    initEnviarCorreo();
    initPostCreatedButton();
    
    isInitialized = true;
});

function initElementos() {
    console.log('Iniciando initElementos');
    const imagenesInput = document.getElementById("imagenes");
    if (!imagenesInput) {
        console.error('No se encontró el input de imágenes');
        return;
    }

    imagenesInput.addEventListener("change", function () {
        console.log('Cambio detectado en input de imágenes:', this.files);
        const contenedor = document.getElementById("preview-contenedor");
        if (!contenedor) {
            console.error('No se encontró el contenedor de preview');
            return;
        }

        // Verificar si ya hay una imagen cargada
        if (contenedor.children.length > 0) {
            alert('Solo puedes cargar una imagen a la vez. Por favor, elimina la imagen actual antes de cargar una nueva.');
            this.value = ''; // Limpiar el input
            return;
        }

        // Verificar si se intenta cargar más de una imagen
        if (this.files.length > 1) {
            alert('Solo puedes cargar una imagen a la vez.');
            this.value = ''; // Limpiar el input
            return;
        }

        contenedor.innerHTML = ""; // limpiar previos
    
        Array.from(this.files).forEach((file, index) => {
            console.log('Procesando archivo:', file.name);
            const reader = new FileReader();
            reader.onload = function (e) {
                const imgContainer = document.createElement("div");
                imgContainer.style.position = "relative";
                imgContainer.style.display = "inline-block";
                imgContainer.style.margin = "5px";
                imgContainer.style.cursor = "pointer";

                const img = document.createElement("img");
                img.src = e.target.result;
                img.style.maxWidth = "100px";
                img.style.borderRadius = "6px";
                img.style.border = "1px solid #ccc";
                img.style.transition = "all 0.3s ease";

                const removeBtn = document.createElement("button");
                removeBtn.innerHTML = "×";
                removeBtn.style.position = "absolute";
                removeBtn.style.top = "-10px";
                removeBtn.style.right = "-10px";
                removeBtn.style.width = "20px";
                removeBtn.style.height = "20px";
                removeBtn.style.borderRadius = "50%";
                removeBtn.style.background = "#ff4444";
                removeBtn.style.color = "white";
                removeBtn.style.border = "none";
                removeBtn.style.cursor = "pointer";
                removeBtn.style.display = "none"; // Oculto por defecto
                removeBtn.style.alignItems = "center";
                removeBtn.style.justifyContent = "center";
                removeBtn.style.fontSize = "16px";
                removeBtn.style.padding = "0";
                removeBtn.style.lineHeight = "1";
                removeBtn.style.zIndex = "2";

                // Mostrar/ocultar botón al hacer clic en la imagen
                imgContainer.addEventListener("click", function(e) {
                    if (e.target === img) {
                        const isSelected = imgContainer.classList.contains("selected");
                        // Deseleccionar todas las imágenes
                        document.querySelectorAll("#preview-contenedor > div").forEach(div => {
                            div.classList.remove("selected");
                            div.querySelector("button").style.display = "none";
                            div.querySelector("img").style.border = "1px solid #ccc";
                        });
                        
                        if (!isSelected) {
                            // Seleccionar esta imagen
                            imgContainer.classList.add("selected");
                            removeBtn.style.display = "flex";
                            img.style.border = "2px solid #0073aa";
                        }
                    }
                });

                removeBtn.addEventListener("click", function(e) {
                    e.stopPropagation(); // Evitar que el clic se propague al contenedor
                    // Crear un nuevo FileList sin la imagen eliminada
                    const dt = new DataTransfer();
                    const files = imagenesInput.files;
                    
                    for (let i = 0; i < files.length; i++) {
                        if (i !== index) {
                            dt.items.add(files[i]);
                        }
                    }
                    
                    // Actualizar el input con las imágenes restantes
                    imagenesInput.files = dt.files;
                    
                    // Eliminar el contenedor de la imagen
                    imgContainer.remove();
                });

                imgContainer.appendChild(img);
                imgContainer.appendChild(removeBtn);
                contenedor.appendChild(imgContainer);
            };
            reader.readAsDataURL(file);
        });
    });
    
    // Verificar elementos
    console.log('Estado de elementos después de init:', {
        tipoContenido: !!tipoContenido,
        publicarBtn: !!publicarBtn,
        imagenesDiv: !!imagenesDiv,
        emailForm: !!emailForm
    });
}

function initMostrarBoton() {
    console.log('Iniciando initMostrarBoton');
    if (!tipoContenido) {
        console.error('No se encontró el elemento tipoContenido');
        return;
    }
    console.log('Tipo contenido inicial:', tipoContenido.value);

    // Manejo inicial
    actualizarVista(tipoContenido.value);

    // Agregar el evento change directamente
    console.log('Agregando evento change');
    tipoContenido.addEventListener('change', function(e) {
        console.log('Cambio detectado:', e.target.value);
        actualizarVista(e.target.value);
    });
}

function actualizarVista(tipo) {
    console.log('Actualizando vista para:', tipo);
    
    // Actualizar el mensaje de plataforma seleccionada
    const plataformaDiv = document.getElementById('plataforma-seleccionada');
    const nombrePlataforma = document.getElementById('nombre-plataforma');
    
    if (plataformaDiv && nombrePlataforma) {
        let nombreMostrar = '';
        switch(tipo) {
            case "post":
                nombreMostrar = "Post para tu Blog";
                plataformaDiv.style.backgroundColor = "#e8f5e9";  // Verde claro
                break;
            case "correo":
                nombreMostrar = "Correo Electrónico";
                plataformaDiv.style.backgroundColor = "#e3f2fd";  // Azul claro
                break;
            case "whatsapp":
                nombreMostrar = "WhatsApp";
                plataformaDiv.style.backgroundColor = "#e0f2f1";  // Verde agua claro
                break;
        }
        nombrePlataforma.textContent = nombreMostrar;
        plataformaDiv.style.display = "block";
    }
    
    // Verificar elementos
    console.log('Estado elementos:', {
        publicarBtn: !!publicarBtn,
        imagenesDiv: !!imagenesDiv,
        emailForm: !!emailForm
    });

    // Ocultar todo primero
    if (publicarBtn) publicarBtn.style.display = "none";
    if (imagenesDiv) imagenesDiv.style.display = "none";
    if (emailForm) emailForm.style.display = "none";

    // Mostrar según el tipo
    switch(tipo) {
        case "post":
            console.log('Mostrando elementos para post');
            if (publicarBtn) publicarBtn.style.display = "inline-block";
            if (imagenesDiv) imagenesDiv.style.display = "block";
            break;
        case "correo":
            console.log('Mostrando elementos para correo');
            if (emailForm) emailForm.style.display = "block";
            break;
        case "whatsapp":
            console.log('Mostrando elementos para whatsapp');
            break;
    }
}

function initTituloSync() {
    if (!tituloEdit || !tituloText) return;
    tituloEdit.addEventListener("input", () => tituloText.value = tituloEdit.value);
    tituloText.addEventListener("input", () => tituloEdit.value = tituloText.value);
}

function initContenidoSync() {
    if (!contenidoEdit || !contenidoText) return;
    contenidoEdit.addEventListener("input", () => contenidoText.value = htmlToText(contenidoEdit.value));
    contenidoText.addEventListener("input", () => contenidoEdit.value = textToHtml(contenidoText.value, contenidoEdit.value));
}

function htmlToText(html) {
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = html;
    return tempDiv.textContent || tempDiv.innerText || "";
}

function textToHtml(text, originalHtml) {
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = originalHtml;
    let textParts = text.split(" ");
    let index = 0;
    function replaceText(node) {
        if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== "") {
            const words = node.textContent.split(" ");
            node.textContent = textParts.slice(index, index + words.length).join(" ");
            index += words.length;
        } else if (node.nodeType === Node.ELEMENT_NODE) {
            node.childNodes.forEach(replaceText);
        }
    }
    replaceText(tempDiv);
    return tempDiv.innerHTML;
}

function initTabs() {
    const htmlTab = document.getElementById("html-tab");
    const textTab = document.getElementById("text-tab");
    const htmlPreview = document.getElementById("html-preview");
    const textPreview = document.getElementById("text-preview");

    if (!htmlTab || !textTab || !htmlPreview || !textPreview) return;

    htmlTab.addEventListener("click", () => {
        htmlPreview.style.display = "block";
        textPreview.style.display = "none";
        htmlTab.classList.add("active");
        textTab.classList.remove("active");
    });

    textTab.addEventListener("click", () => {
        htmlPreview.style.display = "none";
        textPreview.style.display = "block";
        textTab.classList.add("active");
        htmlTab.classList.remove("active");
    });
}

function initKeywordManagement() {
    const addKeywordBtn = document.getElementById("add-keyword-button");
    const container = document.getElementById("keywords-container");

    if (!addKeywordBtn || !container) return;

    addKeywordBtn.addEventListener("click", () => {
        const numKeywords = container.children.length + 1;
        const div = document.createElement("div");
        div.className = "keyword-group";
        div.innerHTML = `
            <input type="text" name="keyword${numKeywords}" placeholder="Palabra clave" required>
            <input type="url" name="enlace${numKeywords}" placeholder="URL" required>
            <button type="button" class="remove-keyword-button">Eliminar</button>
        `;
        container.appendChild(div);
        div.querySelector(".remove-keyword-button").addEventListener("click", () => div.remove());
    });
}

function initCopiarTexto() {
    window.copiarTexto = function() {
        const copyText = document.getElementById("contenidoText");
        if (!copyText) {
            alert("No hay contenido para copiar.");
            return;
        }
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value)
            .then(() => alert("Texto copiado al portapapeles"))
            .catch(err => console.error("Error al copiar:", err));
    };
}

function cleanContent(content) {
    // Eliminar las comillas markdown del inicio y final
    return content.replace(/^```html\s*|\s*```$/gm, '').trim();
}

function initGenerarPost() {
    const form = document.getElementById('generadorForm');
    if (!form) {
        console.error('No se encontró el formulario generadorForm');
        return;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitButton = form.querySelector('button[type="submit"]');
        if (!submitButton) {
            console.error('No se encontró el botón submit');
            return;
        }

        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Generando...';

        try {
            const formData = new FormData(form);
            formData.append('action', 'generar_contenido');
            formData.append('nonce', AICG.nonce);

            // Recolectar keywords
            const keywords = [];
            const keywordInputs = document.querySelectorAll('.keyword-group');
            keywordInputs.forEach(group => {
                const keyword = group.querySelector('input[type="text"]')?.value;
                const link = group.querySelector('input[type="url"]')?.value;
                if (keyword && link) {
                    keywords.push({ keyword, link });
                }
            });
            formData.append('keywords', JSON.stringify(keywords));

            const response = await fetch(AICG.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            // Verificar el tipo de contenido de la respuesta
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no-JSON recibida:', text);
                throw new Error('El servidor devolvió una respuesta no válida. Por favor, intenta de nuevo.');
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.data || 'Error al generar el contenido');
            }

            // Actualizar el contenido en el editor
            if (tituloEdit) {
                tituloEdit.value = data.data.title;
            }
            if (tituloText) {
                tituloText.value = data.data.title;
            }

            if (contenidoEdit) {
                contenidoEdit.value = data.data.content;
            }
            if (contenidoText) {
                contenidoText.value = htmlToText(data.data.content);
            }

            // Mostrar mensaje de éxito
            const successMessage = document.createElement('div');
            successMessage.className = 'notice notice-success is-dismissible';
            successMessage.innerHTML = '<p>Contenido generado exitosamente.</p>';
            form.insertAdjacentElement('beforebegin', successMessage);

            // Eliminar el mensaje después de 5 segundos
            setTimeout(() => {
                successMessage.remove();
            }, 5000);

        } catch (error) {
            console.error('Error:', error);
            
            // Mostrar mensaje de error
            const errorMessage = document.createElement('div');
            errorMessage.className = 'notice notice-error is-dismissible';
            errorMessage.innerHTML = `<p>Error: ${error.message}</p>`;
            form.insertAdjacentElement('beforebegin', errorMessage);

            // Eliminar el mensaje después de 5 segundos
            setTimeout(() => {
                errorMessage.remove();
            }, 5000);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
}

function initPublicarPost() {
    const publicarButton = document.getElementById("publicarPost");
    if (!publicarButton) return;

    publicarButton.addEventListener("click", async function() {
        const titulo = document.getElementById("tituloEdit").value;
        const contenido = document.getElementById("contenidoEdit").value;
        const tipoContenido = document.getElementById("tipo_contenido").value;

        if (!titulo || !contenido) {
            alert("Por favor, completa todos los campos requeridos.");
            return;
        }

        publicarButton.disabled = true;
        publicarButton.textContent = "Publicando...";

        try {
            const formData = new FormData();
            formData.append("action", "publicar_post");
            formData.append("nonce", AICG.nonce);
            formData.append("title", titulo);
            formData.append("content", contenido);
            formData.append("tipo_contenido", tipoContenido);
            
            const imagenesInput = document.getElementById("imagenes");
            if (imagenesInput && imagenesInput.files && imagenesInput.files.length > 0) {
                for (let i = 0; i < imagenesInput.files.length; i++) {
                    formData.append("imagenes[]", imagenesInput.files[i]);
                }
            }

            const response = await fetch(AICG.ajaxurl, { 
                method: "POST", 
                body: formData 
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Respuesta del servidor:', data);

            if (data.success) {
                if (tipoContenido === 'linkedin') {
                    alert('✅ Post publicado en LinkedIn correctamente.');
                } else {
                    const postLink = document.getElementById("postLink");
                    postLink.href = data.data;
                    postLink.innerText = `¡Post creado con éxito!`;
                    document.getElementById("postCreatedButton").style.display = "block";
                }
                
                // Limpiar el input de imágenes después de una publicación exitosa
                if (imagenesInput) {
                    imagenesInput.value = '';
                }
            } else {
                let errorMessage = data.data || 'Error desconocido al publicar el post';
                
                // Manejar errores específicos de LinkedIn
                if (tipoContenido === 'linkedin') {
                    switch(true) {
                        case errorMessage.includes('no_token'):
                            errorMessage = 'No hay token de acceso configurado para LinkedIn. Por favor, configura las credenciales de LinkedIn en la página de ajustes.';
                            break;
                        case errorMessage.includes('empty_content'):
                            errorMessage = 'El contenido no puede estar vacío. Por favor, agrega contenido antes de publicar.';
                            break;
                        case errorMessage.includes('content_too_long'):
                            errorMessage = 'El contenido excede el límite de 3000 caracteres de LinkedIn. Por favor, reduce la longitud del contenido.';
                            break;
                        case errorMessage.includes('invalid_content'):
                            errorMessage = 'El contenido contiene elementos no permitidos por LinkedIn. Por favor, revisa el contenido y elimina cualquier código JavaScript o elementos no permitidos.';
                            break;
                        case errorMessage.includes('unauthorized'):
                            errorMessage = 'Tu sesión de LinkedIn ha expirado. Por favor, reconecta con LinkedIn en la página de ajustes.';
                            break;
                        case errorMessage.includes('forbidden'):
                            errorMessage = 'No tienes permisos para publicar en LinkedIn. Verifica que tu cuenta tenga los permisos necesarios.';
                            break;
                        case errorMessage.includes('rate_limit'):
                            errorMessage = 'Has excedido el límite de publicaciones en LinkedIn. Por favor, espera unos minutos antes de intentar nuevamente.';
                            break;
                        case errorMessage.includes('bad_request'):
                            errorMessage = 'El formato del contenido no es válido para LinkedIn. Por favor, revisa el contenido y asegúrate de que cumpla con los requisitos de LinkedIn.';
                            break;
                    }
                }
                throw new Error(errorMessage);
            }
        } catch (error) {
            console.error('Error:', error);
            alert("Error al publicar el post: " + error.message);
        } finally {
            publicarButton.disabled = false;
            publicarButton.textContent = "Publicar Post";
        }
    });
}

function initEnviarCorreo() {
    if (!enviarCorreoBtn) return;

    enviarCorreoBtn.addEventListener("click", function() {
        const to = document.getElementById("email_to").value.trim();
        const subject = document.getElementById("email_subject").value.trim();
        const cc = document.getElementById("email_cc").value.trim();
        const bcc = document.getElementById("email_bcc").value.trim();
        const content = document.getElementById("contenidoEdit").value.trim();
        const title = document.getElementById("tituloEdit").value.trim();

        if (!to || !subject || !content) {
            emailStatus.innerHTML = "<span style='color:red;'>Completa todos los campos obligatorios.</span>";
            return;
        }

        enviarCorreoBtn.disabled = true;
        enviarCorreoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        const formData = new FormData();
        formData.append("action", "enviar_correo_contenido");
        formData.append("nonce", AICG.nonce);
        formData.append("to", to);
        formData.append("subject", subject);
        formData.append("cc", cc);
        formData.append("bcc", bcc);
        formData.append("content", content);
        formData.append("title", title);

        fetch(AICG.ajax_url, { method: "POST", body: formData })
            .then(response => response.json())
            .then(data => handleEnviarCorreoResponse(data))
            .catch(error => handleAjaxError(error, enviarCorreoBtn, 'Enviar correo'));
    });
}

function handleEnviarCorreoResponse(data) {
    enviarCorreoBtn.disabled = false;
    enviarCorreoBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar correo';
    emailStatus.innerHTML = data.success
        ? "<span style='color:green;'>¡Correo enviado correctamente!</span>"
        : "<span style='color:red;'>Error: " + data.data + "</span>";
}

function handleAjaxError(error, button, defaultText) {
    console.error('Error:', error);
    button.disabled = false;
    button.innerHTML = `<i class="fas fa-cogs"></i> ${defaultText}`;
    alert("Ocurrió un error inesperado.");
}

function initPostCreatedButton() {
    const postCreatedButton = document.getElementById("postCreatedButton");
    if (postCreatedButton) {
        postCreatedButton.addEventListener("click", () => {
            postCreatedButton.style.display = "none";
        });
    }
}

function testCleanContent() {
    const testContent = `\`\`\`html
<p>El <strong>marketing digital</strong> es esencial para alcanzar el éxito hoy en día. Con estrategias efectivas, puedes llegar a más clientes y aumentar tus ventas.</p>
<p>Las <strong>redes sociales</strong> son una herramienta clave. Plataformas como Instagram y Facebook te permiten conectar con tu audiencia de manera directa y creativa.</p>
<p>Además, el <strong>SEO</strong> ayuda a que tu sitio web aparezca en los primeros resultados de búsqueda. Esto aumenta tu visibilidad y credibilidad.</p>
<p>No olvides el poder del <strong>email marketing</strong>. Envía contenido relevante y ofertas especiales para mantener a tus clientes interesados.</p>
<p>¿Listo para llevar tu negocio al siguiente nivel? ¡El marketing digital es tu aliado!</p>
\`\`\``;

    console.log('Contenido original:', testContent);
    const cleanedContent = cleanContent(testContent);
    console.log('Contenido limpio:', cleanedContent);
    
    // Mostrar el resultado en la consola
    console.log('¿Se eliminaron las comillas?', !cleanedContent.includes('```html'));
}

// Agregar botón de prueba al DOM
document.addEventListener('DOMContentLoaded', function() {
    const testButton = document.createElement('button');
    testButton.textContent = 'Probar Limpieza';
    testButton.style.position = 'fixed';
    testButton.style.bottom = '10px';
    testButton.style.right = '10px';
    testButton.style.zIndex = '9999';
    testButton.onclick = testCleanContent;
    document.body.appendChild(testButton);
});

jQuery(document).ready(function($) {
    $('#test_api_connection').on('click', function() {
        var button = $(this);
        var statusDiv = $('#api_status');

        button.prop('disabled', true);
        statusDiv.html('<span style="color: #666;">🔄 Probando conexión con AI21...</span>').show();

        $.ajax({
            url: AICG.ajaxurl,
            type: 'POST',
            data: {
                action: 'test_api_connection',
                nonce: AICG.api_nonce
            },
            success: function(response) {
                if (response.success) {
                    statusDiv.html('<span style="color: #46b450;">✅ Conexión exitosa con AI21</span>').show();
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

    $('#test_linkedin_connection').on('click', function() {
        var button = $(this);
        var statusDiv = $('#linkedin_status');

        button.prop('disabled', true);
        statusDiv.html('<span style="color: #666;">🔄 Probando conexión con LinkedIn...</span>').show();

        $.ajax({
            url: AICG.ajaxurl,
            type: 'POST',
            data: {
                action: 'test_linkedin_connection',
                nonce: AICG.linkedin_nonce
            },
            success: function(response) {
                if (response.success) {
                    statusDiv.html('<span style="color: #46b450;">✅ Conexión exitosa con LinkedIn</span>').show();
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

    $('#imagenes').on('change', function() {
        var files = this.files;
        var formData = new FormData();
        formData.append("action", "test_upload_images");
        formData.append("nonce", AICG.upload_nonce);

        // Mostrar vista previa de las imágenes
        var previewContainer = $('#preview-contenedor');
        previewContainer.empty();

        for (var i = 0; i < files.length; i++) {
            (function(file) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    var preview = $('<div class="preview-item">')
                        .append($('<img>').attr('src', e.target.result))
                        .append($('<div class="preview-name">').text(file.name));
                    previewContainer.append(preview);
                };

                reader.readAsDataURL(file);
            })(files[i]);
        }

        // Subir las imágenes
        for (var i = 0; i < files.length; i++) {
            formData.append("imagenes[]", files[i]);
        }

        $.ajax({
            url: AICG.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    console.log('Imágenes subidas correctamente:', response.data.files);
                } else {
                    console.error('Error al subir imágenes:', response.data.errors);
                }
            },
            error: function() {
                console.error('Error al conectar con el servidor');
            }
        });
    });

    $('#generarPost').on('click', function() {
        var button = $(this);
        var formData = new FormData();
        var tipoContenido = $('#tipo_contenido').val();

        // Agregar datos del formulario
        formData.append('action', 'aicg_publicar_post');
        formData.append('nonce', AICG.nonce);
        formData.append('titulo', $('#titulo').val());
        formData.append('tipo_contenido', tipoContenido);
        formData.append('palabras_clave', $('#palabras_clave').val());

        // Agregar imágenes si existen
        var imagenesInput = $('#imagenes')[0];
        if (imagenesInput.files.length > 0) {
            for (var i = 0; i < imagenesInput.files.length; i++) {
                formData.append('imagenes[]', imagenesInput.files[i]);
            }
        }

        button.prop('disabled', true);
        button.text('Generando...');

        $.ajax({
            url: AICG.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    if (tipoContenido === 'linkedin') {
                        alert('Post publicado en LinkedIn correctamente.');
                    } else {
                        alert('Post publicado en WordPress correctamente.');
                    }
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error al conectar con el servidor');
            },
            complete: function() {
                button.prop('disabled', false);
                button.text('Generar Post');
            }
        });
    });
});
