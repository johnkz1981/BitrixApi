<?php

namespace App\Http\Controllers;

interface IApi
{
  public function setUrl($url);
  public function setCode($q);
  public function getResult();
}
