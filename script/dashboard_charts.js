import { Chart } from "@/components/ui/chart"
// Variables globales para los gráficos
let municipiosChart = null

// Función para mostrar/ocultar elementos
function showElement(elementId) {
  const element = document.getElementById(elementId)
  if (element) {
    element.style.display = "block"
  }
}

function hideElement(elementId) {
  const element = document.getElementById(elementId)
  if (element) {
    element.style.display = "none"
  }
}

// Función para mostrar errores
function showError(message) {
  hideElement("loadingIndicator")
  hideElement("dashboardContent")
  hideElement("chartsContainer")

  const errorElement = document.getElementById("errorMessage")
  const errorText = document.getElementById("errorText")

  if (errorElement && errorText) {
    errorText.textContent = message
    errorElement.classList.remove("d-none")
  }
}

// Función para cargar los datos del dashboard
async function cargarDatosDashboard() {
  try {
    console.log("Iniciando carga de datos...")

    const response = await fetch("php/get_dashboard_data.php", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    })

    console.log("Respuesta recibida:", response.status)

    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`)
    }

    const data = await response.json()
    console.log("Datos recibidos:", data)

    if (!data.success) {
      throw new Error(data.error || "Error desconocido en el servidor")
    }

    // Ocultar indicador de carga
    hideElement("loadingIndicator")
    hideElement("errorMessage")

    // Mostrar contenido
    showElement("dashboardContent")
    showElement("chartsContainer")

    // Actualizar contadores con animación
    actualizarContador("totalBeneficiarios", data.total_beneficiarios || 0)
    actualizarContador("viviendasCompletadas", data.viviendas_completadas || 0)
    actualizarContador("totalComunidades", data.total_comunidades || 0)

    // Crear gráfico de municipios
    if (data.beneficiarios_municipio && data.beneficiarios_municipio.length > 0) {
      crearGraficoMunicipios(data.beneficiarios_municipio)
    } else {
      console.warn("No hay datos de municipios disponibles")
    }
  } catch (error) {
    console.error("Error al cargar los datos:", error)
    showError(`Error al cargar los datos: ${error.message}`)
  }
}

// Función para animar contadores
function actualizarContador(elementId, valorFinal) {
  const elemento = document.getElementById(elementId)
  if (!elemento) return

  const valorInicial = 0
  const duracion = 2000 // 2 segundos
  const incremento = valorFinal / (duracion / 50)
  let valorActual = valorInicial

  const intervalo = setInterval(() => {
    valorActual += incremento
    if (valorActual >= valorFinal) {
      valorActual = valorFinal
      clearInterval(intervalo)
    }
    elemento.textContent = Math.floor(valorActual).toLocaleString()
  }, 50)
}

// Función para crear el gráfico de municipios
function crearGraficoMunicipios(datos) {
  const ctx = document.getElementById("municipiosChart")
  if (!ctx) {
    console.error("Canvas municipiosChart no encontrado")
    return
  }

  // Destruir gráfico anterior si existe
  if (municipiosChart) {
    municipiosChart.destroy()
  }

  const municipios = datos.map((item) => item.municipio)
  const beneficiarios = datos.map((item) => Number.parseInt(item.total_beneficiarios) || 0)

  // Colores para las barras
  const colores = [
    "#FF6384",
    "#36A2EB",
    "#FFCE56",
    "#4BC0C0",
    "#9966FF",
    "#FF9F40",
    "#FF6384",
    "#C9CBCF",
    "#4BC0C0",
    "#FF6384",
  ]

  municipiosChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: municipios,
      datasets: [
        {
          label: "Beneficiarios",
          data: beneficiarios,
          backgroundColor: colores.slice(0, municipios.length),
          borderColor: colores.slice(0, municipios.length),
          borderWidth: 1,
          borderRadius: 5,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: (context) => context.parsed.y + " beneficiarios",
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1,
          },
          grid: {
            color: "rgba(0,0,0,0.1)",
          },
        },
        x: {
          grid: {
            display: false,
          },
          ticks: {
            maxRotation: 45,
            minRotation: 0,
          },
        },
      },
      animation: {
        duration: 1500,
        easing: "easeInOutQuart",
      },
    },
  })
}

// Función para recargar datos
function recargarDatos() {
  hideElement("dashboardContent")
  hideElement("chartsContainer")
  hideElement("errorMessage")
  showElement("loadingIndicator")

  setTimeout(() => {
    cargarDatosDashboard()
  }, 500)
}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM cargado, iniciando dashboard...")
  cargarDatosDashboard()

  // Recargar datos cada 5 minutos
  setInterval(cargarDatosDashboard, 300000)
})

// Manejar errores globales
window.addEventListener("error", (e) => {
  console.error("Error global:", e.error)
})

// Función para debugging (puedes llamarla desde la consola)
window.debugDashboard = () => {
  console.log("Estado actual del dashboard:")
  console.log("- Gráfico municipios:", municipiosChart)
  recargarDatos()
}
