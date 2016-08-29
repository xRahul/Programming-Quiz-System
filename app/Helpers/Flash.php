<?php

namespace QuizSystem\Helpers;

class Flash
{

    /**
     * create a flash message in session
     * @param  string $title   header of flash message
     * @param  string $message body of flash message
     * @param  string $level   level of error
     * @param  string $key     type of overlay
     * @return void
     */
    public function create($title, $message, $level, $key='flash_message')
    {
        session()->flash($key, [
            'title' => $title,
            'message' => $message,
            'level' => $level
        ]);
    }

    /**
     * return info flash message
     * @param  string $title   header of flash message
     * @param  string $message body of flash message
     * @return void
     */
    public function info($title, $message)
    {
        return $this->create($title, $message, 'info');
    }

    /**
     * return success flash message
     * @param  string $title   header of flash message
     * @param  string $message body of flash message
     * @return void
     */
    public function success($title, $message)
    {
        return $this->create($title, $message, 'success');
    }

    /**
     * return error flash message
     * @param  string $title   header of flash message
     * @param  string $message body of flash message
     * @return void
     */
    public function error($title, $message)
    {
        return $this->create($title, $message, 'error');
    }

    /**
     * return overlay flash message | persistent
     * @param  string $title   header of flash message
     * @param  string $message body of flash message
     * @param  string $level   level of error
     * @return void
     */
    public function overlay($title, $message, $level='info')
    {
        return $this->create($title, $message, $level, 'flash_message_overlay');
    }
}
