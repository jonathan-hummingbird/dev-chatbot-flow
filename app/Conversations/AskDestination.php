<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\Log;

class AskDestination extends Conversation
{
    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $message = $this->bot->getMessage();
        $reply = $message->getExtras()['apiReply'];
        $parameters = $message->getExtras()['apiParameters'];
        Log::alert("Destination parameters");
        Log::alert($parameters);
        $this->bot->reply($reply);
        $this->bot->reply("Your distance is 5km");
        if (checkIfLastInFlow()) {
            clearConversationState();
            $this->bot->startConversation(new LoopLandingConversation());
        }
        return;
    }
}
