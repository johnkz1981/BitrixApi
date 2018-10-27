<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConvertContracts extends Controller
{
  private $item;

  public function __construct($item)
  {
    $this->item = $item;
  }

  private function _convert()
  {
    if ($this->item instanceof \stdClass) {
      $this->item->name = $this->item->name ?? $this->item->DetailNameRus;
      $this->item->prise = $this->_getPrise();
      $this->item->vendorСode = $this->item->vendorСode ?? $this->item->DetailNum;
      $this->item->deliveryTime = $this->_getDeliveryTime();
      $this->item->contractor = $this->_getName();
      $this->item->manufacturer = $this->item->manufacturer ?? $this->item->MakeName;
      $this->item->quantity = $this->item->quantity ?? $this->item->Quantity;
      $this->item->brandAndCode = strtoupper($this->item->manufacturer . $this->item->vendorСode);
      $this->item->MakeLogo = $this->item->MakeLogo ?? '';
      $this->item->PriceGroup = $this->item->PriceGroup ?? 'Original';
      $this->item->color = $this->_getColor();
      //$this->item->id = $this->item->id ?? $this->item->GroupId;
    }
  }

  private function _getName()
  {
    if (isset($this->item->contractor)) {
      $this->item->isContractor = 1;
      return $this->item->contractor;
    }
    if (isset($this->item->xmlId)) {
      $this->item->isContractor = 0;
      return 'Югавтодеталь';
    } else {
      $this->item->isContractor = 1;
      return 'emex';
    }
  }

  private function _getDeliveryTime()
  {
    if (isset($this->item->ADDays)) {
      return $this->item->ADDays;
    }
    if (isset($this->item->deliveryTime)) {
      return (int)$this->item->deliveryTime;
    }
    return null;
  }

  private function _getPrise()
  {
    if (isset($this->item->prise)) {
      return (int)$this->item->prise;
    } else {
      return (int)$this->item->ResultPrice;
    }
  }

  private function _getColor()
  {
    if (isset($this->item->DDPercent)) {

      if ($this->item->DDPercent < 25) {
        return 'error';
      } elseif ($this->item->DDPercent < 75) {
        return 'warning';
      } else {
        return 'success';
      }
    } else {
      return '';
    }
  }

  public function getResult()
  {
    $this->_convert();
    return $this->item;
  }
}
