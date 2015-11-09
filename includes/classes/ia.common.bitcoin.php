<?php

class iaBitcoin extends abstractPlugin
{
	const OAUTH_URL = 'https://coinbase.com/oauth/';
	const API_URL = 'https://coinbase.com/api/v1/';

	const SESSION_KEY_TOKEN = 'BITCOIN_TOKEN';

	const HTTP_STATUS_OK = 200;

	protected $_config = array(
		'client_id' => '4ecc81332cc91bfb535c98ba0ae6268934271970b77b47824707e6235f51b4f4',
		'client_secret' => '73306e4bb5cdb64e40cf6d858c3aaccc0706dadf55b40c4014e533c5b6586972'
	);

	protected $_redirectUri;


	public function init()
	{
		if (!in_array('curl', get_loaded_extensions()))
		{
			throw new Exception('Bitcoin: could not perform HTTP request since cUrl extension does not detected.');
		}

		parent::init();

		$this->_redirectUri = IA_ADMIN_URL . 'bitcoin/code/';
	}

	public function getConfig($name)
	{
		return isset($this->_config[$name]) ? $this->_config[$name] : null;
	}

	public function getTokenTimestamp()
	{
		return isset($_SESSION[self::SESSION_KEY_TOKEN])
			? $_SESSION[self::SESSION_KEY_TOKEN]['timestamp']
			: 0;
	}

	public function getAuthorizeUrl()
	{
		$url = $this->_composeUrl('authorize', array(
			'response_type' => 'code',
			'client_id' => $this->getConfig('client_id'),
			'redirect_uri' => $this->_redirectUri
		));

		return $url;
	}

	protected function _composeUrl($action, array $params = array())
	{
		$result = self::OAUTH_URL . $action;
		empty($params) || $result .= '?' . http_build_query($params);

		return $result;
	}

	protected function _httpRequest($url, array $params = array(), $json = false)
	{
		$options = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $params,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false
		);

		if ($json)
		{
			$options[CURLOPT_HTTPHEADER] = array('Content-Type: application/json');
			$options[CURLOPT_POSTFIELDS] = iaUtil::jsonEncode($params);
		}

		$ch = curl_init();
		curl_setopt_array($ch, $options);

		$response = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		empty($response) || $response = iaUtil::jsonDecode($response);

		return array($response, $status);
	}

	protected function _oauthRequest($action, array $postParams = array())
	{
		$url = $this->_composeUrl($action);

		return $this->_httpRequest($url, $postParams);
	}

	protected function _apiRequest($action, array $params = array())
	{
		$url = self::API_URL . $action;
		$url.= '?' . http_build_query(array('access_token' => $this->getToken()->access_token));

		return $this->_httpRequest($url, $params, true);
	}

	public function obtainToken($code)
	{
		list($response, $status) = $this->_oauthRequest('token', array(
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $this->_redirectUri,
			'client_id' => $this->getConfig('client_id'),
			'client_secret' => $this->getConfig('client_secret'),
			'scope' => 'request'
		));

		if (self::HTTP_STATUS_OK == $status)
		{
			$this->_setToken($response);

			$this->iaView->setMessages(iaLanguage::get('authorization_succeed'), iaView::SUCCESS);
		}
		else
		{
			$this->iaView->setMessages($response['error_description'], iaView::ERROR);
		}

		return (self::HTTP_STATUS_OK == $status);
	}

	protected function _setToken($token)
	{
		$_SESSION[self::SESSION_KEY_TOKEN] = array(
			'timestamp' => time(),
			'token' => $token
		);
	}

	private function _isExpired($timestamp, $expireSeconds)
	{
		return ($timestamp + ($expireSeconds * 1000)) > time();
	}

	protected function _refreshToken()
	{
//die('REFRESHING TOKEN...');
	}

	public function getToken($plainArray = false)
	{
		if (isset($_SESSION[self::SESSION_KEY_TOKEN]))
		{
			$result = $_SESSION[self::SESSION_KEY_TOKEN]['token'];

			if ($this->_isExpired($_SESSION[self::SESSION_KEY_TOKEN]['timestamp'], $result['expires_in']))
			{
//				$this->obtainToken($result['refresh_token']);

				$result = $_SESSION[self::SESSION_KEY_TOKEN]['token'];
			}

			return $plainArray ? $result : (object)$result;
		}

		return null;
	}

	public function createButton(array $transactionRecord)
	{
		$params = array('button' => array(
			'name' => $transactionRecord['operation'],
			'type' => 'buy_now',
			'price_string' => (float)$transactionRecord['amount'],
			'price_currency_iso' => $transactionRecord['currency'],
			'custom' => $transactionRecord['id'],
			'callback_url' => IA_URL . 'bitcoin' . IA_URL_DELIMITER,
//			'style' => 'none'
		));

		list($response, $status) = $this->_apiRequest('buttons', $params);

		return (self::HTTP_STATUS_OK == $status) ? $response['button']['code'] : null;
	}
}