<?php
/* Copyright (C) 2016-2018 Kamshory  <kamshory@yahoo.com>
 * Copyright (C) 2019 pmpdelcroix  <pmpdelcroix@gmail.com>
 * This program is free software;you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation;either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
error_reporting(0);
//commands
define('CMD_CONNECT', 1000);
define('CMD_EXIT', 1001);
define('CMD_ENABLEDEVICE', 1002);
define('CMD_DISABLEDEVICE', 1003);
define('CMD_RESTART', 1004);
define('CMD_POWEROFF', 1005);
define('CMD_SLEEP', 1006);
define('CMD_RESUME', 1007);
define('CMD_TEST_TEMP', 1011);
define('CMD_TESTVOICE', 1017);
define('CMD_VERSION', 1100);
define('CMD_CHANGE_SPEED', 1101);
define('CMD_ACK_OK', 2000);
define('CMD_ACK_ERROR', 2001);
define('CMD_ACK_DATA', 2002);
define('CMD_PREPARE_DATA', 1500);
define('CMD_DATA', 1501);
define('CMD_USER_WRQ', 8);
define('CMD_USERTEMP_RRQ', 9);
define('CMD_USERTEMP_WRQ', 10);
define('CMD_OPTIONS_RRQ', 11);
define('CMD_OPTIONS_WRQ', 12);
define('CMD_ATTLOG_RRQ', 13);
define('CMD_CLEAR_DATA', 14);
define('CMD_CLEAR_ATTLOG', 15);
define('CMD_DELETE_USER', 18);
define('CMD_DELETE_USERTEMP', 19);
define('CMD_CLEAR_ADMIN', 20);
define('CMD_ENABLE_CLOCK', 57);
define('CMD_STARTVERIFY', 60);
define('CMD_STARTENROLL', 61);
define('CMD_CANCELCAPTURE', 62);
define('CMD_STATE_RRQ', 64);
define('CMD_WRITE_LCD', 66);
define('CMD_CLEAR_LCD', 67);
define('CMD_GET_TIME', 201);
define('CMD_SET_TIME', 202);
define('USHRT_MAX', 65535);
//roles
define('LEVEL_USER', 0);// 0000 0000
define('LEVEL_ENROLLER', 2);// 0000 0010
define('LEVEL_MANAGER', 12);// 0000 1100
define('LEVEL_SUPERMANAGER', 14);// 0000 1110
/**
 *  Class to communicate with ZK TECO attendance machine
 */
