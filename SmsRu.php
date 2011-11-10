<?php
/**
 * Author: Nikolay Shkuro
 * Email: nikolas.shkuro@gmail.com
 */
 
class SmsRu {

  const HOST  = 'http://sms.ru/';
  const SEND = 'sms/send?';
  const COST = 'sms/cost?';
  const STATUS = 'sms/status?';
  const BALANCE = 'my/balance?';
  const LIMIT = 'my/limit?';
  const TOKEN = 'auth/get_token';
  const CHECK = 'auth/check?';
  
  //TODO: change here
  private $api_id = '';
  private $login = '';
  private $pwd = '';

  private $token;
  private $sig;

  private $_strongAuth = false;

  private $_lastAction;
  private $_lastResponseCode;
  
  protected $_responseCode = array(
    'send' => array(
      '100' => 'Message is accepted to send',
      '200' => 'Incorrect api_id',
      '201' => 'Not enough money',
      '202' => 'Incorrect recipient',
      '203' => 'No text messages',
      '204' => 'The name of the sender is not agreed with the administration',
      '205' => 'The message is too long (more than 5 SMS)',
      '206' => 'Exceeded the daily limit for sending messages',
      '207' => 'On this number can not send messages',
      '208' => 'Time parameter is incorrect',
      '210' => 'Use GET, POST should be used where',
      '211' => 'The method was not found',
      '220' => 'Service is temporarily unavailable, please try later',
    ),

    'status' => array(
      '-1' => 'Message not found',
      '100' => 'Message in the queue',
      '101' => 'Message to the operator',
      '102' => 'Your post (in transit)',
      '103' => 'Message delivered',
      '104' => 'Can not be reached: the lifetime has expired',
      '105' => 'Can not be reached: Removed from operator',
      '106' => 'Can not be reached: failed to phone',
      '107' => 'Can not be reached: unknown cause',
      '108' => 'Can not be reached: rejected',
      '200' => 'Incorrect api_id',
      '210' => 'Use GET, POST should be used where',
      '211' => 'The method was not found',
      '220' => 'Service is temporarily unavailable, please try later',
    ),

    'balance' => array(
      '100' => 'Request is made',
      '200' => 'Incorrect api_id',
      '210' => 'Use GET, POST should be used where',
      '211' => 'The method was not found',
      '220' => 'Service is temporarily unavailable, please try later',
    ),

    'limit' => array(
      '100' => 'Request is made',
      '200' => 'Incorrect api_id',
      '210' => 'Use GET, POST should be used where',
      '211' => 'The method was not found',
      '220' => 'Service is temporarily unavailable, please try later',
    ),
    
    'cost' => array(
      '100' => 'Message is accepted to send',
      '200' => 'Incorrect api_id',
      '202' => 'Incorrect recipient',
      '206' => 'Exceeded the daily limit for sending messages',
      '207' => 'On this number can not send messages',
      '210' => 'Use GET, POST should be used where',
      '211' => 'The method was not found',
      '220' => 'Service is temporarily unavailable, please try later',
    ),

    'check' => array(
      '100' => 'Number and password are the same',
      '300' => 'Invalid token (may have expired, or your IP has changed)',
      '301' => 'Wrong password or user not found'
    )

  );

  /**
   * Consructor
   *
   * @param bool $auth Strong auth.
   */
  function  __construct($auth = false) {
    $this->makeSig();
    if ($auth) {
      $this->_strongAuth = true;
    }
  }

  /*
   * Make sig for stong auth.
   */
  private function makeSig() {
    $this->getToken();
    $this->sig = md5($this->pwd . $this->token);
  }

