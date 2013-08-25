cd C:\xampp\htdocs\thesis\psgr_dbpedia
set backuptime=%date:~-4,4%%date:~-10,2%%date:~-7,2%%time:~0,2%%time:~3,2%%time:~6,2%
set mylogfile="log\output_%backuptime%.html"
C:\xampp\php\php.exe -f controller.php 1 >> %mylogfile%
exit