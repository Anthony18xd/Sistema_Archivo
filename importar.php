<?php
/**
 * ARCHIVO: importar.php
 * SCRIPT ETL DE IMPORTACION MASIVA - FASE 1
 *
 * Lee archivos Excel ( PhpSpreadsheet ) e importa datos a:
 *   - tomos (cabeceras de pestañas tipo PARA WEB / Hoja1)
 *   - documentos_fase1 (filas de pestañas tipo A-001..A-036, CARTAS, Ings)
 *   - documento_expedientes (descomposicion de cadenas multiples)
 *
 * Ejecucion:
 *   CLI:   php importar.php /ruta/archivo.xlsx
 *   Web:   Abrir importar.php en navegador (interfaz con formulario)
 *
 * Requisitos: composer require phpoffice/phpspreadsheet
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Pestanas que representan cabecera de tomo y pestanas de documentos.
// Se definen a nivel global para que funcionen tanto en CLI como en Web.
$hojas_cabecera = ['PARA WEB', 'HOJA1', 'HOJA 1', 'CABECERA', 'TOMOS'];
$patron_hojas_documento = '/^(A-?\d{1,3}|CARTAS?|INGS?|INGRESOS?)/i';

// Constantes de mapeo de columnas (documentos).
// Indice 0-based: columna A=0, B=1, C=2, D=3, E=4, ...
// El archivo real tiene: AÑO(B), SOLICITANTE(C), FOLIOS(D), EXPEDIENTE(E).
define('COL_SOLICITANTE', 2);
define('COL_FOLIOS', 3);
define('COL_EXPEDIENTE', 4);
define('COL_ASUNTO', -1);   // sin columna de asunto en el archivo
define('COL_ANIO_DOC', 1);

// Columnas de la cabecera (pestana PARA WEB / Hoja1)
define('COL_TOMO_CODIGO', 0);
define('COL_TOMO_ANIO', 1);
define('COL_TOMO_AREA', 2);
define('COL_TOMO_TIPO', 3);
define('COL_TOMO_FOLIOS', 4);

// ── AUTOCONFIGURACION ──────────────────────────────────────
$isCLI = (php_sapi_name() === 'cli');

if ($isCLI) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/vendor/autoload.php';

    if (!isset($argv[1])) {
        echo "Uso: php importar.php /ruta/archivo.xlsx\n";
        exit(1);
    }
    $archivos = [$argv[1]];
    $usuarioId = null;
    ejecutarETL($archivos, $usuarioId);
    exit(0);
}

// ── MODO WEB ───────────────────────────────────────────────
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';
Auth::requireLogin();

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// ── PROCESAMIENTO POST ─────────────────────────────────────
$resultados = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['archivos_excel'])) {
    $usuarioId = Auth::id();
    $archivosTmp = [];

    foreach ($_FILES['archivos_excel']['tmp_name'] as $idx => $tmp) {
        if ($tmp && is_uploaded_file($tmp)) {
            $nombre = $_FILES['archivos_excel']['name'][$idx];
            // Ignorar archivos temporales de Excel (~$filename)
            if (strpos($nombre, '~$') === 0) {
                continue;
            }
            $destino = sys_get_temp_dir() . '/archivo_import_' . uniqid() . '_' . basename($nombre);
            if (move_uploaded_file($tmp, $destino)) {
                $archivosTmp[$nombre] = $destino;
            }
        }
    }

    if (!empty($archivosTmp)) {
        $resultados = ejecutarETL(array_keys($archivosTmp), $usuarioId, $archivosTmp);

        // Limpiar archivos temporales
        foreach ($archivosTmp as $tmpFile) {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }
}

$pageTitle = 'Importar Excel';
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3>Importacion Masiva desde Excel</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info" style="margin-bottom:16px;">
            <strong>Formato esperado:</strong> Cada archivo Excel debe contener pestanas tipo <code>PARA WEB</code>
            (o <code>Hoja1</code>) con la cabecera del tomo, y pestanas tipo <code>A-001</code> a <code>A-036</code>,
            <code>CARTAS</code>, <code>Ings</code> con los documentos individuales.<br>
            <strong>Columnas de documentos:</strong> Solicitante | Folios | Expediente | Asunto | Ano
        </div>

        <form method="POST" enctype="multipart/form-data" id="formImportar">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="archivos">Seleccionar archivos Excel (.xlsx, .xls):</label>
                <input type="file" name="archivos_excel[]" id="archivos"
                       class="form-control" multiple
                       accept=".xlsx,.xls,.xlsm"
                       required>
                <small style="color:var(--text-muted);">Puede seleccionar multiples archivos. Los archivos temporales (~$) se ignoran automaticamente.</small>
            </div>
            <button type="submit" class="btn btn-primary" id="btnImportar">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Importar Archivos
            </button>
        </form>
    </div>
</div>

<?php if (!empty($resultados)): ?>
<?php foreach ($resultados as $archivo => $res): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Resultado: <?= sanitize($archivo) ?></h3>
    </div>
    <div class="card-body">
        <?php if ($res['exitoso']): ?>
        <div class="alert alert-success">
            Importacion completada exitosamente.
        </div>
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom:0;">
            <div class="stat-card">
                <div class="stat-info">
                    <h4>Tomos</h4>
                    <span class="stat-value"><?= formatNumber($res['tomos']) ?></span>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-info">
                    <h4>Documentos</h4>
                    <span class="stat-value"><?= formatNumber($res['documentos']) ?></span>
                </div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-info">
                    <h4>Expedientes</h4>
                    <span class="stat-value"><?= formatNumber($res['expedientes']) ?></span>
                </div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-info">
                    <h4>Duracion</h4>
                    <span class="stat-value"><?= $res['duracion'] ?>s</span>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?= sanitize($res['error']) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
document.getElementById('formImportar').addEventListener('submit', function() {
    var btn = document.getElementById('btnImportar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Importando...';
});
</script>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
exit;

// ============================================================
// FUNCIONES ETL
// ============================================================

/**
 * Ejecuta el proceso ETL completo sobre uno o mas archivos Excel.
 *
 * @param array  $archivos     Lista de rutas de archivos a procesar
 * @param ?int   $usuarioId    ID del usuario que ejecuta la importacion
 * @param array  $archivosTmp  Mapa nombre_original => ruta_temporal (modo web)
 * @return array               Resultados por archivo
 */
