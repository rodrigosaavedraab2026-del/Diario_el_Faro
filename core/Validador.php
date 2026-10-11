<?php
/**
 * Validador: reglas de validación en el servidor, reutilizadas por los
 * controladores. Los largos máximos coinciden con las columnas de MySQL
 * para que un dato nunca sea rechazado (o truncado) por la base de datos.
 */
class Validador
{
    private $errores = [];

    public function requerido($campo, $valor, $mensaje)
    {
        if (trim((string) $valor) === "") {
            $this->agregar($campo, $mensaje);
        }
        return $this;
    }

    public function largo($campo, $valor, $min, $max)
    {
        $n = mb_strlen((string) $valor, "UTF-8");
        if ($n > 0 && $n < $min) {
            $this->agregar($campo, "Debe tener al menos $min caracteres.");
        } elseif ($n > $max) {
            $this->agregar($campo, "No puede superar los $max caracteres.");
        }
        return $this;
    }

    public function email($campo, $valor)
    {
        if ($valor !== "" && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            $this->agregar($campo, "Ingresa un correo válido.");
        }
        return $this;
    }

    public function enLista($campo, $valor, array $permitidos, $mensaje)
    {
        if (!in_array($valor, $permitidos, true)) {
            $this->agregar($campo, $mensaje);
        }
        return $this;
    }

    /** Solo letras (con tildes y ñ), espacios, apóstrofo y guion. */
    public function soloTexto($campo, $valor)
    {
        if ($valor !== "" && !preg_match("/^[\p{L} '\-]+$/u", $valor)) {
            $this->agregar($campo, "Usa solo letras y espacios.");
        }
        return $this;
    }

    /** Rechaza etiquetas HTML (p. ej. <script>) en textos que se publican. */
    public function sinEtiquetas($campo, $valor)
    {
        if ($valor !== strip_tags($valor)) {
            $this->agregar($campo, "No se permiten etiquetas HTML.");
        }
        return $this;
    }

    /**
     * Cuerpo de artículo: si trae HTML, solo puede usar lo que permite la
     * política (core/PoliticaHtml.php). Se rechaza y se informa qué sobra,
     * para que el redactor decida en vez de perder contenido sin saberlo.
     */
    public function htmlPermitido($campo, $valor)
    {
        if (PoliticaHtml::tieneHtml($valor)) {
            $politica = new PoliticaHtml();
            $politica->limpiar($valor);
            if ($politica->eliminados()) {
                $this->agregar($campo, "Contiene elementos no permitidos: " . implode(", ", array_slice($politica->eliminados(), 0, 5)) . ".");
            }
        }
        return $this;
    }

    public function agregar($campo, $mensaje)
    {
        if (!isset($this->errores[$campo])) {   // conserva el primer error por campo
            $this->errores[$campo] = $mensaje;
        }
        return $this;
    }

    public function errores() { return $this->errores; }
    public function esValido() { return empty($this->errores); }
}
