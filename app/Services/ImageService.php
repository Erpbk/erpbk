<?php 

namespace App\Services;
use Intervention\Image\Facades\Image as ResizeImage;
use Maestroerror\HeicToJpg;


class ImageService
{
    public function uploadLogo($request){

        $path = public_path('uploads/');
        $path_thumbnail = public_path('uploads/thumbnail/');
      
        !is_dir($path) && mkdir($path, 0777, true);
        !is_dir($path_thumbnail) && mkdir($path_thumbnail, 0777, true);

        $name = time() . '.' . $request->image_name->extension();

        $image_file = $request->file('image_name');

        $fileIsHeic = HeicToJpg::isHeic($request->file('image_name'));
        if ($fileIsHeic) {
            $name = rand(1,20).time() . '.jpg';
            HeicToJpg::convert($request->file('image_name'))->saveAs($path . $name);
            $image_file = $path . $name;
        }

        ResizeImage::make($image_file)
        ->resize(null , 120,function($img){
             $img->aspectRatio();
            })
        ->save($path . $name);
        
        ResizeImage::make($image_file)
        ->resize(null , 75,function($img){
             $img->aspectRatio();
            })
        ->save($path_thumbnail . $name);

       

       return $name;
    }

    public function uploadWithSize($request,$width,$height){

        $path = public_path('uploads/');
        $path_thumbnail = public_path('uploads/thumbnail/');
      
        !is_dir($path) && mkdir($path, 0777, true);
        !is_dir($path_thumbnail) && mkdir($path_thumbnail, 0777, true);

        $name = time() . '.' . $request->image_name->extension();

        $image_file = $request->file('image_name');

        $fileIsHeic = HeicToJpg::isHeic($request->file('image_name'));

        if ($fileIsHeic) {
            $name = rand(1,20).time() . '.jpg';
            HeicToJpg::convert($request->file('image_name'))->saveAs($path . $name);
            $image_file = $path . $name;
        }

        ResizeImage::make($image_file)
        ->resize($width ?? null, $height ?? null,function($img){
             $img->aspectRatio();
            })
        ->save($path . $name);
        
       /*  ResizeImage::make($image_file)
        ->resize(null , 75,function($img){
             $img->aspectRatio();
            })
        ->save($path_thumbnail . $name); */

       

       return $name;
    }

    public function uploadEvent($request){

        $path = public_path('uploads/');
        $path_thumbnail = public_path('uploads/thumbnail/');
      
        !is_dir($path) && mkdir($path, 0777, true);
        !is_dir($path_thumbnail) && mkdir($path_thumbnail, 0777, true);

        $name = time() . '.' . $request->image_name->extension();
        
        $image_file = $request->file('image_name');

        $fileIsHeic = HeicToJpg::isHeic($request->file('image_name'));
        if ($fileIsHeic) {
            $name = rand(1,20).time() . '.jpg';
            HeicToJpg::convert($request->file('image_name'))->saveAs($path . $name);
            $image_file = $path . $name;
        }

        ResizeImage::make($image_file)
        ->resize(null ,500,function($img){
             $img->aspectRatio();
            })
        ->save($path . $name);
        
        ResizeImage::make($image_file)
        ->resize(null ,355,function($img){
             $img->aspectRatio();
            })
        ->save($path_thumbnail . $name);

       
       return $name;
    }
    public function uploadImage($request){

        $path = public_path('uploads/');
      
        !is_dir($path) && mkdir($path, 0777, true);

        $name = time() . '.' . $request->image_name->extension();
        

        $image_file = $request->file('image_name');

        $fileIsHeic = HeicToJpg::isHeic($request->file('image_name'));
        if ($fileIsHeic) {
            $name = rand(1,20).time() . '.jpg';
            HeicToJpg::convert($request->file('image_name'))->saveAs($path . $name);
            $image_file = $path . $name;
        }

        ResizeImage::make($image_file)
        ->resize(null , 212,function($img){
             $img->aspectRatio();
            })
        ->save($path . $name);

        /* ResizeImage::make($request->file('image_name'))
            ->resize(800, null,function($img){
                 $img->aspectRatio();
                })
            ->save($path . $name); */

       return $name;
    }

        public function uploadGallery($request,$type,$type_id){
        
            //Images::where('type',$type)->where('type_id',$type_id)->delete();

            foreach($request->images as $image){

            $path = public_path('uploads/');
        
            !is_dir($path) && mkdir($path, 0777, true);

            $name = rand(1,20).time() . '.' . $image->extension();
            
            $image_file = $image;
            $fileIsHeic = HeicToJpg::isHeic($image);
            if ($fileIsHeic) {
                $name = rand(1,20).time() . '.jpg';
                HeicToJpg::convert($image)->saveAs($path . $name);
                $image_file = $path . $name;
            }
  
                ResizeImage::make($image_file)
                ->resize(null, 500,function($img){
                    $img->aspectRatio();
                    })
                ->save($path . $name);
                       

         /*  ResizeImage::make($request->file('image_name'))
            ->resize(350 , null,function($img){
                $img->aspectRatio();
                })
            ->save($path.'thumbnail/' . $name); */

            
            
        
            $image_data = [
                'type'=>$type,
                'type_id'=>$type_id,
                'image_name'=>$name
            ];

            Images::create($image_data);
            
          }
        return true;
      }
}


?>