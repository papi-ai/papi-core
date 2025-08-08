<?php

declare(strict_types=1);

namespace Papi\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Papi\Core\Nodes\Node;

class NodeTest extends TestCase
{
    #[Test]
    public function it_exposes_properties_and_methods(): void
    {
        $node = new class ('test-node', 'Test Node') implements Node {
            private string $id;
            private string $name;
            private array $config = [];

            public function __construct(string $id, string $name)
            {
                $this->id = $id;
                $this->name = $name;
            }

            public function execute(array $input): array
            {
                return ['executed' => true, 'input' => $input];
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function toArray(): array
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'type' => 'test_node'
                ];
            }
        };

        $this->assertSame('test-node', $node->getId());
        $this->assertSame('Test Node', $node->getName());

        $result = $node->execute(['test' => 'data']);
        $this->assertSame(['executed' => true, 'input' => ['test' => 'data']], $result);
    }
}
