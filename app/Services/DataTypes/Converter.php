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

        // try {
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
        // } catch (\Exception $e) {
        //     //
        // }
    }

    /**
     * Boolean
     */
    private static function toBoolean(&$value): bool
    {
        return (bool) $value;
    }

    /**
     * Numbers
     */

    /**
     * int32 (int) or int64 (long)
     */
    private static function toInt(&$value): Int64
    {
        // return (int) $value;
        return new Int64((string) $value);
    }

    // /**
    //  * long
    //  */ 
    // private static function toLong(&$value): Long {
    //     return new Long((string) $value);
    // }

    /**
     * double
     */
    private static function toDouble(&$value)
    {
        return (float) $value;
    }

    /**
     * decimal
     * @return MongoDB\BSON\Decimal128
     */
    private static function toDecimal128(&$value): Decimal128
    {
        return new Decimal128((string) $value);
    }

    /**
     * String
     */
    private static function toString(&$value): string
    {
        return (string) $value;
    }

    /**
     * UTCDateTime
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
     * object 
     * casts to Document
     */
    private static function toDocumentFromJSON(string &$value): Document
    {
        return Document::fromJSON(json_decode($value));
    }
}