function ejecutarETL(array $archivos, ?int $usuarioId, array $archivosTmp = []): array {
    global $hojas_cabecera, $patron_hojas_documento;
    $resultados = [];
    $pdo = db();

    foreach ($archivos as $nombreOriginal) {
        $ruta = $archivosTmp[$nombreOriginal] ?? $nombreOriginal;

        if (!file_exists($ruta)) {
            $resultados[$nombreOriginal] = [
                'exitoso' => false,
                'error' => "Archivo no encontrado: {$ruta}"
            ];
            continue;
        }

        $inicio = microtime(true);
        $stats = [
            'tomos' => 0,
            'documentos' => 0,
            'expedientes' => 0,
            'errores' => 0,
            'detalle_errores' => []
        ];

        try {
            $spreadsheet = IOFactory::load($ruta);
            $spreadsheet->setActiveSheetIndex(0);
        } catch (Exception $e) {
            $resultados[$nombreOriginal] = [
                'exitoso' => false,
                'error' => 'No se pudo leer el archivo Excel: ' . $e->getMessage()
            ];
            continue;
        }

        $sheetNames = $spreadsheet->getSheetNames();

        // ── PASA 1: procesar pestanas de cabecera (PARA WEB / HOJA1) ──
        // Estas contienen la tabla maestra de tomos (una fila por tomo).
        // Se procesan primero para que los tomos existan cuando las pestanas
        // de documentos (A-001, CARTAS 1, ...) se asocien a ellos por codigo.
        foreach ($sheetNames as $sheetName) {
            $esCabecera = in_array(strtoupper(trim($sheetName)), array_map('strtoupper', $hojas_cabecera));
            if (!$esCabecera) continue;

            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) continue;

            $nuevos = procesarCabeceraParaWeb($pdo, $sheet, $nombreOriginal);
            $stats['tomos'] += $nuevos;
        }

        // ── PASA 2: procesar pestanas de documentos ────────────────────
        foreach ($sheetNames as $sheetName) {
            $esCabecera = in_array(strtoupper(trim($sheetName)), array_map('strtoupper', $hojas_cabecera));
            $esDocumento = preg_match($patron_hojas_documento, trim($sheetName));

            if ($esCabecera || !$esDocumento) {
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) continue;

            // El codigo del tomo es el nombre de la pestana (ej: A-001, CARTAS 1, ING)
            $codigoTomo = limpiarCodigoTomo($sheetName);
            $idTomo = buscarTomoPorCodigo($pdo, $codigoTomo);

            // Si el tomo no se creo desde la cabecera (no estaba en PARA WEB),
            // se auto-crea usando el anio extraido de la hoja.
            if (!$idTomo) {
                $tomoData = [
                    'codigo_tomo'     => $codigoTomo,
                    'anio'            => extraerAnioHoja($sheet, Coordinate::columnIndexFromString($sheet->getHighestColumn())),
                    'area'            => null,
                    'tipo_documento'  => null,
                    'cantidad_folios' => null
                ];
                $idTomo = upsertTomo($pdo, $tomoData, $nombreOriginal);
            }

            if ($idTomo) {
                $stats['tomos']++;
            } else {
                $stats['errores']++;
                $stats['detalle_errores'][] = "No se pudo identificar tomo para pestana: {$sheetName}";
                continue;
            }

            // Procesar filas de documentos
            $totalCols = Coordinate::columnIndexFromString($sheet->getHighestColumn());
            procesarDocumentos($pdo, $sheet, $idTomo, $sheetName, $nombreOriginal, $totalCols, $stats);
        }

        $duracion = round(microtime(true) - $inicio, 2);

        // Registrar en log de importacion
        registrarLogImportacion($pdo, $nombreOriginal, $usuarioId, $stats, $duracion);

        $resultados[$nombreOriginal] = [
            'exitoso' => $stats['errores'] === 0,
            'tomos' => $stats['tomos'],
            'documentos' => $stats['documentos'],
            'expedientes' => $stats['expedientes'],
            'duracion' => $duracion,
            'error' => !empty($stats['detalle_errores']) ? implode('; ', $stats['detalle_errores']) : null
        ];
    }

    return $resultados;
}

