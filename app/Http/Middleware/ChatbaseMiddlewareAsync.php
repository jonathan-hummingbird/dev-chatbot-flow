<?php

namespace App\Http\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Captured;
use BotMan\BotMan\Interfaces\Middleware\Heard;
use BotMan\BotMan\Interfaces\Middleware\Matching;
use BotMan\BotMan\Interfaces\Middleware\Received;
use BotMan\BotMan\Interfaces\Middleware\Sending;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use App\Services\ChatbaseService;
use App\Jobs\SendChatbaseMessage;
use Exception;

/**
 * Send messages to chatbase
 *
 * Class ChatbaseMiddleware
 *
 * @package App\Http\Middleware
 */
class ChatbaseMiddlewareAsync implements Received, Captured, Matching, Heard, Sending
{
    /**
     * Handle an incoming message.
     *
     * @param IncomingMessage $message
     * @param callable        $next
     * @param BotMan          $bot
     *
     * @return mixed
     */
    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        if ($message->getText() && $message->getSender() && !$this->ignoreMessages($message)) {
            $extras = $message->getExtras();
            $intent = $extras['apiIntent'];
            $action = $extras['apiAction'];
            $apiAction = $extras['apiAction'];
            $forwardType = is_incorrect_intent($apiAction)
                ? ChatbaseService::FORWARD_TYPE_NOT_HANDLED_MESSAGE
                : ChatbaseService::FORWARD_TYPE_USER_MESSAGE;
            $isOnboarding = false;
            // \Log::info('ChatbaseMiddlewareAsync ==>>  $intent: '.$intent.' $action: '.$action);
            SendChatbaseMessage::dispatch(new ChatbaseService( $forwardType,
                $message->getSender(), $message->getText(), $intent))->onQueue('chatbase');
            // try {
            //     $customer = Customer::where('facebook_id', '=', $message->getSender())->first();
            //     if ($customer) {
            //         if ($customer->conversationPattern->current_block_id === 41) {
            //             $isOnboarding = true;
            //         }
            //     }
            // } catch(Exception $ex) {
            //     \Log::info('Exception : '.$ex->getMessage());
            // }

            // if (!$isOnboarding || $action == 'hood_faq') {
            //     SendChatbaseMessage::dispatch(new ChatbaseService(ChatbaseService::FORWARD_TYPE_USER_MESSAGE,
            //     $message->getSender(), $message->getText(), $intent));
            // }
        }
        return $next($message);
    }

    /**
     * Handle a captured message.
     *
     * @param IncomingMessage $message
     * @param callable        $next
     * @param BotMan          $bot
     *
     * @return mixed
     */
    public function captured(IncomingMessage $message, $next, BotMan $bot)
    {
        return $next($message);
    }

    /**
     * @param IncomingMessage $message
     * @param string          $pattern
     * @param bool            $regexMatched Indicator if the regular expression was matched too
     *
     * @return bool
     */
    public function matching(IncomingMessage $message, $pattern, $regexMatched)
    {
        return true;
    }

    /**
     * Handle a message that was successfully heard, but not processed yet.
     *
     * @param IncomingMessage $message
     * @param callable        $next
     * @param BotMan          $bot
     *
     * @return mixed
     */
    public function heard(IncomingMessage $message, $next, BotMan $bot)
    {
        return $next($message);
    }

    /**
     * Handle an outgoing message payload before/after it
     * hits the message service.
     *
     * @param mixed    $payload
     * @param callable $next
     * @param BotMan   $bot
     *
     * @return mixed
     */
    public function sending($payload, $next, BotMan $bot)
    {
        if (app()->environment('production')) {
            if (($recipient = data_get($payload, 'recipient.id')) && !$this->ignoreMessages($bot->getMessage())) {
                if ($text = data_get($payload, 'message.text')) {
                    $intent = '';
                    SendChatbaseMessage::dispatch(new ChatBaseService(ChatBaseService::FORWARD_TYPE_BOT_MESSAGE,
                        $recipient, $text, $intent))->onQueue('chatbase');
                }
            }
        }
        return $next($payload);
    }

    /**
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
     *
     * @return bool
     */
    private function ignoreMessages(IncomingMessage $message)
    {
        return \in_array($message->getText(), ['reset', 'stop'], true);
    }
}
