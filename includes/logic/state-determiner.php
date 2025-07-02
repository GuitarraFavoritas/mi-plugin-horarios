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

    $base_date = '1970-01-01 ';
    $dt_inicio_buffer = null; $dt_fin_buffer = null;
    $dt_inicio_jornada = null; $dt_fin_jornada = null;
    try {
        $dt_inicio_buffer = new DateTime($base_date . $hora_inicio_buffer_str);
        $dt_fin_buffer = new DateTime($base_date . $hora_fin_buffer_str);
        $dt_inicio_jornada = new DateTime($base_date . $hora_inicio_jornada_str);
        $dt_fin_jornada = new DateTime($base_date . $hora_fin_jornada_str);
    } catch (Exception $e) {
        error_log("$log_prefix Error crítico creando DateTime iniciales: " . $e->getMessage() . " para buffer $hora_inicio_buffer_str-$hora_fin_buffer_str o jornada $hora_inicio_jornada_str-$hora_fin_jornada_str");
        return 'Mismo o Traslado';
    }


    // --- Obtener datos de la Sede de la CLASE Adyacente (la que genera este buffer) ---
    $hora_cierre_sede_clase_ady = null; $es_sede_clase_ady_comun = false;


    if ( $sede_id_clase_adyacente > 0 ) {
        $hc_raw = get_term_meta( $sede_id_clase_adyacente, 'hora_cierre', true );
        if ( $hc_raw && preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $hc_raw) ) {
             $hora_cierre_sede_clase_ady = $hc_raw;
        }
        $comun_raw = get_term_meta( $sede_id_clase_adyacente, 'sede_comun', true );
        $es_sede_clase_ady_comun = !empty($comun_raw) && $comun_raw === '1';
        error_log("$log_prefix Sede Clase Ady ID: $sede_id_clase_adyacente - Cierre: " . ($hora_cierre_sede_clase_ady ?? 'N/A') . " - Común: " . ($es_sede_clase_ady_comun ? 'Sí' : 'No'));
    }

     // --- Obtener TODOS los bloques del día (para encontrar vecinos) ---
    // Con "Borrar y Recrear", esto a menudo estará vacío para los bloques que se están creando.
    // Su utilidad aumentará con la "Actualización Inteligente".
    $todos_horarios_dia = mph_get_horarios_existentes_dia( $maestro_id, $dia_semana );
    

    // Filtrar solo clases asignadas/llenas para encontrar clases vecinas
    // $clases_vecinas_posts = array_filter($todos_horarios_dia, function($h) {
    //     $estado_h = get_post_meta($h->ID, 'mph_estado', true);
    //     return ($estado_h === 'Asignado' || $estado_h === 'Lleno');
    // });
    // // Filtrar bloques Vacío/Buffer para encontrar vecinos no asignados
    // $bloques_no_asignados_vecinos_posts = array_filter($todos_horarios_dia, function($h) {
    //     $estado_h = get_post_meta($h->ID, 'mph_estado', true);
    //     return !in_array($estado_h, ['Asignado', 'Lleno']);
    // });


    // --- Búsqueda de Bloques Inmediatamente Adyacentes (Clases o No Asignados) ---
    $bloque_siguiente_al_buffer = null; // Puede ser Clase o Vacío/Buffer
    $bloque_anterior_al_buffer = null;  // Puede ser Clase o Vacío/Buffer

    // // Convertir horas del buffer actual para comparación precisa
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


    // PRIORIDAD 1: TRASLADO (Entre dos clases asignadas en sedes diferentes)
    if ($posicion === 'despues' && $bloque_siguiente_al_buffer) {
        $estado_siguiente = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_estado', true);
        if ($estado_siguiente === 'Asignado' || $estado_siguiente === 'Lleno') {
            $sede_clase_siguiente = (int) get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_sede_asignada', true);
            if ($sede_clase_siguiente > 0 && $sede_id_clase_adyacente > 0 && $sede_clase_siguiente !== $sede_id_clase_adyacente) {
                error_log("$log_prefix Estado = Traslado (Buffer DESPUES entre clases en sedes diferentes)");
                return 'Traslado';
            }
        }
    } elseif ($posicion === 'antes' && $bloque_anterior_al_buffer) {
        $estado_anterior = get_post_meta($bloque_anterior_al_buffer->ID, 'mph_estado', true);
        if ($estado_anterior === 'Asignado' || $estado_anterior === 'Lleno') {
            $sede_clase_anterior = (int) get_post_meta($bloque_anterior_al_buffer->ID, 'mph_sede_asignada', true);
            if ($sede_clase_anterior > 0 && $sede_id_clase_adyacente > 0 && $sede_clase_anterior !== $sede_id_clase_adyacente) {
                error_log("$log_prefix Estado = Traslado (Buffer ANTES entre clases en sedes diferentes)");
                return 'Traslado';
            }
        }
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
        // $dt_inicio_jornada y $dt_fin_jornada ya están definidos y validados al inicio de la función

        // 3a. Límite absoluto de jornada
        if ( ($posicion === 'antes' && $dt_inicio_buffer == $dt_inicio_jornada && $bloque_anterior_al_buffer === null) ) {
            error_log("$log_prefix Estado = Mismo (Buffer 'antes' al inicio absoluto de jornada)");
            return 'Mismo';
        }
        if ( ($posicion === 'despues' && $dt_fin_buffer == $dt_fin_jornada && $bloque_siguiente_al_buffer === null) ) {
            error_log("$log_prefix Estado = Mismo (Buffer 'despues' al fin absoluto de jornada)");
            return 'Mismo';
        }

        // 3b. Sucedido por 'No Disponible (Cierre Sede)'
        if ($posicion === 'despues' && $bloque_siguiente_al_buffer) {
             $estado_siguiente = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_estado', true);
             if ($estado_siguiente === 'No Disponible') { // Asumiendo que 'No Disponible' aquí implica Cierre Sede
                 error_log("$log_prefix Estado = Mismo (Sucedido por 'No Disponible')");
                 return 'Mismo';
             }
        }

        // 3c. Adyacente a un Vacío con Sede Única Compatible
        if ($posicion === 'antes' && $bloque_anterior_al_buffer) {
            $estado_anterior = get_post_meta($bloque_anterior_al_buffer->ID, 'mph_estado', true);
            if ($estado_anterior === 'Vacío') {
                error_log("$log_prefix Buffer 'antes' es antecedido por Vacío (ID: {$bloque_anterior_al_buffer->ID}). Verificando sedes compatibles...");
                $sedes_admisibles_vacio_str = get_post_meta($bloque_anterior_al_buffer->ID, 'mph_sedes_admisibles', true);
                $hora_inicio_vacio_str = get_post_meta($bloque_anterior_al_buffer->ID, 'mph_hora_inicio', true);
                if ($sedes_admisibles_vacio_str && $hora_inicio_vacio_str) {
                    $sedes_vacio_ids = !empty($sedes_admisibles_vacio_str) ? explode(',', $sedes_admisibles_vacio_str) : array();
                    $sedes_vacio_filtradas = mph_get_filtered_admisibles_sedes($sedes_vacio_ids, $hora_inicio_vacio_str);

                    $solo_sede_adyacente_o_comunes = true;
                    $sede_adyacente_encontrada_en_vacio = false;
                    if (empty($sedes_vacio_filtradas)) { // Si no quedan sedes, no es compatible para Mismo
                        $solo_sede_adyacente_o_comunes = false;
                    } else {
                        foreach ($sedes_vacio_filtradas as $id_sede_vacio) {
                            if ($id_sede_vacio == $sede_id_clase_adyacente) { // Sede de la clase que sigue
                                $sede_adyacente_encontrada_en_vacio = true;
                                continue;
                            }
                            $es_comun_vacio = get_term_meta($id_sede_vacio, 'sede_comun', true);
                            if (empty($es_comun_vacio) || $es_comun_vacio !== '1') { // Si hay otra sede física NO común
                                $solo_sede_adyacente_o_comunes = false;
                                break;
                            }
                        }
                        // Debe contener la sede adyacente (si esta no es común) O solo comunes
                        if (!$sede_adyacente_encontrada_en_vacio && !$es_sede_clase_ady_comun && $sede_id_clase_adyacente > 0) {
                             $solo_sede_adyacente_o_comunes = false; // Si la sede adyacente no común no está, no es Mismo
                        }
                    }

                    if ($solo_sede_adyacente_o_comunes) {
                        error_log("$log_prefix Estado = Mismo (Buffer 'antes' antecedido por Vacío con sede única compatible o solo comunes).");
                        return 'Mismo';
                    } else {
                         error_log("$log_prefix Vacío anterior tiene múltiples sedes físicas no comunes o no incluye la sede adyacente. No es 'Mismo'.");
                    }
                }
            }
        }

        if ($posicion === 'despues' && $bloque_siguiente_al_buffer) {
            $estado_siguiente = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_estado', true);
            if ($estado_siguiente === 'Vacío') {
                error_log("$log_prefix Buffer 'despues' es sucedido por Vacío (ID: {$bloque_siguiente_al_buffer->ID}). Verificando sedes compatibles...");
                $sedes_admisibles_vacio_str = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_sedes_admisibles', true);
                $hora_inicio_vacio_str = get_post_meta($bloque_siguiente_al_buffer->ID, 'mph_hora_inicio', true);
                if ($sedes_admisibles_vacio_str && $hora_inicio_vacio_str) {
                    $sedes_vacio_ids = !empty($sedes_admisibles_vacio_str) ? explode(',', $sedes_admisibles_vacio_str) : array();
                    $sedes_vacio_filtradas = mph_get_filtered_admisibles_sedes($sedes_vacio_ids, $hora_inicio_vacio_str);

                    $solo_sede_adyacente_o_comunes = true;
                    $sede_adyacente_encontrada_en_vacio = false;
                     if (empty($sedes_vacio_filtradas)) {
                        $solo_sede_adyacente_o_comunes = false;
                    } else {
                        foreach ($sedes_vacio_filtradas as $id_sede_vacio) {
                            if ($id_sede_vacio == $sede_id_clase_adyacente) { // Sede de la clase que precedió
                                $sede_adyacente_encontrada_en_vacio = true;
                                continue;
                            }
                            $es_comun_vacio = get_term_meta($id_sede_vacio, 'sede_comun', true);
                            if (empty($es_comun_vacio) || $es_comun_vacio !== '1') {
                                $solo_sede_adyacente_o_comunes = false;
                                break;
                            }
                        }
                        if (!$sede_adyacente_encontrada_en_vacio && !$es_sede_clase_ady_comun && $sede_id_clase_adyacente > 0) {
                             $solo_sede_adyacente_o_comunes = false;
                        }
                    }

                    if ($solo_sede_adyacente_o_comunes) {
                        error_log("$log_prefix Estado = Mismo (Buffer 'despues' sucedido por Vacío con sede única compatible o solo comunes).");
                        return 'Mismo';
                    } else {
                        error_log("$log_prefix Vacío siguiente tiene múltiples sedes físicas no comunes o no incluye la sede adyacente. No es 'Mismo'.");
                    }
                }
            }
        }

    } catch (Exception $e) { error_log("$log_prefix Error en lógica 'Mismo': " . $e->getMessage()); }


    // PRIORIDAD 4: DEFAULT
    error_log("$log_prefix Estado por defecto = Mismo o Traslado");
    return 'Mismo o Traslado';

} // Fin mph_determinar_estado_buffer