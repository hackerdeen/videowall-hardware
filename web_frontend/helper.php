<?php
/*
 * This is a helper file for the videowall web interface
 * 
 * @author      AndyG - https://twitter.com/andygasman
 * @package     videowall
 * @copyright   Copyright 2016
 * @license     GNU Public License
 * @link        https://github.com/hackerdeen/videowall
 * @version     1.0.0
*/

# execution time, 300 seconds = 5 minutes
ini_set('max_execution_time', 300); 

# this is a general array for storing process info
$videowall_globals = array(
    "error_message" => "",
    "error" => FALSE,    
    "alert_message" => "",
    "alert_type" => "info",
    "debug" => "",
);

# fixed settings
define("OUTPUT_WIDTH", 2400);
define("OUTPUT_HEIGHT", 1800);


/*
function to process uploads
*/
function handle_upload() {

    global $videowall_globals;

    # if they DID upload a file...
    if($_FILES['image_upload']['name']){
        # if no errors...
        if(!$_FILES['image_upload']['error']){
            # now is the time to modify the future file name and validate the file
            $new_file_name = strtolower($_FILES['image_upload']['tmp_name']); 
            if($_FILES['image_upload']['size'] > (102400000))	{
                # too big
                $videowall_globals["error"] = TRUE;
                $videowall_globals["alert_message"] .= "Oops! Your file is size is to large.<br/>";
                $videowall_globals["alert_type"] = "danger";
                
            } else {
                $image_fn = 'image_data/raw/'.$_FILES['image_upload']['name'];
                
                # move it to where we want it to be
                move_uploaded_file($_FILES['image_upload']['tmp_name'], $image_fn);
                
                convert_to_size($image_fn);
                if (!$videowall_globals["error"]) {
                    # all good, now lets convert it to a datafile
                    convert_datafile( basename($image_fn) );
                } else {
                    $videowall_globals["error"] = TRUE;
                    $videowall_globals["alert_message"] .= "Oops! Your file is size is to large.<br/>";
                    $videowall_globals["alert_type"] = "danger";  
                }			
            }
        } else {
            # upload error
            $videowall_globals["error"] = TRUE;
            $videowall_globals["alert_message"] .= "Ooops!  Your upload triggered the following error: " . $_FILES['image_upload']['error'] . "<br/>";
            $videowall_globals["alert_type"] = "danger";              
        }
    }

    return;    
    
}    

/*
function to resize and crop images to the right size
*/
function convert_to_size ($image_fn) {
    
    global $videowall_globals;
    
    $output_width   = OUTPUT_WIDTH;
    $output_height  = OUTPUT_HEIGHT;
    $image_fn_file  = basename($image_fn);

    $target_output_width   = $output_width;
    $target_output_height  = $output_height;    
    
    $output_x       = 0;
    $output_y       = 0;
    $input_x        = 0; 
    $input_y        = 0; 
    
    list($input_width, $input_height) = getimagesize($image_fn);
    $input_ratio = $input_width / $input_height;
    
    $output_ratio = $output_width / $output_height;    
    
    $videowall_globals["debug"] .= "output_width: " . $output_width . "\n";
    $videowall_globals["debug"] .= "output_height: " . $output_height . "\n";
    $videowall_globals["debug"] .= "output_ratio: " . $output_ratio . "\n";
    
    $videowall_globals["debug"] .= "input_width: " . $input_width . "\n";
    $videowall_globals["debug"] .= "input_height: " . $input_height . "\n";
    $videowall_globals["debug"] .= "input_ratio: " . $input_ratio . "\n";
    
    # create an image resourse from the file
    $input_image_res = imagecreatefromjpeg($image_fn);
    
    if ($input_ratio > 1) {
        # ratio is more than 1, so it's a wide image
        $videowall_globals["debug"] .= "Wide image.\n";
    } else {
        # ratio is less than 1, so it's a tall image
        $videowall_globals["debug"] .= "Tall image.\n";
    }    
    
    if ($input_ratio > $output_ratio) {
        # ratio is...
        $videowall_globals["debug"] .= "Input is wider than the output.\n";

        $videowall_globals["debug"] .= "Resize it making the height " . $output_height . "\n";
        $output_width = $output_height * $input_ratio;
        $videowall_globals["debug"] .= "output_width: " . $output_width . "\n";        

    } else {
        # ratio is...
        $videowall_globals["debug"] .= "Input is taller than the output.\n";

        $videowall_globals["debug"] .= "Resize it making the width " . $output_width . "\n";
        $output_height = $output_width / $input_ratio;
        $videowall_globals["debug"] .= "output_height: " . $output_height . "\n";      
                
    }     

    # create an image resourse for the output file
    $output_image_res = imagecreatetruecolor($output_width, $output_height);   
    
    # resize it
    imagecopyresampled($output_image_res, $input_image_res, 0, 0, 0, 0, $output_width, $output_height, $input_width, $input_height);    
    
    $to_crop_array = array('x'=>0, 'y'=> 0, 'width'=>$target_output_width, 'height'=>$target_output_height);
    
    # crop the image down
    if ($output_width > $target_output_width) {
        $videowall_globals["debug"] .= "To wide.\n";
        $width_overflow = $output_width - $target_output_width;
        $to_crop_array['x'] = $width_overflow / 2;
        $output_image_res = imagecrop($output_image_res, $to_crop_array );
    } elseif ($output_height > $target_output_height) {
        $videowall_globals["debug"] .= "To tall.\n";    
        $height_overflow = $output_height - $target_output_height;
        $to_crop_array['y'] = $height_overflow / 2;        
        $output_image_res = imagecrop($output_image_res, $to_crop_array );
    } else {
        $videowall_globals["debug"] .= "Width and height are fine, no cropping required.\n";   
    }    

    $videowall_globals["debug"] .= "input_x: " . $input_x . "\n";
    $videowall_globals["debug"] .= "input_y: " . $input_y . "\n";
    $videowall_globals["debug"] .= "output_width: " . $output_width . "\n";
    $videowall_globals["debug"] .= "output_height: " . $output_height . "\n";
    $videowall_globals["debug"] .= "input_width: " . $input_width . "\n";
    $videowall_globals["debug"] .= "input_height: " . $input_height . "\n";
   
    $output_image_filename = "image_data/resized/" . $image_fn_file;
    imagejpeg($output_image_res, $output_image_filename);    

    $videowall_globals["alert_message"] .= "Resized OK<br/>";
    $videowall_globals["alert_type"] = "success";       
    
    return;
}



