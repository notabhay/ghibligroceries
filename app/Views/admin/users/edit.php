<!--
 * Admin View: User Edit
 *
 * This view provides a form for administrators to edit an existing user's details.
 * It allows modification of the user's name, phone number, role (customer/admin),
 * and account status (active/inactive).
 * Email and registration date are displayed but are read-only.
 * Includes display of validation errors from the server.
 *
 * Expected variables:
 * - $user (array): An associative array containing the data of the user being edited
 *   (user_id, name, email, phone, role, account_status, registration_date).
 * - $csrf_token (string): The CSRF token for form security.
 * - $_SESSION['_flash']['error'] (string|array, optional): Error message(s) from the previous request.
 *   Can be a string for a single error or an array for multiple validation errors.
 -->

<!-- Page Header -->
<div class="admin-content-header">
    <h2>Edit User</h2>
    <!-- Action Buttons: Back to List, View User -->
    <div class="admin-content-actions">
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
            echo '<ul class="mb-0">';
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
<div class="card">
    <div class="card-body">
        <?php // Form submits to /admin/users/{user_id} via POST 
        ?>
        <form action="/admin/users/<?php echo $user['user_id']; ?>" method="post">
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
                <small class="form-text">Email cannot be changed.</small>
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
                <small class="form-text">Registration date cannot be changed.</small>
            </div>

            <!-- Form Action Buttons -->
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="/admin/users/<?php echo $user['user_id']; // Link back to user view 
                                        ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>