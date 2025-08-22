// Attendance Restrictions CRUD Functions
function openRestrictionsModal() {
    $('#restrictionsModal').modal('show');
    loadRestrictions();
}

function loadRestrictions() {
    fetch('restrictions_api.php?action=list')
        .then(response => response.json())
        .then(data => {
            displayRestrictions(data);
        })
        .catch(error => {
            console.error('Error loading restrictions:', error);
            document.getElementById('restrictionsList').innerHTML = '<div class="alert alert-danger">Error loading restrictions</div>';
        });
}

function displayRestrictions(restrictions) {
    const container = document.getElementById('restrictionsList');
    
    if (restrictions.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">No restrictions found</div>';
        return;
    }
    
    let html = '';
    restrictions.forEach(restriction => {
        const statusClass = restriction.is_active == '1' ? 'active' : 'inactive';
        const statusBadge = restriction.is_active == '1' ? 
            '<span class="badge badge-success restriction-badge">Active</span>' : 
            '<span class="badge badge-danger restriction-badge">Inactive</span>';
        
        html += `
            <div class="restriction-item ${statusClass}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            ${restriction.restriction_name} 
                            ${statusBadge}
                            <span class="badge badge-info restriction-badge">${restriction.restriction_type.replace('_', ' ')}</span>
                        </h6>
                        <p class="mb-1"><strong>Value:</strong> ${restriction.restriction_value}</p>
                        <p class="mb-1"><strong>Applies To:</strong> ${restriction.applies_to}</p>
                        ${restriction.description ? `<p class="mb-0 text-muted">${restriction.description}</p>` : ''}
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="editRestriction(${restriction.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-${restriction.is_active == '1' ? 'warning' : 'success'}" 
                                onclick="toggleRestriction(${restriction.id}, ${restriction.is_active})">
                            <i class="fas fa-${restriction.is_active == '1' ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRestriction(${restriction.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Handle restriction type change
function setupRestrictionForm() {
    const restrictionType = document.getElementById('restrictionType');
    const restrictionValue = document.getElementById('restrictionValue');
    const valueHelp = document.getElementById('valueHelp');
    const dateRangeGroup = document.getElementById('dateRangeGroup');
    
    if (restrictionType) {
        restrictionType.addEventListener('change', function() {
            const type = this.value;
            
            switch(type) {
                case 'day_of_week':
                    restrictionValue.placeholder = 'e.g., Sunday, Monday';
                    valueHelp.textContent = 'Enter day name (Sunday, Monday, etc.)';
                    dateRangeGroup.style.display = 'none';
                    break;
                case 'specific_date':
                    restrictionValue.type = 'date';
                    valueHelp.textContent = 'Select specific date';
                    dateRangeGroup.style.display = 'none';
                    break;
                case 'date_range':
                    restrictionValue.placeholder = 'Range name';
                    valueHelp.textContent = 'Enter range name, specify dates below';
                    dateRangeGroup.style.display = 'block';
                    break;
                case 'custom':
                    restrictionValue.placeholder = 'Custom rule description';
                    valueHelp.textContent = 'Describe your custom restriction';
                    dateRangeGroup.style.display = 'none';
                    break;
                default:
                    restrictionValue.placeholder = '';
                    valueHelp.textContent = '';
                    dateRangeGroup.style.display = 'none';
            }
        });
    }
    
    // Handle form submission
    const addForm = document.getElementById('addRestrictionForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveRestriction();
        });
    }
}

function saveRestriction() {
    const form = document.getElementById('addRestrictionForm');
    const formData = new FormData(form);
    
    if (form.dataset.editId) {
        formData.append('action', 'update');
        formData.append('id', form.dataset.editId);
    } else {
        formData.append('action', 'create');
    }
    
    fetch('restrictions_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Restriction saved successfully!');
            resetRestrictionForm();
            loadRestrictions();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error saving restriction:', error);
        alert('Error saving restriction');
    });
}

function editRestriction(id) {
    fetch(`restrictions_api.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateForm(data.restriction);
            } else {
                alert('Error loading restriction');
            }
        });
}

function populateForm(restriction) {
    document.getElementById('restrictionType').value = restriction.restriction_type;
    document.getElementById('restrictionName').value = restriction.restriction_name;
    document.getElementById('restrictionValue').value = restriction.restriction_value;
    document.getElementById('appliesTo').value = restriction.applies_to;
    document.getElementById('restrictionDescription').value = restriction.description || '';
    document.getElementById('isActive').checked = restriction.is_active == '1';
    
    if (restriction.start_date) document.getElementById('startDate').value = restriction.start_date;
    if (restriction.end_date) document.getElementById('endDate').value = restriction.end_date;
    
    // Trigger change event to show/hide appropriate fields
    document.getElementById('restrictionType').dispatchEvent(new Event('change'));
    
    // Change form to edit mode
    const form = document.getElementById('addRestrictionForm');
    form.dataset.editId = restriction.id;
    form.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save"></i> Update Restriction';
}

function resetRestrictionForm() {
    const form = document.getElementById('addRestrictionForm');
    form.reset();
    delete form.dataset.editId;
    form.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save"></i> Save Restriction';
    document.getElementById('dateRangeGroup').style.display = 'none';
    document.getElementById('valueHelp').textContent = '';
}

function toggleRestriction(id, currentStatus) {
    const newStatus = currentStatus == '1' ? '0' : '1';
    const action = newStatus == '1' ? 'activate' : 'deactivate';
    
    if (confirm(`Are you sure you want to ${action} this restriction?`)) {
        const formData = new FormData();
        formData.append('action', 'toggle');
        formData.append('id', id);
        formData.append('is_active', newStatus);
        
        fetch('restrictions_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadRestrictions();
            } else {
                alert('Error updating restriction');
            }
        });
    }
}

function deleteRestriction(id) {
    if (confirm('Are you sure you want to delete this restriction? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('restrictions_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Restriction deleted successfully!');
                loadRestrictions();
            } else {
                alert('Error deleting restriction');
            }
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setupRestrictionForm();
});
