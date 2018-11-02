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
  private $userId = '';

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

    $this->userId = $request->input('userId');
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

  private function _getApiResult($source)
  {
    $target = new \stdClass();
    $target->api = '';
    $target->url = '';
    $target->markup = '';
    $target->isAdmin = false;
    $query = (object)array_merge((array)$target, (array)$source);

    $class = 'App\Http\Controllers\\' . $query->api;
    $key = clone $this->q;
    unset($key->sortField);
    unset($key->group);

    $key = md5(json_encode($key) . json_encode($query));
    //Cache::forget($key);die();

    if (Cache::has($key)) {
      $this->arResult = Cache::get($key);
    } else {
      $api = new $class([
        'url' => $query->url,
        'searchCode' => $this->q->searchCode,
        'makeLogo' => $this->q->makeLogo,
        'substLevel' => $this->q->substLevel,
        'markup' => $query->markup,
        'userId' => $this->userId,
        'isAdmin' => $query->isAdmin
      ]);
      $this->arResult = $api->getResult();
      Cache::put($key, $this->arResult, 10);
    }
    return $this->arResult;
  }

  private function _getArrContractors($queryContractor = null)
  {
    $source = new \stdClass();
    $source->markup = false;
    $source->isAdmin = false;
    $source = (object)array_merge((array)$source, (array)$queryContractor);
    $source->api = 'ApiBitrix';
    $source->url = '192.168.20.221/api/class-search-code.php';

    if ($source->markup) {
      return $this->_getApiResult($source);
    };
    if ($source->isAdmin) {
      return $this->_getApiResult($source);
    };
    $bitrix = $this->_getApiResult($source);
    $bitrixJson = json_decode($bitrix);

    if ($this->q->bitrix === 'yes') {
      return [$bitrixJson];
    }
    $source->api = 'ApiEmex';
    $source->url = 'http://ws.emex.ru/EmExService.asmx?wsdl';
    $source->makeLogo = $this->q->makeLogo;
    $source->substLevel = $this->q->substLevel;
    $emex = $this->_getApiResult($source);
    $emexJson = json_decode($emex);

    return [$bitrixJson, $emexJson];
  }

  private function _mergerContractor($keyLimit = 20)
  {
    $contractors = [];
    $queryContractor = new \stdClass();
    $queryContractor->isAdmin = true;
    $isAdmin = $this->_getArrContractors($queryContractor);
    $queryContractor->isAdmin = false;
    $queryContractor->markup = true;
    $markup = $this->_getArrContractors($queryContractor);
    $queryContractor->markup = false;
    $arrContracts = $this->_getArrContractors();
    $totalObj = new \stdClass();
    $totalItem = new \stdClass();
    $fullObj = new \stdClass();
    $totalItem->countBitix = 0;
    $totalItem->countApi = 0;
    $totalItem->minDays = 0;
    $totalItem->minPriceContractor = 0;
    $totalItem->minPriceOur = 0;
    $arrUniqueBrandAndCode = [];
    $keyLimit = empty($this->q->limit) ? $keyLimit : $this->q->limit;

    foreach ($arrContracts as $contract) {
      if ($contract === null) {
        continue;
      }

      foreach ($contract as $key => $item) {
        $item = new ConvertContracts($item, $markup);
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
    $totalObj->minDays = $totalItem->minDays;
    $totalObj->minPriceOur = $totalItem->minPriceOur;
    $totalObj->minPriceContractor = $totalItem->minPriceContractor;
    $totalObj->countBitix = $totalItem->countBitix;
    $totalObj->countApi = $totalItem->countApi;
    $totalObj->countGroupUnique = count($arrUniqueBrandAndCode);
    $totalObj->isAdmin = $isAdmin;
    $fullObj->item = $contractors;
    $fullObj->total = $totalObj;
    return $fullObj;
  }
}