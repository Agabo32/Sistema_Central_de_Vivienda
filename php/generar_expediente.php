<?php
require_once "../php/conf/conexion.php";

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validación exhaustiva del ID
if (!isset($_GET['id'])) {
    die("Error crítico: Parámetro 'id' no definido.");
}

if (empty($_GET['id']) && $_GET['id'] !== '0') {
    die("Error crítico: Parámetro 'id' está vacío.");
}

// Conversión segura a entero
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id <= 0) {
    die("Error crítico: ID de beneficiario no válido. Valor recibido: " . htmlspecialchars($_GET['id']));
}

// Función para manejar valores nulos
function getValue($value, $default = '#¡REF!') {
    return isset($value) && $value !== '' ? $value : $default;
}

// Consulta SQL con depuración
$sql = "SELECT 
            b.id_beneficiario, 
            b.cedula, 
            b.nombre_beneficiario,
            b.telefono,
            u.comunidad, 
            u.direccion_exacta AS direccion,
            u.utm_norte, 
            u.utm_este, 
            p.parroquia, 
            m.municipio, 
            e.estado,
            dc.avance_fisico
        FROM Beneficiarios b
        LEFT JOIN Ubicaciones u ON b.id_ubicacion = u.id_ubicacion
        LEFT JOIN Parroquias p ON u.id_parroquia = p.id_parroquia
        LEFT JOIN Municipios m ON p.id_municipio = m.id_municipio
        LEFT JOIN Estados e ON m.id_estado = e.id_estado
        LEFT JOIN Datos_de_Construccion dc ON b.id_beneficiario = dc.id_beneficiario
        WHERE b.id_beneficiario = ?";

$stmt = $conexion->prepare($sql);
if ($stmt === false) {
    die("Error de preparación de consulta: " . $conexion->error);
}

if (!$stmt->bind_param("i", $id)) {
    die("Error al vincular parámetros: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Error al ejecutar consulta: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Error al obtener resultados: " . $conexion->error);
}

$beneficiario = $result->fetch_assoc();
if (!$beneficiario) {
    die("No se encontró ningún beneficiario con ID: $id");
}

// Limpiar el buffer de salida antes de generar PDF
while (ob_get_level()) {
    ob_end_clean();
}

// Incluir TCPDF
require_once('../tcpdf_6_3_2/tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
        $image_file = '../imagenes/logo_corpolara.jpg';
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 30, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 15, '22 CORPOLARA', 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 0, 'Corporación de Desarrollo Jacinto Lara', 0, 1, 'C');
        
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 15, 'AÑO', 0, 1, 'R');
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 0, date('Y'), 0, 1, 'R');
        
        $this->Line(15, 40, 195, 40);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, '¡Impulsando el Desarrollo!', 0, false, 'C');
    }
}

// Crear y configurar PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('SIGEVU');
$pdf->SetTitle('Expediente ' . getValue($beneficiario['cedula']));
$pdf->SetMargins(15, 45, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();

// Generar contenido HTML seguro
$html = '
<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }
    td, th {
        border: 0.5px solid #000;
        padding: 4px;
    }
    h3 {
        margin-top: 10px;
        margin-bottom: 5px;
    }
</style>

<h3>N° Expediente</h3>
<p><strong>'.htmlspecialchars(getValue($beneficiario['id_beneficiario'])).'</strong></p>

<h3>Código de obra</h3>
<p><strong>REF-'.htmlspecialchars(getValue($beneficiario['id_beneficiario'])).'</strong></p>

<h3>Beneficiario (a)</h3>
<p><strong>'.htmlspecialchars(getValue($beneficiario['nombre_beneficiario'])).'</strong></p>

<table>
    <tr>
        <td width="50%"><strong>Cédula</strong></td>
        <td width="50%"><strong>Teléfono</strong></td>
    </tr>
    <tr>
        <td>'.htmlspecialchars(getValue($beneficiario['cedula'])).'</td>
        <td>'.htmlspecialchars(getValue($beneficiario['telefono'])).'</td>
    </tr>
</table>

<h3>Ubicación</h3>
<table>
    <tr>
        <td width="50%"><strong>Comunidad</strong></td>
        <td width="50%"><strong>Parroquia</strong></td>
    </tr>
    <tr>
        <td>'.htmlspecialchars(getValue($beneficiario['comunidad'])).'</td>
        <td>'.htmlspecialchars(getValue($beneficiario['parroquia'])).'</td>
    </tr>
    <tr>
        <td><strong>Municipio</strong></td>
        <td><strong>Estado</strong></td>
    </tr>
    <tr>
        <td>'.htmlspecialchars(getValue($beneficiario['municipio'])).'</td>
        <td>'.htmlspecialchars(getValue($beneficiario['estado'])).'</td>
    </tr>
</table>

<h3>Dirección Exacta</h3>
<p><strong>'.htmlspecialchars(getValue($beneficiario['direccion'])).'</strong></p>

<h3>Coordenadas</h3>
<table>
    <tr>
        <td width="50%"><strong>UTM Norte</strong></td>
        <td width="50%"><strong>UTM Este</strong></td>
    </tr>
    <tr>
        <td>'.htmlspecialchars(getValue($beneficiario['utm_norte'])).'</td>
        <td>'.htmlspecialchars(getValue($beneficiario['utm_este'])).'</td>
    </tr>
</table>

<h3>Datos de Construcción</h3>
<table>
    <tr>
        <td width="50%"><strong>Método Constructivo</strong></td>
        <td width="50%"><strong>Modelo Constructivo</strong></td>
    </tr>
    <tr>
        <td>'.htmlspecialchars(getValue($beneficiario['metodo_constructivo'])).'</td>
        <td>'.htmlspecialchars(getValue($beneficiario['modelo_constructivo'])).'</td>
    </tr>
    <tr>
        <td><strong>Avance Físico</strong></td>
        <td><strong>Culminado</strong></td>
    </tr>
    <tr>
        <td>'.(isset($beneficiario['avance_fisico']) ? htmlspecialchars($beneficiario['avance_fisico']).'%' : '#¡REF!').'</td>
        <td>'.(isset($beneficiario['culminado']) && $beneficiario['culminado'] ? 'SI' : 'NO').'</td>
    </tr>
    <tr>
        <td colspan="2"><strong>Observaciones</strong></td>
    </tr>
    <tr>
        <td colspan="2">'.htmlspecialchars(getValue($beneficiario['observaciones'])).'</td>
    </tr>
</table>';

// Escribir contenido y generar PDF
$pdf->writeHTML($html, true, false, true, false, '');
$pdf_filename = 'expediente_'.preg_replace('/[^a-zA-Z0-9]/', '_', getValue($beneficiario['cedula'])).'.pdf';
$pdf->Output($pdf_filename, 'D');

// Cerrar conexiones
$stmt->close();
$conexion->close();
exit();
?>