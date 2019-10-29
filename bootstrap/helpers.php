<?php

use App\Conversations\LoopLandingConversation;
use App\ConversationState;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Facebook\Extensions\ButtonTemplate;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ElementButton;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use BotMan\Drivers\Facebook\Extensions\MediaAttachmentElement;
use BotMan\Drivers\Facebook\Extensions\MediaTemplate;
use BotMan\Drivers\Facebook\Extensions\MediaUrlElement;
use Illuminate\Support\Facades\Log;

const FACEBOOK_TEXT = 0;
const FACEBOOK_CARD = 1;
const FACEBOOK_IMAGE = 3;
const FACEBOOK_QUICK_REPLY = 2;
const FACEBOOK_CUSTOM = 4;

const FINISH_INTENT = "bye";

const CONVERSATION_FLOW = [
    "get_distance" => ["get_origin", "get_destination", "goal" => false],
    "subscribe_email" => ["get_email", "ask_consent", "goal" => false],
    "get_movers" => ["get_location", "get_budget", "goal" => false],
    "get_information_move" =>
        [ "objectives" =>
            [
                "obj_origin" => [
                    "parameter" => "origin",
                    "class" => "App\Conversations\AskOrigin"
                ],
                "obj_destination" => [],
                "obj_moving_date" => [],
                "obj_number_of_rooms" => []
            ],
            "goal" => true
        ]
];

const MAP_ACTION_TO_CONVERSATION = [
    "get_distance" => null, //Root intent
    "subscribe_email" => null, //Root intent
    "get_movers" => null, //Root intent
    "get_information_move" => null,
    "get_origin" => "App\Conversations\AskOrigin",
    "get_destination" => "App\Conversations\AskDestination",
    "get_email" => "App\Conversations\AskEmail",
    "get_location" => "App\Conversations\AskLocation",
    "get_budget" => "App\Conversations\AskBudget",
    "ask_consent" => "App\Conversations\AskConsent",
];

const OBJECTIVE_REQUIREMENTS = [
    "obj_origin" => "origin",
    "obj_destination" => "destination",
    "obj_moving_date" => "moving_date || moving_date_period",
    "obj_number_of_rooms" => "number_of_rooms"
];

function ensureCorrectFlow($nextIntent) {
    $state = new ConversationState();
    $rootIntent = $state->get()[0];
    $others = array_merge(array_slice($state->get(), 1), [$nextIntent]);
    $compareArray = CONVERSATION_FLOW[$rootIntent];
    foreach ($others as $key => $other) {
        if (!is_string($key) && !in_array($other, $compareArray)) {
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

function is_incorrect_intent($intent) {
    return $intent !== FINISH_INTENT && !array_key_exists($intent, MAP_ACTION_TO_CONVERSATION);
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
    if (!array_key_exists($nextIntent, MAP_ACTION_TO_CONVERSATION) || !array_key_exists($nextIntent, CONVERSATION_FLOW)) {
        Log::alert("INCORRECT INTENT DETECTED!");
        $bot->startConversation(new LoopLandingConversation());
        return;
    }

    if (!CONVERSATION_FLOW[$nextIntent]["goal"]) {
        //Logic for flow based conversation
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
    } else {
        //Logic for goal based conversation
        //Initial set status to true to differentiate between flow-based conversation

        //$parameters = ['destination' => ["test" => 1], "number_of_rooms" => 1, "origin" => "", "date_time" => ""];
        // $nextIntent = "get_information_move";

        if (!$state->getStatus()) {
            $state->updateStatus(true);
        }
        //First step: check fulfilled objectives & get a list of unfulfilled objectives
        $extras = $bot->getMessage()->getExtras();
        Log::alert("INTENT EXTRAS!!!");
        Log::alert($extras);
        $parameters = $extras["apiParameters"];

        $all_objectives = array_filter(CONVERSATION_FLOW[$nextIntent], function ($key) {
            return !is_string($key);
        }, ARRAY_FILTER_USE_KEY);

        $fulfilled_objectives_raw = array_filter($parameters, function ($value, $key) use ($all_objectives) {
            $objective_name_array = array_filter(OBJECTIVE_REQUIREMENTS);
            return !!$value && !!$objective_name && in_array($objective_name, $all_objectives);
        }, ARRAY_FILTER_USE_BOTH);

        $fulfilled_objectives = array_map(function ($key) {
            return array_search($key, OBJECTIVE_REQUIREMENTS);
        }, array_keys($fulfilled_objectives_raw));

        $unfulfilled_objectives = array_diff($all_objectives, $fulfilled_objectives);

        Log::alert("UNFULFILLED OBJECTIVES!!!");
        Log::alert($unfulfilled_objectives);

        //Second step: satisfy unfulfilled objectives by going through conversation(s) mapped to the particular
        //each objective
    }

    return;
}

function validateUrl ($url) {
    return !filter_var($url, FILTER_VALIDATE_URL) === false;
}

function generateFacebookButtons($message) {
    $output = [];
    foreach($message["buttons"] as $button) {
        $button = ElementButton::create($button["text"]);
        if (validateUrl($button["postback"])) {
            //postback is a URL
            $output[] = $button->url($button["postback"]);
        } else {
            //postback is not a URL
            $output = $button->payload($button["postback"])->type('postback');
        }
    }
    return $output;
}

function generateFacebookElement($message) {
    $buttons = array_key_exists("buttons", $message)
        ? generateFacebookButtons($message)
        : [];
    $output = Element::create($message['title'])->subtitle($message['subtitle'])->image($message['imageUrl']);
    return count($buttons) > 0
        ? $output->addButtons($buttons)
        : $output;
}

function facebookMessageParser($message) {
    switch ($message['type']) {
        case FACEBOOK_TEXT:
            return $message['speech'];
            break;
        case FACEBOOK_CARD:
            return GenericTemplate::create()
                ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
                ->addElement(generateFacebookElement($message));
            break;
        case FACEBOOK_QUICK_REPLY:
            return ButtonTemplate::create($message['title'])->addButtons(
                array_map(function($item) {
                    return ElementButton::create($item)->payload($item)->type('postback');
                }, $message['replies'])
            );
            break;
        case FACEBOOK_IMAGE:
            return MediaTemplate::create()
                ->element(
                    MediaUrlElement::create('image')
                    ->url($message['imageUrl'])
                );
            break;
        case FACEBOOK_CUSTOM:
            return 'Coming soon';
            break;
        default:
            return '';
            break;
    }
}
