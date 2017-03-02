# SMAPHPConnect
fetches data from SMA solar inverters via ModBus

Known issues
----------
SMAPHPConnect can't read 64bit values. This would require automatic detection of the $registerSize and (preferrably) a better version of the PhpType library found in phpmodbus. 



Requires patched version of https://github.com/Manawyrm/phpmodbus
SMA uses a different endianess as usual. Also the recv()-Routine needed to be modified.