  /**
   *
   * Send SMS
   *
   * @param string $to Phone number. Format 79999999999
   * @param string $text Message ( UTF-8 if not define other encoding)
   * @param bool $express Express delivery
   * @param bool $test Test mode
   * @param string $from Replace sender
   * @param string $encoding Encoding. Support only windows-1251
   * @param string $time Sending time. Timestamp.
   * There should be no longer than 7 days from the filing of a request and not less than the current time.
   * @return int sms_id
   */
  public function send($to, $text, $express = false, $test = false, $from = null, $encoding = null, $time = null) {
    $params = array(
      'to' => $to,
      'text' => $text,
    );

    if ($this->_strongAuth) {
      $params['login'] = $this->login;
      $params['token'] = $this->token;
      $params['sig'] = $this->sig;
    } else {
      $params['api_id'] = $this->api_id;
    }

    if ($encoding) {
      $params['encoding'] = $encoding;
    }

    if ($from) {
      $params['from'] = $from;
    }

    if ($express) {
      $params['express'] = 1;
    }

    if ($test) {
      $params['test'] = 1;
    }

    if ($time) {
      $params['time'] = $time;
    }

    $url = self::HOST . self::SEND . http_build_query($params);
    $res = file_get_contents($url);
    list($code, $id) = explode("\n", $res);
    $this->_lastAction = 'send';
    $this->_lastResponseCode = $code;
    return $id;
  }

  // TODO: Implement this method
  public function mail() {
    return 'Sorry, not implement yet';
  }


  /**
   * Get Status by sms id
   *
   * @param $id Sms ID
   * @return string Status code
   */
  public function status($id) {
    $params = array(
      'api_id' => $this->api_id,
      'id' => $id
    );
    $url = self::HOST . self::STATUS . http_build_query($params);
    $res = file_get_contents($url);
    $status = $res;
    $this->_lastAction = 'status';
    $this->_lastResponseCode = $status;
    return $res;
  }


  /**
   * Returns the value of the specified number of messages and the number of messages needed to send it.
   *
   * @param string $to Phone number. Format 79999999999
   * @param string $text Message ( UTF-8 if not define other encoding)
   * @param string $encoding Encoding. Support only windows-1251
   * @return Cost
   */
  public function cost($to, $text, $encoding = null) {
    $params = array(
      'to' => $to,
      'text' => $text,
    );

    if ($this->_strongAuth) {
      $params['login'] = $this->login;
      $params['token'] = $this->token;
      $params['sig'] = $this->sig;
    } else {
      $params['api_id'] = $this->api_id;
    }
    if ($encoding) {
      $params['encoding'] = $encoding;
    }
    $url = self::HOST . self::COST . http_build_query($params);
    $res = file_get_contents($url);
    list($code, $cost, $length) = explode("\n", $res);
    $this->_lastAction = 'cost';
    $this->_lastResponseCode = $code;
    return $cost;
  }

  /**
   * Getting on the balance.
   *
   * @return Balance
   */
  public function balance() {
    $params = array(
      'api_id' => $this->api_id
    );
    $url = self::HOST . self::BALANCE . http_build_query($params);
    $res = file_get_contents($url);
    list($code,$balance) = explode("\n", $res);
    $this->_lastAction = 'balance';
    $this->_lastResponseCode = $code;
    return $balance;
  }

  /**
   * Getting the current state of your daily limit.
   *
   * @return int Limit
   */
  public function limit() {
    $params = array(
      'api_id' => $this->api_id
    );
    $url = self::HOST . self::LIMIT . http_build_query($params);
    $res = file_get_contents($url);
    list($code,$count,$limit) = explode("\n", $res);
    $this->_lastAction = 'limit';
    $this->_lastResponseCode = $code;
    return (int)($count - $limit);
  }

  /**
   * Check the phone number and password
   *
   * @return string Status code
   */
  public function check() {
    $params = array(
      'login' => $this->login,
      'token' => $this->token,
      'sig' => $this->sig
    );
    $url = self::HOST . self::CHECK . http_build_query($params);
    $res = file_get_contents($url);
    $check = $res;
    $this->_lastAction = 'check';
    $this->_lastResponseCode = $check;
    return $check;
  }

  /**
   * Obtaining a temporary key that allows encrypted password in the future.
   * Used in the methods that require enhanced authorization.
   * Assigned to your IP address and it only works for 10 minutes.
   *
   * @return void
   */
  private function getToken() {
    $url = self::HOST . self::TOKEN;
    $res = file_get_contents($url);
    $this->token = $res;
    $this->_lastAction = 'getToken';
  }

  /**
   * Getting last response code.
   *
   * @return Last response code
   */
  public function getResponseCode() {
    return $this->_lastResponseCode;
  }

  /**
   * Getting last response message
   *
   * @return Response message
   */
  public function getResponseMsg() {
    if ($this->_lastAction) {
      return $this->_responseCode[$this->_lastAction][$this->getResponseCode()];
    } else {
      return false;
    }
  }

}
