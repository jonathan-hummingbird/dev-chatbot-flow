<?php

use App\Conversations\LoopLandingConversation;
use App\ConversationState;
use BotMan\BotMan\BotMan;
use Illuminate\Support\Facades\Log;

const FINISH_INTENT = "bye";

const CONVERSATION_FLOW = [
    "get_distance" => ["get_origin", "get_destination"],
    "subscribe_email" => ["get_email", "ask_consent"],
    "get_movers" => ["get_location", "get_budget"]
];

const MAP_ACTION_TO_CONVERSATION = [
    "get_distance" => null, //Root intent
    "subscribe_email" => null, //Root intent
    "get_movers" => null, //Root intent
    "get_origin" => "App\Conversations\AskOrigin",
    "get_destination" => "App\Conversations\AskDestination",
    "get_email" => "App\Conversations\AskEmail",
    "get_location" => "App\Conversations\AskLocation",
    "get_budget" => "App\Conversations\AskBudget",
    "ask_consent" => "App\Conversations\AskConsent",
];

function ensureCorrectFlow($nextIntent) {
    $state = new ConversationState();
    $rootIntent = $state->get()[0];
    $others = array_merge(array_slice($state->get(), 1), [$nextIntent]);
    $compareArray = CONVERSATION_FLOW[$rootIntent];
    foreach ($others as $other) {
        if (!in_array($other, $compareArray)) {
            //Conversation not in flow
            return false;
        }
    }
    return true;
}

function checkIfLastInFlow() {
    $state = new ConversationState();
    $rootIntent = $state->get()[0];
    $others = array_slice($state->get(), 1);
    $currentIntent = $others[count($others) - 1];
    $flow = CONVERSATION_FLOW[$rootIntent];
    return in_array($currentIntent, $flow) && array_search($currentIntent, $flow, true) === count($flow) - 1;
}

function clearConversationState() {
    $state = new ConversationState();
    $state->clear();
}

function startNextConversation(BotMan $bot, $nextIntent) {
    $state = new ConversationState();
    if ($nextIntent === FINISH_INTENT) {
        Log::alert("BYE FLOW DETECTED!");
        $reply = $bot->getMessage()->getExtras()['apiReply'];
        $bot->reply($reply);
        return;
    }
    //Handle incorrect intent
    if (!array_key_exists($nextIntent, MAP_ACTION_TO_CONVERSATION)) {
        Log::alert("INCORRECT INTENT DETECTED!");
        $bot->startConversation(new LoopLandingConversation());
        return;
    }
    if (MAP_ACTION_TO_CONVERSATION[$nextIntent] === null) {
        //If action is root intent, we need to clear intent
        $state->clear();
        $action = CONVERSATION_FLOW[$nextIntent][0];
        $state->updateMultiple([$nextIntent, $action]);
        $nextClass = MAP_ACTION_TO_CONVERSATION[$action];
        $bot->startConversation(new $nextClass());
    } else {
        //Ensure the flow is correct, otherwise just stops conversation for now
        if (ensureCorrectFlow($nextIntent)) {
            $state->update($nextIntent);
            $nextClass = MAP_ACTION_TO_CONVERSATION[$nextIntent];
            $bot->startConversation(new $nextClass());
        } else {
            Log::alert("INCORRECT FLOW DETECTED!");
            $bot->startConversation(new LoopLandingConversation());
        }
    }
    return;
}
