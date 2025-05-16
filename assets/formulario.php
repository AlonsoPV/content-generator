<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<div class="generador-contenido">
  <div class="formulario">
    <h2>¡Genera tu idea! 84132b46-bdb1-47b9-841c-d4dbc9be6229</h2>
    <form id="generadorForm">
      <div class="form-row">
        <div class="form-group">
          <label for="tipo_contenido">
              <i class="fas fa-platform"></i>Plataforma: </label>
              <select name="tipo_contenido" id="tipo_contenido">
                <option value="post">Post para tu Blog</option>
                <option value="correo">Correo</option>
                <option value="whatsapp">Whatsapp</option>
                <option value="linkedin">LinkedIn</option>
              </select>
              <div id="plataforma-seleccionada" style="margin-top: 10px; padding: 10px; border-radius: 5px; display: none; background-color: #f0f0f0;">
                Plataforma seleccionada: <strong id="nombre-plataforma"></strong>
              </div>
              
              <script>
                // Script directo para manejar el cambio
                document.getElementById('tipo_contenido').addEventListener('change', function() {
                    var plataformaDiv = document.getElementById('plataforma-seleccionada');
                    var nombrePlataforma = document.getElementById('nombre-plataforma');
                    var tipo = this.value;
                    
                    // Configurar el nombre y color según la selección
                    var config = {
                        'post': {nombre: 'Post para tu Blog', color: '#e8f5e9'},
                        'correo': {nombre: 'Correo Electrónico', color: '#e3f2fd'},
                        'whatsapp': {nombre: 'WhatsApp', color: '#e0f2f1'}
                    };
                    
                    if (config[tipo]) {
                        nombrePlataforma.textContent = config[tipo].nombre;
                        plataformaDiv.style.backgroundColor = config[tipo].color;
                        plataformaDiv.style.display = 'block';
                    }
                });
              </script>
        </div>
        <div class="form-group full-width">
          <label for="idea">
            <i class="fas fa-lightbulb"></i> Idea: </label>
          <input type="text" id="idea" name="idea" placeholder="Ej: Marketing Digital" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
            <label for="idioma">
              <i class="fas fa-language"></i> Idioma 
            </label>
            <div class="info-wrapper">
              <span class="info-icon">ℹ️</span>
              <span class="tooltip">🌍 Selecciona el idioma del contenido.
El idioma determina la lengua en la que se generará el texto, asegurando que el mensaje sea claro y natural para la audiencia objetivo.</span>
            </div>
          <select name="idioma" id="idioma">
            <option value="es">Español</option>
            <option value="en">Inglés</option>
          </select>
        </div>
        <div class="form-group">
            <label for="longitud">
              <i class="fas fa-ruler-horizontal"></i> Longitud 
            </label>
            <div class="info-wrapper">
              <span class="info-icon">ℹ️</span>
              <span class="tooltip">✏️ Establece la extensión del contenido.
La longitud define cuántas palabras tendrá el texto, afectando la profundidad y el nivel de detalle.</span>
            </div>
          <input type="number" id="longitud" name="longitud" value="100" min="100" max="2000" step="25">
        </div>
        <div class="form-group">
            <label for="sentimiento">
              <i class="fas fa-heart"></i> Tono 
            </label>
            <div class="info-wrapper">
              <span class="info-icon">ℹ️</span>
              <span class="tooltip">
            📌 Define la personalidad del contenido.
Elegir el tono adecuado determina si tu mensaje será formal, amigable, persuasivo o inspirador.</span>
            </div>
          <select name="sentimiento" id="sentimiento">
            <option value="positivo">Positivo – Ideal para mensajes motivadores o inspiradores.</option>
            <option value="neutral">Neutral – Para información objetiva y directa.</option>
            <option value="informativo">Informativo – Enfocado en datos, hechos y precisión.</option>
            <option value="emocionante">Emocionante – Para generar entusiasmo o expectativa.</option>
            <option value="humoristico">Humorístico – Para contenido ligero y divertido.</option>
            <option value="serio">Serio – Adecuado para temas formales o técnicos.</option>
            <option value="persuasivo">Persuasivo – Orientado a convencer o influenciar.</option>
            <option value="empático">Empático – Para conectar emocionalmente con la audiencia.</option>
          </select>
        </div>
        <div class="form-group">
            <label for="estilo">
              <i class="fas fa-paint-brush"></i> Estilo 
            </label>
            <div class="info-wrapper">
              <span class="info-icon">ℹ️</span>
              <span class="tooltip">✅ Estilo
