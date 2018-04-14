<?php
/**
 * simpleMail class
 *
 */
class Email {

    const TYPE_TO_ADMIN = 0;
    const TYPE_TO_USER = 1;

    private $mail_post = 'hello@iq-engineers.com';
    private $sitename_short = 'iq-engineers';
    private $mail_sender = 'no-reply@iq-engineers.com'; // noreply
    private $mail_developer = 'iq-engineers@iq-engineers.com';

    private $_subject;
    private $subject;
    private $body;
    private $filename;
    private $extension;

    private $type; // to admin, to user

    private $headers;
    private $recipient;

    private $sender;
    private $attache;
    private $un;
    private $result;

    /**
     * Отослать письмо.
     * В опциях нужно передать следующее
     * * subject
     * * body
     * * recipient
     * * sender = array (name, email)
     *
     * $sentRightNow = отослать сразу
     *
     * @param array $options
     * @param int $type
     * @param bool $sentRightNow
     */
    public function __construct($options, $type = self::TYPE_TO_ADMIN, $sentRightNow = true) {
        $this->type = $type;
        $this->newLetter($options, $sentRightNow);
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function setRecipient($email, $name = null) {
        if(!strpos($email, ',')) {
            $recipient = ($name ? $this->encode($name).' ' : null) . '<'.$email.'>';
        } else {
            $recipient = $email;
        }

        $this->recipient = $recipient;
    }

    public function setSubject($subject) {
        $this->subject = $this->encode($subject);
        $this->_subject = $subject;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function getResult() {
        return $this->result;
    }

    public function setSender($email, $name = null) {
        $this->from = ($name ? $this->encode($name).' ' : null) . '<'.$email.'>';
    }

    /**
     * Отослать новое письмо
     * В опциях нужно передать следующее
     * * subject
     * * body
     * * recipient
     * * sender = array (name, email)
     *
     * @param unknown_type $options
     * @param unknown_type $sentRightNow
     */
    public  function newLetter($options, $sentRightNow = true) {
        if($options['subject']) {
            $this->setSubject($options['subject']);
        } else {
            $this->setSubject('Письмо с формы обратной связи');
        }

        if($options['body']) {
            $this->setBody($options['body']);
        }

        if($options['recipient']) {
            if(!is_array($options['recipient'])) {
                $options['recipient'] = array('email' => $options['recipient']);
            }
            $this->setRecipient($options['recipient']['email'], $options['recipient']['name']);
        }

        if($options['sender']) {
            $this->setSender($options['sender']['email'], $options['sender']['name']);
        }
        if($options['file']) {
            $this->filename = $options['file']['name'];
            $this->extension = $options['file']['extension'];
        }

        $this->initData();

        if($sentRightNow) {
            $this->send();
        }
    }

    private function initData() {
        if($this->type == self::TYPE_TO_ADMIN ) {
            if(!$this->recipient) {
                $this->setRecipient($this->mail_post);
            }

            if(!$this->from) {
                $this->setSender($this->mail_developer, $this->sitename_short);
            }
        } else if($this->type == self::TYPE_TO_USER) {
            if (!$this->from) {
                $this->setSender($this->mail_sender, $this->sitename_short);
            }
        }

        $this->buildBody();
    }

    public  function send() {
        $blocking = '';//OptionsUtils::get('mail_blocking');
        if($blocking) {
            return false;
        }

        $redirect = '';//OptionsUtils::get('mail_redirect');
        if($redirect) {
            $this->setSubject($this->_subject . ' [redirected]');
            $this->body .= '<br><br><br>Original recipient: '.$this->recipient;
            $this->setRecipient(OptionsUtils::get('mail_redirect_address'));
        }

        $this->buildHeaders();
        $this->body = stripslashes($this->body);

        $this->addAttachment($this->filename, $this->extension);
        $this -> getAttache();


        $result = @mail($this->recipient, $this->subject, $this->body, $this->headers);
        $this->result = $result;
    }


    private function getAttache() {
        if ($this -> attache) {
            $this -> body = $this -> attache['body'];
            $this -> headers = $this -> attache['headers'];
        }
    }

    public function addAttachment($filename, $extension) {
        if (!file_exists($filename)) {
            return false;
        }
        $fp=fopen($filename,"r");
        if (!$fp) {
            return false;
        }

        $f  = fopen($filename,"rb");
        $file = fread($fp, filesize($filename));
        fclose($fp);

        $name = basename($filename); // в этой переменной надо сформировать имя файла (без всякого пути)
        if ($extension) {
            $name .= ".".$extension;
        }

        if (!$this -> attache['body']) {
            $text = $this -> body;
            $un   = strtoupper(uniqid(time()));
            $this -> un = $un;
            $head = "From: $this->from\n";
            $head .= "To: $this->recipient\n";
            $head .= "X-Mailer: MailSystem\n";
            $head .= "Reply-To: $this->from\n";
            $head .= "Mime-Version: 1.0\n";
            $head .= "Content-Type:multipart/mixed;";
            $head .= "boundary=\"----------".$un."\"\n\n";
            $zag  = "------------".$un."\nContent-Type:text/html;\n";
            $zag  .= "Content-Transfer-Encoding: 8bit\n\n$text\n\n";
            $zag  .= "------------".$un."\n";
            $zag  .= "Content-Type: application/octet-stream;";
            $zag  .= "name=\"".$name."\"\n";
            $zag  .= "Content-Transfer-Encoding:base64\n";
            $zag  .= "Content-Disposition:attachment;";
            $zag  .= "filename=\"".$name."\"\n\n";
            $zag  .= chunk_split(base64_encode($file))."\n";

            $this -> attache = array('headers' => $head,
                'body' => $zag);
        } else {
            $un = $this -> un;
            $zag = "------------".$un."\n";
            $zag .= "Content-Type: application/octet-stream;";
            $zag .= "name=\"".$name."\"\n";
            $zag .= "Content-Transfer-Encoding:base64\n";
            $zag .= "Content-Disposition:attachment;";
            $zag .= "filename=\"".$name."\"\n\n";
            $zag .= chunk_split(base64_encode($file))."\n";
            $this -> attache['body'] .= $zag;
        }

    }

    private function buildHeaders() {
        $header  = "From: $this->from\r\n";
        $header .= "Reply-To: $this->from\r\n";

        $header .= "Content-Type: text/html; charset=utf-8\r\n";
        $header .= "X-Mailer: ArtWeb MailSystem\r\n";

        $this->headers = $header;
    }

    private function buildBody() {
        $body = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';

        $body .= stripslashes($this->body);

        if($this->type == 'to admin') {
            $body .= $this->getMessageInfo();
        }

        $body .= '</body></html>';

        $this->body = $body;
    }

    private function getMessageInfo() {
        $info = '<br><br><p>';
        $info .= 'Дата отправки сообщения: ' .date('d.m.Y, H:i:s').'<br>';
        //$info .= 'UserIP: ' . $_SERVER['REMOTE_ADDR'];
        $info .= '</p>';

        return $info;
    }

    public static function replaceMarkers($text, $markers) {
        if (is_array($markers)) {
            foreach ($markers as $key => $value ) {
                if(is_array($value)) {
                    continue;
                }

                $pattern = '/%'.$key.'([^a-zA-Z_]|$)/i';
                $text = preg_replace($pattern, $value.'$1', $text);
            }
        }

        $text = preg_replace('/\s?onclick="rightFrame\.open\(\'.*?\'\);return false;"/i', null, $text);

        return $text;
    }



    private function encode($str) {
        return $str;
        return '=?windows-1251?b?'.base64_encode($str).'?=';
    }
}
?>