class ZKLibrary
{
    public $ip = null;
    public $port = null;
    public $socket = null;
    public $session_id = 0;
    public $received_data = '';
    public $user_data = array();
    public $attendance_data = array();
    public $timeout_sec = null;
    public $timeout_usec = 2000000;
    /** Object constructor.
     *
     * @param string $ip IP address of device.
     * @param int $port  UDP port of device.
     */
    public function __construct($ip = null, $port = null)
    {
        if($ip != null) {
            $this->ip = $ip;
        }
        if($port != null) {
            $this->port = $port;
        }
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $this->setTimeout($this->sec, $this->usec);
    }
    /** destructor
     * @return null
     */
    public function __destruct()
    {
    unset($this->received_data);
    unset($this->user_data);
    unset($this->attendance_data);
    }
    /** Function to make a connection to the device. If IP address and port is not defined yet, this function must take it. Else, this function return FALSE and does not make any connection.
     *
     * @param sting $ip IP address of the device.
     * @param int $port UDP port of the device.
     * @return bool
     */
    public function connect($ip = null, $port = 4370)
    {
        if($ip != null) {
            $this->ip = $ip;
        }
        if($port != null) {
            $this->port = $port;
        }
        if($this->ip == null || $this->port == null) {
            return false;
        }
        $command = CMD_CONNECT;
        $command_string = '';
        $chksum = 0;
        $session_id = 0;
        $reply_id = -1 + USHRT_MAX;
        $buf = $this->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        try
        {
            socket_recvfrom($this->socket, $this->received_data, 1024, 0, $this->ip, $this->port);
            if(strlen($this->received_data)>0) {
                $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr($this->received_data, 0, 8));
                $this->session_id = hexdec($u['h6'].$u['h5']);
                return $this->checkValid($this->received_data);
            } else {
                return false;
            }
        }
        catch(ErrorException $e)
        {
            return false;
        }
        catch(exception $e)
        {
            return false;
        }
    }
    /** Function to disconnect from the device. If ip address and port is not defined yet, this function must take it. Else, this function return FALSE and does not make any changes.
     *
     * @return bool
     */
    public function disconnect()
    {
        if($this->ip == null || $this->port == null) {
            return false;
        }
        $command = CMD_EXIT;
        $command_string = '';
        $chksum = 0;
        $session_id = $this->session_id;
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $reply_id = hexdec($u['h8'].$u['h7']);
        $buf = $this->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        try
        {
            socket_recvfrom($this->socket, $this->received_data, 1024, 0, $this->ip, $this->port);
            return $this->checkValid($this->received_data);
        }
        catch(ErrorException $e)
        {
            return false;
        }
        catch(Exception $e)
        {
            return false;
        }
    }
    /** Set timeout for socket connection.
     *
     * @param int $sec Timeout in second.
     * @param int $usec Timeout in micro second.
     * @return null
     */
    public function setTimeout($sec = 0, $usec = 0)
    {
        if($sec != 0) {
            $this->timeout_sec = $sec;
        }
        if($usec != 0) {
            $this->timeout_usec = $usec;
        }
        $timeout = array('sec'=>$this->timeout_sec, 'usec'=>$this->timeout_usec);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $timeout);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, $timeout);
    }
    /** ping the attendance machine
     *
     * @param int $timeout does have a timeout
     * @return int ping time
     */
    public function ping($timeout = 1)
    {
        $time1 = microtime(true);
        $pfile = fsockopen($this->ip, $this->port, $errno, $errstr, $timeout);
        if(!$pfile) {
            return false;
        }
        $time2 = microtime(true);
        fclose($pfile);
        return round((($time2 - $time1) * 1000), 0);
    }
    /**  reverse the char of an hexadecimal(IP stack function)
     *
     * @param string $input HEX to reverse
     * @return string
     */
    private function reverseHex($input)
    {
        $output = '';
        for($i = strlen($input);$i >= 0;$i--)
        {
            $output .= substr($input, $i, 2);
            $i--;
        }
        return $output;
    }
    /** encode the time in ZKTECO binary format(IP stack function)
     *
     * @param string $time  YYYY-MM-DD HH:II:SS or UNIX
     * @return int
     */
    private function encodeTime($time)
    {
        if(is_numeric($time)){
            $year = date('y', $time);
            $month = date('n', $time);
            $day = date('j', $time);
            $hour = date('H', $time);
            $minute = date('i', $time);
            $second = date('s', $time);
        } else {
            $str = str_replace(array(":", " "), array("-", "-"), $time);
            $arr = explode("-", $str);
            $year = @$arr[0]*1;
            $month = ltrim(@$arr[1], '0')*1;
            $day = ltrim(@$arr[2], '0')*1;
            $hour = ltrim(@$arr[3], '0')*1;
            $minute = ltrim(@$arr[4], '0')*1;
            $second = ltrim(@$arr[5], '0')*1;
        }
        $data = (($year % 100) * 12 * 31 +(($month - 1) * 31) + $day - 1) * (24 * 60 * 60) +($hour * 60 + $minute) * 60 + $second;
        return $data;
    }
    /** decode the time in ZKTECO format(IP stack function)
     *
     * @param int $data     Binary data from device.
     * @param bool $unix unix format
     * @return time|string      date
     */
    private function decodeTime($data, $unix = true)
    {
        $second = $data % 60;
        $data = $data / 60;
        $minute = $data % 60;
        $data = $data / 60;
        $hour = $data % 24;
        $data = $data / 24;
        $day = $data % 31+1;
        $data = $data / 31;
        $month = $data % 12+1;
        $data = $data / 12;
        $year = floor($data + 2000);
        if($unix === true){
        $d = mktime($hour, $minute, $second, $month, $day, $year);
        } else{
        $d = date("Y-m-d H:i:s", strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second));
        }
        return $d;
    }
    /** This function calculates the chksum of the packet to be sent to the time clock.(IP stack function)
     *
     * @param string $p     Packet to be checked.
     * @return int checksum
     */
    private function checkSum($p)
    {
        /* This function calculates the chksum of the packet to be sent to the time clock */
        $l = count($p);
        $chksum = 0;
        $i = $l;
        $j = 1;
        while($i > 1)
        {
            $u = unpack('S', pack('C2', $p['c'.$j], $p['c'.($j+1)]));
            $chksum += $u[1];
            if($chksum > USHRT_MAX) {
                $chksum -= USHRT_MAX;
            }
            $i -= 2;
            $j += 2;
        }
        if($i) {
            $chksum = $chksum + $p['c'.strval(count($p))];
        }
        while($chksum > USHRT_MAX)
        {
            $chksum -= USHRT_MAX;
        }
        if($chksum > 0) {
            $chksum = -($chksum);
        } else {
            $chksum = abs($chksum);
        }
        $chksum -= 1;
        while($chksum < 0)
        {
            $chksum += USHRT_MAX;
        }
        return pack('S', $chksum);
    }
    /** Create data header to be sent to the device.(IP stack function)
     *
     * @param int $command  Command to the device in integer.
     * @param int $chksum   checksum of the packet
     * @param int $session_id    Session ID of the connection.
     * @param int $reply_id FIXME
     * @param string $command_string    Data to be sent to the device.
     * @return string   header
     */
    public function createHeader($command, $chksum, $session_id, $reply_id, $command_string)
    {
        $buf = pack('SSSS', $command, $chksum, $session_id, $reply_id).$command_string;
        $buf = unpack('C'.(8+strlen($command_string)).'c', $buf);
        $u = unpack('S', $this->checkSum($buf));
        if(is_array($u)) {
            while(list($key) = each($u))
            {
                $u = $u[$key];
                break;
            }
        }
        $chksum = $u;
        $reply_id += 1;
        if($reply_id >= USHRT_MAX) {
            $reply_id -= USHRT_MAX;
        }
        $buf = pack('SSSS', $command, $chksum, $session_id, $reply_id);
        return $buf.$command_string;
    }
    /** Check wether reply is valid or not.
     *
     * @param sting $reply Reply data to be checked.
     * @return bool
     */
    private function checkValid($reply)
    {
        $u = unpack('H2h1/H2h2', substr($reply, 0, 8));
        $command = hexdec($u['h2'].$u['h1']);
        if($command == CMD_ACK_OK) {
            return true;
        } else {
            return false;
        }
    }
    /** Send command and data packet to the device and receive some data if any.
     *
     * @param int $command  Command to the device in integer.
     * @param string $command_string    Data to be sent to the device.
     * @param int $offset_data  Offset data to be returned. The default offset is 8.
     * @return false|string
     */
    public function execCommand($command, $command_string = '', $offset_data = 8)
    {
        $chksum = 0;
        $session_id = $this->session_id;
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $reply_id = hexdec($u['h8'].$u['h7']);
        $buf = $this->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        socket_sendto($this->socket, $buf, strlen($buf), MSG_EOR, $this->ip, $this->port);
        try
        {
            socket_recvfrom($this->socket, $this->received_data, 1024, 0, $this->ip, $this->port);
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr($this->received_data, 0, 8));
            $this->session_id = hexdec($u['h6'].$u['h5']);
            return substr($this->received_data, $offset_data);
        }
        catch(ErrorException $e)
        {
            return false;
        }
        catch(exception $e)
        {
            return false;
        }
    }
    /**  Get number of user.
     *
     * @return int|false Number of registered user in the device.
     */
    private function getSizeUser()
    {
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $command = hexdec($u['h2'].$u['h1']);
        if($command == CMD_PREPARE_DATA) {
            $u = unpack('H2h1/H2h2/H2h3/H2h4', substr($this->received_data, 8, 4));
            $size = hexdec($u['h4'].$u['h3'].$u['h2'].$u['h1']);
            return $size;
        } else {
            return false;
        }
    }
     /**  Get number of attendance log.
     *
     * @return int|false    Number of attendance recorded in the device.
     */
    private function getSizeAttendance()
    {
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $command = hexdec($u['h2'].$u['h1']);
        if($command == CMD_PREPARE_DATA) {
            $u = unpack('H2h1/H2h2/H2h3/H2h4', substr($this->received_data, 8, 4));
            $size = hexdec($u['h4'].$u['h3'].$u['h2'].$u['h1']);
            return $size;
        } else {
            return false;
        }
    }
    /**  get the  number  of template(TBC)
     *
     * @return int|false
     */
    private function getSizeTemplate()
    {
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $command = hexdec($u['h2'].$u['h1']);
        if($command == CMD_PREPARE_DATA) {
            $u = unpack('H2h1/H2h2/H2h3/H2h4', substr($this->received_data, 8, 4));
            $size = hexdec($u['h4'].$u['h3'].$u['h2'].$u['h1']);
            return $size;
        } else {
            return false;
        }
    }
    /** restart device
     *
     * @return false|string exec output
     */
    public function restartDevice()
    {
        $command = CMD_RESTART;
        $command_string = chr(0).chr(0);
        return $this->execCommand($command, $command_string);
    }
    /** Shutdown the device.
     *
     * @return false|string exec output
     */
    public function shutdownDevice()
    {
        $command = CMD_POWEROFF;
        $command_string = chr(0).chr(0);
        return $this->execCommand($command, $command_string);
    }
    /** Sleep the device.
     *
     * @return false|string exec output
     */
    public function sleepDevice()
    {
        $command = CMD_SLEEP;
        $command_string = chr(0).chr(0);
        return $this->execCommand($command, $command_string);
    }
    /** Resume/wake the device.
     *
     * @return false|string exec output
     */
    public function resumeDevice()
    {
        $command = CMD_RESUME;
        $command_string = chr(0).chr(0);
        return $this->execCommand($command, $command_string);
    }
    /** Change transfer speed of the device. 0 = slower. 1 = faster.
     *
     * @param int $speed Transfer speed of packet when the device comunicate to other device, i.e server.(not always supported)
     * @return false|string exec output
     */
    public function changeSpeed($speed = 0)
    {
        if($speed != 0) {
            $speed = 1;
        }
        $command = CMD_CHANGE_SPEED;
        $byte = chr($speed);
        $command_string = $byte;
        return $this->execCommand($command, $command_string);
    }
    /** Write text on LCD. This order transmit character to demonstrate on LCD,
     * the data part 1, 2 bytes of the packet transmit the rank value
     * which start to demonstrate, the 3rd byte setting is 0 ,
     * follows close the filling character which want to be transmit.
     * May work in CMD_CLEAR_LCD when use this function.
     *
     * @param int $rank Line number.
     * @param string $text Text to be demonstrated to LCD of the device.
     * @return false|string exec output
     */
    public function writeLCD($rank, $text)
    {
        $command = CMD_WRITE_LCD;
        $byte1 = chr((int) ($rank % 256));
        $byte2 = chr((int) ($rank >> 8));
        $byte3 = chr(0);
        $command_string = $byte1.$byte2.$byte3.' '.$text;
        return $this->execCommand($command, $command_string);
    }
    /** Clear text from LCD.
     *
     * @return false|string exec output
     */
    public function clearLCD()
    {
        $command = CMD_CLEAR_LCD;
        return $this->execCommand($command);
    }
    /** Test voice of the device.
     *
     * @return false|string exec output
     */
    public function testVoice()
    {
        $command = CMD_TESTVOICE;
        $command_string = chr(0).chr(0);
        return $this->execCommand($command, $command_string);
    }
    /** Get device version.
     *
     * @return false|string exec output
     */
    public function getVersion()
    {
        $command = CMD_VERSION;
        return $this->execCommand($command);
    }
    /** Get OS version.
     *
     * @param bool $net If net set to true, function will return netto data without parameter name.
     * @return false|string exec output
     */
    public function getOSVersion($net = true)
    {
        $command = CMD_OPTIONS_RRQ;
        $command_string = '~OS';
        $return = $this->execCommand($command, $command_string);
        if($net) {
            $arr = explode(" = ", $return, 2);
            return $arr[1];
        } else {
            return $return;
        }
    }
    /** Set OS version
     *
     * @param string $osVersion Version of operating version.
     * @return false|string exec output
     */
    public function setOSVersion($osVersion)
    {
        $command = CMD_OPTIONS_WRQ;
        $command_string = '~OS='.$osVersion;
        return $this->execCommand($command, $command_string);
    }
    /**Get Platform version.
     *
     * @param bool $net If net set to true, function will return netto data without parameter name.
     * @return false|string|string[] exec output
     */
    public function getPlatform($net = true)
    {
        $command = CMD_OPTIONS_RRQ;
        $command_string = '~Platform';
        $return = $this->execCommand($command, $command_string);
        if($net) {
            $arr = explode(" = ", $return, 2);
            return $arr[1];
        } else {
            return $return;
        }
    }
    /** Set platform.
     *
     * @param string  $patform pltaform version
     * @return false|string exec output
     */
    public function setPlatform($patform)
    {
        $command = CMD_OPTIONS_RRQ;
        $command_string = '~Platform='.$patform;
        return $this->execCommand($command, $command_string);
    }
    /**
     *  Get firmware version.
     * @param bool $net If net set to true, function will return netto data without parameter name.
     * @return false|string|string[] exec output
     */
    public function getFirmwareVersion($net = true)
    {
        $command = CMD_OPTIONS_RRQ;
        $command_string = '~ZKFPVersion';
        $return = $this->execCommand($command, $command_string);
        if($net) {
            $arr = explode(" = ", $return, 2);
            return $arr[1];
        } else {
            return $return;
        }
    }
    /** Set firmware version.
     *
     * @param string $firmwareVersion firware version to send
     * @return false|string exec output
     */
    public function setFirmwareVersion($firmwareVersion)
    {
        $command = CMD_OPTIONS_WRQ;
        $command_string = '~ZKFPVersion='.$firmwareVersion;
        return $this->execCommand($command, $command_string);
    }
    /** Get work code.
     *
     * @param bool $net If net set to true, function will return netto data without parameter name.
     * @return false|string|string[] exec output
     */
    public function getWorkCode($net = true)
    {
        $command = CMD_OPTIONS_RRQ;
        $command_string = 'WorkCode';
        $return = $this->execCommand($command, $command_string);
        if($net) {
            $arr = explode(" = ", $return, 2);
            return $arr[1];
        } else {
            return $return;
        }
    }
    /** Set work code.
     *
     * @param string $workCode workcode to send
     * @return false|string exec output
     */
    public function setWorkCode($workCode)
    {
        $command = CMD_OPTIONS_WRQ;
        $command_string = 'WorkCode='.$workCode;
        return $this->execCommand($command, $command_string);
    }
    /** Get SSR
     *
     * @param bool $net If net set to true, function will return netto data without parameter name.
     * @return false|string|string[] exec output
     */
    public function getSSR($net = true)
    {
        $command = CMD_OPTIONS_PRQ;
        $command_string = '~SSR';
        $return = $this->execCommand($command, $command_string);
        if($net) {
            $arr = explode(" = ", $return, 2);
            return $arr[1];
        } else {
            return $return;
        }
    }
    /** Set Self-Service-Recoreder name(TBC)
     *
     * @param string  $ssr  (Self-Service-Recoreder)
     * @return false|string|string[] exec output
     */
    public function setSSR($ssr)
    {
        $command = CMD_OPTIONS_WRQ;
        $command_string = '~SSR='.$ssr;
        return $this->execCommand($command, $command_string);
    }
    /** Get pin width.
     *
     * @return false|string exec output
     */
    public function getPinWidth()
    {
        $command = CMD_GET_PINWIDTH;
        $command = CMD_OPTIONS_PRQ;
        $command_string = '~PIN2Width';
        $return = $this->execCommand($command, $command_string);
        if($net) {
            $arr = explode(" = ", $return, 2);
            return $arr[1];
        } else {
            return $return;
        }
    }
    /** Set pin width.
     *
     * @param int $pinWidth PIN  code lenght
     * @return false|string exec output
     */
    public function setPinWidth($pinWidth)
    {
        $command = CMD_OPTIONS_WRQ;
        $command_string = '~PIN2Width='.$pinWidth;
        return $this->execCommand($command, $command_string);
    }
    /**  Check wether face detection function is available or not.
     *
     * @param bool $net If net set to true, function will return netto data without parameter name.
     * @return false|string|string[] exec output
     */
    public function getFaceFunctionOn($net = true)
    {
        $command = CMD_OPTIONS_RRQ;
        $command_string = 'FaceFunOn';
        $return = $this->execCommand($command, $command_string);
        if($net) {
            $arr = explode(" = ", $return, 2);
            return $arr[1];
        } else {
            return $return;
        }
    }
    /** Set wether face detection function is available or not.
     *
     * @param string $faceFunctionOn param to activate the face function
     * @return false|string exec output
     */
    public function setFaceFunctionOn($faceFunctionOn)
    {
        $command = CMD_OPTIONS_WRQ;
        $command_string = 'FaceFunOn='.$faceFunctionOn;
        return $this->execCommand($command, $command_string);
    }
    /** get serial number of the device.
     *
     * @param bool $net If net set to true, function will return netto data without parameter name.
     * @return false|string|string[] exec output
     */
    public function getSerialNumber($net = true)
    {
        $command = CMD_OPTIONS_RRQ;
        $command_string = '~SerialNumber';
        $return = $this->execCommand($command, $command_string);
        if($net) {
            $arr = explode(" = ", $return, 2);
            return $arr[1];
        } else {
            return $return;
        }
    }
    /** Set serial number of the device.
     *
     * @param string $serialNumber serial number to send
     * @return false|string exec output
     */
    public function setSerialNumber($serialNumber)
    {
        $command = CMD_OPTIONS_WRQ;
        $command_string = '~SerialNumber='.$serialNumber;
        return $this->execCommand($command, $command_string);
    }
    /** Get device name.
     *
     * @param bool $net If net set to true, function will return netto data without parameter name.
     * @return false|string|string[] exec output
     */
    public function getDeviceName($net = true)
    {
        $command = CMD_OPTIONS_RRQ;
        $command_string = '~DeviceName';
        $return = $this->execCommand($command, $command_string);
        if($net) {
            $arr = explode(" = ", $return, 2);
            return $arr[1];
        } else {
            return $return;
        }
    }
    /** Set device name.
     *
     * @param string $deviceName device name to send
     * @return false|string exec output
     */
    public function setDeviceName($deviceName)
    {
        $command = CMD_OPTIONS_WRQ;
        $command_string = '~DeviceName='.$deviceName;
        return $this->execCommand($command, $command_string);
    }
    /** Get time of device from real time clock(RTC). The time resolution is one minute.
     *
     * @param bool $unix     true retun unix time, false     YYYY-MM-DD HH:II:SS.
     * @return dateime|string  RTC of the device
     */
    public function getTime($unix = true)
    {
        // resolution = 1 minute
        $command = CMD_GET_TIME;
        return $this->decodeTime(hexdec($this->reverseHex(bin2hex($this->execCommand($command)))), $unix);
    }
    /** Set time of the device.
     *
     * @param dateime|string $t   unix time,    YYYY-MM-DD HH:II:SS.
     * @return false|string exec output
     */
    public function setTime($t)
    {
        // resolution = 1 second
        $command = CMD_SET_TIME;
        $command_string = pack('I', $this->encodeTime($t));
        return $this->execCommand($command, $command_string);
    }
    /** Ensure the machine to be at in the normal work condition,
     * generally when data communication shields the machine auxiliary equipment(keyboard, LCD, sensor),
     * this order restores the auxiliary equipment to be at the normal work condition.
     *
     * @return false|string exec output
     */
    public function enableDevice()
    {
        $command = CMD_ENABLEDEVICE;
        return $this->execCommand($command);
    }
    /** Shield machine periphery keyboard, LCD, sensor, if perform successfully,
     * there are showing “working” on LCD.
     *
     * @return false|string exec output
     */
    public function disableDevice()
    {
        $command = CMD_DISABLEDEVICE;
        $command_string = chr(0).chr(0);
        return $this->execCommand($command, $command_string);
    }
    /**Set the LCD dot(to glitter ‘:’) the packet data part transmit 0 to stop glittering,
     * 1 start to glitter.
     * After this order carries out successfully, the firmware will refresh LCD.
     *
     * @param int $mode clock mode
     * @return false|string exec output
     */
    public function enableClock($mode = 0)
    {
        $command = CMD_ENABLE_CLOCK;
        $command_string = chr($mode);
        return $this->execCommand($command, $command_string);
    }
    /** //FIXME
     *
     * @param int $uid Serial number of the user(2 bytes)
     * @param int $finger finger to select
     * @return false|string exec output
     */
    public function getSelectedUser($uid, $finger)
    {
        $command = CMD_USERTEMP_RRQ;
        $byte1 = chr((int) ($uid % 256));
        $byte2 = chr((int) ($uid >> 8));
        $command_string = $byte1.$byte2.chr($finger);
        return $this->execCommand($command, $command_string);
    }
    /** Retrive the user list from the device.
     *
     * @return false|array[int uid,string name,int role, string passwd, rfid] exec output
     */
    public function getUser()
    {
        $command = CMD_USERTEMP_RRQ;
        $command_string = chr(5);
        $chksum = 0;
        $session_id = $this->session_id;
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $reply_id = hexdec($u['h8'].$u['h7']);
        $buf = $this->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        try
        {
            socket_recvfrom($this->socket, $this->received_data, 1024, 0, $this->ip, $this->port);
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr($this->received_data, 0, 8));
            $bytes = $this->getSizeUser();
            if($bytes) {
            while($bytes > 0)
            {
                socket_recvfrom($this->socket, $received_data, 1032, 0, $this->ip, $this->port);
                array_push($this->user_data, $received_data);
                $bytes -= 1024;
            }
            $this->session_id = hexdec($u['h6'].$u['h5']);
            socket_recvfrom($this->socket, $received_data, 1024, 0, $this->ip, $this->port);
            }
            $users = array();
            if(count($this->user_data) > 0) {
            $num = count($this->user_data);
            for($x = 0; $x < $num; $x++)
            {
                if($x > 0) {
                    $this->user_data[$x] = substr($this->user_data[$x], 8);
                }
            }
            $user_data = implode('', $this->user_data);
            $user_data = substr($user_data, 11);
            while(strlen($user_data) > 72)
            {
                $u = unpack('H144', substr($user_data, 0, 72));
                $u1 = hexdec(substr($u[1], 2, 2));
                $u2 = hexdec(substr($u[1], 4, 2));
                $uid = $u1+($u2*256);// 2 byte
                $role = hexdec(substr($u[1], 6, 2)).' ';// 1 byte
                $password = hex2bin(substr($u[1], 8, 16)).' ';// 8 byte
                $name = hex2bin(substr($u[1], 24, 48)). ' ';// 24 byte
                $tempcard = hexdec($this->reverseHex(substr($u[1], 72, 8)));	// 4 byte card
                //$tempcard = hexdec($this->reverseHex(substr($u[1], 72, 26)));	// 13 byte card
                $rfid = $tempcard == "0" ? "" : $tempcard;
                $active = hexdec(substr($u[1], 80, 2)). ' ';
                $userid = hex2bin(substr($u[1], 98, 72)).' ';// 36 byte
                $passwordArr = explode(chr(0), $password, 2);// explode to array
                $password = $passwordArr[0];// get password
                $useridArr = explode(chr(0), $userid, 2);// explode to array
                $userid = $useridArr[0];// get user ID
                $nameArr = explode(chr(0), $name, 3);// explode to array
                $name = $nameArr[0];// get name
                if($name == "") {
                    $name = $uid;
                }
                $users[$uid] = array('uid' => $userid, 'name' => $name, 'role' => intval($role), 'passwd' => $password, 'rfid' => $rfid, 'active' => $active);
                $user_data = substr($user_data, 72);
            }
            }
            return $users;
        }
        catch(ErrorException $e)
        {
            return false;
        }
        catch(exception $e)
        {
            return false;
        }
    }
    /** Get all finger print data from the device for one user.
     *
     * @param int $uid Serial number of the user(2 bytes)
     * @return array[U16 size, U16 PIn, char FingerID, int valid, char|array(template data)]
     */
    public function getUserTemplateAll($uid)
    {
        $template = array();
        $j = 0;
        for($i = 5;$i<10;$i++, $j++)
        {
            $template[$j] = $this->getUserTemplate($uid, $i);
            if($template[$j] == array() )unset($template[$j]);
        }
        for($i = 4;$i >= 0;$i--, $j++)
        {
            $template[$j] = $this->getUserTemplate($uid, $i);
            if($template[$j] == array() )unset($template[$j]);
        }
        return $template;
    }
    /** Get finger print data from the device.
     *
     * 
     * @param int $uid Serial number of the user(2 bytes)
     * @param int  $finger  finger(0-9)
     * @return bool|string  array[U16 size, U16 PIn, char FingerID, char valid, array(template data)]
     */
    public function getUserTemplate($uid, $finger)
    {
        $template_data = '';
        $this->user_data = array();
        $command = CMD_USERTEMP_RRQ;
        $byte1 = chr((int) ($uid % 256));
        $byte2 = chr((int) ($uid >> 8));
        $command_string = $byte1.$byte2.chr($finger);
        $chksum = 0;
        $session_id = $this->session_id;
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $reply_id = hexdec($u['h8'].$u['h7']);
        $buf = $this->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        try
        {
            socket_recvfrom($this->socket, $this->received_data, 1024, 0, $this->ip, $this->port);
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr($this->received_data, 0, 8));
            $bytes = $this->getSizeTemplate();
            if($bytes) {
                while($bytes > 0)
                {
                    socket_recvfrom($this->socket, $received_data, 1032, 0, $this->ip, $this->port);
                    array_push($this->user_data, $received_data);
                    $bytes -= 1024;
                }
                $this->session_id = hexdec($u['h6'].$u['h5']);
                socket_recvfrom($this->socket, $received_data, 1024, 0, $this->ip, $this->port);
            }
            $template_data = array();
            if(count($this->user_data) > 0) {
            $num = count($this->user_data);
            for($x = 0; $x < $num; $x++)
            {
                if($x == 0) {
                $this->user_data[$x] = substr($this->user_data[$x], 8);
                } else {
                $this->user_data[$x] = substr($this->user_data[$x], 8);
                }
            }
            $user_data = implode('', $this->user_data);
            $template_size = strlen($user_data)+6;
            $prefix = chr($template_size%256).chr(round($template_size/256)).$byte1.$byte2.chr($finger).chr(1);
            $user_data = $prefix.$user_data;
            if(strlen($user_data) > 6) {
                $valid = 1;
                $template_data = array('size' => $template_size, 'uid' => $uid, 'finger' => $finger, 'valid' =>$valid, 'userdata' => $user_data);
            }
            }
            return $template_data;
        }
        catch(ErrorException $e)
        {
            return false;
        }
        catch(exception $e)
        {
            return false;
        }
    }
    /** get the user data such ad fingers, faceid    *
     * @return bool|string user data
     */
    public function getUserData()
    {
        $uid = 1;
        $command = CMD_USERTEMP_RRQ;
        $command_string = chr(5);
        $chksum = 0;
        $session_id = $this->session_id;
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $reply_id = hexdec($u['h8'].$u['h7']);
        $buf = $this->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        try
        {
            socket_recvfrom($this->socket, $this->received_data, 1024, 0, $this->ip, $this->port);
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr($this->received_data, 0, 8));
            $bytes = $this->getSizeUser();
            if($bytes) {
                while($bytes > 0)
                {
                    socket_recvfrom($this->socket, $received_data, 1032, 0, $this->ip, $this->port);
                    array_push($this->user_data, $received_data);
                    $bytes -= 1024;
                }
                $this->session_id = hexdec($u['h6'].$u['h5']);
                socket_recvfrom($this->socket, $received_data, 1024, 0, $this->ip, $this->port);
            }
            $users = array();
            $retdata = "";
            if(count($this->user_data) > 0) {
            $num = count($this->user_data);
            for($x = 0; $x < $num; $x++)
            {
                if($x > 0) {
                $this->user_data[$x] = substr($this->user_data[$x], 8);
                }
                if($x > 0) {
                $retdata .= substr($this->user_data[$x], 0);
                } else {
                $retdata .= substr($this->user_data[$x], 12);
                }
            }
            }
            return $retdata;
        }
        catch(ErrorException $e)
        {
            return false;
        }
        catch(exception $e)
        {
            return false;
        }
    }
    /** Write user to the device.
     *
     * @param int $uid Serial number of the user(2 bytes)
     * @param int $userid user id
     * @param sting $name user name
     * @param sting $password user password
     * @param int $role 0 = LEVEL_USER, 2 = LEVEL_ENROLLER,12 = LEVEL_MANAGER,14 = LEVEL_SUPERMANAGER
     * @param int £rfid card number
     * @param int  $active 
     * @return false|string exec output
     */
    public function setUser($uid, $userid, $name, $password, $role, $rfid, $active)
    {
        $uid = (int) $uid;
        $role = (int) $role;
        if($uid > USHRT_MAX) {
            return false;
        }
        if($role > 255) $role = 255;
        $name = substr($name, 0, 28);
        $command = CMD_USER_WRQ;
        $byte1 = chr((int) ($uid % 256));
        $byte2 = chr((int) ($uid >> 8));
        $command_string = $byte1.
                            $byte2.chr($role).
                            str_pad($password, 8, chr(0)).
                            str_pad($name, 24, chr(0)).
                            str_pad($rfid, 4, chr(0)).
                            str_pad($active, 9, chr(0)).
                            str_pad($userid, 8, chr(0)).
                            str_repeat(chr(0), 16);
        return $this->execCommand($command, $command_string);
    }
    /**
     *
     * @param string $data data to send
     * @return false|string exec output
     */
    public function setUserTemplate($data)
    {
        $command = CMD_USERTEMP_WRQ;
        $command_string = $data;
        //$length = ord(substr($command_string, 0, 1)) + ord(substr($command_string, 1, 1))*256;
        return $this->execCommand($command, $command_string);
        /*
        $chksum = 0;
        $session_id = $this->session_id;
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $reply_id = hexdec($u['h8'].$u['h7']);
        $buf = $this->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        try
        {
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr($this->received_data, 0, 8));
            $this->session_id = hexdec($u['h6'].$u['h5']);
            return substr($this->received_data, 8);
        }
        catch(ErrorException $e)
        {
            return false;
        }
        catch(exception $e)
        {
            return false;
        }
        */
    }
    /**
     *
     * @return false|string exec output
     */
    public function clearData()
    {
        $command = CMD_CLEAR_DATA;
        return $this->execCommand($command);
    }
    /**
     *
     * @return false|string exec output
     */
    public function clearUser()
    {
        $command = CMD_CLEAR_DATA;
        return $this->execCommand($command);
    }
    /**
     *
     * @param int $uid Serial number of the user(2 bytes)
     * @return false|string exec output
     */
    public function deleteUser($uid)
    {
        $command = CMD_DELETE_USER;
        $byte1 = chr((int) ($uid % 256));
        $byte2 = chr((int) ($uid >> 8));
        $command_string = $byte1.$byte2;
        return $this->execCommand($command, $command_string);
    }
    /**
     *
     * @param int $uid Serial number of the user(2 bytes)
     * @param int $finger finger to delete
     * @return false|string exec output
     */
    public function deleteUserTemp($uid, $finger)
    {
        $command = CMD_DELETE_USERTEMP;
        $byte1 = chr((int) ($uid % 256));
        $byte2 = chr((int) ($uid >> 8));
        $command_string = $byte1.$byte2.chr($finger);
        return $this->execCommand($command, $command_string);
    }
    /**
     *
     * @return false|string exec output
     */
    public function clearAdmin()
    {
        $command = CMD_CLEAR_ADMIN;
        return $this->execCommand($command);
    }
    /**
     *
     * @param int $uid Serial number of the user(2 bytes)
     * @param int $finger   finger to test
     * @return false|string exec output
     */
    public function testUserTemplate($uid, $finger)
    {
        $command = CMD_TEST_TEMP;
        $byte1 = chr((int) ($uid % 256));
        $byte2 = chr((int) ($uid >> 8));
        $command_string = $byte1.$byte2.chr($finger);
        $u = unpack('H2h1/H2h2', $this->execCommand($command, $command_string));
        $ret = hexdec($u['h2'].$u['h1']);
        return($ret == CMD_ACK_OK)?1:0;
    }
    /** start verify //fixme
     *
     * @param int $uid Serial number of the user(2 bytes)
     * @return false|string exec output
     */
    public function startVerify($uid)
    {
        $command = CMD_STARTVERIFY;
        $byte1 = chr((int) ($uid % 256));
        $byte2 = chr((int) ($uid >> 8));
        $command_string = $byte1.$byte2;
        return $this->execCommand($command, $command_string);
    }
    /**  Start enrollement of finger for the user witn the UID
     *
     * @param int $uid user id
     * @param int $finger finger to enroll
     * @return false|string
     */
    public function startEnroll($uid, $finger)
    {
        $command = CMD_STARTENROLL;
        $byte1 = chr((int) ($uid % 256));
        $byte2 = chr((int) ($uid >> 8));
        $command_string = $byte1.$byte2.chr($finger);
        return $this->execCommand($command, $command_string);
    }
    /** cancel current capture
     *
     * @return false|string
     *
     */
    public function cancelCapture()
    {
        $command = CMD_CANCELCAPTURE;
        return $this->execCommand($command);
    }
    /** get the attendance
     *
     * @return false|array
     */
    public function getAttendance()
    {
        $command = CMD_ATTLOG_RRQ;
        $command_string = '';
        $chksum = 0;
        $session_id = $this->session_id;
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->received_data, 0, 8));
        $reply_id = hexdec($u['h8'].$u['h7']);
        $buf = $this->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        try
        {
            socket_recvfrom($this->socket, $this->received_data, 1024, 0, $this->ip, $this->port);
            $bytes = $this->getSizeAttendance();
            if($bytes) {
                while($bytes > 0)
                {
                    socket_recvfrom($this->socket, $received_data, 1032, 0, $this->ip, $this->port);
                    array_push($this->attendance_data, $received_data);
                    $bytes -= 1024;
                }
                $this->session_id = hexdec($u['h6'].$u['h5']);
                socket_recvfrom($this->socket, $received_data, 1024, 0, $this->ip, $this->port);
            }
            $attendance = array();
            if(count($this->attendance_data) > 0) {
                $num = count($this->attendance_data);
                for($x = 0; $x < $num; $x++)
                {
                    if($x > 0) {
                        $this->attendance_data[$x] = substr($this->attendance_data[$x], 8);
                    }
                }
                $attendance_data = implode('', $this->attendance_data);
                $attendance_data = substr($attendance_data, 10);
                while(strlen($attendance_data) > 40)
                {
                    $u = unpack('H78', substr($attendance_data, 0, 39));
                    $u1 = hexdec(substr($u[1], 4, 2));
                    $u2 = hexdec(substr($u[1], 6, 2));
                    $uid = $u1+($u2*256);
                    $id = str_replace("\0", '', hex2bin(substr($u[1], 8, 16)));
                    $state = hexdec(substr($u[1], 56, 2));
                    $timestamp = $this->decodeTime(hexdec($this->reverseHex(substr($u[1], 58, 8))));
                    array_push($attendance, array( 'uid' => $uid, 'id' => $id, 'state' => $state, 'tms' => $timestamp));
                    $attendance_data = substr($attendance_data, 40);
                }
            }
            return $attendance;
        }
        catch(exception $e)
        {
            return false;
        }
    }
    /** remove attendance
     *
     * @return false|string
     */
    public function clearAttendance()
    {
        $command = CMD_CLEAR_ATTLOG;
        return $this->execCommand($command);
    }
}
