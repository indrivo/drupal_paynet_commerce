<?php

namespace Drupal\commerce_paynetmd;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_paynetmd\PaynetResult;
use Drupal\commerce_paynetmd\PaynetCode;
use Drupal\Core\Url;

/**
 * Class PaynetCommerceService.
 */
class PaynetCommerceService {

  const API_VERSION = "Version 1.2";

  /**
   * Paynet merchant code.
   * @var string
   */
  private $merchant_code;

  /**
   * Paynet merchant secret key.
   * @var string
   */
  private $merchant_secret_key;

  /**
   * Paynet merchant user for access to API.
   * @var string
   */
  private $merchant_user;

  /**
   * Paynet merchant user's password.
   * @var string
   */
  private $merchant_user_password;

  /**
   * @var string
   */
  private $api_base_url;

  /**
   * The expiry time for this operation, in hours.
   */
  const EXPIRY_DATE_HOURS = 4;

  /**
   * Hours.
   */
  const ADAPTING_HOURS = 1;

  /**
   * Constructs a new PaynetCommerceService object.
   */
  public function __construct() {}

  public function setPayment(PaymentInterface $payment) {
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin()->getConfiguration();
    $this->merchant_code = $payment_gateway_plugin['id_partner'];
    $this->merchant_user = $payment_gateway_plugin['id_user'];
    $this->merchant_user_password = $payment_gateway_plugin['merchant_user_pass'];

    switch ($payment->getPaymentGateway()->getPlugin()->getMode()) {
      case 'test':
        $this->api_base_url = $payment_gateway_plugin['test_paynet_api_base_ui_url'];
        break;

      case 'live':
        $this->api_base_url = $payment_gateway_plugin['paynet_api_base_ui_url'];
        break;

      default:
        $this->api_base_url = 'https://test.paynet.md:4446';
        break;
    }
  }

  public function Version() {
    return self::API_VERSION;
  }

  public function TokenGet($addHeader = false) {
    $path = '/auth';
    $params = [
      'grant_type' => 'password',
      'username' => $this->merchant_user,
      'password' => $this->merchant_user_password
    ];

    $tokenReq =  $this->callApi($path, 'POST', $params);
    $result = new PaynetResult();

    if($tokenReq->Code == PaynetCode::SUCCESS) {
      if(array_key_exists('access_token', $tokenReq->Data)) {
        $result->Code = PaynetCode::SUCCESS;
        if($addHeader) {
          $result->Data = ["Authorization: Bearer ".$tokenReq->Data['access_token']];
        }
        else {
          $result->Data = $tokenReq->Data['access_token'];
        }
      }
      else {
        $result->Code = PaynetCode::USERNAME_OR_PASSWORD_WRONG;
        if(array_key_exists('Message', $tokenReq->Data)) {
          $result->Message = $tokenReq->Data['Message'];
        }

        if(array_key_exists('error', $tokenReq->Data)) {
          $result->Message = $tokenReq->Data['error'];
        }
      }
    }
    else {
      $result->Code = $tokenReq->Code;
      $result->Message = $tokenReq->Message;
    }

    return $result;
  }

  public function PaymentGet($externalID) {
    $path = '/api/Payments';
    $params = [
      'ExternalID' 	=> $externalID
    ];

    $tokenReq = $this->TokenGet(true);
    $result = new PaynetResult();

    if($tokenReq->IsOk()) {
      $resultCheck = $this->callApi($path, 'GET',null, $params, $tokenReq->Data);
      if($resultCheck->IsOk()) {
        $result->Code = $resultCheck->Code;

        if(array_key_exists('Code',$resultCheck->Data)) {
          $result->Code = $resultCheck->Data['Code'];
          $result->Message = $resultCheck->Data['Message'];
        }
        else {
          $result->Data = $resultCheck->Data;
        }
      }
      else {
        $result = $resultCheck;
      }
    }
    else {
      $result->Code = $tokenReq->Code;
      $result->Message = $tokenReq->Message;
    }

    return $result;
  }