/*
create datafile
*/
function convert_datafile ($image_fn) {
    
    global $videowall_globals;

    $output_width   = OUTPUT_WIDTH;
    $output_height  = OUTPUT_HEIGHT;    

    # create an image resourse from the file        
    $full_image_fn = "image_data/resized/" . $image_fn;
    $image_res = imagecreatefromjpeg($full_image_fn);    
    
    # set data file stuff
    $full_datafile_fn = "image_data/datafile/" . $image_fn;
    $datafile_string = "";

    # create an image resourse for the preview file
    $full_preview_fn = "image_data/preview/" . $image_fn;    
    $preview_image_res = imagecreatetruecolor($output_width, $output_height); 

    $videowall_globals["debug"] .= "full_image_fn: " . $full_image_fn . "\n";
    
    #$image_imagick_obj = new Imagick($full_image_fn); 
    
    # for each column
    for ($pixel_y = 0; $pixel_y < $output_height; $pixel_y++) {
        # read along the row
        for ($pixel_x = 0; $pixel_x < $output_width; $pixel_x++) {

            # Read the pixel colour, http://php.net/manual/en/function.imagecolorat.php            
            $rgb = imagecolorat($image_res, $pixel_x, $pixel_y);
            $full_hex = dechex( $rgb );
            $redux_hex = substr( $full_hex, 0, 1) . substr( $full_hex, 2, 1) . substr( $full_hex, 4, 1);

            # Read the pixel colour, alt option, with imagick http://php.net/manual/en/imagick.getimagepixelcolor.php
            #$pixel = $image_imagick_obj->getImagePixelColor($pixel_x, $pixel_y); 
            #$colors = $pixel->getColor();
            #$redux_hex = substr( $colors['r'], 0, 1) . substr( $colors['g'], 0, 1) . substr( $colors['b'], 0, 1);
            
            #$videowall_globals["debug"] .= "pixel: " . $pixel_x . "x" . $pixel_y . " is " . $rgb . "\n";

            # write the string to the datafile
            $datafile_string .= $redux_hex . ",";
            
            # write the preview data, pad it out to 6 digit hex and convert it to decimal
            $dec_color = hexdec( substr($redux_hex, 0, 1) . "0" . substr($redux_hex, 1, 1) . "0" . substr($redux_hex, 2, 1) . "0" );
            imagesetpixel($preview_image_res, $pixel_x, $pixel_y, $dec_color);
            
        }
        $datafile_string .= "\n";
    }
    $fh = fopen($full_datafile_fn, 'w') or die();
    fwrite($fh, $datafile_string);
    fclose($fh);  


    # output the preview
    imagejpeg($preview_image_res, $full_preview_fn); 
    
    return;
}





/*
delete a single image
*/
function delete_one() {
    
    global $videowall_globals;
    
    $file_to_delete = $_GET["image"];
    
    unlink("image_data/resized/" . $file_to_delete);
    unlink("image_data/raw/" . $file_to_delete);
    unlink("image_data/datafile/" . $file_to_delete);
    unlink("image_data/preview/" . $file_to_delete);    
    
    $videowall_globals["alert_message"] .= "Deleted " . $file_to_delete;
    $videowall_globals["alert_type"] = "success";     
    
    return;
}


?>
