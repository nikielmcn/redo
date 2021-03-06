<?php
namespace Repeka\Domain\Entity\MetadataDateControl;

use DateTime;

final class MetadataDateControlConverterUtil {

    private const FLEXIBLE_DATE_FORMAT = 'Y-m-d\TH:i:s';

    private function __construct() {
    }

    /**
     * @param string | int | null $from
     * @param string | int | null $to
     * @param string | MetadataDateControlMode $mode
     * @param string | MetadataDateControlMode $rangeMode
     * @return FlexibleDate
     */
    public static function convertDateToFlexibleDate($from, $to, $mode, $rangeMode): FlexibleDate {
        $from = !is_null($from) ? self::toFlexibleDateFormat($from) : $from;
        $to = !is_null($to) ? self::toFlexibleDateFormat($to) : $to;
        return ($mode == MetadataDateControlMode::RANGE)
            ? self::getFlexibleRangeDateMode($from, $to, $mode, $rangeMode)
            : self::getFlexibleDateMode($from, $mode);
    }

    /**
     * @param string | int | null $from
     * @param string | int | null $to
     * @param string | MetadataDateControlMode $mode
     * @param string | MetadataDateControlMode $rangeMode
     * @return FlexibleDate
     */
    private static function getFlexibleRangeDateMode($from, $to, $mode, $rangeMode) {
        if (!is_null($from)) {
            $from = !is_null($from) ? new DateTime($from) : null;
            $from = self::startOf($rangeMode, $from);
            $from = $from->format(self::FLEXIBLE_DATE_FORMAT);
        }
        if (!is_null($to)) {
            $to = !is_null($to) ? new DateTime($to) : null;
            $to = self::endOf($rangeMode, $to);
            $to = $to->format(self::FLEXIBLE_DATE_FORMAT);
        }
        return new FlexibleDate($from, $to, $mode, $rangeMode);
    }

    /**
     * @param string | int | null $from
     * @param string | MetadataDateControlMode $mode
     * @return FlexibleDate
     */
    private static function getFlexibleDateMode($from, $mode) {
        $to = new DateTime($from);
        $from = new DateTime($from);
        $from = self::startOf($mode, $from);
        $to = self::endOf($mode, $to);
        $from = $from->format(self::FLEXIBLE_DATE_FORMAT);
        $to = $to->format(self::FLEXIBLE_DATE_FORMAT);
        return new FlexibleDate($from, $to, $mode, null);
    }

    /**
     * @var integer | string $date
     * @return string
     */
    private static function toFlexibleDateFormat($date) {
        return is_integer($date)
            ? (new DateTime())->setTimestamp($date)->format(self::FLEXIBLE_DATE_FORMAT)
            : (new DateTime($date))->format(self::FLEXIBLE_DATE_FORMAT);
    }

    /**
     * @param string | MetadataDateControlMode $mode
     * @param string | DateTime $from
     * @return string | DateTime
     */
    private static function startOf($mode, $from) {
        switch ($mode) {
            case MetadataDateControlMode::YEAR:
                return $from->setDate($from->format('Y'), 1, 1)->setTime(0, 0, 0);
                break;
            case MetadataDateControlMode::MONTH:
                $from = date('Y-m-1', strtotime($from->format(DateTime::ATOM)));
                return new DateTime($from);
                break;
            case MetadataDateControlMode::DAY:
                return $from->setTime(0, 0, 0);
                break;
            case MetadataDateControlMode::DATE_TIME:
                return $from;
                break;
        }
    }

    /**
     * @param string | MetadataDateControlMode $mode
     * @param string | DateTime $to
     * @return string | DateTime
     */
    private static function endOf($mode, $to) {
        switch ($mode) {
            case MetadataDateControlMode::YEAR:
                return $to->setDate($to->format('Y'), 12, 31)->setTime(23, 59, 59);
                break;
            case MetadataDateControlMode::MONTH:
                $to = $to->format('Y-m-t');
                return (new DateTime($to))->setTime(23, 59, 59);
                break;
            case MetadataDateControlMode::DAY:
                return $to->setTime(23, 59, 59);
                break;
            case MetadataDateControlMode::DATE_TIME:
                return $to;
                break;
        }
    }

    public static function convertDateToAtomFormat($value): string {
        $dateTime = new DateTime($value);
        $dateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return $dateTime->format(DateTime::ATOM);
    }
}
