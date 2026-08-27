<?php

namespace Tests\Unit;

use App\Support\IpamAltFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IpamAltFormatTest extends TestCase
{
    #[DataProvider('validAltValues')]
    public function test_accepts_valid_alt_formats(string $value): void
    {
        $this->assertTrue(IpamAltFormat::isValid($value));
    }

    #[DataProvider('invalidAltValues')]
    public function test_rejects_invalid_alt_formats(string $value): void
    {
        $this->assertFalse(IpamAltFormat::isValid($value));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validAltValues(): array
    {
        return [
            'scientific comma' => ['5,50 x 10²'],
            'scientific dot' => ['5.50 x 10^2'],
            'plain number' => ['12,5'],
            'integer' => ['0'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidAltValues(): array
    {
        return [
            'empty' => [''],
            'text only' => ['tidak terdeteksi'],
            'missing exponent' => ['5,50 x'],
        ];
    }
}
