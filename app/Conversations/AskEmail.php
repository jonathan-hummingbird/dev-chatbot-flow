<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\Log;

class AskEmail extends Conversation
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
        Log::info("EMAIL EXTRA IS");
        Log::info($message->getExtras());
        $this->bot->ask($reply, function () {
            $nextAction = $this->bot->getMessage()->getExtras()['apiAction'];
            startNextConversation($this->bot, $nextAction);
        });
        return;
    }
}
