<?php

namespace App\Helpers;

use Dingo\Api\Exception\StoreResourceFailedException;
use Upload\File;
use Upload\Storage\FileSystem;
use Upload\Validation\Extension;
use Upload\Validation\Mimetype;
use Upload\Validation\Size;

class Uploader
{
    protected $maxFileSize = '10M';
    protected $mimeType    = ['image/jpeg', 'image/gif', 'image/png'];
    protected $extension   = ['jpg', 'jpeg', 'png', 'gif'];
    protected $uploadKey   = 'file';
    protected $savePath;

    /**
     * @var array 文件信息
     */
    protected $fileInfo;

    /**
     * @var File
     */
    protected $file;

    /**
     * Uploader constructor.
     *
     * @param string $savePath 保存的路径
     * @param array  $params   配置参数
     */
    public function __construct($savePath, array $params = [])
    {
        $this->savePath = $savePath;

        foreach ($params as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }

    }

    /**
     * @param array $mimeType
     *
     * @return $this
     */
    public function setMimeType(array $mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @param $maxFileSize
     *
     * @return $this
     */
    public function setMaxFileSize($maxFileSize)
    {
        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    /**
     * @param array $extension
     *
     * @return $this
     */
    public function setExtension(array $extension)
    {
        $this->extension = $extension;

        return $this;
    }


    /**
     * 新增支持的MIMETYPE
     *
     * @param string $mimeType
     *
     * @return $this
     */
    public function appendMimeType($mimeType)
    {
        $this->mimeType[] = $mimeType;

        return $this;
    }

    /**
     * 获取文件保存目录
     *
     * @return string
     */
    public function getSavePath()
    {
        return rtrim($this->savePath, '/');
    }

    /**
     * 上传准备
     *
     * @param string $uploadKey
     *
     * @return $this
     */
    public function prepare($uploadKey = 'file')
    {
        $this->uploadKey = $uploadKey;

        $storage = new FileSystem($this->getSavePath());

        $this->file       = new File($this->uploadKey, $storage);
        $originalFilename = $this->file->getNameWithExtension();

        $directory = date('/Y/m/d/');
        if (!file_exists($this->getSavePath() . $directory)) {
            mkdir($this->getSavePath() . $directory, 0777, true);
        }

        $newFilename = $directory . uniqid();
        $this->file->setName($newFilename);

        $this->file->addValidations(new Size($this->maxFileSize));
        if (!empty($this->mimeType)) {
            $this->file->addValidations(new Mimetype($this->mimeType));
        }

        if (!empty($this->extension)) {
            $this->file->addValidations(new Extension($this->extension));
        }

        $this->fileInfo = [
            'original'   => $originalFilename,
            'name'       => $this->file->getNameWithExtension(),
            'extension'  => $this->file->getExtension(),
            'mime'       => $this->file->getMimetype(),
            'size'       => $this->file->getSize(),
            'hash'       => $this->file->getMd5(),
            'dimensions' => ['width' => null, 'height' => null]
        ];

        if (in_array($this->fileInfo['extension'], ['jpg', 'jpeg', 'png', 'bmp', 'gif'])) {
            try {
                $this->fileInfo['dimensions'] = $this->file->getDimensions();
            } catch(\Exception $e) {}
        }

        return $this;
    }


    /**
     * 保存文件
     *
     * @return $this
     */
    public function save()
    {

        if (empty($this->file)) {
            throw new StoreResourceFailedException('没有上传文件');
        }

        try {
            $this->file->upload();
        } catch (\Exception $e) {
            $errors = $this->file->getErrors();
            throw new StoreResourceFailedException('上传失败', $errors, $e);
        }

        return $this;
    }

    /**
     * 获取文件信息
     *
     * @return array
     */
    public function getFileInfo()
    {
        return $this->fileInfo;
    }

    /**
     * 验证文件是否合法
     *
     * @return bool
     */
    public function validate()
    {
        return $this->file->validate();
    }

    /**
     * 获取上传错误信息
     *
     * @return array
     */
    public function getErrors()
    {
        $errors = $this->file->getErrors();

        foreach ($errors as $index => $error) {
            switch ($error) {
                case 'File already exists':
                    $errors[$index] = "文件已经存在";
                    break;
                case 'Directory does not exist':
                    $errors[$index] = '目录不存在';
                    break;
                case 'Directory is not writable':
                    $errors[$index] = '目录不可写';
                    break;
                case 'Invalid file size':
                    $errors[$index] = '文件大小不可用';
                    break;
                case 'File size is too small':
                    $errors[$index] = '文件太小,不允许上传';
                    break;
                case 'File size is too large':
                    $errors[$index] = '上传文件太大,无法上传';
                    break;
                case 'Invalid mimetype':
                    $errors[$index] = '文件媒体类型不合法';
                    break;
                default:
                    if (starts_with($error, 'Invalid file extension. Must be one of:')) {
                        $errors[$index] = '文件扩展名不合法,只允许 ' . substr($error,
                                strlen('Invalid file extension. Must be one of:'));
                    }
            }
        }

        return $errors;
    }

    /**
     * 获取文件哈希值
     *
     * @return string
     */
    public function getFileHash()
    {
        return $this->fileInfo['hash'];
    }

}