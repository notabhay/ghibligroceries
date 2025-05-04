/**
 * Reusable Form Input component with label, validation feedback (error/success),
 * and required indicator.
 *
 * @param {object} props - Component props.
 * @param {string} props.id - The id and name attribute for the input element.
 * @param {string} props.label - The text label for the input field.
 * @param {string} props.type - The input type (e.g., 'text', 'email', 'password', 'tel').
 * @param {string} props.value - The current value of the input field.
 * @param {function} props.onChange - Callback function for the input's onChange event.
 * @param {function} props.onBlur - Callback function for the input's onBlur event.
 * @param {string} props.error - Error message to display if validation fails. Empty string if valid.
 * @param {boolean} props.success - Whether to show a success indicator (check mark).
 * @param {boolean} [props.isRequired=true] - Whether the input field is required. Adds '*' to label if true.
 * @returns {JSX.Element} The rendered form input group.
 */
const FormInput = ({ id, label, type, value, onChange, onBlur, error, success, isRequired = true }) => {
    return (
        <div className="form-group">
            {/* Label with required indicator if applicable */}
            <label htmlFor={id}>{label}{isRequired && <span className="required"> *</span>}</label>
            <div className="input-container">
                {/* Input element */}
                <input
                    type={type}
                    id={id}
                    name={id} // Name attribute is important for form handling
                    value={value}
                    onChange={onChange}
                    onBlur={onBlur} // Trigger validation on blur
                    className={error ? "invalid" : ""} // Add 'invalid' class if there's an error
                    required={isRequired} // HTML5 required attribute
                />
                {/* Success indicator (check mark) shown only if field has value, is valid, and has been touched */}
                {success && !error && value && (
                    <span className="field-success">
                        <i className="fas fa-check-circle"></i>
                    </span>
                )}
            </div>
            {/* Error message display */}
            {error && <div className="error-message"><i className="fas fa-exclamation-circle"></i> {error}</div>}
        </div>
    );
};

/**
 * Password Strength Meter component.
 * Visually indicates the strength of the entered password based on criteria
 * (length, letters, numbers, special characters).
 *
 * @param {object} props - Component props.
 * @param {string} props.password - The password string to evaluate.
 * @returns {JSX.Element|null} The rendered password strength meter or null if password is empty.
 */
