# Input Validation

This document provides a guide for developers on how to add input validation to a new action in the WebFramework. It leverages the `InputValidationService` and various validators to ensure that user input meets the required criteria before processing.

## Steps to Add Input Validation

### 1. Define Your Action Class

Create a new action class in the `actions` directory. This class should implement the necessary logic for handling the request and response.

### 2. Inject Dependencies

In the constructor of your action class, inject the `InputValidationService` along with any other services you need. This service will be used to validate the input data.

~~~php
use WebFramework\Validation\InputValidationService;

class YourNewAction
{
    public function __construct(
        protected InputValidationService $inputValidationService,
        // other dependencies...
    ) {}
}
~~~

### 3. Define Validation Rules

Inside your action method (usually `__invoke`), define the validation rules for the input data. Use the appropriate validators for each field. For example, use `EmailValidator` for email fields, `PasswordValidator` for password fields, etc.

~~~php
use WebFramework\Validation\Valdidator\EmailValidator;
use WebFramework\Validation\Valdidator\PasswordValidator;

$validators = [
    'email' => (new EmailValidator())->required(),
    'password' => new PasswordValidator(),
];
~~~

### 4. Validate Input Data

Use the `InputValidationService` to validate the input data against the defined rules. Pass the request parameters to the `validate` method.

~~~php
$filtered = $this->inputValidationService->validate(
    $validators,
    $request->getParams()
);
~~~

### 5. Handle Validation Errors

If validation fails, the `InputValidationService` will throw a `MultiValidationException`. Catch this exception and handle the errors appropriately, such as by adding error messages to the response.

~~~php
use WebFramework\Exception\ValidationException;

try {
    $filtered = $this->inputValidationService->validate(
        $validators,
        $request->getParams()
    );
    // Proceed with processing the validated data
} catch (ValidationException $e) {
    $this->messageService->addErrors($e->getErrors());
    // Render the form again with error messages
}
~~~

### 6. Process Validated Data

Once the data is validated, you can safely use the `$filtered` array to access the validated input values and proceed with your action's logic.

~~~php
$email = $filtered['email'];
$password = $filtered['password'];

// Perform actions with validated data
~~~

## Example

Here's a simplified example of an action that validates an email and password:

~~~php
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\Valdidator\EmailValidator;
use WebFramework\Validation\Valdidator\PasswordValidator;
use WebFramework\Exception\ValidationException;

class ExampleAction
{
    public function __construct(
        protected InputValidationService $inputValidationService,
        // other dependencies...
    ) {}

    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $validators = [
            'email' => (new EmailValidator())->required(),
            'password' => new PasswordValidator(),
        ];

        try {
            $filtered = $this->inputValidationService->validate(
                $validators,
                $request->getParams()
            );

            // Use validated data
            $email = $filtered['email'];
            $password = $filtered['password'];

            // Perform action logic...

            return $response;
        } catch (ValidationException $e) {
            // Handle validation errors
            return $response->withStatus(400);
        }
    }
}
~~~

## Default Validators

The WebFramework provides several default validators to handle common validation scenarios. Here's a list of the available validators:

### EmailValidator

- **Class**: `WebFramework\Validation\Valdidator\EmailValidator`
- **Purpose**: Validates email addresses.
- **Usage**: Ensures the input is a valid email format and optionally checks for a maximum length.

#### Example

~~~php
$validators = ['email' => (new EmailValidator())->required()];
~~~

### PasswordValidator

- **Class**: `WebFramework\Validation\Valdidator\PasswordValidator`
- **Purpose**: Validates passwords.
- **Usage**: Ensures the input meets password requirements, such as being non-empty.

#### Example

~~~php
$validators = ['password' => new PasswordValidator()];
~~~

### UsernameValidator

- **Class**: `WebFramework\Validation\Valdidator\UsernameValidator`
- **Purpose**: Validates usernames.
- **Usage**: Ensures the input is a valid username format and optionally checks for a maximum length.

#### Example

~~~php
$validators = ['username' => (new UsernameValidator())->required()];
~~~

### CustomBoolValidator

- **Class**: `WebFramework\Validation\Valdidator\CustomBoolValidator`
- **Purpose**: Validates boolean values.
- **Usage**: Ensures the input is either '0', '1', 'true', or 'false'.

#### Example

~~~php
$validators = ['accept_terms' => (new CustomBoolValidator('accept_terms'))->required()];
~~~

### CustomNumberValidator

- **Class**: `WebFramework\Validation\Valdidator\CustomNumberValidator`
- **Purpose**: Validates numeric values.
- **Usage**: Ensures the input is a number and optionally checks for minimum and maximum values.

#### Example

~~~php
$validators = ['age' => (new CustomNumberValidator('age'))->minValue(18)->maxValue(99)];
~~~

### IdValidator

- **Class**: `WebFramework\Validation\Valdidator\IdValidator`
- **Purpose**: Validates ID fields.
- **Usage**: Ensures the input is a valid ID format and can be converted to an integer.

#### Example

~~~php
$validators = ['user_id' => (new IdValidator('user_id'))->required()];
~~~

## Using CustomValidator

The `WebFramework\Validation\Valdidator\CustomValidator` class is a flexible validator that can be extended to create custom validation logic. It provides basic validation functionality and can be configured with various rules.

### Key Features

- **Filter**: Apply a regex filter to the input.
- **Required**: Mark the field as required.
- **Min/Max Length**: Set minimum and maximum length constraints.
- **Default Value**: Specify a default value if the input is empty.

### Example Usage

To create a custom validator, extend the `CustomValidator` class and define your validation logic:

~~~php
use WebFramework\Validation\CustomValidator;

class MyCustomValidator extends CustomValidator
{
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->filter('my_custom_regex')->required()->maxLength(100);
    }

    public function getTyped(string $value): mixed
    {
        // Custom conversion logic
        return strtoupper($value);
    }
}
~~~

### Validation Rules

The WebFramework includes several implementations of the `ValidationRule` interface, which are used to define specific validation criteria for input data. Here are the available rules:

### FilterRule

- **Class**: `WebFramework\Validation\Rule\FilterRule`
- **Purpose**: Provides regex-based filtering.
- **Usage**: Ensures the input matches a specified regular expression.

### MaxLengthRule

- **Class**: `WebFramework\Validation\Rule\MaxLengthRule`
- **Purpose**: Validates maximum length.
- **Usage**: Ensures the input does not exceed a specified length.

### MinLengthRule

- **Class**: `WebFramework\Validation\Rule\MinLengthRule`
- **Purpose**: Validates minimum length.
- **Usage**: Ensures the input meets a specified minimum length.

### MaxValueRule

- **Class**: `WebFramework\Validation\Rule\MaxValueRule`
- **Purpose**: Validates maximum value.
- **Usage**: Ensures the input does not exceed a specified value.

### MinValueRule

- **Class**: `WebFramework\Validation\Rule\MinValueRule`
- **Purpose**: Validates minimum value.
- **Usage**: Ensures the input meets a specified minimum value.

These rules can be used in conjunction with validators to enforce specific constraints on input data, ensuring that it meets the required criteria before being processed.
