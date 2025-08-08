<?php

namespace Tests\Unit\Nodes\Utility;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Papi\Core\Nodes\Utility\Output;

class OutputTest extends TestCase
{
    #[Test]
    public function it_should_implement_node_interface()
    {
        $output = new Output('output1', 'Output Node');
        
        $this->assertInstanceOf(\Papi\Core\Nodes\Node::class, $output);
    }
    
    #[Test]
    public function it_should_format_as_json()
    {
        $output = new Output('output1', 'Output Node', ['format' => 'json']);
        $input = ['name' => 'test', 'value' => 123];
        
        $result = $output->execute($input);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('{"name":"test","value":123}', $result['data']);
        $this->assertArrayHasKey('duration', $result);
        $this->assertArrayHasKey('metadata', $result);
    }
    
    #[Test]
    public function it_should_format_as_json_with_pretty_print()
    {
        $output = new Output('output1', 'Output Node', [
            'format' => 'json',
            'pretty_print' => true
        ]);
        $input = ['name' => 'test', 'value' => 123];
        
        $result = $output->execute($input);
        
        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('"name": "test"', $result['data']);
        $this->assertStringContainsString('"value": 123', $result['data']);
    }
    
    #[Test]
    public function it_should_format_as_xml()
    {
        $output = new Output('output1', 'Output Node', ['format' => 'xml']);
        $input = ['name' => 'test', 'value' => 123];
        
        $result = $output->execute($input);
        
        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('<name>test</name>', $result['data']);
        $this->assertStringContainsString('<value>123</value>', $result['data']);
    }
    
    #[Test]
    public function it_should_format_as_text()
    {
        $output = new Output('output1', 'Output Node', ['format' => 'text']);
        $input = ['name' => 'test', 'value' => 123];
        
        $result = $output->execute($input);
        
        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('name: test', $result['data']);
        $this->assertStringContainsString('value: 123', $result['data']);
    }
    
    #[Test]
    public function it_should_format_as_array()
    {
        $output = new Output('output1', 'Output Node', ['format' => 'array']);
        $input = ['name' => 'test', 'value' => 123];
        
        $result = $output->execute($input);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals($input, $result['data']);
    }
    
    #[Test]
    public function it_should_format_as_csv()
    {
        $output = new Output('output1', 'Output Node', ['format' => 'csv']);
        $input = ['name' => 'test', 'value' => 123];
        
        $result = $output->execute($input);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals("name,value\ntest,123", $result['data']);
    }
    
    #[Test]
    public function it_should_handle_csv_with_arrays()
    {
        $output = new Output('output1', 'Output Node', ['format' => 'csv']);
        $input = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ];
        
        $result = $output->execute($input);
        
        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('name,age', $result['data']);
        $this->assertStringContainsString('John,30', $result['data']);
        $this->assertStringContainsString('Jane,25', $result['data']);
    }
    
    #[Test]
    public function it_should_exclude_metadata_when_configured()
    {
        $output = new Output('output1', 'Output Node', [
            'format' => 'json',
            'include_metadata' => false
        ]);
        $input = ['name' => 'test'];
        
        $result = $output->execute($input);
        
        $this->assertEquals('success', $result['status']);
        $this->assertArrayNotHasKey('metadata', $result);
    }
    
    #[Test]
    public function it_should_handle_errors_gracefully()
    {
        $output = new Output('output1', 'Output Node', ['format' => 'invalid']);
        $input = ['name' => 'test'];
        
        $result = $output->execute($input);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals($input, $result['data']); // Should return input as-is for invalid format
    }
    
    #[Test]
    public function it_should_return_to_array_representation()
    {
        $output = new Output('output1', 'Output Node', [
            'format' => 'json',
            'pretty_print' => true
        ]);
        
        $array = $output->toArray();
        
        $this->assertEquals('output1', $array['id']);
        $this->assertEquals('Output Node', $array['name']);
        $this->assertEquals('output', $array['type']);
        $this->assertEquals('json', $array['format']);
        $this->assertTrue($array['pretty_print']);
        $this->assertTrue($array['include_metadata']);
    }
} 
