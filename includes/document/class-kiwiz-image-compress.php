<?php
/**
 * Kiwiz
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at the following URI:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the PHP License and are unable to
 * obtain it through the web, please send a note to contact@kiwiz.io
 * so we can mail you a copy immediately.
 *
 * @author Kiwiz <contact@kiwiz.io>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


defined( 'ABSPATH' ) || exit;

/**
 * Class Kiwiz_Image_Compress
 */

class Kiwiz_Image_Compress {

    protected $file_url;
    protected $new_name_image;
    protected $quality;
    protected $pngQuality;
    protected $destination;
    protected $image_size;
    protected $image_data;
    protected $image_mime;
    protected $array_img_types;

    public function __construct($file_url, $new_name_image, $quality, $pngQuality, $destination = null) {
        $this->set_file_url($file_url);
        $this->set_new_name_image($new_name_image);
        $this->set_quality($quality);
        $this->set_destination($destination);
    }
    function get_file_url() {
        return $this->file_url;
    }
    function get_new_name_image() {
        return $this->new_name_image;
    }
    function get_quality() {
        return $this->quality;
    }
    function get_pngQuality() {
        return $this->pngQuality;
    }
    function set_file_url($file_url) {
        $this->file_url = $file_url;
    }
    function set_new_name_image($new_name_image) {
        $this->new_name_image = $new_name_image;
    }
    function set_quality($quality) {
        $this->quality = $quality;
    }
    function set_pngQuality($pngQuality) {
        $this->pngQuality = $pngQuality;
    }

    function get_destination() {
        return $this->destination;
    }
    function set_destination($destination) {
        $this->destination = $destination;
    }

    /**
     * Function to compress image
     * @return boolean
     * @throws Exception
     */
    public function compress_image(){

        //Send image array
        $array_img_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
        $new_image = null;
        $last_char = null;
        $image_extension = null;
        $destination_extension = null;
        $png_compression = null;
        $maxsize = 5245330;

        try{

            //If not found the file
            if(empty($this->file_url) && !file_exists($this->file_url)){
                //throw new Exception('Please inform the image!');
                return false;
            }

			$wp_upload_dir = wp_upload_dir();
			$full_path = str_replace($wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $this->file_url);
			$this->set_file_url( $full_path );

            //Get image width, height, mimetype, etc..
            $image_data = getimagesize($this->file_url);
            //Set MimeType on variable
            $image_mime = $image_data['mime'];

            //Verifiy if the file is a image
            if(!in_array($image_mime, $array_img_types)){
                //throw new Exception('Please send a image!');
                return false;
            }

            //Get file size
            $image_size = @filesize($this->file_url);

            //if image size is bigger than 5mb
            if($image_size >= $maxsize){
                //throw new Exception('Please send a imagem smaller than 5mb!');
                return false;
            }

            //If not found the destination
            if(empty($this->new_name_image)){
                //throw new Exception('Please inform the destination name of image!');
                return false;
            }

            //If not found the quality
            if(empty($this->quality)){
                //throw new Exception('Please inform the quality!');
                return false;
            }
            //If not found the png quality
            $png_compression = (!empty($this->pngQuality)) ? $this->pngQuality : 9 ;

            $image_extension = pathinfo($this->file_url, PATHINFO_EXTENSION);
            //Verify if is sended a destination file name with extension
            $destination_extension = pathinfo($this->new_name_image, PATHINFO_EXTENSION);
            //if empty
            if(empty($destination_extension)){
                $this->new_name_image = $this->new_name_image.'.'.$image_extension;
            }

            //Verify if folder destination isn´t empty
            if(!empty($this->destination)){

                //And verify the last one element of value
                $last_char = substr($this->destination, -1);

                if($last_char !== '/'){
                    $this->destination = $this->destination.'/';
                }
            }

            //Switch to find the file type
            switch ($image_mime){
                //if is JPG and siblings
                case 'image/jpeg':
                case 'image/pjpeg':
                    //Create a new jpg image
                    $new_image = imagecreatefromjpeg($this->file_url);
                    imagejpeg($new_image, $this->destination.$this->new_name_image, $this->quality);
                    break;
                //if is PNG and siblings
                case 'image/png':
                case 'image/x-png':
                    //Create a new png image
                    $new_image = imagecreatefrompng($this->file_url);
                    imagealphablending($new_image , false);
                    imagesavealpha($new_image , true);
                    imagepng($new_image, $this->destination.$this->new_name_image, $png_compression);
                    break;
                // if is GIF
                case 'image/gif':
                    //Create a new gif image
                    $new_image = imagecreatefromgif($this->file_url);
                    imagealphablending($new_image, false);
                    imagesavealpha($new_image, true);
                    imagegif($new_image, $this->destination.$this->new_name_image);
            }

        } catch (Exception $ex) {
            return $ex->getMessage();
        }

        //Return the new image resized
        return $this->new_name_image;

    }
}
