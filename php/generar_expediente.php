<?php
// Ruta a la carpeta donde colocaste DomPDF
$dompdfPath = __DIR__.'/../Sistema_Central_de_Vivienda-main/dompdf-master';

// Incluir manualmente las clases necesarias
require_once $dompdfPath . 'src/Dompdf.php';
require_once $dompdfPath . 'src/Options.php';
require_once $dompdfPath . 'src/Canvas.php';
require_once $dompdfPath . 'src/Css/Stylesheet.php';
require_once $dompdfPath . 'src/Adapter/CPDF.php';

// Configurar el namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// El resto de tu código de conexión a la base de datos
require_once '../php/conf/conexion.php';

// Tu consulta SQL y obtención de datos aquí...

// Configurar DomPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

// HTML para el PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: Helvetica; font-size: 12pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { height: 50px; }
        .title { font-size: 16pt; font-weight: bold; }
        .subtitle { font-size: 14pt; }
        .section-title { font-size: 14pt; font-weight: bold; margin-top: 15px; margin-bottom: 10px; }
        .right-align { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 5px; text-align: left; }
        .footer { text-align: center; margin-top: 30px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <img src="../imagenes/logo_corpolara.jpg" class="logo" alt="CORPOLARA">
        <div class="title">22 CORPOLARA</div>
        <div class="subtitle">Corporación de Desarrollo Jacinto Lara</div>
        <div class="right-align">AÑO: ' . date('Y') . '</div>
    </div>

    <div class="section-title">N° Expediente</div>
    <div>Código de obra: ' . htmlspecialchars($data['codigo_obra']) . '</div>

    <div class="section-title">REF</div>
    <div>' . htmlspecialchars($data['nombre_fiscalizador']) . '</div>
    <div>' . htmlspecialchars($data['codigo_obra']) . '</div>

    <div class="section-title">Beneficiario(a)</div>
    <div>' . htmlspecialchars($data['nombre_beneficiario']) . '</div>

    <table>
        <tr>
            <th>Cédula</th>
            <th>Teléfono</th>
            <th>Comunidad</th>
            <th>Parroquia</th>
        </tr>
        <tr>
            <td>' . htmlspecialchars($data['cedula']) . '</td>
            <td>' . htmlspecialchars($data['telefono']) . '</td>
            <td>' . htmlspecialchars($data['comunidad']) . '</td>
            <td>' . htmlspecialchars($data['parroquia']) . '</td>
        </tr>
    </table>

    <div class="section-title">Municipio</div>
    <div>' . htmlspecialchars($data['municipio']) . '</div>

    <div class="section-title">Dirección Exacta</div>
    <div>' . htmlspecialchars($data['direccion_exacta']) . '</div>

    <table>
        <tr>
            <th>UTM Norte</th>
            <th>UTM Este</th>
            <th>Método Constructivo</th>
            <th>Modelo Constructivo</th>
        </tr>
        <tr>
            <td>' . htmlspecialchars($data['utm_norte']) . '</td>
            <td>' . htmlspecialchars($data['utm_este']) . '</td>
            <td>' . htmlspecialchars($data['metodo_constructivo']) . '</td>
            <td>' . htmlspecialchars($data['modelo_constructivo']) . '</td>
        </tr>
    </table>

    <table>
        <tr>
            <th>Proyecto</th>
            <th>Avance Físico</th>
            <th>Culminado</th>
            <th>Observaciones</th>
        </tr>
        <tr>
            <td>IMVI´S</td>
            <td>' . htmlspecialchars($data['avance_fisico']) . '%</td>
            <td>' . ($data['fecha_culminacion'] ? 'Sí' : 'No') . '</td>
            <td>' . substr(htmlspecialchars($data['observaciones_responsables_control'] ?? ''), 0, 50) . '...</td>
        </tr>
    </table>

    <div class="footer">¡Impulsando el Desarrollo!</div>
</body>
</html>
';

// Cargar el HTML en DomPDF
$dompdf->loadHtml($html);

// Configurar el tamaño y orientación del papel
$dompdf->setPaper('A4', 'portrait');

// Renderizar el PDF
$dompdf->render();

// Generar el PDF y enviarlo al navegador
$dompdf->stream('Expediente_' . $data['nombre_beneficiario'] . '.pdf', [
    'Attachment' => false // Para mostrarlo directamente en el navegador
]);

// Cerrar conexión
$stmt->close();
$conexion->close();
?>