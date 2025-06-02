<?php
session_start();
require_once '../php/conf/conexion.php';

// Verificación robusta de sesión y rol
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'admin';

if (!isset($_GET['id'])) {
    echo "ID de beneficiario no especificado.";
    exit;
}

$id = intval($_GET['id']); // Asegurarse de que el ID es un número entero

// Consulta SQL corregida con nombres de tablas y campos actualizados
$sql = "
SELECT
    b.id_beneficiario, 
    b.cedula, 
    b.nombre_beneficiario, 
    b.telefono, 
    b.fecha_actualizacion,
    b.status,
    co.cod_obra AS codigo_obra,
    u.comunidad, 
    u.direccion_exacta, 
    u.utm_norte, 
    u.utm_este,
    m.id_municipio,
    m.municipio AS municipio,  
    p.id_parroquia,
    p.parroquia AS parroquia,
    e.estado AS estado,
    mc.nomb_metodo AS metodo_constructivo, 
    mo.nomb_modelo AS modelo_constructivo,
    dc.avance_fisico, 
    dc.fecha_culminacion, 
    dc.acta_entregada, 
    dc.observaciones_responsables_control, 
    dc.observaciones_fiscalizadores
FROM beneficiarios b
LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
LEFT JOIN municipios m ON u.id_municipio = m.id_municipio
LEFT JOIN parroquias p ON u.id_parroquia = p.id_parroquia
LEFT JOIN estados e ON m.id_estado = e.id_estado
LEFT JOIN metodos_constructivos mc ON b.id_metodo_constructivo = mc.id_metodo
LEFT JOIN modelos_constructivos mo ON b.id_modelo_constructivo = mo.id_modelo
LEFT JOIN cod_obra co ON b.id_cod_obra = co.id_cod_obra
LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario
WHERE b.id_beneficiario = ?
";

