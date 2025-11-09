# User Management Flows

This document describes the user management flows available in WebFramework, including registration, login, password reset, and email change processes. Understanding these flows is essential for developers building authentication and user management features.

## Overview

WebFramework provides a comprehensive set of user management flows that handle authentication, verification, and account management. All flows use a code-based verification system where users receive verification codes via email to complete sensitive operations.

## Common Components

All user management flows share several common components:

- **UserCodeService**: Generates and verifies verification codes with expiration and attempt tracking
- **Verification Codes Database**: Codes are stored in the database with GUIDs, attempt counts, and expiration times
- **Security Iterator**: Prevents replay attacks by tracking security state changes
- **Email Verification**: Codes are sent via email and must be entered to complete flows
- **Brute Force Protection**: Maximum of 5 attempts per code to prevent brute force attacks

## Flow 1: Registration

The registration flow allows new users to create accounts with email verification.

### Step 1: Register Action (`/register`)

**Action**: `WebFramework\Actions\Register`

**Process**:
1. User submits registration form (username, email, password, password2)
2. Validates input (username/email format, password strength, CAPTCHA)
3. Checks username/email availability
4. Creates user account via `RegisterService::register()`
5. Generates verification code entry in database via `UserCodeService::generateVerificationCodeEntry()`
6. Sends verification email via `UserVerificationService::sendVerifyMail()` with code
7. Redirects to Verify page with `guid` parameter

**Configuration**:
- `actions.register.post_verify_page`: Page to redirect after verification
- `actions.register.default_page`: Default page after successful registration
- `actions.verify.location`: Location of verification page

### Step 2: Verify Action (`/verify`)

**Action**: `WebFramework\Actions\Verify`

**Process**:
1. User enters verification code from email
2. Verifies code via `UserCodeService::verifyCodeByGuid()`
3. Validates code matches, hasn't expired, hasn't been used, and hasn't exceeded max attempts
4. Marks code as used and increments attempt counter
5. Redirects to RegisterVerify with `guid` parameter

**Required Parameters**:
- `guid`: GUID from registration email
- `flow`: Must be 'register'
- `code`: Verification code from email

### Step 3: RegisterVerify Action (`/register-verify`)

**Action**: `WebFramework\Actions\RegisterVerify`

**Process**:
1. Extracts user and flow from `guid` via `UserVerificationService::handleData()`
2. Verifies flow is 'register'
3. Sets user as verified (if not already verified)
4. Authenticates user via `AuthenticationService::authenticate()`
5. Calls `RegisterService::postVerify()` hook (extensible for custom logic)
6. Redirects to `actions.register.default_page`

**Customization**: Override `RegisterService::postVerify()` to add custom logic after verification.

## Flow 2: Login with Verification

The login flow handles user authentication, including cases where users need to verify their email.

### Step 1: Login Action (`/login`)

**Action**: `WebFramework\Actions\Login`

**Process**:
1. User submits credentials (username/email, password)
2. Validates credentials via `LoginService::validate()`
3. Checks if user is verified and verification hasn't expired
4. If user not verified → throws `UserVerificationRequiredException`
5. Generates verification code entry and sends verification email with flow='login' and return_page/return_query
6. Redirects to Verify page with `guid` parameter

**Configuration**:
- `actions.login.default_return_page`: Default page after successful login
- `actions.login.after_verify_page`: Page to redirect after verification
- `actions.verify.location`: Location of verification page

**Note**: If user is already verified, login proceeds directly without verification step.

### Step 2: Verify Action (`/verify`)

**Action**: `WebFramework\Actions\Verify`

**Process**:
1. User enters verification code from email
2. Verifies code via `UserCodeService::verifyCodeByGuid()`
3. Marks code as used
4. Redirects to LoginVerify with `guid` parameter

**Required Parameters**:
- `guid`: GUID from login verification email
- `flow`: Must be 'login'
- `code`: Verification code from email

### Step 3: LoginVerify Action (`/login-verify`)

**Action**: `WebFramework\Actions\LoginVerify`

**Process**:
1. Extracts user, flow, and after_verify_data from `guid`
2. Verifies flow is 'login'
3. Authenticates user via `AuthenticationService::authenticate()`
4. Redirects to `after_verify_data['return_page']` with `return_query` parameters

