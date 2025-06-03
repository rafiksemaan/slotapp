<?php
// access_denied.php
?>
<div class="alert alert-danger">
    <h3>Access Denied</h3>
    <p>You do not have permission to access this page.</p>
</div>

<style>
.alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-lg);
}
.alert-danger {
    background-color: rgba(231, 76, 60, 0.2);
    border: 1px solid rgba(231, 76, 60, 0.5);
    color: #e74c3c;
}
</style>