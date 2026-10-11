<?php
/**
 * PoliticaHtml — política explícita de HTML para el CUERPO de los artículos
 * (recomendación del docente: contenido enriquecido con seguridad controlada).
 *
 * Principio: LISTA BLANCA. Todo lo que no está permitido aquí se elimina.
 * Solo el cuerpo del artículo admite HTML; el título, el resumen, la etiqueta,
 * los comentarios y todos los formularios de lectores siguen siendo TEXTO
 * PLANO y se escapan con e() al mostrarse.
 *
 *  Etiqueta        Atributos permitidos             Restricción
 *  --------------  -------------------------------  ---------------------------------------
 *  p, br           —                                párrafos y saltos de línea
 *  strong, em      —                                énfasis (b → strong, i → em)
 *  h3, h4          —                                subtítulos dentro de la nota (h1/h2 son del sitio)
 *  ul, ol, li      —                                listas
 *  blockquote      —                                citas textuales
 *  a               href                             solo https://, http:// o mailto:;
 *                                                   los externos abren en otra pestaña con
 *                                                   rel="noopener noreferrer nofollow"
 *  figure,         —                                agrupan imagen o video con su pie
 *  figcaption
 *  img             src, alt, width, height          src: archivo de assets/img (nombre simple
 *                                                   .jpg/.png/.webp) o https://; alt obligatorio
 *  iframe          src                              solo videos de YouTube (/embed/ID de 11
 *                                                   caracteres); se reescribe a
 *                                                   youtube-nocookie.com con sandbox
 *
 * Siempre se eliminan: atributos on* (onclick, onerror…), style, class, id,
 * y las etiquetas script, style, object, embed, form, input, button, svg,
 * math, template, link, meta y base (con todo su contenido). Las etiquetas
 * desconocidas se quitan pero se conserva su texto.
 *
 * Se aplica dos veces (defensa en profundidad):
 *   1) al publicar (ArticuloController::guardar) para avisar al redactor;
 *   2) al mostrar (Articulo::getCuerpoHtml) por si la base se modificó por fuera.
 */
class PoliticaHtml
{
    const PERMITIDAS = [
        "p" => [], "br" => [], "strong" => [], "em" => [], "h3" => [], "h4" => [],
        "ul" => [], "ol" => [], "li" => [], "blockquote" => [],
        "a" => ["href"], "figure" => [], "figcaption" => [],
        "img" => ["src", "alt", "width", "height"], "iframe" => ["src"],
    ];
    /** Equivalencias: se convierten a la etiqueta semántica permitida. */
    const EQUIVALENTES = ["b" => "strong", "i" => "em", "h1" => "h3", "h2" => "h3", "h5" => "h4", "h6" => "h4"];
    /** Se eliminan con todo su contenido. */
    const PELIGROSAS = ["script", "style", "object", "embed", "form", "input", "button", "textarea", "select",
                        "svg", "math", "template", "link", "meta", "base", "noscript"];
    /** Atributos que la política agrega por sí misma (se recalculan en cada limpieza). */
    const GENERADOS = ["loading", "target", "rel", "title", "allowfullscreen", "referrerpolicy", "sandbox"];
    const PATRON_YOUTUBE = '#^https://(?:www\.)?(?:youtube\.com|youtube-nocookie\.com)/embed/([A-Za-z0-9_-]{11})(?:\?.*)?$#';

    /** @var string[] elementos quitados en la última limpieza (para avisar al redactor) */
    private $eliminados = [];
    private $baseImagenes;

    public function __construct($baseImagenes = "assets/img/")
    {
        $this->baseImagenes = $baseImagenes;
    }

    /** ¿El texto contiene etiquetas? (si no, se trata como texto plano con párrafos). */
    public static function tieneHtml($texto)
    {
        return (bool) preg_match('/<\s*[a-z!\/]/i', (string) $texto);
    }

    public function eliminados()
    {
        return array_values(array_unique($this->eliminados));
    }

