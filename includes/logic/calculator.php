<?php
/**
 * Lógica Principal para Calcular y Dividir Bloques de Horario.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function mph_calcular_bloques_horario( $maestro_id, $data ) {
    $log_prefix = "mph_calcular_bloques_horario:";
    error_log("$log_prefix Iniciando - Maestro ID: $maestro_id, Datos: " . print_r($data, true));

    // --- 1. Validación Inicial y Limpieza de Datos Esenciales ---
    if ( empty( $maestro_id ) || !isset( $data['dia_semana'] ) || empty( $data['hora_inicio_general'] ) || empty( $data['hora_fin_general'] ) ) {
        error_log("$log_prefix Error - Datos insuficientes.");
        return new WP_Error( 'datos_insuficientes', __( 'Faltan datos esenciales para calcular los horarios.', 'mi-plugin-horarios' ) );
    }
    $time_regex = "/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/";
    if ( !preg_match($time_regex, $data['hora_inicio_general']) || !preg_match($time_regex, $data['hora_fin_general']) ) {
        error_log("$log_prefix Error - Formato de hora general inválido.");
        return new WP_Error( 'formato_hora_invalido', __( 'El formato de hora general es inválido.', 'mi-plugin-horarios' ) );
    }
    if ($data['hora_fin_general'] <= $data['hora_inicio_general']) {
         error_log("$log_prefix Error - Hora general fin <= inicio.");
         return new WP_Error( 'hora_fin_menor_inicio', __( 'La hora de fin general debe ser posterior a la hora de inicio.', 'mi-plugin-horarios' ) );
    }

    $dia_semana = intval( $data['dia_semana'] );
    $inicio_general_str = $data['hora_inicio_general'];
    $fin_general_str = $data['hora_fin_general'];
    $programas_admisibles = isset($data['programa_admisibles']) ? array_map('intval', (array) $data['programa_admisibles']) : array();
    $sedes_admisibles = isset($data['sede_admisibles']) ? array_map('intval', (array) $data['sede_admisibles']) : array();
    $rangos_admisibles = isset($data['rango_de_edad_admisibles']) ? array_map('intval', (array) $data['rango_de_edad_admisibles']) : array();

    if (empty($programas_admisibles) || empty($sedes_admisibles) || empty($rangos_admisibles)) {
         error_log("$log_prefix Error - Faltan selecciones de admisibilidad.");
         return new WP_Error('admisibles_faltantes', __('Debe seleccionar al menos una opción admisible para Programas, Sedes y Rangos de Edad.', 'mi-plugin-horarios'));
    }

    // --- 2. Determinar si hay una Asignación Específica ---
    $hay_asignacion = ! empty( $data['hora_inicio_asignada'] ) && ! empty( $data['hora_fin_asignada'] ) &&
                      ! empty( $data['programa_asignado'] ) && ! empty( $data['sede_asignada'] ) && ! empty( $data['rango_de_edad_asignado'] ) &&
                      preg_match($time_regex, $data['hora_inicio_asignada']) && preg_match($time_regex, $data['hora_fin_asignada']) &&
                      $data['hora_fin_asignada'] > $data['hora_inicio_asignada'];

    $bloques_resultantes = array();
    $base_date = '1970-01-01 '; // Para crear objetos DateTime

    // --- 3. Lógica de División de Tiempo ---
    if ( ! $hay_asignacion ) {
        // --- 3.a. Caso: Solo Disponibilidad General (Bloque único 'Vacío') ---
        error_log("$log_prefix Calculando bloque único 'Vacío'.");
        $sedes_admisibles_filtradas = mph_get_filtered_admisibles_sedes($sedes_admisibles, $inicio_general_str);
        if (empty($sedes_admisibles_filtradas) && !empty($sedes_admisibles)) {
            error_log("$log_prefix ADVERTENCIA: Todas las sedes admisibles generales fueron filtradas para Vacío $inicio_general_str-$fin_general_str.");
        }
        $bloque_vacio = mph_crear_sub_bloque( $maestro_id, $dia_semana, $inicio_general_str, $fin_general_str, 'Vacío', $programas_admisibles, $sedes_admisibles_filtradas, $rangos_admisibles );
        $bloques_resultantes[] = $bloque_vacio;

    } else {
        // --- 3.b. Caso: Hay Asignación Específica ---
        error_log("$log_prefix Calculando división por asignación específica.");
        $inicio_asignado_str = $data['hora_inicio_asignada'];
        $fin_asignado_str = $data['hora_fin_asignada'];
        $programa_asignado = intval($data['programa_asignado']);
        $sede_asignada = intval($data['sede_asignada']);
        $rango_asignado = intval($data['rango_de_edad_asignado']);
        $vacantes = isset($data['vacantes']) ? max(0, intval($data['vacantes'])) : 0;
        $buffer_antes_min = isset($data['buffer_minutos_antes']) ? max(0, intval($data['buffer_minutos_antes'])) : 0;
        $buffer_despues_min = isset($data['buffer_minutos_despues']) ? max(0, intval($data['buffer_minutos_despues'])) : 0;

         try {
             $dt_inicio_general = new DateTime($base_date . $inicio_general_str);
             $dt_fin_general = new DateTime($base_date . $fin_general_str);
             $dt_inicio_asignado = new DateTime($base_date . $inicio_asignado_str);
             $dt_fin_asignado = new DateTime($base_date . $fin_asignado_str);

             if ($dt_inicio_asignado < $dt_inicio_general || $dt_fin_asignado > $dt_fin_general) {
                 error_log("$log_prefix Error - Horas asignadas fuera del rango general.");
                 return new WP_Error('horas_fuera_rango', __('Las horas asignadas deben estar dentro del rango general.', 'mi-plugin-horarios'));
             }

            // --- Punto de referencia para construir bloques ---
            $punto_actual = clone $dt_inicio_general;

            // --- A. Bloque Vacío ANTES del Buffer Antes ---
            $dt_inicio_buffer_antes_calculado = clone $dt_inicio_asignado;
            if ($buffer_antes_min > 0) { $dt_inicio_buffer_antes_calculado->sub(new DateInterval("PT{$buffer_antes_min}M")); }
            $dt_inicio_buffer_antes_real = max($dt_inicio_buffer_antes_calculado, $dt_inicio_general);

            if ($dt_inicio_buffer_antes_real > $punto_actual) {
                error_log("$log_prefix Creando bloque 'Vacío' ANTES.");
                $sedes_filtradas_vacio_antes = mph_get_filtered_admisibles_sedes($sedes_admisibles, $punto_actual->format('H:i'));
                $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_inicio_buffer_antes_real->format('H:i'), 'Vacío', $programas_admisibles, $sedes_filtradas_vacio_antes, $rangos_admisibles );
                $punto_actual = clone $dt_inicio_buffer_antes_real;
            }

            // --- B. Bloque Buffer ANTES ---
            if ($punto_actual < $dt_inicio_asignado) { // Solo si hay espacio para el buffer antes
                error_log("$log_prefix Determinando estado para 'Buffer ANTES'.");
                $estado_buffer_antes = mph_determinar_estado_buffer( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_inicio_asignado->format('H:i'), 'antes', $sede_asignada, $inicio_general_str, $fin_general_str );
                $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_inicio_asignado->format('H:i'), $estado_buffer_antes, $programas_admisibles, $sedes_admisibles, $rangos_admisibles, 0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min, $sede_asignada );
                $punto_actual = clone $dt_inicio_asignado;
            }

            // --- C. Bloque ASIGNADO ---
            $estado_asignado = ($vacantes > 0) ? 'Asignado' : 'Lleno';
            error_log("$log_prefix Creando bloque '$estado_asignado'.");
            $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $dt_inicio_asignado->format('H:i'), $dt_fin_asignado->format('H:i'), $estado_asignado, $programas_admisibles, $sedes_admisibles, $rangos_admisibles, $vacantes, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min );
            $punto_actual = clone $dt_fin_asignado;


            // --- D. Bloque Buffer DESPUÉS (con posible división por cierre) ---
            if ($buffer_despues_min > 0 && $punto_actual < $dt_fin_general) { // Solo si hay buffer y espacio en jornada
                $dt_fin_buffer_potencial_original = clone $punto_actual;
                $dt_fin_buffer_potencial_original->add(new DateInterval("PT{$buffer_despues_min}M"));
                $dt_fin_buffer_real_en_jornada = min($dt_fin_buffer_potencial_original, $dt_fin_general);

                // Solo procesar si el buffer resultante tiene duración
                if ($punto_actual < $dt_fin_buffer_real_en_jornada) {
                    $hora_cierre_sede_clase = null; $es_sede_clase_comun = false;
                    if ($sede_asignada > 0) {
                        $hc_raw = get_term_meta($sede_asignada, 'hora_cierre', true);
                        if ($hc_raw && preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $hc_raw)) { $hora_cierre_sede_clase = $hc_raw; }
                        $comun_raw = get_term_meta($sede_asignada, 'sede_comun', true);
                        $es_sede_clase_comun = !empty($comun_raw) && $comun_raw === '1';
                    }
                    $dt_hora_cierre_clase = null;
                    if (!$es_sede_clase_comun && $hora_cierre_sede_clase) {
                        try { $dt_hora_cierre_clase = new DateTime($base_date . $hora_cierre_sede_clase); } catch (Exception $e) {}
                    }

                    // Caso 1: Sede de la clase cierra ANTES o AL INICIO de este buffer
                    if ($dt_hora_cierre_clase && $dt_hora_cierre_clase <= $punto_actual) { // $punto_actual es $dt_fin_asignado aquí
                        error_log("$log_prefix Sede $sede_asignada ya cerró o cierra al fin de la clase. Buffer después completo es afectado.");
                        $estado_buffer_total = mph_determinar_estado_buffer( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), 'despues', $sede_asignada, $inicio_general_str, $fin_general_str );
                        
                        // VERIFICACIÓN PARA TRASLADO (Escenario 2.2)
                        if ($estado_buffer_total === 'No Disponible' && $dt_fin_buffer_real_en_jornada < $dt_fin_jornada) {
                            $sedes_para_siguiente_vacio = mph_get_filtered_admisibles_sedes($sedes_admisibles, $dt_fin_buffer_real_en_jornada->format('H:i'));
                            $hay_fisica_siguiente = false;
                            foreach($sedes_para_siguiente_vacio as $s_id) { if(!get_term_meta($s_id, 'sede_comun', true)) {$hay_fisica_siguiente = true; break;} }
                            if ($hay_fisica_siguiente) {
                                error_log("$log_prefix CAMBIANDO estado de buffer post-cierre a TRASLADO porque hay Vacío con física después.");
                                $estado_buffer_total = 'Traslado';
                            }
                        }

                        $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), $estado_buffer_total, $programas_admisibles, $sedes_admisibles, $rangos_admisibles, 0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min, $sede_asignada );
                        $punto_actual = clone $dt_fin_buffer_real_en_jornada;
                    }
                    // Caso 2: Sede de la clase cierra DURANTE este buffer
                    elseif ($dt_hora_cierre_clase && $dt_hora_cierre_clase > $punto_actual && $dt_hora_cierre_clase < $dt_fin_buffer_real_en_jornada) {
                        error_log("$log_prefix Sede $sede_asignada cierra DURANTE el buffer después. Dividiendo buffer.");
                        // Parte A del buffer: Antes del cierre
                        $estado_buffer_parte1 = mph_determinar_estado_buffer( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_hora_cierre_clase->format('H:i'), 'despues', $sede_asignada, $inicio_general_str, $fin_general_str );
                        $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_hora_cierre_clase->format('H:i'), $estado_buffer_parte1, $programas_admisibles, $sedes_admisibles, $rangos_admisibles, 0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min, $sede_asignada );
                        // Parte B del buffer: Después del cierre
                        $estado_buffer_parte2 = mph_determinar_estado_buffer( $maestro_id, $dia_semana, $dt_hora_cierre_clase->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), 'despues', $sede_asignada, $inicio_general_str, $fin_general_str );

                        // VERIFICACIÓN PARA TRASLADO (Escenario 2.2) para Parte B
                        if ($estado_buffer_parte2 === 'No Disponible' && $dt_fin_buffer_real_en_jornada < $dt_fin_jornada) {
                            $sedes_para_siguiente_vacio = mph_get_filtered_admisibles_sedes($sedes_admisibles, $dt_fin_buffer_real_en_jornada->format('H:i'));
                            $hay_fisica_siguiente = false;
                            foreach($sedes_para_siguiente_vacio as $s_id) { if(!get_term_meta($s_id, 'sede_comun', true)) {$hay_fisica_siguiente = true; break;} }
                            if ($hay_fisica_siguiente) {
                                error_log("$log_prefix CAMBIANDO estado de Parte B del buffer a TRASLADO.");
                                $estado_buffer_parte2 = 'Traslado';
                            }
                        } 

                        $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $dt_hora_cierre_clase->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), $estado_buffer_parte2, $programas_admisibles, $sedes_admisibles, $rangos_admisibles, 0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min, $sede_asignada );
                        $punto_actual = clone $dt_fin_buffer_real_en_jornada;
                    }
                    // Caso 3: Sede de la clase no cierra o cierra después del buffer
                    else {
                        error_log("$log_prefix Sede $sede_asignada no afecta el buffer después o cierra después. Procesando buffer completo.");
                        $estado_buffer_total = mph_determinar_estado_buffer( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), 'despues', $sede_asignada, $inicio_general_str, $fin_general_str );
                        $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), $estado_buffer_total, $programas_admisibles, $sedes_admisibles, $rangos_admisibles, 0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min, $sede_asignada );
                        $punto_actual = clone $dt_fin_buffer_real_en_jornada;
                    }
                } else {
                     error_log("$log_prefix Buffer después sin duración válida en jornada.");
                }
            } else {
                 error_log("$log_prefix No hay buffer después definido (0 min) o no hay espacio.");
            }

            // --- E. Bloque Vacío DESPUÉS de todo ---
            if ($punto_actual < $dt_fin_general) {
                $sedes_filtradas_vacio_despues = mph_get_filtered_admisibles_sedes($sedes_admisibles, $punto_actual->format('H:i'));

                $solo_comunes_quedan = true;
                if (!empty($sedes_filtradas_vacio_despues)) {
                    foreach($sedes_filtradas_vacio_despues as $id_sede_filtrada) {
                        $es_comun = get_term_meta($id_sede_filtrada, 'sede_comun', true);
                        if (empty($es_comun) || $es_comun !== '1') { $solo_comunes_quedan = false; break; }
                    }
                } else { $solo_comunes_quedan = true; } // También si no queda ninguna sede

                // Obtener el último bloque creado ANTES de este potencial Vacío
                $ultimo_bloque_previo = end($bloques_resultantes);
                $estado_ultimo_bloque_previo = $ultimo_bloque_previo ? $ultimo_bloque_previo['estado'] : null;

                if ($estado_ultimo_bloque_previo === 'No Disponible' && $solo_comunes_quedan) {
                    error_log("$log_prefix Extender bloque 'No Disponible' anterior hasta el fin de jornada.");
                    $key_ultimo = array_key_last($bloques_resultantes);
                    if ($key_ultimo !== null) {
                        $bloques_resultantes[$key_ultimo]['hora_fin'] = $dt_fin_general->format('H:i');
                        $bloques_resultantes[$key_ultimo]['meta_input']['mph_hora_fin'] = $dt_fin_general->format('H:i');
                        $bloques_resultantes[$key_ultimo]['post_title'] = sprintf("Maestro %d - Día %d - %s-%s - %s", $maestro_id, $dia_semana, $bloques_resultantes[$key_ultimo]['hora_inicio'], $dt_fin_general->format('H:i'), $estado_ultimo_bloque_previo);
                    }
                } else {
                    // Crear bloque Vacío si hay sedes (físicas o comunes) o si el último no fue 'No Disponible'
                     if (!empty($sedes_filtradas_vacio_despues)) {
                         error_log("$log_prefix Creando bloque 'Vacío' DESPUES con sedes filtradas.");
                         $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_fin_general->format('H:i'), 'Vacío', $programas_admisibles, $sedes_filtradas_vacio_despues, $rangos_admisibles );
                     } else {
                         error_log("$log_prefix No se crea bloque 'Vacío' DESPUES: no hay sedes admisibles restantes (ni físicas ni comunes).");
                         // Si llegamos aquí, y el último bloque fue un buffer, ese buffer podría necesitar
                         // cambiar su estado a 'Mismo' si ahora es el fin real de la actividad.
                         // Esta lógica de re-evaluación es para la Fase 2.5 (Actualización Inteligente).
                     }
                }
            }
            /* Finaliza Modificación */

         } catch (Exception $e) {
             error_log("$log_prefix EXCEPCION al procesar fechas: " . $e->getMessage());
             return new WP_Error('fecha_invalida', __('Error al procesar las horas proporcionadas.', 'mi-plugin-horarios'));
         }
    } // Fin else $hay_asignacion

    error_log("$log_prefix Finalizando - Bloques resultantes: " . count($bloques_resultantes));
    return $bloques_resultantes;
}
?>