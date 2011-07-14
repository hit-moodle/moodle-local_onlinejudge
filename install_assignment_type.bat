@echo off

rem Copy all files in %SRCPATH% to %DSTPATH%

set SRCPATH="clients\mod\assignment\type\onlinejudge"
set DSTPATH="..\..\mod\assignment\type\onlinejudge"

if exist %DSTPATH% rmdir /S /Q %DSTPATH%
mkdir %DSTPATH%
xcopy /S /E /Q %SRCPATH%\* %DSTPATH%
