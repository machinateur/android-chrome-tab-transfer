@echo off

rem "Assume php, adb and ios-webkit-debug-proxy are already in place and `tab-transfer.cmd` is prepared."
copy "%cd%\..\tab-transfer.phar" "%cd%\..\tools\tab-transfer.phar"
copy "%cd%\..\LICENSE"           "%cd%\..\tools\LICENSE"

rem "Assume pandoc to be installed on the system."
rem "TODO: Fix image import issue (ideally remove the image in the conversion)."
pandoc -s "%cd%\..\README.md" -o "%cd%\..\tools\README.rtf"
pandoc -s "%cd%\CREDITS.md" -o "%cd%\..\tools\CREDITS.rtf"

rem "Compile `tab-transfer.iss` setup script. You have to modify it from the `.dist` for your system."
iscc "%cd%\tab-transfer.iss"
