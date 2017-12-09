<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://subrion.org/
 *
 ******************************************************************************/

class iaCoinbase extends abstractModuleAdmin
{
    const OAUTH_URL = 'https://coinbase.com/oauth/';
    const API_URL = 'https://coinbase.com/api/v1/';

    const SESSION_KEY_TOKEN = 'BITCOIN_TOKEN';

    const HTTP_STATUS_OK = 200;

    protected $_config = [
        'client_id' => 'XXX',
        'client_secret' => 'XXX'
    ];

    protected $_redirectUri;


    public function init()
    {
        if (!in_array('curl', get_loaded_extensions())) {
            throw new Exception('Coinbase: could not perform HTTP request since cUrl extension does not detected.');
        }

        parent::init();

        $this->_redirectUri = IA_ADMIN_URL . 'coinbase/code/';
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
        $url = $this->_composeUrl('authorize', [
            'response_type' => 'code',
            'client_id' => $this->getConfig('client_id'),
            'redirect_uri' => $this->_redirectUri
        ]);

        return $url;
    }

    protected function _composeUrl($action, array $params = [])
    {
        $result = self::OAUTH_URL . $action;
        empty($params) || $result .= '?' . http_build_query($params);

        return $result;
    }

    protected function _httpRequest($url, array $params = [], $json = false)
    {
        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        if ($json) {
            $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
            $options[CURLOPT_POSTFIELDS] = json_encode($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        empty($response) || $response = json_decode($response, true);

        return [$response, $status];
    }

    protected function _oauthRequest($action, array $postParams = [])
    {
        $url = $this->_composeUrl($action);

        return $this->_httpRequest($url, $postParams);
    }

    protected function _apiRequest($action, array $params = [])
    {
        $url = self::API_URL . $action;
        $url .= '?' . http_build_query(['access_token' => $this->getToken()->access_token]);

        return $this->_httpRequest($url, $params, true);
    }

    public function obtainToken($code)
    {
        list($response, $status) = $this->_oauthRequest('token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->_redirectUri,
            'client_id' => $this->getConfig('client_id'),
            'client_secret' => $this->getConfig('client_secret'),
            'scope' => 'request'
        ]);

        if (self::HTTP_STATUS_OK == $status) {
            $this->_setToken($response);

            $this->iaView->setMessages(iaLanguage::get('authorization_succeed'), iaView::SUCCESS);
        } else {
            $this->iaView->setMessages($response['error_description'], iaView::ERROR);
        }

        return (self::HTTP_STATUS_OK == $status);
    }

    protected function _setToken($token)
    {
        $_SESSION[self::SESSION_KEY_TOKEN] = [
            'timestamp' => time(),
            'token' => $token
        ];
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
        if (isset($_SESSION[self::SESSION_KEY_TOKEN])) {
            $result = $_SESSION[self::SESSION_KEY_TOKEN]['token'];

            if ($this->_isExpired($_SESSION[self::SESSION_KEY_TOKEN]['timestamp'], $result['expires_in'])) {
//				$this->obtainToken($result['refresh_token']);

                $result = $_SESSION[self::SESSION_KEY_TOKEN]['token'];
            }

            return $plainArray ? $result : (object)$result;
        }

        return null;
    }

    public function createButton(array $transactionRecord)
    {
        $params = [
            'button' => [
                'name' => $transactionRecord['operation'],
                'type' => 'buy_now',
                'price_string' => (float)$transactionRecord['amount'],
                'price_currency_iso' => $transactionRecord['currency'],
                'custom' => $transactionRecord['id'],
                'callback_url' => IA_URL . 'coinbase' . IA_URL_DELIMITER,
//			'style' => 'none'
            ]
        ];

        list($response, $status) = $this->_apiRequest('buttons', $params);

        return (self::HTTP_STATUS_OK == $status) ? $response['button']['code'] : null;
    }
}
