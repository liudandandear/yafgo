<?php

/**
 * 所有controller的基类
 * 用于获取请求参数（ip，版本号，用户验证信息，浏览器信息......）
 * Class Container_Handler_BaseHandler
 */
abstract class Container_Handler_BaseHandler extends Yaf_Controller_Abstract
{
    use Container_Tool_HandlerHelp;

    /**
     * @var int 用户ID
     */
    protected $userId;

    /**
     * @var string 客户端IP
     */
    protected $_ip;

    /**
     * @var array 请求参数
     */
    public $_params;

    /**
     * @var string 接口请求方式
     */
    public $_method;

    /**
     * @var string 用户浏览器信息
     */
    protected $_user_agent;

    /**
     * @var string 接口版本号
     */
    protected $_version;

    /**
     * @var string 路由地址（API接口）
     */
    protected $_route;

    /**
     * @var string 用户token
     */
    protected $_token;

    /**
     * @var object 请求的浏览器信息
     */
    protected $_browseInfo;

    /**
     * @var object 请求体
     */
    protected $_http_request;

    /**
     * @var
     */
    protected $requestBody;

    /**
     * @var Container_Handler_HandlerConfig
     */
    protected $_config;

    protected abstract function checkAuth();

    /**
     * 默认初始化方法，如果不需要，可以删除掉这个方法
     * 如果这个方法被定义，那么在Controller被构造以后，Yaf会调用这个方法
     */
    public function initConstruct()
    {
        $this->_ip = $this->getIp();//获取客户端请求的IP
        $this->_method = $this->getMethod();//获取HTTP的请求方式
        $this->_route = $this->getRoute();//获取用户请求的路由地址
        $this->_params = $this->getParams();//获取请求的参数
        $this->_user_agent = $this->getUserAgent();//获取用户浏览器信息
        $this->_version = $this->getVersion();//获取接口版本号
        $this->_token = $this->getToken();//获取用户请求的token验证信息
        $this->token = $this->getToken();//获取用户请求的token验证信息
        $this->_browseInfo = $this->getBrowseInfo();//获取用户浏览器信息
        $this->_http_request = $this->getRequest();//Yaf框架自身属性，获取当前的请求实例
        $this->_config = new Container_Handler_HandlerConfig();
        $this->setApiConfig();
        $this->checkApiAuth();
        $this->work();
    }

    /**
     * 接口主要功能验证
     * @return string
     */
    public function work()
    {
        $jsonMap = new JsonMapper();
        $jsonMap->bEnforceMapType = false;
        $content = $this->_params;
        set_error_handler(array($this, 'setMyRecoverableError'));
        try {
            $this->requestBody = $jsonMap->map($content, $this->setRequestBody());
        } catch (InvalidArgumentException $e) {
            $this->_setApiError(Container_Error_ErrDesc_ErrorDto::PARAM_FORMAT_REQ_ERROR);
            return $this->getResult(Container_Error_ErrDesc_ErrorCode::API_ERROR);
        }
        //检查入参
        $checkResMsg = $this->requestBody->checkFieldValue();
        if ($checkResMsg != 'success') {
            $this->_setApiError($checkResMsg);
            return $this->getResult(Container_Error_ErrDesc_ErrorCode::API_ERROR);
        }

    }

    /**
     * 检查接口权限
     */
    public function checkApiAuth()
    {
        //检查接口是否需要token验证
        if ($this->_config->needToken) {
            $this->exclusion = ['index'];
        }
        //检查接口是否需要token验证
        if ($this->_config->needCollection) {
            $this->collectionAction = ['index'];
        }
        //TODO::是否需要对接口请求进行安全验证，暴力请求和恶意攻击等
        if ($this->_config->checkRequest) {

        }
        //TODO::是否需要对接口请求方式进行限制(需要严格规范请求方式)
        if ($this->_config->checkMethod) {

        }
    }

    /**
     * 获取客户端请求的IP
     * @return string
     */
    public function getIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * 获取客户端的连接web服务器的端口
     * @return string
     */
    public function getPort()
    {
        return $_SERVER['REMOTE_PORT'];
    }

    /**
     * 获取用户请求的路由地址
     * @return string
     */
    public function getRoute()
    {
        $url = $this->getRequestUrl();
        $arr = parse_url($url);
        return str_replace($_SERVER['HTTP_HOST'], '', $arr['path']);
    }

    /**
     * 获取完整的请求URL地址
     * @return string
     */
    public function getRequestUrl()
    {
        return $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * 获取请求的参数
     * @return array
     */
    public function getParams()
    {
        //根据不同的请求方式获取参数
        if ($_SERVER['REQUEST_METHOD'] == "GET") {//get请求
            $url = $this->getRequestUrl();
            $arr = parse_url($url);
            //①query传参
            if (!empty($arr['query'])) {
                $param = $this->convertUrlQuery($arr['query']);
            } //②其他
            else {
                $param = '';
            }
            $params = json_decode(json_encode($param, JSON_FORCE_OBJECT));
        } else if ($_SERVER['REQUEST_METHOD'] == "POST") {//post请求
            //①form-data传参
            if (strstr($this->getContentType(), 'multipart/form-data')) {
                $params = $_POST;
            } //②x-www-form-urlencoded传参
            elseif (strstr($this->getContentType(), 'application/x-www-form-urlencoded')) {
                $params = $_POST;
            } //③raw-json传参
            else {
                $params = json_decode($this->getRequest()->getRaw());
            }
        } else {
            $params = null;
        }
        return $params;
    }

    /**
     * 获取HTTP的请求方式
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * 获取用户浏览器信息
     * @return mixed
     */
    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * 获取浏览器数据提交的ContentType
     * @return mixed
     */
    public function getContentType()
    {
        return $_SERVER['CONTENT_TYPE'];
    }

    /**
     * TODO::获取接口版本号（后续的版本参数请求可以放在header请求头里边）
     * @return string
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * 获取用户请求的token
     * @return string
     */
    public
    function getToken()
    {
        return empty($_SERVER['HTTP_AUTHORIZATION']) ? null : $_SERVER['HTTP_AUTHORIZATION'];
    }

    /**
     * 获取用户浏览器信息
     * @return string
     */
    public
    function getBrowseInfo()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }
}