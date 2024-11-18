<?php

namespace App\Services\DataTypes;

use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Document;
use MongoDB\BSON\Int64;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

final class Converter
{
    public static function createObjectId(?string $id = null)
    {
        return new ObjectId($id);
    }

    public static function convert(mixed &$value, string $dataType): mixed
    {
        if (is_null($value)) return $value;

        return match ($dataType) {
            'bool' => static::toBoolean($value),

            'int' => static::toInt($value),
            'long' => static::toInt($value), // toLong

            'double' => static::toDouble($value),
            'decimal128' => static::toDecimal128($value),

            'string' => static::toString($value),

            'date' => static::toUTCDateTime($value),

            'object' => static::toDocumentFromJSON($value),

            default => static::toString($value),
        };
    }

    /**
     * @return bool
     */
    private static function toBoolean(&$value): bool
    {
        return (bool) $value;
    }

    /**
     * int32 (int) or int64 (long)
     * @return MongoDB\BSON\Int64
     */
    private static function toInt(&$value): Int64
    {
        // return (int) $value;
        return new Int64((string) $value);
    }

    /**
     * @return float
     */
    private static function toDouble(&$value): float
    {
        return (float) $value;
    }

    /**
     * @return MongoDB\BSON\Decimal128
     */
    private static function toDecimal128(&$value): Decimal128
    {
        return new Decimal128((string) $value);
    }

    /**
     * @return string
     */
    private static function toString(&$value): string
    {
        return (string) $value;
    }

    /**
     * @return MongoDB\BSON\UTCDateTime
     */
    private static function toUTCDateTime(string &$value): UTCDateTime
    {
        // Додаємо час '00:00:00', якщо передано тільки дату
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $value .= ' 00:00:00';
        }
        // Додаємо дату '1970-01-01', якщо передано тільки час
        elseif (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            $value = '1970-01-01 ' . $value;
        }

        // Конвертуємо у мілісекунди і створюємо об'єкт UTCDateTime
        return new UTCDateTime(strtotime($value) * 1000);
    }

    /**
     * casts JSON string to Document
     * @return MongoDB\BSON\Document | null
     */
    private static function toDocumentFromJSON(string &$value): Document | null
    {
        // return Document::fromJSON(json_decode($value));
        return  isJsonString($value) ?  Document::fromJSON($value) : null;
    }
}
