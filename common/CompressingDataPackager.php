<?php

require_once("EncryptedDataPacker.php");

/*!
	\brief	Extends the EncryptedDataPackager, by adding gzip compression
	
	\remarks	This class overrides the encode and decode methods of EncryptedDataPackager to add gzip compression.
				The resulting string is usually 25% to 60% smaller, depending on its contents.
				
	\author		Dimitrios Tsobanopoulos, 2014

*/
class CompressingDataPacker extends EncryptedDataPacker
{
	public function __construct($max_size = EDP_MAX_SIZE, $flags=EDP_OPT_SIZE)
	{
		parent::EncryptedDataPacker($max_size, $flags);
	}


	public function encodePayload($passwd, $data_array)
	{
		$str = parent::encodePayload($passwd, $data_array);
		$str = gzcompress($str);
		return base64_encode($str);
	}

	public function decodePayload($cookie_value, $passwd)
	{
		$value = base64_decode($cookie_value);
		$value = gzuncompress($value);
		$value =  parent::decodePayload($value, $passwd);
		return $value;
	}

}

?>