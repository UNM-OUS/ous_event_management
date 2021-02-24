<?php
namespace Digraph\Modules\ous_event_management\Chunks;

use ByJoby\ImageTransform\Image;
use ByJoby\ImageTransform\Sizers\Fit;
use Digraph\DSO\Noun;
use Digraph\Forms\Fields\ImageFieldSingle;

class GradPhotoField extends ImageFieldSingle
{
    public function dsoNoun($noun)
    {
        $this->noun = $noun;
        if ($files = $this->nounValue()) {
            //set up the clearing tip
            $this['upload']->addTip(
                $this->cms->helper('strings')->string('forms.file.tips.upload_clear_warning')
            );
            //set up options
            $opts = [];
            foreach ($files as $file) {
                $opts[$file->uniqid()] = '<img src="' . $file->imageUrl('signup-thumbnail') . '">';
            }
            $this['current']->opts($opts);
        }
    }

    public function hook_formWrite(Noun $noun, array $map)
    {
        $fs = $this->cms->helper('filestore');
        /*
        use the 'current' field to do any deletions
         */
        foreach ($this['current']->deleted() as $uniqid) {
            $fs->delete($noun, $uniqid);
        }
        /*
        use the 'current' field to set the order of the array in the filestore
        field of the noun
         */
        $arr = [];
        foreach ($this['current']->value() as $uniqid) {
            $arr[$uniqid] = $noun['filestore.' . $this->path . '.' . $uniqid];
        }
        unset($noun['filestore.' . $this->path]);
        $noun['filestore.' . $this->path] = $arr;
        $noun->update(true);
        /*
        save uploaded files to the noun using the filestore helper
         */
        if ($upload = $this['upload']->value()) {
            //only import file if value is an array, because this means it's a
            //new upload -- otherwise it's a FileStoreFile representing a file
            //that's already in the object
            if (is_array($upload)) {
                // move uploaded file to temp file with right name
                $srcFile = $upload['file'];
                $tmpFile = realpath(sys_get_temp_dir()) . '/' . uniqid() . '.' . $upload['name'];
                if (is_uploaded_file($srcFile)) {
                    @unlink($tmpFile);
                    if (!move_uploaded_file($srcFile, $tmpFile)) {
                        throw new \Exception("Failed to move uploaded file $srcFile to $tmpFile");
                    }
                } else {
                    if (!copy($srcFile, $tmpFile)) {
                        throw new \Exception("Failed to copy file $srcFile to $tmpFile");
                    }
                }
                // load original exif
                if (preg_match('/\.jpe?g$/',$tmpFile)) {
                    $exif = exif_read_data($tmpFile);
                }else {
                    $exif = [];
                }
                // image resizer
                $class = $this->cms->config['image-transform.driver.class'];
                $driver = new $class($this->cms->config['image-transform.driver.arg1']);
                $sizer = new Fit(1200, 1200);
                $image = new Image(
                    $tmpFile,
                    $driver,
                    $sizer
                );
                // do rotation based on orientation exif
                switch(@$exif['Orientation']) {
                    case 2:
                        $image->flipH();
                        break;
                      case 3:
                        $image->rotate(2);
                        break;
                      case 4:
                        $image->rotate(2);
                        $image->flipH();
                        break;
                      case 5:
                        $image->rotate(1);
                        $image->flipH();
                        break;
                      case 6:
                        $image->rotate(1);
                        break;
                      case 7:
                        $image->rotate(3);
                        $image->flipH();
                        break;
                      case 8:
                        $image->rotate(3);
                        break;
                      default:
                        //does nothing
                }
                // save to new temp file
                $image->save($tmpFile . '.jpg');
                $upload['file'] = $tmpFile . '.jpg';
                $upload['type'] = 'image/jpg';
                $upload['size'] = filesize($upload['file']);
                $upload['name'] = $noun['dso.id'] . '.jpg';
                // save into filestore
                $fs = $this->cms->helper('filestore');
                $fs->clear($this->noun, $this->path);
                $fs->import($this->noun, $upload, $this->path);
            }
        }
    }
}