/**
 * Busca un año (4 digitos) dentro de una hoja de calculo.
 * Revisa celdas con etiquetas AÑO/AÑO/GESTION y, si no hay,
 * cualquier celda que contenga un numero de 4 digitos en las
 * primeras filas de cabecera.
 */
function extraerAnioHoja($sheet, int $totalCols): ?int {
    $maxFilas = min(15, $sheet->getHighestRow());
    $maxCols = min($totalCols, 10);

    // 1) Buscar etiqueta explicita de año
    for ($row = 1; $row <= $maxFilas; $row++) {
        for ($col = 1; $col <= $maxCols; $col++) {
            $valor = trim((string) $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue());
            if (empty($valor)) continue;
            $upper = strtoupper($valor);
            if (strpos($upper, 'AÑO') !== false || strpos($upper, 'ANIO') !== false || strpos($upper, 'GESTION') !== false) {
                $cand = extraerValorEtiqueta($sheet, $row, $col, $totalCols);
                if ($cand) {
                    $limpio = preg_replace('/[^0-9]/', '', $cand);
                    if (strlen($limpio) === 4) {
                        return (int) $limpio;
                    }
                }
            }
        }
    }

    // 2) Buscar cualquier numero de 4 digitos en la cabecera (filas 1-6, cols 1-6)
    for ($row = 1; $row <= min(6, $maxFilas); $row++) {
        for ($col = 1; $col <= min(6, $maxCols); $col++) {
            $valor = trim((string) $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue());
            if (empty($valor)) continue;
            $limpio = preg_replace('/[^0-9]/', '', $valor);
            if (strlen($limpio) === 4) {
                return (int) $limpio;
            }
        }
    }

    return null;
}

/**
 * Extrae la cabecera de un tomo desde una hoja de calculo.
 * Busca en las primeras 10 filas datos como codigo, anio, area, tipo.
 *
 * @return array|null
 */
