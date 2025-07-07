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
        // --- Caso: Solo Disponibilidad General (Dividir si es necesario) ---
        error_log("$log_prefix Calculando bloque(s) 'Vacío' para la disponibilidad general.");

        // 1. Recolectar todos los puntos de tiempo de cambio
        $puntos_de_tiempo_str = array(
            $inicio_general_str,
            $fin_general_str
        );

        error_log("$log_prefix Sedes admisibles generales para evaluar cierre: " . print_r($sedes_admisibles, true));
        // Obtener horas de cierre de las sedes no comunes
        foreach ($sedes_admisibles as $sede_id) {
            $es_comun = get_term_meta($sede_id, 'sede_comun', true);
            if (!$es_comun) {
                $hora_cierre = get_term_meta($sede_id, 'hora_cierre', true);
                if ($hora_cierre && preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $hora_cierre)) {
                    // Añadir hora de cierre solo si está DENTRO del rango general
                    if ($hora_cierre > $inicio_general_str && $hora_cierre < $fin_general_str) {
                         $puntos_de_tiempo_str[] = $hora_cierre;
                         error_log("$log_prefix Añadido punto de cambio por cierre de Sede ID $sede_id a las $hora_cierre.");
                    }
                }
            }
        }

        // 2. Ordenar y hacer únicos los puntos de tiempo
        $puntos_de_tiempo_str = array_unique($puntos_de_tiempo_str);
        usort($puntos_de_tiempo_str, 'strcmp');
        error_log("$log_prefix Puntos de tiempo finales para división: " . print_r($puntos_de_tiempo_str, true));

        // 3. Crear un bloque 'Vacío' para cada intervalo resultante
        for ($i = 0; $i < count($puntos_de_tiempo_str) - 1; $i++) {
            $intervalo_inicio = $puntos_de_tiempo_str[$i];
            $intervalo_fin = $puntos_de_tiempo_str[$i+1];

            if ($intervalo_inicio >= $intervalo_fin) continue; // Saltar intervalos sin duración

            error_log("$log_prefix Procesando intervalo Vacío: $intervalo_inicio - $intervalo_fin");

            // 4. Filtrar sedes admisibles para ESTE intervalo específico
            $sedes_admisibles_para_intervalo = mph_get_filtered_admisibles_sedes($sedes_admisibles, $intervalo_inicio);

            // 5. Crear el bloque solo si hay sedes admisibles para él
            if (!empty($sedes_admisibles_para_intervalo)) {
                $bloques_resultantes[] = mph_crear_sub_bloque(
                    $maestro_id, $dia_semana,
                    $intervalo_inicio, $intervalo_fin,
                    'Vacío',
                    $programas_admisibles,
                    $sedes_admisibles_para_intervalo,
                    $rangos_admisibles
                );
            } else {
                 error_log("$log_prefix Saltando intervalo $intervalo_inicio - $intervalo_fin: no quedaron sedes admisibles después de filtrar por hora de cierre.");
            }
        }

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

            // --- A. Bloque(s) Vacío ANTES del Buffer Antes ---
            $dt_inicio_buffer_antes_calculado = clone $dt_inicio_asignado;
            if ($buffer_antes_min > 0) { $dt_inicio_buffer_antes_calculado->sub(new DateInterval("PT{$buffer_antes_min}M")); }
            $dt_inicio_buffer_antes_real = max($dt_inicio_buffer_antes_calculado, $dt_inicio_general);

            if ($dt_inicio_buffer_antes_real > $punto_actual) {
                error_log("$log_prefix Generando bloque(s) 'Vacío' ANTES del buffer.");
                // Aplicar misma lógica de división que para un Vacío general
                $puntos_cambio_vacio_antes = array(
                    $punto_actual->format('H:i'),
                    $dt_inicio_buffer_antes_real->format('H:i')
                );
                foreach ($sedes_admisibles as $sede_id) {
                    $es_comun = get_term_meta($sede_id, 'sede_comun', true);
                    if (!$es_comun) {
                        $hora_cierre = get_term_meta($sede_id, 'hora_cierre', true);
                        if ($hora_cierre && $hora_cierre > $punto_actual->format('H:i') && $hora_cierre < $dt_inicio_buffer_antes_real->format('H:i')) {
                            $puntos_cambio_vacio_antes[] = $hora_cierre;
                        }
                    }
                }
                $puntos_cambio_vacio_antes = array_unique($puntos_cambio_vacio_antes);
                usort($puntos_cambio_vacio_antes, 'strcmp');

                for ($i = 0; $i < count($puntos_cambio_vacio_antes) - 1; $i++) {
                    $intervalo_inicio = $puntos_cambio_vacio_antes[$i];
                    $intervalo_fin = $puntos_cambio_vacio_antes[$i+1];
                    if ($intervalo_inicio >= $intervalo_fin) continue;

                    $sedes_filtradas = mph_get_filtered_admisibles_sedes($sedes_admisibles, $intervalo_inicio);
                    if (!empty($sedes_filtradas)) {
                        $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $intervalo_inicio, $intervalo_fin, 'Vacío', $programas_admisibles, $sedes_filtradas, $rangos_admisibles );
                    }
                }
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
            if ($buffer_despues_min > 0 && $punto_actual < $dt_fin_general) {
                $dt_fin_buffer_potencial_original = clone $punto_actual;
                $dt_fin_buffer_potencial_original->add(new DateInterval("PT{$buffer_despues_min}M"));
                $dt_fin_buffer_real_en_jornada = min($dt_fin_buffer_potencial_original, $dt_fin_general);

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

                    // Caso 1: Sede cierra DURANTE este buffer -> DIVIDIR
                    if ($dt_hora_cierre_clase && $dt_hora_cierre_clase > $punto_actual && $dt_hora_cierre_clase < $dt_fin_buffer_real_en_jornada) {
                        error_log("$log_prefix Sede $sede_asignada cierra DURANTE el buffer después. Dividiendo buffer.");

                        // Parte A del buffer (antes del cierre)
                        $hay_fisica_despues_parte_A = true;
                        $estado_buffer_parte1 = mph_determinar_estado_buffer( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_hora_cierre_clase->format('H:i'), 'despues', $sede_asignada, $inicio_general_str, $fin_general_str, $hay_fisica_despues_parte_A );
                        $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_hora_cierre_clase->format('H:i'), $estado_buffer_parte1, $programas_admisibles, $sedes_admisibles, $rangos_admisibles, 0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min, $sede_asignada );

                        // Parte B del buffer (después del cierre)
                        $hay_fisica_despues_parte_B = false;
                        if ($dt_fin_buffer_real_en_jornada < $dt_fin_general) {
                            $sedes_para_siguiente_vacio = mph_get_filtered_admisibles_sedes($sedes_admisibles, $dt_fin_buffer_real_en_jornada->format('H:i'));
                            foreach($sedes_para_siguiente_vacio as $s_id) { if(!get_term_meta($s_id, 'sede_comun', true)) {$hay_fisica_despues_parte_B = true; break;} }
                        }
                        $estado_buffer_parte2 = mph_determinar_estado_buffer( $maestro_id, $dia_semana, $dt_hora_cierre_clase->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), 'despues', $sede_asignada, $inicio_general_str, $fin_general_str, $hay_fisica_despues_parte_B );
                        $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $dt_hora_cierre_clase->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), $estado_buffer_parte2, $programas_admisibles, $sedes_admisibles, $rangos_admisibles, 0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min, $sede_asignada );
                        
                        $punto_actual = clone $dt_fin_buffer_real_en_jornada;

                    } else { // Caso 2: El buffer NO se divide por cierre
                        error_log("$log_prefix Buffer después no se divide por cierre. Procesando buffer completo.");
                        $hay_fisica_despues = false;
                        if ($dt_fin_buffer_real_en_jornada < $dt_fin_general) {
                            $sedes_para_siguiente_vacio = mph_get_filtered_admisibles_sedes($sedes_admisibles, $dt_fin_buffer_real_en_jornada->format('H:i'));
                            foreach($sedes_para_siguiente_vacio as $s_id) { if(!get_term_meta($s_id, 'sede_comun', true)) {$hay_fisica_despues = true; break;} }
                        }
                        $estado_buffer_total = mph_determinar_estado_buffer( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), 'despues', $sede_asignada, $inicio_general_str, $fin_general_str, $hay_fisica_despues );
                        $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $punto_actual->format('H:i'), $dt_fin_buffer_real_en_jornada->format('H:i'), $estado_buffer_total, $programas_admisibles, $sedes_admisibles, $rangos_admisibles, 0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min, $sede_asignada );
                        $punto_actual = clone $dt_fin_buffer_real_en_jornada;
                    }
                }
            }                  

            // --- E. Bloque(s) Vacío DESPUÉS de todo ---
            if ($punto_actual < $dt_fin_general) {
                error_log("$log_prefix Generando bloque(s) 'Vacío' DESPUES.");
                $puntos_cambio_vacio_despues = array(
                    $punto_actual->format('H:i'),
                    $dt_fin_general->format('H:i')
                );
                foreach ($sedes_admisibles as $sede_id) {
                    $es_comun = get_term_meta($sede_id, 'sede_comun', true);
                    if (!$es_comun) {
                        $hora_cierre = get_term_meta($sede_id, 'hora_cierre', true);
                        if ($hora_cierre && $hora_cierre > $punto_actual->format('H:i') && $hora_cierre < $dt_fin_general->format('H:i')) {
                            $puntos_cambio_vacio_despues[] = $hora_cierre;
                        }
                    }
                }
                $puntos_cambio_vacio_despues = array_unique($puntos_cambio_vacio_despues);
                usort($puntos_cambio_vacio_despues, 'strcmp');

                for ($i = 0; $i < count($puntos_cambio_vacio_despues) - 1; $i++) {
                    $intervalo_inicio = $puntos_cambio_vacio_despues[$i];
                    $intervalo_fin = $puntos_cambio_vacio_despues[$i+1];
                    if ($intervalo_inicio >= $intervalo_fin) continue;

                    $sedes_filtradas = mph_get_filtered_admisibles_sedes($sedes_admisibles, $intervalo_inicio);

                    // Lógica para extender No Disponible anterior (si aplica)
                    $ultimo_bloque_creado = end($bloques_resultantes);
                    $estado_ultimo_bloque = $ultimo_bloque_creado ? $ultimo_bloque_creado['estado'] : null;
                    $solo_comunes_quedan = true;
                    if (!empty($sedes_filtradas)) {
                        foreach($sedes_filtradas as $id_sede_filtrada) { if (!get_term_meta($id_sede_filtrada, 'sede_comun', true)) { $solo_comunes_quedan = false; break; } }
                    }

                    if ($estado_ultimo_bloque === 'No Disponible' && $solo_comunes_quedan) {
                         // Extender
                        error_log("$log_prefix Extender bloque 'No Disponible' anterior para cubrir intervalo $intervalo_inicio-$intervalo_fin.");
                        $key_ultimo = array_key_last($bloques_resultantes);
                        if ($key_ultimo !== null) {
                            $bloques_resultantes[$key_ultimo]['hora_fin'] = $intervalo_fin;
                            $bloques_resultantes[$key_ultimo]['meta_input']['mph_hora_fin'] = $intervalo_fin;
                            $bloques_resultantes[$key_ultimo]['post_title'] = sprintf("Maestro %d - Día %d - %s-%s - %s", $maestro_id, $dia_semana, $bloques_resultantes[$key_ultimo]['hora_inicio'], $dt_fin_general->format('H:i'), $estado_ultimo_bloque_previo);

                    // El bloque anterior al que acabamos de extender es ahora el penúltimo del array.
                    $key_penultimo = count($bloques_resultantes) >= 2 ? array_keys($bloques_resultantes)[count($bloques_resultantes)-2] : null;

                    if ($key_penultimo !== null) {
                        // Si el penúltimo bloque es un 'Mismo o Traslado', ahora debe ser 'Mismo'
                        // porque lo que le sigue es un cierre definitivo de actividad.
                        if ($bloques_resultantes[$key_penultimo]['estado'] === 'Mismo o Traslado') {
                             error_log("$log_prefix Re-evaluando estado del bloque penúltimo (ID temporal) a 'Mismo' porque es sucedido por 'No Disponible' extendido.");
                             $bloques_resultantes[$key_penultimo]['estado'] = 'Mismo';
                             $bloques_resultantes[$key_penultimo]['meta_input']['mph_estado'] = 'Mismo';
                             // Actualizar título también
                             $bloques_resultantes[$key_penultimo]['post_title'] = sprintf("Maestro %d - Día %d - %s-%s - %s",
                                $maestro_id, $dia_semana,
                                $bloques_resultantes[$key_penultimo]['hora_inicio'],
                                $bloques_resultantes[$key_penultimo]['hora_fin'],
                                'Mismo'
                            );
                        }
                    }
                }
                } elseif (!empty($sedes_filtradas)) {
                        // Crear Vacío normal
                        $bloques_resultantes[] = mph_crear_sub_bloque( $maestro_id, $dia_semana, $intervalo_inicio, $intervalo_fin, 'Vacío', $programas_admisibles, $sedes_filtradas, $rangos_admisibles );
                    }
                }
            }

         } catch (Exception $e) {
             error_log("$log_prefix EXCEPCION al procesar fechas: " . $e->getMessage());
             return new WP_Error('fecha_invalida', __('Error al procesar las horas proporcionadas.', 'mi-plugin-horarios'));
         }
    } // Fin else $hay_asignacion

    error_log("$log_prefix Finalizando - Bloques resultantes: " . count($bloques_resultantes));
    return $bloques_resultantes;
}
?>