@echo off

rem Move all files in %SRCPATH% to %DSTPATH%

set SCRIPTPATH="%~dp0"
set SRCPATH="%SCRIPTPATH%..\clients\mod\assign\feedback\onlinejudge"
set DSTPATH="%SCRIPTPATH%..\..\..\mod\assign\feedback\onlinejudge"

if exist %DSTPATH% rmdir /S /Q %DSTPATH%
mkdir %DSTPATH%
robocopy /S /E /Q %SRCPATH%\* %DSTPATH% /MOVE
