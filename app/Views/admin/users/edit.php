<!--
 * Admin View: User Edit
 *
 * This view provides a form for administrators to edit an existing user's details.
 * It allows modification of the user's name, phone number, role (customer/admin),
 * and account status (active/inactive).
 * Email and registration date are displayed but are read-only.
 * Includes display of validation errors from the server.
 * Contains embedded CSS for styling the form and layout.
 *
 * Expected variables:
 * - $user (array): An associative array containing the data of the user being edited
 *   (user_id, name, email, phone, role, account_status, registration_date).
 * - $csrf_token (string): The CSRF token for form security.
 * - $_SESSION['_flash']['error'] (string|array, optional): Error message(s) from the previous request.
 *   Can be a string for a single error or an array for multiple validation errors.
 -->

<!-- Main container for the user edit page -->
<div class="user-edit-container">
    <!-- Page Header -->
    <div class="user-edit-header">
        <h2>Edit User</h2>
        <!-- Action Buttons: Back to List, View User -->
        <div class="user-actions">
            <a href="/admin/users" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
            <a href="/admin/users/<?php echo $user['user_id']; // Link to the user's detail view 
                                    ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View User
            </a>
        </div>
    </div>

    <?php // Display flash error messages if they exist 
    ?>
    <?php if (isset($_SESSION['_flash']['error'])): ?>
        <div class="alert alert-danger">
            <?php
            // Check if the error message is an array (multiple validation errors)
            if (is_array($_SESSION['_flash']['error'])) {
                echo '<ul class="error-list">';
                foreach ($_SESSION['_flash']['error'] as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>'; // Escape each error
                }
                echo '</ul>';
            } else {
                // Display a single error message
                echo htmlspecialchars($_SESSION['_flash']['error']); // Escape the error
            }
            // unset($_SESSION['_flash']['error']); // Optional: Unset after display
            ?>
        </div>
    <?php endif; ?>

    <!-- User Edit Form Card -->
    <div class="user-edit-card">
        <?php // Form submits to /admin/users/{user_id} via POST 
        ?>
        <form action="/admin/users/<?php echo $user['user_id']; ?>" method="post" class="user-edit-form">
            <!-- CSRF Token Hidden Input -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <!-- Name Input -->
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); // Pre-fill with current name 
                                                                                        ?>" required>
            </div>

            <!-- Email Input (Read-only) -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); // Pre-fill with current email 
                                                                                        ?>" readonly>
                <small class="form-text text-muted">Email cannot be changed.</small>
            </div>

            <!-- Phone Input -->
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); // Pre-fill with current phone 
                                                                                        ?>" required>
            </div>

            <!-- Role Selection Dropdown -->
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; // Select current role 
                                                ?>>Customer
                    </option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; // Select current role 
                                            ?>>Admin</option>
                </select>
            </div>

            <!-- Account Status Selection Dropdown -->
            <div class="form-group">
                <label for="account_status">Account Status</label>
                <select id="account_status" name="account_status" class="form-control" required>
                    <?php // Select current status, default to 'active' if not set 
                    ?>
                    <option value="active"
                        <?php echo (!isset($user['account_status']) || $user['account_status'] === 'active') ? 'selected' : ''; ?>>
                        Active</option>
                    <option value="inactive"
                        <?php echo (isset($user['account_status']) && $user['account_status'] === 'inactive') ? 'selected' : ''; ?>>
                        Inactive</option>
                </select>
            </div>

            <!-- Registration Date Input (Read-only) -->
            <div class="form-group">
                <label>Registration Date</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars(date('F d, Y', strtotime($user['registration_date']))); // Format and display date 
                                                                ?>" readonly>
                <small class="form-text text-muted">Registration date cannot be changed.</small>
            </div>

            <!-- Form Action Buttons -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="/admin/users/<?php echo $user['user_id']; // Link back to user view 
                                        ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div> <!-- End user-edit-card -->
</div> <!-- End user-edit-container -->

<!-- Embedded CSS for styling -->
<style>
    /* Body */
    body {
        margin: 0;
        padding: 0;
    }

    /* Main container layout */
    .user-edit-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        /* Spacing between header, alert, card */
    }

    /* Header layout */
    .user-edit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        /* Space below header */
    }

    .user-edit-header h2 {
        margin: 0;
        color: var(--admin-secondary, #343a40);
        /* Use CSS variable or fallback */
    }

    /* Action buttons in header */
    .user-actions {
        display: flex;
        gap: 0.5rem;
        /* Space between buttons */
    }

    /* Alert box styling */
    .alert {
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: 0.25rem;
    }

    /* Danger alert (for errors) */
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    /* Styling for list of errors */
    .error-list {
        margin: 0.5rem 0 0.5rem 1.5rem;
        /* Indent list */
        padding: 0;
        list-style: disc;
        /* Use standard bullet points */
    }

    .error-list li {
        margin-bottom: 0.25rem;
    }

    /* Card styling for the form */
    .user-edit-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        /* Subtle shadow */
        padding: 1.5rem;
    }

    /* Form layout */
    .user-edit-form {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        /* Space between form groups */
    }

    /* Form group layout (label + input) */
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        /* Space between label and input */
    }

    /* Label styling */
    .form-group label {
        font-weight: 600;
        color: var(--admin-secondary, #495057);
    }

    /* General form control styling (inputs, selects) */
    .form-control {
        display: block;
        width: 100%;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    /* Focus state for form controls */
    .form-control:focus {
        color: #495057;
        background-color: #fff;
        border-color: #80bdff;
        /* Highlight border on focus */
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        /* Add focus glow */
    }

    /* Styling for disabled/readonly inputs */
    .form-control:disabled,
    .form-control[readonly] {
        background-color: #e9ecef;
        /* Grey background */
        opacity: 1;
    }

    /* Helper text styling */
    .form-text {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        /* Smaller font size */
    }

    .text-muted {
        color: #6c757d;
        /* Muted color */
    }

    /* Form action buttons container */
    .form-actions {
        display: flex;
        gap: 0.5rem;
        /* Space between buttons */
        margin-top: 1rem;
        /* Space above buttons */
    }

    /* General button styling */
    .btn {
        display: inline-block;
        font-weight: 400;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        user-select: none;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        text-decoration: none;
        cursor: pointer;
    }

    /* Primary button styling (Save) */
    .btn-primary {
        color: white;
        background-color: var(--admin-primary, #007bff);
        border-color: var(--admin-primary, #007bff);
    }

    .btn-primary:hover {
        background-color: #0056b3;
        /* Darker shade on hover */
        border-color: #0056b3;
    }

    /* Secondary button styling (Back, Cancel) */
    .btn-secondary {
        color: white;
        background-color: #6c757d;
        border-color: #6c757d;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #5a6268;
    }

    /* Info button styling (View User) */
    .btn-info {
        color: white;
        background-color: #17a2b8;
        border-color: #17a2b8;
    }

    .btn-info:hover {
        background-color: #138496;
        border-color: #138496;
    }

    /* Responsive adjustments for smaller screens */
    @media (max-width: 768px) {
        .user-edit-header {
            flex-direction: column;
            /* Stack header items vertically */
            align-items: flex-start;
            /* Align items to the start */
            gap: 1rem;
            /* Add gap between stacked items */
        }
    }
</style>