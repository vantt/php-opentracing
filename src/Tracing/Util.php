<?php

namespace Tracing;

use Thrift\Factory\TStringFuncFactory;

class Util {

  public static function calcSizeInBytes($data) {
    if ($data === null) {
      return 0;
    }

    $strSerializedData = serialize($data);

    $intSize = TStringFuncFactory::create()->strlen($strSerializedData);

    return $intSize;
  }

  public static function exceedSizeInBytes($data, $intSizeInBytes) {
    if (self::calcSizeInBytes($data) > $intSizeInBytes) {
      return true;
    }

    return false;
  }

}
