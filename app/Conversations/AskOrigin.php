<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Support\Facades\Log;

class AskOrigin extends Conversation
{
    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $message = $this->bot->getMessage();
        $extras = $message->getExtras();
        $reply = $message->getExtras()['apiReply'];
        $parameters = $message->getExtras()['apiParameters'];
        Log::alert("Origin parameters");
        Log::alert($parameters);
        Log::alert("Extra parameters ");
        Log::alert($extras);
        $this->bot->userStorage()->save(['origin' => $parameters]);
        $this->bot->ask($reply, function () {
            $nextAction = $this->bot->getMessage()->getExtras()['apiAction'];
            Log::alert($nextAction);
            startNextConversation($this->bot, $nextAction);
        });
        return;
    }
}
