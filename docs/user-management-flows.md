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
4. Calls `RegisterExtensionInterface::customValueCheck()` for custom validation
5. Gets after-verify data via `RegisterExtensionInterface::getAfterVerifyData()`
6. Creates user account via `RegisterService::register()` (which generates verification code and sends email) and dispatches `UserRegistered` event
7. Calls `RegisterExtensionInterface::postCreate()` hook
8. Redirects to Verify page with `guid` parameter

**Extension Hooks**:
- `getCustomParams()`: Add custom template parameters
- `customValueCheck()`: Perform additional validation checks
- `getAfterVerifyData()`: Provide data to pass through verification
- `postCreate()`: Execute logic after user creation
- `postVerify()`: Execute logic after verification (called in RegisterVerify)

**Configuration**:
- `actions.register.return_page`: Page to redirect after successful registration verification
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
5. Calls `RegisterExtensionInterface::postVerify()` hook (extensible for custom logic)
6. Redirects to `actions.register.return_page`

**Customization**: Implement `RegisterExtensionInterface` and configure it in dependency injection to add custom logic.

## Flow 2: Login with Verification

The login flow handles user authentication, including cases where users need to verify their email.

### Step 1: Login Action (`/login`)

**Action**: `WebFramework\Actions\Login`

**Process**:
1. User submits credentials (username/email, password)
2. Validates credentials via `LoginService::validate()`
3. Checks if user is verified and verification hasn't expired
4. If user not verified → throws `UserVerificationRequiredException`
5. Calls `LoginExtensionInterface::customValueCheck()` for custom validation
6. If checks pass, authenticates user and redirects and dispatches `UserLoggedIn` event
7. If user not verified, generates verification code entry and sends verification email
8. Redirects to Verify page with `guid` parameter

**Extension Hooks**:
- `getCustomParams()`: Add custom template parameters
- `customValueCheck()`: Perform additional validation checks before authentication

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

### Step 1: ResetPassword Action (`/reset-password`)

**Action**: `WebFramework\Actions\ResetPassword`

**Process**:
1. User submits username/email
2. Validates input
3. Calls `ResetPasswordExtensionInterface::customValueCheck()` for custom validation
4. Finds user via `UserRepository::getUserByUsername()`
5. Calls `ResetPasswordService::sendPasswordResetMail()`
6. Generates verification code entry in database with action='reset_password'
7. Increments security iterator to invalidate old reset links
8. Sends password reset email with code
9. Redirects to Verify page with `guid` parameter

**Extension Hooks**:
- `getCustomParams()`: Add custom template parameters
- `customValueCheck()`: Perform additional validation checks

**Configuration**:
- `actions.reset_password.after_verify_page`: Page to redirect after verification
- `actions.verify.location`: Location of verification page

### Step 2: Verify Action (`/verify`)

**Action**: `WebFramework\Actions\Verify`

**Process**:
1. User enters verification code from email
2. Verifies code via `UserCodeService::verifyCodeByGuid()`
3. Marks code as used
4. Redirects to ResetPasswordVerify with `guid` parameter

**Required Parameters**:
- `guid`: GUID from password reset email
- `flow`: Must be 'reset_password'
- `code`: Verification code from email

### Step 3: ResetPasswordVerify Action (`/reset-password-verify`)

**Action**: `WebFramework\Actions\ResetPasswordVerify`

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
3. Calls `ChangeEmailExtensionInterface::customValueCheck()` for custom validation
4. Calls `ChangeEmailService::sendChangeEmailVerify()`
5. Generates verification code entry in database with action='change_email'
6. Increments security iterator
7. Sends verification email to **new** email address with code
8. Redirects to Verify page with `guid` parameter

**Extension Hooks**:
- `getCustomParams()`: Add custom template parameters
- `customValueCheck()`: Perform additional validation checks

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
<?php

