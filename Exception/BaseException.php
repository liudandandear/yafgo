<?php

use Container\Utilities\Constant\DomainConstant;
use Container\Utilities\Constant\FileConstant;

/**
 * 基础异常类，所有业务的异常均需要继承于此.除此异常外的异常均为系统级的异常
 * Class Container_Exception_BaseException
 */
class Container_Exception_BaseException extends \Exception
{

    /**
     * @var string 用于错误描述的调试性信息
     */
    public $extraInformation;

    /**
     * exception constructor.
     * @param string|array $message 错误异常信息
     * @param int $code
     * @param $information
     */
    function __construct($message, $code, $information)
    {
        $this->message = $message;
        $this->code = $code;
        $this->extraInformation = $information;
        //记录错误异常日志
        $errLogPath = $GLOBALS['_G']['config']['log']["path"] . FileConstant::ERR_LOG_PATH;
        Container_Utilities_Common_Http::setHttpException($errLogPath, '', $code, $message, '');
        //发送错误异常告警
        $content = "# **ALD运营后台微服务异常告警⚠️**\n" .
            "> 异常code：<font color=\"info\">{$code}</font> \n" .
            "> 异常message：<font color=\"info\">{$message}</font> \n";
        Container_Utilities_Common_Notice::sendWxWebhook(DomainConstant::WECHAT_MONITOR, $content);
    }
}