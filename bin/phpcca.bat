@echo off
REM Cognitive Code Analysis
REM
REM @author    Florian Krämer
REM @copyright 2024 Florian Krämer
REM @license   https://github.com/Phauthentic/cognitive-code-analysis/blob/master/LICENSE GPL-3.0

if "%PHP_PEAR_PHP_BIN%" neq "" (
    set PHPBIN=%PHP_PEAR_PHP_BIN%
) else set PHPBIN=php

"%PHPBIN%" "%~dp0\phpcca" %*
