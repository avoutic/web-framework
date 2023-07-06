<?php declare(strict_types = 1);

namespace WebFramework\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\MethodTypeSpecifyingExtension;

class FactoryCoreVerifyMethodTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private $typeSpecifier;

    public function getClass(): string
    {
        return "WebFramework\Core\FactoryCore";
    }

    public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool
    {
        $methodName = $methodReflection->getName();

        return ($methodName === 'verify' || $methodName === 'blacklist404') && isset($node->getArgs()[0]);
    }

    public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $expr = $node->getArgs()[0]->value;

        return $this->typeSpecifier->specifyTypesInCondition($scope, $expr, TypeSpecifierContext::createTruthy());
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }
}
