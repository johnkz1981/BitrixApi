<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class ApiController extends Controller
{
  use TraitForApiController;

  private $q = null;
  private $arResult = null;

  function __construct(Request $request)
  {
    $objProvader = new \stdClass();

    $target = new \stdClass();
    $target->makeLogo = '';
    $target->substLevel = '';
    $target->brandAndCode = '';
    $target->bitrix = '';
    $target->priceGroupName = '';
    $target->priceGroup = '';
    $target->group = '';
    $target->array = '';

    $this->q = (object)array_merge((array)$target, (array)json_decode($request->input('q')));
  }

  function index()
  {
    if ($this->q->array === 'yes') {
      print_r($this->_mergerContractor());
      return;
    }
    echo json_encode($this->_mergerContractor());
  }

  private function _getApiResult($api, $url)
  {
    $class = 'App\Http\Controllers\\' . $api;
    $key = md5(json_encode($this->q) . $api);
    //Cache::forget($key);die();

    if (Cache::has($key)) {
      $this->arResult = Cache::get($key);
    } else {
      $api = new $class($url, $this->q->searchCode, $this->q->makeLogo, $this->q->substLevel);
      $this->arResult = $api->getResult();
      Cache::put($key, $this->arResult, 10);
    }
    return $this->arResult;
  }

  private function _getArrContractors()
  {
    $bitrix = $this->_getApiResult('ApiBitrix', '192.168.20.221/api/class-search-code.php');
    $bitrixJson = json_decode($bitrix);

    if ($this->q->bitrix === 'yes') {
      return [$bitrixJson];
    }
    $emex = $this->_getApiResult('ApiEmex', 'http://ws.emex.ru/EmExService.asmx?wsdl', $this->q->makeLogo, $this->q->substLevel);
    $emexJson = json_decode($emex);

    return [$bitrixJson, $emexJson];
  }

  private function _mergerContractor($keyLimit = 30)
  {
    $contractors = [];
    $arrContracts = $this->_getArrContractors();
    $totalObj = new \stdClass();
    $totalItem = new \stdClass();
    $totalItem->countBitix = 0;
    $totalItem->countApi = 0;
    $totalItem->minDays = 0;
    $totalItem->minPriseContractor = 0;
    $totalItem->minPriseOur = 0;
    $arrUniqueBrandAndCode = [];
    $keyLimit = empty($this->q->limit) ? $keyLimit : $this->q->limit;

    foreach ($arrContracts as $contract) {
      if ($contract === null) {
        continue;
      }
      foreach ($contract as $key => $item) {
        $item = new ConvertContracts($item);
        $contractorItem = $item->getResult();
        if ($this->_getSkipped($contractorItem)) {
          continue;
        }
        $totalItem = $this->_getSeparationTotal($contractorItem, $totalItem);
        if ($this->_isSkippedNotUnique($contractorItem, $arrUniqueBrandAndCode)) {
          continue;
        }
        if ($this->_isSkippedPriceGroup($contractorItem, $arrUniqueBrandAndCode)) {
          continue;
        }
        $contractors[] = $contractorItem;
      }
    }
    $this->_sortBy($contractors);
    $contractors = $this->_limitRows($contractors, $keyLimit);
    $totalObj->name = '';
    $totalObj->code = '';
    $totalObj->prise = '';
    $totalObj->vendorСode = '';
    $totalObj->deliveryTime = '';
    $totalObj->contractor = '';
    $totalObj->manufacturer = '';
    $totalObj->quantity = '';
    $totalObj->minDays = $totalItem->minDays;
    $totalObj->minPriseOur = $totalItem->minPriseOur;
    $totalObj->minPriseContractor = $totalItem->minPriseContractor;
    $totalObj->countBitix = $totalItem->countBitix;
    $totalObj->countApi = $totalItem->countApi;
    $totalObj->countGroupUnique = count($arrUniqueBrandAndCode);
    $contractors[] = $totalObj;
    return $contractors;
  }
}