// File Management System JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and modals
    initializeComponents();
    
    // File upload drag and drop
    initializeFileUpload();
    
    // Form builder for managers
    initializeFormBuilder();
    
    // Dynamic form handler
    initializeDynamicForms();
    
    // Commit confirmation
    initializeCommitHandler();
});

function initializeComponents() {
    // Initialize any tooltips or other components
    console.log('Components initialized');
}

function initializeFileUpload() {
    const uploadAreas = document.querySelectorAll('.upload-area');
    
    uploadAreas.forEach(uploadArea => {
        const fileInput = uploadArea.querySelector('input[type="file"]');
        
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            fileInput.files = files;
            updateFileList(fileInput);
        });
        
        fileInput.addEventListener('change', () => {
            updateFileList(fileInput);
        });
    });
}

function updateFileList(fileInput) {
    const fileList = fileInput.parentElement.querySelector('.file-list');
    if (!fileList) return;
    
    fileList.innerHTML = '';
    
    for (let i = 0; i < fileInput.files.length; i++) {
        const file = fileInput.files[i];
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.innerHTML = `
            <span class="file-name">${file.name}</span>
            <span class="file-size">${formatFileSize(file.size)}</span>
        `;
        fileList.appendChild(fileItem);
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function initializeFormBuilder() {
    const formBuilder = document.getElementById('form-builder');
    if (!formBuilder) return;
    
    const addFieldBtn = document.getElementById('add-field');
    const fieldsContainer = document.getElementById('fields-container');
    const formPreview = document.getElementById('form-preview');
    
    let fieldCounter = 0;
    
    addFieldBtn?.addEventListener('click', () => {
        fieldCounter++;
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'field-builder';
        fieldDiv.dataset.fieldId = fieldCounter;
        
        fieldDiv.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h5>Field ${fieldCounter}</h5>
                    <button type="button" class="btn btn-danger btn-sm remove-field">Remove</button>
                </div>
                <div class="form-group">
                    <label class="form-label">Field Label</label>
                    <input type="text" class="form-control field-label" placeholder="Enter field label">
                </div>
                <div class="form-group">
                    <label class="form-label">Field Type</label>
                    <select class="form-control form-select field-type">
                        <option value="text">Text Input</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select Dropdown</option>
                        <option value="radio">Radio Buttons</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="file">File Upload</option>
                        <option value="date">Date</option>
                        <option value="number">Number</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="form-group options-group" style="display: none;">
                    <label class="form-label">Options (one per line)</label>
                    <textarea class="form-control field-options" placeholder="Option 1\nOption 2\nOption 3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" class="field-required"> Required field
                    </label>
                </div>
            </div>
        `;
        
        fieldsContainer.appendChild(fieldDiv);
        
        // Add event listeners
        const fieldType = fieldDiv.querySelector('.field-type');
        const optionsGroup = fieldDiv.querySelector('.options-group');
        const removeBtn = fieldDiv.querySelector('.remove-field');
        
        fieldType.addEventListener('change', () => {
            if (['select', 'radio', 'checkbox'].includes(fieldType.value)) {
                optionsGroup.style.display = 'block';
            } else {
                optionsGroup.style.display = 'none';
            }
            updatePreview();
        });
        
        fieldDiv.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', updatePreview);
            input.addEventListener('change', updatePreview);
        });
        
        removeBtn.addEventListener('click', () => {
            fieldDiv.remove();
            updatePreview();
        });
        
        updatePreview();
    });
    
    function updatePreview() {
        if (!formPreview) return;
        
        const fields = [];
        document.querySelectorAll('.field-builder').forEach(fieldDiv => {
            const label = fieldDiv.querySelector('.field-label').value;
            const type = fieldDiv.querySelector('.field-type').value;
            const options = fieldDiv.querySelector('.field-options').value;
            const required = fieldDiv.querySelector('.field-required').checked;
            
            if (label) {
                fields.push({
                    label,
                    type,
                    options: options.split('\n').filter(opt => opt.trim()),
                    required
                });
            }
        });
        
        let previewHTML = '<div class="card"><div class="card-header"><h5>Form Preview</h5></div>';
        
        fields.forEach((field, index) => {
            previewHTML += `<div class="form-group">`;
            previewHTML += `<label class="form-label">${field.label}${field.required ? ' *' : ''}</label>`;
            
            switch (field.type) {
                case 'text':
                case 'email':
                case 'number':
                case 'date':
                    previewHTML += `<input type="${field.type}" class="form-control" ${field.required ? 'required' : ''}>`;
                    break;
                case 'textarea':
                    previewHTML += `<textarea class="form-control" ${field.required ? 'required' : ''}></textarea>`;
                    break;
                case 'select':
                    previewHTML += `<select class="form-control form-select" ${field.required ? 'required' : ''}>`;
                    previewHTML += `<option value="">Choose...</option>`;
                    field.options.forEach(option => {
                        previewHTML += `<option value="${option}">${option}</option>`;
                    });
                    previewHTML += `</select>`;
                    break;
                case 'radio':
                    field.options.forEach(option => {
                        previewHTML += `<div><label><input type="radio" name="field_${index}" value="${option}" ${field.required ? 'required' : ''}> ${option}</label></div>`;
                    });
                    break;
                case 'checkbox':
                    field.options.forEach(option => {
                        previewHTML += `<div><label><input type="checkbox" name="field_${index}[]" value="${option}"> ${option}</label></div>`;
                    });
                    break;
                case 'file':
                    previewHTML += `<input type="file" class="form-control" ${field.required ? 'required' : ''}>`;
                    break;
            }
            
            previewHTML += `</div>`;
        });
        
        previewHTML += '</div>';
        formPreview.innerHTML = previewHTML;
        
        // Update hidden field with form structure
        const formStructureInput = document.getElementById('form-structure');
        if (formStructureInput) {
            formStructureInput.value = JSON.stringify(fields);
        }
    }
}

function initializeDynamicForms() {
    // Handle dynamic form rendering based on JSON structure
    const dynamicForms = document.querySelectorAll('.dynamic-form');
    
    dynamicForms.forEach(form => {
        const formData = JSON.parse(form.dataset.formStructure || '[]');
        renderDynamicForm(form, formData);
    });
}

function renderDynamicForm(container, formData) {
    let formHTML = '';
    
    formData.forEach((field, index) => {
        formHTML += `<div class="form-group">`;
        formHTML += `<label class="form-label">${field.label}${field.required ? ' *' : ''}</label>`;
        
        const fieldName = `field_${index}`;
        
        switch (field.type) {
            case 'text':
            case 'email':
            case 'number':
            case 'date':
                formHTML += `<input type="${field.type}" name="${fieldName}" class="form-control" ${field.required ? 'required' : ''}>`;
                break;
            case 'textarea':
                formHTML += `<textarea name="${fieldName}" class="form-control" ${field.required ? 'required' : ''}></textarea>`;
                break;
            case 'select':
                formHTML += `<select name="${fieldName}" class="form-control form-select" ${field.required ? 'required' : ''}>`;
                formHTML += `<option value="">Choose...</option>`;
                field.options.forEach(option => {
                    formHTML += `<option value="${option}">${option}</option>`;
                });
                formHTML += `</select>`;
                break;
            case 'radio':
                field.options.forEach(option => {
                    formHTML += `<div><label><input type="radio" name="${fieldName}" value="${option}" ${field.required ? 'required' : ''}> ${option}</label></div>`;
                });
                break;
            case 'checkbox':
                field.options.forEach(option => {
                    formHTML += `<div><label><input type="checkbox" name="${fieldName}[]" value="${option}"> ${option}</label></div>`;
                });
                break;
            case 'file':
                formHTML += `<input type="file" name="${fieldName}" class="form-control" ${field.required ? 'required' : ''}>`;
                break;
        }
        
        formHTML += `</div>`;
    });
    
    container.innerHTML = formHTML;
}

function initializeCommitHandler() {
    const commitBtns = document.querySelectorAll('.commit-btn');
    
    commitBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const confirmed = confirm('Are you sure you want to commit this data? Once committed, it cannot be edited.');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });
}

// Utility functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

function showModal(title, content) {
    const modal = document.getElementById('dynamic-modal');
    if (modal) {
        modal.querySelector('.modal-title').textContent = title;
        modal.querySelector('.modal-body').innerHTML = content;
        modal.style.display = 'block';
    }
}

function closeModal() {
    const modal = document.getElementById('dynamic-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// AJAX helper function
function sendAjaxRequest(url, data, method = 'POST') {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    });
}