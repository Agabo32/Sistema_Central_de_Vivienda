document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const collapseBtn = document.querySelector('.sidebar-collapse-btn');
    const container = document.querySelector('.container');

    // Inicializa la tabla con animación al cargar
    const table = document.querySelector('table');
    if (table) {
        table.classList.add('fade-in');
    }

    // Aplicar el estado guardado de la barra lateral
    const applySidebarState = (collapsed) => {
        sidebar.classList.toggle('collapsed', collapsed);
        container.style.marginLeft = collapsed
            ? 'var(--sidebar-collapsed-width)'
            : 'var(--sidebar-width)';
        localStorage.setItem('sidebarCollapsed', collapsed);
    };

    // Evento para colapsar/expandir
    collapseBtn.addEventListener('click', () => {
        if (sidebar.classList.contains('animating')) return;

        sidebar.classList.add('animating');
        const isCollapsed = sidebar.classList.contains('collapsed');
        applySidebarState(!isCollapsed);

        // Remover clase animación después de transicionar
        setTimeout(() => {
            sidebar.classList.remove('animating');
        }, 300);

        document.dispatchEvent(new Event('sidebarToggled'));
    });

    // Responsividad en ventana
    const handleResponsive = () => {
        if (window.innerWidth < 992) {
            sidebar.classList.add('collapsed');
            container.style.marginLeft = 'var(--sidebar-collapsed-width)';
        } else {
            const savedState = localStorage.getItem('sidebarCollapsed') === 'true';
            applySidebarState(savedState);
        }
    };

    // Inicializa con el estado adecuado
    handleResponsive();
    window.addEventListener('resize', handleResponsive);
});

if (window.innerWidth >= 992) {
    container.style.marginLeft = collapsed
        ? 'var(--sidebar-collapsed-width)'
        : 'var(--sidebar-width)';
} else {
    container.style.marginLeft = '0';
}