function extraerCabeceraTomo($sheet, int $totalCols): ?array {
    $data = [
        'codigo_tomo' => null,
        'anio' => null,
        'area' => null,
        'tipo_documento' => null,
        'cantidad_folios' => null
    ];

    // Buscar en las primeras 15 filas
    $maxFilas = min(15, $sheet->getHighestRow());

    for ($row = 1; $row <= $maxFilas; $row++) {
        for ($col = 1; $col <= min($totalCols, 10); $col++) {
            $valor = trim((string) $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue());
            if (empty($valor)) continue;

            $valorUpper = strtoupper($valor);

            // Detectar etiquetas y asignar valores
            if (strpos($valorUpper, 'CODIGO') !== false || strpos($valorUpper, 'TOMO') !== false) {
                $data['codigo_tomo'] = extraerValorEtiqueta($sheet, $row, $col, $totalCols);
            } elseif (strpos($valorUpper, 'ANIO') !== false || strpos($valorUpper, 'AÑO') !== false || strpos($valorUpper, 'GESTION') !== false) {
                $data['anio'] = extraerValorEtiqueta($sheet, $row, $col, $totalCols);
            } elseif (strpos($valorUpper, 'AREA') !== false || strpos($valorUpper, 'DEPENDENCIA') !== false || strpos($valorUpper, 'OFICINA') !== false) {
                $data['area'] = extraerValorEtiqueta($sheet, $row, $col, $totalCols);
            } elseif (strpos($valorUpper, 'TIPO') !== false || strpos($valorUpper, 'DOCUMENTO') !== false) {
                $data['tipo_documento'] = extraerValorEtiqueta($sheet, $row, $col, $totalCols);
            } elseif (strpos($valorUpper, 'FOLIO') !== false) {
                $data['cantidad_folios'] = extraerValorEtiqueta($sheet, $row, $col, $totalCols);
            }
        }
    }

    // Si no se encontro codigo explicito, intentar usar el valor de la celda A1
    if (empty($data['codigo_tomo'])) {
        $a1 = trim((string) $sheet->getCellByColumnAndRow(1, 1)->getCalculatedValue());
        if (!empty($a1) && strlen($a1) < 100) {
            $data['codigo_tomo'] = $a1;
        }
    }

    // Si no se encontro nada util, retornar null
    if (empty($data['codigo_tomo']) && empty($data['area'])) {
        return null;
    }

    // Si no tiene codigo, generar uno basado en area y tipo
    if (empty($data['codigo_tomo'])) {
        $data['codigo_tomo'] = generarCodigoTomo($data);
    }

    // Limpiar codigo
    $data['codigo_tomo'] = limpiarCodigoTomo($data['codigo_tomo']);

    // Normalizar anio
    if (!empty($data['anio'])) {
        $anioLimpio = preg_replace('/[^0-9]/', '', $data['anio']);
        if (strlen($anioLimpio) === 4) {
            $data['anio'] = (int) $anioLimpio;
        } else {
            $data['anio'] = null;
        }
    }

    // Normalizar folios
    if (!empty($data['cantidad_folios'])) {
        $folLimpio = preg_replace('/[^0-9]/', '', $data['cantidad_folios']);
        $data['cantidad_folios'] = !empty($folLimpio) ? (int) $folLimpio : null;
    }

    return $data;
}

/**
 * Extrae el valor de una celda adyacente a una etiqueta.
 */
function extraerValorEtiqueta($sheet, int $row, int $col, int $totalCols): ?string {
    // Intentar celda de la derecha
    if ($col < $totalCols) {
        $valor = trim((string) $sheet->getCellByColumnAndRow($col + 1, $row)->getCalculatedValue());
        if (!empty($valor)) return $valor;
    }
    // Intentar celda de abajo
    $valor = trim((string) $sheet->getCellByColumnAndRow($col, $row + 1)->getCalculatedValue());
    if (!empty($valor)) return $valor;

    return null;
}

/**
 * Genera un codigo de tomo basado en area y tipo.
 */
function generarCodigoTomo(array $data): string {
    $partes = [];
    if (!empty($data['area'])) {
        $partes[] = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $data['area']), 0, 5));
    }
    if (!empty($data['tipo_documento'])) {
        $partes[] = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $data['tipo_documento']), 0, 5));
    }
    if (!empty($data['anio'])) {
        $partes[] = $data['anio'];
    }

    return !empty($partes) ? implode('-', $partes) : 'SIN-CODIGO-' . date('YmdHis');
}

/**
 * Limpia y normaliza un codigo de tomo.
 */
