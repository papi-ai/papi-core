<?php

namespace Papi\Core\Tools;

/**
 * MathTool - Tool for mathematical operations
 *
 * Allows AI agents to perform calculations and mathematical operations.
 */
class MathTool implements ToolInterface
{
    public function getName(): string
    {
        return 'math_calculation';
    }

    public function getDescription(): string
    {
        return 'Perform mathematical calculations and operations';
    }

    /**
     * @return array<string, array<string, string|bool>>
     */
    public function getParameters(): array
    {
        return [
            'operation' => [
                'type' => 'string',
                'description' => 'The mathematical operation to perform (add, subtract, multiply, divide, power, sqrt)',
                'required' => true
            ],
            'a' => [
                'type' => 'number',
                'description' => 'First number for the operation',
                'required' => true
            ],
            'b' => [
                'type' => 'number',
                'description' => 'Second number for the operation (not needed for sqrt)',
                'required' => false
            ]
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function execute(array $params): array
    {
        if (!$this->validate($params)) {
            return [
                'success' => false,
                'error' => 'Invalid parameters'
            ];
        }

        $operation = strtolower($params['operation']);
        $a = (float) $params['a'];
        $b = isset($params['b']) ? (float) $params['b'] : null;

        try {
            if ($operation === 'divide' && $b == 0) {
                return [
                    'success' => false,
                    'error' => 'Division by zero'
                ];
            }
            if ($operation === 'sqrt' && $a < 0) {
                return [
                    'success' => false,
                    'error' => 'Cannot calculate square root of negative number'
                ];
            }
            $result = match ($operation) {
                'add' => $a + $b,
                'subtract' => $a - $b,
                'multiply' => $a * $b,
                'divide' => $a / $b,
                'power' => $a ** $b,
                'sqrt' => sqrt($a),
                default => throw new \InvalidArgumentException("Unknown operation: $operation")
            };

            return [
                'success' => true,
                'result' => $result,
                'operation' => $operation,
                'inputs' => ['a' => $a, 'b' => $b]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public function validate(array $params): bool
    {
        if (!isset($params['operation']) || empty($params['operation'])) {
            return false;
        }

        if (!isset($params['a']) || !is_numeric($params['a'])) {
            return false;
        }

        $operation = strtolower($params['operation']);
        $validOperations = ['add', 'subtract', 'multiply', 'divide', 'power', 'sqrt'];

        if (!in_array($operation, $validOperations)) {
            return false;
        }

        // For operations that need two numbers
        if ($operation !== 'sqrt' && (!isset($params['b']) || !is_numeric($params['b']))) {
            return false;
        }

        return true;
    }
}
