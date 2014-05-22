<?php

/**
* Class AbstractService
*
* @author Jhon Mike Soares <https://github.com/jhonmike>
* @version 1.0
*
* Dependencia Imagine (https://github.com/avalanche123/Imagine.git)
*/

namespace Zf2Base\Upload;

use Zend\File\Transfer\Adapter;
use Imagine\Gd\Imagine;
use Imagine\Image\Box as Box;
use Imagine\Image\ImageInterface;

class AbstractUpload
{
    protected $files;
    protected $destination;
    protected $options;

    public function __construct(array $files)
    {
        $this->files = (count($files) > 0) ? $files : '';
    }

    public function loadArquivos()
    {
        /* Verifica se o diretório já existe, caso não exista ele é criado e alterado sua permissão  */
        $directory = $this->options['directory'];
        if(!is_dir($directory)) {
            umask(0);
            mkdir($directory, 0777, true);
            chmod($directory, 0777);
        }

        /* Verifica se o diretório temp já existe, caso não exista ele é criado e alterado sua permissão  */
        $temp_directory = $this->setDestination($this->options['temp_directory']);
        if(!is_dir($temp_directory)) {
            umask(0);
            mkdir($temp_directory, 0777, true);
            chmod($temp_directory, 0777);
        }
        return $this->upload();
    }

    protected function upload()
    {
        $result = array();
        if ($this->options['type'] == 'multiple') {
            foreach ($this->files as $key => $files)
            {
                $result[] = $this->save($files);
            }
        } else {
            return $this->save($this->files);
        }
        return $result;
    }

    protected function save($files)
    {
        try{
            if (move_uploaded_file($files['tmp_name'], $this->getDestination($files['name'])))
            {
                if(!$this->options['name'])
                    $name = $this->autoRename();
                else
                    $name = $this->options['name'];

                $ext = end(explode('.', $files['name']));
                $mime_type = explode("/", $files['type']);

                if($mime_type[0]=="image"){

                    $tamanhoImagem = getimagesize($this->getDestination($files['name']));

                    $largura_img = $tamanhoImagem[0];
                    $altura_img = $tamanhoImagem[1];

                    $imagine = new Imagine();

                    $image = $imagine->open($this->getDestination($files['name']));
                    if($largura_img > 1920) /* Imagem normal */
                    {
                        $altura_img_nova = floor( $altura_img * ( 1920 / $largura_img));
                        $image->resize(new Box(1920, $altura_img_nova));
                    }
                    $image->save($this->options['directory'] . $name . "." . $ext);

                    $this->options['ext'] = $ext;
                    $this->thumbnail($name);
                } else {
                    copy($this->getDestination($files['name']), $this->options['directory'] . $name . "." . $ext);
                }
                unlink($this->getDestination($files['name']));
                return $name . "." . $ext;
            }
        } catch(Exception $e) {
            $flashmessenger = Zend_Controller_Action_HelperBroker::getStaticHelper ('FlashMessenger');
            $flashmessenger->addMessage('Ocorreu um erro ao enviar a(s) imagem(ns)!');
            return false;
        }
    }

    protected function setDestination($destination)
    {
        $this->destination = $destination;
    }

    protected function getDestination($file = null)
    {
        return $this->destination.$file;
    }

    protected function thumbnail($name)
    {
        $imagine = new Imagine();
        foreach ($this->options['thumb'] as $key => $value) {
            $namethumb = $value['name'].$name.".".$this->options['ext'];
            $size = new Box($value['options']['widht'], $value['options']['height']);
            $mode = ImageInterface::THUMBNAIL_INSET;
            $imagine->open($this->options['directory'] . $name . "." . $this->options['ext'])
                    ->thumbnail($size, $mode)
                    ->save($this->options['directory'] . $namethumb);
        }
    }

    public function autoRename()
    {
        return md5(uniqid(rand(), true));
    }

    public function addOptions(array $options = array())
    {
        $this->options = $options;
    }
}