function limpiarCodigoTomo(string $codigo): string {
    $codigo = trim($codigo);
    $codigo = preg_replace('/\s+/', '-', $codigo);
    $codigo = preg_replace('/[^A-Za-z0-9\-_.]/', '', $codigo);
    return strtoupper($codigo);
}

/**
 * Procesa una pestana de cabecera maestra de tomos (PARA WEB / HOJA1).
 *
 * Formato esperado (tabla columnar):
 *   Fila 1: encabezados (año, gerencia, area, tipo doc., codigo, folios)
 *   Fila 2+: una fila por tomo.
 *
 * Retorna la cantidad de tomos creados/actualizados.
 */
function procesarCabeceraParaWeb(PDO $pdo, $sheet, string $fuente): int {
    $creados = 0;
    $maxFilas = $sheet->getHighestRow();
    $maxCols = min(8, Coordinate::columnIndexFromString($sheet->getHighestColumn()));

    for ($row = 2; $row <= $maxFilas; $row++) {
        $año  = trim((string) $sheet->getCellByColumnAndRow(1, $row)->getCalculatedValue());
        $codi = trim((string) $sheet->getCellByColumnAndRow(5, $row)->getCalculatedValue());
        $tipo = trim((string) $sheet->getCellByColumnAndRow(4, $row)->getCalculatedValue());
        $foli = trim((string) $sheet->getCellByColumnAndRow(6, $row)->getCalculatedValue());
        $area = trim((string) $sheet->getCellByColumnAndRow(3, $row)->getCalculatedValue());
        $gera = trim((string) $sheet->getCellByColumnAndRow(2, $row)->getCalculatedValue());

        // Solo procesar filas que tengan un codigo de tomo valido
        $codiLimpio = limpiarCodigoTomo($codi);
        if ($codiLimpio === '' || $codiLimpio === 'CODIGO') {
            continue;
        }

        $anioNorm = null;
        if (!empty($año)) {
            $limpio = preg_replace('/[^0-9]/', '', $año);
            if (strlen($limpio) === 4) $anioNorm = (int) $limpio;
        }

        $data = [
            'codigo_tomo'     => $codiLimpio,
            'anio'            => $anioNorm,
            'area'            => !empty($area) ? $area : (!empty($gera) ? $gera : null),
            'tipo_documento'  => !empty($tipo) ? $tipo : null,
            'cantidad_folios' => null
        ];

        // cantidad_folios es INT: extraer solo numeros de la cadena
        if (!empty($foli)) {
            $folLimpio = preg_replace('/[^0-9]/', '', $foli);
            if (!empty($folLimpio)) {
                $data['cantidad_folios'] = (int) $folLimpio;
            }
        }

        if (upsertTomo($pdo, $data, $fuente)) {
            $creados++;
        }
    }

    return $creados;
}

/**
 * Inserta o actualiza un tomo en la base de datos.
 *
 * @return int|null ID del tomo
 */
