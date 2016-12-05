<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\DataTransformer;

use Rollerworks\Component\Search\DataTransformerInterface;
use Rollerworks\Component\Search\Exception\TransformationFailedException;

/**
 * Transforms between a number type and a number with grouping
 * (each thousand) and comma separators.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class NumberToStringTransformer implements DataTransformerInterface
{
    /**
     * Rounds a number towards positive infinity.
     *
     * Rounds 1.4 to 2 and -1.4 to -1.
     */
    const ROUND_CEILING = \NumberFormatter::ROUND_CEILING;

    /**
     * Rounds a number towards negative infinity.
     *
     * Rounds 1.4 to 1 and -1.4 to -2.
     */
    const ROUND_FLOOR = \NumberFormatter::ROUND_FLOOR;

    /**
     * Rounds a number away from zero.
     *
     * Rounds 1.4 to 2 and -1.4 to -2.
     */
    const ROUND_UP = \NumberFormatter::ROUND_UP;

    /**
     * Rounds a number towards zero.
     *
     * Rounds 1.4 to 1 and -1.4 to -1.
     */
    const ROUND_DOWN = \NumberFormatter::ROUND_DOWN;

    /**
     * Rounds to the nearest number and halves to the next even number.
     *
     * Rounds 2.5, 1.6 and 1.5 to 2 and 1.4 to 1.
     */
    const ROUND_HALF_EVEN = \NumberFormatter::ROUND_HALFEVEN;

    /**
     * Rounds to the nearest number and halves away from zero.
     *
     * Rounds 2.5 to 3, 1.6 and 1.5 to 2 and 1.4 to 1.
     */
    const ROUND_HALF_UP = \NumberFormatter::ROUND_HALFUP;

    /**
     * Rounds to the nearest number and halves towards zero.
     *
     * Rounds 2.5 and 1.6 to 2, 1.5 and 1.4 to 1.
     */
    const ROUND_HALF_DOWN = \NumberFormatter::ROUND_HALFDOWN;

    /**
     * @var int|null
     */
    protected $precision;

    /**
     * @var bool|null
     */
    protected $grouping;

    /**
     * @var int|null
     */
    protected $roundingMode;

    /**
     * @var int
     */
    protected $type;

    /**
     * @param int  $precision
     * @param bool $grouping
     * @param int  $roundingMode
     * @param int  $type
     */
    public function __construct($precision = null, $grouping = null, $roundingMode = null, $type = \NumberFormatter::TYPE_DOUBLE)
    {
        if (null === $grouping) {
            $grouping = false;
        }

        if (null === $roundingMode) {
            $roundingMode = self::ROUND_HALF_UP;
        }

        $this->precision = $precision;
        $this->grouping = $grouping;
        $this->roundingMode = $roundingMode;
        $this->type = $type;
    }

    /**
     * Transforms a number type into localized number.
     *
     * @param int|float $value Number value
     *
     * @throws TransformationFailedException If the given value is not numeric
     *                                       or if the value can not be transformed
     *
     * @return string Localized value
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric.');
        }

        $formatter = $this->getNumberFormatter();
        $value = $formatter->format($value);

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new TransformationFailedException($formatter->getErrorMessage());
        }

        // Convert fixed spaces to normal ones
        $value = str_replace("\xc2\xa0", ' ', $value);

        return $value;
    }

    /**
     * Transforms a localized number into an integer or float.
     *
     * @param string $value    The localized value
     * @param string $currency The parsed currency value
     *
     * @throws TransformationFailedException If the given value is not a string
     *                                       or if the value can not be transformed
     *
     * @return int|float The numeric value
     */
    public function reverseTransform($value, &$currency = null)
    {
        if (!is_scalar($value)) {
            throw new TransformationFailedException('Expected a scalar.');
        }

        if ('' === $value) {
            return;
        }

        if ('NaN' === $value) {
            throw new TransformationFailedException('"NaN" is not a valid number');
        }

        $position = 0;
        $formatter = $this->getNumberFormatter(false === $currency ? \NumberFormatter::DECIMAL : null);
        $groupSep = $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        $decSep = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        if ('.' !== $decSep && (!$this->grouping || '.' !== $groupSep)) {
            $value = str_replace('.', $decSep, $value);
        }

        if (',' !== $decSep && (!$this->grouping || ',' !== $groupSep)) {
            $value = str_replace(',', $decSep, $value);
        }

        if (false !== $currency && \NumberFormatter::TYPE_CURRENCY === $this->type) {
            $result = $formatter->parseCurrency($value, $currency, $position);
        } else {
            $result = $formatter->parse($value, \NumberFormatter::TYPE_DOUBLE, $position);
        }

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new TransformationFailedException($formatter->getErrorMessage());
        }

        if ($result >= PHP_INT_MAX || $result <= -PHP_INT_MAX) {
            throw new TransformationFailedException('I don\'t have a clear idea what infinity looks like');
        }

        if (function_exists('mb_detect_encoding') && false !== $encoding = mb_detect_encoding($value, mb_detect_order(), true)) {
            $strlen = function ($string) use ($encoding) {
                return mb_strlen($string, $encoding);
            };
            $substr = function ($string, $offset, $length) use ($encoding) {
                return mb_substr($string, $offset, $length, $encoding);
            };
        } else {
            $strlen = 'strlen';
            $substr = 'substr';
        }

        $length = $strlen($value);

        // After parsing, position holds the index of the character where the
        // parsing stopped
        if ($position < $length) {
            // Check if there are unrecognized characters at the end of the
            // number (excluding whitespace characters)
            $remainder = trim($substr($value, $position, $length), " \t\n\r\0\x0b\xc2\xa0");

            if ('' !== $remainder) {
                throw new TransformationFailedException(
                    sprintf('The number contains unrecognized characters: "%s"', $remainder)
                );
            }
        }

        // NumberFormatter::parse() does not round
        return $this->round($result);
    }

    /**
     * Returns a preconfigured \NumberFormatter instance.
     *
     * @param int $type
     *
     * @return \NumberFormatter
     */
    protected function getNumberFormatter($type = null)
    {
        if (null === $type) {
            $type = \NumberFormatter::TYPE_CURRENCY === $this->type ? \NumberFormatter::CURRENCY : \NumberFormatter::DECIMAL;
        }

        $formatter = new \NumberFormatter('en_us', $type);

        if (null !== $this->precision) {
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->precision);
            $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, $this->roundingMode);
        }

        $formatter->setAttribute(\NumberFormatter::GROUPING_USED, $this->grouping);

        return $formatter;
    }

    /**
     * Rounds a number according to the configured precision and rounding mode.
     *
     * @param int|float $number A number
     *
     * @return int|float The rounded number
     */
    private function round($number)
    {
        if (null !== $this->precision && null !== $this->roundingMode) {
            // shift number to maintain the correct precision during rounding
            $roundingCoef = pow(10, $this->precision);
            $number *= $roundingCoef;

            switch ($this->roundingMode) {
                case self::ROUND_CEILING:
                    $number = ceil($number);
                    break;
                case self::ROUND_FLOOR:
                    $number = floor($number);
                    break;
                case self::ROUND_UP:
                    $number = $number > 0 ? ceil($number) : floor($number);
                    break;
                case self::ROUND_DOWN:
                    $number = $number > 0 ? floor($number) : ceil($number);
                    break;
                case self::ROUND_HALF_EVEN:
                    $number = round($number, 0, PHP_ROUND_HALF_EVEN);
                    break;
                case self::ROUND_HALF_UP:
                    $number = round($number, 0, PHP_ROUND_HALF_UP);
                    break;
                case self::ROUND_HALF_DOWN:
                    $number = round($number, 0, PHP_ROUND_HALF_DOWN);
                    break;
            }

            /* @noinspection CallableParameterUseCaseInTypeContextInspection */
            $number /= $roundingCoef;
        }

        return $number;
    }
}
