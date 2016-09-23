<?php

namespace Anax\FlashMessages;

/**
 * Store messages for flashing them to the user as user feedback.
 *
 */
class CFlashMessages
{
    use \Anax\DI\TInjectable;

    /**
     * Properties
     *
     */
    protected $message;

   /**
     * Set a message.
     *
     * @param string $message a message.
     *
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

   /**
     * Set a session message.
     *
     * @param string $info containing type of message.
     * @param string $message containing message to be stored.
     *
     * @return void
     */
    public function setSessionMessage($info, $message)
    {
        if ($this->session->has('flash')) {
            $messages = $this->session->get('flash');
            $messages[] = ['type' => $info, 'message' => htmlspecialchars($message)];
            $this->session->set('flash', $messages);
        } else {
            $this->session->set('flash', [['type' => $info, 'message' => htmlspecialchars($message)]]);
        }
    }

   /**
     * Forward error message to setSessionMessage.
     *
     * @param string $message containing message to be stored.
     *
     * @return void
     */
    public function errorMessage($message)
    {
        $this->setSessionMessage("error", $message);
    }

   /**
     * Forward success message to setSessionMessage.
     *
     * @param string $message containing message to be stored.
     *
     * @return void
     */
    public function successMessage($message)
    {
        $this->setSessionMessage("success", $message);
    }

   /**
     * Forward notice message to setSessionMessage.
     *
     * @param string $message containing message to be stored.
     *
     * @return void
     */
    public function noticeMessage($message)
    {
        $this->setSessionMessage("notice", $message);
    }

   /**
     * Forward warning message to setSessionMessage.
     *
     * @param string $message containing message to be stored.
     *
     * @return void
     */
    public function warningMessage($message)
    {
        $this->setSessionMessage("warning", $message);
    }

   /**
     * Clears all messages stored in the session.
     *
     * @return void
     */
    public function clearMessage()
    {
        $this->session->set('flash', []);
    }

    public function hasMessage()
    {
        return $this->session->has('flash');
    }

   /**
     * Get the message.
     *
     * @return void
     *
     */
    public function getMessage()
    {
        return $this->message;
    }

   /**
     * Get the session messages.
     *
     * @return html block with output from session messages.
     *
     */
    public function getSessionMessage()
    {
        $output = "<div class='flashmessages'>";
        if ($this->session->has('flash')) {
            $messages = $this->session->get('flash');
            $this->session->set('flash', []);
            foreach ($messages as $index => $value) {
                $type = $value['type'];
                $message = $value['message'];
                $output .= "<p class='{$type}'>";
                $output .= '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ';
                $output .= $message;
                $output .= "</p>";
            }
        }
        $output .= "</div>";
        return $output;
    }

   /**
     * Get the session messages as raw array.
     *
     * @return array with messages.
     *
     */
    public function getSessionMessageClean()
    {
        $messages = $this->session->get('flash');
        $this->session->set('flash', []);
        return $messages;
    }
}
