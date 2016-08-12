<?php

/**
 * Lengow sync helper data
 *
 * @category    Lengow
 * @package     Lengow_Cron
 * @author      Pierre Basile <pierre.basile@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Helper_Cron extends Mage_Core_Helper_Abstract
{

    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_MISSED = 'missed';
    const STATUS_ERROR = 'error';

    const STATUS_KILLED = 'killed';
    const STATUS_DISAPPEARED = 'gone';
    const STATUS_DIDNTDOANYTHING = 'nothing';
    const STATUS_SKIP_LOCKED = 'locked';
    const STATUS_SKIP_OTHERJOBRUNNING = 'other_job_running';
    const STATUS_DIED = 'died';

    /**
     * Decorate status values
     *
     * @param $status
     * @return string
     */
    public function decorateStatus($status)
    {
        switch ($status) {
            case self::STATUS_SUCCESS:
            case self::STATUS_DIDNTDOANYTHING:
                return '<span class="lengow-status green"><span>' . $status . '</span></span>';
                break;
            case self::STATUS_PENDING:
                return '<span class="lengow-status grey"><span>' . $status . '</span></span>';
                break;
            case self::STATUS_RUNNING:
                return '<span class="lengow-status blue"><span>' . $status . '</span></span>';
                break;
            case self::STATUS_SKIP_OTHERJOBRUNNING:
            case self::STATUS_SKIP_LOCKED:
            case self::STATUS_MISSED:
                return '<span class="lengow-status orange"><span>' . $status . '</span></span>';
                break;
            case self::STATUS_ERROR:
            case self::STATUS_DISAPPEARED:
            case self::STATUS_KILLED:
                return '<span class="lengow-status red"><span>' . $status . '</span></span>';
                break;
            default:
                return $status;
                break;
        }
    }
}