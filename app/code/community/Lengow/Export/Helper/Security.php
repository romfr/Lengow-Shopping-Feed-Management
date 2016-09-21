<?php
/**
 * Lengow export helper security
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Helper_Security extends Mage_Core_Helper_Abstract {

    /**
     * Lengow IP.
     */
    public static $IPS_LENGOW = array(
        '127.0.0.1' ,
        '95.131.137.18' ,
        '95.131.137.19' ,
        '95.131.137.21' ,
        '95.131.137.26' ,
        '95.131.137.27' ,
        '88.164.17.227' ,
        '88.164.17.216' ,
        '109.190.78.5' ,
        '80.11.36.123' ,
        '95.131.141.169' ,
        '95.131.141.170' ,
        '95.131.141.171' ,
        '82.127.207.67' ,
        '80.14.226.127' ,
        '80.236.15.223' ,
    );

    /**
     * Check if current IP is authorized.
     *
     * @return boolean.
     */
    public function checkIP() {
        $ips = Mage::getStoreConfig('lenexport/performances/valid_ip');
        $ips = trim(str_replace(array("\r\n", ',', '-', '|', ' '), ';', $ips), ';');
        $ips = explode(';', $ips);
        $authorized_ips = array_merge($ips, self::$IPS_LENGOW);
        $hostname_ip = $_SERVER['REMOTE_ADDR'];
        if(in_array($hostname_ip, $authorized_ips))
            return true;
        return false;
    }

    /**
     * Check if Authorized Ip is Valid.
     *
     * @return boolean.
     */
    public function checkValidAuthorizedIP($authorizedIp)
    {
        $cleanIps = preg_replace('/\ /','',$authorizedIp);
        if (strlen($cleanIps)==0){ return false; }
        $ips = explode(',',$cleanIps);
        foreach($ips as $ip){
            if (!preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/',$ip)){
                return false;
            }
        }
        return true;
    }


}