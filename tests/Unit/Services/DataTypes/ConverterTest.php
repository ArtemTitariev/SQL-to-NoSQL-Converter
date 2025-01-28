<?php

namespace Tests\Unit\Services\DataTypes;

use PHPUnit\Framework\TestCase;
use App\Services\DataTypes\Converter;
use MongoDB\BSON\Int64;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Document;
use MongoDB\BSON\UTCDateTime;

class ConverterTest extends TestCase
{
    public function testConvertToBoolean(): void
    {
        $this->assertBooleanConversion(1, true);
        $this->assertBooleanConversion('true', true);
        $this->assertBooleanConversion('abc', true);
        $this->assertBooleanConversion(-1, true);

        $this->assertBooleanConversion(0, false);
        $this->assertBooleanConversion([], false);

        $value = null;
        $this->assertNull(Converter::convert($value, 'bool'));
    }

    public function testConvertToInteger(): void
    {
        $this->assertIntegerConversion(100, '100');
        $this->assertIntegerConversion(-10, '-10');

        $this->expectException(\InvalidArgumentException::class);
        $value = '1a';
        Converter::convert($value, 'int');

        $this->assertNull(Converter::convert(null, 'int'));
    }

    public function testConvertToDecimal128(): void
    {
        $value = '12.34';
        $result = Converter::convert($value, 'decimal128');
        $this->assertInstanceOf(Decimal128::class, $result);
        $this->assertEquals('12.34', (string) $result);
    }

    public function testConvertToUTCDateTime(): void
    {
        $this->assertDateConversion('2025-01-01', strtotime('2025-01-01') * 1000);
        $this->assertDateConversion('2025-01-01 12:30:00', strtotime('2025-01-01 12:30:00') * 1000);
        $this->assertDateConversion('12:00:00', strtotime('1970-01-01 12:00:00') * 1000);
    }

    public function testConvertToDocument(): void
    {
        $value = json_encode(['a' => 1]);
        $result = Converter::convert($value, 'object');
        $this->assertInstanceOf(Document::class, $result);
        
        $value = 'abc';
        $result = Converter::convert($value, 'object');
        $this->assertNull($result);
    }

    private function assertDateConversion(string $value, int $expectedMilliseconds): void
    {
        $result = Converter::convert($value, 'date');
        $this->assertInstanceOf(UTCDateTime::class, $result);
        $this->assertEquals($expectedMilliseconds, $result->toDateTime()->getTimestamp() * 1000);
    }

    private function assertBooleanConversion(mixed $value, bool $expectedResult): void
    {
        $result = Converter::convert($value, 'bool');
        $this->assertEquals($expectedResult, $result);
    }
    
    private function assertIntegerConversion(int $value, string $expectedResult): void
    {
        $result = Converter::convert($value, 'int');
        $this->assertInstanceOf(Int64::class, $result);
        $this->assertEquals($expectedResult, (string) $result);
    }
}