## Flow 3: Password Reset

The password reset flow allows users to reset forgotten passwords via email verification.

### Step 1: ForgotPassword Action (`/forgot-password`)

**Action**: `WebFramework\Actions\ForgotPassword`

**Process**:
1. User submits username/email
2. Finds user via `UserRepository::getUserByUsername()`
3. Calls `ResetPasswordService::sendPasswordResetMail()`
4. Generates verification code entry in database with flow='reset_password'
5. Increments security iterator to invalidate old reset links
6. Sends password reset email with code
7. Redirects to Verify page with `flow=reset_password` and `guid` parameter

**Configuration**:
- `actions.forgot_password.reset_password_page`: Page to redirect after verification
- `actions.verify.location`: Location of verification page

### Step 2: Verify Action (`/verify`)

**Action**: `WebFramework\Actions\Verify`

**Process**:
1. User enters verification code from email
2. Verifies code via `UserCodeService::verifyCodeByGuid()`
3. Marks code as used
4. Redirects to ResetPassword with `guid` parameter

**Required Parameters**:
- `guid`: GUID from password reset email
- `flow`: Must be 'reset_password'
- `code`: Verification code from email

### Step 3: ResetPassword Action (`/reset-password`)

**Action**: `WebFramework\Actions\ResetPassword`

**Process**:
1. Calls `ResetPasswordService::handleData()` with `guid`
2. Retrieves verification code from database and verifies security iterator matches
3. Generates new random password via `sendNewPassword()`
4. Updates password hash and increments security iterator
5. Sends new password email to user
6. Invalidates all user sessions via `AuthenticationService::invalidateSessions()`
7. Redirects to login page

**Security**: The security iterator check ensures that if a user requests multiple password resets, only the most recent reset link will work.

## Flow 4: Change Email

The change email flow allows authenticated users to change their email address with verification.

### Step 1: ChangeEmail Action (`/change-email`)

**Action**: `WebFramework\Actions\ChangeEmail`

**Process**:
1. Authenticated user submits new email address
2. Validates email format and uniqueness
3. Calls `ChangeEmailService::sendChangeEmailVerify()`
4. Generates verification code entry in database with flow='change_email'
5. Increments security iterator
6. Sends verification email to **new** email address with code
7. Redirects to Verify page with `guid` parameter

**Configuration**:
- `actions.change_email.after_verify_page`: Page to redirect after verification
- `actions.change_email.return_page`: Page to redirect after successful email change
- `actions.verify.location`: Location of verification page

**Security**: Verification email is sent to the new address to ensure the user has access to it.

### Step 2: Verify Action (`/verify`)

**Action**: `WebFramework\Actions\Verify`

**Process**:
1. Requires authentication for change_email flow
2. User enters verification code from email (sent to new address)
3. Verifies code via `UserCodeService::verifyCodeByGuid()`
4. Marks code as used
5. Redirects to ChangeEmailVerify with `guid` parameter

**Required Parameters**:
- `guid`: GUID from change email verification email
- `flow`: Must be 'change_email'
- `code`: Verification code from email

### Step 3: ChangeEmailVerify Action (`/change-email-verify`)

**Action**: `WebFramework\Actions\ChangeEmailVerify`

**Process**:
1. Calls `ChangeEmailService::handleData()` with authenticated user and `guid`
2. Retrieves verification code from database
3. Verifies code belongs to current authenticated user (throws `WrongAccountException` if not)
4. Verifies security iterator hasn't changed
5. Updates email address (and username if `unique_identifier='email'`)
6. Invalidates all user sessions via `AuthenticationService::invalidateSessions()`
7. Re-authenticates user
8. Dispatches `UserEmailChanged` event
9. Redirects to `actions.change_email.return_page`

**Security**: Multiple security checks ensure:
- Code belongs to the authenticated user
- Security iterator prevents replay attacks
- All sessions are invalidated after email change

## Configuration Reference

All flows require configuration in `config/config.php`. Key configuration options:

```php
return [
    'actions' => [
        'login' => [
            'location' => '/login',                 // Location of the login page
            'after_verify' => '/login/verify',      // Location to redirect to after verifying
            'default_return_page' => '/',           // Default return page
            'bruteforce_protection' => true,        // Enable bruteforce protection
            'template_name' => 'Login.latte',       // Template name for login page
        ],
        'reset_password' => [
            'location' => '/forgot-password',    // Location of the forgot password page
            'after_verify' => '/reset-password', // Location of the reset password page
            'template_name' => 'ForgotPassword.latte', // Template name for forgot password page
        ],
        'change_password' => [
            'location' => '/change-password',   // Location of the change password page
            'return_page' => '/',               // Location to redirect after changing password
            'template_name' => 'ChangePassword.latte', // Template name for change password page
        ],
        'change_email' => [
            'location' => '/change-email',            // Location of the change email page
            'after_verify' => '/change-email/verify', // Location of the change email verify page
            'return_page' => '/',                     // Location to redirect after changing email
            'template_name' => 'ChangeEmail.latte',   // Template name for change email page
        ],
        'register' => [
            'location' => '/register',            // Location of the register page
            'after_verify' => '/register/verify', // Location to redirect to after verifying
            'return_page' => '/',                 // Default page to redirect to after verifying
            'template_name' => 'Register.latte',  // Template name for register page
        ],
        'verify' => [
            'location' => '/verify',
        ],
        'forgot_password' => [
            'location' => '/forgot-password',
            'reset_password_page' => '/reset-password',
            'after_verify_page' => '/reset-password',
        ],
        'change_email' => [
            'location' => '/change-email',
            'after_verify_page' => '/change-email-verify',
            'return_page' => '/settings',
        ],
    ],
    'security' => [
        'email_verification' => [
            'code_length' => 6,             // Length of verification code
            'code_expiry_minutes' => 15,    // Minutes until code expires
            'max_attempts' => 5,           // Maximum verification attempts per code
        ],
        'validity_period_days' => 365, // Email verification validity period
    ],
];
```

## Customization Points

### RegisterService::postVerify()

Override this method to add custom logic after user registration verification:

```php
public function postVerify(User $user, array $afterVerifyParams = []): void
{
    // Add custom logic here
    // e.g., send welcome email, create default settings, etc.
}
```

### Custom Action Methods

All action classes provide extensible methods:

- **Register**: `customPreparePageContent()`, `customValueCheck()`, `customFinalizeCreate()`, `getAfterVerifyData()`
- **Login**: `customValueCheck()`
- **ChangeEmail**: `customParams()`

Override these methods in your action classes to customize behavior.

## Security Features

All flows include multiple security features:

1. **Code Expiration**: Verification codes expire after a configured time period (default: 15 minutes)
2. **Attempt Tracking**: Maximum of 5 attempts per code prevents brute force attacks (this is the primary protection)
3. **Security Iterator**: Prevents replay attacks by tracking state changes
4. **Database Storage**: Codes stored in database with GUIDs enable audit trails and tracking
5. **Session Invalidation**: Password and email changes invalidate all sessions
6. **Account Verification**: Ensures verification codes belong to the correct user
7. **CAPTCHA Protection**: Registration and login can require CAPTCHA verification
8. **One-Time Use**: Codes are marked as used after successful verification

**Note**: Codes are stored in plaintext in the database. This is acceptable because:
- The attempt limit (5 attempts) prevents brute force attacks
- Codes expire quickly (default: 15 minutes)
- Codes are single-use only
- The small code space (6 characters) would be brute-forceable regardless of hashing

## Events

The following events are dispatched during user management flows:

- `UserRegistered`: Dispatched when a new user is registered
- `UserVerified`: Dispatched when a user's email is verified
- `UserLoggedIn`: Dispatched when a user successfully logs in
- `UserEmailChanged`: Dispatched when a user's email is changed

Listen to these events to add custom behavior (e.g., logging, notifications, analytics).

## Error Handling

All flows handle various error conditions:

- **CodeVerificationException**: Invalid or expired verification code
- **InvalidCodeException**: Code doesn't match protected data
- **WrongAccountException**: Code belongs to different user (change email flow)
- **UserVerificationRequiredException**: User needs to verify email before login
- **DuplicateEmailException**: Email already in use
- **ValidationException**: Input validation failures

Error messages are displayed via the `MessageService` and appropriate redirects are performed.

