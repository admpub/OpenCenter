<?php
namespace Common\Service;
/**
 * API Client基类
 * @author swh <swh@admpub.com>
 */
class Service {
	protected $_url = 'http://public.coscms.com/zy/'; //正式环境
	protected $_data = array(
		'appId' => '',
		'osType' => 'web',
	);
	protected $_picUrl = 'http://pic.coscms.com';
	public $path = '';
	public $debug = false;

	public function __construct() {
	}

	public function wPost($uri, $data = array(), $expectedType = 'json') {
		$resp = $this->http($uri, $data, 'post', $expectedType)->send();
		$this->check($resp);
		return $resp;
	}

	public function wGet($uri, $data = array(), $expectedType = 'json') {
		$resp = $this->http($uri, $data, 'get', $expectedType)->send();
		$this->check($resp);
		return $resp;
	}

	public function wJson($uri, $data = array(), $expectedType = 'json') {
		$resp = $this->http($uri, $data, 'post', $expectedType)->sendsJson()->send();
		$this->check($resp);
		return $resp;
	}

	public function http($uri, $data = array(), $httpMethod = 'get', $expectedType = 'json') {
		$uri = $this->url($uri);
		if ($this->debug) {
			echo $uri, '<br/>';
		}
		#$expectedType = 'html';
		$resp = self::rest($uri, $data ? array_merge($this->_data, is_array($data) ? $data : array($data)) : $this->_data, $httpMethod, null, $expectedType);
		//$resp->withoutAutoParsing();
		return $resp;
	}

	public function url($path) {
		return $this->_url . $this->path . $path;
	}

	public function check(&$obj) {
		#dump($obj);exit;
		if (!$this->debug) {
			return;
		}
		if (!is_object($obj)) {
			exit('解析JSON数据失败');
		}
		if (!$obj->body) {
			if (!empty($_REQUEST['debug'])) {
				echo 'error: <pre>' . var_export($obj, true) . '</pre>';
			} else {
				echo 'API接口返回内容为空';
			}
			exit;
		}
		/*
			if ($obj->body->status == 0 && empty($obj->body->error) == false) {
				echo 'error: ', $obj->body->error;
				exit;
			}
		*/
	}

	public function picUrl($value = '') {
		return $this->_picUrl . $value;
	}

	/**
	 * restful客户端
	 * @param  string $uri         网址
	 * @param  array  $data        post数据
	 * @param  string $method      http请求方式
	 * @param  string $mime        http提交内容类型
	 * @param  string $expectsType http请求内容内容
	 * @return object
	 * @example rest('http://www.coscms.com/', array('appId' => '1', 'osType' => 'web'), 'post', null, 'json')->send());
	 */
	static public function rest($uri, $data = null, $method = 'get', $mime = null, $expectsType = 'json') {
		if (class_exists('\Httpful\Bootstrap', false) == false) {
			require VENDOR_PATH . 'Httpful/Bootstrap.php';
			\Httpful\Bootstrap::init();
		}

		switch ($method) {
		case 'post':
			$request = \Httpful\Request::post($uri, $data, !$mime ? 'form' : $mime);
			break;
		case 'get':
		default:
			$request = \Httpful\Request::get($uri, $mime);
		}
		if ($expectsType) {
			$request = $request->expectsType($expectsType);
		}
		return $request;
	}

}