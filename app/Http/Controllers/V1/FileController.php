<?php

namespace App\Http\Controllers\V1;

use App\File;
use App\Helpers\Uploader;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Http\Request;
use Intervention\Image\Facades\Image;

class FileController extends BaseController
{
    public function upload(Request $request){
        try {
           $uploader = new Uploader(env('UPLOAD_PATH'));
           $uploader->prepare($request->input('key', 'file'));

           if (!$uploader->validate()) {
               throw new StoreResourceFailedException(implode(', ', $uploader->getErrors()));
           }

           $hash     = $uploader->getFileHash();
           $fileInfo = $uploader->getFileInfo();

           $file = File::where('hash', $hash)->first();
           if (empty($file)) {
               $uploader->save();
               $file = File::create($fileInfo
                                    + ['upload_time' => date('Y-m-d H:i:s')]);
           }

           if (empty($file)) {
               throw new StoreResourceFailedException('检索文件信息失败');
           }

           $result = $file->toArray() + [
                   'original' => $fileInfo['original'],
                   'download' => $this->getDownloadUrl($fileInfo['hash'])
               ];
       } catch (\Exception $e) {
           $result = [
               'status_code' => $e->getCode(),
               'message'     => $e->getMessage()
           ];
       }

       if ($request->input('cross')==1) {
         return $this->_crossResponse($request,$result);
       }else{
         return $result;
       }
    }

    private function _crossResponse(Request $request, array $result)
    {
        // 避免结果被任意修改
        $result = json_encode($result);

        // 用于解决跨域问题
        // 如果提供了cross字段,会跳转到该页面,该页面为调用方网站
        // 同域名下的地址,返回结果将以json的形式传递给该页面
        $cross_url = $request->input('cross_url');
        if (empty($cross_url)) {
            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
            if (empty($referer)) {
                return $result;
            }

            $endPos = strpos($referer, '/', 7);
            $cross_url = rtrim(substr($referer, 0, $endPos > 0 ? $endPos : strlen($referer)), '/') . '/ajax?data=';
        }

        return redirect($cross_url . $result);
    }

    /**
     * 获取下载地址
     *
     * @param $hash
     *
     * @return mixed
     */
    private function getDownloadUrl($hash)
    {
        return env('STORAGE_URL') . "/api/file/{$hash}";
    }

    public function download(Request $request, $hash)
    {
        $this->validate($request, [
            'size'     => 'integer|in:60,100,120,150,200,300,350,400,500,800,1000',
            'original' => 'boolean',
        ]);

        $file = File::findOrFail($hash);

        if (starts_with($file->mime, 'image/')) {
            $original = $request->input('original', true);
            // 如果请求的不是原图,则返回压缩图
            if (!$original) {
                // 不指定图片大小的情况下,默认使用thumb后缀
                $size = $request->input('size', 'thumb');

                // 原始文件地址
                $thumbPath = $this->getRealPath($file->name, $size);
                // 如果缩略图不存在,则重新生成
                if (!file_exists($thumbPath)) {
                    $originalPath = $this->getRealPath($file->name);

                    $image = Image::make($originalPath);
                    
                    $image->resize(
                        $size == 'thumb' ? 2000 : $size,
                        $size == 'thumb' ? 2000 : $size,
                        function ($constraint) {
                        // 保持原有宽高比
                        $constraint->aspectRatio();
                        // 防止图片被放大
                        $constraint->upsize();
                    });

                    // 图片精度只保留80%
                    $image->save($thumbPath, 80);
                }

                $file->name = $this->getThumbName($file->name, $size);
            }
        };

        return redirect($this->getRealDownloadUrl($file->name, $request->input('name', null)));
    }

    /**
     * 获取缩略图名称
     *
     * @param      $filename
     * @param null $size
     *
     * @return string
     */
    public function getThumbName($filename, $size = null)
    {
        if (!empty($size)) {
            $dotPos   = strrpos($filename, '.');
            $filename = substr($filename, 0, $dotPos) . "_{$size}"
                        . substr($filename, $dotPos);
        }

        return $filename;
    }

     /**
     * 获取文件存储目录
     *
     * @param string $filename
     * @param string $size
     *
     * @return string
     */
    public function getRealPath($filename, $size = null)
    {
        $filename = $this->getThumbName($filename, $size);

        return rtrim(env('UPLOAD_PATH'), '/') . $filename;
    }

    /**
     * 获取文件下载地址
     *
     * @param string $filename 文件实际存储路径
     * @param string $saveName 文件另存为文件名
     *
     * @return string
     */
    private function getRealDownloadUrl($filename, $saveName = null)
    {
        $storageUrl = rtrim(env('DOWNLOAD_URL'), '/') . $filename;
        if (!empty($saveName)) {
          $storageUrl = "{$storageUrl}?name={$saveName}";
        }

        return $storageUrl;
    }
}
