<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;

class AskBudget extends Conversation
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
        $this->bot->reply($reply);
        if (checkIfLastInFlow()) {
            clearConversationState();
            $this->bot->startConversation(new LoopLandingConversation());
        }
        return;
    }
}
