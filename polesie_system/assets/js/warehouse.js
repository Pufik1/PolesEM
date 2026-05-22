/**
 * Warehouse Module JavaScript for OAO "Polesieelectromash" ERP System
 * Professional warehouse management functionality
 */

// Global materials data storage
let allMaterials = [];
let filteredMaterials = [];

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Load materials data
    loadMaterials();
    
    // Initialize event listeners
    initEventListeners();
});

// Load materials from server
function loadMaterials() {
    fetch('?action=get_materials')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allMaterials = data.data;
                filteredMaterials = [...allMaterials];
                updateStatistics();
                renderTable();
                renderCards();
            }
        })
        .catch(error => console.error('Error loading materials:', error));
}

// Initialize all event listeners
function initEventListeners() {
    // Filter toggle
    const toggleBtn = document.getElementById('toggleFilters');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const filtersContent = document.querySelector('.filters-content');
            const icon = this.querySelector('i');
            
            if (filtersContent.style.maxHeight === '0px' || filtersContent.style.maxHeight === '') {
                filtersContent.style.maxHeight = filtersContent.scrollHeight + 'px';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                filtersContent.style.maxHeight = '0px';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        });
    }
    
    // Apply filters button
    const applyBtn = document.getElementById('applyFilters');
    if (applyBtn) {
        applyBtn.addEventListener('click', applyFilters);
    }
    
    // Reset filters button
    const resetBtn = document.getElementById('resetFilters');
    if (resetBtn) {
        resetBtn.addEventListener('click', resetFilters);
    }
    
    // Export button
    const exportBtn = document.getElementById('exportData');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportToExcel);
    }
    
    // View toggle buttons
    const viewButtons = document.querySelectorAll('.btn-icon-view');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Update active state
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide views
            const tableView = document.getElementById('tableView');
            const cardsView = document.getElementById('cardsView');
            
            if (view === 'table') {
                tableView.classList.remove('hidden');
                cardsView.classList.add('hidden');
            } else {
                tableView.classList.add('hidden');
                cardsView.classList.remove('hidden');
            }
        });
    });
    
    // Search text input - real-time filtering
    const searchText = document.getElementById('searchText');
    if (searchText) {
        searchText.addEventListener('input', debounce(applyFilters, 300));
    }
}

// Apply all filters
function applyFilters() {
    const filters = {
        search: document.getElementById('searchText')?.value.toLowerCase() || '',
        category: document.getElementById('filterCategory')?.value || '',
        section: document.getElementById('filterSection')?.value || '',
        zone: document.getElementById('filterZone')?.value || '',
        power: document.getElementById('filterPower')?.value || '',
        rpm: document.getElementById('filterRpm')?.value || '',
        voltage: document.getElementById('filterVoltage')?.value || '',
        material: document.getElementById('filterMaterial')?.value || '',
        weightMin: document.getElementById('weightMin')?.value || '',
        weightMax: document.getElementById('weightMax')?.value || '',
        gost: document.getElementById('filterGost')?.value || '',
        storage: document.getElementById('filterStorage')?.value || '',
        stock: document.getElementById('filterStock')?.value || ''
    };
    
    filteredMaterials = allMaterials.filter(item => {
        // Text search
        if (filters.search) {
            const searchText = `${item.article} ${item.name} ${item.brand || ''} ${item.subsection || ''}`.toLowerCase();
            if (!searchText.includes(filters.search)) return false;
        }
        
        // Category filter
        if (filters.category && item.category !== filters.category) return false;
        
        // Section filter
        if (filters.section && item.section !== filters.section) return false;
        
        // Zone filter
        if (filters.zone && item.zone !== filters.zone) return false;
        
        // Power filter (for motors)
        if (filters.power && item.power != filters.power) return false;
        
        // RPM filter (for motors)
        if (filters.rpm && item.rpm != filters.rpm) return false;
        
        // Voltage filter
        if (filters.voltage && item.voltage != filters.voltage) return false;
        
        // Material filter
        if (filters.material && item.material !== filters.material && item.brand !== filters.material) return false;
        
        // Weight range filter
        if (filters.weightMin && item.weight && item.weight < parseFloat(filters.weightMin)) return false;
        if (filters.weightMax && item.weight && item.weight > parseFloat(filters.weightMax)) return false;
        
        // GOST filter
        if (filters.gost && item.gost !== filters.gost) return false;
        
        // Storage filter
        if (filters.storage && !item.storage?.includes(filters.storage)) return false;
        
        return true;
    });
    
    updateStatistics();
    renderTable();
    renderCards();
}

// Reset all filters
function resetFilters() {
    // Clear all inputs
    document.querySelectorAll('.filter-input, .filter-select').forEach(input => {
        input.value = '';
    });
    
    // Reset to all materials
    filteredMaterials = [...allMaterials];
    updateStatistics();
    renderTable();
    renderCards();
}

