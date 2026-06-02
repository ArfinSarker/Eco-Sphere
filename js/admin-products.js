// Admin Products Management
document.addEventListener('DOMContentLoaded', function() {
    const updateProductForm = document.getElementById('updateProductForm');
    const productSelect = document.getElementById('productSelect');
    const productDetails = document.getElementById('productDetails');
    
    // Load products for dropdown
    loadProductsForUpdate();

    // Handle product selection change
    productSelect.addEventListener('change', function() {
        const productId = this.value;
        if (productId) {
            loadProductDetails(productId);
            productDetails.style.display = 'block';
        } else {
            productDetails.style.display = 'none';
        }
    });

    // Handle form submission
    updateProductForm.addEventListener('submit', function(e) {
        e.preventDefault();
        updateProduct();
    });
});

// Load products for the dropdown
async function loadProductsForUpdate() {
    try {
        const response = await fetch('admin/get_products.php');
        const products = await response.json();
        
        const productSelect = document.getElementById('productSelect');
        productSelect.innerHTML = '<option value="">Select a product</option>';
        
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = `${product.name} - $${product.price}`;
            productSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading products:', error);
        showNotification('Error loading products', 'error');
    }
}

// Load product details for editing
async function loadProductDetails(productId) {
    try {
        const response = await fetch(`admin/get_product_details.php?id=${productId}`);
        const product = await response.json();
        
        if (product.error) {
            showNotification(product.error, 'error');
            return;
        }

        // Populate form fields
        document.getElementById('updateProductId').value = product.id;
        document.getElementById('updateName').value = product.name;
        document.getElementById('updateDescription').value = product.description;
        document.getElementById('updatePrice').value = product.price;
        document.getElementById('updateCategory').value = product.category_id;
        document.getElementById('updateStock').value = product.stock_quantity;
        document.getElementById('updateFeatured').checked = product.featured == 1;

        // Show current image if exists
        const currentImageDiv = document.getElementById('currentImage');
        if (product.primary_image) {
            currentImageDiv.innerHTML = `
                <p><strong>Current Image:</strong></p>
                <img src="../${product.primary_image}" alt="${product.name}" style="max-width: 200px; max-height: 200px;">
            `;
        } else {
            currentImageDiv.innerHTML = '<p>No current image</p>';
        }

    } catch (error) {
        console.error('Error loading product details:', error);
        showNotification('Error loading product details', 'error');
    }
}

// Update product function
async function updateProduct() {
    const form = document.getElementById('updateProductForm');
    const formData = new FormData(form);
    
    // Add action parameter
    formData.append('action', 'update_product');
    
    try {
        const response = await fetch('admin/update_product.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Product updated successfully!', 'success');
            // Reset form and hide details
            form.reset();
            document.getElementById('productDetails').style.display = 'none';
            document.getElementById('productSelect').value = '';
            
            // Reload products in dropdown
            loadProductsForUpdate();
        } else {
            showNotification(result.error || 'Error updating product', 'error');
        }
    } catch (error) {
        console.error('Error updating product:', error);
        showNotification('Error updating product', 'error');
    }
}

// Show notification function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        z-index: 1000;
        font-weight: bold;
        max-width: 300px;
    `;
    
    if (type === 'success') {
        notification.style.backgroundColor = '#4CAF50';
    } else {
        notification.style.backgroundColor = '#f44336';
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}