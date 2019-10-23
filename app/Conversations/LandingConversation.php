<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Support\Facades\Log;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;

class LandingConversation extends Conversation
{
    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $extras = $this->bot->getMessage()->getExtras();
        $apiReply = $extras['apiReply'];
        $apiContext = $extras['apiContexts'];
        Log::alert($apiContext);
        $question = Question::create($apiReply)->callbackId("which_action")
            ->addButtons([
                Button::create("Get moving distance")->value("distance"),
                Button::create("Subscribe to newsletter")->value("subscribe"),
                Button::create("Get movers in a location")->value("movers"),
            ]);
        $this->bot->ask($question, function (Answer $answer) {
//            $this->bot->reply("Received " . $answer->getText());
            //Get user reply
            $nextAction = $this->bot->getMessage()->getExtras()['apiAction'];
            startNextConversation($this->bot, $nextAction);
        });
    }
}