function upsertTomo(PDO $pdo, array $data, string $fuente): ?int {
    // Buscar si ya existe por codigo
    $stmt = $pdo->prepare("SELECT id_tomo FROM tomos WHERE codigo_tomo = :codigo LIMIT 1");
    $stmt->execute([':codigo' => $data['codigo_tomo']]);
    $existente = $stmt->fetch();

    if ($existente) {
        // Actualizar solo campos no nulos
        $campos = [];
        $params = [':id' => $existente['id_tomo']];

        if (!empty($data['area'])) {
            $campos[] = 'area = :area';
            $params[':area'] = $data['area'];
        }
        if (!empty($data['tipo_documento'])) {
            $campos[] = 'tipo_documento = :tipo';
            $params[':tipo'] = $data['tipo_documento'];
        }
        if (!empty($data['anio'])) {
            $campos[] = 'anio = :anio';
            $params[':anio'] = $data['anio'];
        }
        if (!empty($data['cantidad_folios'])) {
            $campos[] = 'cantidad_folios = :folios';
            $params[':folios'] = $data['cantidad_folios'];
        }

        if (!empty($campos)) {
            $sql = "UPDATE tomos SET " . implode(', ', $campos) . " WHERE id_tomo = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        return (int) $existente['id_tomo'];
    }

    // Insertar nuevo tomo
    $stmt = $pdo->prepare(
        "INSERT INTO tomos (codigo_tomo, anio, area, tipo_documento, cantidad_folios,
                           ubicacion_estado, fuente_importacion, usuario_registro_id)
         VALUES (:codigo, :anio, :area, :tipo, :folios,
                 'pendiente_asignacion', :fuente, :usuario_id)"
    );
    $stmt->execute([
        ':codigo'    => $data['codigo_tomo'],
        ':anio'      => $data['anio'] ?? null,
        ':area'      => $data['area'] ?? null,
        ':tipo'      => $data['tipo_documento'] ?? null,
        ':folios'    => $data['cantidad_folios'] ?? null,
        ':fuente'    => $fuente,
        ':usuario_id'=> Auth::check() ? Auth::id() : null
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Busca un tomo existente por codigo.
 */
function buscarTomoPorCodigo(PDO $pdo, string $codigo): ?int {
    $stmt = $pdo->prepare("SELECT id_tomo FROM tomos WHERE codigo_tomo = :codigo LIMIT 1");
    $stmt->execute([':codigo' => $codigo]);
    $result = $stmt->fetch();
    return $result ? (int) $result['id_tomo'] : null;
}

/**
 * Procesa las filas de documentos de una pestana.
 * Retorna la cantidad insertada.
 */
function procesarDocumentos(
    PDO $pdo,
    $sheet,
    int $idTomo,
    string $nombreHoja,
    string $fuente,
    int $totalCols,
    array &$stats
): int {
    $insertados = 0;
    $totalFilas = $sheet->getHighestRow();

    // Detectar fila de inicio (saltar encabezados)
    $filaInicio = detectarFilaInicioDocumentos($sheet, $totalCols);

    // Filas ya importadas para este tomo/pestana.
    // Hace la importacion idempotente: re-subir el mismo archivo
    // no vuelve a insertar documentos (evita duplicados en el inventario).
    $stmtExistentes = $pdo->prepare(
        "SELECT fila_origen FROM documentos_fase1
         WHERE id_tomo = :id_tomo AND hoja_origen = :hoja AND fila_origen IS NOT NULL"
    );
    $stmtExistentes->execute([':id_tomo' => $idTomo, ':hoja' => $nombreHoja]);
    $filasExistentes = array_flip(array_map('intval', $stmtExistentes->fetchAll(PDO::FETCH_COLUMN)));

    // Preparar statements
    $stmtDoc = $pdo->prepare(
        "INSERT INTO documentos_fase1 (id_tomo, anio, solicitante, folios_texto,
                                       expediente_texto, asunto, hoja_origen, fila_origen)
         VALUES (:id_tomo, :anio, :solicitante, :folios, :expediente, :asunto, :hoja, :fila)"
    );
    $stmtExp = $pdo->prepare(
        "INSERT INTO documento_expedientes (id_documento, numero_expediente_unificado)
         VALUES (:id_doc, :numero)"
    );

    $pdo->beginTransaction();

    try {
        for ($row = $filaInicio; $row <= $totalFilas; $row++) {
            // Leer celdas de la fila
                        $solicitante = trim((string) $sheet->getCellByColumnAndRow(COL_SOLICITANTE + 1, $row)->getCalculatedValue());
            $folios     = trim((string) $sheet->getCellByColumnAndRow(COL_FOLIOS + 1, $row)->getCalculatedValue());
            $expediente = trim((string) $sheet->getCellByColumnAndRow(COL_EXPEDIENTE + 1, $row)->getCalculatedValue());
            $anioDoc    = COL_ANIO_DOC >= 0
                ? trim((string) $sheet->getCellByColumnAndRow(COL_ANIO_DOC + 1, $row)->getCalculatedValue())
                : '';
            $asunto     = COL_ASUNTO >= 0
                ? trim((string) $sheet->getCellByColumnAndRow(COL_ASUNTO + 1, $row)->getCalculatedValue())
                : '';

            // Saltar filas completamente vacias
            if (empty($solicitante) && empty($folios) && empty($expediente) && empty($asunto)) {
                continue;
            }

            // Saltar filas ya importadas (idempotencia)
            if (isset($filasExistentes[$row])) {
                continue;
            }
            $filasExistentes[$row] = true;

            // El guion "-" en expediente significa "sin expediente"
            if ($expediente === '-' || $expediente === '—') {
                $expediente = '';
            }

            // Saltar filas que parezcan encabezados
            if (strtoupper($solicitante) === 'SOLICITANTE' || strtoupper($solicitante) === 'NOMBRE') {
                continue;
            }

            // Normalizar anio del documento
            $anioNorm = null;
            if (!empty($anioDoc)) {
                $anioLimpio = preg_replace('/[^0-9]/', '', $anioDoc);
                if (strlen($anioLimpio) === 4) {
                    $anioNorm = (int) $anioLimpio;
                }
            }
            // Si no hay anio en la fila, intentar del tomo padre
            if ($anioNorm === null) {
                $stmtAnio = $pdo->prepare("SELECT anio FROM tomos WHERE id_tomo = :id LIMIT 1");
                $stmtAnio->execute([':id' => $idTomo]);
                $anioTomo = $stmtAnio->fetchColumn();
                $anioNorm = $anioTomo ? (int) $anioTomo : null;
            }

            // Insertar documento
            $stmtDoc->execute([
                ':id_tomo'     => $idTomo,
                ':anio'        => $anioNorm,
                ':solicitante' => !empty($solicitante) ? $solicitante : null,
                ':folios'      => !empty($folios) ? $folios : null,
                ':expediente'  => !empty($expediente) ? $expediente : null,
                ':asunto'      => !empty($asunto) ? $asunto : null,
                ':hoja'        => $nombreHoja,
                ':fila'        => $row
            ]);

            $idDocumento = (int) $pdo->lastInsertId();
            $insertados++;

            // Descomponer expedientes multiples (separados por |)
            if (!empty($expediente)) {
                $nums = array_filter(array_map('trim', explode('|', $expediente)));
                foreach ($nums as $numExp) {
                    if (!empty($numExp)) {
                        $stmtExp->execute([
                            ':id_doc' => $idDocumento,
                            ':numero' => $numExp
                        ]);
                        $stats['expedientes']++;
                    }
                }
            }
        }

        $pdo->commit();
        $stats['documentos'] += $insertados;

    } catch (Exception $e) {
        $pdo->rollBack();
        $stats['errores']++;
        $stats['detalle_errores'][] = "Error en pestana {$nombreHoja}: " . $e->getMessage();
    }

    return $insertados;
}

/**
 * Detecta en que fila empiezan los datos reales de documentos.
 * Salta filas de encabezado y filas vacias.
 */
function detectarFilaInicioDocumentos($sheet, int $totalCols): int {
    $maxFilas = $sheet->getHighestRow();
    // La columna que contiene el encabezado del solicitante (col C en el archivo real)
    $colSolicitante = COL_SOLICITANTE >= 0 ? (COL_SOLICITANTE + 1) : 3;

    for ($row = 1; $row <= min(15, $maxFilas); $row++) {
        $celda = strtoupper(trim((string) $sheet->getCellByColumnAndRow($colSolicitante, $row)->getCalculatedValue()));

        // Si encuentra un encabezado conocido, la siguiente fila es datos
        if (in_array($celda, ['SOLICITANTE', 'NOMBRE', 'DEPENDENCIA', 'TITULAR', '#', 'N°', 'NRO'])) {
            return $row + 1;
        }
    }

    // Por defecto, empezar en fila 4 (fila 3 = encabezado AÑO/SOLICITANTE/FOLIOS/EXPEDIENTE)
    return 4;
}

/**
 * Registra una ejecucion del ETL en el log.
 */
function registrarLogImportacion(PDO $pdo, string $archivo, ?int $usuarioId, array $stats, float $duracion): void {
    $stmt = $pdo->prepare(
        "INSERT INTO log_importacion (archivo, usuario_id, total_tomos, total_documentos,
                                      total_expedientes, errores, detalle_errores, duracion_segundos)
         VALUES (:archivo, :usuario, :tomos, :docs, :exp, :errores, :detalle, :duracion)"
    );
    $stmt->execute([
        ':archivo'  => $archivo,
        ':usuario'  => $usuarioId,
        ':tomos'    => $stats['tomos'],
        ':docs'     => $stats['documentos'],
        ':exp'      => $stats['expedientes'],
        ':errores'  => $stats['errores'],
        ':detalle'  => !empty($stats['detalle_errores']) ? implode("\n", $stats['detalle_errores']) : null,
        ':duracion' => $duracion
    ]);
}
