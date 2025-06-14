<?php
/**
 * Access Denied Page
 */
?>
<div class="access-denied-page fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Access Denied</h3>
        </div>
        <div class="card-body">
            <div class="error-content text-center">
                <div class="error-icon" style="font-size: 4rem; margin-bottom: 1rem;">🚫</div>
                <h2 style="color: var(--danger-color); margin-bottom: 1rem;">Access Denied</h2>
                <p style="margin-bottom: 2rem;">You do not have permission to access this page or perform this action.</p>
                
                <div class="error-details" style="background-color: rgba(231, 76, 60, 0.1); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
                    <p><strong>Your current role:</strong> <?php echo ucfirst($_SESSION['user_role'] ?? 'Unknown'); ?></p>
                    <p><strong>Required permission:</strong> Editor or Administrator</p>
                </div>
                
                <div class="error-actions">
                    <a href="index.php?page=dashboard" class="btn btn-primary">Go to Dashboard</a>
                    <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.access-denied-page {
    max-width: 600px;
    margin: 2rem auto;
}

.error-content {
    padding: 2rem;
}

.error-details {
    text-align: left;
}

.error-details p {
    margin-bottom: 0.5rem;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-secondary {
    background-color: var(--text-muted);
    color: white;
    padding: var(--spacing-sm) var(--spacing-lg);
    border: none;
    border-radius: var(--border-radius);
    text-decoration: none;
    transition: all var(--transition-speed);
}

.btn-secondary:hover {
    background-color: #666;
    color: white;
}

@media (max-width: 768px) {
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .error-actions .btn {
        width: 200px;
    }
}
</style>