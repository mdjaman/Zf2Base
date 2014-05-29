<?php
/**
* @author Jhon Mike Soares <https://github.com/jhonmike>
*/

namespace Zf2Base;

use Zend\Mvc\MvcEvent,
	Zend\ModuleManager\ModuleManager,
	Zend\Mail\Transport\Smtp as SmtpTransport,
	Zend\Mail\Transport\SmtpOptions;

class Module
{
	public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Zf2Base\Mail\Transport' => function($sm) {
                    $config = $sm->get('Config');

                    $transport = new SmtpTransport;
                    $options = new SmtpOptions($config['mail']);
                    $transport->setOptions($options);

                    return $transport;
                }
            )
        );
    }
}
