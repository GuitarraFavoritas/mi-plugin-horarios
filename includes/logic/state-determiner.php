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
function mph_determinar_estado_buffer( $maestro_id, $dia_semana, $hora_inicio_buffer_str, $hora_fin_buffer_str, $posicion, $sede_id_clase_adyacente, $hora_inicio_jornada_str, $hora_fin_jornada_str, $hay_sede_fisica_viable_despues = true ) { // <-- Nuevo parámetro con default
    $log_prefix = "mph_determinar_estado_buffer:";
    error_log("$log_prefix Iniciando - Maestro: $maestro_id, Dia: $dia_semana, Buffer: $hora_inicio_buffer_str-$hora_fin_buffer_str, Pos: $posicion, SedeClaseAdy: $sede_id_clase_adyacente, Jornada: $hora_inicio_jornada_str-$hora_fin_jornada_str");

    $base_date = '1970-01-01 ';
    try {
        $dt_inicio_buffer = new DateTime($base_date . $hora_inicio_buffer_str);
        $dt_fin_buffer = new DateTime($base_date . $hora_fin_buffer_str);
        $dt_inicio_jornada = new DateTime($base_date . $hora_inicio_jornada_str);
        $dt_fin_jornada = new DateTime($base_date . $hora_fin_jornada_str);
    } catch (Exception $e) { /* ... return ... */ }

    // --- Obtener datos de la Sede de la CLASE Adyacente (la que genera este buffer) ---
    $hora_cierre_sede_clase_ady = null; // Inicializar a null
    $es_sede_clase_ady_comun = false; // Inicializar a false
    if ( $sede_id_clase_adyacente > 0 ) {
        $hc_raw = get_term_meta( $sede_id_clase_adyacente, 'hora_cierre', true );
        if ( $hc_raw && preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $hc_raw) ) {
             $hora_cierre_sede_clase_ady = $hc_raw;
        }
        $comun_raw = get_term_meta( $sede_id_clase_adyacente, 'sede_comun', true );
        $es_sede_clase_ady_comun = !empty($comun_raw) && $comun_raw === '1';
        $term_sede_obj = get_term($sede_id_clase_adyacente); // Para log
        $nombre_sede_ady = is_wp_error($term_sede_obj) ? 'Error' : $term_sede_obj->name;
        error_log("$log_prefix Sede Clase Ady ID: $sede_id_clase_adyacente ($nombre_sede_ady) - Cierre: " . ($hora_cierre_sede_clase_ady ?? 'N/A') . " - Común: " . ($es_sede_clase_ady_comun ? 'Sí' : 'No'));
    }

    // --- Obtener TODOS los bloques del día para encontrar vecinos reales en la BD ---
    // Esta consulta es ahora mucho más útil con la Actualización Inteligente.
    $todos_horarios_dia = mph_get_horarios_existentes_dia( $maestro_id, $dia_semana );
    error_log("$log_prefix Verificando contexto con " . count($todos_horarios_dia) . " horarios existentes.");

    $bloque_siguiente_al_buffer = null;
    $bloque_anterior_al_buffer = null;
    foreach ($todos_horarios_dia as $h_vecino) {
        $inicio_vecino_str = get_post_meta($h_vecino->ID, 'mph_hora_inicio', true);
        $fin_vecino_str = get_post_meta($h_vecino->ID, 'mph_hora_fin', true);
        if (!$inicio_vecino_str || !$fin_vecino_str) continue;
        try {
            $dt_inicio_vecino = new DateTime($base_date . $inicio_vecino_str);
            $dt_fin_vecino = new DateTime($base_date . $fin_vecino_str);
            if ($dt_inicio_vecino == $dt_fin_buffer) { $bloque_siguiente_al_buffer = $h_vecino; }
            if ($dt_fin_vecino == $dt_inicio_buffer) { $bloque_anterior_al_buffer = $h_vecino; }
        } catch (Exception $e) { continue; }
    }

    if ($bloque_anterior_al_buffer) error_log("$log_prefix Bloque anterior encontrado: ID " . $bloque_anterior_al_buffer->ID . " (" . get_post_meta($bloque_anterior_al_buffer->ID, 'mph_estado', true) . ")");
    if ($bloque_siguiente_al_buffer) error_log("$log_prefix Bloque siguiente encontrado: ID " . $bloque_siguiente_al_buffer->ID . " (" . get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_estado', true) . ")");

    // PRIORIDAD 1: TRASLADO / NO DISPONIBLE POR CIERRE (para buffer 'despues')
    if ( $posicion === 'despues' && !$es_sede_clase_ady_comun && $hora_cierre_sede_clase_ady ) {
        try {
            $dt_hora_cierre_ady = new DateTime('1970-01-01 ' . $hora_cierre_sede_clase_ady);
            // Si este buffer empieza EN o DESPUÉS del cierre de la sede adyacente
            if ($dt_inicio_buffer >= $dt_hora_cierre_ady) {
                if ($hay_sede_fisica_viable_despues) { // <-- Usar el nuevo parámetro
                    error_log("$log_prefix Estado = Traslado (Sede Ady cerró, pero hay sedes físicas viables después).");
                    return 'Traslado'; // Escenario 2.2
                } else {
                    error_log("$log_prefix Estado = No Disponible (Sede Ady cerró, y NO hay sedes físicas viables después).");
                    return 'No Disponible'; // Escenario 2.3
                }
            }
        } catch (Exception $e) { /* ... */ }
    }

    // PRIORIDAD 2: NO DISPONIBLE (CIERRE SEDE) o TRASLADO POR CIERRE
    if ( $posicion === 'despues' && !$es_sede_clase_ady_comun && $hora_cierre_sede_clase_ady ) {
        try {
            $dt_hora_cierre_ady = new DateTime($base_date . $hora_cierre_sede_clase_ady);

            if ($dt_inicio_buffer >= $dt_hora_cierre_ady) { // Buffer empieza EN o DESPUÉS del cierre
                error_log("$log_prefix Buffer ($hora_inicio_buffer_str-$hora_fin_buffer_str) afectado por cierre de SedeAdy ($hora_cierre_sede_clase_ady).");
                // ¿Hay tiempo DESPUÉS de este buffer en la jornada Y sedes físicas viables?
                if ($dt_fin_buffer < $dt_fin_jornada) {
                    // Necesitamos los ADMISIBLES GENERALES para este maestro
                    // Esto es una limitación aquí, ya que esta función no los tiene.
                    // Solución temporal para la prueba 2.2: si hay tiempo después, es Traslado.
                    // Esto se volverá más preciso cuando 'calculator' pueda informar mejor el contexto
                    // o cuando la 'actualización inteligente' permita consultar el 'Vacío' siguiente real.
                    // Si mph_get_horarios_existentes_dia devolviera el futuro 'Vacío' (en una actualización inteligente),
                    // podríamos verificar sus sedes aquí.

                    // Lógica simplificada: si hay tiempo, asumimos que podría haber otra sede.
                    // Esto resultará en 'Traslado' para 2.2. Para 2.3 (solo Online), calculator.php
                    // debe haber extendido el 'No Disponible' anterior para cubrir este slot.
                    // Por lo tanto, si llegamos aquí, es porque calculator NO extendió, lo que implica que
                    // SÍ había un Vacío con sedes físicas después.
                    $siguiente_bloque_vacio_con_fisica = false; // Placeholder para lógica más avanzada
                    // Para simular la decisión de calculator.php:
                    // Si calculator creó un Vacío después de este buffer, y ese vacío tiene sedes físicas.
                    // Por ahora, si hay tiempo y no es el último slot, decimos traslado.
                    // Esto necesita que calculator.php sea la fuente de verdad para "extender No Disponible".

                    // Si este bloque es la Parte B (después del cierre) de un buffer dividido por calculator.php:
                    // Y el siguiente bloque que *creará* calculator.php es un 'Vacío' con sedes físicas.
                    if ($bloque_siguiente_al_buffer) { // Con Actualización Inteligente esto funcionará
                        $estado_siguiente = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_estado', true);
                        if ($estado_siguiente === 'Vacío') {
                             $sedes_vacio = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_sedes_admisibles', true);
                             $sedes_vacio_arr = !empty($sedes_vacio) ? explode(',', $sedes_vacio) : [];
                             $sedes_vacio_filtradas = mph_get_filtered_admisibles_sedes($sedes_vacio_arr, get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_hora_inicio', true));
                             foreach($sedes_vacio_filtradas as $s_id) { if(!get_term_meta($s_id, 'sede_comun', true)) {$siguiente_bloque_vacio_con_fisica = true; break;}}
                        }
                    } else { // Si estamos en "Borrar y Recrear", asumimos que calculator decidirá
                        // si el siguiente Vacío tiene físicas. Si este buffer NO es el último de la jornada,
                        // y calculator NO lo va a fusionar con un No Disponible extendido, debe ser Traslado.
                        if ($dt_fin_buffer < $dt_fin_jornada) {
                            // Esta es una suposición para hacer funcionar el escenario 2.2.
                            // La decisión final de si el siguiente Vacío tiene físicas o no
                            // y por ende si este bloque es Traslado o No Disponible, la tiene calculator.php
                            // al decidir si extender el 'No Disponible' o crear un 'Vacío'.
                            // Si calculator.php decide crear un Vacío después de este buffer,
                            // y ese Vacío tiene sedes físicas, este DEBE ser Traslado.
                            // Para simplificar aquí, si no es fin de jornada, asumimos Traslado.
                             $siguiente_bloque_vacio_con_fisica = true;
                        }
                    }

                    if ($siguiente_bloque_vacio_con_fisica) {
                        error_log("$log_prefix Estado = Traslado (Sede Ady cerró, pero hay Vacío viable después)");
                        return 'Traslado';
                    } else {
                        error_log("$log_prefix Estado = No Disponible (Sede Ady cerró, y NO hay Vacío viable después o es fin de jornada)");
                        return 'No Disponible';
                    }
                } else { // El buffer termina exactamente con la jornada.
                    error_log("$log_prefix Estado = No Disponible (Cierre Sede Ady y fin de jornada).");
                    return 'No Disponible';
                }
            }
            // Si el cierre es DURANTE este buffer, calculator.php lo dividió. Esta función evalúa cada parte.
            // La parte ANTES del cierre (ej. 15:30-16:00) NO entrará en el if de arriba ($dt_inicio_buffer < $dt_hora_cierre_ady).
            // Pasará a las reglas de Mismo/Mismo o Traslado.
        } catch (Exception $e) { error_log("$log_prefix Error en lógica cierre (P2): " . $e->getMessage()); }
    }


    // PRIORIDAD 3: MISMO
    /* Lógica de "Mismo" con Vacío Adyacente Compatible */
    try {
        // 3a. Límite absoluto de jornada
        if ( ($posicion === 'antes' && $dt_inicio_buffer == $dt_inicio_jornada) ) {
            // Nota: Con la Actualización Inteligente, un bloque que no es el primero podría ser contiguo al inicio de jornada
            // si se borra el intermedio, así que eliminamos la comprobación de $bloque_anterior_al_buffer === null
             error_log("$log_prefix Estado = Mismo (Buffer 'antes' al inicio absoluto de jornada)");
             return 'Mismo';
        }
        if ( ($posicion === 'despues' && $dt_fin_buffer == $dt_fin_jornada) ) {
            error_log("$log_prefix Estado = Mismo (Buffer 'despues' al fin absoluto de jornada)");
            return 'Mismo';
        }

        // 3b. Sucedido por 'No Disponible (Cierre Sede)' - Importante para Escenario 2.3
        if ($posicion === 'despues' && $bloque_siguiente_al_buffer) {
             $estado_siguiente = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_estado', true);
             if ($estado_siguiente === 'No Disponible') {
                 error_log("$log_prefix Estado = Mismo (Sucedido por 'No Disponible')");
                 return 'Mismo';
             }
        }

        // 3c. Adyacente a un Vacío con Sede Única Compatible
        if ($posicion === 'antes' && $bloque_anterior_al_buffer) {
            $estado_anterior = get_post_meta($bloque_anterior_al_buffer->ID, 'mph_estado', true);
            // Si el bloque anterior es un tipo de "disponibilidad" (no una clase)
            if (in_array($estado_anterior, ['Vacío', 'Mismo', 'Mismo o Traslado'])) {
                error_log("$log_prefix Buffer 'antes' es antecedido por Bloque Abierto (ID: {$bloque_anterior_al_buffer->ID}, Estado: $estado_anterior). Verificando sedes...");
                $sedes_admisibles_vecino_str = get_post_meta($bloque_anterior_al_buffer->ID, 'mph_sedes_admisibles', true);
                $hora_inicio_vecino_str = get_post_meta($bloque_anterior_al_buffer->ID, 'mph_hora_inicio', true);
                if ($sedes_admisibles_vecino_str && $hora_inicio_vecino_str) {
                    $sedes_vecino_ids = !empty($sedes_admisibles_vecino_str) ? explode(',', $sedes_admisibles_vecino_str) : array();
                    $sedes_vecino_filtradas = mph_get_filtered_admisibles_sedes($sedes_vecino_ids, $hora_inicio_vecino_str);

                    $solo_sede_adyacente_o_comunes = true;
                    if (empty($sedes_vecino_filtradas)) {
                        $solo_sede_adyacente_o_comunes = false; // Si no quedan sedes, no es compatible para Mismo
                    } else {
                        foreach ($sedes_vecino_filtradas as $id_sede_vecino) {
                            // Si hay otra sede física NO común que NO sea la sede de nuestra clase adyacente
                            if ($id_sede_vecino != $sede_id_clase_adyacente && !get_term_meta($id_sede_vecino, 'sede_comun', true)) {
                                $solo_sede_adyacente_o_comunes = false;
                                break;
                            }
                        }
                    }
                    if ($solo_sede_adyacente_o_comunes) {
                        error_log("$log_prefix Estado = Mismo (Buffer 'antes' antecedido por bloque con sede única compatible).");
                        return 'Mismo';
                    } else {
                         error_log("$log_prefix Vecino anterior tiene múltiples sedes físicas no comunes. No es 'Mismo'.");
                    }
                }
            }
        }

        if ($posicion === 'despues' && $bloque_siguiente_al_buffer) {
            $estado_siguiente = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_estado', true);
            if (in_array($estado_siguiente, ['Vacío', 'Mismo', 'Mismo o Traslado'])) {
                error_log("$log_prefix Buffer 'despues' es sucedido por Bloque Abierto (ID: {$bloque_siguiente_al_buffer->ID}, Estado: $estado_siguiente). Verificando sedes...");
                $sedes_admisibles_vecino_str = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_sedes_admisibles', true);
                $hora_inicio_vecino_str = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_hora_inicio', true);
                if ($sedes_admisibles_vecino_str && $hora_inicio_vecino_str) {
                    $sedes_vecino_ids = !empty($sedes_admisibles_vecino_str) ? explode(',', $sedes_admisibles_vecino_str) : array();
                    $sedes_vecino_filtradas = mph_get_filtered_admisibles_sedes($sedes_vecino_ids, $hora_inicio_vecino_str);

                    $solo_sede_adyacente_o_comunes = true;
                     if (empty($sedes_vecino_filtradas)) {
                        $solo_sede_adyacente_o_comunes = false;
                    } else {
                        foreach ($sedes_vecino_filtradas as $id_sede_vecino) {
                            if ($id_sede_vecino != $sede_id_clase_adyacente && !get_term_meta($id_sede_vecino, 'sede_comun', true)) {
                                $solo_sede_adyacente_o_comunes = false;
                                break;
                            }
                        }
                    }
                    if ($solo_sede_adyacente_o_comunes) {
                        error_log("$log_prefix Estado = Mismo (Buffer 'despues' sucedido por bloque con sede única compatible).");
                        return 'Mismo';
                    } else {
                        error_log("$log_prefix Vecino siguiente tiene múltiples sedes físicas no comunes. No es 'Mismo'.");
                    }
                }
            }
        }

    } catch (Exception $e) { error_log("$log_prefix Error en lógica 'Mismo': " . $e->getMessage()); }


    // PRIORIDAD 4: DEFAULT
    error_log("$log_prefix Estado por defecto = Mismo o Traslado");
    return 'Mismo o Traslado';

} // Fin mph_determinar_estado_buffer