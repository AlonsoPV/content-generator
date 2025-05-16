// File: assets/script.js

console.log('Script cargado - inicio del archivo');

// Variables globales (solo una vez)
let tituloEdit, tituloText, contenidoEdit, contenidoText, tipoContenido, publicarBtn, imagenesDiv, emailForm, enviarCorreoBtn, emailStatus;
let isInitialized = false;

document.addEventListener("DOMContentLoaded", function() {
    if (isInitialized) {
        console.log('Script ya inicializado, evitando reinicialización');
        return;
    }
    
    console.log('DOMContentLoaded ejecutado');
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
    document.getElementById("imagenes").addEventListener("change", function () {
        const contenedor = document.getElementById("preview-contenedor");
        contenedor.innerHTML = ""; // limpiar previos
    
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.createElement("img");
                img.src = e.target.result;
                img.style.maxWidth = "100px";
                img.style.borderRadius = "6px";
                img.style.border = "1px solid #ccc";
                contenedor.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });
    
    // Obtener elementos
    tipoContenido = document.getElementById("tipo_contenido");
    publicarBtn = document.getElementById("publicarPost");
    imagenesDiv = document.querySelector(".form-group#imagenes");
    emailForm = document.getElementById("email-form");
    enviarCorreoBtn = document.getElementById("enviarCorreo");
    emailStatus = document.getElementById("emailStatus");
    
    // Verificar elementos críticos
    if (!tipoContenido) console.error('No se encontró el elemento tipo_contenido');
    if (!publicarBtn) console.error('No se encontró el elemento publicarPost');
    if (!imagenesDiv) console.error('No se encontró el elemento imagenes');
    if (!emailForm) console.error('No se encontró el elemento email-form');
    
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
    const generarButton = document.getElementById('generarPost');
    if (!generarButton) {
        console.error('Botón generarPost no encontrado');
        return;
    }

    generarButton.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const ideaInput = document.getElementById('idea');
        if (!ideaInput || !ideaInput.value.trim()) {
            alert('Por favor, ingresa una idea para el contenido.');
            return;
        }

        const form = document.getElementById('generadorForm');
        if (!form) {
            console.error('Formulario no encontrado');
            return;
        }

        // Recolectar keywords
        const keywords = [];
        const keywordInputs = document.querySelectorAll('.keyword-group');
        keywordInputs.forEach(group => {
            const keyword = group.querySelector('.keyword-input')?.value;
            const link = group.querySelector('.keyword-link')?.value;
            if (keyword && link) {
                keywords.push({ keyword, link });
            }
        });

        // Mostrar loading
        generarButton.disabled = true;
        generarButton.textContent = 'Generando...';

        try {
            const formData = new FormData(form);
            formData.append('action', 'generar_contenido');
            formData.append('nonce', AICG.nonce);
            formData.append('keywords', JSON.stringify(keywords));

            const response = await fetch(AICG.ajax_url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            console.log('Respuesta del servidor:', data);

            if (data.success) {
                // Limpiar el contenido antes de mostrarlo
                const cleanContent = cleanContent(data.data.content);
                
                // Actualizar el título y contenido
                document.getElementById('title').value = data.data.title;
                quill.root.innerHTML = cleanContent;
                
                // Mostrar el contenido generado
                document.getElementById('contenidoGenerado').style.display = 'block';
            } else {
                alert(data.data || 'Error al generar el contenido');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al generar el contenido. Por favor, intenta de nuevo.');
        } finally {
            // Restaurar el botón
            generarButton.disabled = false;
            generarButton.textContent = 'Generar Contenido';
        }
    });
}

function initPublicarPost() {
    const publicarButton = document.getElementById("publicarPost");
    if (!publicarButton) {
        console.error('No se encontró el botón de publicar');
        return;
    }

    publicarButton.addEventListener("click", async function() {
        const contenido = document.getElementById("contenidoEdit").value.trim();
        if (contenido === "") {
            alert("Cuidado!: Debes generar el contenido antes de publicar algo.");
            return;
        }

        // Log de los datos que se enviarán
        console.log('Enviando datos para publicar:', {
            title: document.getElementById("tituloEdit").value,
            contentLength: contenido.length,
            hasImages: document.getElementById("imagenes")?.files?.length > 0
        });

        publicarButton.disabled = true;
        publicarButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';

        try {
            const formData = new FormData();
            formData.append("action", "publicar_post");
            formData.append("nonce", AICG.nonce);
            formData.append("title", document.getElementById("tituloEdit").value);
            formData.append("content", contenido);
            
            const imagenesInput = document.getElementById("imagenes");
            if (imagenesInput && imagenesInput.files && imagenesInput.files.length > 0) {
                for (let i = 0; i < imagenesInput.files.length; i++) {
                    formData.append(`imagenes[${i}]`, imagenesInput.files[i]);
                }
            }

            const response = await fetch(AICG.ajax_url, { 
                method: "POST", 
                body: formData 
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Respuesta del servidor:', data);

            if (data.success) {
                const postLink = document.getElementById("postLink");
                postLink.href = data.data;
                postLink.innerText = `¡Post creado con éxito!`;
                document.getElementById("postCreatedButton").style.display = "block";
                
                // Limpiar el input de imágenes después de una publicación exitosa
                if (imagenesInput) {
                    imagenesInput.value = '';
                }
            } else {
                throw new Error(data.data || 'Error desconocido al publicar el post');
            }
        } catch (error) {
            console.error('Error:', error);
            alert("Error al publicar el post: " + error.message);
        } finally {
            publicarButton.disabled = false;
            publicarButton.innerHTML = '<i class="fas fa-paper-plane"></i> Publicar';
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