$stmt = $conexion->prepare($sql);
if ($stmt === false) {
    die("Error en la preparación de la consulta: " . $conexion->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("No se encontraron registros para el beneficiario con ID: $id");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CORPOLARA - Expediente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 2rem 0;
        }
        
        .header-card {
            background-color: #dc3545;
            color: white;
            padding: 1rem;
        }
        
        .logo-badge {
            background-color: white;
            color: #dc3545;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: bold;
            font-size: 1.125rem;
        }
        
        .year-badge {
            background-color: white;
            color: black;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
        }
        
        .content-card {
            background-color: #f0f9ff;
            padding: 2rem;
        }
        
        .field-label {
            background-color: #bbf7d0;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.875rem;
            text-align: center;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            text-align: center;
            background-color: white;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .form-input.readonly {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .footer-section {
            background-color: white;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-top: 2rem;
            text-align: center;
        }
        
        .venezuela-flag {
            display: inline-flex;
            margin-left: 1rem;
        }
        
        .flag-stripe {
            width: 24px;
            height: 16px;
        }
        
        .yellow { background-color: #fbbf24; }
        .blue { background-color: #2563eb; }
        .red { background-color: #dc2626; }
        
        .btn-save {
            background-color: #28a745;
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
        }
        
        .btn-save:hover {
            background-color: #218838;
            color: white;
        }
        
        .btn-print {
            background-color: #17a2b8;
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-print:hover {
            background-color: #138496;
            color: white;
        }
        
        .ref-placeholder {
            color: #dc3545;
            font-style: italic;
            font-weight: 600;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-active { background-color: #28a745; }
        .status-inactive { background-color: #dc3545; }
        
        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .content-card { background: white !important; }
            .form-input { border: 1px solid #000 !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="card shadow-lg">
                    <!-- Header -->
                    <div class="header-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="logo-badge me-3">CP</div>
                                <div>
                                    <h1 class="h4 mb-0">CORPOLARA</h1>
                                    <p class="small mb-0">Corporación de Desarrollo Jacinto Lara</p>
                                </div>
                            </div>
                            <div class="year-badge">
                                <div class="small fw-bold">AÑO</div>
                                <div class="h5 mb-0">2024</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="content-card">
                        <!-- Botones de Acción -->
                        <div class="d-flex justify-content-end mb-3 no-print">
                            <?php if ($esAdmin): ?>
                                <button type="button" class="btn btn-save" onclick="guardarCambios()">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                                <button type="button" class="btn btn-print" onclick="imprimirExpediente()">
                                    <i class="fas fa-print me-2"></i>Imprimir
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <form id="corpolara-form">
                            <input type="hidden" id="id_beneficiario" value="<?php echo $data['id_beneficiario']; ?>">
                            
                            <!-- Primera fila -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">N° Expediente</label>
                                    <input type="text" class="form-control form-input" id="expediente" 
                                           value="<?php echo !empty($data['id_beneficiario']) ? htmlspecialchars($data['id_beneficiario']) : ''; ?>"
                                           placeholder="<?php echo empty($data['id_beneficiario']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Código de obra</label>
                                    <input type="text" class="form-control form-input" id="codigoObra" 
                                           value="<?php echo !empty($data['codigo_obra']) ? htmlspecialchars($data['codigo_obra']) : ''; ?>"
                                           placeholder="<?php echo empty($data['codigo_obra']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Beneficiario -->
                            <div class="mb-3">
                                <label class="field-label">Beneficiario (a)</label>
                                <input type="text" class="form-control form-input" id="beneficiario" 
                                       value="<?php echo !empty($data['nombre_beneficiario']) ? htmlspecialchars($data['nombre_beneficiario']) : ''; ?>"
                                       placeholder="<?php echo empty($data['nombre_beneficiario']) ? '#REF!' : ''; ?>">
                            </div>
                            
                            <!-- Cédula y Teléfono -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Cédula</label>
                                    <input type="text" class="form-control form-input" id="cedula" 
                                           value="<?php echo !empty($data['cedula']) ? htmlspecialchars($data['cedula']) : ''; ?>"
                                           placeholder="<?php echo empty($data['cedula']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Teléfono</label>
                                    <input type="text" class="form-control form-input" id="telefono" 
                                           value="<?php echo !empty($data['telefono']) ? htmlspecialchars($data['telefono']) : ''; ?>"
                                           placeholder="<?php echo empty($data['telefono']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Comunidad y Parroquia -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">COMUNIDAD</label>
                                    <input type="text" class="form-control form-input" id="comunidad" 
                                           value="<?php echo !empty($data['comunidad']) ? htmlspecialchars($data['comunidad']) : ''; ?>"
                                           placeholder="<?php echo empty($data['comunidad']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Parroquia</label>
                                    <input type="text" class="form-control form-input" id="parroquia" 
                                           value="<?php echo !empty($data['parroquia']) ? htmlspecialchars($data['parroquia']) : ''; ?>"
                                           placeholder="<?php echo empty($data['parroquia']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Municipio y Estado -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Municipio</label>
                                    <input type="text" class="form-control form-input" id="municipio" 
                                           value="<?php echo !empty($data['municipio']) ? htmlspecialchars($data['municipio']) : ''; ?>"
                                           placeholder="<?php echo empty($data['municipio']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Estado</label>
                                    <input type="text" class="form-control form-input" id="estado" 
                                           value="<?php echo !empty($data['estado']) ? htmlspecialchars($data['estado']) : ''; ?>"
                                           placeholder="<?php echo empty($data['estado']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Dirección Exacta -->
                            <div class="mb-3">
                                <label class="field-label">Dirección Exacta</label>
                                <input type="text" class="form-control form-input" id="direccionExacta" 
                                       value="<?php echo !empty($data['direccion_exacta']) ? htmlspecialchars($data['direccion_exacta']) : ''; ?>"
                                       placeholder="<?php echo empty($data['direccion_exacta']) ? '#REF!' : ''; ?>">
                            </div>
                            
                            <!-- UTM Norte y Este -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">UTM Norte</label>
                                    <input type="text" class="form-control form-input" id="utmNorte" 
                                           value="<?php echo !empty($data['utm_norte']) ? htmlspecialchars($data['utm_norte']) : ''; ?>"
                                           placeholder="<?php echo empty($data['utm_norte']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">UTM Este</label>
                                    <input type="text" class="form-control form-input" id="utmEste" 
                                           value="<?php echo !empty($data['utm_este']) ? htmlspecialchars($data['utm_este']) : ''; ?>"
                                           placeholder="<?php echo empty($data['utm_este']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Método Constructivo y Modelo -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Método Constructivo</label>
                                    <input type="text" class="form-control form-input" id="metodoConstructivo" 
                                           value="<?php echo !empty($data['metodo_constructivo']) ? htmlspecialchars($data['metodo_constructivo']) : ''; ?>"
                                           placeholder="<?php echo empty($data['metodo_constructivo']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Modelo Constructivo</label>
                                    <input type="text" class="form-control form-input" id="modeloConstructivo" 
                                           value="<?php echo !empty($data['modelo_constructivo']) ? htmlspecialchars($data['modelo_constructivo']) : ''; ?>"
                                           placeholder="<?php echo empty($data['modelo_constructivo']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Proyecto y Avance Físico -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Proyecto</label>
                                    <input type="text" class="form-control form-input" id="proyecto" value="IMVI´S" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Avance Físico (%)</label>
                                    <input type="number" class="form-control form-input" id="avanceFisico" 
                                           value="<?php echo !empty($data['avance_fisico']) ? htmlspecialchars($data['avance_fisico']) : ''; ?>"
                                           placeholder="<?php echo empty($data['avance_fisico']) ? '#REF!' : ''; ?>"
                                           min="0" max="100">
                                </div>
                            </div>
                            
                            <!-- Culminado y Estado -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Fecha Culminación</label>
                                    <input type="date" class="form-control form-input" id="fechaCulminacion" 
                                           value="<?php echo !empty($data['fecha_culminacion']) ? htmlspecialchars($data['fecha_culminacion']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Estado del Beneficiario</label>
                                    <div class="form-control form-input d-flex align-items-center justify-content-center">
                                        <span class="status-indicator <?php echo $data['status'] == 'activo' ? 'status-active' : 'status-inactive'; ?>"></span>
                                        <?php echo ucfirst($data['status']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Observaciones -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Observaciones Control</label>
                                    <textarea class="form-control form-input" id="observacionesControl" rows="3" 
                                              placeholder="<?php echo empty($data['observaciones_responsables_control']) ? '#REF!' : ''; ?>"><?php echo !empty($data['observaciones_responsables_control']) ? htmlspecialchars($data['observaciones_responsables_control']) : ''; ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Observaciones Fiscalizadores</label>
                                    <textarea class="form-control form-input" id="observacionesFiscalizadores" rows="3" 
                                              placeholder="<?php echo empty($data['observaciones_fiscalizadores']) ? '#REF!' : ''; ?>"><?php echo !empty($data['observaciones_fiscalizadores']) ? htmlspecialchars($data['observaciones_fiscalizadores']) : ''; ?></textarea>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Footer -->
                        <div class="footer-section">
                            <span class="h5 fw-bold fst-italic">¡Impulsando el Desarrollo!</span>
                            <div class="venezuela-flag">
                                <div class="flag-stripe yellow"></div>
                                <div class="flag-stripe blue"></div>
                                <div class="flag-stripe red"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript para manejar el formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('corpolara-form');
            
            // Agregar event listeners para cada campo
            const campos = [
                'expediente', 'codigoObra', 'beneficiario', 'cedula', 'telefono',
                'comunidad', 'parroquia', 'municipio', 'estado', 'direccionExacta',
                'utmNorte', 'utmEste', 'metodoConstructivo', 'modeloConstructivo',
                'proyecto', 'avanceFisico', 'fechaCulminacion', 'observacionesControl', 
                'observacionesFiscalizadores'
            ];
            
            campos.forEach(campo => {
                const input = document.getElementById(campo);
                if (input) {
                    input.addEventListener('input', function() {
                        console.log(`Campo ${campo} actualizado: ${this.value}`);
                        // Marcar el campo como modificado
                        this.classList.add('modified');
                    });
                }
            });
            
            // Función para obtener todos los datos del formulario
            function obtenerDatosFormulario() {
                const datos = {};
                campos.forEach(campo => {
                    const input = document.getElementById(campo);
                    if (input) {
                        datos[campo] = input.value;
                    }
                });
                datos.id_beneficiario = document.getElementById('id_beneficiario').value;
                return datos;
            }
            
            // Función para establecer datos en el formulario
            function establecerDatosFormulario(datos) {
                Object.keys(datos).forEach(campo => {
                    const input = document.getElementById(campo);
                    if (input) {
                        input.value = datos[campo];
                    }
                });
            }
            
            // Exponer funciones globalmente para uso externo
            window.corpolara = {
                obtenerDatos: obtenerDatosFormulario,
                establecerDatos: establecerDatosFormulario
            };
        });
        
        // Función para guardar cambios
        function guardarCambios() {
            const datos = window.corpolara.obtenerDatos();
            
            // Mostrar indicador de carga
            const btnSave = document.querySelector('.btn-save');
            const originalText = btnSave.innerHTML;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            btnSave.disabled = true;
            
            // Enviar datos al servidor (aquí deberías implementar la llamada AJAX)
            fetch('actualizar_expediente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datos)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cambios guardados exitosamente');
                    // Remover clase de modificado de todos los campos
                    document.querySelectorAll('.modified').forEach(el => {
                        el.classList.remove('modified');
                    });
                } else {
                    alert('Error al guardar los cambios: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar los cambios');
            })
            .finally(() => {
                btnSave.innerHTML = originalText;
                btnSave.disabled = false;
            });
        }
        
        // Función para imprimir expediente
        function imprimirExpediente() {
            window.print();
        }
    </script>
</body>
</html>
<?php
// Cerrar conexiones de base de datos si las hay
if (isset($conexion)) {
    $conexion->close();
}
?>