// Update statistics counters
function updateStatistics() {
    document.getElementById('total-items').textContent = filteredMaterials.length;
    document.getElementById('in-stock').textContent = filteredMaterials.filter(m => m.weight).length;
    document.getElementById('low-stock').textContent = filteredMaterials.filter(m => m.min_stock).length;
}

// Render table view
function renderTable() {
    const tbody = document.getElementById('materialsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = filteredMaterials.map(item => `
        <tr>
            <td><span class="article-code">${escapeHtml(item.article)}</span></td>
            <td><strong>${escapeHtml(item.name)}</strong></td>
            <td>${escapeHtml(item.subsection || item.category || '-')}</td>
            <td><span class="zone-badge">${escapeHtml(item.zone)}</span></td>
            <td><span class="weight-value">${item.weight ? item.weight + ' кг' : '-'}</span></td>
            <td>${escapeHtml(item.unit || '-')}</td>
            <td>${escapeHtml(item.gost || '-')}</td>
            <td>${escapeHtml(item.storage || '-')}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-primary btn-icon" onclick="showMaterialDetail('${item.article}')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success btn-icon" title="Печать">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    // Update results count
    document.getElementById('resultsCount').textContent = `${filteredMaterials.length} позиций`;
}

// Render cards view
function renderCards() {
    const grid = document.getElementById('materialsGrid');
    if (!grid) return;
    
    grid.innerHTML = filteredMaterials.map(item => `
        <div class="material-card category-${getCategoryClass(item.category)}">
            <div class="material-card-header">
                <span class="article">${escapeHtml(item.article)}</span>
                <span class="zone-tag">${escapeHtml(item.zone)}</span>
            </div>
            <div class="material-card-body">
                <h4>${escapeHtml(item.name)}</h4>
                <div class="material-specs">
                    ${renderSpecItem('Категория', item.subsection || item.category)}
                    ${renderSpecItem('Вес', item.weight ? item.weight + ' кг' : '-')}
                    ${item.power ? renderSpecItem('Мощность', item.power + ' кВт') : ''}
                    ${item.rpm ? renderSpecItem('Обороты', item.rpm + ' об/мин') : ''}
                    ${item.voltage ? renderSpecItem('Напряжение', item.voltage + ' В') : ''}
                    ${item.gost ? renderSpecItem('ГОСТ', item.gost) : ''}
                    ${item.material ? renderSpecItem('Материал', item.material) : ''}
                    ${item.application ? renderSpecItem('Применение', item.application) : ''}
                </div>
            </div>
            <div class="material-card-footer">
                <span class="stock-status in-stock">
                    <i class="fas fa-check-circle"></i> В наличии
                </span>
                <button class="btn btn-sm btn-primary" onclick="showMaterialDetail('${item.article}')">
                    <i class="fas fa-info-circle"></i> Подробнее
                </button>
            </div>
        </div>
    `).join('');
}

// Helper to render spec item
function renderSpecItem(label, value) {
    return `
        <div class="spec-item">
            <span class="spec-label">${label}</span>
            <span class="spec-value">${escapeHtml(value || '-')}</span>
        </div>
    `;
}

// Get category class for color coding
function getCategoryClass(category) {
    if (category?.startsWith('raw')) return 'raw';
    if (category?.startsWith('motor')) return 'motor';
    if (category?.startsWith('cast')) return 'cast';
    if (category?.startsWith('parts')) return 'parts';
    return 'raw';
}

// Show material detail modal
function showMaterialDetail(article) {
    const material = allMaterials.find(m => m.article === article);
    if (!material) return;
    
    const modalBody = document.getElementById('modalBody');
    if (!modalBody) return;
    
    modalBody.innerHTML = `
        <div class="modal-detail-grid">
            <div class="detail-section">
                <h4><i class="fas fa-barcode"></i> Основная информация</h4>
                <div class="detail-row">
                    <span class="label">Артикул</span>
                    <span class="value">${escapeHtml(material.article)}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Наименование</span>
                    <span class="value">${escapeHtml(material.name)}</span>
                </div>
                ${material.brand ? `
                <div class="detail-row">
                    <span class="label">Марка</span>
                    <span class="value">${escapeHtml(material.brand)}</span>
                </div>` : ''}
                ${material.gost ? `
                <div class="detail-row">
                    <span class="label">ГОСТ/Стандарт</span>
                    <span class="value">${escapeHtml(material.gost)}</span>
                </div>` : ''}
                <div class="detail-row">
                    <span class="label">Раздел</span>
                    <span class="value">${escapeHtml(material.section)}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Категория</span>
                    <span class="value">${escapeHtml(material.subsection || material.category)}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-warehouse"></i> Хранение</h4>
                <div class="detail-row">
                    <span class="label">Зона хранения</span>
                    <span class="value"><strong>${escapeHtml(material.zone)}</strong></span>
                </div>
                <div class="detail-row">
                    <span class="label">Условия хранения</span>
                    <span class="value">${escapeHtml(material.storage || 'Не указаны')}</span>
                </div>
                ${material.shelf_life ? `
                <div class="detail-row">
                    <span class="label">Срок хранения</span>
                    <span class="value">${escapeHtml(material.shelf_life)}</span>
                </div>` : ''}
                ${material.packaging ? `
                <div class="detail-row">
                    <span class="label">Упаковка</span>
                    <span class="value">${escapeHtml(material.packaging)}</span>
                </div>` : ''}
                ${material.min_stock ? `
                <div class="detail-row">
                    <span class="label">Мин. остаток</span>
                    <span class="value">${material.min_stock} ${escapeHtml(material.unit || 'шт')}</span>
                </div>` : ''}
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-cube"></i> Характеристики</h4>
                ${material.weight ? `
                <div class="detail-row">
                    <span class="label">Вес единицы</span>
                    <span class="value">${material.weight} кг</span>
                </div>` : ''}
                ${material.unit ? `
                <div class="detail-row">
                    <span class="label">Ед. измерения</span>
                    <span class="value">${escapeHtml(material.unit)}</span>
                </div>` : ''}
                ${material.form ? `
                <div class="detail-row">
                    <span class="label">Форма</span>
                    <span class="value">${escapeHtml(material.form)}</span>
                </div>` : ''}
                ${material.fraction ? `
                <div class="detail-row">
                    <span class="label">Фракция/Размер</span>
                    <span class="value">${escapeHtml(material.fraction)}</span>
                </div>` : ''}
                ${material.dimensions ? `
                <div class="detail-row">
                    <span class="label">Габариты</span>
                    <span class="value">${escapeHtml(material.dimensions)}</span>
                </div>` : ''}
                ${material.composition ? `
                <div class="detail-row">
                    <span class="label">Хим. состав</span>
                    <span class="value">${escapeHtml(material.composition)}</span>
                </div>` : ''}
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-bolt"></i> Технические параметры</h4>
                ${material.power ? `
                <div class="detail-row">
                    <span class="label">Мощность</span>
                    <span class="value">${material.power} кВт</span>
                </div>` : ''}
                ${material.rpm ? `
                <div class="detail-row">
                    <span class="label">Обороты</span>
                    <span class="value">${material.rpm} об/мин</span>
                </div>` : ''}
                ${material.voltage ? `
                <div class="detail-row">
                    <span class="label">Напряжение</span>
                    <span class="value">${material.voltage} В</span>
                </div>` : ''}
                ${material.efficiency ? `
                <div class="detail-row">
                    <span class="label">КПД</span>
                    <span class="value">${material.efficiency}%</span>
                </div>` : ''}
                ${material.cos_phi ? `
                <div class="detail-row">
                    <span class="label">cos φ</span>
                    <span class="value">${material.cos_phi}</span>
                </div>` : ''}
                ${material.ip_rating ? `
                <div class="detail-row">
                    <span class="label">Степень защиты</span>
                    <span class="value">${escapeHtml(material.ip_rating)}</span>
                </div>` : ''}
                ${material.features ? `
                <div class="detail-row">
                    <span class="label">Особенности</span>
                    <span class="value">${escapeHtml(material.features)}</span>
                </div>` : ''}
                ${material.application ? `
                <div class="detail-row">
                    <span class="label">Применение</span>
                    <span class="value">${escapeHtml(material.application)}</span>
                </div>` : ''}
                ${material.purpose ? `
                <div class="detail-row">
                    <span class="label">Назначение</span>
                    <span class="value">${escapeHtml(material.purpose)}</span>
                </div>` : ''}
            </div>
        </div>
    `;
    
    // Show modal
    document.getElementById('materialModal').classList.add('active');
}

// Close modal
function closeModal() {
    document.getElementById('materialModal').classList.remove('active');
}

// Export to Excel (CSV format)
function exportToExcel() {
    const headers = ['Артикул', 'Наименование', 'Категория', 'Зона', 'Вес (кг)', 'Ед. изм.', 'ГОСТ', 'Условия хранения'];
    const rows = filteredMaterials.map(item => [
        item.article,
        item.name,
        item.subsection || item.category,
        item.zone,
        item.weight || '',
        item.unit || '',
        item.gost || '',
        item.storage || ''
    ]);
    
    let csvContent = '\uFEFF'; // BOM for UTF-8
    csvContent += headers.join(';') + '\n';
    rows.forEach(row => {
        csvContent += row.map(cell => `"${cell}"`).join(';') + '\n';
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `warehouse_export_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('materialModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // ESC to close modal
    if (e.key === 'Escape') {
        closeModal();
    }
    
    // Ctrl+F to focus search
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchText')?.focus();
    }
});
