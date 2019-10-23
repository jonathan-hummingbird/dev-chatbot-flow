<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\Log;

class AskConsent extends Conversation
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
        Log::info("PARAMETERS ");
        Log::info($parameters);
        $this->bot->reply($reply);
        if (checkIfLastInFlow()) {
            clearConversationState();
            $this->bot->startConversation(new LoopLandingConversation());
        }
        return;
    }
}
