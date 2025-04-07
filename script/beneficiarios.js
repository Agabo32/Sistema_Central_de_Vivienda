document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const sidebar = document.querySelector('.sidebar');
    const collapseBtn = document.querySelector('.sidebar-collapse-btn');
    const container = document.querySelector('.container');
    const sidebarToggler = document.querySelector('.sidebar-toggler');
    
    // Restaurar estado de la barra lateral
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        sidebar.classList.add('collapsed');
        container.style.marginLeft = 'var(--sidebar-collapsed-width)';
    }
    
    // Función para alternar el estado de la barra lateral
    function toggleSidebar() {
        // Prevenir múltiples clics durante la animación
        if (sidebar.classList.contains('animating')) return;
        
        sidebar.classList.add('animating');
        sidebar.classList.toggle('collapsed');
        
        // Animar el contenedor principal
        if (sidebar.classList.contains('collapsed')) {
            container.style.marginLeft = 'var(--sidebar-collapsed-width)';
        } else {
            container.style.marginLeft = 'var(--sidebar-width)';
        }
        
        // Guardar preferencia
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        
        // Notificar cambios
        document.dispatchEvent(new Event('sidebarToggled'));
        
        // Remover la clase de animación después de que termine
        setTimeout(() => {
            sidebar.classList.remove('animating');
        }, 300);
    }
    
    // Función para el menú móvil
    function toggleMobileMenu() {
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('menu-active');
            if (sidebarToggler) {
                const togglerIcon = sidebarToggler.querySelector('.material-symbols-rounded');
                if (togglerIcon) {
                    togglerIcon.textContent = sidebar.classList.contains('menu-active') ? 'chevron_right' : 'chevron_left';
                }
            }
        }
    }
    
    // Event listeners
    if (collapseBtn) {
        collapseBtn.addEventListener('click', toggleSidebar);
    }
    
    if (sidebarToggler) {
        sidebarToggler.addEventListener('click', toggleMobileMenu);
    }
    
    // Cargar datos de beneficiarios con animación
    function loadBeneficiarios() {
        const beneficiarios = [
            { id: 1, nombre: "María González", cedula: "V-12345678", ubicacion: "Caracas, Distrito Capital", progreso: 75 },
            { id: 2, nombre: "Juan Pérez", cedula: "V-87654321", ubicacion: "Maracaibo, Zulia", progreso: 50 },
            { id: 3, nombre: "Carlos Rodríguez", cedula: "V-11223344", ubicacion: "Valencia, Carabobo", progreso: 30 },
            { id: 4, nombre: "Ana Martínez", cedula: "V-44332211", ubicacion: "Barquisimeto, Lara", progreso: 15 },
            { id: 5, nombre: "Luisa Fernández", cedula: "V-55667788", ubicacion: "Mérida, Mérida", progreso: 90 }
        ];
        
        const tbody = document.querySelector('.table tbody');
        tbody.innerHTML = '';
        
        beneficiarios.forEach((beneficiario, index) => {
            const row = document.createElement('tr');
            row.style.animationDelay = `${index * 0.1}s`;
            
            // Determinar color de la barra de progreso según el porcentaje
            let progressClass = '';
            if (beneficiario.progreso >= 70) progressClass = 'bg-success';
            else if (beneficiario.progreso >= 40) progressClass = 'bg-info';
            else if (beneficiario.progreso >= 20) progressClass = 'bg-warning';
            else progressClass = 'bg-danger';
            
            row.innerHTML = `
                <td>${beneficiario.id}</td>
                <td>${beneficiario.nombre}</td>
                <td>${beneficiario.cedula}</td>
                <td>${beneficiario.ubicacion}</td>
                <td>
                    <div class="progress">
                        <div class="progress-bar ${progressClass}" role="progressbar" 
                             style="width: ${beneficiario.progreso}%" 
                             aria-valuenow="${beneficiario.progreso}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            ${beneficiario.progreso}%
                        </div>
                    </div>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-2 btn-action">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning me-2 btn-action">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-action">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
        // Agregar eventos a los botones de acción
        document.querySelectorAll('.btn-action').forEach(btn => {
            btn.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
            });
        });
    }
    
    // Cargar datos al iniciar
    loadBeneficiarios();
    
    // Manejar responsive
    let timeoutId;
    function handleResize() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('collapsed');
                container.style.marginLeft = '0';
            } else {
                const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    container.style.marginLeft = 'var(--sidebar-collapsed-width)';
                } else {
                    sidebar.classList.remove('collapsed');
                    container.style.marginLeft = 'var(--sidebar-width)';
                }
            }
        }, 250);
        if (window.innerWidth <= 992) {
            sidebar.classList.add('collapsed');
        } else {
            // Restaurar estado preferido
            const shouldCollapse = localStorage.getItem('sidebarCollapsed') === 'true';
            if (shouldCollapse) sidebar.classList.add('collapsed');
            else sidebar.classList.remove('collapsed');
        }
    }
    
    window.addEventListener('resize', handleResize);
    handleResize(); // Ejecutar al cargar
});