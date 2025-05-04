<!--
 * Admin View: User Show (Details)
 *
 * This view displays the detailed information for a specific user.
 * It shows basic information (ID, name, email, phone, role, status, registration date)
 * and provides an option to trigger a password reset email for the user.
 * Includes action buttons to go back to the user list or edit the user.
 * Displays flash messages for success/error feedback.
 * Contains embedded CSS for layout and styling.
 *
 * Expected variables:
 * - $user (array): An associative array containing all details of the specific user being viewed
 *   (user_id, name, email, phone, role, account_status, registration_date).
 * - $csrf_token (string): The CSRF token for the password reset form submission.
 * - $_SESSION['_flash']['success'] (string, optional): Success message from a previous action (e.g., password reset sent).
 * - $_SESSION['_flash']['error'] (string, optional): Error message from a previous action.
 -->

<!-- Main container for the user details page -->
<div class="user-details-container">
    <!-- Page Header -->
    <div class="user-details-header">
        <h2>User Details</h2>
        <!-- Action Buttons: Back to List, Edit User -->
        <div class="user-actions">
            <a href="/admin/users" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
            <a href="/admin/users/<?php echo $user['user_id']; ?>/edit" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit User
            </a>
        </div>
    </div>

    <?php // Display flash messages if they exist 
    ?>
    <?php if (isset($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['_flash']['success']); // Escape HTML 
            ?>
        </div>
        <?php // unset($_SESSION['_flash']['success']); // Optional: Unset after display 
        ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['_flash']['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['_flash']['error']); // Escape HTML 
            ?>
        </div>
        <?php // unset($_SESSION['_flash']['error']); // Optional: Unset after display 
        ?>
    <?php endif; ?>

    <!-- User Details Card -->
    <div class="user-details-card">
        <!-- Basic Information Section -->
        <div class="user-details-section">
            <h3>Basic Information</h3>
            <!-- Grid layout for user details -->
            <div class="user-details-grid">
                <!-- User ID -->
                <div class="detail-item">
                    <span class="detail-label">User ID:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['user_id']); ?></span>
                </div>
                <!-- Name -->
                <div class="detail-item">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['name']); ?></span>
                </div>
                <!-- Email -->
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <!-- Phone -->
                <div class="detail-item">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                </div>
                <!-- Role -->
                <div class="detail-item">
                    <span class="detail-label">Role:</span>
                    <span class="detail-value">
                        <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-customer'; // Dynamic badge class 
                                            ?>">
                            <?php echo htmlspecialchars(ucfirst($user['role'])); // Capitalized role name 
                            ?>
                        </span>
                    </span>
                </div>
                <!-- Account Status -->
                <div class="detail-item">
                    <span class="detail-label">Account Status:</span>
                    <span class="detail-value">
                        <span class="badge <?php echo isset($user['account_status']) && $user['account_status'] === 'active' ? 'badge-active' : 'badge-inactive'; // Dynamic badge class, default active 
                                            ?>">
                            <?php echo htmlspecialchars(ucfirst($user['account_status'] ?? 'active')); // Capitalized status, default 'active' 
                            ?>
                        </span>
                    </span>
                </div>
                <!-- Registration Date -->
                <div class="detail-item">
                    <span class="detail-label">Registration Date:</span>
                    <span class="detail-value"><?php echo htmlspecialchars(date('F d, Y', strtotime($user['registration_date']))); // Formatted date 
                                                ?></span>
                </div>
            </div> <!-- End user-details-grid -->
        </div> <!-- End user-details-section (Basic Info) -->

        <!-- Password Management Section -->
        <div class="user-details-section">
            <h3>Password Management</h3>
            <p>Use this option to send a password reset email to the user.</p>
            <!-- Password Reset Form -->
            <form action="/admin/users/<?php echo $user['user_id']; ?>/reset-password" method="post"
                class="reset-password-form">
                <!-- CSRF Token Hidden Input -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <!-- Submit Button -->
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-key"></i> Send Password Reset Email
                </button>
            </form>
        </div> <!-- End user-details-section (Password) -->
    </div> <!-- End user-details-card -->
</div> <!-- End user-details-container -->

<!-- Embedded CSS for styling -->
<style>
    /* Body */
    body {
        margin: 0;
        padding: 0;
    }

    /* Main container layout */
    .user-details-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        /* Spacing between elements */
    }

    /* Header layout */
    .user-details-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .user-details-header h2 {
        margin: 0;
        color: var(--admin-secondary, #343a40);
        /* Use CSS variable or fallback */
    }

    /* Action buttons in header */
    .user-actions {
        display: flex;
        gap: 0.5rem;
    }

    /* Alert box styling */
    .alert {
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: 0.25rem;
    }

    /* Success alert */
    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    /* Danger alert */
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    /* Card styling for user details */
    .user-details-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        /* Subtle shadow */
        overflow: hidden;
        /* Ensures border radius applies correctly */
    }

    /* Styling for sections within the card */
    .user-details-section {
        padding: 1.5rem;
        border-bottom: 1px solid #e9ecef;
        /* Separator line */
    }

    /* Remove border from the last section */
    .user-details-section:last-child {
        border-bottom: none;
    }

    /* Section heading styling */
    .user-details-section h3 {
        margin-top: 0;
        margin-bottom: 1rem;
        color: var(--admin-secondary, #495057);
        font-size: 1.25rem;
    }

    /* Grid layout for basic information */
    .user-details-grid {
        display: grid;
        /* Creates responsive columns: fills with columns of at least 300px */
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
        /* Spacing between grid items */
    }

    /* Individual detail item styling (label + value) */
    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        /* Space between label and value */
    }

    /* Detail label styling (e.g., "Name:") */
    .detail-label {
        font-weight: 600;
        color: #6c757d;
        /* Muted color */
        font-size: 0.875rem;
        /* Slightly smaller */
    }

    /* Detail value styling (e.g., "John Doe") */
    .detail-value {
        font-size: 1rem;
    }

    /* General badge styling */
    .badge {
        display: inline-block;
        padding: 0.25em 0.6em;
        font-size: 75%;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    /* Admin role badge */
    .badge-admin {
        color: white;
        background-color: var(--admin-primary, #007bff);
    }

    /* Customer role badge */
    .badge-customer {
        color: white;
        background-color: #6c757d;
    }

    /* Active status badge */
    .badge-active {
        color: white;
        background-color: #28a745;
    }

    /* Inactive status badge */
    .badge-inactive {
        color: white;
        background-color: #dc3545;
    }

    /* Password reset form margin */
    .reset-password-form {
        margin-top: 1rem;
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

    /* Primary button (Edit User) */
    .btn-primary {
        color: white;
        background-color: var(--admin-primary, #007bff);
        border-color: var(--admin-primary, #007bff);
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    /* Secondary button (Back) */
    .btn-secondary {
        color: white;
        background-color: #6c757d;
        border-color: #6c757d;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #5a6268;
    }

    /* Warning button (Password Reset) */
    .btn-warning {
        color: #212529;
        /* Dark text for yellow background */
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .btn-warning:hover {
        background-color: #e0a800;
        /* Darker yellow on hover */
        border-color: #e0a800;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {

        /* Stack header items on smaller screens */
        .user-details-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        /* Force grid items into a single column */
        .user-details-grid {
            grid-template-columns: 1fr;
        }
    }
</style>