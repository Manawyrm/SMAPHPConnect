<?php

require_once dirname(__FILE__) . '/Phpmodbus/ModbusMaster.php';

$modbus = new ModbusMaster("sma.plenet.org", "TCP");

$unitID = 3;

$registerToRead = [
    30529, /* Total yield Wh */
    30535, /* Daily yield Wh */
    30777, /* Power L1 W */
    30779, /* Power L2 W */
    30781, /* Power L3 W */
    30803, /* Grid frequency Hz */
];


/* ---------- Ende der Konfiguration ------------ */


// CSV mit Registerliste (kommt von SMA) parsen
$registers = [];
$values = file_get_contents(dirname(__FILE__) . "/values.csv");
$values = explode("\n", $values);
foreach ($values as $value)
{
    $value = explode(";", $value);
    foreach ($value as $key => $value1)
    {
        $value[$key] = trim($value1);
    }
    if ($value[0] != "")
    {
        $registers[$value[0]] = $value;
    }
}

$data = [];

/* 
  [30781]=>
  array(6) {
    [0]=>
    string(5) "30781"
    [1]=>
    string(8) "Power L3"
    [2]=>
    string(1) "W"
    [3]=>
    string(3) "S32"
    [4]=>
    string(4) "FIX0"
    [5]=>
    string(3) "RO"
  }
*/

foreach ($registerToRead as $register)
{
    $register = $registers[$register];
    try
    {
        $registerSize = 2;
        $registerAddress = (int) $register[0];

        $recData = $modbus->readMultipleRegisters($unitID, $registerAddress, $registerSize);
        $bytes = array_chunk($recData, 4)[0];

        if (checkIfValid($bytes))
        {
             // Daten aus Bytes in Integer umwandeln, dabei Vorzeichen beachten
            if ($register[3] == "S32")
            {
                // Signed value
                $value = PhpType::bytes2signedInt($bytes, false);
            }
            if ($register[3] == "U32")
            {
                // Unsigned value
                $value = PhpType::bytes2unsignedInt($bytes, false);
            }

            // Wenn Fixkomma-Zahlen, entsprechend das Komma setzen
            if ($register[4] == "FIX1")
                $value /= (float) 10;
            if ($register[4] == "FIX2")
                $value /= (float) 100; 
            if ($register[4] == "FIX3")
                $value /= (float) 1000;

        }
        else
        {
            $value = false; 
        }

       
        $data[$registerAddress] = [
            "name" => $register[1],
            "value" => $value,
            "unit" => $register[2],
        ];
    }
    catch (Exception $e)
    {
        echo $modbus;
        echo $e;
    }

}

function checkIfValid($data)
{
    if ($data[0] == 128 &&
        $data[1] == 0   &&
        $data[2] == 0   &&
        $data[3] == 0      )
        return false; 

    if ($data[0] == 255 &&
        $data[1] == 255 &&
        $data[2] == 255 &&
        $data[3] == 255    )
        return false; 

    return true; 
}


var_dump($data);