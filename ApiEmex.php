<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiEmex implements IApi
{
  private $url = '';
  private $q = '';
  private $makeLogo = '';
  private $substLevel = '';

  public function __construct($url = '', $q = '', $makeLogo = '', $substLevel = '')
  {
    $this->url = $url;
    $this->q = $q;
    $this->makeLogo = urldecode($makeLogo);
    $this->substLevel = $substLevel;
    $this->login = '1217282';
    $this->password = "125asdf";
  }

  public function setUrl($url)
  {
    $this->url = $url;
  }

  public function setCode($q)
  {
    $this->q = $q;
  }

  public function getResult()
  {
    try {
      $client = new \SoapClient($this->url);
      $params = [
        "login" => $this->login,
        "password" => $this->password,
        "detailNum" => $this->q,
        "makeLogo" => $this->makeLogo,
        "substLevel" => $this->substLevel,
        "substFilter" => "None",
        "deliveryRegionType" => "PRI",
        "maxOneDetailOffersCount" => null,
      ];
      $result = $client->FindDetailAdv4($params);
      $resulJson = json_encode($result->FindDetailAdv4Result->Details);

      if ($resulJson === '{}' || $result->FindDetailAdv4Result->Details->SoapDetailItem instanceof \stdClass) {
        return $this->arResult = json_encode($result->FindDetailAdv4Result->Details);
      } else {
        return $this->arResult = json_encode($result->FindDetailAdv4Result->Details->SoapDetailItem);
      }
    } catch (SoapFault $exception) {

    }
  }
}