  public function FormCreate($pRequest) {
    $result = new PaynetResult();
    $result->Code = PaynetCode::SUCCESS;

    $_service_name = '';
    $product_line = 0;
    $_service_item = "";

    $pRequest->ExpiryDate = $this->ExpiryDateGet(self::EXPIRY_DATE_HOURS);

    $amount = 0;
    foreach ( $pRequest->Service["Products"] as $item ) {
      $_service_item .='<input type="hidden" name="Services[0][Products]['.$product_line.'][LineNo]" value="'.htmlspecialchars_decode($item['LineNo']).'"/>';
      $_service_item .='<input type="hidden" name="Services[0][Products]['.$product_line.'][Code]" value="'.htmlspecialchars_decode($item['Code']).'"/>';
      $_service_item .='<input type="hidden" name="Services[0][Products]['.$product_line.'][BarCode]" value="'.htmlspecialchars_decode($item['Barcode']).'"/>';
      $_service_item .='<input type="hidden" name="Services[0][Products]['.$product_line.'][Name]" value="'.htmlspecialchars_decode($item['Name']).'"/>';
      $_service_item .='<input type="hidden" name="Services[0][Products]['.$product_line.'][Description]" value="'.htmlspecialchars_decode($item['Descrption']).'"/>';
      $_service_item .='<input type="hidden" name="Services[0][Products]['.$product_line.'][Quantity]" value="'.htmlspecialchars_decode($item['Quantity'] ).'"/>';
      $_service_item .='<input type="hidden" name="Services[0][Products]['.$product_line.'][UnitPrice]" value="'.htmlspecialchars_decode(($item['UnitPrice'])).'"/>';
      $product_line++;
      $amount += $item['Quantity']/100 * $item['UnitPrice'];
    }

    $pRequest->Service["Amount"] = $amount;
    $signature = $this->SignatureGet($pRequest);
    $pp_form =  '<form method="POST" action="'.$this->api_base_url.'">'.
      '<input type="hidden" name="ExternalID" value="'.$pRequest->ExternalID.'"/>'.
      '<input type="hidden" name="Services[0][Description]" value="'.htmlspecialchars_decode($pRequest->Service["Description"]).'"/>'.
      '<input type="hidden" name="Services[0][Name]" value="'.htmlspecialchars_decode($pRequest->Service["Name"]).'"/>'.
      '<input type="hidden" name="Services[0][Amount]" value="'.$amount.'"/>'.
      $_service_item.
      '<input type="hidden" name="Currency" value="'.$pRequest->Currency.'"/>'.
      '<input type="hidden" name="Merchant" value="'.$this->merchant_code.'"/>'.
      '<input type="hidden" name="Customer.Code"   value="'.htmlspecialchars_decode($pRequest->Customer['Code']).'"/>'.
      '<input type="hidden" name="Customer.Name"   value="'.htmlspecialchars_decode($pRequest->Customer['Name']).'"/>'.
      '<input type="hidden" name="Customer.Address"   value="'.htmlspecialchars_decode($pRequest->Customer['Address']).'"/>'.
      '<input type="hidden" name="ExternalDate" value="'.htmlspecialchars_decode($this->ExternalDate()).'"/>'.
      '<input type="hidden" name="LinkUrlSuccess" value="'.htmlspecialchars_decode($pRequest->LinkSuccess).'"/>'.
      '<input type="hidden" name="LinkUrlCancel" value="'.htmlspecialchars_decode($pRequest->LinkCancel).'"/>'.
      '<input type="hidden" name="ExpiryDate"   value="'.htmlspecialchars_decode($pRequest->ExpiryDate).'"/>'.
      '<input type="hidden" name="Signature" value="'.$signature.'"/>'.
      '<input type="hidden" name="Lang" value="'.$pRequest->Lang.'"/>'.
      '<input type="submit" value="GO to a payment gateway of paynet" />'.
      '</form>';
    $result->Data = $pp_form;

    // @todo: create an array in the correct format.
    $result->Data = [];

    return $result;
  }

