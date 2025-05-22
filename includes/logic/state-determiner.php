<?php
/**
 * Lógica para Determinar el Estado de los Bloques de Horario.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Determina el estado específico de un bloque de buffer.
 *
 * @param int    $maestro_id
 * @param int    $dia_semana
 * @param string $hora_inicio_buffer_str  (HH:MM)
 * @param string $hora_fin_buffer_str     (HH:MM)
 * @param string $posicion                'antes' o 'despues' de la clase asignada.
 * @param int    $sede_id_clase_adyacente ID de la sede de la CLASE ASIGNADA adyacente a este buffer.
 * @param string $hora_inicio_jornada_str (HH:MM) Hora inicio de la jornada general.
 * @param string $hora_fin_jornada_str    (HH:MM) Hora fin de la jornada general.
 * @return string El estado calculado.
 */
function mph_determinar_estado_buffer( $maestro_id, $dia_semana, $hora_inicio_buffer_str, $hora_fin_buffer_str, $posicion, $sede_id_clase_adyacente, $hora_inicio_jornada_str, $hora_fin_jornada_str ) {
    $log_prefix = "mph_determinar_estado_buffer:";
    error_log("$log_prefix Iniciando - Maestro: $maestro_id, Dia: $dia_semana, Buffer: $hora_inicio_buffer_str-$hora_fin_buffer_str, Pos: $posicion, SedeClaseAdy: $sede_id_clase_adyacente, Jornada: $hora_inicio_jornada_str-$hora_fin_jornada_str");

    $base_date = '1970-01-01 '; // Fecha base para comparaciones de tiempo

    // --- Preparar DateTimes para comparaciones ---
    $dt_inicio_buffer = null;
    $dt_fin_buffer = null;
    $dt_inicio_jornada = new DateTime($base_date . $hora_inicio_jornada_str);
    $dt_fin_jornada = new DateTime($base_date . $hora_fin_jornada_str);
    
     try {
          $dt_inicio_buffer = new DateTime($base_date . $hora_inicio_buffer_str);
          $dt_fin_buffer = new DateTime($base_date . $hora_fin_buffer_str);
     } catch (Exception $e) {
         error_log("$log_prefix Error creando DateTime para buffer actual: " . $e->getMessage());
         return 'Mismo o Traslado'; // Default en error de fecha
     }

    // --- Obtener datos de la Sede de la CLASE Adyacente (la que genera este buffer) ---
    $hora_cierre_sede_clase_ady = null;
    $es_sede_clase_ady_comun = false;


    if ( $sede_id_clase_adyacente > 0 ) {
        $hc_raw = get_term_meta( $sede_id_clase_adyacente, 'hora_cierre', true );
        if ( $hc_raw && preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $hc_raw) ) {
             $hora_cierre_sede_clase_ady = $hc_raw;
        }
        $comun_raw = get_term_meta( $sede_id_clase_adyacente, 'sede_comun', true );
        $es_sede_clase_ady_comun = !empty($comun_raw) && $comun_raw === '1';
        error_log("$log_prefix Sede Clase Ady ID: $sede_id_clase_adyacente - Cierre: " . ($hora_cierre_sede_clase_ady ?? 'N/A') . " - Común: " . ($es_sede_clase_ady_comun ? 'Sí' : 'No'));
    }

    // --- Obtener todos los horarios del maestro para ese día ---
    // (Esto es crucial para la lógica de Traslado y Mismo con vecinos)
    $todos_horarios_dia = mph_get_horarios_existentes_dia( $maestro_id, $dia_semana );
    // Filtrar solo clases asignadas/llenas para encontrar clases vecinas
    $clases_vecinas_posts = array_filter($todos_horarios_dia, function($h) {
        $estado_h = get_post_meta($h->ID, 'mph_estado', true);
        return ($estado_h === 'Asignado' || $estado_h === 'Lleno');
    });
    // Filtrar bloques Vacío/Buffer para encontrar vecinos no asignados
    $bloques_no_asignados_vecinos_posts = array_filter($todos_horarios_dia, function($h) {
        $estado_h = get_post_meta($h->ID, 'mph_estado', true);
        return !in_array($estado_h, ['Asignado', 'Lleno']);
    });


    // --- Búsqueda de Bloques Inmediatamente Adyacentes (Clases o No Asignados) ---
    $bloque_siguiente_al_buffer = null; // Puede ser Clase o Vacío/Buffer
    $bloque_anterior_al_buffer = null;  // Puede ser Clase o Vacío/Buffer

    // Convertir horas del buffer actual para comparación precisa
    $dt_buffer_inicio_actual = new DateTime($base_date . $hora_inicio_buffer_str);
    $dt_buffer_fin_actual = new DateTime($base_date . $hora_fin_buffer_str);

    // --- Lógica de "Borrar y Recrear" ---
    foreach ($todos_horarios_dia as $h_vecino) {
        // Evitar compararse consigo mismo si este buffer ya fuera un post existente (para Actualización Inteligente)
        // if ($buffer_post_id_actual && $h_vecino->ID == $buffer_post_id_actual) continue;

        $inicio_vecino_str = get_post_meta($h_vecino->ID, 'mph_hora_inicio', true);
        $fin_vecino_str = get_post_meta($h_vecino->ID, 'mph_hora_fin', true);
        if (!$inicio_vecino_str || !$fin_vecino_str) continue;

        try {
            $dt_inicio_vecino = new DateTime($base_date . $inicio_vecino_str);
            $dt_fin_vecino = new DateTime($base_date . $fin_vecino_str);

            // Buscar bloque siguiente
            if ($dt_inicio_vecino == $dt_buffer_fin_actual) { // El vecino empieza justo cuando termina este buffer
                $bloque_siguiente_al_buffer = $h_vecino;
            }
            // Buscar bloque anterior
            if ($dt_fin_vecino == $dt_buffer_inicio_actual) { // El vecino termina justo cuando empieza este buffer
                $bloque_anterior_al_buffer = $h_vecino;
            }
        } catch (Exception $e) { continue; }
    }
    if ($bloque_anterior_al_buffer) error_log("$log_prefix Bloque anterior encontrado: ID " . $bloque_anterior_al_buffer->ID . " (" . get_post_meta($bloque_anterior_al_buffer->ID, 'mph_estado', true) . ")");
    if ($bloque_siguiente_al_buffer) error_log("$log_prefix Bloque siguiente encontrado: ID " . $bloque_siguiente_al_buffer->ID . " (" . get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_estado', true) . ")");


    // PRIORIDAD 1: TRASLADO (SOLO forzado entre clases asignadas en sedes diferentes)
    // Con "Borrar y Recrear", esta lógica es difícil que se active correctamente para un buffer que se está creando.
    // Se activará si SE PROCESAN DOS ASIGNACIONES JUNTAS en una misma llamada a calculator.
    if ($posicion === 'despues' && $bloque_siguiente_al_buffer) { 
        $estado_siguiente = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_estado', true);
    if ($estado_siguiente === 'Asignado' || $estado_siguiente === 'Lleno') {
        $sede_clase_siguiente = (int) get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_sede_asignada', true);
        if ($sede_clase_siguiente > 0 && $sede_id_clase_adyacente > 0 && $sede_clase_siguiente !== $sede_id_clase_adyacente) {
            error_log("$log_prefix Estado = Traslado (Buffer DESPUES entre sedes $sede_id_clase_adyacente y $sede_clase_siguiente)");
            return 'Traslado';
        }
    } }
    elseif ($posicion === 'antes' && $bloque_anterior_al_buffer) { 
        $estado_anterior = get_post_meta($bloque_anterior_al_buffer->ID, 'mph_estado', true);
    if ($estado_anterior === 'Asignado' || $estado_anterior === 'Lleno') {
        $sede_clase_anterior = (int) get_post_meta($bloque_anterior_al_buffer->ID, 'mph_sede_asignada', true);
        if ($sede_clase_anterior > 0 && $sede_id_clase_adyacente > 0 && $sede_clase_anterior !== $sede_id_clase_adyacente) {
            error_log("$log_prefix Estado = Traslado (Buffer ANTES entre sedes $sede_clase_anterior y $sede_id_clase_adyacente)");
            return 'Traslado';
        }
    } }

    /* Inicia Modificación: Ajustar lógica de cierre para priorizar el comportamiento de calculator.php */
    // PRIORIDAD 2: NO DISPONIBLE (CIERRE SEDE)
    // Si el buffer es 'despues', la sede adyacente (no común) cierra.
    // Calculator.php ya divide el buffer si el cierre es DURANTE.
    // Aquí solo nos preocupamos si este buffer (o su inicio) está EN o DESPUÉS del cierre.
    if ( $posicion === 'despues' && !$es_sede_clase_ady_comun && $hora_cierre_sede_clase_ady ) {
        try {
            $dt_hora_cierre_ady = new DateTime($base_date . $hora_cierre_sede_clase_ady);
            // Si el buffer actual (que podría ser la Parte B de un buffer dividido)
            // empieza en o después de la hora de cierre.
            if ($dt_inicio_buffer >= $dt_hora_cierre_ady) {
                error_log("$log_prefix Estado = No Disponible (Buffer inicia en/después del cierre de SedeAdy: $sede_id_clase_adyacente a las $hora_cierre_sede_clase_ady)");
                return 'No Disponible'; // Esto cubre la Parte B de un buffer dividido y el Escenario 2.1.
                                        // Para el Escenario 2.2 (Traslado), calculator.php debe crear el Vacío con SedeY después,
                                        // y este 'No Disponible' se convertiría en 'Traslado' si tuviéramos info del siguiente.
                                        // Como no la tenemos, calculator.php es quien extiende el No Disponible si el Vacío
                                        // posterior solo tiene comunes.
            }
            // Si el buffer (no dividido) termina DESPUÉS de la hora de cierre (cierra durante)
            // Esto ya lo maneja calculator.php al dividir. Si llega aquí, es la parte ANTES del cierre.
            // Así que no necesitamos la condición ($dt_fin_buffer > $dt_hora_cierre_ady && $dt_inicio_buffer < $dt_hora_cierre_ady) aquí.

        } catch (Exception $e) { error_log("$log_prefix Error comparando hora cierre para 'No Disponible': " . $e->getMessage()); }
    }
    /* Finaliza Modificación */


    // PRIORIDAD 3: MISMO
    if ( ($posicion === 'antes' && $dt_inicio_buffer == $dt_inicio_jornada && $bloque_anterior_al_buffer === null) ) {
        error_log("$log_prefix Estado = Mismo (Buffer 'antes' al inicio absoluto de la jornada)");
        return 'Mismo';
    }
    // Para buffer 'despues', la condición de "Mismo" si es fin de jornada absoluta O si lo que sigue es No Disponible (Cierre Sede)
    if ($posicion === 'despues') {
        if ($dt_fin_buffer == $dt_fin_jornada && $bloque_siguiente_al_buffer === null) {
            error_log("$log_prefix Estado = Mismo (Buffer 'despues' al fin absoluto de la jornada)");
            return 'Mismo';
        }
        // Si el siguiente bloque (que creará calculator.php) va a ser un "No Disponible" extendido,
        // este buffer debería ser "Mismo".
        // Esta lógica es difícil de implementar aquí sin saber explícitamente lo que calculator hará.
        // Confiamos en que si calculator.php extiende No Disponible, este bloque (que sería
        // el que termina justo en la hora de cierre de la sede) se convertirá visualmente en el
        // último antes del cierre total. Su estado aquí podría ser Mismo o Traslado.
        // Dejamos el TODO para la Fase 2.5.
    }
    // TODO: Lógica "Mismo" si adyacente es Vacío con sede única compatible (requiere info de los admisibles del Vacío).


    // PRIORIDAD 4: DEFAULT
    error_log("$log_prefix Estado por defecto = Mismo o Traslado");
    return 'Mismo o Traslado';

} // Fin mph_determinar_estado_buffer