🖋️ Configura la forma de expresión del contenido.
El estilo define cómo se presentará la información: narrativo, técnico, conversacional o directo.</span>
            </div>
          <select name="estilo" id="estilo">
            <option value="casual">Casual – Lenguaje relajado, amigable e informal.</option>
            <option value="comercial">Comercial – Orientado a ventas, con un llamado a la acción claro.</option>
            <option value="formal">Formal – Para contenido profesional o institucional.</option>
            <option value="persuasivo">Persuasivo – Para influir o convencer al lector.</option>
            <option value="narrativo">Narrativo – Contar una historia o experiencia.</option>
            <option value="tecnico">Técnico – Preciso, detallado, con terminología específica.</option>
            <option value="creativo">Creativo – Para contenido artístico o innovador.</option>
            <option value="corporativo">Corporativo – Profesional, dirigido al mundo empresarial.</option>
          </select>
        </div>
      </div>
      <h3>Palabras clave</h3>
      <div id="keywords-container" class="keywords-grid"></div>
      <button type="button" id="add-keyword-button" class="btn-add-keyword">
        <i class="fas fa-plus"></i> Añadir palabra clave </button>
      <button type="button" id="generarPost" class="btn-generar">
        <i class="fas fa-cogs"></i> Generar</button>
    </form>
  </div>
  <div class="editor">
    <h2>Vista Previa</h2>
    <div id="preview-tabs">
      <button type="button" id="text-tab">Texto Plano</button>
      <button type="button" id="html-tab" class="active">Vista HTML</button>
    </div>
    <div id="html-preview">
      <input type="text" id="tituloEdit" placeholder="Título">
      <textarea id="contenidoEdit" placeholder="Contenido"></textarea>
    </div>
    <div id="text-preview" style="display: none;">
      <input type="text" id="tituloText" placeholder="Título">
      <textarea id="contenidoText" placeholder="Contenido"></textarea>
      <div id="editor-toolbar">
        <button onclick="copiarTexto()"><i class="fas fa-copy"></i> Copiar texto plano</button>
      </div>
    </div>
    <div class="form-group" id="imagenes">
      <label for="imagenes"><i class="fas fa-images"></i> Subir imágenes:</label>
      <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/*">
      <div id="preview-contenedor" style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 10px;"></div>

    </div>
    <button id="publicarPost" class="btn-publicar">
      <i class="fas fa-paper-plane"></i> Publicar</button>
    <div id="postCreatedButton" style="display: none;">
      <a id="postLink" href="#" target="_blank"></a>
    </div>
    
  </div>
  <div id="email-form" style="display:none; margin-top: 20px;">
      <h3>Enviar como correo electrónico</h3>
      <div class="form-group">
        <label for="email_to">Para (puedes ingresar varios correos separados por coma):</label>
        <input type="text" id="email_to" placeholder="destino1@ejemplo.com, destino2@ejemplo.com" required>
      </div>
      <div class="form-group">
        <label for="email_subject">Asunto:</label>
        <input type="text" id="email_subject" placeholder="Asunto del correo" required>
      </div>
      <div class="form-group">
        <label for="email_cc">Con copia a (CC, opcional):</label>
        <input type="text" id="email_cc" placeholder="cc1@ejemplo.com, cc2@ejemplo.com">
      </div>
      <div class="form-group">
        <label for="email_bcc">Con copia oculta a (BCC, opcional):</label>
        <input type="text" id="email_bcc" placeholder="bcc1@ejemplo.com, bcc2@ejemplo.com">
      </div>
      <button id="enviarCorreo" class="btn-generar" type="button">
        <i class="fas fa-paper-plane"></i> Enviar correo
      </button>
      <div id="emailStatus" style="margin-top:10px;"></div>
  </div>
  
</div> 

