<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Support\Facades\Log;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;

class LoopLandingConversation extends Conversation
{
    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $question = Question::create("Is there any more I can do to assist?")->callbackId("which_action")
            ->addButtons([
                Button::create("Get moving distance")->value("distance"),
                Button::create("Subscribe to newsletter")->value("subscribe"),
                Button::create("Get movers in a location")->value("movers"),
                Button::create("Goodbye")->value("bye")
            ]);
        $this->bot->ask($question, function (Answer $answer) {
//            $this->bot->reply("Received " . $answer->getText());
            //Get user reply
            $nextAction = $this->bot->getMessage()->getExtras()['apiAction'];
            startNextConversation($this->bot, $nextAction);
        });
    }
}