const PasswordStrengthMeter = ({ password }) => {
    /**
     * Calculates the password strength based on predefined criteria.
     * @param {string} password - The password to check.
     * @returns {{strength: string, label: string}} Object containing strength category ('weak', 'medium', 'strong') and label.
     */
    const getPasswordStrength = (password) => {
        if (!password) return { strength: "", label: "" }; // No password, no strength

        // Define criteria
        const hasLetter = /[a-zA-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        const isLongEnough = password.length >= 8;

        // Count how many criteria are met
        const criteria = [hasLetter, hasNumber, hasSpecial, isLongEnough];
        const metCriteria = criteria.filter(Boolean).length;

        // Determine strength level based on met criteria count
        if (metCriteria <= 2) return { strength: "weak", label: "Weak" };
        if (metCriteria === 3) return { strength: "medium", label: "Medium" };
        return { strength: "strong", label: "Strong" }; // 4 criteria met
    };

    // Get strength details
    const { strength, label } = getPasswordStrength(password);

    // Don't render the meter if there's no password input yet
    if (!password) return null;

    // Render the visual meter and text label
    return (
        <div className="password-strength">
            <div className="strength-meter">
                {/* The visual bar, class determines the fill color/width */}
                <div className={`strength-meter-fill ${strength}`}></div>
            </div>
            {/* Text label indicating strength */}
            <div className={`strength-text ${strength}`}>{label}</div>
        </div>
    );
};

/**
 * Main Registration Form component.
 * Handles user input, validation, email availability check, and form submission.
 *
 * @param {object} props - Component props.
 * @param {string} props.csrfToken - The CSRF token required for form submission.
 * @returns {JSX.Element} The rendered registration form or success message.
 */
const RegistrationForm = ({ csrfToken }) => {
    // State for form input values
    const [formData, setFormData] = React.useState({
        name: "",
        phone: "",
        email: "",
        password: ""
    });

    // State for validation errors for each field
    const [errors, setErrors] = React.useState({
        name: "",
        phone: "",
        email: "",
        password: ""
    });

    // State to track which fields have been touched (blurred) by the user
    const [touched, setTouched] = React.useState({
        name: false,
        phone: false,
        email: false,
        password: false
    });

    // State flags for submission status and feedback
    const [isSubmitting, setIsSubmitting] = React.useState(false); // True while fetch request is in progress
    const [submitError, setSubmitError] = React.useState(""); // Stores general submission error messages
    const [submitSuccess, setSubmitSuccess] = React.useState(false); // True if registration was successful

    // State flags for asynchronous email checking
    const [isCheckingEmail, setIsCheckingEmail] = React.useState(false); // True while checking email availability
    const [emailExists, setEmailExists] = React.useState(false); // True if the entered email already exists

    // Ref for debouncing email check API calls
    const emailCheckTimeout = React.useRef(null);

    // Validation functions for each field
    const validate = {
        name: (value) => {
            if (!value.trim()) return "Name is required";
            if (!/^[a-zA-Z\s]+$/.test(value)) return "Name should only contain letters and spaces";
            return ""; // No error
        },
        phone: (value) => {
            if (!value.trim()) return "Phone number is required";
            // Basic 10-digit validation
            if (!/^\d{10}$/.test(value)) return "Phone number must be 10 digits";
            return ""; // No error
        },
        email: (value) => {
            if (!value.trim()) return "Email is required";
            // Basic email format validation
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return "Invalid email format";
            // Check against the state updated by the API call
            if (emailExists) return "Email already exists";
            return ""; // No error
        },
        password: (value) => {
            if (!value) return "Password is required";
            if (value.length < 8) return "Password must be at least 8 characters";
            // Further strength validation is visual via PasswordStrengthMeter, not blocking submission here
            return ""; // No error
        }
    };

    /**
     * Handles changes in form input fields.
     * Updates formData state and performs validation if the field has been touched.
     * Debounces email existence check.
     * @param {React.ChangeEvent<HTMLInputElement>} e - The input change event.
     */
    const handleChange = (e) => {
        const { name, value } = e.target;

        // Update the form data state
        setFormData(prevData => ({
            ...prevData,
            [name]: value
        }));

        // If the field has been touched previously, validate it immediately on change
        if (touched[name]) {
            setErrors(prevErrors => ({
                ...prevErrors,
                [name]: validate[name](value)
            }));
        }

        // --- Debounced Email Check ---
        // If the changed field is 'email', it's not empty, and looks like a valid format
        if (name === 'email' && value.trim() && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            // Clear any existing timeout to prevent unnecessary checks
            if (emailCheckTimeout.current) {
                clearTimeout(emailCheckTimeout.current);
            }
            // Set a new timeout to check email after a short delay (e.g., 500ms)
            emailCheckTimeout.current = setTimeout(() => {
                checkEmailExists(value);
            }, 500);
        } else if (name === 'email') {
            // If email is cleared or invalid format, clear any pending check and reset emailExists state
            if (emailCheckTimeout.current) {
                clearTimeout(emailCheckTimeout.current);
            }
            setEmailExists(false);
            // If the current error is specifically "Email already exists", clear it
            if (errors.email === "Email already exists") {
                setErrors(prevErrors => ({ ...prevErrors, email: "" }));
            }
        }
    };

    /**
     * Handles the blur event on form input fields.
     * Marks the field as touched and triggers validation.
     * @param {React.FocusEvent<HTMLInputElement>} e - The input blur event.
     */
    const handleBlur = (e) => {
        const { name } = e.target;

        // Mark the field as touched
        setTouched(prevTouched => ({
            ...prevTouched,
            [name]: true
        }));

        // Validate the field now that it has been touched and blurred
        setErrors(prevErrors => ({
            ...prevErrors,
            [name]: validate[name](formData[name])
        }));

        // If the blurred field is email, trigger the check immediately if valid format
        // (Handles cases where user tabs out quickly before debounce timer fires)
        if (name === 'email' && formData.email.trim() && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
            if (emailCheckTimeout.current) {
                clearTimeout(emailCheckTimeout.current); // Clear debounce timer
            }
            checkEmailExists(formData.email);
        }
    };

    /**
     * Checks if the provided email already exists in the database via an AJAX call.
     * Updates the `emailExists` state and potentially the `errors.email` state.
     * @param {string} email - The email address to check.
     */
    const checkEmailExists = (email) => {
        setIsCheckingEmail(true); // Show loading indicator
        setEmailExists(false); // Assume not exists initially for this check

        // Use FormData for simple POST request
        const data = new FormData();
        data.append('email', email);

        fetch('ajax/check-email', { // Endpoint for checking email
            method: 'POST',
            body: data
        })
            .then(response => response.json()) // Expect JSON response { exists: boolean }
            .then(data => {
                setIsCheckingEmail(false); // Hide loading indicator
                if (data.exists) {
                    setEmailExists(true); // Set flag if email exists
                    // Update the errors state immediately to show the message
                    setErrors(prevErrors => ({
                        ...prevErrors,
                        email: "Email already exists"
                    }));
                } else {
                    // If email doesn't exist, ensure the "Email already exists" error is cleared
                    // (in case it was set previously and the user corrected the email)
                    if (errors.email === "Email already exists") {
                        setErrors(prevErrors => ({ ...prevErrors, email: "" }));
                    }
                }
            })
            .catch(error => {
                console.error('Error checking email:', error);
                setIsCheckingEmail(false); // Hide loading indicator on error
                // Optionally show a generic error to the user or handle silently
            });
    };

    /**
     * Handles the form submission event.
     * Performs final validation on all fields, then sends registration data to the server.
     * Displays success or error messages. Redirects on success.
     * @param {React.FormEvent<HTMLFormElement>} e - The form submission event.
     */
    const handleSubmit = (e) => {
        e.preventDefault(); // Prevent default browser form submission

        // --- Final Validation ---
        // Mark all fields as touched to trigger validation messages for untouched fields
        const allTouched = {};
        Object.keys(formData).forEach(key => { allTouched[key] = true; });
        setTouched(allTouched);

        // Re-validate all fields and check if any errors exist
        const newErrors = {};
        let hasErrors = false;
        Object.keys(formData).forEach(key => {
            const error = validate[key](formData[key]);
            newErrors[key] = error;
            if (error) hasErrors = true;
        });
        setErrors(newErrors); // Update errors state with results of final validation

        // If there are any validation errors, stop submission
        if (hasErrors) return;

        // --- Submit Data ---
        setIsSubmitting(true); // Show loading state on button
        setSubmitError(""); // Clear previous submission errors

        // Prepare data using FormData
        const data = new FormData();
        Object.keys(formData).forEach(key => {
            data.append(key, formData[key]);
        });
        data.append('csrf_token', csrfToken); // Add CSRF token

        // Send POST request to the registration endpoint
        fetch('register', { // Assumes relative URL 'register'
            method: 'POST',
            headers: {
                // Indicate an AJAX request (useful for backend differentiation)
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data
        })
            .then(response => {
                // Handle HTTP errors first
                if (!response.ok) {
                    // Try to include status text for more context
                    throw new Error(`HTTP error! status: ${response.status} ${response.statusText || ''}`);
                }
                // Expect JSON response on success or failure
                return response.json();
            })
            .then(data => {
                setIsSubmitting(false); // Hide loading state
                if (data.success) {
                    // --- Handle Success ---
                    setSubmitSuccess(true); // Show success message
                    // Clear form fields
                    setFormData({ name: "", phone: "", email: "", password: "" });
                    // Reset touched state
                    setTouched({ name: false, phone: false, email: false, password: false });
                    // Redirect to login page after a short delay
                    setTimeout(() => {
                        window.location.href = '/login'; // Redirect
                    }, 2000); // 2-second delay
                } else {
                    // --- Handle Server-Side Failure ---
                    setSubmitError(data.message || "Registration failed. Please try again.");
                }
            })
            .catch(error => {
                // --- Handle Fetch/Network Errors ---
                console.error('Error during registration:', error);
                setIsSubmitting(false); // Hide loading state
                // Provide specific feedback for common errors like CSRF failure (403)
                if (error.message.includes('403')) {
                    setSubmitError("Security token validation failed. Please refresh the page and try again.");
                } else {
                    setSubmitError("An error occurred. Please try again later.");
                }
            });
    };

    // --- Render Logic ---
    return (
        <div className="register-form-container">
            {/* Conditional rendering: Show success message or the form */}
            {submitSuccess ? (
                // Success Message View
                <div className="alert alert-success">
                    <p>Registration successful! Redirecting to login page...</p>
                </div>
            ) : (
                // Form View
                <React.Fragment>
                    {/* Display general submission errors */}
                    {submitError && (
                        <div className="alert alert-error">
                            <p>{submitError}</p>
                        </div>
                    )}
                    {/* The actual form */}
                    <form onSubmit={handleSubmit} className="register-form" noValidate> {/* Add noValidate to rely on custom validation */}
                        {/* Reusable FormInput components for each field */}
                        <FormInput
                            id="name"
                            label="Full Name"
                            type="text"
                            value={formData.name}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={touched.name ? errors.name : ""} // Show error only if touched
                            success={touched.name && !errors.name} // Show success only if touched and valid
                        />
                        <FormInput
                            id="phone"
                            label="Phone Number (10 digits)"
                            type="tel" // Use 'tel' type for semantic meaning
                            value={formData.phone}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={touched.phone ? errors.phone : ""}
                            success={touched.phone && !errors.phone}
                        />
                        <FormInput
                            id="email"
                            label="Email Address"
                            type="email" // Use 'email' type for browser hints/validation
                            value={formData.email}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={touched.email ? errors.email : ""}
                            // Show success only if touched, valid, and not currently checking
                            success={touched.email && !errors.email && !isCheckingEmail}
                        />
                        {/* Loading indicator while checking email */}
                        {isCheckingEmail && (
                            <div className="loading-indicator">
                                <i className="fas fa-spinner fa-spin"></i> Checking email...
                            </div>
                        )}
                        <FormInput
                            id="password"
                            label="Password"
                            type="password"
                            value={formData.password}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={touched.password ? errors.password : ""}
                            success={touched.password && !errors.password}
                        />
                        {/* Password strength meter component */}
                        <PasswordStrengthMeter password={formData.password} />

                        {/* Submit Button */}
                        <div className="form-group">
                            <button
                                type="submit"
                                className="btn btn-primary"
                                disabled={isSubmitting} // Disable button while submitting
                            >
                                {isSubmitting ? (
                                    // Show loading text and spinner
                                    <React.Fragment>
                                        <i className="fas fa-spinner fa-spin"></i> Registering...
                                    </React.Fragment>
                                ) : (
                                    // Default button text
                                    "Register"
                                )}
                            </button>
                        </div>
                    </form>
                    {/* Link to login page */}
                    <div className="login-link">
                        <p>Already have an account? <a href="/login">Login here</a></p>
                    </div>
                </React.Fragment>
            )}
        </div>
    );
};
