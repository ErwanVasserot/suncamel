(() => {
    const storageKey = 'suncamel.admin.product.column-widths.v1';
    const defaults = {
        id: 72, title: 200, slug: 190, tagline: 240, summary: 300,
        halfDayAmount: 155, fullDayAmount: 155, collectionSlug: 180,
        coverImage: 120, heroImage: 120, highlights: 180, specs: 180,
        stockQuantity: 120, position: 85, isActive: 85,
    };
    const clamp = (width) => Math.max(64, Math.min(800, width));

    function initialize() {
        const table = document.querySelector('body.ea-index-Product table.datagrid');
        if (!table || table.dataset.resizableColumns || !table.tHead?.rows.length) return;
        table.dataset.resizableColumns = 'true';

        let saved = {};
        try {
            const value = JSON.parse(localStorage.getItem(storageKey) || '{}');
            if (value && typeof value === 'object' && !Array.isArray(value)) saved = value;
        } catch { /* Resizing also works when browser storage is unavailable. */ }

        const wrapper = document.createElement('div');
        wrapper.className = 'product-table-scroll';
        wrapper.tabIndex = 0;
        wrapper.setAttribute('role', 'region');
        wrapper.setAttribute('aria-label', 'Produits — tableau à défilement horizontal');
        table.before(wrapper);
        wrapper.append(table);

        const columns = document.createElement('colgroup');
        const widths = [];
        const headers = Array.from(table.tHead.rows[0].cells);
        const persist = () => {
            try { localStorage.setItem(storageKey, JSON.stringify(saved)); } catch { /* Optional storage. */ }
        };
        const applyWidth = (index, width) => {
            widths[index] = width;
            columns.children[index].style.width = `${width}px`;
            table.style.width = `${widths.reduce((sum, value) => sum + value, 0)}px`;
        };

        headers.forEach((header, index) => {
            const key = header.dataset.column;
            const fallback = key ? (defaults[key] ?? 160) : (index === 0 ? 44 : 150);
            const width = key && Number.isFinite(saved[key]) ? clamp(saved[key]) : fallback;
            widths.push(width);
            const column = document.createElement('col');
            column.style.width = `${width}px`;
            columns.append(column);
            if (!key) return;

            const handle = document.createElement('button');
            handle.type = 'button';
            handle.className = 'product-column-resize';
            handle.setAttribute('aria-label', `Ajuster la largeur : ${header.textContent.trim()}`);
            handle.title = 'Glisser pour redimensionner ; double-clic pour réinitialiser. Flèches gauche/droite au clavier.';
            handle.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); });
            handle.addEventListener('pointerdown', (event) => {
                if (event.button !== 0) return;
                event.preventDefault();
                event.stopPropagation();
                const startX = event.clientX;
                const startWidth = widths[index];
                handle.setPointerCapture(event.pointerId);
                document.documentElement.classList.add('product-column-resizing');
                const move = (next) => applyWidth(index, clamp(startWidth + next.clientX - startX));
                const finish = () => {
                    saved[key] = widths[index];
                    persist();
                    document.documentElement.classList.remove('product-column-resizing');
                    handle.removeEventListener('pointermove', move);
                    handle.removeEventListener('pointerup', finish);
                    handle.removeEventListener('pointercancel', finish);
                    handle.removeEventListener('lostpointercapture', finish);
                };
                handle.addEventListener('pointermove', move);
                handle.addEventListener('pointerup', finish);
                handle.addEventListener('pointercancel', finish);
                handle.addEventListener('lostpointercapture', finish);
            });
            handle.addEventListener('dblclick', (event) => {
                event.preventDefault();
                event.stopPropagation();
                delete saved[key];
                applyWidth(index, fallback);
                persist();
            });
            handle.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
                event.preventDefault();
                event.stopPropagation();
                applyWidth(index, clamp(widths[index] + (event.key === 'ArrowRight' ? 10 : -10)));
                saved[key] = widths[index];
                persist();
            });
            header.append(handle);
        });
        table.prepend(columns);
        table.style.width = `${widths.reduce((sum, value) => sum + value, 0)}px`;

        document.querySelector('[data-product-columns-reset]')?.addEventListener('click', () => {
            saved = {};
            persist();
            headers.forEach((header, index) => {
                const key = header.dataset.column;
                applyWidth(index, key ? (defaults[key] ?? 160) : (index === 0 ? 44 : 150));
            });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
    document.addEventListener('turbo:load', initialize);
})();