return [
    'actions' => [
        'login' => [
            'location' => '/login',                 // Location of the login page
            'after_verify' => '/login/verify',      // Location to redirect to after verifying
            'default_return_page' => '/',           // Default return page
            'template_name' => 'Login.latte',       // Template name for login page
        ],
        'reset_password' => [
            'location' => '/reset-password',            // Location of the reset password page
            'after_verify' => '/reset-password/verify', // Location to redirect to after verifying
            'template_name' => 'ResetPassword.latte',   // Template name for reset password page
        ],
        'change_password' => [
            'location' => '/change-password',          // Location of the change password page
            'return_page' => '/',                      // Location to redirect after changing password
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
            'location' => '/verify',            // Location of the verify page
            'templates' => [                    // Template names per action type
                'login' => 'Verify.latte',
                'register' => 'Verify.latte',
                'reset_password' => 'Verify.latte',
                'change_email' => 'Verify.latte',
            ],
        ],
    ],
    'security' => [
        'email_verification' => [
            'code_length' => 6,             // Length of verification code
            'code_expiry_minutes' => 15,    // Minutes until code expires
            'max_attempts' => 5,           // Maximum verification attempts per code
        ],
        'validity_period_days' => 365,      // Email verification validity period
    ],
];
```

## Customization Points

WebFramework uses an extension interface system for customizing user management flows. Instead of overriding action classes or service methods, implement the appropriate extension interface and configure it via dependency injection.

### Extension Interfaces

Each flow has a corresponding extension interface:

- **RegisterExtensionInterface**: Customize registration flow
- **LoginExtensionInterface**: Customize login flow
- **ChangeEmailExtensionInterface**: Customize email change flow
- **ChangePasswordExtensionInterface**: Customize password change flow
- **ResetPasswordExtensionInterface**: Customize password reset flow

### Implementing an Extension

1. **Create your extension class** implementing the appropriate interface:

```php
<?php

use WebFramework\Security\Extension\RegisterExtensionInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;

class MyRegisterExtension implements RegisterExtensionInterface
{
    public function getCustomParams(Request $request): array
    {
        return [
            'customField' => 'value',
        ];
    }

    public function customValueCheck(Request $request): bool
    {
        // Perform additional validation
        return true;
    }

    public function getAfterVerifyData(Request $request): array
    {
        return [
            'referral_code' => $request->getParam('referral_code'),
        ];
    }

    public function postCreate(Request $request, User $user): void
    {
        // Execute logic after user creation
        // e.g., create default settings, send welcome email
    }

    public function postVerify(User $user, array $afterVerifyParams): void
    {
        // Execute logic after verification
        // e.g., process referral code from $afterVerifyParams
    }
}
```

2. **Configure in dependency injection** (`definitions/definitions.php`):

```php
<?php

return [
    // ... other definitions ...
    Security\Extension\RegisterExtensionInterface::class => DI\autowire(MyRegisterExtension::class),
];
```

### Available Extension Hooks

#### RegisterExtensionInterface
- `getCustomParams(Request $request): array` - Add custom template parameters
- `customValueCheck(Request $request): bool` - Perform additional validation before user creation
- `getAfterVerifyData(Request $request): array` - Provide data to pass through verification
- `postCreate(Request $request, User $user): void` - Execute logic after user creation
- `postVerify(User $user, array $afterVerifyParams): void` - Execute logic after verification

#### LoginExtensionInterface
- `getCustomParams(Request $request): array` - Add custom template parameters
- `customValueCheck(Request $request, User $user): bool` - Perform additional validation before authentication

#### ChangeEmailExtensionInterface
- `getCustomParams(Request $request): array` - Add custom template parameters
- `customValueCheck(Request $request, User $user): bool` - Perform additional validation

#### ChangePasswordExtensionInterface
- `getCustomParams(Request $request): array` - Add custom template parameters
- `customValueCheck(Request $request, User $user): bool` - Perform additional validation

#### ResetPasswordExtensionInterface
- `getCustomParams(Request $request): array` - Add custom template parameters
- `customValueCheck(Request $request): bool` - Perform additional validation

### Default Implementations

Null implementations are provided as defaults (`NullRegisterExtension`, `NullLoginExtension`, etc.) that perform no operations. These are configured in `definitions/definitions.php` and can be replaced with your custom implementations.

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

