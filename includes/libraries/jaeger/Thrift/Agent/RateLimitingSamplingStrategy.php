<?php
namespace Jaeger\Thrift\Agent;

/**
 * Autogenerated by Thrift Compiler (0.11.0)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
use Thrift\Base\TBase;
use Thrift\Type\TType;
use Thrift\Type\TMessageType;
use Thrift\Exception\TException;
use Thrift\Exception\TProtocolException;
use Thrift\Protocol\TProtocol;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Exception\TApplicationException;


class RateLimitingSamplingStrategy extends TBase {
  static $isValidate = false;

  static $_TSPEC = array(
    1 => array(
      'var' => 'maxTracesPerSecond',
      'isRequired' => true,
      'type' => TType::I16,
      ),
    );

  /**
   * @var int
   */
  public $maxTracesPerSecond = null;

  public function __construct($vals=null) {
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'RateLimitingSamplingStrategy';
  }

  public function read($input)
  {
    return $this->_read('RateLimitingSamplingStrategy', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('RateLimitingSamplingStrategy', self::$_TSPEC, $output);
  }

}

