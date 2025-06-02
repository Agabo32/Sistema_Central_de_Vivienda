<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CORPOLARA - Formulario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
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
                                <div class="h5 mb-0">2013</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="content-card">
                        <form id="corpolara-form">
                            <!-- Primera fila -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">N° Expediente</label>
                                    <input type="text" class="form-control form-input" id="expediente" placeholder="#REF!">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Código de obra</label>
                                    <input type="text" class="form-control form-input" id="codigoObra" placeholder="#REF!">
                                </div>
                            </div>
                            
                            <!-- Beneficiario -->
                            <div class="mb-3">
                                <label class="field-label">Beneficiario (a)</label>
                                <input type="text" class="form-control form-input" id="beneficiario" placeholder="#REF!">
                            </div>
                            
                            <!-- Cédula y Teléfono -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Cédula</label>
                                    <input type="text" class="form-control form-input" id="cedula" value="14398672">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Teléfono</label>
                                    <input type="text" class="form-control form-input" id="telefono" placeholder="#REF!">
                                </div>
                            </div>
                            
                            <!-- Comunidad y Parroquia -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">COMUNIDAD</label>
                                    <input type="text" class="form-control form-input" id="comunidad" placeholder="#REF!">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Parroquia</label>
                                    <input type="text" class="form-control form-input" id="parroquia" placeholder="#REF!">
                                </div>
                            </div>
                            
                            <!-- Municipio -->
                            <div class="mb-3">
                                <label class="field-label">Municipio</label>
                                <input type="text" class="form-control form-input" id="municipio" placeholder="#REF!">
                            </div>
                            
                            <!-- Dirección Exacta -->
                            <div class="mb-3">
                                <label class="field-label">Dirección Exacta</label>
                                <input type="text" class="form-control form-input" id="direccionExacta" placeholder="#REF!">
                            </div>
                            
                            <!-- UTM Norte y Este -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">UTM Norte</label>
                                    <input type="text" class="form-control form-input" id="utmNorte" placeholder="#REF!">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">UTM Este</label>
                                    <input type="text" class="form-control form-input" id="utmEste" placeholder="#REF!">
                                </div>
                            </div>
                            
                            <!-- Método Constructivo -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Método Constructivo</label>
                                    <input type="text" class="form-control form-input" id="metodoConstructivo1" placeholder="#REF!">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Método Constructivo</label>
                                    <input type="text" class="form-control form-input" id="metodoConstructivo2" placeholder="#REF!">
                                </div>
                            </div>
                            
                            <!-- Proyecto y Avance Físico -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Proyecto</label>
                                    <input type="text" class="form-control form-input" id="proyecto" value="IMVIS">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Avance Físico</label>
                                    <input type="text" class="form-control form-input" id="avanceFisico" placeholder="#REF!">
                                </div>
                            </div>
                            
                            <!-- Culminado y Observaciones -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Culminado</label>
                                    <input type="text" class="form-control form-input" id="culminado" placeholder="#REF!">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="field-label">Observaciones</label>
                                    <input type="text" class="form-control form-input" id="observaciones" placeholder="#REF!">
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
                'comunidad', 'parroquia', 'municipio', 'direccionExacta',
                'utmNorte', 'utmEste', 'metodoConstructivo1', 'metodoConstructivo2',
                'proyecto', 'avanceFisico', 'culminado', 'observaciones'
            ];
            
            campos.forEach(campo => {
                const input = document.getElementById(campo);
                if (input) {
                    input.addEventListener('input', function() {
                        console.log(`Campo ${campo} actualizado: ${this.value}`);
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
    </script>
</body>
</html>
