@echo off

rem Copy all files in %SRCPATH% to %DSTPATH%

set SCRIPTPATH="%~dp0"
set SRCPATH="%SCRIPTPATH%..\clients\mod\assignment\type\onlinejudge"
set DSTPATH="%SCRIPTPATH%..\..\..\mod\assignment\type\onlinejudge" 

if exist %DSTPATH% rmdir /S /Q %DSTPATH%
mkdir %DSTPATH%
xcopy /S /E /Q %SRCPATH%\* %DSTPATH%
