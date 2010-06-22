<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - Upload image
	@version 1.2
	@author Domas Labokas domas@htg.lt
	@date 2009 04 03
	@see http://www.swampyfoot.com
	@copyright 2009 SwampyFoot
	@license SwampyBrowser is licensed under a Creative Commons Attribution-Noncommercial 3.0
	@license http://creativecommons.org/licenses/by-nc/3.0/
**/

include('../configs.php');
include("../lang/$LANG.php");
include('common.php');

$dir = $_POST['dir'];
$directory = $PUBLIC_HTML_DIR.$dir;

if($dir == "")
	die(msg('alert', $DLG['select_dir']));

if(!is_writable($directory))
	die(msg('error', $DLG['no_permission']));

if(!validate_path($directory))
	die(msg('error', $DLG['invalid_dir']." <b>($dir)</b>"));

if($_POST['upload'])
{
	//cheking is file selected
	if($_FILES['file']['name']=="")
		die(stopUpload('alert', $DLG['select_file']));

	//geting file/path info
	$info = swampy_pathinfo($_FILES['file']['name']);
	$filename = ($_POST['filename'] != "") ? $_POST['filename'] : $info['filename'];
	$filename = format_filename($filename);

	//cheking file name
	if(ereg("[^a-zA-Z0-9._-]", $filename))
		die(stopUpload('alert',$DLG['invalid_filename']));

	//check is file name exists
	if(is_filename_exists($directory, $filename))
		die(stopUpload('alert', $DLG['file_exists']));

	$src_path = $_FILES['file']['tmp_name'];	// Source image path from uploaded file
	$image_info = getimagesize($src_path);		// Geting source image info width height type

	// Selecting operation to load image by its type
	switch($image_info[2])
	{
		case 1:// GIF
			$extension = ".gif";
			$src_image = @ ImageCreateFromGIF ($src_path);
			break;
		case 2:// JPEG
			$extension = ".jpg";
			$src_image = @ ImageCreateFromJPEG ($src_path);
			break;
		case 3:// PNG
			$extension = ".png";
			$src_image = @ ImageCreateFromPNG ($src_path);
			break;
		default:
			die(stopUpload('error', $DLG['invalid_img']));
			break;
	}

	//checking is image loaded
	if(!$src_image)
		die(stopUpload('error', $DLG['invalid_image']));


	$IMAGE_FORMATS['custom'] = $_POST['format'];	// Adds custom format to IMAGE FORMAT list from configure file
	array_push($_POST['formats'], "mthumb");	// Adds manager thumb format

	//seting orginal width and height from image info
	$src_w = $image_info[0]; //width
	$src_h = $image_info[1]; //height
	$src_ratio = $src_w / $src_h;		//source image width/height ratio

	umask(0002);

	//making images by selected formats
	foreach($_POST['formats'] as $fid)
	{
		$format = $IMAGE_FORMATS[$fid];

		if(!$format['width'] && $format['height'])
			 $format['width'] = round($format['height'] * $src_ratio);

		if($format['width'] && !$format['height'])
			 $format['height'] = round($format['width'] / $src_ratio);

		$width = $format['width'] ? $format['width'] : $src_w;
		$height = $format['height'] ? $format['height'] : $src_h;
		$dst_x = $dst_y = $src_x = $src_y = 0;
		$format_ratio = $width / $height;	//image format ratio

		//formating image parameter by scale type
		switch($format['scale'])
		{
			case "crop":
				$dst_w = ($format_ratio < $src_ratio) ? round($height * $src_ratio) : $width;
				$dst_h = ($format_ratio > $src_ratio) ? round($width / $src_ratio) : $height;
				$dst_x = ($width/2) - ($dst_w/2);
				$dst_y = ($height/2) - ($dst_h/2);
				break;
			case "addbg":
				$dst_w = ($format_ratio > $src_ratio) ? round($height * $src_ratio) : $width;
				$dst_h = ($format_ratio < $src_ratio) ? round($width / $src_ratio) : $height;
				$dst_x = ($width/2) - ($dst_w/2);
				$dst_y = ($height/2) - ($dst_h/2);
				break;
			case "max":
				$width = $dst_w = ($format_ratio > $src_ratio) ? round($height * $src_ratio) : $width;
				$height = $dst_h = ($format_ratio < $src_ratio) ? round($width / $src_ratio) : $height;
				break;
			case "stretch":
				$dst_w = $width;
				$dst_h = $height;
				break;
			case "orginal":
			default:
				$width = $dst_w = $src_w;
				$height = $dst_h = $src_h;
				break;
		}
		$dst_image = imageCreateTrueColor($width, $height);

		//creates image backgound color
		if($format['scale'] == "addbg")
		{
			$r = hexdec(substr($format['bg'],0,2));
			$g = hexdec(substr($format['bg'],2,2));
			$b = hexdec(substr($format['bg'],4,2));
			$color = imagecolorallocate($dst_image, $r, $g, $b);
			imagefill($dst_image, 0, 0, $color);
		}

		imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

		//image masking
		if($format['mask'])
		{
			$mask_image = ImageCreateFromPNG($PUBLIC_HTML_DIR.$format['mask']);
			imagealphablending($mask_image, 1);
			imagecopy($dst_image, $mask_image, 0, 0, 0, 0, $dst_w, $dst_h);
		}

		if(!file_exists($directory.$format['dir']))			//Creating directory if needed
			mkdir($directory.$format['dir'], 0775);

		$ext = $format['ext'] ? ".".$format['ext'] : $extension;	//seting extension format
		$file = $filename.$ext;						//seting full file name
		$dst_path = $directory.$format['dir'].$file;			//image destination path

		switch($ext)	//creating image by selected extension format
		{
			case ".jpg":
				imagejpeg($dst_image, $dst_path);
				break;
			case ".png":
				imagepng($dst_image, $dst_path, 9);
				break;
			case ".gif":
				imagegif($dst_image, $dst_path);
				break;
		}
		imagedestroy($dst_image);	//frees any memory associated with destination image image
	}

	$file = $filename.$extension;
	$msg = rawurlencode("<b>'$filename'</b> {$DLG['upload_success']}<br><a href=\"javascript:browser.insertImage('$file');\">{$DLG['insert_uploaded']}</a>");
	die(stopUpload('done', $msg));
}
?>
<form id="upload_form" action="scripts/upload_image.php" method="post" enctype="multipart/form-data" target="upload_target" onsubmit="browser.upload.start(this);" >
	<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
	<p class="info" ><?php echo $DLG['select_to_upload']; ?></p>
	<table>
		<tr><td><?php echo $DLG['dir'];?>: </td><td><input type="input" name="dir" value="<?php echo $dir;?>" readonly/></td></tr>
		<tr><td><?php echo $DLG['file_to_upload'];?>:</td><td><input class="file_input" type="file"   name="file"  accept="image/*,image/jpg,image/png,image/gif" /></td></tr>
		<tr><td><?php echo $DLG['as_file_name'];?>:</td><td><input type="input"   name="filename" /></td></tr>
	</table>
	<hr>
	<p class="info" ><?php echo $DLG['select_img_format'];?></p>
	<table width="100%">
		<tr>
			<th>&nbsp;</th>
			<th><?php echo $DLG['format'];?></th>
			<th><?php echo $DLG['width'];?></th>
			<th><?php echo $DLG['height'];?></th>
			<th><?php echo $DLG['scale_type'];?></th>
			<th><?php echo $DLG['bg'];?></th>
		<tr>
		<tr>
			<td><input type="radio" name="formats[]" value="custom"/></td>
			<td><?php echo $DLG['custom'];?></td>
			<td><input type="text" name="format[width]" size="4" value="0"/>px</td>
			<td><input type="text" name="format[height]" size="4" value="0"/>px</td>
			<td>
				<select name="format[scale]">
				<?php
					foreach($SCALE_TYPES as $type => $title)
						echo "<option value='$type'>$title</option>";
				?>
				</select>
			</td>
			<td>#<input type="text" name="format[bg]" size="5" value="FFFFFF"/></td>
		</tr>
		<?php
			array_shift($IMAGE_FORMATS);
		
			foreach($IMAGE_FORMATS as $id => $format)
			{
				$options = ($id == "orginal") ? "type='radio' checked" : "type='checkbox'";
		
				echo "<tr>";
				echo "<td><input $options name='formats[]' value='$id'/></td>";
				echo "<td>{$format['title']}</td>";
				echo "<td>{$format['width']}</td>";
				echo "<td>{$format['height']}</td>";
				echo "<td>".$SCALE_TYPES[$format['scale']]."</td>";
				echo "<td><div style='background:#{$format['bg']};border:1px solid black;width:16px;height:16px;'></div></td>";
				echo "</tr>";
			}
		?>
	</table>
	<hr>
	<input type="submit" name="upload" value="<?php echo $DLG['upload'];?>" />
	<div id="upload_messages"></div>
</form>
<iframe id="upload_target" name="upload_target" src="#" style="width:0px;height:0px;border:0px solid #fff;"></iframe>
<div id="loading_form" style="text-align:center;display:none;">
	<p><?php echo $DLG['uploading'];?>...</p>
	<p><img src='styles/images/file-loader.gif' align='middle'/></p>
</div>
