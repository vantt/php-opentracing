<?php

namespace Tracing\Util;

use Thrift\Factory\TStringFuncFactory;

class SizeUtil {

  const MAX_TAG_SIZE_PER_SPAN_IN_BYTES = 8192; // 8 KB

  const MAX_LOG_SIZE_PER_SPAN_IN_BYTES = 32768; // 32 KB

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