  public  function PaymentReg($pRequest) {
    $path = '/api/Payments/Send';
    $pRequest->ExpiryDate = $this->ExpiryDateGet(self::EXPIRY_DATE_HOURS);

    foreach ($pRequest->Service[0]['Products'] as $item) {
      $pRequest->Service[0]['Amount'] += ($item['Quantity']) * $item['UnitPrice'];
    }

    $params = [
      'Invoice' => $pRequest->ExternalID,
      'MerchantCode' => $this->merchant_code,
      'LinkUrlSuccess' =>  $pRequest->LinkSuccess,
      'LinkUrlCancel' => $pRequest->LinkCancel,
      'Customer' => $pRequest->Customer,
      'Payer' => null,
      'Currency' => $pRequest->Currency,
      'ExternalDate' => $this->ExternalDate(),
      'ExpiryDate' => $this->ExpiryDateGet(self::EXPIRY_DATE_HOURS),
      'Services' => $pRequest->Service,
      'Lang' => $pRequest->Lang
    ];

    $tokenReq =  $this->TokenGet(true);
    $result = new PaynetResult();

    if($tokenReq->IsOk()) {
      $resultCheck = $this->callApi($path, 'POST', $params,[], $tokenReq->Data);
      if($resultCheck->IsOk()) {
        $result->Code = $resultCheck->Code;

        if(array_key_exists('Code',$resultCheck->Data)) {
          $result->Code = $resultCheck->Data['Code'];
          $result->Message = $resultCheck->Data['Message'];
        }
        else {
//          $result->Data = '<form method="POST" action="'.$this->api_base_url.'">'.
//            '<input type="hidden" name="operation" value="'.htmlspecialchars_decode($resultCheck->Data['PaymentId']).'"/>'.
//            '<input type="hidden" name="LinkUrlSucces" value="'.htmlspecialchars_decode($pRequest->LinkSuccess).'"/>'.
//            '<input type="hidden" name="LinkUrlCancel" value="'.htmlspecialchars_decode($pRequest->LinkCancel).'"/>'.
//            '<input type="hidden" name="ExpiryDate"   value="'.htmlspecialchars_decode($pRequest->ExpiryDate).'"/>'.
//            '<input type="hidden" name="Signature" value="'.$resultCheck->Data['Signature'].'"/>'.
//            '<input type="hidden" name="Lang" value="'.$pRequest->Lang.'"/>'.
//            '<input type="submit" value="GO to a payment gateway of paynet" />'.
//            '</form>';

          $result->Data = [
            'operation' => htmlspecialchars_decode($resultCheck->Data['PaymentId']),
            'LinkUrlSucces' => htmlspecialchars_decode($pRequest->LinkSuccess),
            'LinkUrlCancel' => htmlspecialchars_decode($pRequest->LinkCancel),
            'ExpiryDate' => htmlspecialchars_decode($pRequest->ExpiryDate),
            'Signature' => $resultCheck->Data['Signature'],
            'Lang' => $pRequest->Lang,
          ];
        }
      }
      else {
        $result = $resultCheck;
      }
    }
    else {
      $result->Code = $tokenReq->Code;
      $result->Message = $tokenReq->Message;
    }

    return $result;
  }

  private function callApi($path, $method = 'GET', $params = [], $query_params = [], $headers = []) {
    $result = new PaynetResult();

    $url = $this->api_base_url . $path;

    if (count($query_params) > 0) {
      $url .= '?' . http_build_query($query_params);
    }

    $ch = curl_init($url);
    if ($headers) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    if ($method != 'GET') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    $json_response = curl_exec($ch);
    if ($json_response === false) {
      // If an error occurred, remember the error and return false.
      $result->Message = curl_error($ch).', '.curl_errno($ch);
      $result->Code = PaynetCode::CONNECTION_ERROR;

      // Remember to close the cURL object
      curl_close($ch);
      return $result;
    }

    // No error, just decode the JSON response, and return it.
    $result->Data = json_decode($json_response, true);

    // Remember to close the cURL object
    curl_close($ch);
    $result->Code = PaynetCode::SUCCESS;
    return $result;
  }

  private function ExpiryDateGet($addHours) {
    $date = strtotime("+".$addHours." hour");
    return date('Y-m-d', $date).'T'.date('H:i:s', $date);
  }

  public function ExternalDate($addHours = self::ADAPTING_HOURS) {
    $date = strtotime("+".$addHours." hour");
    return date('Y-m-d', $date).'T'.date('H:i:s', $date);
  }

  private function SignatureGet($request) {
    $_sing_raw  = $request->Currency;
    $_sing_raw .= $request->Customer['Address'].$request->Customer['Code'].$request->Customer['Name'];
    $_sing_raw .= $request->ExpiryDate.strval($request->ExternalID).$this->merchant_code;
    $_sing_raw .= $request->Service['Amount'].$request->Service['Name'].$request->Service['Description'];
    $_sing_raw .= $this->merchant_secret_key;

    return base64_encode(md5($_sing_raw, true));
  }

  public function __get ($name) {
    return $this->$name ?? null;
  }

}
