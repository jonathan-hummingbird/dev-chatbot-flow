<?php

use App\Conversations\LandingConversation;
use App\Http\Controllers\BotManController;
use BotMan\BotMan\Middleware\ApiAi;
use BotMan\BotMan\BotMan;

$botman = resolve('botman');
$dialogFlow = ApiAi::create(env("DIALOG_FLOW_API_TOKEN", ""))->listenForAction();

// Apply global "received" middleware
$botman->middleware->received($dialogFlow);


//$botman->hears('Hi', function ($bot) {
//    $bot->reply('Hello!');
//});
$botman->hears('Start conversation', BotManController::class.'@startConversation');

$botman->hears('greetings', function(BotMan $bot) {

//    Log::info($apiContext);
//    Log::info($extras);
//    $bot->reply($apiReply);
//    $bot->ask($apiReply, function (Answer $answer) {
//        //Get user reply
//        $this->bot->reply("Got ur name " . $answer->getText());
//    });
    $bot->startConversation(new LandingConversation());
})->middleware($dialogFlow);

//$botman->hears('ask_name', function(BotMan $bot) {
//    $next = $bot->getMessage();
//    $extras = $next->getExtras();
//    $apiReply = $extras['apiReply'];
//    $bot->reply($apiReply);
//    Log::info("Logging the next message");
//    Log::info($next);
//})->middleware($dialogFlow);
