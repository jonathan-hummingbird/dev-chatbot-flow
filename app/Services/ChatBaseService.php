<?php
namespace App\Services;
use Richdynamix\Chatbase\Facades\Chatbase;


class ChatBaseService
{
    public const FORWARD_TYPE_NOT_HANDLED_MESSAGE = 0;
    public const FORWARD_TYPE_USER_MESSAGE  = 1;
    public const FORWARD_TYPE_BOT_MESSAGE = 2;
    private $userId;
    private $message;
    private $intent;
    private $forwardType;
    public function __construct($forwardType, $userId, $message, $intent)
    {
        $this->forwardType = $forwardType;
        $this->message = $message;
        $this->userId = $userId;
        $this->intent = $intent;
    }
    public function forwardMessage()
    {
        switch($this->forwardType) {
            case self::FORWARD_TYPE_NOT_HANDLED_MESSAGE:
                Chatbase::notHandledUserMessage()
                    ->with([
                        'user_id' => $this->userId,
                        'message' => $this->message,
                        'intent' => $this->intent,
                    ])
                    ->send();
                break;
            case self::FORWARD_TYPE_USER_MESSAGE:
                Chatbase::userMessage()
                    ->with([
                        'user_id' => $this->userId,
                        'message' => $this->message,
                        'intent' => $this->intent,
                    ])
                    ->send();
                break;
            case self::FORWARD_TYPE_BOT_MESSAGE:
                Chatbase::botMessage()
                    ->with([
                        'user_id' => $this->userId,
                        'message' => $this->message,
                        'intent' => $this->intent,
                    ])
                    ->send();
                break;
        }
    }
}