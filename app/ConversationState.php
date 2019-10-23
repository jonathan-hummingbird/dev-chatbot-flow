<?php


namespace App;

use Spatie\Valuestore\Valuestore;

class ConversationState
{
    private $valueStore = null;
    const defaultKey = "state";
    public function __construct()
    {
        $this->valueStore = Valuestore::make(storage_path('app/conversation_state.json'));
        if (!$this->valueStore->has(self::defaultKey)) {
            //initialise state
            $this->valueStore->put(self::defaultKey, []);
        }
    }

    public function clear() {
        $this->valueStore->put(self::defaultKey, []);
    }

    public function update($update) {
        $new = array_merge($this->valueStore->get(self::defaultKey), [$update]);
        $this->valueStore->put(self::defaultKey, $new);
    }

    public function updateMultiple($updateArray) {
        $new = array_merge($this->valueStore->get(self::defaultKey), $updateArray);
        $this->valueStore->put(self::defaultKey, $new);
    }

    public function get() {
        return $this->valueStore->get(self::defaultKey);
    }

}