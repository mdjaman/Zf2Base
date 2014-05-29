<?php

/**
* Class Mail
*
* @author Jhon Mike Soares <https://github.com/jhonmike>
* @version 1.0
*/

namespace Zf2Base\Mail;

use Zend\Mail\Transport\Smtp as SmtpTransport,
    Zend\Mail\Message,
    Zend\View\Model\ViewModel,
    Zend\Mime\Message as MimeMessage,
    Zend\Mime\Part as MimePart,
    Zend\Mime\Mime;

class Mail
{
    protected $transport;
    protected $view;
    protected $body;
    protected $message;
    protected $subject;
    protected $to;
    protected $data;
    protected $page;

    public function __construct(SmtpTransport $transport, $view, $page)
    {
        $this->transport = $transport;
        $this->view = $view;
        $this->page = $page;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function renderView($page, array $data)
    {
        $model = new ViewModel;
        $model->setTemplate("mailer/{$page}.phtml")
            ->setOption('has_parent',true)
            ->setVariables($data);

        return $this->view->render($model);
    }

    public function prepare()
    {
        $html = new MimePart($this->renderView($this->page, $this->data));
        $html->type = "text/html";

        $body = new MimeMessage();
        $this->body = $body;

        $config = $this->transport->getOptions()->toArray();

        $this->message = new Message;
        $this->message->addFrom($config['connection_config']['from'])
            ->addTo($this->to)
            ->setSubject($this->subject)
            ->setBody($this->body);

        return $this;
    }

    public function send()
    {
        $this->transport->send($this->message);
    }
}
