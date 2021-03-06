<?php

/*
|--------------------------------------------------------------------------
| Register The Artisan Commands
|--------------------------------------------------------------------------
|
| Each available Artisan command must be registered with the console so
| that it is available to be called. We'll register every command so
| the console gets access to each of the command object instances.
|
*/

Artisan::add(new ConvertBridgeToCGCSL);
Artisan::add(new ConvertBridgeToFPT);
Artisan::add(new ConvertBridgeToFC);
Artisan::add(new ConvertSAPToBridge);
Artisan::add(new LoadWbs);
Artisan::add(new LoadMm);