    /** Devuelve HTML seguro según la política. */
    public function limpiar($html)
    {
        $this->eliminados = [];
        $html = trim((string) $html);
        if ($html === "") {
            return "";
        }

        $doc = new DOMDocument("1.0", "UTF-8");
        $previo = libxml_use_internal_errors(true);
        // El prefijo XML fuerza UTF-8; el contenedor evita que libxml agregue <html><body>.
        $doc->loadHTML('<?xml encoding="UTF-8"?><div id="raiz">' . $html . '</div>',
                       LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        $raiz = $doc->getElementById("raiz");
        if ($raiz === null) {
            return "";
        }
        $this->limpiarHijos($raiz, $doc);

        $salida = "";
        foreach ($raiz->childNodes as $hijo) {
            $salida .= $doc->saveHTML($hijo);
        }
        return trim($salida);
    }

    private function limpiarHijos(DOMNode $nodo, DOMDocument $doc)
    {
        // Se copia la lista porque se modifica durante el recorrido.
        foreach (iterator_to_array($nodo->childNodes) as $hijo) {
            if ($hijo instanceof DOMComment || $hijo instanceof DOMProcessingInstruction) {
                $nodo->removeChild($hijo);
                continue;
            }
            if (!$hijo instanceof DOMElement) {
                continue;   // texto: libxml ya lo entrega decodificado y saveHTML lo vuelve a escapar
            }
            $etiqueta = strtolower($hijo->tagName);

            if (in_array($etiqueta, self::PELIGROSAS, true)) {
                $this->eliminados[] = "<$etiqueta>";
                $nodo->removeChild($hijo);
                continue;
            }
            if (isset(self::EQUIVALENTES[$etiqueta])) {
                $hijo = $this->renombrar($hijo, self::EQUIVALENTES[$etiqueta], $doc);
                $etiqueta = self::EQUIVALENTES[$etiqueta];
            }
            if (!isset(self::PERMITIDAS[$etiqueta])) {
                // Etiqueta desconocida: se conserva el texto, se quita la etiqueta.
                $this->eliminados[] = "<$etiqueta>";
                $this->limpiarHijos($hijo, $doc);
                while ($hijo->firstChild) {
                    $nodo->insertBefore($hijo->firstChild, $hijo);
                }
                $nodo->removeChild($hijo);
                continue;
            }

            $this->limpiarAtributos($hijo, $etiqueta);
            if ($hijo->parentNode === null) {
                continue;   // fue eliminado por un atributo obligatorio inválido (img/iframe)
            }
            $this->limpiarHijos($hijo, $doc);
        }
    }

    private function limpiarAtributos(DOMElement $el, $etiqueta)
    {
        $permitidos = self::PERMITIDAS[$etiqueta];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $nombre = strtolower($attr->name);
            if (!in_array($nombre, $permitidos, true)) {
                if (!in_array($nombre, self::GENERADOS, true)) {   // los que pone la propia política no se informan
                    $this->eliminados[] = "atributo $nombre";
                }
                $el->removeAttribute($attr->name);
            }
        }

        if ($etiqueta === "a") {
            $href = trim($el->getAttribute("href"));
            if ($href === "" || !preg_match('#^(https?://|mailto:)#i', $href)) {
                if ($href !== "") {
                    $this->eliminados[] = "enlace no permitido";
                }
                $el->removeAttribute("href");
            } else {
                $el->setAttribute("href", $href);
                if (preg_match('#^https?://#i', $href)) {
                    $el->setAttribute("target", "_blank");
                    $el->setAttribute("rel", "noopener noreferrer nofollow");
                }
            }
        }

        if ($etiqueta === "img") {
            $src = trim($el->getAttribute("src"));
            // Se acepta el nombre simple ("nota1.jpg") o la ruta ya normalizada
            // ("assets/img/nota1.jpg"), para que filtrar dos veces dé el mismo resultado.
            $archivo = preg_replace('#^' . preg_quote($this->baseImagenes, '#') . '#', '', $src);
            if (preg_match('/^[a-z0-9_-]+\.(jpe?g|png|webp)$/i', $archivo)) {
                $el->setAttribute("src", $this->baseImagenes . $archivo);
            } elseif (!preg_match('#^https://#i', $src)) {
                $this->eliminados[] = "imagen con origen no permitido";
                $el->parentNode->removeChild($el);
                return;
            }
            $el->setAttribute("alt", trim($el->getAttribute("alt")));   // alt obligatorio (vacío = decorativa)
            $el->setAttribute("loading", "lazy");
            foreach (["width", "height"] as $dim) {
                if ($el->hasAttribute($dim) && !ctype_digit($el->getAttribute($dim))) {
                    $el->removeAttribute($dim);
                }
            }
        }

        if ($etiqueta === "iframe") {
            if (!preg_match(self::PATRON_YOUTUBE, trim($el->getAttribute("src")), $m)) {
                $this->eliminados[] = "<iframe> que no es un video de YouTube";
                $el->parentNode->removeChild($el);
                return;
            }
            $el->setAttribute("src", "https://www.youtube-nocookie.com/embed/" . $m[1]);
            $el->setAttribute("title", "Video de YouTube");
            $el->setAttribute("loading", "lazy");
            $el->setAttribute("allowfullscreen", "");
            $el->setAttribute("referrerpolicy", "strict-origin-when-cross-origin");
            $el->setAttribute("sandbox", "allow-scripts allow-same-origin allow-presentation allow-popups");
        }
    }

    private function renombrar(DOMElement $el, $nueva, DOMDocument $doc)
    {
        $nuevo = $doc->createElement($nueva);
        while ($el->firstChild) {
            $nuevo->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($nuevo, $el);
        return $nuevo;
    }
}
