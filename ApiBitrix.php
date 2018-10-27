<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiBitrix implements IApi
{
  private $url = '';
  private $q = '';

  public function __construct($url = '', $q = '')
  {
    $this->url = $url;
    $this->q = $q;
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
    $ch = curl_init("$this->url?q=$this->q");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    return curl_exec($ch);
  }
}
